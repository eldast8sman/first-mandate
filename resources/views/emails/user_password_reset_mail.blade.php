@extends('emails.layouts.second_app')

@section('content')
<div style="padding: 10px; margin: 0 auto; width: 90%">
  <h1
    style="font-size: 25px; letter-spacing: 0.4px; margin: 10px 0 0 0"
  >
    Reset your password
  </h1>
  <div style="margin: 22px 0">
    <p
      style="
        text-align: left;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
      "
    >
      Hi {{ $name }},
    </p>
    <p
      style="
        text-align: left;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
      "
    >
      Forgotten your password? Don't worry - it happens to the best of
      us. Click the button below to reset your password and regain
      access to your account:
    </p>
  </div>
  <a href="{{ $link }}">
    <button
    style="
      background-color: #000;
      color: #ffffff;
      padding: 12px 0;
      border: transparent;
      border-radius: 5px;
      width: 250px;
      cursor: pointer;
      text-align: center;
      font-family: inherit;
      font-size: 16px;
    "
  >
    Reset your password
  </button>
  </a>
  <div style="margin: 22px 0">
    <p
      style="
        text-align: left;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
      "
    >
      Best,
    </p>
    <p
      style="
        text-align: left;
        font-size: 16px;
        font-weight: 100;
        line-height: 28px;
        margin: 0;
      "
    >
      1st Mandate
    </p>
  </div>
</div>
@endsection