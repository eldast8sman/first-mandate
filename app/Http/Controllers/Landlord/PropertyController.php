<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\StorePropertyManagerRequest;
use App\Http\Requests\Landlord\StorePropertyRequest;
use App\Http\Requests\Landlord\StorePropertyTenantRequest;
use App\Http\Requests\Landlord\StorePropertyUnitRequest;
use App\Mail\AddPropertyManagerMail;
use App\Mail\AddTenantMail;
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
        $prop_managers = [];
        $managers = PropertyManager::where('property_id', $property->id)->get();
        if(!empty($managers)){
            foreach($managers as $manager){
                unset($manager->id);
                $prop_managers[] = $manager;
            }
        }
        $property->property_managers = $prop_managers;
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

    public static function manager(PropertyManager $manager) : PropertyManager {
        $property = Property::find($manager->property_id);
        $manager->property = $property;
        
        return $manager;
    }

    public static function tenant(PropertyTenant $tenant) : PropertyTenant {
        $property = Property::find($tenant->property_id);
        $tenant->property = $property;
        return $tenant;
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $properties = Property::where('landlord_id', $this->user->id)->paginate($limit);
        if(!empty($properties)){
            foreach($properties as $property){
                $property = self::property($property);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Properties fetched successfully',
            'data' => $properties
        ], 200);
    }

    public function store(StorePropertyRequest $request){
        $all = $request->except(['manager_first_name', 'manager_last_name', 'manager_emsil', 'manager_phone']);
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
        $all['landlord_id'] = $this->user->id;
        if(!$property = Property::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Server Error! Please try again later'
            ], 500);
        }

        if(!empty($request->manager_email)){
            if(empty($manager = User::where('email', $request->manager_email)->first())){
                $m_uuid = "";
                for($i=1; $i<=40; $i++){
                    $t_uuid = Str::uuid();
                    if(User::where('uuid', $t_uuid)->count() < 1){
                        $m_uuid = $t_uuid;
                        break;
                    } else {
                        continue;
                    }
                }
                if(empty($m_uuid)){
                    $property->delete();

                    return response([
                        'status' => 'failed',
                        'message' => 'Property Upload could not be completed. Please try again later'
                    ], 500);
                }
                if(!$manager = User::create([
                    'uuid' => $m_uuid,
                    'email' => $request->manager_email,
                    'name' => $request->manager_name,
                    'phone' => $request->manager_phone ?? '',
                    'verification_token' => Str::random(20).time(),
                    'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                    'roles' => 'property manager',
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
                if(!str_contains($manager->roles, 'property manager')){
                    $manager->roles = !empty($manager->roles) ? $manager->roles.',property manager' : 'property manager';
                    $manager->save();
                }
                $new_user = false;
            }

            $tuuid = "";
            for($i=1; $i<=40; $i++){
                $t_uuid = Str::uuid();
                if(PropertyManager::where('uuid', $t_uuid)->count() < 1){
                    $tuuid = $t_uuid;
                    break;
                } else {
                    continue;
                }
            }
            if(empty($tuuid)){
                return response([
                    'status' => 'failed',
                    'message' => 'Property Upload could not be completed. Please try again later'
                ], 500);
            }
            PropertyManager::create([
                'uuid' => $tuuid,
                'property_id' => $property->id,
                'landlord_id' => $this->user->id,
                'manager_id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'phone' => !empty($manager->phone) ? (string)$manager->phone : "",
                'status' => 1
            ]);

            $user = User::find($this->user->id);
            if(!str_contains($user->roles, 'landlord')){
                $user->roles = !empty($user->roles) ? $user->roles.',landord' : 'landlord';
                $user->save();
            }

            Mail::to($manager)->send(new AddPropertyManagerMail($manager->name, $this->user->name, $new_user, $new_user ? $manager->verification_token : ""));
        }
        return response([
            'status' => 'success',
            'message' => 'Property added successfully',
            'data' => $property
        ], 200);
    }

    public function show($uuid){
        $property = Property::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($property)){
            return response([
                'status' => 'failed',
                'message' => 'No Property was fetched'
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Property fetched successfully',
            'data' => self::property($property)
        ], 200);
    }

    public function store_manager(StorePropertyManagerRequest $request){
        $property = Property::where('uuid', $request->property_uuid)->first();
        if(empty($property) or ($property->landlord_id != $this->user->id)){
            return response([
                'status' => 'failed',
                'message' => 'No Property was fetched'
            ], 404);
        }

        if(!empty($request->email)){
            if(PropertyManager::where('email', $request->email)->where('property_id', $property->id)->count() > 0){
                return response([
                    'status' => 'failed',
                    'message' => 'This person already manages this Property'
                ], 409);
            }
        }

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
                    'message' => 'There was an error addng Property. Please try again later'
                ], 500);
            }
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'verification_token' => Str::random(20).time(),
                'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                'roles' => 'property manager',
                'uuid' => $uuid
            ]);
            if(!$user){
                return response([
                    'status' => 'failed',
                    'message' => 'Property Manager was not added as a User. Please try again later'
                ], 500);
            }

            $new_user = true;
        } else {
            if(!str_contains($user->roles, 'property manager')){
                $user->roles = !empty($user->roles) ?  $user->roles.',property manager' : 'property manager';
                $user->save();
            }
            $new_user = false;
        }
        $tuuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(PropertyManager::where('uuid', $t_uuid)->count() < 1){
                $tuuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($tuuid)){
            return response([
                'status' => 'failed',
                'message' => 'Property Manager addition failed.'
            ], 500);
        }
        $manager = PropertyManager::create([
            'uuid' => $tuuid,
            'property_id' => $property->id,
            'landlord_id' => $this->user->id,
            'manager_id' => $user->id,
            'name' => $request->name,
            'phone' => !empty($request->phone) ? $request->phone : "",
            "email" => $request->email,
            "status" => 1
        ]);

        Mail::to($user)->send(new AddPropertyManagerMail($user->name, $this->user->name, $new_user, $new_user ? $user->verification_token : ""));

        return response([
            'status' => 'success',
            'message' => 'Property Manager added successfully',
            'data' => $manager
        ], 200);
    }

    public function property_managers(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $managers = PropertyManager::where('landlord_id', $this->user->id)->orderBy('property_id', 'asc')->orderBy('name', 'asc')->paginate($limit);
        if(!empty($managers)){
            foreach($managers as $manager){
                $manager = self::manager($manager);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Property Managers fetched successfully',
            'data' => $managers
        ], 200);
    }

    public function property_manager($uuid){
        $manager = PropertyManager::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($manager)){
            return response([
                'status' => 'failed',
                'message' => 'No Property Manager was fetched',
                'data' => $manager
            ], 200);
        }

        return response([
            'status' => 'success',
            'message' => 'Property Manager fetched successfully',
            'data' => self::manager($manager)
        ], 200);
    }

    public function tenants(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $tenants = PropertyTenant::where('landlord_id', $this->user->id)->orderBy('property_id', 'asc')->orderBy('name', 'asc')->paginate($limit);
        if(!empty($tenants)){
            foreach($tenants as $tenant){
                $tenant = self::tenant($tenant);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Tenants fetched successfully',
            'data' => $tenants
        ], 200);
    }

    public function store_unit(StorePropertyUnitRequest $request, $uuid){
        $property = Property::where('uuid', $uuid)->first();
        if(empty($property) or ($property->landlord_id != $this->user->id)){
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
        $all['landlord_id'] = $this->user->id;

        if(!$unit = PropertyUnit::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Unt Addition Failed. Please try again later'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Property Unit added successfully',
            'data' => $unit
        ], 200);
    }

    public function store_tenant(StorePropertyTenantRequest $request, $uuid){
        $unit = PropertyUnit::where('uuid', $uuid)->first();
        if(empty($unit) or ($unit->landlord_id != $this->user->id)){
            return response([
                'status' => 'failed',
                'message' => 'No Property Unit was fetched'
            ], 404);
        }

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
            return request([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant'
            ], 409);
        }
        if($within->where('lease_start', '<=', $request->lease_end)->where('lease_end', '>=', $request->lease_end)->count() > 0){
            return request([
                'status' => 'failed',
                'message' => 'The tenancy period is overlapping that of another Tenant'
            ], 409);
        }
        if($within->where('lease_start', '>', $request->lease_start)->where('lease_end', '<', $request->lease_end)->count() > 0){
            return request([
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
        }
        $all['current_tenant'] = $current;
        $all['user_id'] = isset($user) ? $user->id : null;
        $all['landlord_id'] = $this->user->id;
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

        Mail::to($user)->send(new AddTenantMail($user->name, $this->user->name, $new_user, $new_user ? $user->verification_token : ""));

        return response([
            'status' => 'success',
            'message' => 'Tenant added successfully',
            'data' => $tenant
        ], 200);
    }

    public function update_manager(StorePropertyManagerRequest $request, $uuid){
        $manager = PropertyManager::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($manager)){
            return response([
                'status' => 'failed',
                'message' => 'No Manager was fetched'
            ]);
        }

        $all = $request->all();
        if(!$manager->update($all)){
            return response([
                'status' => 'failed',
                'message' => 'Failed to update Manager'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Property Manager updated successfully',
            'data' => $manager
        ], 200);
    }

    public function update_tenant(StorePropertyTenantRequest $request, $uuid){
        $tenant = PropertyTenant::where('uuid', $uuid)->where('landlord_id', $this->user->id)->first();
        if(empty($tenant)){
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
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Tenant updated successfully',
            'data' => $tenant
        ], 200);
    }
}
