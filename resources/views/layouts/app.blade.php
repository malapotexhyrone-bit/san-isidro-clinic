@php
    $isReceptionist = auth()->user()->role === 'Receptionist';
    $isNurse = auth()->user()->role === 'Nurse';
    $isPhysician = auth()->user()->role === 'Physician';
    $isAdministrator = auth()->user()->role === 'Administrator';
    $hasEnhancedWorkspace = $isReceptionist || $isNurse || $isPhysician || $isAdministrator;
    $workspaceLabel = $isNurse ? 'Nursing workspace' : ($isPhysician ? 'Physician workspace' : ($isAdministrator ? 'Admin workspace' : 'Reception workspace'));
    $stationLabel = $isNurse ? 'Nursing station' : ($isPhysician ? 'Consultation room' : ($isAdministrator ? 'Administration center' : 'Reception desk'));
    $stationStatus = $isNurse ? 'Vital signs workflow ready' : ($isPhysician ? 'Assigned patients ready' : ($isAdministrator ? 'System controls ready' : 'Clinic services ready'));
    $welcomeLabel = $isNurse ? 'San Isidro Nursing' : ($isPhysician ? 'San Isidro Physician Portal' : ($isAdministrator ? 'San Isidro Administration' : 'San Isidro Reception'));
    $welcomeMessage = $isNurse ? 'Your nursing workspace is ready.' : ($isPhysician ? 'Your consultation workspace is ready.' : ($isAdministrator ? 'Your administration workspace is ready.' : 'Your reception workspace is ready.'));
    $welcomeStorageKey = 'clinic-'.strtolower(auth()->user()->role).'-welcome-shown';
    $interactiveProfileSlug = match (auth()->user()->username) {
        'physician' => 'doctor1',
        'physician2' => 'doctor2',
        default => null,
    };
    $hasInteractiveProfile = filled($interactiveProfileSlug) && blank(auth()->user()->profile_photo_path);
