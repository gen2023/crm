<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Ошибка') — CRM</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f5; }
        .box { background: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); max-width: 420px; text-align: center; }
        .code { font-size: 3rem; font-weight: 700; color: #dc2626; margin: 0; }
        .message { font-size: 1rem; color: #333; margin: .5rem 0 1.5rem; }
        a.btn { display: inline-block; padding: .5rem 1.2rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="box">
        <p class="code">@yield('code')</p>
        <p class="message">@yield('message')</p>
        <a class="btn" href="{{ url('/') }}">На главную</a>
    </div>
</body>
</html>
