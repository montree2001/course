<?php
// admin/logout.php
// ทำลาย session และออกจากระบบ

// เริ่ม session
session_start();

// ลบค่าทั้งหมดใน session
$_SESSION = array();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// redirect ไปยังหน้า login
header("Location: index.php");
exit;