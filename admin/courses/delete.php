<?php
// admin/courses/delete.php
// ไฟล์สำหรับลบข้อมูลรายวิชา

// เรียกใช้ไฟล์ตรวจสอบการเข้าสู่ระบบ
require_once '../check_login.php';
require_once '../../config/db_connect.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // บันทึกข้อความแจ้งเตือน
    $_SESSION['error_message'] = 'ไม่พบรายวิชาที่ต้องการลบ';
    
    // Redirect ไปยังหน้ารายการรายวิชา
    header('Location: index.php');
    exit;
}

$course_id = $_GET['id'];

try {
    // ตรวจสอบว่ารหัสรายวิชานี้ถูกใช้งานอยู่ในตาราง request_details หรือไม่
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS count FROM request_details WHERE course_id = :course_id
    ");
    $stmt->execute(['course_id' => $course_id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // รายวิชานี้ถูกใช้งานอยู่ ไม่สามารถลบได้
        $_SESSION['error_message'] = 'ไม่สามารถลบรายวิชานี้ได้ เนื่องจากมีการใช้งานในคำร้องขอเปิดรายวิชา';
        header('Location: index.php');
        exit;
    }
    
    // ดึงข้อมูลรายวิชาก่อนลบเพื่อเก็บข้อมูลสำหรับการแจ้งเตือน
    $stmt = $pdo->prepare("SELECT course_code, course_name FROM courses WHERE course_id = :course_id");
    $stmt->execute(['course_id' => $course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        // ไม่พบข้อมูลรายวิชา
        $_SESSION['error_message'] = 'ไม่พบข้อมูลรายวิชาที่ต้องการลบ';
        header('Location: index.php');
        exit;
    }
    
    // เริ่มต้น Transaction
    $pdo->beginTransaction();
    
    // ลบข้อมูลรายวิชา
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = :course_id");
    $stmt->execute(['course_id' => $course_id]);
    
    // Commit Transaction
    $pdo->commit();
    
    // บันทึกข้อความแจ้งเตือนสำเร็จ
    $_SESSION['success_message'] = 'ลบรายวิชา ' . $course['course_code'] . ' (' . $course['course_name'] . ') เรียบร้อยแล้ว';
    
} catch (PDOException $e) {
    // Rollback Transaction ในกรณีที่เกิดข้อผิดพลาด
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // บันทึกข้อความแจ้งเตือนข้อผิดพลาด
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage();
}

// Redirect กลับไปยังหน้ารายการรายวิชา
header('Location: index.php');
exit;