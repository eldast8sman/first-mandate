<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\StorePropertyRequest;
use App\Mail\AddPropertyManagerMail;
use App\Models\Property;
use App\Models\PropertyManager;
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
        return $property;
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
                    'verification_token' => Str::random(20).time(),
                    'verification_token_expiry' => date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7),
                    'roles' => 'property manager'
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
                    $manager->roles = !empty($property->roles) ? $manager->roles.',property manager' : 'property manager';
                    $manager->save();
                }
                $new_user = false;
            }

            PropertyManager::create([
                'property_id' => $property->id,
                'landlord_id' => $this->user->id,
                'manager_id' => $manager->id,
                'name' => $manager->name
            ]);

            $user = User::find($this->user->id);
            if(!str_contains($user->roles, 'landlord')){
                $user->roles = !empty($user->roles) ? $user->roles.',landord' : 'landlord';
                $user->save();
            }

            $landlord = $this->user->first_name.' '.$this->user->last_name;
            $manager->name = $manager->first_name.' '.$manager->last_name;
            Mail::to($manager)->send(new AddPropertyManagerMail($manager->first_name, $landlord, $new_user, $new_user ? $manager->verification_token : ""));

            return response([
                'status' => 'success',
                'message' => 'Property added successfully',
                'data' => $property
            ], 200);
        }
    }

    public function show($uuid){
        $property = Property::where('uuid', $uuid)->where('landlord', $this->user->id)->frst();
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
}
