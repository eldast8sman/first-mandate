<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use App\Models\ElectricityBillPayment;
use App\Http\Requests\PayElectricityBillRequest;
use App\Http\Requests\ResolveBillCustomerRequest;
use App\Mail\ElectricityBillPaymentMail;
use Illuminate\Support\Facades\Mail;

class UtilityBillController extends Controller
{
    private $user;
    private $flutterwave;
    private $charge_type; //flat_fee or percentage
    private $charge;
    private $charge_cap;

    public function __construct()
    {
        $this->user = AuthController::user();
        $this->flutterwave = new FlutterwaveController();
    }


    public function fetch_billers(){
        $billers = Cache::remember('flutterwave_electricity_billers', 60*60*24*30, function(){
            return $this->flutterwave->get_electricity_billers();
        });

        return response([
            'status' => 'success',
            'message' => 'Electricity billers fetched successfully',
            'data' => $billers
        ], 200);
    }

    public function fetch_bills($biller_code){
        $biils = Cache::remember('flutterwave_bills_utility_'.$biller_code, 60*60*24*7, function() use ($biller_code) {
            return $this->flutterwave->get_bills_information($biller_code);
        });
        return response([
            'status' => 'success',
            'message' => 'Utility bills fetched successfully',
            'data' => $biils
        ], 200);
    }

    public function index(Request $request){
        $limit = $request->has('limit') ? intval($request->limit) : 10;
        $from = $request->has('from') ? $request->from : "";
        $to = $request->has('to') ? $request->to : "";

        $bill_payments = ElectricityBillPayment::where('user_id', $this->user->id)
            ->when($from != "", function($query) use ($from){
                $query->whereDate('created_at', '>=', $from);
            })
            ->when($to != "", function($query) use ($to){
                $query->whereDate('created_at', '<=', $to);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response([
            'status' => 'success',
            'message' => 'Electricity bill payments fetched successfully',
            'data' => $bill_payments
        ], 200);
    }

    public function show($uuid){
        $bill_payment = ElectricityBillPayment::where('user_id', $this->user->id)->where('uuid', $uuid)->first();
        if(empty($bill_payment)){
            return response([
                'status' => 'error',
                'message' => 'Electricity bill payment not found'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Electricity bill payment fetched successfully',
            'data' => $bill_payment
        ], 200);
    }

    public function validate_customer(ResolveBillCustomerRequest $request){
        if(!$resolved = $this->flutterwave->validate_customer($request->item_code, $request->identifier)){
            return response([
                'status' => 'error',
                'message' => $this->flutterwave->errors
            ], 400);
        }

        return response([
            'status' => 'success',
            'message' => 'Customer resolved successfully',
            'data' => [
                'customer' => $resolved->name
            ]
        ], 200);
    }

    public function pay_electricity_bill(PayElectricityBillRequest $request){
        if(!$resolved = $this->flutterwave->validate_customer($request->item_code, $request->identifier)){
            return response([
                'status' => 'error',
                'message' => $this->flutterwave->errors
            ], 400);
        }

        if($this->charge_type == 'flat_fee'){
            $charge = $this->charge;
        } else {
            $charge = ($this->charge / 100) * $request->amount;
            if($charge > $this->charge_cap){
                $charge = $this->charge_cap;
            }
        }
        $total_amount = $request->amount + $charge;
        $wal_controller = new WalletController();
        $wallet = $wal_controller->confirm_wallet();

        if($wallet->balance < $total_amount){
            return response([
                'status' => 'error',
                'message' => 'Insufficient wallet balance. Please fund your wallet to complete this transaction.'
            ], 400);
        }

        $reference = 'EBP_FLW_'.Str::random(16).'_'.time();
        if(!$payment = $this->flutterwave->pay_bill($reference, $request->biller_code, $request->item_code, $request->identifier, $request->amount)){
            return response([
                'status' => 'error',
                'message' => $this->flutterwave->errors
            ], 400);
        }

        $data = $payment['response'];

        $bill_payment = ElectricityBillPayment::create([
            'user_id' => $this->user->id,
            'platform' => 'Flutterwave',
            'biller' => $request->biller,
            'biller_code' => $request->biller_code,
            'billing_product' => $request->item,
            'billing_product_code' => $request->item_code,
            'customer_identifier' => $request->identifier,
            'customer_name' => $resolved->name,
            'amount' => $request->amount,
            'charges' => $charge,
            'request' => json_encode($payment['request']),
            'response' => json_encode($data),
            'transaction_reference' => $data->tx_ref ?? null,
            'reference' => $reference,
            'status' => 'pending'
        ]);

        $wallet->total_debit += $total_amount;
        $wallet->balance -= $total_amount;
        $wallet->save();

        WalletTransaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'original_amount' => $request->amount,
            'amount' => $total_amount,
            'charges' => $charge,
            'pre_amount' => $wallet->balance + $total_amount,
            'post_amount' => $wallet->balance,
            'remarks' => 'Payment for electricity bill - '.$request->biller.'_'.$request->item.'_'.$resolved->name
        ]);

        return response([
            'status' => 'success',
            'message' => 'Electricity bill payment initiated successfully. Rechage token will be shared with you shortly.',
            'data' => $bill_payment
        ], 200);
    }

    public function update_electricity_bill_payment_status(ElectricityBillPayment $payment){
        if($payment->status != 'pending'){
            return;
        }

        if(!$status = $this->flutterwave->bill_payment_status($payment->transaction_reference)){
            return;
        }

        if(!empty($status->extra)){
            $payment->token = $status->extra;
            $payment->status = 'completed';
            $payment->response2 = json_encode($status);
            $payment->save();

            Mail::to($this->user)->send(new ElectricityBillPaymentMail($this->user->name, $payment->biller, $payment->customer_name, $payment->customer_identifier, $payment->amount, $payment->token));
        }
    }

    public function check_electricity_bill_status($uuid){
        $payment = ElectricityBillPayment::where('user_id', $this->user->id)->where('uuid', $uuid)->first();
        if(empty($payment)){
            return response([
                'status' => 'error',
                'message' => 'Electricity bill payment not found'
            ], 404);
        }

        $this->update_electricity_bill_payment_status($payment);

        return response([
            'status' => 'success',
            'message' => 'Electricity bill payment status updated successfully'
        ], 200);
    }
}
