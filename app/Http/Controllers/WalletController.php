<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\ResolveAccountNumberRequest;
use App\Http\Requests\SetAccountDetailsRequest;
use App\Http\Requests\WithdrawFundsRequest;
use App\Models\CustomerFlutterwaveToken;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    private $user;
    private $flutterwave;
    private $transfer_fee_type = 'flat'; //flat or percentage
    private $transfer_fee = 100; //in naira
    private $transfer_fee_cap = 0; //in naira, 0 for no cap
    private $deposit_fee_type = 'percentage'; //flat or percentage
    private $deposit_fee = 2.1;
    private $deposit_fee_cap = 2000; //in naira

    public function __construct()
    {
        $this->user = AuthController::user();
        $this->flutterwave = new FlutterwaveController();
    }

    public function fetch_banks(){
        $banks = Cache::remember('flutterwave_banks', 60*60*24*30, function() {
            return $this->flutterwave->get_banks();
        });

        return response([
            'status' => 'success',
            'message' => 'Banks fetched successfully',
            'data' => $banks
        ], 200);
    }

    public function confirm_wallet($user_id="")
    {
        if(empty($user_id)){
            $user_id = $this->user->id;
        }
        if(empty($wallet = Wallet::where('user_id', $user_id)->first())){
            $uuid = Str::uuid();
            $uuid = $uuid."-".time();
            $wallet = Wallet::create([
                'user_id' => $user_id,
                'uuid' => $uuid,
                'balance' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'bank' => '',
                'bank_code' => '',
                'account_number' => '',
                'account_name' => ''
            ]);
        }
        return $wallet;
    }

    public function fetch_wallet_balance(){
        $this->confirm_wallet();
        $wallet = Wallet::where('user_id', $this->user->id)->first();

        return response([
            'status' => 'success',
            'message' => 'Wallet fetched successfully',
            'data' => $wallet
        ], 200);
    }

    public function fund_wallet(FundWalletRequest $request){
        $this->confirm_wallet();
        $flw = new FlutterwaveController();
        if($request->payment_method == 'new'){
            $charge = $flw->initiate_payment($this->user, $request->amount);
            if(!$charge){
                return response([
                    'status' => 'failed',
                    'message' => $flw->errors
                ], 500);
            }
            return response([
                'status' => 'success',
                'message' => 'Payment Link successfully generated',
                'data' => $charge
            ], 200);
        } elseif($request->payment_method == 'old'){
            $token = CustomerFlutterwaveToken::where('id', $request->card_id)->where('user_id', $this->user->id)->first();
            if(empty($token)){
                return response([
                    'status' => 'failed',
                    'message' => 'Wrong Card'
                ], 409);
            }
            if(!$charge = $flw->token_charge($token->token, $this->user, $request->amount, $token->email)){
                return response([
                    'status' => 'failed',
                    'message' => $flw->errors
                ], 500);
            }

            if($charge['status'] == 'pending'){
                return response([
                    'status' => 'success',
                    'message' => 'Redirecting to payment processor',
                    'data' => [
                        'link' => $charge['redirect_link']
                    ]
                ], 200);
            } else {
                return response([
                    'status' => 'success',
                    'message' => 'Wallet funded successfully',
                    'data' => Wallet::where('user_id', $this->user->id)->first(['balance', 'total_credit', 'total_debit'])
                ], 200);
            }
        }
    }

    public function credit_wallet($transaction_id){
        $transaction = Transaction::find($transaction_id);
        if($transaction->value_given != 1){
            $wallet = Wallet::where('user_id', $transaction->user_id)->first();
            if(!empty($wallet)){
                $original_amount = $transaction->amount;
                if($this->deposit_fee_type == 'flat'){
                    $charges = $this->deposit_fee;
                } else {
                    $charges = ($this->deposit_fee / 100) * $original_amount;
                    if($this->deposit_fee_cap > 0 and $charges > $this->deposit_fee_cap){
                        $charges = $this->deposit_fee_cap;
                    }
                }
                $amount = $original_amount - $charges;

                $wallet->balance = $wallet->balance + $amount;
                $wallet->total_credit = $wallet->total_credit + $original_amount;
                $wallet->total_debit = $wallet->total_debit + $charges;
                $wallet->save();

                $tranx = WalletTransaction::create([
                    'user_id' => $transaction->user_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'original_amount' => $original_amount,
                    'charges' => $charges,
                    'amount' => $amount,
                    'pre_amount' => $wallet->balance - $amount,
                    'post_amount' => $wallet->balance,
                    'amount' => $amount,
                    'remarks' => 'Wallet transaction funded via '.$transaction->trans_reference,
                    'status' => 1
                ]);

                $transaction->value_given = 1;
                $transaction->event_id = $tranx->id;
                $transaction->save();
            }
        }
    }

    public function verify_payment($trans_id){
        if(!$verify = $this->flutterwave->verify_payment($trans_id)){
            return response([
                'status' => 'failed',
                'message' => $this->flutterwave->errors
            ], 500);
        }

        $this->credit_wallet($verify->id);

        return response([
            'status' => 'success',
            'message' => 'Payment verified successfully',
            'data' => Wallet::where('user_id', $this->user->id)->first(['balance', 'total_credit', 'total_debit'])
        ], 200);
    }

    public function cards(){
        $tokens = CustomerFlutterwaveToken::where('user_id', $this->user->id)->where('token_expiry', '>=', date('Y-m-d'));
        if($tokens->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Card was fetched'
            ], 200);
        }
        $tokens = $tokens->get(['id', 'first_digits', 'last_digits', 'card_type', 'card_expiry']);
        
        return response([
            'status' => 'success',
            'message' => 'Payment Cards fetched succesfully',
            'data' => $tokens
        ], 200);
    }

    public function remove_card($id){
        $token = CustomerFlutterwaveToken::where('id', $id)->where('user_id', $this->user->id)->first();
        if(empty($token)){
            return response([
                'status' => 'failed',
                'message' => 'No Payment Card was fetched'
            ], 404);
        }

        $token->delete();
        return response([
            'status' => 'success',
            'message' => 'Payment Card was deleted successfully'
        ], 200);
    }

    public function resolve_account_number(ResolveAccountNumberRequest $request){
        if(!$data = $this->flutterwave->verify_account($request->account_number, $request->bank_code)){
            return response([
                'status' => 'failed',
                'message' => $this->flutterwave->errors
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Account resolved successfully',
            'data' => [
                'account_name' => $data->account_name
            ]
        ]);
    }

    public function set_account_details(SetAccountDetailsRequest $request){
        $this->confirm_wallet();
        $wallet = Wallet::where('user_id', $this->user->id)->first();

        if(!Hash::check($request->password, $this->user->password)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Password'
            ], 401);
        }

        $wallet->update($request->only(['bank', 'bank_code', 'account_number', 'account_name']));

        return response([
            'status' => 'success',
            'message' => 'Account details set successfully',
            'data' => $wallet
        ], 200);
    }

    public function initiate_bvn_consent(Request $request){
        $validate = $request->validate([
            'bvn' => 'required|string|size:11'
        ]);
        if(!$validate){
            return response([
                'status' => 'failed',
                'message' => 'Validation Failed'
            ], 422);
        }

        $name = explode(' ', $this->user->name);
        $first_name = $name[0];
        $last_name = isset($name[1]) ? $name[1] : '';

        if(!$data = $this->flutterwave->initiate_bvn_consent($validate['bvn'], $first_name, $last_name)){
            return response([
                'status' => 'failed',
                'message' => $this->flutterwave->errors
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'BVN Consent Initiated Successfully',
            'data' => $data
        ], 200);
    }

    public function withdraw_funds(WithdrawFundsRequest $request){
        $this->confirm_wallet();
        $wallet = Wallet::where('user_id', $this->user->id)->first();

        if(!Hash::check($request->password, $this->user->password)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Password'
            ], 401);
        }

        if($request->amount > $wallet->balance){
            return response([
                'status' => 'failed',
                'message' => 'Insufficient Wallet Balance'
            ], 409);
        }

        if(empty($wallet->bank) or empty($wallet->bank_code) or empty($wallet->account_number) or empty($wallet->account_name)){
            return response([
                'status' => 'failed',
                'message' => 'You need to set your bank account details before you can withdraw funds'
            ], 409);
        }

        $original_amount = $request->amount;
        if($this->transfer_fee_type == 'flat'){
            $charges = $this->transfer_fee;
        } else {
            $charges = ($this->transfer_fee / 100) * $original_amount;
            if($this->transfer_fee_cap > 0 and $charges > $this->transfer_fee_cap){
                $charges = $this->transfer_fee_cap;
            }
        }
        $amount = $original_amount + $charges;
        if($amount > $wallet->balance){
            return response([
                'status' => 'failed',
                'message' => 'Insufficient Wallet Balance to cover transfer charges'
            ], 409);
        }

        $reference = 'FLW_WTDRW_'.Str::upper(Str::random(10)).'_'.time();
        if(!$tranx = $this->flutterwave->transfer($this->user, $wallet->account_number, $wallet->bank_code, $request->amount, $reference)){
            return response([
                'status' => 'failed',
                'message' => $this->flutterwave->errors
            ], 500);
        }

        $wallet->balance = $wallet->balance - $amount;
        $wallet->total_debit = $wallet->total_debit + $amount;
        $wallet->save();

        $trans = WalletTransaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'original_amount' => $original_amount,
            'charges' => $charges,
            'amount' => $amount,
            'pre_amount' => $wallet->balance + $amount,
            'post_amount' => $wallet->balance,
            'remarks' => 'Wallet withdrawal initiated via Flutterwave. Transfer Reference: '.$tranx->trans_reference,
            'status' => 1
        ]);

        $tranx->event_id = $trans->id;
        $tranx->save();

        return response([
            'status' => 'success',
            'message' => 'Withdrawal Initiated Successfully',
            'data' => [
                'wallet' => $wallet,
                'transfer' => $trans
            ]
        ], 200);
    }

    public function transaction_history(Request $request){
        $limit = $request->has('limit') ? (int)$request->limit : 10;
        $type = $request->has('type') ? $request->type : '';
        $from = $request->has('from') ? $request->from : '';
        $to = $request->has('to') ? $request->to : '';

        $transactions = WalletTransaction::where('user_id', $this->user->id);
        if(!empty($type) and in_array($type, ['debit', 'credit'])){
            $transactions = $transactions->where('type', $type);
        }
        if(!empty($from)){
            $transactions = $transactions->whereDate('created_at', '>=', $from.' 00:00:00');
        }
        if(!empty($to)){
            $transactions = $transactions->whereDate('created_at', '<=', $to.' 23:59:59');
        }

        $transactions = $transactions->orderBy('id', 'desc')->paginate($limit);
        return response([
            'status' => 'success',
            'message' => 'Transaction history fetched successfully',
            'data' => $transactions
        ], 200);
    }
}
