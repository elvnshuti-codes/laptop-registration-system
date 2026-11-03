<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'UNBS  Visitor  Management System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    @auth
    <nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
        <div class="container">
            <span class="navbar-brand">UNBS  Visitor  Management System</span>

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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @yield('scripts')
</body>
</html>