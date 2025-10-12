<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Manager\StoreLandlordRequest;
use App\Http\Requests\Manager\StorePropertyRequest;
use App\Http\Requests\Manager\StorePropertyTenantRequest;
use App\Http\Requests\Manager\StorePropertyUnitRequest;
use App\Mail\AddPropertyLandlordMail;
use App\Mail\ManagerAddTenantMail;
use App\Models\DueDate;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\PropertyTenant;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function property(Property $property) : Property
    {
        $property->landlord = User::where('id', $property->id)->first(['name', 'email', 'phone']);
        $units = PropertyUnit::where('property_id', $property->id)->get();
        if(!empty($units)){
            foreach($units as $unit){
                $unit = self::unit($unit);
            }
        }
        $property->units = $units;
        return $property;
    }

    public static function unit(PropertyUnit $unit) : PropertyUnit
    {
        $unit->tenant = PropertyTenant::where('property_unit_id', $unit->id)->where('current_tenant', 1)->first();
        $property = Property::find($unit->property_id);
        $unit->property_uuid = $property->uuid;
        return $unit;
    }

    public static function tenant(PropertyTenant $tenant) : PropertyTenant
    {
        $tenant->property = Property::find($tenant->property_id);
        $tenant->property_unit = PropertyUnit::find($tenant->property_unit_id);
        return $tenant;
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $properties = [];
        $managers = PropertyManager::where('manager_id', $this->user->id)->orderBy('created_at', 'desc')->get();
        if(!empty($managers)){
            foreach($managers as $manager){
                $properties[] = Property::find($manager->property_id);
            }
        }
        if(empty($properties)){
            return response([
                'status' => 'failed',
                'message' => 'No Property has been added yet'
            ], 200);
        }

        $properties = self::paginate_array($properties, $limit, $page);
        foreach($properties as $property){
            $property = self::property($property);
        }

        return response([
            'status' => 'success',
            'message' => 'Properties fetched successfully',
            'data' => $properties
        ], 200);
    }

    public function store(StorePropertyRequest $request){
        $all = $request->except(['landlord_name', 'landlord_email', 'landlord_phone']);
        $uuid = "";
        for($i=1; $i<=40; $i++){
            $temp_uuid = Str::uuid();
            if(Property::where('uuid', $temp_uuid)->count() < 1){
                $uuid = $temp_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($uuid)){
            return response([
                'status' => 'failed',
                'message' => 'There was an error addng Property. Please try again later'
            ], 500);
        }

        $all['uuid'] = $uuid;
        if(!$property = Property::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Server Error! Please try again later'
            ], 500);
        }

        NoticeController::land_log_activity($this->user->id, "Added Property: {$property->title}", "properties", $property->uuid);

        if(!empty($request->landlord_email)){
            if(empty($landlord = User::where('email', $request->landlord_email)->first())){
                $l_uuid = "";
                for($i=1; $i<=40; $i++){
                    $t_uuid = Str::uuid();
                    if(User::where('uuid', $t_uuid)->count() < 1){
                        $l_uuid = $t_uuid;
                        break;
                    } else {
                        continue;
                    }
                }
                if(empty($l_uuid)){
                    $property->delete();

                    return response([
                        'status' => 'failed',
                        'message' => 'Property Upload could not be completed. Please try again later'
                    ], 500);
                }
                if(!$landlord = User::create([
                    'uuid' => $l_uuid,
                    'email' => $request->landlord_email,
                    'name' => $request->landlord_name,
                    'phone' => $request->landlord_phone ?? '',
                    'verification_token' => Str::random(20).time(),
                    'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                    'roles' => 'landlord',
                    'status' => 1
                ])){
                    $property->delete();
                    return response([
                        'status' => 'failed',
                        'message' => 'Property upload failed. Please try again later'
                    ], 500);
                }

                $new_user = true;
            } else {
                if(!str_contains($landlord->roles, 'landlord')){
                    $landlord->roles = !empty($landlord->roles) ? $landlord->roles.',landlord' : 'landlord';
                    $landlord->save();
                }

                $new_user = false;
            }
            $property->landlord_id = $landlord->id;
            $property->save();

            Mail::to($landlord)->send(new AddPropertyLandlordMail($landlord->name, $this->user->name, $new_user, $new_user ? $landlord->verification_token : ""));
            NotificationController::store('landord', $landlord->id, "Added as a Landlord", "You've just been added as the Landlord to a Property, {$property->title}, managed by ".$this->user->name, "properties", $property->uuid);
            NoticeController::land_log_activity($this->user->id, "Added Landlord, {$landlord->name}, to Property, {$property->title}", "properties", $property->uuid);
        }

        $p_uuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(PropertyManager::where('uuid', $t_uuid)->count() < 1){
                $p_uuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }  

        if(empty($p_uuid)){
            $property->delete();
            return response([
                'status' => 'failed',
                'message' => 'Property Upload could not be completed. Please try again later'
            ], 500);
        }      
        PropertyManager::create([
            'uuid' => $p_uuid,
            'property_id' => $property->id,
            'landlord_id' => (isset($landlord) and !empty($landlord)) ? $landlord->id : NULL,
            'manager_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => !empty($this->user->phone) ? (string)$this->user->phone : "",
            'status' => 1
        ]);

        $user = User::find($this->user->id);
        if(!str_contains($user->roles, 'property manager')){
            $user->roles = !empty($user->roles) ? $user->roles.',property manager' : 'property manager';
            $user->save();
        }

        return response([
            'status' => 'success',
            'message' => 'Property added successfully',
            'data' => $property
        ], 200);
    }

    public function store_unit(StorePropertyUnitRequest $request, $uuid){
        $property = Property::where('uuid', $uuid)->first();
        if(empty($property) or (PropertyManager::where('manager_id', $this->user->id)->where('property_id', $property->id)->count() < 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Property was fetched'
            ], 404);
        }
        $uuid = "";
        for($i=1; $i<=40; $i++){
            $temp_uuid = Str::uuid();
            if(PropertyUnit::where('uuid', $temp_uuid)->count() < 1){
                $uuid = $temp_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($uuid)){
            return response([
                'status' => 'failed',
                'message' => 'Could not add Property Unit! Please try again!'
            ], 500);
        }
        $all = $request->all();
        $all['uuid'] = $uuid;
        $all['property_id'] = $property->id;
        $all['landlord_id'] = $property->landlord_id;

        if(!$unit = PropertyUnit::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Unit Addition Failed. Please try again later'
            ], 500);
        }

        NoticeController::land_log_activity($this->user->id, "Added a Unit, {$unit->unit_name} to Property, {$property->title}", "property_units", $unit->uuid);

        return response([
            'status' => 'success',
            'message' => 'Property Unit added successfully',
            'data' => $unit
        ], 200);
    }

    public function store_tenant(StorePropertyTenantRequest $request, $uuid){
        $unit = PropertyUnit::where('uuid', $uuid)->first();
        if(empty($unit) or (PropertyManager::where('manager_id', $this->user->id)->where('property_id', $unit->property_id)->count() < 1)){
            return response([
                'status' => 'failed',
                'message' => 'No Property Unit was fetched'
            ], 404);
        }
        $property = Property::find($unit->property_id);

        if($request->lease_end < $request->lease_start){
            return response([
                'status' => 'failed',
                'message' => 'Lease End date must be later than Lease start date'
            ], 409);
        }

        if(!empty($request->email)){
            if(empty($user = User::where('email', $request->email)->first())){
                $uuid = "";
                for($i=1; $i<=40; $i++){
                    $temp_uuid = Str::uuid();
                    if(Property::where('uuid', $temp_uuid)->count() < 1){
                        $uuid = $temp_uuid;
                        break;
                    } else {
                        continue;
                    }
                }
                if(empty($uuid)){
                    return response([
                        'status' => 'failed',
                        'message' => 'There was an error addng Tensnt. Please try again later'
                    ], 500);
                }
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => !empty($request->phone) ? $request->phone : "",
                    'verification_token' => Str::random(20).time(),
                    'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                    'roles' => 'tenant',
                    'uuid' => $uuid
                ]);
                if(!$user){
                    return response([
                        'status' => 'failed',
                        'message' => 'Tenant was not added as a User. Please try again later'
                    ], 500);
                }
    
                $new_user = true;
            } else {
                if(!str_contains($user->roles, 'tenant')){
                    $user->roles = !empty($user->roles) ? $user->roles.',tenant' : 'tenant';
                    $user->save();
                }
                $new_user = false;
            }
        }

        $tuuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(PropertyTenant::where('uuid', $t_uuid)->count() < 1){
                $tuuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($tuuid)){
            return response([
                'status' => 'failed',
                'message' => 'Tenant addition failed.'
            ], 500);
        }

        $within = PropertyTenant::where('property_unit_id', $unit->id);
        if($within->where('lease_start', '<=', $request->lease_start)->where('lease_end', '>=', $request->lease_start)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant'
            ], 409);
        }
        if($within->where('lease_start', '<=', $request->lease_end)->where('lease_end', '>=', $request->lease_end)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant'
            ], 409);
        }
        if($within->where('lease_start', '>', $request->lease_start)->where('lease_end', '<', $request->lease_end)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant'
            ], 409);
        }

        $today = date('Y-m-d');
        $current = false;
        $all = $request->all();
        $all['uuid'] = $tuuid;
        if(($today >= $request->lease_start) and ($today <= $request->lease_end)){
            $current = true;
            $unit->occupation_status = 'occupied';
            $unit->save();
        }

        $all['current_tenant'] = $current;
        $all['user_id'] = isset($user) ? $user->id : null;
        $all['landlord_id'] = $unit->landlord_id;
        $all['property_id'] = $unit->property_id;
        $all['property_unit_id'] = $unit->id;
        if(isset($all['no_of_installments']) and ($all['no_of_installments'] > 1)){
            $all['installment_amount'] = $all['rent_amount'] / $all['no_of_installments'];
        } else {
            $all['installment_amount'] = $all['rent_amount'];
        }
        if(!$tenant = PropertyTenant::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Tenanr addition failed'
            ], 500);
        }

        if(($tenant->current_tenant == 1) and (!empty($tenant->rent_due_date))){
            DueDate::create([
                'landlord_id' => $this->user->id,
                'property_tenant_id' => $tenant->id,
                'property_id' => $unit->property_id,
                'property_unit_id' => $unit->id,
                'due_date' => $tenant->rent_due_date,
                'purpose' => 'Rent Due Date',
                'cash_payment' => true
            ]);
        }

        if(!empty($request->email)){
            Mail::to($user)->send(new ManagerAddTenantMail($user->name, $this->user->name, $new_user, $new_user ? $user->verification_token : ""));
            NotificationController::store('tenant', $user->id, 'Added as a Tenant', "You have been added as a Tenant to the Apartment ".$property->title." - ".$unit->unit_name." managed by {$this->user->name}", "apartments", $tenant->uuid);
        }

        NoticeController::land_log_activity($this->user->id, "Added Tenant, {$tenant->name}, to Apartment. {$property->title} - {$unit->unit_name}", "tenants", $tenant->uuid);

        return response([
            'status' => 'success',
            'message' => 'Tenant added successfully',
            'data' => $tenant
        ], 200);
    }

    public function tenants(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;

        $tenants = [];
        $properties = [];
        $managers = PropertyManager::where('manager_id', $this->user->id)->orderBy('created_at', 'desc')->get();
        if(!empty($managers)){
            foreach($managers as $manager){
                $properties[] = Property::find($manager->property_id);
            }
        }
        foreach($properties as $property){
            $tenancies = PropertyTenant::where('property_id', $property->id)->where('current_tenant', 1)->orderBy('name', 'asc')->get();
            foreach($tenancies as $tenancy){
                $tenants[] = $tenancy;
            }
        }
        if(empty($tenants)){
            return response([
                'status' => 'failed',
                'message' => 'No Tenant has been added',
                'data' => []
            ], 200);
        }

        $tenants = self::paginate_array($tenants, $limit, $page);
        foreach($tenants as $tenant){
            $tenant = self::tenant($tenant);
        }

        return response([
            'status' => 'success',
            'message' => 'Tenants successfully fetched',
            'data' => $tenants
        ], 200);
    }

    public function store_landlord(StoreLandlordRequest $request){
        $property = Property::where('uuid', $request->property_uuid)->first();
        $manager = PropertyManager::where('property_id', $property->id)->where('manager_id', $this->user->id)->first();
        if(empty($property) or empty($manager)){
            return response([
                'status' => 'failed',
                'message' => 'No Property was fetched'
            ], 404);
        }
        if(!empty($property->landlord_id)){
            return response([
                'status' => 'failed',
                'message' => 'This Property already has a Landlord'
            ], 409);
        }

        if(empty($landlord = User::where('email', $request->email)->first())){
            $l_uuid = "";
            for($i=1; $i<=40; $i++){
                $t_uuid = Str::uuid();
                if(User::where('uuid', $t_uuid)->count() < 1){
                    $l_uuid = $t_uuid;
                    break;
                } else {
                    continue;
                }
            }
            if(empty($l_uuid)){

                return response([
                    'status' => 'failed',
                    'message' => 'Property Upload could not be completed. Please try again later'
                ], 500);
            }
            if(!$landlord = User::create([
                'uuid' => $l_uuid,
                'email' => $request->email,
                'name' => $request->name,
                'phone' => $request->phone ?? '',
                'verification_token' => Str::random(20).time(),
                'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                'roles' => 'landlord',
                'status' => 1
            ])){
                return response([
                    'status' => 'failed',
                    'message' => 'Property upload failed. Please try again later'
                ], 500);
            }

            $new_user = true;
        } else {
            if(!str_contains($landlord->roles, 'landlord')){
                $landlord->roles = !empty($landlord->roles) ? $landlord->roles.',landlord' : 'landlord';
                $landlord->save();
            }

            $new_user = false;
        }
        $property->landlord_id = $landlord->id;
        $property->save();

        $manager->landlord_id = $landlord->id;
        $manager->save();

        Mail::to($landlord)->send(new AddPropertyLandlordMail($landlord->name, $this->user->name, $new_user, $new_user ? $landlord->verification_token : ""));
        NotificationController::store('landlord', $landlord->id, "Added as Landlord", "You've just been added as the Landlord to a Property, {$property->name}, managed by ".$this->user->name, "properties", $property->uuid);
        NoticeController::land_log_activity($this->user->id, "Added Ladlord, {$landlord->name}, to Property, {$property->title}", "properties", $property->uuid);

        return response([
            'status' => 'success',
            'message' => 'Landlord added successfully',
            'data' => self::property($property)
        ], 200);
    }

    public function update_tenant(StorePropertyTenantRequest $request, $uuid){
        $tenant = PropertyTenant::where('uuid', $uuid)->first();
        if(empty($tenant) or empty(PropertyManager::where('manager_id', $this->user->id)->where('property_id', $tenant->property_id)->first())){
            return response([
                'status' => 'failed',
                'message' => 'No Tenant was fetched'
            ], 404);
        }
        if($request->lease_end < $request->lease_start){
            return response([
                'status' => 'failed',
                'message' => 'Lease End date must be later than Lease start date'
            ], 409);
        }

        $within = PropertyTenant::where('property_unit_id', $tenant->property_unit_id);
        if($within->where('lease_start', '<=', $request->lease_start)->where('lease_end', '>=', $request->lease_start)->where('id', '<>', $tenant->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant1'
            ], 409);
        }
        if($within->where('lease_start', '<=', $request->lease_end)->where('lease_end', '>=', $request->lease_end)->where('id', '<>', $tenant->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant2'
            ], 409);
        }
        if($within->where('lease_start', '>', $request->lease_start)->where('lease_end', '<', $request->lease_end)->where('id', '<>', $tenant->id)->count() > 0){
            return response([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant3'
            ], 409);
        }

        $today = date('Y-m-d');
        $current = false;
        $all = $request->all();
        if(($today >= $request->lease_start) and ($today <= $request->lease_end)){
            $current = true;
            $unit = PropertyUnit::find($tenant->property_unit_id);
            $unit->occupation_status = 'occupied';
            $unit->save();
        }
        $all['current'] = $current;
        if(isset($all['no_of_installments']) and ($all['no_of_installments'] > 1)){
            $all['installment_amount'] = $all['rent_amount'] / $all['no_of_installments'];
        } else {
            $all['installment_amount'] = $all['rent_amount'];
        }

        if(!$tenant->update($all)){
            return response([
                'status' => 'failed',
                'message' => 'Tenant Update failed'
            ]);
        }

        if(($tenant->current_tenant == 1) and (!empty($tenant->rent_due_date))){
            $due_date = DueDate::where('purpose', 'Rent Due Date')->where('property_tenant_id', $tenant->id)->where('status', 1)->first();
            if(!empty($due_date)){
                $due_date->due_date = $tenant->rent_due_date;
                $due_date->save();
            } else {
                DueDate::create([
                    'landlord_id' => $this->user->id,
                    'property_tenant_id' => $tenant->id,
                    'property_id' => $tenant->property_id,
                    'property_unit_id' => $tenant->property_unit_id,
                    'due_date' => $tenant->rent_due_date,
                    'purpose' => 'Rent Due Date',
                    'cash_payment' => true
                ]);
            }
        }

        NoticeController::land_log_activity($this->user->id, "Edited Tenant Details", "tenants", $tenant->uuid);

        return response([
            'status' => 'success',
            'message' => 'Tenant updated successfully',
            'data' => $tenant
        ], 200);
    }
}
