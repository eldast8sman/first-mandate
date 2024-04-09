<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\SendNoticeRequest;
use App\Mail\LandlordSendTenantNotice;
use App\Models\Notice;
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

        $notices = Notice::where('sender_type', 'landlord')->where('sender_id', $this->user->id);
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
        if($tenant->landlord_id != $this->user->id){
            return response([
                'status' => 'failed',
                'message' => 'No Tenant was fetched'
            ], 404);
        }

        $all = $request->all();
        $all['sender_type'] = 'landlord';
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

        Mail::to($tenant)->send(new LandlordSendTenantNotice($tenant->name, $this->user->name, $notice->description));

        return response([
            'status' => 'success',
            'message' => 'Notice sent successfully',
            'data' => $notice
        ], 200);
    }
}
