<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WalletController;
use App\Http\Requests\Tenant\InitiateRentPayment;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertySetting;
use App\Models\PropertyTenant;
use App\Models\RentPayment;
use App\Models\RentPaymentInstallment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RentPaymentController extends Controller
{
    private $user;
    public $errors;
    private $rent_commission_type = 'percentage'; //percentage or fixed
    private $rent_commission = 5; 
    private $rent_commission_cap = 0; //0 for no cap

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function fetch_rent($uuid){
        $tenancy = PropertyTenant::where('uuid', $uuid)->where('user_id', $this->user->id)->where('current_tenant', 1)->first();
        if(empty($tenancy)){
            return response([
                'status' => 'failed',
                'message' => 'No Apartment was fetched'
            ], 404);
        }
        
        $setting = PropertySetting::where('property_id', $tenancy->property_id)->where('user_type', 'landlord')->first();
        if(empty($setting)){
            $setting = PropertySetting::where('property_id', $tenancy->property_id)->where('user_type', 'property_manager')->first();
        }

        $pay_rent_to = !empty($setting) ? $setting->pay_rent_to : "landlord";
        $tenant_pays_commission = !empty($setting) ? $setting->tenant_pays_commission : false;

        if($pay_rent_to == "landlord"){
            $recipient = User::find($tenancy->landlord_id);
            if(empty($recipient)){
                $manager = PropertyManager::where('property_id', $tenancy->property_id)->first();
                $recipient = User::find($manager->manager_id);
            }
        } else {
            $manager = PropertyManager::where('property_id', $tenancy->property_id)->first();
            $recipient = User::find($manager->manager_id);
        }

        $amount = $tenancy->rent_amount;
        if($tenant_pays_commission == 1){
            if($this->rent_commission_type == 'percentage'){
                $commission = ($this->rent_commission / 100) * $amount;
                if($this->rent_commission_cap > 0){
                    if($commission > $this->rent_commission_cap){
                        $commission = $this->rent_commission_cap;
                    }
                }
            } else {
                $commission = $this->rent_commission;
            }
            $amount += $commission;
        }

        return response([
            'status' => 'success',
            'message' => 'Rent details fetched successfully',
            'data' => [
                'recipient' => !empty($recipient) ? $recipient->name : "",
                'rent_term' => $tenancy->rent_term ?? 12,
                'rent_due_date' => $tenancy->rent_due_date,
                'rent_amount' => $amount,
                'payment_type' => $tenancy->payment_type
            ]
        ], 200);
    }

    public function fetch_property_settings(PropertyTenant $tenancy){
        $property = Property::find($tenancy->property_id);
        $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'landlord')->first();
        if(empty($setting)){
            $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'property_manager')->first();
        }
        if(empty($setting)){
            $this->errors = "Property settings not found";
            return false;
        }

        if($setting->pay_rent_to == 'landlord') {
            $payee = !empty($property->landlord_id) ? $property->landlord_id : null;
        } else {
            $prop_managers = PropertyManager::where('property_id', $property->id)->first();
            $payee = !empty($prop_managers) ? $prop_managers->manager_id : null;
        }

       if(empty($payee)){
            $this->errors = "Payee not found";
            return false;
        }

        $payee = User::find($payee);
        if($payee->status != 1 or $payee->email_verified != 1){
            $this->errors = "Payee is not active";
            return false;
        }
        return [
            'tenant_pays_commission' => $setting->tenant_pays_commission,
            'pay_rent_to' => $setting->pay_rent_to,
            'payee' => $payee
        ];     
    }

    public function check_installment_payments(PropertyTenant $tenancy, Request $request){
        $payment = RentPayment::where('tenancy_id', $tenancy->id)
                ->where('payment_type', 'installment')
                ->where('payment_status', 3)
                ->first();
        if(!empty($payment)){
            $installments = RentPaymentInstallment::where('rent_payment_id', $payment->id)->where('status', '!=', 2)->get();
            if(!empty($installments)){
                if($installments->sum('no_of_installment') + $request->no_of_installments > $tenancy->no_of_installments){
                    $this->errors = "You can only pay ".($tenancy->no_of_installments - $installments->sum('no_of_installment'))." more installments";
                    return false;
                }
            } else {
                if($request->no_of_installments > $tenancy->no_of_installments){
                    $this->errors = "You can only pay ".$tenancy->no_of_installments." installments";
                    return false;
                }
            }
            $installment = RentPaymentInstallment::create([
                'rent_payment_id' => $payment->id,
                'payment_method' => $request->payment_method,
                'amount' => $tenancy->installment_amount * $request->no_of_installments,
                'no_of_installment' => $request->no_of_installments
            ]);          
        } else { 
            $no_of_months = $tenancy->rent_term ?? 12;
            $old_end_date = $tenancy->lease_end;
            $new_start_date = Carbon::parse($old_end_date)->addDay();
            $new_end_date = Carbon::parse($new_start_date)->addMonths($no_of_months)->subDay();          
            $payment = RentPayment::create([
                'tenancy_id' => $tenancy->id,
                'rent_amount' => $tenancy->rent_amount,
                'payment_method' => $request->payment_method,
                'payment_type' => 'installment',
                'no_of_installments' => $tenancy->no_of_installments,
                'installment_amount' => $tenancy->installment_amount,
                'next_due_date' => $tenancy->rent_due_date,
                'rent_start_date' => $new_start_date,
                'rent_end_date' => $new_end_date,
                'payment_status' => 0 //Not yet processed
            ]);

            $installment = RentPaymentInstallment::create([
                'rent_payment_id' => $payment->id,
                'payment_method' => $request->payment_method,
                'amount' => $tenancy->installment_amount * $request->no_of_installments,
                'no_of_installment' => $request->no_of_installments
            ]);
        }
        return $installment;
    }

    private function clear_unused_installment_payments(PropertyTenant $tenancy, Request $request){
        $old_payment = RentPayment::where('tenancy_id', $tenancy->id)
                ->where('payment_status', 3)->first();
        if(!empty($old_payment)){
            $this->errors = "You have an ongoing installment payment. Please complete it before making a full payment.";
            return false;
        }
        $payment = RentPayment::where('tenancy_id', $tenancy->id)
                ->where('payment_status', 0)
                ->get();
        if(!empty($payments)){
            foreach($payments as $payment){
                RentPaymentInstallment::where('rent_payment_id', $payment->id)->delete();
                $payment->delete();
            }
        }
        $payment->delete();
        $no_of_months = $tenancy->rent_term;
        $old_end_date = $tenancy->lease_end;
        $new_start_date = Carbon::parse($old_end_date)->addDay();
        $new_end_date = Carbon::parse($new_start_date)->addMonths($no_of_months)->subDay();          
        $payment = RentPayment::create([
            'tenancy_id' => $tenancy->id,
            'rent_amount' => $tenancy->rent_amount,
            'payment_method' => $request->payment_method,
            'payment_type' => 'full',
            'no_of_installments' => 1,
            'installment_amount' => $tenancy->rent_amount,
            'next_due_date' => $tenancy->rent_due_date,
            'rent_start_date' => $new_start_date,
            'rent_end_date' => $new_end_date,
            'payment_status' => 0 //Not yet processed
        ]);

        $installment = RentPaymentInstallment::create([
            'rent_payment_id' => $payment->id,
            'payment_method' => $request->payment_method,
            'amount' => $tenancy->rent_amount,
            'no_of_installment' => 1
        ]);

        return $installment;
    }

    public static function update_rent_payment(RentPaymentInstallment $installment){
        if($installment->status == 1){
            $payment = RentPayment::find($installment->rent_payment_id);

            $others = RentPaymentInstallment::where('rent_payment_id', $payment->id)
                        ->where('status', 1)->get();
            if($others->sum('amount') >= $payment->rent_amount){
                $payment->payment_status = 1; //Paid
            } else {
                $payment->payment_status = 3; //Partially paid
            }
            $payment->installments_paid = $others->sum('no_of_installment');
            $payment->save();

            $tenancy = PropertyTenant::find($payment->tenancy_id);
            $tenancy->lease_end = $payment->rent_end_date;
            $tenancy->rent_payment_status = $payment->payment_status == 1 ? 'full_payment' : 'part_payment';
            $tenancy->save();
        }
    }

    public function initiate_rent_payment(InitiateRentPayment $request, $uuid){
        $tenancy = PropertyTenant::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
        if(empty($tenancy)){
            return response([
                'status' => 'failed',
                'message' => 'Tenancy not found',
            ], 404);
        }

        if(!$settings = $this->fetch_property_settings($tenancy)){
            return response([
                'status' => 'failed',
                'message' => $this->errors,
            ], 400);
        }

        if($request->payment_type == 'installment'){
            if(!$payment = $this->check_installment_payments($tenancy, $request)){
                return response([
                    'status' => 'failed',
                    'message' => $this->errors,
                ], 400);
            }
            $amount = $payment->amount;
        } elseif($request->payment_type == 'full'){
            $payment = $this->clear_unused_installment_payments($tenancy, $request);
            $amount = $tenancy->rent_amount;   
        }

        if($settings['tenant_pays_commission'] == 1){
            if($this->rent_commission_type == 'percentage'){
                $commission = ($this->rent_commission / 100) * $amount;
                if($this->rent_commission_cap > 0){
                    if($commission > $this->rent_commission_cap){
                        $commission = $this->rent_commission_cap;
                    }
                }
            } else {
                $commission = $this->rent_commission;
            }
            $final_amount = $amount + $commission;
        } else {
            $final_amount = $amount;
        }

        if($request->payment_method == 'wallet'){
            $wal_controller = new WalletController();
            $wallet = $wal_controller->confirm_wallet();

            if($wallet->balance < $final_amount){
                return response([
                    'status' => 'failed',
                    'message' => 'Insufficient wallet balance. Please fund your wallet to proceed with the payment.',
                ], 400);
            }

            $wallet->total_debit += $final_amount;
            $wallet->balance -= $final_amount;
            $wallet->save();

            WalletTransaction::create([
                'user_id' => $this->user->id,
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'original_amount' => $final_amount,
                'amount' => $final_amount,
                'charges' => 0,
                'pre_amount' => $wallet->balance + $final_amount,
                'post_amount' => $wallet->balance,
                'remarks' => 'Rent payment for '.$tenancy->property->title.' - '.$tenancy->unit->unit_name,
                'status' => 1
            ]);

            $payment->status = 1;
            $payment->save();

            self::update_rent_payment($payment);

            $payee = $settings['payee'];
            $payee_wallet = $wal_controller->confirm_wallet($payee->id);
            $payee_wallet->total_credit += $amount;
            $payee_wallet->balance += $amount;
            $payee_wallet->save();

            WalletTransaction::create([
                'user_id' => $payee->id,
                'wallet_id' => $payee_wallet->id,
                'type' => 'credit',
                'original_amount' => $amount,
                'amount' => $amount,
                'charges' => 0,
                'pre_amount' => $payee_wallet->balance - $amount,
                'post_amount' => $payee_wallet->balance,
                'remarks' => 'Rent payment from '.$this->user->name.' for '.$tenancy->property->title.' - '.$tenancy->unit->unit_name,
                'status' => 1
            ]);

            if($settings['tenant_pays_commission'] == 0){
                //Pay commission to platform
                if($this->rent_commission_type == 'percentage'){
                    $commission = ($this->rent_commission / 100) * $amount;
                    if($this->rent_commission_cap > 0){
                        if($commission > $this->rent_commission_cap){
                            $commission = $this->rent_commission_cap;
                        }
                    }
                } else {
                    $commission = $this->rent_commission;
                }
                $payee_wallet->total_debit += $commission;
                $payee_wallet->balance -= $commission;
                $payee_wallet->save();

                WalletTransaction::create([
                    'user_id' => $payee->id,
                    'wallet_id' => $payee_wallet->id,
                    'type' => 'debit',
                    'original_amount' => $commission,
                    'amount' => $commission,
                    'charges' => 0,
                    'pre_amount' => $payee_wallet->balance + $commission,
                    'post_amount' => $payee_wallet->balance,
                    'remarks' => 'Commission payment to platform for rent payment from '.$this->user->name.' for '.$tenancy->property->title.' - '.$tenancy->unit->unit_name,
                    'status' => 1
                ]);
            }

            return response([
                'status' => 'success',
                'message' => 'Rent payment successful',
                'data' => null
            ], 200);
        }


    }

    public function rent_payments($uuid){
        $tenancy = PropertyTenant::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
        if(empty($tenancy)){
            return response([
                'status' => 'failed',
                'message' => 'Tenancy not found',
            ], 404);
        }

        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $payments = RentPayment::where('tenancy_id', $tenancy->id)->paginate($limit);
        if(empty($payments)){
            return response([
                'status' => 'failed',
                'message' => 'No rent payments found',
                'data' => []
            ], 200);
        }
        foreach($payments as $payment){
            $payment->installments = RentPaymentInstallment::where('rent_payment_id', $payment->id)->get();
        }

        return response([
            'status' => 'success',
            'message' => 'Rent payments fetched successfully',
            'data' => $payments
        ], 200);
    }
}
