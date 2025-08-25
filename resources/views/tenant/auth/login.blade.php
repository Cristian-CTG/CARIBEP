@extends('tenant.layouts.auth')

@section('content')
@php
    $path_background = $vc_company->logo_login != '' ? 'storage/uploads/logos/'.$vc_company->logo_login : 'images/fondo-5.svg';
@endphp
<section class="auth auth__form-right login-container">
    <article class="auth__image" style="background-image: url({{ asset($path_background) }});background-size: 100%">
        @if ($vc_company->logo ?? false)
            <img class="auth__logo top-left" src="{{ asset('storage/uploads/logos/'.$vc_company->logo) }}" alt="Logo" />
        @endif
    </article>
    <article class="auth__form">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="text-left">
                <h1 class="auth__title">Bienvenido a<br>{{ $vc_company->trade_name }}</h1>
                <p class="mb-4 text-login">Ingrese su correo electrónico y contraseña a continuación para iniciar sesión en su cuenta.</p>
            </div>
            <div class="form-group mb-2">
                <label for="email">Correo electrónico</label>
                <input type="email" name="email" id="email" placeholder="correo@ejemplo.com" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" autofocus>
                @if ($errors->has('email'))
                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                @endif
            </div>
            <div class="form-group">
                <div class="d-flex justify-content-between">
                    <label for="password">Contraseña</label>
                    {{-- <a href="{{ url('password/reset') }}" tabindex="5">¿Has olvidado tu contraseña?</a> --}}
                </div>
                <div class="position-relative">
                    <input type="password" name="password" id="password" placeholder="********" class="form-control input-password hide-password {{ $errors->has('password') ? 'is-invalid' : '' }}">
                    <button type="button" class="btn btn-eye" id="btnEye" tabindex="4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-eye-off" aria-hidden="true">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path>
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path>
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path>
                            <path d="m2 2 20 20"></path>
                        </svg>
                    </button>
                </div>
                @if ($errors->has('password'))
                    <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                @endif
            </div>
            <button type="submit" class="btn btn-signin btn-block mt-4">Iniciar Sesión</button>
        </form>
    </article>
</section>
@endsection

@push('scripts')
    <script>
        const inputPassword = document.getElementById('password');
        const btnEye = document.getElementById('btnEye');

        const iconOpen = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-eye" aria-hidden="true">
                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696
                    10.75 10.75 0 0 1-19.876 0"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;

        const iconClose = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-eye-off" aria-hidden="true">
                <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696
                    10.747 10.747 0 0 1-1.444 2.49"></path>
                <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path>
                <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696
                    10.75 10.75 0 0 1 4.446-5.143"></path>
                <path d="m2 2 20 20"></path>
            </svg>
        `;

        btnEye.addEventListener('click', () => {
            const isHidden = inputPassword.type === 'password';
            inputPassword.type = isHidden ? 'text' : 'password';
            btnEye.innerHTML = isHidden ? iconOpen : iconClose;
        });
    </script>
@endpush
