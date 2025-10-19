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
      You just received a Notice from your Landlord
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
      Your Landlord, {{ $landlord }} just sent you a notice on the 1st Mandate App.   
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
      
      <p style="
          text-align: center;
          font-size: 16px;
          font-weight: 100;
          line-height: 28px;
          margin: 10px 0;
        ">
          <strong>Notice:</strong> {{ $notice }}
      </p>
      
      <p style="
        text-align: center;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 10px 0;
      ">
        You can check your notices tab on 1st Mandate for more details.
        <br>
      <a href="{{ $link }}">More Details</a>
      </p>
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

        