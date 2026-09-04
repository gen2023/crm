<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'CRM')</title>
    <style>
        :root {
            /* Design tokens — change the look of the whole app from here. */
            --color-bg-page: #F7F0E3;
            --color-bg-input: #F4F6FA;
            --color-primary: #00a1df;
            --color-accent: #f7a001;
            --color-accent-hover: #f8b02c;
            --color-text-on-accent: #ffffff;
            --color-danger: #dc2626;
            --color-success: #15803d;
            --color-sidebar-bg: #242424;
            --color-sidebar-text: #ffffff;
            --color-sidebar-border: rgba(255, 255, 255, .14);
            --color-sidebar-hover: #333333;
            --color-card-border: #ece3d0;

            --sidebar-width: 300px;
            --sidebar-width-collapsed: 76px;
        }
        * { box-sizing: border-box; }
        body { font-family: sans-serif; margin: 0; background: var(--color-bg-page); }
        svg.icon { width: 20px; height: 20px; flex-shrink: 0; }

        .app-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width); flex-shrink: 0; background: var(--color-sidebar-bg);
            display: flex; flex-direction: column; transition: width .15s ease;
        }
        .sidebar.collapsed { width: var(--sidebar-width-collapsed); }

        .sidebar-top {
            display: flex; align-items: center; justify-content: space-between; padding: 1.25rem;
            border-bottom: 2px solid var(--color-sidebar-border);
        }
        .sidebar.collapsed .sidebar-top { justify-content: center; padding: 1.25rem .5rem; }
        .sidebar-logo { font-weight: 700; color: var(--color-primary); font-size: 1.15rem; white-space: nowrap; overflow: hidden; }
        .sidebar.collapsed .sidebar-logo { display: none; }
        .sidebar-toggle { background: none; border: none; cursor: pointer; padding: .35rem; color: var(--color-sidebar-text); border-radius: 6px; display: inline-flex; }
        .sidebar-toggle:hover { color: var(--color-accent-hover); }

        .sidebar-nav { flex: 1; display: flex; flex-direction: column; padding: .5rem; }
        .sidebar-link {
            display: flex; align-items: center; gap: .8rem; padding: .75rem .9rem;
            text-decoration: none; color: var(--color-sidebar-text); font-size: .9rem; border: none; background: none;
            border-bottom: 1px solid var(--color-sidebar-border);
            width: 100%; text-align: left; cursor: pointer; white-space: nowrap; overflow: hidden;
        }
        .sidebar-link:hover { color: var(--color-accent-hover); background: var(--color-sidebar-hover); }
        .sidebar-link.active { color: var(--color-accent-hover); font-weight: 600; background: var(--color-sidebar-hover); }
        .sidebar.collapsed .sidebar-link { justify-content: center; padding: .75rem; }
        .sidebar.collapsed .sidebar-link .label { display: none; }

        .content { flex: 1; padding: 2rem; min-width: 0; }
        .content-inner { max-width: 1400px; margin: 0 auto; }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; }
        .card-grid .card { margin-top: 0; }

        .topbar { display: flex; justify-content: flex-end; margin-bottom: 1.5rem; }
        .user-menu { position: relative; }
        .user-menu-trigger {
            display: inline-flex; align-items: center; gap: .4rem; background: #fff; border: 1px solid var(--color-card-border);
            border-radius: 8px; padding: .5rem .9rem; font-size: .9rem; cursor: pointer; color: #333;
        }
        .user-menu-dropdown {
            position: absolute; right: 0; top: calc(100% + .4rem); background: #fff; border: 1px solid var(--color-card-border);
            border-radius: 8px; min-width: 160px; box-shadow: 0 4px 12px rgba(0,0,0,.08); overflow: hidden; z-index: 10;
        }
        .user-menu-dropdown a, .user-menu-dropdown button {
            display: block; width: 100%; text-align: left; padding: .6rem .9rem; font-size: .9rem; color: #333;
            text-decoration: none; background: none; border: none; cursor: pointer;
        }
        .user-menu-dropdown a:hover, .user-menu-dropdown button:hover { background: var(--color-bg-input); }

        .card { background: #fff; border-radius: 12px; border: 1px solid var(--color-card-border); padding: 1.5rem; }
        .card + .card { margin-top: 1.5rem; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #f0ece0; font-size: .9rem; }
        tbody tr:last-child td { border-bottom: none; }
        .status { color: var(--color-success); font-size: .875rem; margin-bottom: 1rem; }
        .errors { color: var(--color-danger); font-size: .875rem; margin-bottom: 1rem; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; background: var(--color-accent); color: var(--color-text-on-accent); text-decoration: none; border-radius: 8px; font-size: .85rem; border: none; cursor: pointer; font-weight: 600; }
        .btn .icon { width: 16px; height: 16px; }
        .btn-danger { background: var(--color-danger); color: var(--color-text-on-accent); }
        label { display: block; margin-top: 1rem; font-size: .875rem; }
        input[type=text], input[type=email], input[type=password], textarea, select { width: 100%; padding: .55rem .7rem; margin-top: .25rem; box-sizing: border-box; border-radius: 8px; border: none; background: var(--color-bg-input); font-size: .9rem; }
        .permissions { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; margin-top: .5rem; }
        h1 { font-size: 1.25rem; margin-top: 0; }

        .row-actions { display: flex; align-items: center; gap: .35rem; }
        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px;
            border-radius: 6px; border: none; background: none; color: #666; cursor: pointer; text-decoration: none; padding: 0;
        }
        .icon-btn .icon { width: 17px; height: 17px; }
        .icon-btn:hover { background: var(--color-bg-input); color: #333; }
        .icon-btn.danger:hover { background: #fde8e8; color: var(--color-danger); }
        .icon-btn.success:hover { background: #e6f6ec; color: var(--color-success); }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-top">
                <span class="sidebar-logo">GenCrm</span>
                <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Свернуть меню">
                    <x-icon name="chevron-left" />
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <x-icon name="dashboard" />
                    <span class="label">Dashboard</span>
                </a>
                @can('customers.view')
                    <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <x-icon name="customers" />
                        <span class="label">Customers</span>
                    </a>
                @endcan
                @can('products.view')
                    <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <x-icon name="products" />
                        <span class="label">Products</span>
                    </a>
                @endcan
                @can('orders.view')
                    <a href="{{ route('orders.index') }}" class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <x-icon name="orders" />
                        <span class="label">Orders</span>
                    </a>
                @endcan
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
                @can('settings.edit')
                    <a href="{{ route('settings.edit') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <x-icon name="settings" />
                        <span class="label">Settings</span>
                    </a>
                @endcan
            </nav>
        </aside>

        <main class="content">
            <div class="content-inner">
                <div class="topbar">
                    <div class="user-menu" id="user-menu">
                        <button type="button" class="user-menu-trigger" id="user-menu-trigger">
                            {{ auth()->user()->name }}
                            <x-icon name="chevron-down" style="width:14px;height:14px;" />
                        </button>
                        <div class="user-menu-dropdown" id="user-menu-dropdown" hidden>
                            <a href="{{ route('profile.show') }}">Просмотр</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Выход</button>
                            </form>
                        </div>
                    </div>
                </div>

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

        (function () {
            var menu = document.getElementById('user-menu');
            var trigger = document.getElementById('user-menu-trigger');
            var dropdown = document.getElementById('user-menu-dropdown');

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.hidden = !dropdown.hidden;
            });

            document.addEventListener('click', function (e) {
                if (!dropdown.hidden && !menu.contains(e.target)) {
                    dropdown.hidden = true;
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    dropdown.hidden = true;
                }
            });
        })();
    </script>
</body>
</html>
