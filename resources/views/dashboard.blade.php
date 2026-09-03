<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Dashboard — CRM</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        button { padding: .5rem 1rem; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Добро пожаловать, {{ $user->name }}</h1>
    <p>Email: {{ $user->email }}</p>
    <p>Последний вход: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Выйти</button>
    </form>
</body>
</html>
