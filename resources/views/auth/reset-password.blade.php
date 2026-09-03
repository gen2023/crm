<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Новый пароль — CRM</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f5; }
        form { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); width: 320px; }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=email], input[type=password] { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; }
        .errors { color: #b91c1c; font-size: .875rem; margin-top: 1rem; }
        button { margin-top: 1.5rem; width: 100%; padding: .6rem; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h1 style="font-size:1.25rem;margin:0;">Новый пароль</h1>

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

        <button type="submit">Сохранить пароль</button>
    </form>
</body>
</html>
