<?php
// admin/teachers/delete.php
// ไฟล์สำหรับลบข้อมูลครู

// เรียกใช้ไฟล์ config และ functions
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบสถานะการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่ามีการระบุ ID ครู
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_msg'] = 'ไม่พบข้อมูลครู';
    header('Location: index.php');
    exit;
}

$teacher_id = $_GET['id'];

try {
    // ตรวจสอบว่าครูมีการสอนรายวิชาอยู่หรือไม่
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM request_details WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $course_count = $stmt->fetch()['count'];
    
    if ($course_count > 0) {
        // ถ้ามีการสอนรายวิชา ไม่อนุญาตให้ลบ
        $_SESSION['error_msg'] = 'ไม่สามารถลบข้อมูลครูได้ เนื่องจากมีการสอนรายวิชาอยู่ กรุณาเปลี่ยนครูประจำวิชาก่อนลบข้อมูล';
        header('Location: index.php');
        exit;
    }
    
    // ดึงข้อมูลครูเพื่อใช้แสดงข้อความยืนยัน
    $stmt = $pdo->prepare("SELECT prefix, first_name, last_name FROM teachers WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        $_SESSION['error_msg'] = 'ไม่พบข้อมูลครู';
        header('Location: index.php');
        exit;
    }
    
    // ลบข้อมูลครู
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    
    $_SESSION['success_msg'] = 'ลบข้อมูลครู ' . $teacher['prefix'] . $teacher['first_name'] . ' ' . $teacher['last_name'] . ' เรียบร้อยแล้ว';
} catch (PDOException $e) {
    $_SESSION['error_msg'] = 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage();
}

// Redirect กลับไปหน้ารายการครู
header('Location: index.php');
exit;
?>