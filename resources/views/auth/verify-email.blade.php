@extends('layouts.app')

<!--
 * @file verify-email.blade.php
 * @description Aviso mostrado a un usuario autenticado cuyo correo aún no ha sido verificado,
 *              con opción de reenviar el enlace. Mismo estilo glassmorphism claro que auth/login.
 * @date 2026-08-21
 * @author Antigravity
 -->

@section('title', 'Verifica tu correo - Attitour')

@section('content')
<div class="relative min-h-[calc(100vh-10rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden">

    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-brand-teal/5 blur-3xl animate-float pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10 space-y-5">

        <div class="text-center">
            <a href="{{ route('home') }}" class="inline-block">
                <img src="/images/logo/LOGO 3.png" alt="Attitour" class="h-22 w-auto object-contain mx-auto transition-transform duration-250 hover:scale-105" />
            </a>
        </div>

        <div class="p-1 rounded-3xl bg-gradient-to-br from-slate-100 via-slate-200 to-slate-300 border border-slate-200 shadow-xl">
            <div class="p-8 rounded-[22px] bg-white/95 text-center">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-teal/10 text-2xl mb-5">
                    📧
                </div>

                <h1 class="text-lg font-bold text-slate-800 mb-2">Verifica tu correo electrónico</h1>
                <p class="text-sm text-slate-500 leading-relaxed mb-6">
                    Te enviamos un enlace de verificación a <strong>{{ Auth::user()->email }}</strong>.
                    Da clic en el enlace para activar por completo tu cuenta.
                </p>

                <form action="{{ route('verification.send') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover hover:opacity-95 text-sm font-bold uppercase tracking-widest text-white shadow-md shadow-brand-teal/10 cursor-pointer transition-all hover:scale-[1.01]">
                        Reenviar enlace de verificación
                    </button>
                </form>

                <a href="{{ route('home') }}" class="inline-block mt-5 text-xs font-semibold text-slate-500 hover:text-brand-teal transition-colors">
                    ← Volver al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
