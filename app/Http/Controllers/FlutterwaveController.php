<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Models\FlutterwaveWebhook;
use App\Models\CustomerFlutterwaveToken;
use App\Models\ElectricityBillPayment;

class FlutterwaveController extends Controller
{
    protected $secret_key;
    protected $public_key;
    protected $base_url;
    protected $secret_hash;
    public $errors;


    public function __construct()
    {
        if(env('FLW_ENV') == 'DEVELOPMENT'){
            $this->secret_key = env('FLW_SECRET_KEY_DEV');
            $this->public_key = env('FLW_PUBLIC_KEY_DEV');
            $this->secret_hash = env('FLW_SECRET_HASH_DEV');
        } elseif(env('FLW_ENV') == 'PRODUCTION'){
            $this->secret_key = env('FLW_PUBLIC_KEY_PROD');
            $this->public_key = env('FLW_PUBLIC_KEY_PROD');
            $this->secret_hash = env('FLW_SECRET_HASH_PROD');
        }
        $this->base_url = env('FLW_BASE_URL');
    }

    private function perform_post_curl($url, $payload){
        $url = $this->base_url.$url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 200);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', 
            'Authorization: Bearer '.$this->secret_key
        ]);
                        
        $request = curl_exec($ch);

        $this->errors = $request;
        
        if($request){
            $result = json_decode($request);
            if($result){
                return $result;
            } else {
                $this->errors = $request;
                return false;
            }
        } else {
            if(curl_error($ch)){
                $this->errors = 'FLW Error: ' . curl_error($ch);
            } else {
                $this->errors = "Bank Connection Failed";
            }
            return false;
        }
        curl_close($ch);
    }

    private function perform_get_curl($url){
        $url = $this->base_url.$url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', 
            'Authorization: Bearer '.$this->secret_key
        ]);
        $request = curl_exec($ch);
        
        
        if($request){
            $result = json_decode($request);
            if($result){
                return $result;
            } else {
                $this->errors = $request;
                return false;
            }
        } else {
            if(curl_error($ch)){
                $this->errors = 'FLW Error: ' . curl_error($ch);
            } else {
                $this->errors = "Bank Connection Failed";
            }
            return false;
        }
		curl_close($ch);
    }

    public function initiate_payment($user, $amount){
        $url = '/payments';
        $reference = 'FLW_'.Str::random(6).'_'.Str::random(6).'_'.time();
        $payload = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'redirect_url' => env('FRONTEND_URL').'/payments/verify',
            'currency' => 'NGN',
            'customer' => [
                'name' => $user->name,
                'email' => $user->email
            ],
            'customizations' => [
                'title' => '1stMandate Payments',
                'logo' => 'https://#'
            ]
        ];

        $initiate = $this->perform_post_curl($url, $payload);
        if(!$initiate){
            return false;
        }

        if($initiate->status != "success"){
            $this->errors = $initiate->message;
            return false;
        }

        Transaction::create([
            'user_type' => 'user',
            'user_type_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'trans_reference' => $reference,
            'transaction_id' => rand(000000, 999999),
            'currency' => 'NGN',
            'amount' => $amount,
            'platform' => 'Flutterwave',
            'request' => json_encode($payload),
            'response1' => json_encode($initiate),
            'status' => 0,
            'event' => 'wallet_transaction',
            'event_id' => rand(000000, 999999),
            'value_given' => 0
        ]);

        return $initiate->data;
    }

    public function token_charge($token, $user, $amount, $email){
        $url = '/tokenized-charges';
        $name = $user->name;
        $name_array = explode(' ', $name);
        $first_name = $name_array[0];
        $last_name = $name_array[1];
        $reference = 'FLW_'.Str::random(6).'_'.Str::random(6).'_'.time();
        $data = [
            'token' => $token,
            'email' => $email,
            'currency' => 'NGN',
            'amount' => $amount,
            'tx_ref' => $reference,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'redirect_url' =>'https://app.1stmandate.com/payments/verify/old-card'
        ];

        if(!$charge = $this->perform_post_curl($url, $data)){
            return false;
        }

        if($charge->status != 'success'){
            $this->errors = $charge->message;
            return false;
        }

        $tranx = Transaction::create([
            'user_type' => 'user',
            'user_type_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'trans_reference' => $reference,
            'transaction_id' => rand(000000, 999999),
            'currency' => 'NGN',
            'amount' => $amount,
            'platform' => "Flutterwave",
            'request' => json_encode($data),
            'response1' => json_encode($charge),
            'status' => 0,
            'event' => 'wallet_transaction',
            'event_id' => rand(000000, 999999),
            'value_given' => 0
        ]);

        $charge_data = $charge->data;

        if($charge_data->status == 'successful'){
            if(!$this->verify_payment($charge_data->id)){
                $this->errors = "Failed Transaction";
                $tranx->status = 2;
                $tranx->save();
                return false;
            } else {
                return [
                    'status' => 'successful',
                    'redirect_link' => null
                ];
            }
        } elseif($charge_data->status === 'pending'){
            if(!empty($charge_data->meta) and !empty($charge_data->meta->authorization) and !empty($charge_data->meta->authorization->redirect)){
                return [
                    'status' => 'pending',
                    'redirect_link' => $charge_data->meta->authorization->redirect
                ];
            }
        } else {
            $this->errors = "Failed Transaction";
            $tranx->status = 2;
            $tranx->save();
            return false;
        }
    }

    public function verify_payment($trans_id){
        $url = "/transactions/{$trans_id}/verify";
        if(!$verify = $this->perform_get_curl($url)){
            return false;
        }
        if($verify->status != "success"){
            $this->errors = $verify->message;
            return false;
        }

        $data = $verify->data;
        $tranx = Transaction::where('trans_reference', $data->tx_ref)->first();
        if(empty($tranx)){
            $this->errors = "No Transaction was fetched";
            return false;
        }

        $tranx->response2 = json_encode($verify);
        $tranx->transaction_id = $trans_id;
        $tranx->save();

        if($data->status != "successful"){
            $tranx->status - 2;
            $tranx->save();
            $this->errors = "Failed Transaction";
            return false;
        }

        if(($data->currency != $tranx->currency) or ($data->amount < $tranx->amount)){
            $tranx->status = 2;
            $tranx->save();
            $this->errors = "Wrong Transaction";
            return false;
        }

        $tranx->status = 1;
        $tranx->save();

        if(!empty($data->card->token)){
            $token = CustomerFlutterwaveToken::where([
                'user_id' => $tranx->user_id,
                'first_digits' => $data->card->first_6digits,
                'last_digits' => $data->card->last_4digits,
                'card_issuer' => $data->card->issuer,
                'card_type' => $data->card->type,
                'country' => $data->card->country,
                'card_expiry' => $data->card->expiry
            ])->first();
            if(empty($token)){
                CustomerFlutterwaveToken::create([
                    'user_id' => $tranx->user_id,
                    'first_digits' => $data->card->first_6digits,
                    'last_digits' => $data->card->last_4digits,
                    'card_issuer' => $data->card->issuer,
                    'card_type' => $data->card->type,
                    'country' => $data->card->country,
                    'token' => $data->card->token,
                    'card_expiry' => $data->card->expiry,
                    'email' => $data->customer->email,
                    'token_expiry' => Carbon::now('Africa/Lagos')->addYear()->format('Y-m-d')
                ]);
            }
        }

        return $tranx;
    }

    public function flutterwave_verification($trans_id){
        if(!$this->verify_payment($trans_id)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 409);
        }

        return response([
            'status' => 'success',
            'message' => 'Payment successfully verified'
        ], 200);
    }

    public function webhook(Request $request){
        $hash = $request->header('verif-hash');
        if(!$hash or ($hash != $this->secret_hash)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Sender'
            ], 401);
        }

        $webhook = FlutterwaveWebhook::create([
            'webhook' => json_encode($request->all()),
            'event' => isset($request->event) ? $request->event : null,
            'trans_reference' => isset($request->tx_ref) ? $request->tx_ref : null,
            'amount' => isset($request->amount) ? $request->amount : null
        ]);

        if(!empty($webhook->trans_reference)){
            $tranx = Transaction::where('trans_reference', $webhook->trans_reference)->first();
            if(!empty($tranx)){
                $webhook->user_id = $tranx->user_id;
                $webhook->save();
            }

            if($webhook->event == 'charge.completed'){
                $this->verify_payment($request->data->id);
            }
        }

        return response([], 200);
    }

    public function create_account($tx_ref, $first_name, $last_name, $bvn, $email, $phone){
        $data = [
            'email' => $email,
            'currency' => 'NGN',
            'firstname' => $first_name,
            'lastname' => $last_name,
            'tx_ref' => $tx_ref,
            'amount' => 2000,
            'is_permanent' => true,
            'narration' => '1stMandate '.$first_name.' '.$last_name,
            'phonenumber' => $phone,
        ];
        $create = $this->perform_post_curl('/virtual-account-numbers', $data);
        if(!$create){
            return false;
        }

        if($create->status != 'success'){
            $this->errors = $create->message;
            return false;
        }

        return $create->data;
    }

    public function initiate_bvn_consent($bvn, $first_name, $last_name){
        $data = [
            'bvn' => $bvn,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'redirect_url' => env('APP_URL').'/bvn-redirect'
        ];
        $initiate = $this->perform_post_curl('/bvn/verifications', $data);
        if(!$initiate){
            return false;
        }

        if($initiate->status != 'success'){
            $this->errors = $initiate->message;
            return false;
        }

        return $initiate->data;
    }

    public function get_banks(){
        $banks = $this->perform_get_curl('/banks/NG');
        if(!$banks){
            return false;
        }

        if($banks->status != 'success'){
            $this->errors = $banks->message;
            return false;
        }

        return $banks->data;
    }

    public function verify_account($account_number, $bank_code){
        $url = "/accounts/resolve";
        $data = [
            'account_number' => $account_number,
            'account_bank' => $bank_code
        ];
        if(!$verify = $this->perform_post_curl($url, $data)){
            return false;
        }
        if($verify->status != 'success'){
            $this->errors = $verify->message;
            return false;
        }

        return $verify->data;
    }

    public function transfer($user, $account_number, $bank_code, $amount, $tx_ref){
        $url = "/transfers";
        $data = [
            "reference" => $tx_ref,
            "account_bank" => $bank_code,
            "account_number" => $account_number,
            "amount" => $amount,
            "currency" => "NGN",
            "narration" => "1stMandate Withdrawal",
            "callback_url" => env('APP_URL')."/api/flutterwave/transfer-callback",
        ];

        $withdraw = $this->perform_post_curl($url, $data);
        if(!$withdraw){
            return false;
        }

        $tranx = Transaction::create([
            'user_type' => 'user',
            'user_type_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'debit',
            'trans_reference' => $tx_ref,
            'transaction_id' => rand(000000, 999999),
            'amount' => $amount,
            'currency' => 'NGN',
            'platform' => 'Flutterwave',
            'request' => json_encode($data),
            'response1' => json_encode($withdraw),
            'status' => 0,
            'event' => 'wallet_transaction',
            'event_id' => rand(000000, 999999),
            'value_given' => 0
        ]);

        if($withdraw->status != 'success'){
            $this->errors = $withdraw->message;
            $tranx->status = 2;
            $tranx->save();
            return false;
        }

        $withdraw_data = $withdraw->data;
        if($withdraw_data->status == 'FAILED'){
            $this->errors = "Failed Transaction: ".$withdraw_data->complete_message;
            $tranx->status = 2;
            $tranx->save();
            return false;
        }

        $tranx->transaction_id = $withdraw_data->id;
        $tranx->save();

        return $tranx;
    }

    public function transfer_callback(Request $request){
        $hash = $request->header('verif-hash');
        if(!$hash or ($hash != $this->secret_hash)){
            return response([
                'status' => 'failed',
                'message' => 'Wrong Sender'
            ], 401);
        }

        $webhook = FlutterwaveWebhook::create([
            'webhook' => json_encode($request->all()),
            'event' => isset($request->event) ? $request->event : null,
            'trans_reference' => isset($request->data['reference']) ? $request->data['reference'] : null,
            'amount' => isset($request->data['amount']) ? $request->data['amount'] : null
        ]);
        if($request->event != 'transfer.completed'){
            return response([], 200);
        }
        $tranx = Transaction::where('trans_reference', $webhook->trans_reference)->first();
        if(!empty($tranx)){
            $webhook->user_id = $tranx->user_id;
            $webhook->save();

            if($tranx->value_given == 1){
                return response([], 200);
            }
            $tranx->response2 = json_encode($request->all());
            if($request->data['status'] == 'SUCCESSFUL'){
                $tranx->status = 1;
            } elseif($request->data['status'] == 'FAILED'){
                $tranx->status = 2;
                if($tranx->event == 'wallet_transaction'){
                    $trans = WalletTransaction::find($tranx->event_id);
                    if(!empty($trans)){
                        $wallet = Wallet::find($trans->wallet_id);
                        if(!empty($wallet)){
                            $wallet->balance = $wallet->balance + $trans->amount;
                            $wallet->total_credit = $wallet->total_credit + $trans->amount;
                            $wallet->save();

                            WalletTransaction::create([
                                'user_id' => $wallet->user_id,
                                'wallet_id' => $wallet->id,
                                'type' => 'credit',
                                'original_amount' => $trans->amount,
                                'charges' => 0,
                                'amount' => $trans->amount,
                                'pre_amount' => $wallet->balance - $trans->amount,
                                'post_amount' => $wallet->balance,
                                'remarks' => 'Reversal of failed withdrawal transfer. Transfer Reference: '.$tranx->trans_reference
                            ]);
                        }
                    }
                }
            }
            $tranx->value_given = 1;
            $tranx->save();
        }
    }

    public function get_electricity_billers(){
        $billers = $this->perform_get_curl('/bills/UTILITYBILLS/billers?country=NG');
        if(!$billers){
            return false;
        }

        if($billers->status != 'success'){
            $this->errors = $billers->message;
            return false;
        }

        return $billers->data;
    }

    public function get_bills_information($biller_code){
        $bills = $this->perform_get_curl('/billers/'.$biller_code.'/items');
        if(!$bills){
            return false;
        }
        if($bills->status != 'success'){
            $this->errors = $bills->message;
            return false;
        }

        return $bills->data;
    }

    public function validate_customer($item_code, $identifier){
        $url = '/bill-items/'.$item_code.'/validate?customer='.$identifier;
        $validate = $this->perform_get_curl($url);
        if(!$validate){
            return false;
        }

        if($validate->status != 'success'){
            $this->errors = $validate->message;
            return false;
        }

        return $validate->data;
    }

    public function pay_bill($ref, $biller_code, $product_code, $customer_identifier, $amount){
        $url = '/billers/'.$biller_code.'/items/'.$product_code.'/payment';
        $data = [
            'reference' => $ref,
            'country' => 'NG',
            'customer_id' => $customer_identifier,
            'amount' => $amount,
            'callback_url' => env('APP_URL').'/api/flutterwave/bill-payment-callback/'.$ref
        ];

        if(!$pay = $this->perform_post_curl($url, $data)){
            return false;
        }

        if($pay->status != 'success'){
            $this->errors = $pay->message;
            return false;
        }

        return [
            'request' => [
                'route' => $url,
                'data' => $data
            ],
            'response' => $pay->data
        ];
    }

    public function bill_payment_status($reference){
        $url = '/bills/'.$reference;
        $status = $this->perform_get_curl($url);
        if(!$status){
            return false;
        }

        if($status->status != 'success'){
            $this->errors = $status->message;
            return false;
        }

        return $status->data;
    }

    public function bill_payment_callback(Request $request, $reference){
        $webhook = FlutterwaveWebhook::create([
            'webhook' => json_encode($request->all()),
            'event' => isset($request->event) ? $request->event : null,
            'trans_reference' => isset($request->data['tx_ref']) ? $request->data['tx_ref'] : null,
            'amount' => isset($request->data['amount']) ? $request->data['amount'] : null
        ]);

        $bill_payment = ElectricityBillPayment::where('reference', $reference)->first();
        if(empty($bill_payment)){
            return response([], 200);
        }

        $webhook->user_id = $bill_payment->user_id;
        $webhook->save();

        $controller = new UtilityBillController();
        $controller->update_electricity_bill_payment_status($bill_payment);

        return response([], 200);
    }
}