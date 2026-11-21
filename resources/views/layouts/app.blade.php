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
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
        }
        .unbs-nav .navbar-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            font-weight: 600;
            letter-spacing: .3px;
        }
        .unbs-nav .navbar-brand img { height: 42px; width: auto; }
        .unbs-nav .nav-link { color: rgba(255, 255, 255, .78); white-space: nowrap; }
        .unbs-nav .nav-link:hover,
        .unbs-nav .nav-link.show { color: #fff; }
        .unbs-nav .nav-link.active {
            color: #fff;
            font-weight: 600;
            box-shadow: inset 0 -3px 0 var(--unbs-gold);
        }
        .unbs-nav .navbar-toggler { border-color: rgba(255, 255, 255, .4); }
        .unbs-nav .dropdown-toggle::after { vertical-align: .15em; }
        .unbs-nav .dropdown-menu {
            border: 0;
            border-radius: .6rem;
            box-shadow: 0 .5rem 1.25rem rgba(8, 64, 144, .2);
            --bs-dropdown-link-active-bg: var(--unbs-blue);
        }
        .unbs-nav .dropdown-header {
            text-transform: uppercase;
            letter-spacing: .6px;
            font-size: .7rem;
            color: var(--unbs-blue);
        }
        .unbs-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--unbs-gold);
            color: var(--unbs-navy);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
    @php
        $u = auth()->user();
        $isAdmin = $u->hasRole('Administrator');
        $on = fn (...$patterns) => request()->routeIs(...$patterns);

        // Checkpoints, in the order a visitor meets them.
        $stations = [];
        if ($u->can('manage gate visitors')) {
            $stations[] = ['group' => 'Main Gate', 'flat' => 'Home', 'item' => 'Home', 'url' => route('gate.home'), 'active' => $on('gate.home')];
            $stations[] = ['group' => 'Main Gate', 'flat' => 'Check-In', 'item' => 'Check-In', 'url' => route('gate.create'), 'active' => $on('gate.create', 'gate.ticket')];
            $stations[] = ['group' => 'Main Gate', 'flat' => 'Check-Out', 'item' => 'Check-Out', 'url' => route('gate.checkout.index'), 'active' => $on('gate.checkout.*')];
        }
        if ($u->can('manage visitor devices')) {
            $stations[] = ['group' => 'Entry', 'flat' => 'Entry', 'item' => 'Visitor devices', 'url' => route('entry.index'), 'active' => $on('entry.*') && ! $on('entry.staff.*')];
        }
        if ($u->can('manage staff entry')) {
            $stations[] = ['group' => 'Entry', 'flat' => 'Staff devices', 'item' => 'Staff devices', 'url' => route('entry.staff.index'), 'active' => $on('entry.staff.*')];
        }
        
        if ($u->can('manage visitor reception')) {
            $stations[] = ['group' => 'Reception', 'flat' => 'Reception', 'item' => 'Standard House', 'url' => route('reception.index'), 'active' => $on('reception.*')];
        }
        if ($u->can('manage sample reception')) {
            $stations[] = ['group' => 'Reception', 'flat' => 'Sample Reception', 'item' => 'Sample House', 'url' => route('sample.index'), 'active' => $on('sample.*')];
        }

        // Staff laptops: the employee list, and the old registrations page for now.
        $staff = [];
        if ($u->can('manage staff devices')) {
            $staff[] = ['flat' => 'Staff Laptops', 'item' => 'Employees', 'url' => route('staff.employees.index'), 'active' => $on('staff.employees.*')];
        }
        if ($u->can('manage staff entry')) {
            $stations[] = ['group' => 'Entry', 'flat' => 'Staff check-out', 'item' => 'Staff check-out', 'url' => route('entry.staff-out.start'), 'active' => $on('entry.staff-out.*')];
        }
        if ($u->can('view registrations')) {
            $staff[] = ['flat' => 'Staff Laptops', 'item' => 'Registrations', 'url' => route('registrations.index'), 'active' => $on('registrations.*')];
        }
        if ($u->can('manage staff devices')) {
            $staff[] = ['flat' => 'Issued devices', 'item' => 'Issued devices', 'url' => route('issued.index'), 'active' => $on('issued.*')];
        }

        // Reports.
        $insights = [];
        if ($u->can('view reports')) {
            $insights[] = ['flat' => 'Reports', 'item' => 'Summary reports', 'url' => route('reports.index'), 'active' => $on('reports.*')];
        }
        if ($u->can('view visitor records')) {
            $insights[] = ['flat' => 'Records', 'item' => 'Visitor records', 'url' => route('records.index'), 'active' => $on('records.*')];
        }
         
        if ($u->can('verify gate items')) {
            $pendingItems = \App\Models\VisitorVisit::query()
            ->where(fn ($q) => $q->whereHas('leftItems', fn ($i) => $i->whereNull('approved_at'))
                    ->orWhereHas('arms', fn ($i) => $i->whereNull('approved_at'))
                    ->orWhereHas('waivers', fn ($i) => $i->whereNull('approved_at')))
                ->count();
            $insights[] = ['flat' => 'Items to verify', 'item' => 'Items to verify' . ($pendingItems ? " ({$pendingItems})" : ''), 'url' => route('gate.items.index'), 'active' => $on('gate.items.index', 'gate.items.approve')];
        }
        if ($u->can('view gate items report')) {
            $insights[] = ['flat' => 'Items report', 'item' => 'Gate items report', 'url' => route('gate.items.report'), 'active' => $on('gate.items.report')];
        }
        if ($u->can('view gadgets report')) {
            $insights[] = ['flat' => 'Walk-in gadgets', 'item' => 'Walk-in gadgets', 'url' => route('labels.gadgets.report'), 'active' => $on('labels.gadgets.report')];
        }

        // Administration.
        $admin = [];
        if ($u->can('manage users')) {
            $admin[] = ['group' => 'People and access', 'label' => 'Users', 'url' => route('users.index'), 'active' => $on('users.*')];
        }
        if ($u->can('approve device requests')) {
            $pendingRequests = \App\Models\StaffDeviceRequest::where('status', 'pending')->count();
            $admin[] = ['group' => 'Staff devices', 'label' => 'Device requests' . ($pendingRequests ? " ({$pendingRequests})" : ''), 'url' => route('admin.device-requests.index'), 'active' => $on('admin.device-requests.*')];
        }
        if ($isAdmin) {
            $admin[] = ['group' => 'People and access', 'label' => 'User access', 'url' => route('admin.report-access.index'), 'active' => $on('admin.report-access.*')];
            $admin[] = ['group' => 'Visitor setup', 'label' => 'Purposes of visit', 'url' => route('admin.purposes.index'), 'active' => $on('admin.purposes.*')];
            $admin[] = ['group' => 'Visitor setup', 'label' => 'Sample types', 'url' => route('admin.sample-types.index'), 'active' => $on('admin.sample-types.*')];
            $admin[] = ['group' => 'Visitor setup', 'label' => 'Unserved reasons', 'url' => route('admin.reasons.index'), 'active' => $on('admin.reasons.*')];
            $admin[] = ['group' => 'Stickers', 'label' => 'Labels', 'url' => route('labels.index'), 'active' => $on('labels.*')];
        }
    @endphp

    <nav class="navbar navbar-expand-xl navbar-dark unbs-nav mb-4">
        <div class="container">
            <span class="navbar-brand">
                <img src="{{ asset('images/unbs-logo.png') }}" alt="UNBS">
                <span>UNBS Visitor Management System</span>
            </span>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-xl-0">

                    {{-- Checkpoints: plain links for most roles, one dropdown when there are many --}}
                    @if (count($stations) > 3)
                        @include('layouts._nav-dropdown', ['label' => 'Checkpoints', 'items' => $stations])
                    @else
                        @foreach ($stations as $item)
                            @include('layouts._nav-link', ['item' => $item])
                        @endforeach
                    @endif

                    @if (count($staff) > 1)
                        @include('layouts._nav-dropdown', ['label' => 'Staff Laptops', 'items' => $staff])
                    @else
                        @foreach ($staff as $item)
                            @include('layouts._nav-link', ['item' => $item])
                        @endforeach
                    @endif

                    @if (count($insights) > 1)
                        @include('layouts._nav-dropdown', ['label' => 'Reports', 'items' => $insights])
                    @else
                        @foreach ($insights as $item)
                            @include('layouts._nav-link', ['item' => $item])
                        @endforeach
                    @endif

                    @if (count($admin) > 1)
                        @include('layouts._nav-dropdown', ['label' => 'Administration', 'items' => $admin])
                    @else
                        @foreach ($admin as $item)
                            @include('layouts._nav-link', ['item' => $item])
                        @endforeach
                    @endif
                </ul>

                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-white text-decoration-none dropdown-toggle text-nowrap"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="unbs-avatar">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                        <span>{{ $u->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ $u->getRoleNames()->implode(', ') ?: 'No role' }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">My profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item">Log out</button>
                            </form>
                        </li>
                    </ul>
                </div>
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

@auth
    @include('layouts._kiosk', ['kioskAllowed' => auth()->user()->can('use kiosk mode')])
@endauth
</body>
</html>