<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'CRM')</title>
    <style>
        :root {
            --bg-page: #F7F0E3;
            --bg-input: #F4F6FA;
            --color-brand: #00a1df;
            --color-accent: #f7a001;
            --sidebar-width: 300px;
            --sidebar-width-collapsed: 76px;
        }
        * { box-sizing: border-box; }
        body { font-family: sans-serif; margin: 0; background: var(--bg-page); }

        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width); flex-shrink: 0; background: #fff; border-right: 1px solid #ece3d0;
            display: flex; flex-direction: column; transition: width .15s ease;
        }
        .sidebar.collapsed { width: var(--sidebar-width-collapsed); }

        .sidebar-top { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem; }
        .sidebar.collapsed .sidebar-top { justify-content: center; padding: 1.25rem .5rem; }
        .sidebar-logo { font-weight: 700; color: var(--color-brand); font-size: 1.15rem; white-space: nowrap; overflow: hidden; }
        .sidebar.collapsed .sidebar-logo { display: none; }
        .sidebar-toggle { background: none; border: none; cursor: pointer; padding: .35rem; color: #666; border-radius: 6px; }
        .sidebar-toggle:hover { background: var(--bg-input); }

        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: .2rem; padding: .5rem; }
        .sidebar-link {
            display: flex; align-items: center; gap: .8rem; padding: .65rem .9rem; border-radius: 8px;
            text-decoration: none; color: #333; font-size: .9rem; border: none; background: none;
            width: 100%; text-align: left; cursor: pointer; white-space: nowrap; overflow: hidden;
        }
        .sidebar-link:hover { background: var(--bg-input); }
        .sidebar-link.active { background: var(--bg-input); color: var(--color-brand); font-weight: 600; }
        .sidebar.collapsed .sidebar-link { justify-content: center; padding: .65rem; }
        .sidebar.collapsed .sidebar-link .label { display: none; }
        .sidebar-link .icon { width: 20px; height: 20px; flex-shrink: 0; }

        .sidebar-logout { padding: .5rem; border-top: 1px solid #ece3d0; }

        .content { flex: 1; padding: 2rem; min-width: 0; }
        .content-inner { max-width: 1000px; margin: 0 auto; }

        .card { background: #fff; border-radius: 12px; border: 1px solid #ece3d0; padding: 1.5rem; }

        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #e5e7eb; font-size: .9rem; }
        .status { color: #15803d; font-size: .875rem; margin-bottom: 1rem; }
        .errors { color: #b91c1c; font-size: .875rem; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: .4rem .8rem; background: var(--color-accent); color: #000; text-decoration: none; border-radius: 6px; font-size: .85rem; border: none; cursor: pointer; font-weight: 600; }
        .btn-danger { background: #dc2626; color: #fff; }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=text], input[type=email], textarea { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; border-radius: 8px; border: 1px solid #ddd; }
        .permissions { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-top: .5rem; }
        h1 { font-size: 1.25rem; }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-top">
                <span class="sidebar-logo">GenCrm</span>
                <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Свернуть меню">
                    <x-icon name="chevron-left" class="icon" />
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <x-icon name="dashboard" />
                    <span class="label">Dashboard</span>
                </a>
                @can('users.view')
                    <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <x-icon name="users" />
                        <span class="label">Users</span>
                    </a>
                @endcan
                @can('roles.view')
                    <a href="{{ route('roles.index') }}" class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <x-icon name="roles" />
                        <span class="label">Roles</span>
                    </a>
                @endcan
            </nav>

            <div class="sidebar-logout">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link">
                        <x-icon name="logout" />
                        <span class="label">Выйти ({{ auth()->user()->name }})</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="content">
            <div class="content-inner">
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
            </div>
        </main>
    </div>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar');
            var toggle = document.getElementById('sidebar-toggle');
            var KEY = 'crm-sidebar-collapsed';

            try {
                if (localStorage.getItem(KEY) === '1') {
                    sidebar.classList.add('collapsed');
                }
            } catch (e) {}

            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                try {
                    localStorage.setItem(KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
                } catch (e) {}
            });
        })();
    </script>
</body>
</html>
