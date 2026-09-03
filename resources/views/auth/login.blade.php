<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вход — CRM</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f5; }
        form { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); width: 320px; }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=email], input[type=password] { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; }
        .errors { color: #b91c1c; font-size: .875rem; margin-top: 1rem; }
        button { margin-top: 1.5rem; width: 100%; padding: .6rem; cursor: pointer; }
        .remember { margin-top: 1rem; font-size: .875rem; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <h1 style="font-size:1.25rem;margin:0;">Вход в CRM</h1>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" required>

        <label class="remember">
            <input type="checkbox" name="remember_me" value="1" {{ old('remember_me') ? 'checked' : '' }}>
            Запомнить меня
        </label>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <button type="submit">Войти</button>
    </form>
</body>
</html>
