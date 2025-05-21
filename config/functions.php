<?php
// config/functions.php
// ไฟล์รวมฟังก์ชันหลักของระบบ

// ฟังก์ชันตรวจสอบว่ามีการล็อกอินหรือไม่
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// ฟังก์ชันบังคับให้ล็อกอินก่อนเข้าถึงหน้า
function requireLogin() {
    if (!isLoggedIn()) {
        // บันทึกหน้าที่ผู้ใช้พยายามเข้าถึง
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        // ส่งไปที่หน้าล็อกอิน
        header('Location: /course_request_system/admin/index.php');
        exit;
    }
}

// ฟังก์ชันแปลงวันที่เป็นรูปแบบไทย
function dateThaiFormat($date) {
    $thai_months = [
        '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม',
        'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน',
        'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    $date_parts = explode('-', $date);
    
    if (count($date_parts) !== 3) {
        return $date;
    }
    
    $year = (int)$date_parts[0] + 543; // แปลงเป็น พ.ศ.
    $month = (int)$date_parts[1];
    $day = (int)$date_parts[2];
    
    return $day . ' ' . $thai_months[$month] . ' ' . $year;
}

// ฟังก์ชันแปลงเวลาเป็นรูปแบบไทย
function timeThaiFormat($time) {
    $time_parts = explode(':', $time);
    
    if (count($time_parts) < 2) {
        return $time;
    }
    
    $hour = (int)$time_parts[0];
    $minute = (int)$time_parts[1];
    
    return sprintf('%02d:%02d น.', $hour, $minute);
}

// ฟังก์ชันหาข้อมูลนักเรียนจากรหัสนักเรียน
function getStudentByCode($pdo, $student_code) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_code = :student_code");
    $stmt->execute(['student_code' => $student_code]);
    return $stmt->fetch();
}

// ฟังก์ชันหาข้อมูลรายวิชาจากรหัสวิชา
function getCourseByCode($pdo, $course_code) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = :course_code");
    $stmt->execute(['course_code' => $course_code]);
    return $stmt->fetch();
}

