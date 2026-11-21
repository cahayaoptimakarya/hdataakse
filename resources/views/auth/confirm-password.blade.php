@extends('layouts.auth')

@section('title', 'Konfirmasi kata sandi')

@section('form')
    <h2>Konfirmasi kata sandi</h2>
    <div class="sub">Ini area yang aman. Masukkan kata sandi Anda untuk melanjutkan.</div>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <label for="password">Kata sandi</label>
        <input id="password" type="password" name="password" required autocomplete="current-password" class="@error('password') bad @enderror">
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <button type="submit" class="btn">Konfirmasi</button>
    </form>
@endsection
