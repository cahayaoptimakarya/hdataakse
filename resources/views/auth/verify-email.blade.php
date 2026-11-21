@extends('layouts.auth')

@section('title', 'Verifikasi email')

@section('form')
    <h2>Verifikasi email</h2>
    <div class="sub">Sebelum mulai, buka tautan verifikasi yang kami kirim ke email Anda. Belum menerima? Kirim ulang di bawah.</div>
    @if (session('status') == 'verification-link-sent')
        <div class="ok">Tautan verifikasi baru sudah dikirim ke email Anda.</div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn">Kirim ulang email verifikasi</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn sec">Keluar</button>
    </form>
@endsection
