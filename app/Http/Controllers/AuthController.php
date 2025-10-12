<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use App\Mail\UserPasswordResetMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreUserRequest;
use App\Mail\UserEmailVerificationMail;
use App\Http\Requests\UpdateUserRequest;
use App\Models\CustomerFlutterwaveToken;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\AccountActivationRequest;

class AuthController extends Controller
{
    public $errors;

    public function store(StoreUserRequest $request)
    {
        $prev = User::where('status', '<>', 2)->where('email', $request->email)->first();
        if(!empty($prev)){
            return response([
                'status' => 'failed',
                'message' => 'Emai unavailable'
            ], 422);
        }
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
        $all['uuid'] = $uuid;
        $all['password'] = Hash::make($request->password);
        $all['verification_token'] = Str::random(20).time();
        $all['verification_token_expiry'] = date('Y-m-d H:i:s', time() + 1200);
        
        if(!$user = User::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Registration failed. Please try again later'
            ], 500);
        }
        Mail::to($user)->send(new UserEmailVerificationMail($user->name, $user->verification_token));      

        if(!$login = $this->login_function($user->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 401);
        }

        return response([
            'status' => 'success',
            'message' => 'Signup succesful',
            'data' => $login
        ], 200);
    }

    public function login_function($email, $password){
        $token = auth('user-api')->attempt(['email' => $email, 'password' => $password]);
        if(!$token){
            $this->errors = "Wrong Credentials";
            return false;
        }

        $user = User::where('email', $email)->first();
        $user->prev_login = !empty($user->last_login) ? $user->last_login : date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();
        $this->clear_flutterwave_tokens($user->id);
        $user->sections = json_decode($user->sections, true) ?? [];

        $authorization = [
            'token' => $token,
            'type' => 'Bearer',
            'expires' => 1440 * 60
        ];

        $user->authorization = $authorization;

        return $user;
    }

    private function clear_flutterwave_tokens($user_id){
        $tokens = CustomerFlutterwaveToken::where('user_id', $user_id)->where('token_expiry', '<', date('Y-m-d'))->get();
        if(!empty($tokens)){
            foreach($tokens as $token){
                $token->delete();
            }
        }
    }

    public function login(LoginRequest $request){
        $user = User::where('email', $request->email)->first();
        if($user->status == 2){
            return response([
                'status' => 'failed',
                'message' => 'No User was found'
            ], 422);
        }
        if($user->status == 0){
            return response([
                'status' => 'failed',
                'message' => 'Not an active account'
            ], 409);
        }

        if(!$login = $this->login_function($request->email, $request->password)){
            return response([
                'status' => 'failed',
                'message' => $this->errors
            ], 401);
        }

        return response([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => $login
        ], 200);
    }

    public function activate_account(ActivateAccountRequest $request){
        $user = User::where('verification_token', $request->token)->first();
        if($user->email_verified == 1){
            $user->verification_token = NULL;
            $user->verification_token_expiry = NULL;
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Email already verified'
            ], 422);
        }
        
        if($user->verification_token_expiry < date('Y-m-d H:i:s')){
            return response([
                'status' => 'failed',
                'message' => 'Expired Link'
            ], 422);
        }

        $user->email_verified = 1;
        $user->verification_token = NULL;
        $user->verification_token_expiry = NULL;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Email verified successful'
        ], 200);
    }

    public function activate_from_addition(AccountActivationRequest $request){
        $user = User::where('verification_token', $request->token)->first();
        if(empty($user)){
            return response([
                'status' => 'failed',
                'message' => 'User Not Found'
            ], 404);
        }

        if($user->verification_token_expiry < date('Y-m-d H:i:s')){
            return response([
                'status' => 'failed',
                'message' => 'Expired Link'
            ], 422);
        }
        $user->password = Hash::make($request->password);
        $user->email_verified = 1;
        $user->verification_token = NULL;
        $user->verification_token_expiry = NULL;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Account successfully activated'
        ], 200);
    }

    public function resend_activation_link(){
        $user = User::find(self::user()->id);
        if($user->email_verified == 1){
            $user->verification_token = NULL;
            $user->verification_token_expiry = NULL;
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Email already verified'
            ], 422);
        }

        $user->verification_token = Str::random(20).time();
        $user->verification_token_expiry = date('Y-m-d H:i:s', time() + 1200);
        $user->save();
        Mail::to($user)->send(new UserEmailVerificationMail($user->name, $user->verification_token));

        return response([
            'status' => 'success',
            'message' => 'Activation Link has been sent to '.$user->email
        ], 200);
    }

    public function forgot_password(ForgotPasswordRequest $request){
        $user = User::where('email', $request->email)->where('status', 1)->first();
        if(empty($user)){
            return response([
                'status' => 'failed',
                'message' => 'No User was fetched'
            ], 404);
        }

        $user->token = Str::random(20).time();
        $user->token_expiry = date('Y-m-d H:i:s', time() + 300);
        $user->save();
        Mail::to($user)->send(new UserPasswordResetMail($user->name, $user->token));

        return response([
            'status' => 'success',
            'message' => 'Password reset Link sent to '.$user->email
        ], 200);
    }

    public function reset_password(ResetPasswordRequest $request){
        $user = User::where('token', $request->token)->first();
        if($user->token_expiry < date('Y-m-d H:i:s')){
            $user->token = NULL;
            $user->token_expiry = NULL;
            $user->save();
            return response([
                'status' => 'failed',
                'message' => 'Expired Link'
            ], 409);
        }
        $user->password = Hash::make($request->password);
        $user->token = NULL;
        $user->token_expiry = NULL;
        $user->save();

        return response([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ], 200);
    }

    public static function user() : User
    {
        return auth('user-api')->user();
    }

    public function me(){
        $user = self::user();
        $user->sections = json_decode($user->sections, true);
        return response([
            'status' => 'success',
            'message' => 'User details fetched successfully',
            'data' => $user
        ], 200);
    }

    public function update_sections(UpdateSectionRequest $request)
    {
        $user = self::user();
        if(empty($user)){
            return response([
                'status' => 'failed',
                'message' => 'User not found'
            ], 404);
        }

        $user->sections = json_encode($request->sections);
        $user->save();
        $user->sections = json_decode($user->sections, true);

        return response([
            'status' => 'success',
            'message' => 'Sections updated successfully',
            'data' => $user
        ], 200);
    }

    public function switch_section($section)
    {
        $user = self::user();
        if(empty($user)){
            return response([
                'status' => 'failed',
                'message' => 'User not found'
            ], 404);
        }

        $sections = json_decode($user->sections, true);
        if(!in_array($section, $sections)){
            return response([
                'status' => 'failed',
                'message' => 'Section not available'
            ], 422);
        }

        $user->section = $section;
        $user->save();
        $user->sections = json_decode($user->sections, true);

        return response([
            'status' => 'success',
            'message' => 'Section switched successfully',
            'data' => $user
        ], 200);
    }

    public function update(UpdateUserRequest $request)
    {
        $user = self::user();
        if(empty($user)){
            return response([
                'status' => 'failed',
                'message' => 'User not found'
            ], 404);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        if($request->has('phone')){
            $user->phone = $request->phone;
        }
        if($request->has('sections')){
            $user->sections = json_encode($request->sections);
        }
        $user->save();
        $user->sections = json_decode($user->sections, true);

        return response([
            'status' => 'success',
            'message' => 'User details updated successfully',
            'data' => $user
        ], 200);
    }
}
