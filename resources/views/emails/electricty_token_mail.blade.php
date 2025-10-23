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
      Utility Bill Payment Successful
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
        Your Electricity Bill Payment was successful. Please find below the details of your payment.
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
            margin: 5px 0;
            "><strong>Biller:</strong> {{ $biller }}</p>
        <p style="
            text-align: center;
            font-size: 16px;
            font-weight: 100;
            line-height: 28px;
            margin: 5px 0;
            "><strong>Customer Name:</strong> {{ $customer_name }}</p>
        <p style="
            text-align: center;
            font-size: 16px;
            font-weight: 100;
            line-height: 28px;
            margin: 5px 0;
            "><strong>Customer Identifier:</strong> {{ $customer_identifier }}</p>
        <p style="
            text-align: center;
            font-size: 16px;
            font-weight: 100;
            line-height: 28px;
            margin: 5px 0;
            "><strong>Amount Paid:</strong> {{ $amount }}</p>
        <p style="
            text-align: center;
            font-size: 16px;
            font-weight: 100;
            line-height: 28px;
            margin: 5px 0;
            "><strong>Token:</strong> {{ $token }}</p>
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

        