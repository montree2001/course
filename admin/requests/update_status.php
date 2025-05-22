<?php
// admin/requests/update_status.php
// ไฟล์สำหรับอัปเดตสถานะคำร้องขอเปิดรายวิชา

header('Content-Type: application/json; charset=utf-8');

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือ GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'การเข้าถึงไม่ถูกต้อง'
    ]);
    exit;
}

// รับข้อมูลจากฟอร์ม (รองรับทั้ง POST และ GET)
$request_id = $_REQUEST['request_id'] ?? null;
$new_status = $_REQUEST['new_status'] ?? null;
$comment = $_REQUEST['comment'] ?? '';
$rejection_reason = $_REQUEST['rejection_reason'] ?? '';

// ตรวจสอบข้อมูลที่จำเป็น
if (empty($request_id) || empty($new_status)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกข้อมูลที่จำเป็น: รหัสคำร้อง=' . $request_id . ', สถานะ=' . $new_status
    ]);
    exit;
}

// ตรวจสอบว่าสถานะที่เลือกถูกต้อง
$allowed_statuses = ['อนุมัติ', 'ไม่อนุมัติ'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'สถานะที่เลือกไม่ถูกต้อง: ' . $new_status
    ]);
    exit;
}

// ถ้าเลือกไม่อนุมัติต้องมีเหตุผล
if ($new_status === 'ไม่อนุมัติ' && empty($rejection_reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกเหตุผลที่ไม่อนุมัติ'
    ]);
    exit;
}

try {
    // เริ่ม Transaction
    $pdo->beginTransaction();
    
    // ตรวจสอบว่าคำร้องมีอยู่จริงและยังเป็นสถานะรอดำเนินการ
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               s.prefix, s.first_name, s.last_name, s.student_code
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        WHERE cr.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        throw new Exception('ไม่พบคำร้องที่ต้องการอัปเดต รหัสคำร้อง: ' . $request_id);
    }
    
    // ตรวจสอบสถานะปัจจุบัน
    if ($request['status'] !== 'รอดำเนินการ') {
        throw new Exception('คำร้องนี้ได้ถูกดำเนินการแล้ว สถานะปัจจุบัน: ' . $request['status']);
    }
    
    // อัปเดตสถานะคำร้อง
    $update_data = [
        'status' => $new_status,
        'request_id' => $request_id
    ];
    
    $sql = "UPDATE course_requests SET status = :status, updated_at = NOW()";
    
    // ถ้าไม่อนุมัติให้เพิ่มเหตุผล
    if ($new_status === 'ไม่อนุมัติ') {
        $sql .= ", rejection_reason = :rejection_reason";
        $update_data['rejection_reason'] = $rejection_reason;
    }
    
    $sql .= " WHERE request_id = :request_id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($update_data);
    
    if (!$result) {
        throw new Exception('ไม่สามารถอัปเดตสถานะคำร้องได้');
    }
    
    // บันทึกประวัติการติดตามสถานะ
    $status_message = '';
    if ($new_status === 'อนุมัติ') {
        $status_message = 'คำร้องได้รับการอนุมัติเรียบร้อยแล้ว';
        
        // อัปเดตการอนุมัติในขั้นตอนต่างๆ
        $stmt = $pdo->prepare("
            UPDATE course_requests SET 
                advisor_approval = 1,
                department_head_approval = 1,
                curriculum_head_approval = 1,
                academic_deputy_approval = 1,
                director_approval = 1
            WHERE request_id = :request_id
        ");
        $stmt->execute(['request_id' => $request_id]);
        
    } else {
        $status_message = 'คำร้องไม่ได้รับการอนุมัติ';
    }
    
    $tracking_comment = $comment;
    if ($new_status === 'ไม่อนุมัติ' && $rejection_reason) {
        $tracking_comment .= ($comment ? "\n" : '') . "เหตุผล: " . $rejection_reason;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO status_tracking (request_id, status, comment, updated_by, created_at)
        VALUES (:request_id, :status, :comment, :updated_by, NOW())
    ");
    
    $stmt->execute([
        'request_id' => $request_id,
        'status' => $status_message,
        'comment' => $tracking_comment,
        'updated_by' => $_SESSION['admin_name'] ?? 'ผู้ดูแลระบบ'
    ]);
    
    // หากอนุมัติ ให้สร้างแจ้งเตือนสำหรับการจัดตารางเรียน
    if ($new_status === 'อนุมัติ') {
        // บันทึกข้อความเพิ่มเติมในระบบติดตาม
        $stmt = $pdo->prepare("
            INSERT INTO status_tracking (request_id, status, comment, updated_by, created_at)
            VALUES (:request_id, :status, :comment, :updated_by, NOW())
        ");
        
        $stmt->execute([
            'request_id' => $request_id,
            'status' => 'รอการจัดตารางเรียน',
            'comment' => 'กรุณาดำเนินการจัดตารางเรียนสำหรับรายวิชาที่ได้รับการอนุมัติ',
            'updated_by' => 'ระบบ'
        ]);
    }
    
    // Commit Transaction
    $pdo->commit();
    
    // ส่งผลลัพธ์กลับ
    $response = [
        'success' => true,
        'message' => 'อัปเดตสถานะคำร้องเรียบร้อยแล้ว',
        'data' => [
            'request_id' => $request_id,
            'new_status' => $new_status,
            'student_name' => $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback Transaction ในกรณีที่เกิดข้อผิดพลาด
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'request_id' => $request_id,
            'new_status' => $new_status,
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
} catch (PDOException $e) {
    // Rollback Transaction ในกรณีที่เกิดข้อผิดพลาดฐานข้อมูล
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()
    ]);
}
?>