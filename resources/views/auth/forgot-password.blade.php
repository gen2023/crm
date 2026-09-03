<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Восстановление пароля — CRM</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f5; }
        form { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); width: 320px; }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=email] { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; }
        .errors { color: #b91c1c; font-size: .875rem; margin-top: 1rem; }
        .status { color: #15803d; font-size: .875rem; margin-top: 1rem; }
        button { margin-top: 1.5rem; width: 100%; padding: .6rem; cursor: pointer; }
        .back-link { display: block; margin-top: 1rem; font-size: .875rem; text-align: center; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <h1 style="font-size:1.25rem;margin:0;">Восстановление пароля</h1>
        <p style="font-size:.875rem;color:#555;">Укажите email — если он зарегистрирован, мы отправим ссылку для сброса пароля.</p>

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

        <button type="submit">Отправить ссылку</button>
        <a class="back-link" href="{{ route('login') }}">Назад ко входу</a>
    </form>
</body>
</html>
