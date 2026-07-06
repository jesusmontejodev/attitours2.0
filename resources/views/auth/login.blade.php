@extends('layouts.app')

<!--
 * @file login.blade.php
 * @description Vista de inicio de sesión y registro de cuenta nueva para clientes finales.
 *              Diseño glassmorphism claro con tabs animados: Iniciar Sesión / Crear Cuenta.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', isset($showRegister) && $showRegister ? 'Crear Cuenta - Atti Tours' : 'Iniciar Sesión - Atti Tours')

@section('content')
<div class="relative min-h-[calc(100vh-10rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">

    {{-- Fondos decorativos --}}
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-brand-teal/5 blur-3xl animate-float pointer-events-none"></div>
    <div class="absolute top-1/4 left-1/5 w-[300px] h-[300px] rounded-full bg-brand-orange/5 blur-2xl animate-float pointer-events-none" style="animation-delay: -2s;"></div>
    <div class="absolute bottom-1/4 right-1/5 w-[250px] h-[250px] rounded-full bg-brand-teal/5 blur-2xl animate-float pointer-events-none" style="animation-delay: -1s;"></div>

    <div class="w-full max-w-md relative z-10 space-y-5">

        {{-- LOGO --}}
        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-block">
                <img src="/images/logo/LOGO 3.png" alt="Atti Tours" class="h-22 w-auto object-contain mx-auto transition-transform duration-250 hover:scale-105" />
            </a>
            <p class="text-[10px] text-slate-400 mt-2 tracking-widest uppercase font-bold">Experiencias Inolvidables</p>
        </div>

        {{-- TARJETA PRINCIPAL --}}
        <div class="p-1 rounded-3xl bg-gradient-to-br from-slate-100 via-slate-200 to-slate-300 border border-slate-200 shadow-xl backdrop-blur-xl">
            <div class="p-7 rounded-[22px] bg-white/95">

                {{-- TABS --}}
                <div class="flex rounded-xl bg-slate-100 p-1 mb-7 gap-1" role="tablist">
                    <button type="button" id="tab-login-btn" role="tab" onclick="switchAuthTab('login')"
                        class="auth-tab flex-1 h-9 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer bg-gradient-to-r from-brand-teal to-brand-teal-hover text-white shadow-sm">
                        Iniciar Sesión
                    </button>
                    <button type="button" id="tab-register-btn" role="tab" onclick="switchAuthTab('register')"
                        class="auth-tab flex-1 h-9 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200 cursor-pointer text-slate-500 hover:text-slate-800">
                        Crear Cuenta
                    </button>
                </div>

                {{-- Errores generales --}}
                @if($errors->any() && !$errors->has('name'))
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                    <svg class="h-4 w-4 shrink-0 mt-0.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif
                @if($errors->has('name') || $errors->has('email') && old('_form') === 'register')
                <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700">
                    <svg class="h-4 w-4 shrink-0 mt-0.5 text-rose-605" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <ul class="space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                {{-- ===== FORMULARIO LOGIN ===== --}}
                <div id="panel-login">
                    <form action="{{ route('login') }}" method="POST" class="flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="_form" value="login">

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Correo Electrónico</label>
                            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                placeholder="tu@correo.com"
                                class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contraseña</label>
                            <div class="relative">
                                <input type="password" id="login-pass" name="password" required autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                                <button type="button" onclick="togglePass('login-pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-650 transition-colors cursor-pointer">
                                    <svg class="h-4 w-4 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="h-3.5 w-3.5 rounded border-slate-300 bg-slate-50 text-brand-teal focus:ring-brand-teal focus:ring-offset-white">
                                <span class="text-xs text-slate-500 font-semibold">Recordarme</span>
                            </label>
                        </div>

                        <button type="submit"
                            class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover hover:opacity-95 text-sm font-bold uppercase tracking-widest text-white shadow-md shadow-brand-teal/10 cursor-pointer transition-all hover:scale-[1.01]">
                            Iniciar Sesión
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>

                        <p class="text-center text-xs text-slate-500 pt-1 font-semibold">
                            ¿No tienes cuenta?
                            <button type="button" onclick="switchAuthTab('register')" class="text-brand-teal hover:text-brand-teal-hover font-bold cursor-pointer underline-offset-2 hover:underline transition-colors">
                                Regístrate gratis
                            </button>
                        </p>
                    </form>
                </div>

                {{-- ===== FORMULARIO REGISTRO ===== --}}
                <div id="panel-register" class="hidden">
                    <form action="{{ route('register.submit') }}" method="POST" class="flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="_form" value="register">

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nombre Completo *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                                    placeholder="Juan García"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors {{ $errors->has('name') ? 'border-rose-500' : '' }}">
                                @error('name')<p class="text-[10px] text-rose-655 font-bold mt-0.5">{{ $message }}</p>@enderror
                            </div>

                            <div class="col-span-2 flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Correo Electrónico *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                    placeholder="tu@correo.com"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors {{ $errors->has('email') ? 'border-rose-500' : '' }}">
                                @error('email')<p class="text-[10px] text-rose-655 font-bold mt-0.5">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Teléfono</label>
                                <input type="text" name="telefono" value="{{ old('telefono') }}" autocomplete="tel"
                                    placeholder="+52 999 123 4567"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">País</label>
                                <select name="pais" class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-sm text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none transition-colors cursor-pointer">
                                    <option value="">Seleccionar...</option>
                                    <option value="México" {{ old('pais') === 'México' ? 'selected' : '' }}>🇲🇽 México</option>
                                    <option value="Estados Unidos" {{ old('pais') === 'Estados Unidos' ? 'selected' : '' }}>🇺🇸 Estados Unidos</option>
                                    <option value="Canadá" {{ old('pais') === 'Canadá' ? 'selected' : '' }}>🇨🇦 Canadá</option>
                                    <option value="España" {{ old('pais') === 'España' ? 'selected' : '' }}>🇪🇸 España</option>
                                    <option value="Colombia" {{ old('pais') === 'Colombia' ? 'selected' : '' }}>🇨🇴 Colombia</option>
                                    <option value="Argentina" {{ old('pais') === 'Argentina' ? 'selected' : '' }}>🇦🇷 Argentina</option>
                                    <option value="Brasil" {{ old('pais') === 'Brasil' ? 'selected' : '' }}>🇧🇷 Brasil</option>
                                    <option value="Chile" {{ old('pais') === 'Chile' ? 'selected' : '' }}>🇨🇱 Chile</option>
                                    <option value="Otro" {{ old('pais') === 'Otro' ? 'selected' : '' }}>🌍 Otro</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contraseña *</label>
                                <div class="relative">
                                    <input type="password" id="reg-pass" name="password" required autocomplete="new-password"
                                        placeholder="Mín. 6 caracteres"
                                        class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors {{ $errors->has('password') ? 'border-rose-500' : '' }}">
                                    <button type="button" onclick="togglePass('reg-pass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-650 cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                                @error('password')<p class="text-[10px] text-rose-650 font-bold mt-0.5">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Confirmar *</label>
                                <div class="relative">
                                    <input type="password" id="reg-confirm" name="password_confirmation" required autocomplete="new-password"
                                        placeholder="Repite la contraseña"
                                        class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                                    <button type="button" onclick="togglePass('reg-confirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-650 cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-sm font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/10 cursor-pointer transition-all hover:scale-[1.01]">
                            Crear Mi Cuenta Gratis
                            <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </button>

                        <p class="text-center text-xs text-slate-500 pt-1 font-semibold">
                            ¿Ya tienes cuenta?
                            <button type="button" onclick="switchAuthTab('login')" class="text-brand-teal hover:text-brand-teal-hover font-bold cursor-pointer underline-offset-2 hover:underline transition-colors">
                                Iniciar sesión
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        {{-- CREDENCIALES DE PRUEBA (solo para demo) --}}
        <div class="p-4 rounded-2xl border border-slate-200 bg-white/70 backdrop-blur-sm shadow-md">
            <h3 class="text-[10px] font-black uppercase tracking-wider text-brand-teal flex items-center gap-1.5 mb-3">
                💡 Accesos de Prueba (Demo)
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[9px] text-slate-550 leading-relaxed font-semibold">
                <div class="p-2 rounded-lg border border-slate-200 bg-slate-50/50">
                    <p class="font-bold text-slate-700 mb-0.5">👤 Administrador</p>
                    <p class="text-slate-600">admin@attitours.com</p>
                    <p class="text-brand-teal font-bold">admin123</p>
                </div>
                <div class="p-2 rounded-lg border border-slate-200 bg-slate-50/50">
                    <p class="font-bold text-slate-700 mb-0.5">🏢 Proveedor</p>
                    <p class="text-slate-600">proveedor@attitours.com</p>
                    <p class="text-brand-orange font-bold">proveedor123</p>
                </div>
                <div class="p-2 rounded-lg border border-slate-200 bg-slate-50/50">
                    <p class="font-bold text-slate-700 mb-0.5">✈️ Cliente de Prueba</p>
                    <p class="text-slate-600">cliente@attitours.com</p>
                    <p class="text-brand-teal font-bold">cliente123</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ========================
