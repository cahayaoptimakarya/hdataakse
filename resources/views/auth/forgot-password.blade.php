@extends('layouts.auth')

@section('title', 'Lupa kata sandi')

@section('form')
    <h2>Lupa kata sandi</h2>
    <div class="sub">Masukkan email akun Anda. Kami kirimkan tautan untuk membuat kata sandi baru.</div>
    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="@error('email') bad @enderror">
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button type="submit" class="btn">Kirim tautan reset</button>
    </form>
    <div class="note"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></div>
@endsection