// ฟังก์ชันหาข้อมูลคำร้องขอเปิดรายวิชา
function getCourseRequestById($pdo, $request_id) {
    $stmt = $pdo->prepare("
        SELECT cr.*, s.prefix, s.first_name, s.last_name, s.level, s.year, s.student_code, d.department_name
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        JOIN departments d ON s.department_id = d.department_id
        WHERE cr.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    return $stmt->fetch();
}

// ฟังก์ชันหารายละเอียดคำร้อง (รายวิชาที่ขอเปิด)
function getRequestDetails($pdo, $request_id) {
    $stmt = $pdo->prepare("
        SELECT rd.*, c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.prefix, t.first_name, t.last_name
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        WHERE rd.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    return $stmt->fetchAll();
}

// ฟังก์ชันอัปเดตสถานะคำร้อง
function updateRequestStatus($pdo, $request_id, $status_field, $status_value, $comment, $updated_by) {
    try {
        $pdo->beginTransaction();
        
        // อัปเดตสถานะในตาราง course_requests
        $stmt = $pdo->prepare("
            UPDATE course_requests 
            SET $status_field = :status_value, 
                {$status_field}_comment = :comment,
                updated_at = NOW()
            WHERE request_id = :request_id
        ");
        
        $stmt->execute([
            'status_value' => $status_value,
            'comment' => $comment,
            'request_id' => $request_id
        ]);
        
        // อัปเดตการติดตามสถานะ
        $status_name = str_replace('_approval', '', $status_field);
        $status_text = $status_value ? 'อนุมัติ' : 'ไม่อนุมัติ';
        $status_message = "คำร้องได้รับการ$status_text โดย $status_name";
        
        $stmt = $pdo->prepare("
            INSERT INTO status_tracking (request_id, status, comment, updated_by)
            VALUES (:request_id, :status, :comment, :updated_by)
        ");
        
        $stmt->execute([
            'request_id' => $request_id,
            'status' => $status_message,
            'comment' => $comment,
            'updated_by' => $updated_by
        ]);
        
        // ถ้าผู้อำนวยการอนุมัติ ให้อัปเดตสถานะรวมเป็น "อนุมัติ"
        if ($status_field === 'director_approval' && $status_value) {
            $stmt = $pdo->prepare("
                UPDATE course_requests 
                SET status = 'อนุมัติ',
                    updated_at = NOW()
                WHERE request_id = :request_id
            ");
            
            $stmt->execute(['request_id' => $request_id]);
            
            // บันทึกการติดตามสถานะ
            $stmt = $pdo->prepare("
                INSERT INTO status_tracking (request_id, status, comment, updated_by)
                VALUES (:request_id, :status, :comment, :updated_by)
            ");
            
            $stmt->execute([
                'request_id' => $request_id,
                'status' => 'คำร้องได้รับการอนุมัติเรียบร้อยแล้ว',
                'comment' => 'ขั้นตอนทั้งหมดเสร็จสิ้น สามารถดาวน์โหลดตารางเรียนได้',
                'updated_by' => $updated_by
            ]);
        }
        
        // ถ้าไม่อนุมัติในขั้นตอนใดก็ตาม ให้อัปเดตสถานะรวมเป็น "ไม่อนุมัติ"
        if (!$status_value) {
            $stmt = $pdo->prepare("
                UPDATE course_requests 
                SET status = 'ไม่อนุมัติ',
                    updated_at = NOW()
                WHERE request_id = :request_id
            ");
            
            $stmt->execute(['request_id' => $request_id]);
            
            // บันทึกการติดตามสถานะ
            $stmt = $pdo->prepare("
                INSERT INTO status_tracking (request_id, status, comment, updated_by)
                VALUES (:request_id, :status, :comment, :updated_by)
            ");
            
            $stmt->execute([
                'request_id' => $request_id,
                'status' => 'คำร้องไม่ได้รับการอนุมัติ',
                'comment' => 'คำร้องถูกปฏิเสธในขั้นตอน ' . $status_name,
                'updated_by' => $updated_by
            ]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Update Request Status Error: ' . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันอ่านประวัติการติดตามสถานะ
function getStatusHistory($pdo, $request_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM status_tracking
        WHERE request_id = :request_id
        ORDER BY created_at
    ");
    $stmt->execute(['request_id' => $request_id]);
    return $stmt->fetchAll();
}

// ฟังก์ชันสรุปจำนวนนักเรียนที่ขอเปิดรายวิชา
function getCourseSummary($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.course_id, c.course_code, c.course_name, 
               COUNT(DISTINCT rd.request_id) as request_count
        FROM courses c
        JOIN request_details rd ON c.course_id = rd.course_id
        JOIN course_requests cr ON rd.request_id = cr.request_id
        GROUP BY c.course_id
        ORDER BY request_count DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// ฟังก์ชันแสดงความผิดพลาด
function showError($message) {
    return '<div class="alert alert-danger mt-3" role="alert">' . $message . '</div>';
}

// ฟังก์ชันแสดงข้อความสำเร็จ
function showSuccess($message) {
    return '<div class="alert alert-success mt-3" role="alert">' . $message . '</div>';
}

// ฟังก์ชันสำหรับการเชื่อมต่อกับ MPDF
function initMPDF() {
    // ตั้งค่า MPDF
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9,
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/../assets/fonts',
        ]),
        'fontdata' => $fontData + [
            'thsarabun' => [
                'R' => 'THSarabunNew.ttf',
                'B' => 'THSarabunNew Bold.ttf',
                'I' => 'THSarabunNew Italic.ttf',
                'BI' => 'THSarabunNew BoldItalic.ttf'
            ]
        ],
        'default_font' => 'thsarabun'
    ]);
    
    $mpdf->SetTitle('วิทยาลัยการอาชีพปราสาท - ระบบขอเปิดรายวิชา');
    $mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
    
    return $mpdf;
}