// Tab switcher
// ========================
function switchAuthTab(tab) {
    const loginBtn    = document.getElementById('tab-login-btn');
    const registerBtn = document.getElementById('tab-register-btn');
    const panelLogin  = document.getElementById('panel-login');
    const panelReg    = document.getElementById('panel-register');

    const activeLoginClasses    = ['bg-gradient-to-r','from-brand-teal','to-brand-teal-hover','text-white','shadow-sm'];
    const activeRegisterClasses = ['bg-gradient-to-r','from-brand-orange','to-brand-orange-hover','text-white','shadow-sm'];
    const inactiveClasses       = ['text-slate-500'];

    if (tab === 'login') {
        // Activar Login
        activeLoginClasses.forEach(c => loginBtn.classList.add(c));
        inactiveClasses.forEach(c => loginBtn.classList.remove(c));
        
        // Desactivar Registro
        activeRegisterClasses.forEach(c => registerBtn.classList.remove(c));
        registerBtn.classList.remove('bg-gradient-to-r', 'from-brand-teal', 'to-brand-teal-hover'); // Limpieza
        inactiveClasses.forEach(c => registerBtn.classList.add(c));
        
        panelLogin.classList.remove('hidden');
        panelReg.classList.add('hidden');
    } else {
        // Activar Registro
        activeRegisterClasses.forEach(c => registerBtn.classList.add(c));
        inactiveClasses.forEach(c => registerBtn.classList.remove(c));
        
        // Desactivar Login
        activeLoginClasses.forEach(c => loginBtn.classList.remove(c));
        inactiveClasses.forEach(c => loginBtn.classList.add(c));
        
        panelLogin.classList.add('hidden');
        panelReg.classList.remove('hidden');
    }
}

// Mostrar/ocultar contraseña
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.querySelector('svg').style.opacity = isPassword ? '0.4' : '1';
}

// Inicializar según contexto (si viene con errores de registro o desde /register)
document.addEventListener('DOMContentLoaded', () => {
    const hasRegisterErrors = {{ ($errors->has('name') || $errors->has('password') || $errors->has('password_confirmation')) ? 'true' : 'false' }};
    const isRegisterPage    = {{ (isset($showRegister) && $showRegister) ? 'true' : 'false' }};

    if (hasRegisterErrors || isRegisterPage) {
        switchAuthTab('register');
    }
});
</script>
@endsection
