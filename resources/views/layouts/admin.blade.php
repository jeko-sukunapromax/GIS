<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoPortal Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-base: #090d16;
            --bg-panel: rgba(15, 23, 42, 0.7);
            --bg-card: rgba(30, 41, 59, 0.45);
            --border-color: rgba(148, 163, 184, 0.12);
            --text-main: #cbd5e1;
            --text-muted: #94a3b8;
            --text-heading: #f8fafc;
            --accent-blue: #38bdf8;
            --accent-blue-hover: #0ea5e9;
            --accent-blue-glow: rgba(56, 189, 248, 0.25);
            --sidebar-bg: rgba(15, 23, 42, 0.85);
            --sidebar-text: #94a3b8;
            --sidebar-active: rgba(30, 41, 59, 0.6);
            --glass-blur: blur(16px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg-base); 
            background-image: radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 40%);
            color: var(--text-main); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background-color: var(--sidebar-bg); 
            color: var(--sidebar-text); 
            display: flex; 
            flex-direction: column; 
            border-right: 1px solid var(--border-color);
            backdrop-filter: var(--glass-blur);
        }
        .sidebar-header { 
            padding: 20px; 
            border-bottom: 1px solid var(--border-color); 
            font-family: 'Outfit', sans-serif;
            font-size: 18px; 
            font-weight: 700; 
            color: var(--text-heading); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .sidebar-header i {
            color: var(--accent-blue);
            text-shadow: 0 0 10px var(--accent-blue-glow);
        }
        .sidebar-menu { padding: 20px 0; flex: 1; overflow-y: auto; }
        .menu-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 24px; 
            color: var(--sidebar-text); 
            text-decoration: none; 
            transition: all 0.2s ease; 
            font-size: 14px;
            font-weight: 500;
        }
        .menu-item:hover, .menu-item.active { 
            background-color: var(--sidebar-active); 
            color: white; 
            border-left: 3px solid var(--accent-blue); 
            text-shadow: 0 0 5px rgba(255,255,255,0.2);
        }

        /* Main Content Wrapper */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { 
            height: 64px; 
            background: var(--bg-panel); 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 24px; 
            backdrop-filter: var(--glass-blur);
        }
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
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
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
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15); 
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
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-earth-asia"></i> GeoPortal Admin
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('admin.barangays.index') }}" class="menu-item {{ request()->routeIs('admin.barangays.*') ? 'active' : '' }}"><i class="fa-solid fa-map-location-dot"></i> Barangays</a>
            <a href="{{ route('admin.features.index') }}" class="menu-item {{ request()->routeIs('admin.features.*') ? 'active' : '' }}"><i class="fa-solid fa-layer-group"></i> Map Editor</a>
            <a href="{{ route('admin.layer-types.index') }}" class="menu-item {{ request()->routeIs('admin.layer-types.*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i> Layer Settings</a>
            <a href="/" class="menu-item" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Map</a>
        </div>
    </div>

    <div class="main-wrapper">
        <div class="topbar">
            <div></div>
            <div style="font-weight: 600; font-size: 14px; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></div>
                BDRRMC / GIS Admin
            </div>
        </div>
        
        <div class="content">
            @if(session('success'))
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif
            
            @yield('content')
        </div>
    </div>

</body>
</html>
