@extends('layouts.admin')

@section('title', 'Profil')
@section('page_title', 'Profil & Kata Sandi')

@section('content')
<div class="row g-5" style="max-width:1100px">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><div class="card-title"><h2>Informasi profil</h2></div></div>
            <div class="card-body">
                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success py-3">Profil tersimpan.</div>
                @endif
                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    <div class="mb-5">
                        <label class="form-label" for="name">Nama</label>
                        <input id="name" name="name" type="text" class="form-control form-control-solid @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-6">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control form-control-solid @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><div class="card-title"><h2>Ganti kata sandi</h2></div></div>
            <div class="card-body">
                @if (session('status') === 'password-updated')
                    <div class="alert alert-success py-3">Kata sandi diperbarui.</div>
                @endif
                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')
                    @foreach([
                        ['current_password', 'Kata sandi sekarang', 'current-password'],
                        ['password', 'Kata sandi baru', 'new-password'],
                        ['password_confirmation', 'Ulangi kata sandi baru', 'new-password'],
                    ] as [$field, $label, $auto])
                        <div class="mb-5">
                            <label class="form-label" for="pw_{{ $field }}">{{ $label }}</label>
                            <input id="pw_{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $auto }}"
                                class="form-control form-control-solid {{ $errors->updatePassword->has($field) ? 'is-invalid' : '' }}">
                            @if($errors->updatePassword->has($field))
                                <div class="invalid-feedback">{{ $errors->updatePassword->first($field) }}</div>
                            @endif
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary">Perbarui kata sandi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
