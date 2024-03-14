<x-mail::message>
# Added as a Tenant.

Hello {{ $name }}, <br>

You have been added as a Tenant at a property Unit belonging to {{ $landlord }} on 1st Mandate App.

@if ($new_user)
    In order to have full access to your account, please click on the link below

    <p>
        <a href="{{ env('FRONTEND_URL') }}/activate-account?verification_token={{ $token }}&role=tenant">Activate Account</a>
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
