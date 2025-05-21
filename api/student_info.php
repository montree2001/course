<?php
// api/student_info.php
// API สำหรับดึงข้อมูลนักเรียนจากรหัสนักเรียน

header('Content-Type: application/json');

require_once '../config/db_connect.php';
require_once '../config/functions.php';

$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // ตรวจสอบว่ามีการส่งรหัสนักเรียนมาหรือไม่
    if (!isset($_POST['student_code']) || empty($_POST['student_code'])) {
        throw new Exception('กรุณาระบุรหัสนักเรียน');
    }
    
    $student_code = $_POST['student_code'];
    
    // ค้นหาข้อมูลนักเรียน
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name 
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE s.student_code = :student_code
    ");
    $stmt->execute(['student_code' => $student_code]);
    $student = $stmt->fetch();
    
    // ตรวจสอบว่าพบข้อมูลหรือไม่
    if ($student) {
        $response['success'] = true;
        $response['data'] = $student;
        $response['message'] = 'ดึงข้อมูลนักเรียนสำเร็จ';
    } else {
        $response['message'] = 'ไม่พบข้อมูลนักเรียนจากรหัสที่ระบุ';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);