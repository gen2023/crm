<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'CRM')</title>
    <style>
        :root {
            --color-bg-page: #F7F0E3;
            --color-bg-input: #F4F6FA;
            --color-primary: #00a1df;
            --color-accent: #f7a001;
            --color-text-on-accent: #ffffff;
            --color-danger: #b91c1c;
            --color-success: #15803d;
            --color-card-border: #ece3d0;
        }
        * { box-sizing: border-box; }
        body { font-family: sans-serif; margin: 0; min-height: 100vh; background: var(--color-bg-page); }
        .auth-page { display: flex; flex-direction: column; min-height: 100vh; }
        .auth-logo { padding: 1.5rem 2rem; font-size: 1.35rem; font-weight: 700; color: var(--color-primary); }
        .auth-body { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-card { background: #fff; border-radius: 12px; border: 1px solid var(--color-card-border); padding: 2rem; width: 100%; max-width: 360px; }
        .auth-card h1 { font-size: 1.15rem; margin: 0 0 .25rem; }
        .auth-card p.hint { font-size: .85rem; color: #666; margin: 0 0 1rem; }
        label { display: block; margin-top: 1rem; font-size: .85rem; color: #333; }
        input[type=email], input[type=password], input[type=text] {
            width: 100%; padding: .65rem .75rem; margin-top: .3rem;
            border-radius: 8px; border: none; background: var(--color-bg-input); font-size: .9rem;
        }
        input:focus { outline: 2px solid var(--color-primary); outline-offset: 1px; }
        .btn-primary {
            margin-top: 1.5rem; width: 100%; padding: .7rem; border: none; border-radius: 8px;
            background: var(--color-accent); color: var(--color-text-on-accent); font-weight: 600; cursor: pointer; font-size: .9rem;
        }
        .status { color: var(--color-success); font-size: .85rem; margin-top: 1rem; }
        .errors { color: var(--color-danger); font-size: .85rem; margin-top: 1rem; }
        .remember { margin-top: 1rem; font-size: .85rem; display: flex; align-items: center; gap: .4rem; }
        .aux-link { display: block; margin-top: 1rem; font-size: .85rem; text-align: center; color: var(--color-primary); text-decoration: none; }
        .aux-link:hover { text-decoration: underline; }

        .password-field { position: relative; }
        .password-field input { padding-right: 2.5rem; }
        .password-toggle {
            position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #666; display: flex; padding: .25rem;
        }
        .password-toggle .icon { width: 18px; height: 18px; }
        .password-toggle:hover { color: #333; }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-logo">GenCrm</div>
        <div class="auth-body">
            <div class="auth-card">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.password-toggle');
            if (!btn) return;
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
