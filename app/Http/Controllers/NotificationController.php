<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function store($section, $user_id, $title, $notification, $page="", $identifier=null){
        Notification::create([
            'user_id' => $user_id,
            'section' => $section,
            'title' => $title,
            'notification' => $notification,
            'page' => $page,
            'identifier' => $identifier
        ]);

        return true;
    }

    public function notification_count(){
        $count = Notification::where('user_id', $this->user->id)->where('status', 0)->count();

        return response([
            'status' => 'success',
            'message' => 'Notification count fetched successfully',
            'data' => ['count' => $count]
        ], 200);
    }

    public function index(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $notifications = Notification::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->paginate($limit);

        return response([
            'status' => 'success',
            'message' => 'Notification fetched successfully',
            'data' => $notifications
        ], 200);
    }

    public function open(Notification $notification){
        $notification->status = 1;
        $notification->save();

        return response([
            'status' => 'success',
            'message' => 'Notification saved successfully'
        ], 200);
    }

    public function mark_all_as_opened(){
        $notifications = Notification::where('user_id', $this->user->id)->where('status', 0);
        if($notifications->count() > 0){
            foreach($notifications->get() as $notification){
                $notification->status = 1;
                $notification->save();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Notifications marked as opened successfully'
        ], 200);
    }

    public function activity_logs(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 50;
        
        $logs = ActivityLog::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->paginate($limit);
        return response([
            'status' => 'success',
            'message' => 'Activity Logs fetched successfully',
            'data' => $logs
        ], 200);
    }
}
