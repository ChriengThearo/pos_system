<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Oracle Commerce')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg: #f6f8fa;
            --surface: #ffffff;
            --surface-soft: #eef3f8;
            --text: #101820;
            --muted: #5a6b7f;
            --primary: #0055a5;
            --accent: #e36414;
            --success: #1d7a4f;
            --danger: #a32222;
            --border: #d6dfeb;
            --shadow: 0 18px 44px rgba(16, 24, 32, 0.1);
            --appbar: #d9dfe5;
            --sidebar-bg: #ffffff;
            --sidebar-text: #111b4d;
            --sidebar-muted: #7f89a8;
            --sidebar-active: #eef3f8;
            --sidebar-accent: #049461;
            --sidebar-icon: #a6aac9;
            --appbar-height: 56px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--text);
            font-family: "Manrope", sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(circle at 15% 0%, rgba(0, 85, 165, 0.17), transparent 32%),
                radial-gradient(circle at 85% 12%, rgba(227, 100, 20, 0.17), transparent 28%),
                linear-gradient(165deg, #f9fbfd, #ecf1f7 58%, #e8eef6 100%);
        }

        a { color: inherit; text-decoration: none; }

        .shell {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .layout {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            min-height: calc(100vh - var(--appbar-height));
        }

        .appbar {
            height: var(--appbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            background: var(--appbar);
            border-bottom: 1px solid #c7d1db;
        }

        .appbar .brand {
            font-size: 1rem;
        }

        .appbar-search {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #c7d1db;
            border-radius: 6px;
            overflow: hidden;
        }

        .appbar-search input {
            border: 0;
            padding: 7px 10px;
            width: 220px;
            font-size: .9rem;
            outline: none;
        }

        .appbar-search button {
            border: 0;
            padding: 7px 10px;
            background: #ffffff;
            cursor: pointer;
            color: #2f3b45;
            font-weight: 700;
            border-left: 1px solid #c7d1db;
        }

        .sidebar {
            position: sticky;
            top: var(--appbar-height);
            z-index: 100;
            align-self: start;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            border-radius: 0;
            box-shadow: none;
            border-right: 1px solid #e8edf3;
            padding: 0;
            min-height: calc(100vh - var(--appbar-height));
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: .02em;
        }

        .brand-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(140deg, var(--primary), var(--accent));
            box-shadow: 0 0 0 8px rgba(0, 85, 165, 0.08);
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .nav {
            display: grid;
            gap: 0;
            border-top: 1px solid #edf0f4;
        }

        .nav-section {
            margin-top: 12px;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--sidebar-muted);
        }

        .nav-link {
            font-size: .9rem;
            color: var(--sidebar-text);
            padding: 16px 24px;
            border-radius: 0;
            transition: all .2s ease;
            border: 0;
            border-bottom: 1px solid #dbe2ea;
            display: block;
            min-height: 51px;
            background: #eef2f6;
            font-weight: 700;
        }

        .nav-link:hover {
            color: var(--sidebar-text);
            background: #e5ebf2;
        }

        .nav-link.active {
            color: var(--sidebar-text);
            background: #dfe6ee;
        }

        .nav-ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            color: var(--sidebar-icon);
            font-size: 1.45rem;
            flex: 0 0 24px;
        }

        .nav-badge {
            margin-left: auto;
            background: #ba2d2d;
            color: #fff;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: .7rem;
            font-weight: 700;
            line-height: 1;
        }

        .sidebar-toggle {
            border: none;
            background: var(--sidebar-accent);
            color: #ffffff;
            font-size: 1.45rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 18px;
            width: 100%;
            height: 56px;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            transition: background-color .18s ease;
        }

        .sidebar-toggle:hover {
            background: #057c54;
        }

        .toggle-label {
            font-size: .82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .layout {
            transition: grid-template-columns .18s ease;
        }

        .layout.sidebar-collapsed {
            grid-template-columns: 74px minmax(0, 1fr);
        }

        .sidebar {
            width: 100%;
            overflow: visible;
            transition: padding .18s ease;
        }

        .sidebar.collapsed {
            align-items: stretch;
        }

        .nav-item {
            position: relative;
        }

        .nav-trigger {
            display: flex;
            align-items: center;
            gap: 18px;
            width: 100%;
            font: inherit;
            text-align: left;
            border: 1px solid transparent;
            background: #ffffff;
            color: var(--sidebar-text);
            cursor: pointer;
            border-radius: 0;
            padding: 0 24px;
            font-size: 1rem;
            font-weight: 800;
            transition: all .2s ease;
            min-height: 62px;
            border-bottom-color: #edf0f4;
        }

        .nav-trigger:hover {
            color: var(--sidebar-text);
            background: #f8fafc;
        }

        .nav-trigger.active {
            color: var(--sidebar-text);
            background: #ffffff;
        }

        .nav-item.open > .nav-trigger {
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
        }

        .nav-trigger.active .nav-ico {
            color: var(--sidebar-icon);
        }

        .nav-label {
            white-space: nowrap;
        }

        .nav-caret {
            margin-left: auto;
            color: var(--sidebar-text);
            font-size: .85rem;
            transition: transform .18s ease;
        }

        .nav-item.open > .nav-trigger .nav-caret {
            transform: rotate(180deg);
        }

        .nav-flyout-title {
            display: none;
            font-weight: 700;
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--sidebar-muted);
            padding: 4px 10px 8px;
        }

        .nav-children {
            display: none;
        }

        .sidebar:not(.collapsed) .nav-item.open > .nav-children {
            display: grid;
            gap: 0;
            padding: 0;
            border-bottom: 1px solid #edf0f4;
            background: #eef2f6;
        }

        .sidebar.collapsed .sidebar-toggle {
            justify-content: center;
            padding: 0;
        }

        .sidebar.collapsed .toggle-label {
            display: none;
        }

        .sidebar.collapsed .nav-item {
            display: flex;
            justify-content: center;
            margin-bottom: 0;
        }

        .sidebar.collapsed .nav-trigger {
            width: 100%;
            height: 62px;
            justify-content: center;
            padding: 0;
            border-radius: 0;
        }

        .sidebar.collapsed .nav-label {
            display: none;
        }

        .sidebar.collapsed .nav-caret {
            display: none;
        }

        .sidebar.collapsed .nav-item .nav-children {
            position: absolute;
            left: 100%;
            top: 0;
            margin-left: 0;
            min-width: 210px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0 10px 10px 0;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            padding: 0;
            z-index: 200;
            grid-gap: 4px;
        }

        .sidebar.collapsed .nav-item:hover .nav-children {
            display: grid;
        }

        .sidebar.collapsed .nav-flyout-title {
            display: block;
        }

        .sidebar.collapsed .nav-section {
            display: none;
        }

        .sidebar.collapsed .staff-meta {
            display: none;
        }

        .sidebar.collapsed .logout-btn {
            width: 100%;
            min-height: 50px;
            border-radius: 0;
            padding: 0;
        }

        .sidebar.collapsed .logout-btn span {
            display: none;
        }

        .sidebar.collapsed .logout-btn i {
            font-size: 1.35rem;
        }

        .staff-meta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid #edf0f4;
            background: #f8fafc;
            color: var(--sidebar-text);
            font-size: .8rem;
            line-height: 1.25;
        }

        .staff-meta strong {
            display: block;
            font-size: .84rem;
            color: #1b2a40;
        }

        .logout-btn {
            border: 1px solid #edf0f4;
            background: #ffffff;
            color: var(--sidebar-text);
            border-radius: 9px;
            font-size: .8rem;
            font-weight: 700;
            padding: 8px 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 14px;
            padding: 18px 20px 40px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 18px;
        }

        .page-hero {
            position: relative;
            padding: 22px 24px;
            border-radius: 20px;
            background: linear-gradient(120deg, rgba(0, 85, 165, 0.2), rgba(46, 139, 255, 0.12), rgba(227, 100, 20, 0.18));
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            animation: hero-rise .15s ease;
        }

        .page-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            top: -150px;
            right: -110px;
            background: radial-gradient(circle, rgba(0, 85, 165, 0.35), transparent 65%);
            filter: blur(10px);
            opacity: 0.7;
        }

        .hero-kicker {
            margin: 0 0 6px;
            font-size: .72rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: #38506b;
            font-weight: 700;
        }

        .hero-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            align-items: center;
        }

        .chip-strong {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(0, 0, 0, 0.08);
            color: #1b2a40;
            font-weight: 700;
        }

        .headline {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            letter-spacing: .01em;
        }

        .subtle {
            color: var(--muted);
            font-size: .95rem;
            line-height: 1.55;
        }

        .grid {
            display: grid;
            gap: 14px;
        }

        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

        .search-card {
            display: grid;
            gap: 12px;
        }

        .search-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
        }

        .tab-switch {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .tab-btn {
            border: 0;
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: .86rem;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all .2s ease;
        }

        .tab-btn.active {
            color: #fff;
            background: linear-gradient(130deg, var(--primary), #2e8bff);
            box-shadow: 0 10px 20px rgba(0, 85, 165, 0.25);
        }

        .stat {
            border: 1px solid var(--border);
            background: var(--surface-soft);
            border-radius: 14px;
            padding: 14px;
        }

        .stat .label {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .stat .value {
            font-size: 1.45rem;
            font-weight: 800;
            font-family: "Space Grotesk", sans-serif;
        }

        .flash {
            border-radius: 12px;
            padding: 11px 13px;
            border: 1px solid transparent;
            font-size: .92rem;
        }

        .flash.success {
            color: #0f5132;
            background: #d1e7dd;
            border-color: #badbcc;
        }

        .flash.error {
            color: #842029;
            background: #f8d7da;
            border-color: #f5c2c7;
        }

        .flash.toast {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            animation: toast-pop 0.18s ease-out;
        }

        .flash.toast .toast-ico {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #1d7a4f;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        @keyframes toast-pop {
            0% { opacity: 0; transform: translateY(-6px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        .error-list {
            margin: 0;
            padding-left: 18px;
            color: var(--danger);
            font-size: .9rem;
        }

        .btn {
            border: 0;
            border-radius: 11px;
            padding: 9px 13px;
            font-family: inherit;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            transition: background-color .12s, opacity .12s;
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: .6;
        }

        .btn-primary {
            color: #fff;
            background: var(--accent);
        }

        .btn-primary:hover { opacity: .88; }

        .btn-muted {
            color: var(--text);
            background: #edf3fa;
            border: 1px solid var(--border);
        }

        .btn-danger {
            color: #fff;
            background: linear-gradient(140deg, #ba2d2d, #932323);
        }

        .chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: .75rem;
            padding: 4px 10px;
            border: 1px solid var(--border);
            background: #f5f8fc;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .table-wrap.soft {
            background: #fff;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 680px;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #e7edf5;
            text-align: left;
            font-size: .9rem;
        }

        th {
            background: #f3f7fb;
            color: #33485f;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        tr:last-child td { border-bottom: 0; }

        .employee-table tbody tr:hover {
            background: #f6f9ff;
        }

        .employee-photo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
        }

        .field-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        label {
            display: block;
            font-size: .82rem;
            color: #3c4f64;
            margin-bottom: 6px;
            font-weight: 700;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid #cbd8e6;
            border-radius: 10px;
            padding: 9px 10px;
            font-size: .92rem;
            background: #fff;
            color: var(--text);
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 85, 165, 0.14);
        }

        textarea { min-height: 88px; resize: vertical; }

        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pager {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pager .pager-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pager .pager-links {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .pager .pager-btn,
        .pager .pager-page,
        .pager .pager-ellipsis {
            padding: 6px 10px;
            font-size: .86rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .pager .pager-btn,
        .pager .pager-page {
            color: var(--text);
            transition: all .15s ease;
        }

        .pager .pager-btn:hover,
        .pager .pager-page:hover {
            background: #edf3fa;
        }

        .pager .pager-btn.disabled,
        .pager .pager-page.disabled {
            color: var(--muted);
            background: #f3f7fb;
            cursor: not-allowed;
        }

        .pager .pager-page.active {
            background: var(--primary);
            color: #fff;
            border-color: transparent;
        }

        .pager .pager-ellipsis {
            color: var(--muted);
            border-style: dashed;
            background: #f8fafc;
        }

        .pager .pager-meta {
            font-size: .86rem;
            color: var(--muted);
        }

        .stock-alert-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(7, 18, 33, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 9999;
            opacity: 0;
            animation: stock-alert-fade 0.15s ease forwards;
        }

        .stock-alert-card {
            width: min(460px, 100%);
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #ffffff;
            box-shadow: var(--shadow);
            padding: 18px;
            animation: stock-alert-rise 0.15s ease;
        }

        .stock-alert-title {
            margin: 0 0 6px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.05rem;
            color: #0f2a44;
        }

        .stock-alert-body {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .stock-alert-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        @keyframes stock-alert-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes stock-alert-rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes hero-rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 980px) {
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 200px minmax(0, 1fr); }
        }

        @media (max-width: 760px) {
            .shell { width: 100%; }
            .layout { grid-template-columns: 1fr; }
            .layout.sidebar-collapsed { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .brand { justify-content: flex-start; }
            .appbar-search input { width: 140px; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .field-grid { grid-template-columns: 1fr; }
            .headline { font-size: 1.35rem; }
            .page-hero { flex-direction: column; align-items: flex-start; }
            .search-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $storeName = 'Electronic Store';
    $storeLogoPath = 'images/store-logo.png';
    $storeLogoExists = file_exists(public_path($storeLogoPath));
    $staffUser = \App\Support\StaffAuth::user();
    $canDashboardManage = \App\Support\StaffAuth::can('dashboard.manage');
    $canDashboardRead = \App\Support\StaffAuth::can('dashboard.read');
    $canShop = \App\Support\StaffAuth::can('shop.read');
    $canCheckout = \App\Support\StaffAuth::can('checkout.process');
    $canOrders = \App\Support\StaffAuth::can('orders.read');
    $canTotalSales = \App\Support\StaffAuth::can('total-sales.read');
    $canInvoices = \App\Support\StaffAuth::can('invoices.read');
    $canReturns = \App\Support\StaffAuth::can('returns.read');
    $canPurchases = \App\Support\StaffAuth::can('purchases.read');
    $canEmployees = \App\Support\StaffAuth::can('employees.read');
    $canJobs = \App\Support\StaffAuth::can('jobs.read');
    $canClients = \App\Support\StaffAuth::can('clients.read');
    $canClientDebts = \App\Support\StaffAuth::can('client-depts.read');
    $canCurrencies = \App\Support\StaffAuth::can('currencies.read');
    $canProducts = \App\Support\StaffAuth::can('products.read');
    $canManageProducts = \App\Support\StaffAuth::can('products.manage');
    $canStockStatus = \App\Support\StaffAuth::can('stock-status.read');
    $canFutureStock = \App\Support\StaffAuth::can('future-stock.read');
    $canUsers = \App\Support\StaffAuth::can('users.read');
    $canRoles = \App\Support\StaffAuth::can('roles.read');
    $canPermissions = \App\Support\StaffAuth::can('permissions.read');
    $canSystemAudit = \App\Support\StaffAuth::can('system.audit');
    $dashboardUrl = $canDashboardManage ? route('admin.dashboard') : route('store.home');
    $dashboardActive = $canDashboardManage ? request()->routeIs('admin.*') : request()->routeIs('store.home');
    $inProcessCount = isset($inProcessCount) ? (int) $inProcessCount : null;
    if ($inProcessCount === null && $canOrders) {
        try {
            $inProcessCount = (int) \Illuminate\Support\Facades\DB::connection('oracle')
                ->table('INVOICES')
                ->where('INVOICE_STATUS', '=', 'In Process')
                ->count();
        } catch (\Throwable) {
            $inProcessCount = 0;
        }
    }
    $inProcessCount = $inProcessCount ?? 0;

    $clientDeptCount = isset($clientDeptCount) ? (int) $clientDeptCount : null;
    if ($clientDeptCount === null && $canClientDebts) {
        try {
            $clientDeptCount = (int) \Illuminate\Support\Facades\DB::connection('oracle')
                ->table('INVOICES')
                ->where('INVOICE_STATUS', '=', 'In Debt')
                ->count();
        } catch (\Throwable) {
            $clientDeptCount = 0;
        }
    }
    $clientDeptCount = $clientDeptCount ?? 0;

    $underStockCount = isset($underStockCount) ? (int) $underStockCount : null;
    if ($underStockCount === null && $canStockStatus) {
        $fallbackUnderStockCount = (int) session()->get('understock_last_known', 0);
        try {
            $underStockCount = (int) \Illuminate\Support\Facades\DB::connection('oracle')
                ->table('PRODUCTS')
                ->whereRaw("UPPER(NVL(STATUS, 'UNKNOWN')) = ?", ['UNDERSTOCK'])
                ->count();
            session()->put('understock_last_known', $underStockCount);
        } catch (\Throwable) {
            $underStockCount = $fallbackUnderStockCount;
        }
    }
    $underStockCount = $underStockCount ?? 0;
    $lastUnderStockCount = (int) session()->get('understock_alert_last', 0);
    $showUnderStockAlert = false;
    if (! $canStockStatus || $underStockCount <= 0) {
        session()->forget('understock_alert_last');
    } else {
        if ($underStockCount > $lastUnderStockCount) {
            $showUnderStockAlert = true;
        }
        session()->put('understock_alert_last', $underStockCount);
    }
    try {
        \App\Support\StockAlertNotifier::notifyFromPopupContext((int) $underStockCount, (bool) $showUnderStockAlert);
    } catch (\Throwable) {
        // Keep UI alert flow resilient even if Telegram check fails.
    }

    $navGroups = [
        [
            'key' => 'home',
            'icon' => 'bi-house-door-fill',
            'label' => 'Home',
            'items' => [
                [
                    'show' => ($canDashboardManage || $canDashboardRead) && ! \App\Support\StaffAuth::hasRole('CASHIER'),
                    'url' => $dashboardUrl,
                    'label' => $canDashboardManage ? 'Admin Dashboard' : 'Dashboard',
                    'active' => $dashboardActive,
                ],
                [
                    'show' => $canShop,
                    'url' => $canShop ? route('store.catalog') : null,
                    'label' => 'Shop',
                    'active' => request()->routeIs('store.catalog'),
                ],
            ],
        ],
        [
            'key' => 'sales',
            'icon' => 'bi-receipt-cutoff',
            'label' => 'Sales',
            'items' => [
                [
                    'show' => $canInvoices,
                    'url' => $canInvoices ? route('invoices.index') : null,
                    'label' => 'Invoices',
                    'active' => request()->routeIs('invoices.*'),
                ],
                [
                    'show' => $canClientDebts,
                    'url' => $canClientDebts ? route('client-depts.index') : null,
                    'label' => 'Client Debts',
                    'active' => request()->routeIs('client-depts.*'),
                    'badge' => $clientDeptCount > 0 ? $clientDeptCount : null,
                ],
                [
                    'show' => $canReturns,
                    'url' => $canReturns ? route('returns.index') : null,
                    'label' => 'Return/Refunds',
                    'active' => request()->routeIs('returns.*'),
                ],
                [
                    'show' => $canClients,
                    'url' => $canClients ? route('clients.index') : null,
                    'label' => 'Clients',
                    'active' => request()->routeIs('clients.*') || request()->routeIs('client-types.*'),
                ],
                [
                    'show' => $canCheckout,
                    'url' => $canCheckout ? route('store.cart') : null,
                    'label' => 'Checkout',
                    'active' => request()->routeIs('store.cart*') || request()->routeIs('store.checkout*'),
                ],
                [
                    'show' => $canTotalSales,
                    'url' => $canTotalSales ? route('total-sales.index') : null,
                    'label' => 'Total Sales Orders',
                    'active' => request()->routeIs('total-sales.*'),
                ],
                [
                    'show' => $canOrders,
                    'url' => $canOrders ? route('store.orders') : null,
                    'label' => 'Orders',
                    'active' => request()->routeIs('store.orders*'),
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'icon' => 'bi-layers-fill',
            'label' => 'Inventory',
            'items' => [
                [
                    'show' => $canProducts,
                    'url' => $canProducts ? route('products.index') : null,
                    'label' => 'Products',
                    'active' => request()->routeIs('products.index') || request()->routeIs('products.store') || request()->routeIs('products.update') || request()->routeIs('product-types.*') || request()->routeIs('alert-stocks.*'),
                ],
                [
                    'show' => $canStockStatus,
                    'url' => $canStockStatus ? route('products.status') : null,
                    'label' => 'Product Status',
                    'active' => request()->routeIs('products.status'),
                    'badge' => $underStockCount > 0 ? $underStockCount : null,
                ],
                [
                    'show' => $canFutureStock,
                    'url' => $canFutureStock ? route('products.status.future') : null,
                    'label' => 'Analyst Future',
                    'active' => request()->routeIs('products.status.future'),
                ],
                [
                    'show' => $canSystemAudit,
                    'url' => $canSystemAudit ? route('store.deep-check') : null,
                    'label' => 'Deep Check',
                    'active' => request()->routeIs('store.deep-check'),
                ],
            ],
        ],
        [
            'key' => 'purchases',
            'icon' => 'bi-truck',
            'label' => 'Purchases',
            'items' => [
                [
                    'show' => $canPurchases,
                    'url' => $canPurchases ? route('purchases.index') : null,
                    'label' => 'Purchases',
                    'active' => request()->routeIs('purchases.index') || request()->routeIs('purchases.history') || request()->routeIs('purchases.store') || request()->routeIs('purchases.items.*'),
                ],
                [
                    'show' => $canManageProducts,
                    'url' => $canManageProducts ? route('china-store.index') : null,
                    'label' => 'China Store',
                    'active' => request()->routeIs('china-store.*'),
                ],
            ],
        ],
        [
            'key' => 'employees',
            'icon' => 'bi-person-badge-fill',
            'label' => 'Employees',
            'items' => [
                [
                    'show' => $canEmployees,
                    'url' => $canEmployees ? route('employees.index') : null,
                    'label' => 'Employees',
                    'active' => request()->routeIs('employees.*'),
                ],
                [
                    'show' => $canJobs,
                    'url' => $canJobs ? route('jobs.index') : null,
                    'label' => 'Jobs',
                    'active' => request()->routeIs('jobs.*'),
                ],
            ],
        ],
        [
            'key' => 'setting',
            'icon' => 'bi-tools',
            'label' => 'Setting',
            'items' => [
                [
                    'show' => $canUsers,
                    'url' => $canUsers ? route('admin.rbac.users.index') : null,
                    'label' => 'Users',
                    'active' => request()->routeIs('admin.rbac.users.*'),
                ],
                [
                    'show' => $canRoles,
                    'url' => $canRoles ? route('admin.rbac.roles.index') : null,
                    'label' => 'Roles',
                    'active' => request()->routeIs('admin.rbac.roles.*'),
                ],
                [
                    'show' => $canPermissions,
                    'url' => $canPermissions ? route('admin.rbac.permissions.index') : null,
                    'label' => 'Permissions',
                    'active' => request()->routeIs('admin.rbac.permissions.*'),
                ],
                [
                    'show' => $canCurrencies,
                    'url' => $canCurrencies ? route('currencies.index') : null,
                    'label' => 'Currencies',
                    'active' => request()->routeIs('currencies.*'),
                ],
            ],
        ],
    ];

    foreach ($navGroups as &$navGroup) {
        $navGroup['items'] = array_values(array_filter($navGroup['items'], fn ($item) => $item['show']));
        $navGroup['hasActive'] = collect($navGroup['items'])->contains('active', true);
    }
    unset($navGroup);
    $navGroups = array_values(array_filter($navGroups, fn ($group) => count($group['items']) > 0));
@endphp
<div class="shell">
    <header class="appbar">
        <div style="display: flex; align-items: center;">
            <a href="{{ route('dashboard.entry') }}" class="brand">
                @if($storeLogoExists)
                    <img src="{{ asset($storeLogoPath) }}" alt="{{ $storeName }} logo" class="brand-logo">
                @else
                    <span class="brand-dot"></span>
                @endif
                <span>{{ $storeName }}</span>
            </a>
        </div>
        <!-- search removed -->
    </header>
    <div class="layout">
        <aside class="sidebar">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Minimize navigation" aria-expanded="true">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <span class="toggle-label">Minimize</span>
            </button>
            <nav class="nav">
                @foreach ($navGroups as $group)
                    <div class="nav-item {{ $group['hasActive'] ? 'open' : '' }}">
                        <button type="button" class="nav-trigger {{ $group['hasActive'] ? 'active' : '' }}" title="{{ $group['label'] }}" aria-label="{{ $group['label'] }}">
                            <i class="bi {{ $group['icon'] }} nav-ico" aria-hidden="true"></i>
                            <span class="nav-label">{{ $group['label'] }}</span>
                            <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
                        </button>
                        <div class="nav-children">
                            <div class="nav-flyout-title">{{ $group['label'] }}</div>
                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}" style="display: flex; align-items: center; gap: 8px;">
                                    {{ $item['label'] }}
                                    @if(!empty($item['badge']))
                                        <span class="nav-badge">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div style="margin-top: auto;">
                @if($staffUser)
                    <div class="staff-meta" style="margin-top: 14px;">
                        <div>
                            <strong>{{ $staffUser['employee_name'] }}</strong>
                            {{ $staffUser['job_title'] }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('staff.logout') }}" style="margin-top: 10px;">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <main class="content">
        @if (session('success'))
            <div class="flash success toast">
                <span class="toast-ico" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" focusable="false">
                        <path fill="currentColor" d="M9.55 17.55L4.5 12.5l1.4-1.4 3.65 3.65 8.05-8.05 1.4 1.4z"/>
                    </svg>
                </span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="card">
                <h3 style="margin-top: 0;">Please fix these issues</h3>
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            @yield('content')
        </main>
    </div>
</div>
@if($showUnderStockAlert)
    <div class="stock-alert-backdrop" id="stock-alert-modal" role="dialog" aria-modal="true" aria-labelledby="stock-alert-title">
        <div class="stock-alert-card">
            <h2 class="stock-alert-title" id="stock-alert-title">Stock Alert</h2>
            <p class="stock-alert-body">{{ number_format($underStockCount) }} products are running low on stock.</p>
            <div class="stock-alert-actions">
                <button type="button" class="btn btn-muted" data-stock-alert-close>OK</button>
                <a href="{{ route('products.status') }}#under-stock-list" class="btn btn-primary">Manage</a>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const modal = document.getElementById('stock-alert-modal');
            if (!modal) return;
            const closeModal = () => modal.remove();
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            modal.querySelectorAll('[data-stock-alert-close]').forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });
        })();
    </script>
@endif
<script>
    (function () {
        const layout = document.querySelector('.layout');
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        if (!layout || !sidebar || !toggleBtn) return;

        const STORAGE_KEY = 'pos-sidebar-collapsed';

        function applyState(collapsed) {
            sidebar.classList.toggle('collapsed', collapsed);
            layout.classList.toggle('sidebar-collapsed', collapsed);
            toggleBtn.setAttribute('aria-expanded', String(!collapsed));
            toggleBtn.setAttribute('aria-label', collapsed ? 'Open navigation' : 'Minimize navigation');

            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            }
        }

        applyState(localStorage.getItem(STORAGE_KEY) === '1');

        toggleBtn.addEventListener('click', () => {
            const collapsed = !sidebar.classList.contains('collapsed');
            applyState(collapsed);
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        });

        sidebar.querySelectorAll('.nav-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                if (sidebar.classList.contains('collapsed')) return;
                const item = trigger.closest('.nav-item');
                const isOpen = item.classList.contains('open');
                sidebar.querySelectorAll('.nav-item.open').forEach((el) => el.classList.remove('open'));
                if (!isOpen) item.classList.add('open');
            });
        });
    })();
</script>
</body>
</html>
