<?php
// api/request_details.php
// API สำหรับดึงรายละเอียดคำร้องขอเปิดรายวิชา

header('Content-Type: application/json; charset=utf-8');

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// ตรวจสอบการส่งข้อมูล
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบรหัสคำร้อง'
    ]);
    exit;
}

$request_id = $_GET['id'];

try {
    // ดึงข้อมูลคำร้อง
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               s.student_code, s.prefix, s.first_name, s.last_name, 
               s.level, s.year, s.phone, s.student_id,
               d.department_name
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE cr.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบคำร้องที่ต้องการ'
        ]);
        exit;
    }
    
    // ดึงรายละเอียดรายวิชาที่ขอเปิด
    $stmt = $pdo->prepare("
        SELECT rd.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        WHERE rd.request_id = :request_id
        ORDER BY c.course_code
    ");
    $stmt->execute(['request_id' => $request_id]);
    $courses = $stmt->fetchAll();
    
    // ดึงประวัติการติดตามสถานะ
    $stmt = $pdo->prepare("
        SELECT * FROM status_tracking 
        WHERE request_id = :request_id 
        ORDER BY created_at ASC
    ");
    $stmt->execute(['request_id' => $request_id]);
    $tracking = $stmt->fetchAll();
    
    // แปลงวันที่เป็นภาษาไทย
    $request['request_date_thai'] = dateThaiFormat($request['request_date']);
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'data' => [
            'request' => $request,
            'courses' => $courses,
            'tracking' => $tracking,
            'student' => [
                'student_id' => $request['student_id'],
                'student_code' => $request['student_code'],
                'prefix' => $request['prefix'],
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'level' => $request['level'],
                'year' => $request['year'],
                'phone' => $request['phone'],
                'department_name' => $request['department_name']
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>