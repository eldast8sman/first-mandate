<x-mail::message>
# Added as Property Manager.

Hello {{ $name }}, <br>

You hae been added as the Property Manager for the Property of {{ $landlord }} on 1st Mandate App.

@if ($new_user)
    In order to have full access to your account, please click on the link below

    <p>
        <a href="{{ env('FRONTEND_URL') }}/activate-account?verification_token={{ $token }}&role=property manager">Activate Account</a>
    </p>
@else
    Login to your Account to have more details

    <p>
        <a href="{{ env('FRONTEND_URL') }}/login">Login</a>
    </p>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
