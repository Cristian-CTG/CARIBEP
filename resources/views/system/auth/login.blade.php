@extends('system.layouts.auth')

@section('content')

    <section class="body-sign sign-system">
        <div class="center-sign">
            <p class="card-title text-center mb-3">Panel administrador {{ config('app.name') }}</p>
            <div class="d-flex justify-content-center">
                <div class="card-body card-sign">
                    <div class="text-left w-100">
                        <p class="h6 sign-text"><strong>Iniciar sesión</strong></p>
                        <p class="mb-2">Acceso solo para administradores de cuentas</p>
                    </div>
                    <form method="POST" action="{{ route('login') }}" class="w-100">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Correo electrónico</label>
                            <div class="input-group">
                                <input id="email" type="email" name="email" class="form-control form-control-lg" placeholder="correo@ejemplo.com" value="{{ old('email') }}">                                
                            </div>
                            @if ($errors->has('email'))
                                <label class="error">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </label>
                            @endif
                        </div>
                        <div class="form-group mb-3 {{ $errors->has('password') ? ' error' : '' }}">
                            <label>Contraseña</label>
                            <div class="position-relative">
                                <input id="password" name="password" type="password" placeholder="********" class="form-control form-control-lg input-password">                                
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
                                <label class="error">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </label>
                            @endif
                        </div>
                        <div class="row">
                            {{-- <div class="col-sm-8">
                                <div class="checkbox-custom checkbox-default">
                                    <input name="remember" id="RememberMe" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                                    <label for="RememberMe">Recordarme</label>
                                </div>
                            </div> --}}
                            <div class="col-sm-12">
                                <button type="submit" class="btn btn-sign-system mt-2 d-flex align-items-center justify-content-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-login-2 mr-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 8v-2a2 2 0 0 1 2 -2h7a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-2" /><path d="M3 12h13l-3 -3" /><path d="M13 15l3 -3" /></svg>    
                                    Iniciar sesión
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted mt-3 mb-3">&copy; Copyright {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados</p>
        </div>
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

        if (btnEye && inputPassword) {
            btnEye.addEventListener('click', () => {
                const isHidden = inputPassword.type === 'password';
                inputPassword.type = isHidden ? 'text' : 'password';
                btnEye.innerHTML = isHidden ? iconOpen : iconClose;
            });
        }
    </script>
@endpush
