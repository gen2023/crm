@extends('auth.layout')

@section('title', 'Восстановление пароля — CRM')

@section('content')
    <h1>Восстановление пароля</h1>
    <p class="hint">Укажите email — если он зарегистрирован, мы отправим ссылку для сброса пароля.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

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

        <button class="btn-primary" type="submit">Отправить ссылку</button>
        <a class="aux-link" href="{{ route('login') }}">Назад ко входу</a>
    </form>
@endsection
