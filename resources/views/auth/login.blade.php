<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <script src="{{ asset('js/glass-controls.js') }}"></script>
    <title>Staff Login - San Isidro Clinic</title>
    <script>
        (() => {
            sessionStorage.removeItem('clinic-reception-welcome-shown');
            sessionStorage.removeItem('clinic-nurse-welcome-shown');
            sessionStorage.removeItem('clinic-physician-welcome-shown');
            sessionStorage.removeItem('clinic-administrator-welcome-shown');
            const savedTheme = localStorage.getItem('clinic-theme');
            const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = savedTheme || preferredTheme;
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/password-controls.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glass-controls.css') }}">
</head>
<body class="login-page">
<div class="login-appearance-tools">
<x-glass-controls />
<button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch appearance">
    <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z"></path>
    </svg>
    <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="3.75"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path>
    </svg>
</button>
</div>

<main class="login-shell">
    <div class="login-layout">
        <section class="login-intro" aria-labelledby="clinic-name">
            <div class="login-brand-lockup">
                <span class="login-brand-mark" aria-hidden="true">+</span>
                <span>San Isidro Clinic System</span>
            </div>

            <p class="login-eyebrow">Welcome to</p>
            <h1 id="clinic-name">San Isidro Family<br>Medical Clinic</h1>
            <div class="login-accent" aria-hidden="true"></div>

            <p class="login-system-name">Appointment and Patient Record Management System</p>
            <p class="typewriter-line" aria-live="polite">
                <span id="typewriter-text">Care that feels close to home.</span><span class="typewriter-caret" aria-hidden="true"></span>
            </p>

            <div class="login-meta" aria-label="System information">
                <span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
                    Secure staff access
                </span>
                <span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"></path><path d="M12 7v5l3 2"></path></svg>
                    Clinic-ready workflow
                </span>
            </div>
        </section>

        <section class="login-card-wrap" aria-label="Staff sign in">
            <form class="login-card" method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="login-card-heading">
                    <span class="login-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </span>
                    <div>
                        <p class="login-card-kicker">Staff portal</p>
                        <h2>Welcome back</h2>
                    </div>
                </div>
                <p class="login-card-copy">Sign in with your clinic account to continue.</p>

                <x-validation-feedback />

                <div class="field">
                    <label class="required" for="username">Clinic account</label>
                    <select id="username" name="username" required autofocus autocomplete="username">
                        <option value="">Select your clinic account</option>
                        @foreach($users as $user)
                            <option value="{{ $user->username }}" @selected(old('username')===$user->username)>
                                {{ $user->username }} — {{ $user->name }} ({{ $user->role }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="required" for="password">Password</label>
                    <x-password-input id="password" name="password" autocomplete="current-password" required placeholder="Enter your password" />
                </div>
                <button class="btn btn-primary login-submit" type="submit">
                    <span>Sign in securely</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </button>
                <p class="login-help">Need access? Contact the clinic administrator.</p>
                @php $clockNow = now('Asia/Manila'); @endphp
                <div class="login-clock" aria-label="Current Philippine date and time">
                    <p class="login-clock-label">Philippine time</p>
                    <time id="login-clock" datetime="{{ $clockNow->toIso8601String() }}" data-server-time="{{ $clockNow->getTimestampMs() }}">
                        <span data-clock-date>{{ $clockNow->format('l, F j, Y') }}</span>
                        <span class="login-clock-divider" aria-hidden="true">·</span>
                        <strong data-clock-time>{{ $clockNow->format('h:i:s A') }}</strong>
                    </time>
                </div>
            </form>
        </section>
    </div>

    <footer class="login-footer">School project system <span aria-hidden="true">•</span> All names and records are fictional.</footer>
</main>

<script>
    (() => {
        const root = document.documentElement;
        const themeButton = document.getElementById('theme-toggle');
        const typewriter = document.getElementById('typewriter-text');
        const phrases = [
            'Care that feels close to home.',
            'Better schedules. Better patient care.',
            'Trusted records for every visit.'
        ];

        function updateThemeButton() {
            const isDark = root.dataset.theme === 'dark';
            themeButton.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            themeButton.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
        }

        themeButton.addEventListener('click', () => {
            root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('clinic-theme', root.dataset.theme);
            updateThemeButton();
        });
        updateThemeButton();

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            typewriter.textContent = phrases[0];
            return;
        }

        let phraseIndex = 0;
        let characterIndex = phrases[0].length;
        let deleting = true;

        function animateTypewriter() {
            const phrase = phrases[phraseIndex];
            characterIndex += deleting ? -1 : 1;
            typewriter.textContent = phrase.slice(0, characterIndex);

            let delay = deleting ? 34 : 58;
            if (!deleting && characterIndex === phrase.length) {
                deleting = true;
                delay = 2200;
            } else if (deleting && characterIndex === 0) {
                deleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                delay = 420;
            }
            window.setTimeout(animateTypewriter, delay);
        }

        window.setTimeout(animateTypewriter, 1800);
    })();
</script>
<script src="{{ asset('js/password-controls.js') }}" defer></script>
<script src="{{ asset('js/login-clock.js') }}" defer></script>
<script src="{{ asset('js/form-validation.js') }}" defer></script>
</body>
</html>
