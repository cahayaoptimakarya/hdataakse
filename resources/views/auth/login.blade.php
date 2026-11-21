@extends('layouts.auth')

@section('title', 'Masuk')

@section('form')
    <h2>Masuk</h2>
    <div class="sub">Gunakan akun yang diberikan oleh admin.</div>
    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="@error('email') bad @enderror">
        @error('email')<div class="err">{{ $message }}</div>@enderror

        <label for="password">Kata sandi
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
        </label>
        <input id="password" type="password" name="password" required autocomplete="current-password" class="@error('password') bad @enderror">
        @error('password')<div class="err">{{ $message }}</div>@enderror

        <label class="chk"><input type="checkbox" name="remember"> Ingat saya di perangkat ini</label>
        <button type="submit" class="btn">Masuk</button>
    </form>
    <div class="note">Belum punya akun? Hubungi admin keuangan.</div>
@endsection