@endphp
<!doctype html>
<html lang="en" @if($hasEnhancedWorkspace) data-theme="light" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <script src="{{ asset('js/glass-controls.js') }}"></script>
    <script src="{{ asset('js/reading-size-controls.js') }}"></script>
    <title>@yield('title', 'Clinic') - San Isidro</title>
    @if($hasEnhancedWorkspace)
        <script>
            (() => {
                const savedTheme = localStorage.getItem('clinic-theme');
                const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = savedTheme || preferredTheme;
                document.documentElement.dataset.sidebar = localStorage.getItem('clinic-sidebar') === 'expanded' ? 'expanded' : 'collapsed';
            })();
        </script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/clinic.css') }}">
    @if($hasEnhancedWorkspace)
        <link rel="stylesheet" href="{{ asset('css/reception.css') }}">
        <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
        @if($isNurse)<link rel="stylesheet" href="{{ asset('css/nurse.css') }}">@endif
        @if($isPhysician)<link rel="stylesheet" href="{{ asset('css/physician.css') }}">@endif
        @if($isAdministrator)<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endif
        <link rel="stylesheet" href="{{ asset('css/navigation.css') }}">
    @endif
    <style>
        .confirm-modal[hidden]{display:none}.confirm-modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(25,27,31,.58)}.confirm-dialog{width:min(440px,100%);background:#fff;border:1px solid #e7e8eb;border-radius:9px;box-shadow:0 24px 70px rgba(0,0,0,.24);overflow:hidden}.confirm-dialog-head{padding:18px 20px;border-bottom:1px solid #e7e8eb;background:#fff0f3}.confirm-dialog-head h2{margin:0;color:#25262a;font-size:18px}.confirm-dialog-body{padding:20px}.confirm-dialog-body p{margin:0;line-height:1.55;color:#4e5158}.confirm-dialog-actions{display:flex;justify-content:flex-end;gap:9px;padding:0 20px 20px}.confirm-dialog-icon{display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;margin-right:9px;border-radius:50%;background:#ff3157;color:#fff;font-weight:800}
    </style>
    <link rel="stylesheet" href="{{ asset('css/password-controls.css') }}">
    <link rel="stylesheet" href="{{ asset('css/date-picker.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link rel="stylesheet" href="{{ asset('css/system-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/success-feedback.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glass-controls.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reading-size-controls.css') }}">
</head>
<body class="{{ $isReceptionist ? 'role-receptionist' : ($isNurse ? 'role-receptionist role-nurse' : ($isPhysician ? 'role-receptionist role-physician' : ($isAdministrator ? 'role-receptionist role-admin' : ''))) }}">
<div class="app">
    <aside class="sidebar" id="clinic-sidebar" aria-label="Clinic navigation">
        @if($hasEnhancedWorkspace)
            <button class="sidebar-mobile-close" id="sidebar-mobile-close" type="button" aria-label="Close navigation">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
            <div class="brand reception-brand">
                <span class="reception-brand-mark" aria-hidden="true">+</span>
                <span>SAN ISIDRO<small>Family Medical Clinic</small></span>
            </div>
            <button class="sidebar-collapse" id="sidebar-collapse" type="button" aria-controls="clinic-sidebar" aria-expanded="true" aria-label="Collapse navigation" title="Collapse navigation">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="1.5"></rect><path d="M11 5v14"></path></svg>
                <span class="sidebar-collapse-label">Collapse navigation</span>
            </button>
            <div class="workspace"><span>{{ $workspaceLabel }}</span><i aria-hidden="true"></i></div>
        @else
            <div class="brand">SAN ISIDRO<small>Family Medical Clinic</small></div>
            <div class="workspace">Staff workspace</div>
        @endif

        <nav class="nav" aria-label="Primary navigation">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard')?'active':'' }}" data-label="Dashboard" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"></path><path d="M5.5 10v10h13V10M9 20v-6h6v6"></path></svg></span>@endif
                <span>Dashboard</span>
            </a>
            <a href="{{ route('patients.index') }}" class="{{ request()->routeIs('patients.*')?'active':'' }}" data-label="Patients" @if(request()->routeIs('patients.*')) aria-current="page" @endif>
                @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 20a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"></path></svg></span>@endif
                <span>Patients</span>
            </a>
            @if(auth()->user()->hasRole('Receptionist','Physician','Administrator'))
                <a href="{{ route('appointments.index') }}" class="{{ request()->routeIs('appointments.*','walkins.*')?'active':'' }}" data-label="{{ auth()->user()->role==='Physician'?'My Appointments':'Appointments' }}" @if(request()->routeIs('appointments.*','walkins.*')) aria-current="page" @endif>
                    @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 10h18M8 14h3M14 14h2M8 17h2"></path></svg></span>@endif
                    <span>{{ auth()->user()->role==='Physician'?'My Appointments':'Appointments' }}</span>
                </a>
            @endif
            <a href="{{ route('schedules.index') }}" class="{{ request()->routeIs('schedules.*','physicians.*')?'active':'' }}" data-label="Physician Schedules" @if(request()->routeIs('schedules.*','physicians.*')) aria-current="page" @endif>
                @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.5 2"></path></svg></span>@endif
                <span>Physician Schedules</span>
            </a>
            <a href="{{ route('visits.index') }}" class="{{ request()->routeIs('visits.*')?'active':'' }}" data-label="{{ auth()->user()->role==='Nurse'?'Patient Visits':(auth()->user()->role==='Physician'?'Consultation':'Clinic Visits') }}" @if(request()->routeIs('visits.*')) aria-current="page" @endif>
                @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 3h8v4H8zM6 5H4v16h16V5h-2"></path><path d="M12 10v7M8.5 13.5h7"></path></svg></span>@endif
                <span>{{ auth()->user()->role==='Nurse'?'Patient Visits':(auth()->user()->role==='Physician'?'Consultation':'Clinic Visits') }}</span>
            </a>
            @if(auth()->user()->role==='Administrator')
                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*')?'active':'' }}" data-label="Reports" @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                    @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg></span>@endif
                    <span>Reports</span>
                </a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*')?'active':'' }}" data-label="User Management" @if(request()->routeIs('users.*')) aria-current="page" @endif>
                    @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 20a5.5 5.5 0 0 1 11 0M17 11a3 3 0 1 0 0-6M16 15a5 5 0 0 1 5 5"></path></svg></span>@endif
                    <span>User Management</span>
                </a>
                <a href="{{ route('activity-logs.index') }}" class="{{ request()->routeIs('activity-logs.*')?'active':'' }}" data-label="Activity Logs" @if(request()->routeIs('activity-logs.*')) aria-current="page" @endif>
                    @if($hasEnhancedWorkspace)<span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v4H6zM5 5H3v16h18V5h-2"></path><path d="M7 11h10M7 15h10M7 19h6"></path></svg></span>@endif
                    <span>Activity Logs</span>
                </a>
            @endif
        </nav>

        @if($hasEnhancedWorkspace)
            <div class="reception-sidebar-note">
                <span class="status-dot" aria-hidden="true"></span>
                <div><strong>{{ $stationLabel }}</strong><small>{{ $stationStatus }}</small></div>
            </div>
        @endif

        <form method="POST" action="{{ route('logout') }}" data-confirm="Are you sure you want to log out of the clinic system?">
            @csrf
            <button class="logout">
                @if($hasEnhancedWorkspace)<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"></path></svg>@endif
                <span>Log out</span>
            </button>
        </form>
        <div class="sidebar-footer">Clinic local network</div>
    </aside>

    @if($hasEnhancedWorkspace)
        <button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Close navigation" hidden></button>
    @endif

    <main class="main">
        @if($hasEnhancedWorkspace)
            <header class="topbar reception-topbar">
                <div class="reception-header-start">
                    <button class="sidebar-mobile-open" id="sidebar-mobile-open" type="button" aria-controls="clinic-sidebar" aria-expanded="false" aria-label="Open navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                    </button>
                    <div class="reception-breadcrumb"><span>San Isidro Clinic</span><i aria-hidden="true">/</i><strong>@yield('crumb','Dashboard')</strong></div>
                </div>
                <div class="reception-profile">
                    <x-reading-size-controls />
                    <x-glass-controls />
                    <button class="reception-theme-toggle" id="reception-theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch appearance">
                        <svg class="reception-theme-icon theme-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.5 8.5 0 1 0 20.2 15.4Z"></path></svg>
                        <svg class="reception-theme-icon theme-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.75"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"></path></svg>
                    </button>
                    <a class="profile-trigger {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.show') }}" aria-label="Open your profile" title="View your profile">
                        <span class="reception-avatar {{ $hasInteractiveProfile ? 'interactive-profile' : '' }}" aria-hidden="true">
                            @if(auth()->user()->profile_photo_path)
                                <img src="{{ route('profile.photo',auth()->user()) }}" alt="">
                            @elseif($hasInteractiveProfile)
                                <img class="profile-state profile-state-light profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-light.jpeg') }}" alt="">
                                <img class="profile-state profile-state-light profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-light.jpeg') }}" alt="">
                                <img class="profile-state profile-state-dark profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-dark.jpeg') }}" alt="">
                                <img class="profile-state profile-state-dark profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-dark.jpeg') }}" alt="">
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="reception-user"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role }}</small></span>
                        <svg class="profile-trigger-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"></path></svg>
                    </a>
                </div>
            </header>
        @else
            <header class="topbar"><span>Clinic / @yield('crumb','Dashboard')</span><strong>{{ auth()->user()->name }} · {{ auth()->user()->role }}</strong></header>
        @endif

        <section class="content">
            @if(session('warning') && !session('success'))<div class="alert" style="background:#fff8e1;color:#76520a;border-color:#ead391">{{ session('warning') }}</div>@endif
            <x-validation-feedback />
            <div class="clinic-page">@yield('content')</div>
        </section>
    </main>
