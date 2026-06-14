<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoPortal Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-base: #090d16;
            --bg-panel: rgba(15, 23, 42, 0.7);
            --bg-card: rgba(30, 41, 59, 0.45);
            --border-color: rgba(148, 163, 184, 0.12);
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --text-heading: #f8fafc;
            --accent-blue: #0099ff;
            --accent-blue-hover: #0077cc;
            --accent-blue-glow: rgba(0, 153, 255, 0.25);
            --sidebar-bg: rgba(15, 23, 42, 0.85);
            --sidebar-text: #94a3b8;
            --sidebar-active: rgba(30, 41, 59, 0.6);
            --glass-blur: blur(16px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg-base); 
            background-image: radial-gradient(circle at 10% 20%, rgba(0, 153, 255, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 40%);
            color: var(--text-main); 
            display: flex; 
            flex-direction: column;
            height: 100vh; 
            overflow: hidden; 
        }

        /* Navbar */
        .navbar { 
            height: 72px; 
            background: var(--bg-panel); 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 24px; 
            backdrop-filter: var(--glass-blur);
            position: relative;
            z-index: 1000;
        }
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            filter: drop-shadow(0 0 10px rgba(0, 153, 255, 0.45));
        }
        .brand-copy {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #0099ff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-shadow: 0 0 10px rgba(0, 153, 255, 0.4);
        }
        .brand-kicker {
            font-family: 'Inter', sans-serif;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .navbar-center {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 100%;
        }
        .nav-dropdown {
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .nav-dropdown-btn {
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-main);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .nav-dropdown-btn:hover,
        .nav-dropdown.open .nav-dropdown-btn {
            color: white;
            background: rgba(255, 255, 255, 0.04);
            border-color: var(--border-color);
        }
        .nav-dropdown-btn.active-group {
            color: #0099ff;
            background: rgba(0, 153, 255, 0.05);
            border-color: rgba(0, 153, 255, 0.2);
            text-shadow: 0 0 5px rgba(0, 153, 255, 0.2);
        }
        .nav-dropdown-btn .dropdown-chevron {
            font-size: 10px;
            color: var(--text-muted);
            transition: transform 0.2s ease;
        }
        .nav-dropdown:hover .nav-dropdown-btn .dropdown-chevron,
        .nav-dropdown.open .nav-dropdown-btn .dropdown-chevron {
            transform: rotate(180deg);
            color: white;
        }
        .nav-dropdown-content {
            position: absolute;
            top: calc(100% - 10px);
            left: 0;
            min-width: 220px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            padding: 8px;
            z-index: 1010;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            pointer-events: none;
        }
        .nav-dropdown:hover .nav-dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .dropdown-item i {
            width: 16px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
            transition: color 0.2s ease;
        }
        .dropdown-item:hover {
            background: rgba(0, 153, 255, 0.1);
            color: white;
        }
        .dropdown-item:hover i {
            color: #0099ff;
        }
        .dropdown-item.active {
            background: rgba(0, 153, 255, 0.15);
            color: white;
            border-left: 3px solid var(--accent-blue);
            padding-left: 11px;
        }
        .dropdown-item.active i {
            color: #0099ff;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--text-main);
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
        }

        @media (max-width: 991px) {
            .navbar-center {
                display: none;
                position: absolute;
                top: 72px;
                left: 0;
                width: 100%;
                background: rgba(15, 23, 42, 0.98);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--border-color);
                flex-direction: column;
                align-items: stretch;
                padding: 16px;
                gap: 12px;
                height: auto;
                box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            }
            .navbar-center.open {
                display: flex;
            }
            .mobile-menu-toggle {
                display: block;
            }
            .nav-dropdown {
                flex-direction: column;
                align-items: flex-start;
                height: auto;
            }
            .nav-dropdown-btn {
                width: 100%;
                justify-content: space-between;
                padding: 12px;
            }
            .nav-dropdown-content {
                position: static;
                width: 100%;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                background: rgba(255,255,255,0.02);
                border: 1px solid rgba(255,255,255,0.05);
                margin-top: 4px;
                display: none;
                pointer-events: auto;
            }
            .nav-dropdown.open .nav-dropdown-content {
                display: block;
            }
            .navbar-right {
                gap: 8px;
            }
            .user-meta {
                display: none;
            }
        }

        /* User Chip & Actions */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(0, 153, 255, 0.14);
            color: #7dd3fc;
            border: 1px solid rgba(125, 211, 252, 0.28);
        }
        .user-meta {
            line-height: 1.1;
        }
        .user-name {
            color: var(--text-heading);
            font-size: 13px;
            font-weight: 700;
        }
        .user-office {
            margin-top: 3px;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .logout-form {
            margin: 0;
            display: flex;
            align-items: center;
        }
        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            border: 1px solid rgba(248, 113, 113, 0.25);
            background: rgba(239, 68, 68, 0.10);
            color: #fecaca;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.18);
            border-color: rgba(248, 113, 113, 0.45);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(239, 68, 68, 0.16);
        }

        /* Main Content Wrapper */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .content { flex: 1; padding: 32px; overflow-y: auto; }
        
        /* Typography & Utilities */
        h1.page-title { 
            color: var(--text-heading); 
            font-family: 'Outfit', sans-serif;
            font-size: 26px; 
            font-weight: 700; 
            letter-spacing: -0.5px;
            margin-bottom: 24px; 
        }
        .card { 
            background: var(--bg-card); 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            padding: 30px; 
            backdrop-filter: var(--glass-blur);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4); 
        }

        h3 {
            color: var(--text-heading);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Buttons */
        .btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            border: 1px solid transparent; 
            transition: all 0.2s ease; 
        }
        .btn-primary { 
            background: var(--accent-blue); 
            color: #090d16; 
            box-shadow: 0 0 15px rgba(0, 153, 255, 0.2);
        }
        .btn-primary:hover { 
            background: var(--accent-blue-hover); 
            transform: translateY(-1px);
            box-shadow: 0 0 20px var(--accent-blue);
        }
        .btn-secondary { 
            background: rgba(255, 255, 255, 0.05); 
            border-color: var(--border-color); 
            color: var(--text-main); 
        }
        .btn-secondary:hover { 
            background: rgba(255, 255, 255, 0.1); 
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        .btn-danger { 
            background: rgba(239, 68, 68, 0.2); 
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5; 
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);
        }
        
        /* Table Styles */
        .table-responsive {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: var(--glass-blur);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { 
            font-size: 11px; 
            font-weight: 700; 
            color: var(--text-heading); 
            text-transform: uppercase; 
            letter-spacing: 1px;
            background: rgba(15, 23, 42, 0.6); 
        }
        td {
            font-size: 14px;
            color: var(--text-main);
        }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        tr:last-child td { border-bottom: none; }
        
        /* Forms styling */
        .form-group { margin-bottom: 20px; }
        .form-label { 
            display: block; 
            font-size: 13px; 
            font-weight: 600; 
            color: var(--text-main); 
            margin-bottom: 8px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control { 
            width: 100%; 
            padding: 12px 16px; 
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 14px; 
            color: var(--text-heading);
            outline: none; 
            transition: all 0.2s ease; 
        }
        .form-control:focus { 
            border-color: var(--accent-blue); 
            background: rgba(15, 23, 42, 0.6);
            box-shadow: 0 0 12px rgba(0, 153, 255, 0.15); 
        }
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }
        select.form-control option {
            background-color: #0f172a;
            color: var(--text-heading);
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .alert-success { 
            background: rgba(16, 185, 129, 0.15); 
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0; 
            padding: 16px; 
            border-radius: 8px; 
            margin-bottom: 24px; 
            font-weight: 500; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger { 
            background: rgba(239, 68, 68, 0.15); 
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5; 
            padding: 16px; 
            border-radius: 8px; 
            margin-bottom: 24px; 
            font-weight: 500; 
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body class="admin-body">

    @php
        $mapGroupOpen = Request::routeIs('admin.map') || Request::routeIs('admin.map-export.*');
        $layersGroupOpen = Request::routeIs('admin.features.*') || Request::routeIs('admin.layer-types.*');
        $dataGroupOpen = Request::routeIs('admin.uploads.*')
            || Request::routeIs('admin.gis-converter.*')
            || Request::routeIs('admin.municipal-boundary.*')
            || Request::routeIs('admin.barangays.*')
            || Request::routeIs('admin.data-completeness.*');
        $systemGroupOpen = Request::routeIs('admin.activity-logs.*') || Request::routeIs('admin.users.*');
    @endphp

    <header class="navbar">
        <div class="navbar-left">
            <img src="/images/logo.png" alt="Bayambang Logo" class="brand-logo">
            <div class="brand-copy">
                <div class="brand-title">GEOBAYAMBANG</div>
                <div class="brand-kicker">Admin Console</div>
            </div>
        </div>

        <nav class="navbar-center" id="navbarCenterMenu">
            <!-- Workspace Dropdown -->
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn {{ $mapGroupOpen ? 'active-group' : '' }}" onclick="toggleMobileDropdown(event)">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Workspace
                    <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
                </button>
                <div class="nav-dropdown-content">
                    <a href="/" class="dropdown-item"><i class="fa-solid fa-map"></i> Public Map</a>
                    <a href="{{ route('admin.map') }}" class="dropdown-item {{ Request::routeIs('admin.map') ? 'active' : '' }}"><i class="fa-solid fa-location-dot"></i> Admin Map</a>
                    <a href="{{ route('admin.map-export.index') }}" class="dropdown-item {{ Request::routeIs('admin.map-export.*') ? 'active' : '' }}"><i class="fa-solid fa-file-export"></i> Map Export</a>
                </div>
            </div>

            <!-- Layers & Features Dropdown -->
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn {{ $layersGroupOpen ? 'active-group' : '' }}" onclick="toggleMobileDropdown(event)">
                    <i class="fa-solid fa-layer-group"></i>
                    Layers & Features
                    <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
                </button>
                <div class="nav-dropdown-content">
                    <a href="{{ route('admin.features.index') }}" class="dropdown-item {{ Request::routeIs('admin.features.*') ? 'active' : '' }}"><i class="fa-solid fa-draw-polygon"></i> Map Features</a>
                    @hasanyrole('admin|super-admin')
                        <a href="{{ route('admin.layer-types.index') }}" class="dropdown-item {{ Request::routeIs('admin.layer-types.*') ? 'active' : '' }}"><i class="fa-solid fa-layer-group"></i> Layer Types</a>
                    @endhasanyrole
                </div>
            </div>

            <!-- Boundary & Data Dropdown (restricted to admin|super-admin) -->
            @hasanyrole('admin|super-admin')
                <div class="nav-dropdown">
                    <button class="nav-dropdown-btn {{ $dataGroupOpen ? 'active-group' : '' }}" onclick="toggleMobileDropdown(event)">
                        <i class="fa-solid fa-database"></i>
                        Boundary & Data
                        <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
                    </button>
                    <div class="nav-dropdown-content">
                        <a href="{{ route('admin.uploads.index') }}" class="dropdown-item {{ Request::routeIs('admin.uploads.*') ? 'active' : '' }}"><i class="fa-solid fa-arrow-up-from-bracket"></i> Upload Data</a>
                        <a href="{{ route('admin.gis-converter.index') }}" class="dropdown-item {{ Request::routeIs('admin.gis-converter.*') ? 'active' : '' }}"><i class="fa-solid fa-code-compare"></i> GIS Converter</a>
                        <a href="{{ route('admin.municipal-boundary.index') }}" class="dropdown-item {{ Request::routeIs('admin.municipal-boundary.*') ? 'active' : '' }}"><i class="fa-solid fa-border-top-left"></i> Bayambang Boundary</a>
                        <a href="{{ route('admin.barangays.index') }}" class="dropdown-item {{ Request::routeIs('admin.barangays.*') ? 'active' : '' }}"><i class="fa-solid fa-mountain-city"></i> Barangay Management</a>
                        <a href="{{ route('admin.data-completeness.index') }}" class="dropdown-item {{ Request::routeIs('admin.data-completeness.*') ? 'active' : '' }}"><i class="fa-solid fa-list-check"></i> Data Completeness</a>
                    </div>
                </div>
            @endhasanyrole

            <!-- System Admin Dropdown (restricted to admin|super-admin) -->
            @hasanyrole('admin|super-admin')
                <div class="nav-dropdown">
                    <button class="nav-dropdown-btn {{ $systemGroupOpen ? 'active-group' : '' }}" onclick="toggleMobileDropdown(event)">
                        <i class="fa-solid fa-shield-halved"></i>
                        System Admin
                        <i class="fa-solid fa-chevron-down dropdown-chevron"></i>
                    </button>
                    <div class="nav-dropdown-content">
                        <a href="{{ route('admin.activity-logs.index') }}" class="dropdown-item {{ Request::routeIs('admin.activity-logs.*') ? 'active' : '' }}"><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</a>
                        @role('super-admin')
                            <a href="{{ route('admin.users.index') }}" class="dropdown-item {{ Request::routeIs('admin.users.*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i> Users</a>
                        @endrole
                    </div>
                </div>
            @endhasanyrole
        </nav>

        <div class="navbar-right">
            @auth
                <div class="user-chip">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="user-meta">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-office">{{ auth()->user()->office ?? 'BDRRMC Office' }}</div>
                    </div>
                </div>
            @endauth

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </button>
            </form>

            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="content">
            @yield('content')
        </div>
    </div>

    @if(session('success') || session('error'))
        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: @json(session('success') ? 'success' : 'error'),
                title: @json(session('success') ?: session('error')),
                showConfirmButton: false,
                timer: @json(session('success') ? 2800 : 3800),
                timerProgressBar: true,
                background: '#0f172a',
                color: '#f8fafc',
                customClass: {
                    popup: 'admin-toast'
                }
            });
        </script>
    @endif

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarCenterMenu');
            if (menu) menu.classList.toggle('open');
        }
        
        function toggleMobileDropdown(event) {
            if (window.innerWidth < 992) {
                event.preventDefault();
                event.stopPropagation();
                const btn = event.currentTarget;
                const dropdown = btn.closest('.nav-dropdown');
                
                // Toggle current dropdown
                const isOpen = dropdown.classList.contains('open');
                
                // Close all dropdowns first
                document.querySelectorAll('.nav-dropdown').forEach(d => {
                    d.classList.remove('open');
                });
                
                if (!isOpen) {
                    dropdown.classList.add('open');
                }
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.nav-dropdown') && !event.target.closest('.mobile-menu-toggle')) {
                document.querySelectorAll('.nav-dropdown').forEach(d => {
                    d.classList.remove('open');
                });
                const menu = document.getElementById('navbarCenterMenu');
                if (menu) menu.classList.remove('open');
            }
        });
    </script>

</body>
</html>
