@extends('auth.layout')

@section('title', 'Новый пароль — CRM')

@section('content')
    <h1>Новый пароль</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus>

        <label for="password">Новый пароль</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirmation">Подтверждение пароля</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <button class="btn-primary" type="submit">Сохранить пароль</button>
    </form>
@endsection
