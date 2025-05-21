<?php
// admin/check_login.php
// ไฟล์สำหรับตรวจสอบสถานะการเข้าสู่ระบบของผู้ดูแลระบบ

// ตรวจสอบว่ามีการเริ่ม session หรือไม่
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/functions.php';

// ตรวจสอบว่ามีการ login หรือไม่
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // ไม่ได้ login ให้ redirect ไปที่หน้า login
    header('Location: ' . getBaseUrl() . 'admin/index.php');
    exit;
}

// ฟังก์ชันสำหรับรับ base URL ของเว็บไซต์
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    
    // ตรวจสอบว่าอยู่ใน subfolder หรือไม่
    $pathParts = explode('/admin', $path);
    $basePath = $pathParts[0];
    
    if (empty($basePath)) {
        $basePath = '/';
    } else {
        $basePath = $basePath . '/';
    }
    
    return $protocol . $host . $basePath;
}

// ฟังก์ชันตรวจสอบระดับสิทธิ์ (หากมีการใช้งานในอนาคต)
function checkPermission($requiredPermission) {
    // ตรวจสอบว่ามีสิทธิ์หรือไม่
    if (!isset($_SESSION['admin_permissions'])) {
        return false;
    }
    
    return in_array($requiredPermission, $_SESSION['admin_permissions']);
}