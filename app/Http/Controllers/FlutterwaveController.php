<?php

namespace App\Http\Controllers;

use App\Models\CustomerFlutterwaveToken;
use App\Models\FlutterwaveWebhook;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

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
            'Authorization: BEARER '.$this->secret_key
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
            'Authorization: '.$this->secret_key
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

    public function initiate_payment($user_type, $user_id, $user_type_id, $trans_reference, $event, $event_id=null, $currency='NGN', $amount, $customer, $redirect_url){
        $url = '/payments';
        $payload = [
            'tx_ref' => $trans_reference,
            'amount' => $amount,
            'redirect_url' => env('FRONTEND_URL').'/'.$redirect_url,
            'currency' => $currency,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email
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

        $tranx = Transaction::create([
            'user_type' => $user_type,
            'user_type_id' => $user_type_id,
            'user_id' => $user_id,
            'type' => 'debit',
            'trans_reference' => $trans_reference,
            'currency' => $currency,
            'amount' => $amount,
            'platform' => 'Flutterwave',
            'request' => json_encode($payload),
            'response1' => json_encode($initiate),
            'status' => 0,
            'event' => $event,
            'event_id' => $event_id,
            'value_given' => 0
        ]);

        if($initiate->status != "success"){
            $this->errors = $initiate->message;
            $tranx->status = 2;
            $tranx->save();
            return false;
        }

        return $initiate->data;
    }

    public function token_charge($token, $user_type, $user_id, $user_type_id, $trans_reference, $event, $event_id=null, $currency='NGN', $amount, $customer){
        $url = '/tokenized-charges';
        $data = [
            'token' => $token,
            'email' => $customer->email,
            'currency' => $currency,
            'amount' => $amount,
            'tx_ref' => $trans_reference
        ];

        if(!$charge = $this->perform_post_curl($url, $data)){
            return false;
        }

        $tranx = Transaction::create([
            'user_type' => $user_type,
            'user_type_id' => $user_type_id,
            'user_id' => $user_id,
            'type' => 'debit',
            'trans_reference' => $trans_reference,
            'currency' => $currency,
            'amount' => $amount,
            'platform' => "Flutterwave",
            'request' => json_encode($data),
            'response1' => json_encode($charge),
            'status' => 0,
            'event' => $event,
            'event_id' => $event_id,
            'value_given' => 0
        ]);

        if($charge->status != 'success'){
            $this->errors = $charge->message;
            $tranx->status = 2;
            $tranx->save();
            return false;
        }

        $charge_data = $charge->data;
        if($charge_data->status != 'successful'){
            $this->errors = "Failed Transaction";
            $tranx->status = 2;
            $tranx->save();
            return false;
        }

        if(!$this->verify_payment($charge_data->id)){
            return false;
        }

        return true;
    }

    public function verify_payment($transaction_id){
        $url = "/transactions/{$transaction_id}/verify";

        $verify = $this->perform_get_curl($url);
        if(!$verify){
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
        $tranx->transaction_id = $transaction_id;
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
            $flw_token = CustomerFlutterwaveToken::where('user_id', $tranx->user_id)->where('token', $data->card->token)->first();
            if(empty($flw_token)){
                CustomerFlutterwaveToken::create([
                    'user_id' => $tranx->user_id,
                    'first_digits' => $data->card->first_6digits,
                    'last_digits' => $data->card->last_4digits,
                    'card_issuer' => $data->card->issuer,
                    'card_type' => $data->card->type,
                    'country' => $data->card->country,
                    'token' => $data->card->token
                ]);
            }
        }
        if($tranx->value_given != 1){
            if($tranx->event == 'wallet_transaction'){
                $trans = WalletTransaction::find($tranx->event_id);
                if(!empty($trans)){
                    if($trans->type == 'credit'){
                        $wallet = Wallet::find($trans->wallet_id);
                        if(!empty($wallet)){
                            $trans->pre_amount = $wallet->balance;
                            $wallet->balance += $trans->amount;
                            $wallet->total_credit += $trans->amount;
                            $trans->post_amount = $wallet->balance;
                            $wallet->save();
                            $trans->save();
                        }
                    }
                }
                $tranx->value_given = 1;
                $tranx->save();
            }
        }

        return true; 
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

    public function transfer_funds($amount, )
}