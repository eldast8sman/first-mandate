<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Manager\SendNoticeRequest;
use App\Mail\ManagerSendTenantNotice;
use App\Models\Notice;
use App\Models\PropertyManager;
use App\Models\PropertyTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        $notice->tenant = PropertyController::tenant($tenant);
        return $notice;
    }

    public function index(){
        $status = !empty($_GET['acknowledged_status']) ? (string)$_GET['acknowledged_status'] : "";
        $limit = !empty($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $notices = Notice::where('sender_type', 'manager')->where('sender_id', $this->user->id);
        if(!empty($status)){
            $notices = $notices->where("acknowledged_status", $status);
        }
        if($notices->count() < 1){
            return response([
                'status' => 'failed',
                'message' => 'You are yet to send any Notice',
                'data' => []
            ]);
        }

        $notices = $notices->paginate($limit);
        foreach($notices as $notice){
            $notice = self::notice($notice);
        }

        return response([
            'status' => 'success',
            'message' => 'Sent Notices fetched successfully',
            'data' => $notices
        ], 200);
    }

    public function send_notice(SendNoticeRequest $request){
        $tenant = PropertyTenant::where('uuid', $request->tenant_uuid)->first();
        if(empty(PropertyManager::where('manager_id', $this->user->id)->where('property_id', $tenant->property_id)->first())){
            return response([
                'status' => 'failed',
                'message' => 'No Tenant was fetched'
            ], 404);
        }

        $all = $request->all();
        $all['sender_type'] = 'manager';
        $all['sender_id'] = $this->user->id;
        $all['receiver_type'] = 'tenant';
        $all['receiver_id'] = $tenant->user_id;
        $all['tenant_id'] = $tenant->id;

        if(!$notice = Notice::create($all)){
            return response([
                'status' => 'failed',
                'message' => 'Notice sending failed'
            ], 500);
        }

        Mail::to($tenant)->send(new ManagerSendTenantNotice($tenant->name, $this->user->name, $notice->description));
        NotificationController::store('tenant', $tenant->user_id, "Notice set to you", "Your manager, {$this->user->name}, just sent you a Notice", "notices", $notice->uuid);
        self::land_log_activity($this->user->id, "You sent a Notice to your Tenant, {$tenant->name}", "notices", $notice->uuid);

        return response([
            'status' => 'success',
            'message' => 'Notice sent successfully',
            'data' => $notice
        ], 200);
    }

    public static function land_log_activity($user_id, $activity, $page="", $identifier=null){
        self::log_activity($user_id, 'manager', $activity, $page, $identifier);
    }
}
