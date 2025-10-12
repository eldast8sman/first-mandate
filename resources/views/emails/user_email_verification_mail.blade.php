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
    Welcome to 1st Mandate! We're thrilled to have you on board. To
    continue please click the button below to verify your email
    address:
  </p>
</div>
<div id="user-content" style="margin: 17px 0; text-align: center">
  <a href="{{ $link }}"><button
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
    Verify Email Address
</button></a>
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