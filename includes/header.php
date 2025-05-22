<?php
// admin/includes/header.php
// ส่วนหัวของเว็บส่วนผู้ดูแลระบบ

// ตรวจสอบสถานะการเข้าสู่ระบบ
require_once __DIR__ . '/../check_login.php';

// ตรวจสอบว่ามีตัวแปร $pageTitle หรือไม่
if (!isset($pageTitle)) {
    $pageTitle = 'ผู้ดูแลระบบ';
}

// ตรวจสอบว่ามีตัวแปร $currentPage หรือไม่
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ระบบขอเปิดรายวิชา</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --bs-sidebar-width: 280px;
            --bs-sidebar-collapsed-width: 70px;
        }
        
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .content-wrapper {
            margin-left: var(--bs-sidebar-width);
            transition: margin 0.25s ease-in-out;
        }
        
        .content-wrapper.sidebar-collapsed {
            margin-left: var(--bs-sidebar-collapsed-width);
        }
        
        main {
            min-height: calc(100vh - 56px);
            padding: 1.5rem;
            background-color: #f8f9fa;
        }
        
        .navbar-admin {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--bs-sidebar-width);
            transition: width 0.25s ease-in-out;
            background-color: #343a40;
            color: white;
            z-index: 1030;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: var(--bs-sidebar-collapsed-width);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-brand-icon {
            display: none;
            font-size: 1.5rem;
            text-align: center;
        }
        
        .sidebar.collapsed .sidebar-brand-icon {
            display: block;
        }
        
        .sidebar.collapsed .sidebar-brand-text {
            display: none;
        }
        
        .sidebar-toggle {
            cursor: pointer;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            padding: 0;
            font-size: 1.25rem;
        }
        
        .sidebar-toggle:hover {
            color: white;
        }
        
        .sidebar-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-item:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-item.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #0d6efd;
        }
        
        .sidebar-icon {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar-divider {
            height: 0;
            margin: 0.5rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-heading {
            padding: 0.75rem 1rem 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .sidebar.collapsed .sidebar-heading {
            display: none;
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .sidebar-dropdown-icon {
            margin-left: auto;
            transition: transform 0.2s;
        }
        
        .sidebar-item[aria-expanded="true"] .sidebar-dropdown-icon {
            transform: rotate(90deg);
        }
        
        .sidebar-submenu {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .sidebar-submenu-item {
            padding: 0.6rem 1rem 0.6rem 3.2rem;
            display: block;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-submenu-item:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-submenu-item.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar.collapsed .sidebar-submenu {
            display: none !important;
        }
        
        .sidebar-user {
            padding: 1rem;
            display: flex;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        
        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }
        
        .sidebar-user-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-user-role {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar.collapsed .sidebar-user-info {
            display: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--bs-sidebar-width);
                z-index: 1050;
            }
            
            .sidebar.collapsed {
                width: var(--bs-sidebar-width);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper, .content-wrapper.sidebar-collapsed {
                margin-left: 0;
            }
            
            .navbar-toggler-sidebar {
                display: block;
            }
            
            .sidebar.collapsed .sidebar-brand-icon {
                display: none;
            }
            
            .sidebar.collapsed .sidebar-brand-text {
                display: block;
            }
            
            .sidebar.collapsed .sidebar-text {
                display: block;
            }
            
            .sidebar.collapsed .sidebar-heading {
                display: block;
            }
            
            .sidebar.collapsed .sidebar-submenu {
                display: block !important;
            }
            
            .sidebar.collapsed .sidebar-user-info {
                display: block;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* DataTables Customization */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_info {
            padding-top: 0.85rem;
        }
        
        .dt-buttons {
            margin-bottom: 1rem;
        }
        
        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            box-shadow: none !important;
        }
        
        /* Custom Card Styles */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.03);
            padding: 0.75rem 1.25rem;
        }
        
        /* Custom Breadcrumb Styles */
        .breadcrumb {
            margin-bottom: 1.5rem;
            background-color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Loader */
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #0d6efd;
            border-bottom-color: transparent;
            border-radius: 50%;
            animation: loader 1s linear infinite;
        }
        
        @keyframes loader {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper" id="loader" style="display: none;">
        <div class="loader"></div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand-icon">
                <i class="bi bi-book"></i>
            </div>
            <div class="sidebar-brand-text">
                <h3>ระบบขอเปิดรายวิชา</h3>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>
        
        <!-- Sidebar Menu -->
        <?php include_once 'sidebar.php'; ?>
        
        <!-- Sidebar User Info -->
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                <div class="sidebar-user-role">ผู้ดูแลระบบ</div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper" id="contentWrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-admin">
            <div class="container-fluid">
                <button class="navbar-toggler navbar-toggler-sidebar" type="button" id="sidebarCollapseBtn">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="d-flex align-items-center ms-auto">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../dashboard.php"><i class="bi bi-speedometer2"></i> แดชบอร์ด</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main>