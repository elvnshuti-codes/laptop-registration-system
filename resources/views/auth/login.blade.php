<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome &middot; UNBS Visitor Management System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/unbs-logo.png') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --unbs-blue: #084090; --unbs-blue-dark: #062f6b; --unbs-navy: #0b2a5c; --unbs-gold: #fcd116;
            --bs-primary: #084090; --bs-primary-rgb: 8, 64, 144;
        }

        html, body { height: 100%; }
        body { margin: 0; background: #f3f6fb; }

        .btn-primary {
            --bs-btn-bg: #084090; --bs-btn-border-color: #084090;
            --bs-btn-hover-bg: #062f6b; --bs-btn-hover-border-color: #062f6b;
            --bs-btn-active-bg: #062f6b; --bs-btn-active-border-color: #062f6b;
        }
        .form-control:focus { border-color: #084090; box-shadow: 0 0 0 .25rem rgba(8, 64, 144, .2); }

        .welcome { display: flex; flex-direction: column; min-height: 100vh; }
        @media (min-width: 992px) { .welcome { flex-direction: row; } }

        /* ---- Left: brand, slowly moving ---- */
        .hero {
            position: relative; overflow: hidden; color: #fff; flex: 1.25;
            display: flex; flex-direction: column; align-items: center;
            padding: 2.5rem 1.5rem 1.25rem; text-align: center; min-height: 42vh;
            background: linear-gradient(125deg, #062f6b, #084090, #0b5cc7, #0b2a5c, #084090);
            background-size: 300% 300%;
            animation: drift 22s ease-in-out infinite;
        }
        @keyframes drift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        .shape { position: absolute; border-radius: 50%; background: rgba(252, 209, 22, .10); animation: float 16s ease-in-out infinite; }
        .shape.s1 { width: 22rem; height: 22rem; left: -7rem; top: -6rem; }
        .shape.s2 { width: 14rem; height: 14rem; right: -4rem; top: 18%; background: rgba(255, 255, 255, .07); animation-delay: -5s; animation-duration: 20s; }
        .shape.s3 { width: 18rem; height: 18rem; left: 12%; bottom: -9rem; animation-delay: -9s; }
        .shape.s4 { width: 7rem; height: 7rem; right: 22%; bottom: 12%; background: rgba(255, 255, 255, .08); animation-delay: -2s; animation-duration: 12s; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(2.2rem, -2.6rem) scale(1.08); } }

        .hero > *:not(.shape) { position: relative; z-index: 1; }

        /* The content sits in the middle; the agency line follows below it, never on top of it. */
        .hero-main { flex: 1; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }

        .logo-badge {
            width: clamp(120px, 20vh, 200px); aspect-ratio: 1; border-radius: 50%; background: #fff;
            display: grid; place-items: center; margin-bottom: 1.5rem;
            animation: halo 5s ease-in-out infinite;
        }
        .logo-badge img { width: 74%; height: auto; }
        @keyframes halo {
            0%, 100% { box-shadow: 0 0 0 .5rem rgba(255, 255, 255, .14), 0 1rem 2rem rgba(0, 0, 0, .25); }
            50% { box-shadow: 0 0 0 1.2rem rgba(255, 255, 255, .06), 0 1rem 2rem rgba(0, 0, 0, .25); }
        }

        .hero h1 { font-size: clamp(1.5rem, 3vw, 2.7rem); font-weight: 300; line-height: 1.15; margin: 0; }
        .hero h1 .brand { font-weight: 800; letter-spacing: .04em; }
        .hero .vms { display: inline-block; margin-top: .7rem; padding: .15rem .9rem; border-radius: 2rem; background: var(--unbs-gold); color: #1a1a1a; font-weight: 700; letter-spacing: .2em; }

        /* Messages that fade one after another. */
        .messages { position: relative; height: 2.6rem; width: 100%; margin-top: 1.5rem; }
        .messages span {
            position: absolute; inset: 0; opacity: 0; font-size: clamp(1.2rem, 2vw, 1.7rem); color: #ffe27a;
            animation: msg 12s infinite both;
        }
        .messages span:nth-child(2) { animation-delay: 4s; }
        .messages span:nth-child(3) { animation-delay: 8s; }
        @keyframes msg {
            0% { opacity: 0; transform: translateY(10px); }
            6%, 30% { opacity: 1; transform: none; }
            36%, 100% { opacity: 0; transform: translateY(-10px); }
        }

        .clock {
            margin-top: 1.25rem; padding: .9rem 1.8rem; border-radius: 1rem;
            background: rgba(255, 255, 255, .10); border: 1px solid rgba(255, 255, 255, .18);
            -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
        }
        .clock .time { font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 300; letter-spacing: .05em; line-height: 1; }
        .clock .date { opacity: .85; margin-top: .35rem; }

        .agency { padding-top: 1rem; font-size: .85rem; opacity: .7; }

        /* ---- Right: sign-in ---- */
        .signin { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1.25rem; border-top: 4px solid var(--unbs-gold); }
        @media (min-width: 992px) { .signin { border-top: 0; border-left: 4px solid var(--unbs-gold); } }
        .signin .card { width: 100%; max-width: 26rem; border: 0; border-radius: 1.25rem; box-shadow: 0 .5rem 2rem rgba(8, 64, 144, .15); }
        .signin .form-control { min-height: 3.4rem; border-radius: .75rem; }
        .signin .input-group .btn { border-radius: 0 .75rem .75rem 0; }
        .signin .btn-primary { min-height: 3.3rem; font-size: 1.1rem; border-radius: .75rem; }
        .help { margin-top: 1.5rem; text-align: center; font-size: .85rem; color: #6c757d; }

        @media (prefers-reduced-motion: reduce) {
            .hero, .shape, .messages span, .logo-badge { animation: none; }
            .messages span:not(:first-child) { display: none; }
            .messages span:first-child { opacity: 1; }
        }
    </style>
</head>
<body>
<div class="welcome">

    <section class="hero">
        <span class="shape s1"></span><span class="shape s2"></span><span class="shape s3"></span><span class="shape s4"></span>

        <div class="hero-main">
            <div class="logo-badge"><img src="{{ asset('images/unbs-logo.png') }}" alt="UNBS"></div>

            <h1><span class="brand">UNBS</span> Visitor Management System</h1>
            <span class="vms">VMS</span>

            <div class="messages" aria-live="off">
                <span>Welcome</span>
                <span>Karibu</span>
                <span>Please sign in to begin</span>
            </div>

            <div class="clock">
                <div class="time" id="clock-time">--:--</div>
                <div class="date" id="clock-date">&nbsp;</div>
            </div>
        </div>

        <div class="agency">Uganda National Bureau of Standards</div>
    </section>

    <section class="signin">
        <div class="w-100" style="max-width: 26rem">
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h4 mb-1">Sign in</h2>
                    <p class="text-muted mb-4">Use your UNBS VMS account.</p>

                    <form action="{{ route('login.submit') }}" method="POST" id="signin-form">
                        @csrf

                        <div class="form-floating mb-3">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Email"
                                   autocomplete="username" class="form-control @error('email') is-invalid @enderror" autofocus>
                            <label for="email">Email</label>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <div class="form-floating flex-grow-1">
                                    <input type="password" id="password" name="password" placeholder="Password"
                                           autocomplete="current-password" class="form-control @error('password') is-invalid @enderror">
                                    <label for="password">Password</label>
                                </div>
                                <button type="button" class="btn btn-outline-secondary" id="show-password">Show</button>
                            </div>
                            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Sign in</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="help">Forgot your password or cannot sign in? Please contact the system administrator.</div>
        </div>
    </section>
</div>

<script>
    // A fresh sign-in starts a fresh idle clock.
    try { sessionStorage.removeItem('vms_last_activity'); } catch (e) {}

    // Live clock.
    (function () {
        const time = document.getElementById('clock-time');
        const date = document.getElementById('clock-date');

        function update() {
            const now = new Date();
            time.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            date.textContent = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }

        update();
        setInterval(update, 1000);
    })();

    // Show or hide the password.
    document.getElementById('show-password').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
    });

    // A screen left on this page for hours would hold an expired security token and fail on sign-in.
    // Every 20 minutes, if nobody has typed anything, load the page again to renew it.
    setInterval(function () {
        if (document.getElementById('email').value === '' && document.getElementById('password').value === '') {
            location.reload();
        }
    }, 20 * 60 * 1000);
</script>

@include('layouts._kiosk', ['kioskAllowed' => true, 'kioskControls' => false])
</body>
</html>