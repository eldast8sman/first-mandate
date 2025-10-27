<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddPropertySettingRequest;
use App\Models\Property;
use App\Models\PropertySetting;
use Illuminate\Http\Request;

class PropertySettingController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function fetch_setting($uuid){
        $property = Property::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($property)){
            return response([
                'status' => 'failed',
                'message' => 'Property not found',
            ], 404);
        }

        $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'landlord')->first();
        if(empty($setting)){
            $setting =  PropertySetting::create([
                'property_id' => $property->id,
                'user_type' => 'landlord',
                'tenant_pays_commission' => false,
                'pay_rent_to' => 'landlord'
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Property setting fetched successfully',
            'data' => $setting
        ], 200);
    }

    public function update_setting(AddPropertySettingRequest $request, $uuid){
        $property = Property::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($property)){
            return response([
                'status' => 'failed',
                'message' => 'Property not found',
            ], 404);
        }

        $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'landlord')->first();
        if(empty($setting)){
            $setting =  PropertySetting::create([
                'property_id' => $property->id,
                'user_type' => 'landlord',
                'tenant_pays_commission' => false,
                'pay_rent_to' => 'landlord'
            ]);
        }

        $setting->tenant_pays_commission = $request->tenant_pays_commission;
        $setting->pay_rent_to = $request->pay_rent_to;
        $setting->save();

        NoticeController::land_log_activity($this->user->id, "Updated Property Setting for: {$property->title}", "properties", $property->uuid);

        return response([
            'status' => 'success',
            'message' => 'Property setting updated successfully',
            'data' => $setting
        ], 200);
    }
}
