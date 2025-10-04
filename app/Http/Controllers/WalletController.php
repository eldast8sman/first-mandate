<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundWalletRequest;
use App\Models\CustomerFlutterwaveToken;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FlutterwaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    private $user;
    private $flutterwave;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
        $this->flutterwave = new FlutterwaveController();
    }

    public function fetch_banks(){
        // $banks = Cache::remember('flutterwave_banks', 60*60*24*30, function() {
        //     return $this->flutterwave->get_banks();
        // });

        // return response([
        //     'status' => 'success',
        //     'message' => 'Banks fetched successfully',
        //     'data' => $banks
        // ], 200);
        $banks = $this->flutterwave->get_banks();
        if(!$banks){
            return response([
                'status' => 'failed',
                'message' => $this->flutterwave->errors
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Banks fetched successfully',
            'data' => $banks
        ], 200);
    }

    public function fetch_wallet_balance(){
        $wallet = Wallet::where('user_id', $this->user->id)->first();
        if(empty($wallet)){
            $uuid = Str::uuid();
            $uuid = $uuid."-".time();
            $wallet = Wallet::create([
                'user_id' => $this->user->id,
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

        return response([
            'status' => 'success',
            'message' => 'Wallet fetched successfully',
            'data' => $wallet
        ], 200);
    }

    public function fund_wallet(FundWalletRequest $request){
        $wallet = Wallet::where('user_id', $this->user->id)->first();
        if(empty($wallet)){
            $uuid = Str::uuid();
            $uuid = $uuid."-".time();
            $wallet = Wallet::create([
                'user_id' => $this->user->id,
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

        $tranx = WalletTransaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $request->amount,
            'pre_amount' => $wallet->balance,
            'status' => 0
        ]);

        $tx_ref = 'FLW_WLT_'.Str::random(6).'_'.Str::random(6).'_'.time();

        $flw = new FlutterwaveController();
        if($request->payment_method == 'new'){
            $charge = $flw->initiate_payment('user', $this->user->id, $this->user->id, $tx_ref, 'wallet_transaction', $tranx->id, 'NGN', $request->amount, $this->user, $request->redirect_url);
            if(!$charge){
                $tranx->status = 2;
                $tranx->save();
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
            if(!$charge = $flw->token_charge($token->token, 'user', $this->user->id, $this->user->id, $tx_ref, 'wallet_transaction', $tranx->id, 'NGN', $request->amount, $this->user)){
                $tranx->status = 2;
                $tranx->save();
                return response([
                    'status' => 'failed',
                    'message' => $flw->errors
                ], 500);
            }

            return response([
                'status' => 'success',
                'message' => 'Wallet funded successfully',
                'data' => Wallet::where('user_id', $this->user->id)->first(['balance', 'total_credit', 'total_debit'])
            ], 200);
        }
    }

    public function cards(){
        $tokens = CustomerFlutterwaveToken::where('user_id', $this->user->id);
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

//     public function fetch_banks(){
//         $banks = Cache::remember('flutterwave_banks', 60*60*24*30, function() {
//             return $this->flutterwave->get_banks();
//         });

//         return response([
//             'status' => 'success',
//             'message' => 'Banks fetched successfully',
//             'data' => $banks
//         ], 200);
//     }
}
