<?php

namespace App\Http\Controllers\Tenant;

use App\Models\User;
use App\Models\DueDate;
use App\Models\Property;
use Illuminate\Support\Str;
use App\Models\PropertyUnit;
use Illuminate\Http\Request;
use App\Models\PropertyTenant;
use App\Models\PropertyManager;
use App\Models\PropertySetting;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Http\Requests\Tenant\StoreApartmentRequest;

class ApartmentController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function apartment(PropertyTenant $tenancy) : PropertyTenant
    {
        $unit = PropertyUnit::find($tenancy->property_unit_id);
        $property = Property::find($tenancy->property_id);
        $landlord = !empty($property->landlord_id) ? User::find($property->landlord_id) : "";
        $prop_managers = PropertyManager::where('property_id', $property->id)->first();
        $manager = !empty($prop_managers) ? User::find($prop_managers->manager_id) : "";
        $unit = PropertyUnit::find($tenancy->property_unit_id);
        
        $tenancy->property_title = $property->title;
        $tenancy->property_address = $property->address;
        $tenancy->property_city = $property->city;
        $tenancy->property_state = $property->state;
        $tenancy->property_country = $property->country;
        $tenancy->property_uuid = $property->uuid;
        $tenancy->unit_name = $unit->unit_name;
        $tenancy->unit_type = $unit->unit_type;
        $tenancy->no_of_bedrooms = $unit->no_of_bedrooms;
        $tenancy->occupation_status = $unit->occupation_status;

        $tenancy->landlord_name = !empty($landlord) ? $landlord->name : "";
        $tenancy->landlord_phone = !empty($landlord) ? $landlord->phone : "";
        
        $tenancy->manager_name = !empty($manager) ? $manager->name : "";
        $tenancy->manager_phone = !empty($manager) ? $manager->phone : "";

        return $tenancy;
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $tenancies = PropertyTenant::where('user_id', $this->user->id)->paginate($limit);
        if(empty($tenancies)){
            return response([
                'status' => 'failed',
                'message' => 'You are not a Tenant at any Apartment on this App',
                'data' => []
            ], 200);
        }

        foreach($tenancies as $tenancy){
            $tenancy = self::apartment($tenancy);
        }

        return response([
            'status' => 'success',
            'message' => 'Apartments fetched successfully',
            'data' => $tenancies
        ], 200);
    }

    public function store(StoreApartmentRequest $request){
        $uuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(Property::where('uuid', $t_uuid)->count() < 1){
                $uuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($uuid)){
            return response([
                'status' => 'failed',
                'message' => 'Server encountered error while uploading Apartment Property'
            ], 500);
        }
        $property = Property::create([
            'uuid' => $uuid,
            'title' => $request->property_title,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => !empty($request->country) ? $request->country : "Nigeria",
            'status' => 1
        ]);

        $u_uuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(PropertyUnit::where('uuid', $t_uuid)->count() < 1){
                $u_uuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($u_uuid)){
            return response([
                'status' => 'failed',
                'message' => 'Server encountered error while uploading Apartment Property Unit'
            ], 500);
        }
        $unit = PropertyUnit::create([
            'uuid' => $u_uuid,
            'property_id' => $property->id,
            'unit_name' => !empty($request->unit_name) ? $request->unit_name : $property->title,
            'unit_type' => !empty($request->building_type) ? $request->building_type : "Flat",
            'no_of_bedrooms' => !empty($request->no_of_bedrooms) ? $request->no_of_bedrooms : 2,
            'occupation_status' => 'occupied'
        ]);
        
        $l_uuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(PropertyTenant::where('uuid', $t_uuid)->count() < 1){
                $l_uuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }

        $today = date('Y-m-d');
        $current_tenant = (($today >= $request->lease_start) and ($today <= $request->lease_end)) ? true : false;
        $tenant = PropertyTenant::create([
            'uuid' => $l_uuid,
            'user_id' => $this->user->id,
            'property_id' => $property->id,
            'property_unit_id' => $unit->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'lease_start' => $request->lease_start,
            'lease_end' => $request->lease_end,
            'current_tenant' => $current_tenant,
            'rent_payment_status' => !empty($request->rent_payment_status) ? $request->rent_payment_status : "",
            'rent_due_date' => $request->rent_due_date,
            'rent_amount' => $request->rent_amount,
            'renew_rent' => $request->renew_rent
        ]);

        if(($tenant->current_tenant == 1) and !empty($tenant->rent_due_date)){
            DueDate::create([
                'property_tenant_id' => $tenant->id,
                'property_id' => $property->id,
                'property_unit_id' => $unit->id,
                'due_date' => $tenant->rent_due_date,
                'purpose' => 'Rent Due Date',
                'cash_payment' => true
            ]);
        }

        NoticeController::land_log_activity($this->user->id, "Added an Apartment: {$property->title} - {$unit->unit_name}", "apartments", $tenant->uuid);

        return response([
            'status' => 'success',
            'message' => 'Apartment created successfully',
            'data' => self::apartment($tenant)
        ], 200);
    }
}