<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\StoreReminderRequest;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReminderController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public function store(StoreReminderRequest $request){
        $uuid = "";
        for($i=1; $i<=40; $i++){
            $t_uuid = Str::uuid();
            if(Reminder::where('uuid', $t_uuid)->count() < 1){
                $uuid = $t_uuid;
                break;
            } else {
                continue;
            }
        }
        if(empty($uuid)){
            return response([
                'status' => 'failed',
                'message' => 'Reminder addition failed. Please try again later'
            ], 500);
        }
        $all = $request->all();
        $all['uuid'] = $uuid;
        $all['user_type'] = 'landlord';
        $all['user_id'] = $this->user->id;
        $all['recipient_type'] = 'landlord';
        $all['recipient_id'] = $this->user->id;
        $all['frequency_type'] = 'one_time';

        if(!$reminder = Reminder::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Failed to add Reminder'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Reminder added successfully',
            'data' => $reminder
        ], 200);
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $reminders = Reminder::where('user_id', $this->user->id)->orderBy('next_reminder_date', 'desc');
        if($reminders->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Reminder was fetched',
                'data' => []
            ], 200);
        }

        $reminders = $reminders->paginate($limit);

        return response([
            'status' => 'success',
            'message' => 'Reminders fetched successfully',
            'data' => $reminders
        ], 200);
    }

    public function show($uuid){
        $reminder = Reminder::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
        if(empty($reminder)){
            return response([
                'status' => 'failed',
                'message' => 'No Reminder was fetched' 
            ], 404);
        }

        return response([
            'status' => 'success',
            'message' => 'Reminder fetched succesfully',
            'data' => $reminder
        ], 200);
    }

    public function update(StoreReminderRequest $request, $uuid){
        $reminder = Reminder::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
        if(empty($reminder)){
            return response([
                'status' => 'failed',
                'message' => 'No Reminder was fetched'
            ], 404);
        }

        if(!$reminder->update($request->all())){
            return response([
                'status' => 'failed',
                'message' => 'Reminder Update Failed'
            ], 500);
        }

        return response([
            'status' => 'success',
            'message' => 'Reminder Updated Successfully',
            'data' => $reminder
        ], 200);
    }

    public function destroy($uuid){
        $reminder = Reminder::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
        if(empty($reminder)){
            return response([
                'status' => 'failed',
                'message' => 'No Reminder was fetched'
            ], 404);
        }

        if(!$reminder->delete()){
            return response([
                'status' => 'failed',
                'message' => 'Failed to delete Reminder'
            ], 409);
        }

        return response([
            'status' => 'success',
            'message' => 'Reminder deleted successfully',
            'data' => $reminder
        ], 200);
    }
}