</div>

<x-success-feedback />

@if($hasEnhancedWorkspace && request()->routeIs('dashboard') && !session('success'))
    <div id="reception-welcome" class="reception-welcome" hidden aria-hidden="true">
        <div class="reception-welcome-card" role="status" aria-live="polite">
            <span class="reception-welcome-mark" aria-hidden="true">+</span>
            <p>{{ $welcomeLabel }}</p>
            <h1>Welcome back,<br>{{ auth()->user()->name }}!</h1>
            <span>{{ $welcomeMessage }}</span>
            <button id="reception-welcome-dismiss" type="button">Enter workspace <b aria-hidden="true">→</b></button>
        </div>
    </div>
@endif

<div id="confirmation-modal" class="confirm-modal" hidden role="dialog" aria-modal="true" aria-labelledby="confirmation-title" aria-describedby="confirmation-message">
    <div class="confirm-dialog">
        <div class="confirm-dialog-head"><h2 id="confirmation-title"><span class="confirm-dialog-icon">!</span>Confirm action</h2></div>
        <div class="confirm-dialog-body"><p id="confirmation-message"></p></div>
        <div class="confirm-dialog-actions">
            <button id="confirmation-cancel" type="button" class="btn">Cancel</button>
            <button id="confirmation-continue" type="button" class="btn btn-primary">Continue</button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('confirmation-modal');
    const messageElement = document.getElementById('confirmation-message');
    const cancelButton = document.getElementById('confirmation-cancel');
    const continueButton = document.getElementById('confirmation-continue');
    let pendingAction = null;
    let previouslyFocused = null;

    function openConfirmation(message, action) {
        messageElement.textContent = message;
        pendingAction = action;
        previouslyFocused = document.activeElement;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        cancelButton.focus();
    }

    function closeConfirmation() {
        modal.hidden = true;
        document.body.style.overflow = '';
        pendingAction = null;
        previouslyFocused?.focus();
    }

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
        let message = submitter?.dataset.confirm || form.dataset.confirm;
        const buttonLabel = submitter?.textContent?.trim() || '';
        if (!message && /^Save\b/i.test(buttonLabel)) {
            message = `${buttonLabel}? Please confirm that the entered information is correct.`;
        }
        if (!message) return;

        event.preventDefault();
        openConfirmation(message, function () {
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter || undefined);
        });
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-confirm]');
        if (!link) return;
        event.preventDefault();
        openConfirmation(link.dataset.confirm, () => window.location.assign(link.href));
    });

    cancelButton.addEventListener('click', closeConfirmation);
    continueButton.addEventListener('click', function () {
        const action = pendingAction;
        closeConfirmation();
        action?.();
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeConfirmation();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeConfirmation();
    });
})();
</script>

