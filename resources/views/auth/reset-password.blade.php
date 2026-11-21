@extends('layouts.auth')

@section('title', 'Buat kata sandi baru')

@section('form')
    <h2>Buat kata sandi baru</h2>
    <div class="sub">Isi kata sandi baru untuk akun Anda.</div>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="@error('email') bad @enderror">
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label for="password">Kata sandi baru</label>
        <input id="password" type="password" name="password" required autocomplete="new-password" class="@error('password') bad @enderror">
        @error('password')<div class="err">{{ $message }}</div>@enderror
        <label for="password_confirmation">Ulangi kata sandi baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        @error('password_confirmation')<div class="err">{{ $message }}</div>@enderror
        <button type="submit" class="btn">Simpan kata sandi</button>
    </form>
@endsection
