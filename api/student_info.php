<?php
// api/student_info.php
// API สำหรับค้นหาข้อมูลนักเรียนจากรหัสนักเรียน

header('Content-Type: application/json; charset=utf-8');

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// ตรวจสอบการส่งข้อมูล
if (!isset($_POST['student_code']) || empty($_POST['student_code'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสนักเรียน'
    ]);
    exit;
}

$student_code = $_POST['student_code'];

try {
    // ค้นหาข้อมูลนักเรียน
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name 
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE s.student_code = :student_code
    ");
    $stmt->execute(['student_code' => $student_code]);
    $student = $stmt->fetch();
    
    if ($student) {
        echo json_encode([
            'success' => true,
            'message' => 'พบข้อมูลนักเรียน',
            'data' => $student
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลนักเรียนจากรหัสที่ระบุ'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการค้นหา: ' . $e->getMessage()
    ]);
}
?>