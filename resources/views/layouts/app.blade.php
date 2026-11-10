<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'UNBS Visitor Management System')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/unbs-logo.png') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --unbs-blue: #084090;
            --unbs-blue-dark: #062f6b;
            --unbs-navy: #0b2a5c;
            --unbs-gold: #fcd116;

            /* Bootstrap's "primary" becomes the UNBS blue everywhere. */
            --bs-primary: #084090;
            --bs-primary-rgb: 8, 64, 144;
            --bs-link-color: #084090;
            --bs-link-color-rgb: 8, 64, 144;
            --bs-link-hover-color: #062f6b;
            --bs-link-hover-color-rgb: 6, 47, 107;
        }

        body.bg-light { background-color: #f3f6fb !important; }

        /* Navigation bar */
        .unbs-nav {
            background-color: var(--unbs-blue);
            border-bottom: 3px solid var(--unbs-gold);
        }
        .unbs-nav .navbar-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            font-weight: 600;
            letter-spacing: .3px;
        }
        .unbs-nav .navbar-brand img { height: 42px; width: auto; }
        .unbs-nav .nav-link { color: rgba(255, 255, 255, .78); }
        .unbs-nav .nav-link:hover { color: #fff; }
        .unbs-nav .nav-link.active {
            color: #fff;
            font-weight: 600;
            box-shadow: inset 0 -3px 0 var(--unbs-gold);
        }

        /* Buttons */
        .btn-primary {
            --bs-btn-bg: var(--unbs-blue);
            --bs-btn-border-color: var(--unbs-blue);
            --bs-btn-hover-bg: var(--unbs-blue-dark);
            --bs-btn-hover-border-color: var(--unbs-blue-dark);
            --bs-btn-active-bg: var(--unbs-blue-dark);
            --bs-btn-active-border-color: var(--unbs-blue-dark);
            --bs-btn-disabled-bg: var(--unbs-blue);
            --bs-btn-disabled-border-color: var(--unbs-blue);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--unbs-blue);
            --bs-btn-border-color: var(--unbs-blue);
            --bs-btn-hover-bg: var(--unbs-blue);
            --bs-btn-hover-border-color: var(--unbs-blue);
            --bs-btn-active-bg: var(--unbs-blue);
            --bs-btn-active-border-color: var(--unbs-blue);
        }

        /* Cards */
        .card { border-radius: .75rem; overflow: hidden; }
        .card-header.bg-dark { background-color: var(--unbs-navy) !important; }
    </style>
</head>
<body class="bg-light">

    @auth
    <nav class="navbar navbar-expand navbar-dark unbs-nav mb-4">
        <div class="container">
            <span class="navbar-brand">
                <img src="{{ asset('images/unbs-logo.png') }}" alt="UNBS">
                <span>UNBS Visitor Management System</span>
            </span>

            <ul class="navbar-nav me-auto">
                @can('view registrations')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('registrations.*') ? 'active' : '' }}"
                           href="{{ route('registrations.index') }}">Registrations</a>
                    </li>
                @endcan
                @can('manage users')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           href="{{ route('users.index') }}">Users</a>
                    </li>
                @endcan
                @can('manage gate visitors')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('gate.create', 'gate.ticket') ? 'active' : '' }}"
                           href="{{ route('gate.create') }}">Check-In</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('gate.checkout.*') ? 'active' : '' }}"
                           href="{{ route('gate.checkout.index') }}">Check-Out</a>
                    </li>
                @endcan
                @can('manage visitor devices')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('entry.*') ? 'active' : '' }}"
                           href="{{ route('entry.index') }}">Entry</a>
                    </li>
                @endcan
                @can('manage visitor reception')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reception.*') ? 'active' : '' }}"
                           href="{{ route('reception.index') }}">Reception</a>
                    </li>
                @endcan
                @can('manage sample reception')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sample.*') ? 'active' : '' }}"
                           href="{{ route('sample.index') }}">Samples</a>
                    </li>
                @endcan
                @role('Administrator')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('labels.*') ? 'active' : '' }}"
                           href="{{ route('labels.index') }}">Labels</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.purposes.*') ? 'active' : '' }}"
                           href="{{ route('admin.purposes.index') }}">Purposes</a>
                    </li>
                @endrole
            </ul>

            <div class="d-flex align-items-center text-white">
                <a href="{{ route('profile.edit') }}" class="me-3 small text-white text-decoration-none">{{ Auth::user()->name }}</a>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="container">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

    @yield('scripts')
</body>
</html>