@if($hasEnhancedWorkspace)
<script>
(() => {
    const root = document.documentElement;
    const body = document.body;
    const themeButton = document.getElementById('reception-theme-toggle');
    const welcomeStorageKey = @json($welcomeStorageKey);
    const welcome = document.getElementById('reception-welcome');
    const dismissButton = document.getElementById('reception-welcome-dismiss');
    const sidebar = document.getElementById('clinic-sidebar');
    const collapseButton = document.getElementById('sidebar-collapse');
    const collapseButtonLabel = collapseButton.querySelector('.sidebar-collapse-label');
    const openButton = document.getElementById('sidebar-mobile-open');
    const closeButton = document.getElementById('sidebar-mobile-close');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mainContent = document.querySelector('.main');
    const desktopQuery = window.matchMedia('(min-width: 901px)');
    const navigationLinks = [...sidebar.querySelectorAll('.nav a')];
    let navigationReturnFocus = null;
    let welcomeTimer;

    function updateCollapseButton() {
        const isCollapsed = root.dataset.sidebar === 'collapsed';
        collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
        collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand navigation' : 'Collapse navigation');
        collapseButton.title = isCollapsed ? 'Expand navigation' : 'Collapse navigation';
        collapseButtonLabel.textContent = isCollapsed ? 'Expand navigation' : 'Collapse navigation';
    }

    function setMobileNavigation(open, restoreFocus = false) {
        if (desktopQuery.matches) open = false;
        body.classList.toggle('mobile-nav-open', open);
        openButton.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        sidebar.toggleAttribute('inert', !desktopQuery.matches && !open);
        mainContent.toggleAttribute('inert', open);

        if (open) {
            navigationReturnFocus = document.activeElement;
            window.requestAnimationFrame(() => navigationLinks[0]?.focus({ preventScroll: true }));
        } else if (restoreFocus && navigationReturnFocus instanceof HTMLElement) {
            navigationReturnFocus.focus({ preventScroll: true });
        }
    }

    collapseButton.addEventListener('click', () => {
        root.dataset.sidebar = root.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
        localStorage.setItem('clinic-sidebar', root.dataset.sidebar);
        updateCollapseButton();
    });

    openButton.addEventListener('click', () => setMobileNavigation(true));
    closeButton.addEventListener('click', () => setMobileNavigation(false, true));
    backdrop.addEventListener('click', () => setMobileNavigation(false, true));
    navigationLinks.forEach((link) => link.addEventListener('click', () => {
        if (!desktopQuery.matches) setMobileNavigation(false);
    }));
    desktopQuery.addEventListener('change', () => setMobileNavigation(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('mobile-nav-open')) {
            setMobileNavigation(false, true);
            return;
        }

        if (event.key !== 'Tab' || !body.classList.contains('mobile-nav-open')) return;
        const focusable = [...sidebar.querySelectorAll('a[href], button:not([disabled])')];
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });

    updateCollapseButton();
    setMobileNavigation(false);

    function updateThemeButton() {
        const isDark = root.dataset.theme === 'dark';
        themeButton.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        themeButton.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
    }

    function dismissWelcome() {
        if (!welcome || welcome.hidden || welcome.classList.contains('is-leaving')) return;
        window.clearTimeout(welcomeTimer);
        welcome.classList.add('is-leaving');
        window.setTimeout(() => {
            welcome.hidden = true;
            welcome.setAttribute('aria-hidden', 'true');
            body.classList.remove('reception-welcome-active');
        }, 520);
    }

    themeButton.addEventListener('click', () => {
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('clinic-theme', root.dataset.theme);
        updateThemeButton();
    });
    updateThemeButton();

    window.requestAnimationFrame(() => body.classList.add('reception-ready'));

    if (welcome && !sessionStorage.getItem(welcomeStorageKey)) {
        sessionStorage.setItem(welcomeStorageKey, '1');
        welcome.hidden = false;
        welcome.setAttribute('aria-hidden', 'false');
        body.classList.add('reception-welcome-active');
        dismissButton?.focus({ preventScroll: true });
        welcomeTimer = window.setTimeout(dismissWelcome, 3000);
    }

    dismissButton?.addEventListener('click', dismissWelcome);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') dismissWelcome();
    });
})();
</script>
@endif
<script src="{{ asset('js/password-controls.js') }}" defer></script>
<script src="{{ asset('js/date-picker.js') }}" defer></script>
<script src="{{ asset('js/form-validation.js') }}" defer></script>
<script src="{{ asset('js/success-feedback.js') }}" defer></script>
@stack('scripts')
</body>
</html>
