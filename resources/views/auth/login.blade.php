@extends('auth.layout')

@section('title', 'Вход — CRM')

@section('content')
    <h1>Вход в CRM</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" required>

        <label class="remember">
            <input type="checkbox" name="remember_me" value="1" {{ old('remember_me') ? 'checked' : '' }}>
            Запомнить меня
        </label>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <button class="btn-primary" type="submit">Войти</button>
        <a class="aux-link" href="{{ route('password.request') }}">Забыли пароль?</a>
    </form>
@endsection
