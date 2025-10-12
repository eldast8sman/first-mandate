<?php

namespace App\Http\Controllers\Manager;

use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\PropertySetting;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Http\Requests\AddPropertySettingRequest;

class PropertySettingController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function fetch_setting($uuid){
        $managerId = $this->user->id;

        $property = Property::where('uuid', $uuid)
            ->whereExists(function ($query) use ($managerId) {
                $query->selectRaw(1)
                    ->from('property_managers')
                    ->whereColumn('property_managers.property_id', 'properties.id')
                    ->where('property_managers.manager_id', $managerId);
            })
            ->first();
        if(empty($property)){
            return response([
                'status' => 'failed',
                'message' => 'Property not found',
            ], 404);
        }

        $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'property_manager')->first();
        if(empty($setting)){
            $setting =  PropertySetting::create([
                'property_id' => $property->id,
                'user_type' => 'property_manager',
                'tenant_pays_commission' => false,
                'pay_rent_to' => 'property_manager'
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'Property setting fetched successfully',
            'data' => $setting
        ], 200);
    }

    public function update_setting(AddPropertySettingRequest $request, $uuid){
        $managerId = $this->user->id;

        $property = Property::where('uuid', $uuid)
            ->whereExists(function ($query) use ($managerId) {
                $query->selectRaw(1)
                    ->from('property_managers')
                    ->whereColumn('property_managers.property_id', 'properties.id')
                    ->where('property_managers.manager_id', $managerId);
            })
            ->first();
        if(empty($property)){
            return response([
                'status' => 'failed',
                'message' => 'Property not found',
            ], 404);
        }

        $setting = PropertySetting::where('property_id', $property->id)->where('user_type', 'property_manager')->first();
        if(empty($setting)){
            $setting =  PropertySetting::create([
                'property_id' => $property->id,
                'user_type' => 'property_manager',
                'tenant_pays_commission' => false,
                'pay_rent_to' => 'property_manager'
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
