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
      Welcome to 1st Mandate
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
      Dear {{ $name }},
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
      Welcome to 1st Mandate! We're thrilled to have you on board. 
      You have been added as the Landlord of a Property managed by {{ $manager }} on this Platform.
      <br>
      @if ($new_user)
      In order to have full access to your account, please click on the link below
      @else
      Login to your Account to have more details  
      @endif
    </p>
  </div>
  <div id="user-content" style="margin: 17px 0; text-align: center">
    @if ($new_user)
      <a href="{{ env('FRONTEND_URL') }}/activate-account?verification_token={{ $token }}&role=landlord"><button
        style="
          background-color: #000;
          color: #ffffff;
          padding: 12px 0;
          border: transparent;
          font-weight: inherit;
          border-radius: 5px;
          width: 300px;
          margin: 13px 0;
          text-align: center;
          cursor: pointer;
          font-size: 16px;
        "
      >
        Activate Account
      </button></a>
    @else
      <a href="{{ env('FRONTEND_URL') }}/login"><button
        style="
          background-color: #000;
          color: #ffffff;
          padding: 12px 0;
          border: transparent;
          font-weight: inherit;
          border-radius: 5px;
          width: 300px;
          margin: 13px 0;
          text-align: center;
          cursor: pointer;
          font-size: 16px;
        "
      >
        Login
      </button></a>   
    @endif
    
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


        