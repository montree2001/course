<?php
// admin/students/delete.php
// ไฟล์สำหรับลบข้อมูลนักเรียน

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการลบ';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$student_id = $_GET['id'];

try {
    // ตรวจสอบว่านักเรียนมีคำร้องขอเปิดรายวิชาหรือไม่
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_requests WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);
    $request_count = $stmt->fetchColumn();
    
    if ($request_count > 0) {
        // ถ้ามีคำร้อง ให้แสดงข้อความแจ้งเตือนและยกเลิกการลบ
        $_SESSION['message'] = 'ไม่สามารถลบข้อมูลนักเรียนได้ เนื่องจากมีประวัติการยื่นคำร้องขอเปิดรายวิชา (' . $request_count . ' รายการ)';
        $_SESSION['message_type'] = 'warning';
        header('Location: index.php');
        exit;
    }
    
    // ดึงข้อมูลนักเรียนเพื่อเก็บชื่อไว้แสดงข้อความหลังลบ
    $stmt = $pdo->prepare("SELECT prefix, first_name, last_name FROM students WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        // ถ้าไม่พบข้อมูลนักเรียน
        $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการลบ';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
    
    // ลบข้อมูลนักเรียน
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);
    
    // แสดงข้อความสำเร็จ
    $_SESSION['message'] = 'ลบข้อมูลนักเรียน ' . $student['prefix'] . $student['first_name'] . ' ' . $student['last_name'] . ' เรียบร้อยแล้ว';
    $_SESSION['message_type'] = 'success';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาด
    $_SESSION['message'] = 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// กลับไปยังหน้ารายการนักเรียน
header('Location: index.php');
exit;
?>