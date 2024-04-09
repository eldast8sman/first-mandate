<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Tenant\AcknowledgeNoticeRequest;
use App\Models\Notice;
use App\Models\PropertyTenant;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->middleware('auth:user-api');
        $this->user = AuthController::user();
    }

    public static function notice(Notice $notice) : Notice
    {
        $tenant = PropertyTenant::find($notice->tenant_id);
        $notice->apartment = ApartmentController::apartment($tenant);

        return $notice;
    }

    public function pending_notices(){
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $notices = Notice::where('receiver_type', 'tenant')->where('receiver_id', $this->user->id)->where('acknowledged_status', 'pending');
        if($notices->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'There are no Pending Notices',
                'data' => []
            ]);
        }
        $notices = $notices->orderBy('created_at', 'asc')->paginate($limit);
        foreach($notices as $notice){
            $notice = self::notice($notice);
        }

        return response([
            'status' => 'success',
            'message' => 'Pending Notices fetched successfully',
            'data' => $notices
        ], 200);
    }

    public function index(){
        $status = !empty($_GET['acknowledged_status']) ? (string)$_GET['acknowledged_status'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $notices = Notice::where('receiver_type', 'tenant')->where('receiver_id', $this->user->id);
        if(!empty($status)){
            $notices = $notices->where('acknowledged_status', $status);
        }
        if($notices->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'No Notice ha been sent to you yet',
                'data' => []
            ], 200);
        }

        $notices = $notices->orderBy('created_at', 'desc')->paginate($limit);
        foreach($notices as $notice){
            $notice = self::notice($notice);
        }

        return response([
            'status' => 'success',
            'message' => 'Notices fetched successfully',
            'data' => $notices
        ], 200);
    }

    public function acknowledge_notice(AcknowledgeNoticeRequest $request){
        $notice = Notice::where('uuid', $request->uuid)->where('receiver_type', 'tenant')->where('receiver_id', $this->user->id)->first();
        if(empty($notice)){
            return response([
                'status' => 'failed',
                'message' => 'No Notice was fetched'
            ], 404);
        }
        if(strtolower($notice->acknowledged_status) != 'pending'){
            return response([
                'status' => 'failed',
                'message' => 'You can only acknowledge a pending Notice'
            ], 409);
        }

        $notice->acknowledged_status = $request->acknowledged_status;
        if(!empty($notice->remarks)){
            $notice->remarks = $request->remarks;
        }
        $notice->save();
        NotificationController::store($notice->sender_type, $notice->sender_id, "Notice Feedback", "{$this->user->name} has given a feedback to the notice you sent", $notice->uuid);
        self::land_log_activity($this->user->id, "Reacted to a Notice", "notices", $notice->uuid);

        return response([
            'status' => 'success',
            'message' => 'Notice Acknowledged successfully',
            'data' => $notice
        ], 200);
    }

    public static function land_log_activity($user_id, $activity, $page="", $identifier=null){
        self::log_activity($user_id, 'tenant', $activity, $page, $identifier);
    }
}
