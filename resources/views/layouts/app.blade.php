<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'CRM')</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f4f4f5; }
        nav { background: #1f2937; color: #fff; padding: .75rem 1.5rem; display: flex; gap: 1.5rem; align-items: center; }
        nav a { color: #fff; text-decoration: none; font-size: .9rem; }
        nav a:hover { text-decoration: underline; }
        nav form { margin-left: auto; }
        nav button { background: none; border: none; color: #fff; cursor: pointer; font-size: .9rem; }
        main { padding: 2rem; max-width: 960px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #e5e7eb; font-size: .9rem; }
        .status { color: #15803d; font-size: .875rem; margin-bottom: 1rem; }
        .errors { color: #b91c1c; font-size: .875rem; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: .4rem .8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: .85rem; border: none; cursor: pointer; }
        .btn-danger { background: #dc2626; }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=text], input[type=email], textarea { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; }
        .permissions { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-top: .5rem; }
        h1 { font-size: 1.25rem; }
    </style>
</head>
<body>
    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a>
        @can('roles.view')
            <a href="{{ route('roles.index') }}">Roles</a>
        @endcan
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Выйти ({{ auth()->user()->name }})</button>
        </form>
    </nav>
    <main>
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

        @yield('content')
    </main>
</body>
</html>
