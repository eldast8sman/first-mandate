@extends('emails.layouts.app')

@section('content')
<div id="user-content" style="margin: 17px 0; text-align: center">
  <div id="logo" style="margin: 13px 0; text-align: center">
    <img
      src="https://i.postimg.cc/RFnKCmhw/welcome.png"
      alt="1st Mandate"
    />
  </div>
  <p
    style="
      text-align: center;
      font-size: 16px;
      font-weight: 100;
      line-height: 28px;
      margin: 0;
    "
  >
    🔔 Reminder Notification
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
    Dear {{ $user_name }},
  </p>
  <p
    style="
      text-align: center;
      font-size: 16px;
      font-weight: 100;
      line-height: 28px;
      margin: 0;
    "
  >
    This is a friendly reminder about your scheduled task:
  </p>
</div>
<div id="user-content" style="margin: 17px 0; text-align: center">
  <div style="
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin: 20px 0;
    border-left: 4px solid #000;
  ">
    <h3 style="
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      margin: 10px 0;
      color: #000;
    ">
      {{ $reminder_type }}
    </h3>
    
    @if($short_description)
      <p style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 10px 0;
      ">
        <strong>Description:</strong> {{ $short_description }}
      </p>
    @endif
    
    @if($money_reminder && $amount)
      <p style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 10px 0;
      ">
        <strong>Amount:</strong>
      </p>
      <div style="
        font-size: 24px;
        font-weight: bold;
        color: #28a745;
        margin: 10px 0;
        text-align: center;
      ">
        ${{ number_format($amount, 2) }}
      </div>
    @endif
    
    <p style="
      text-align: center;
      font-size: 16px;
      font-weight: 100;
      line-height: 28px;
      margin: 10px 0;
    ">
      <strong>Date:</strong> {{ date('F j, Y', strtotime($reminder_date)) }}
    </p>
    
    @if($reminder_time)
      <p style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 10px 0;
      ">
        <strong>Time:</strong> {{ date('g:i A', strtotime($reminder_time)) }}
      </p>
    @endif
  </div>
</div>

@if($money_reminder)
<div id="user-content" style="margin: 17px 0; text-align: center">
  <p style="
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    line-height: 28px;
    margin: 0;
    color: #dc3545;
  ">
    💰 This is a money-related reminder. Please ensure you have the necessary funds available.
  </p>
</div>
@endif

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
    Please take the necessary action as required.
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
    Best regards,
  </p>
  <p
    style="
      text-align: center;
      font-size: 16px;
      font-weight: 100;
      line-height: 28px;
      margin: 0;
    "
  >
    1st Mandate
  </p>
</div>
@endsection
