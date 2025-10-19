@extends('emails.layouts.app')

@section('content')
  <div id="user-content" style="margin: 17px 0; text-align: center">
    <p
      style="
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        line-height: 28px;
        margin: 0;
        color: #e74c3c;
      "
    >
      Notice Reminder - Action Required
    </p>
  </div>
  
  <div id="user-content" style="margin: 17px 0; text-align: center">
    <p
      style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
      "
    >
      Dear {{ $recipient_name }},
    </p>
    <p
      style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 15px 0;
      "
    >
      This is a friendly reminder that you have an unacknowledged notice from your {{ ucfirst($sender_type) }}, <strong>{{ $sender_name }}</strong>.
    </p>
  </div>

  <div style="background-color: #f8f9fa; border-left: 4px solid #e74c3c; padding: 15px; margin: 20px 0; text-align: left;">
    <h3 style="margin: 0 0 10px 0; color: #333; text-align: center;">Notice Details:</h3>
    <p style="margin: 5px 0; text-align: center;"><strong>Type:</strong> {{ $notice_type }}</p>
    @if($notice_date)
    <p style="margin: 5px 0; text-align: center;"><strong>Notice Date:</strong> {{ \Carbon\Carbon::parse($notice_date)->format('F j, Y') }}</p>
    @endif
    @if($notice_time)
    <p style="margin: 5px 0; text-align: center;"><strong>Notice Time:</strong> {{ $notice_time }}</p>
    @endif
    <p style="margin: 5px 0; text-align: center;"><strong>Sent:</strong> {{ $created_at }}</p>
    <p style="margin: 10px 0 5px 0; text-align: center;"><strong>Description:</strong></p>
    <div style="background-color: #fff; padding: 10px; border-radius: 4px; margin: 5px 0; text-align: center;">
      {{ $notice_description }}
    </div>
  </div>

  <div id="user-content" style="margin: 17px 0; text-align: center">
    <p
      style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
        color: #e74c3c;
      "
    >
      <strong>Action Required:</strong> Please acknowledge this notice in your 1st Mandate app to confirm you have received and read it.
    </p>
  </div>

  <div style="text-align: center; margin: 30px 0;">
    <a href="{{ env('FRONTEND_URL') }}" style="
      background-color: #3498db; 
      color: white; 
      padding: 12px 25px; 
      text-decoration: none; 
      border-radius: 5px; 
      display: inline-block;
      font-size: 16px;
    ">
      Open 1st Mandate App
    </a>
  </div>

  <div id="user-content" style="margin: 17px 0; text-align: center">
    <p
      style="
        text-align: center;
        font-size: 14px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
        color: #666;
      "
    >
      If you have already acknowledged this notice, please disregard this reminder.
    </p>
  </div>
@endsection
