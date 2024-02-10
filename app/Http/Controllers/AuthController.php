<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\UserEmailVerificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public $errors;

    public function store(StoreUserRequest $request)
    {
        $all = $request->except('password');
        $uuid = "";
        for($i=1; $i<=20; $i++){
            $temp_uuid = Str::uuid();
            $found = User::where('uuid', $temp_uuid)->first();
            if(empty($found)){
                $uuid = $temp_uuid;
                break;
            }
        }
        if(empty($uuid)){
            return response([
                'status' => 'failed',
                'message' => 'Network Error! Please try again later'
            ], 500);
        }
        $all['password'] = Hash::make($request->password);
        $all['verification_token'] = Str::random(20).time();
        $all['verification_token_expiry'] = date('Y-m-d H:i:s', time() + 1200);
        
        if(!$user = User::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Registration failed. Please try again later'
            ], 500);
        }
        $user->name = $user->first_name.' '.$user->last_name;
        Mail::to($user)->send(new UserEmailVerificationMail($user->name, $user->verification_token));
        unset($user->name);

        
    }

    public function login($email, $password){
        $token = auth('user-api')->attempt(['email' => $email, 'password' => $password]);
        if(!$token){
            $this->errors = "Wrong Credentials";
            return false;
        }

        $user = User::where('email', $email)->first();
        $user->prev_login = !empty($user->last_login) ? $user->last_login : date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        $authorization = [
            'token' => $token,
            'type' => 'Bearer',
            'expires' => 1440 * 60
        ];

        return $authorization;
    }

    public function show(User $user)
    {
        //
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        //
    }
}
