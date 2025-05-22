<?php
// admin/requests/print_pdftk.php
// ไฟล์สำหรับกรอกข้อมูลใน PDF Form ด้วย PDFtk

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ไม่พบคำร้องที่ต้องการพิมพ์');
}

$request_id = $_GET['id'];

try {
    // ดึงข้อมูลคำร้อง
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               s.student_code, s.prefix, s.first_name, s.last_name, 
               s.level, s.year, s.phone,
               d.department_name
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE cr.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        die('ไม่พบคำร้องที่ต้องการพิมพ์');
    }
    
    // ดึงรายละเอียดรายวิชา
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
    $request_details = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

// เตรียมข้อมูลสำหรับกรอกใน PDF Form
$formData = [];

// ข้อมูลพื้นฐาน
$request_date = new DateTime($request['request_date']);
$formData['request_id'] = $request_id;
$formData['day'] = $request_date->format('d');
$formData['month'] = getThaiMonth((int)$request_date->format('n'));
$formData['year'] = (int)$request_date->format('Y') + 543;
$formData['semester'] = $request['semester'];
$formData['academic_year'] = $request['academic_year'];

// ข้อมูลนักเรียน
$formData['student_name'] = $request['first_name'] . ' ' . $request['last_name'];
$formData['student_code'] = $request['student_code'];
$formData['student_level'] = $request['level'];
$formData['student_year'] = $request['year'];
$formData['department'] = $request['department_name'] ?? 'ไม่ระบุ';
$formData['phone'] = $request['phone'] ?? 'ไม่ระบุ';

// ข้อมูลรายวิชา
foreach ($request_details as $index => $detail) {
    $i = $index + 1;
    $formData["course_{$i}_code"] = $detail['course_code'];
    $formData["course_{$i}_name"] = $detail['course_name'];
    $formData["course_{$i}_theory"] = $detail['theory_hours'];
    $formData["course_{$i}_practice"] = $detail['practice_hours'];
    $formData["course_{$i}_credit"] = $detail['credit_hours'];
    $formData["course_{$i}_teacher"] = $detail['teacher_prefix'] . $detail['teacher_first_name'] . ' ' . $detail['teacher_last_name'];
}

// คำนวณสรุป
$formData['total_courses'] = count($request_details);
$formData['total_theory'] = array_sum(array_column($request_details, 'theory_hours'));
$formData['total_practice'] = array_sum(array_column($request_details, 'practice_hours'));
$formData['total_credit'] = array_sum(array_column($request_details, 'credit_hours'));

// สถานะการอนุมัติ
$formData['status_approved'] = ($request['status'] === 'อนุมัติ') ? 'Yes' : 'Off';
$formData['status_rejected'] = ($request['status'] === 'ไม่อนุมัติ') ? 'Yes' : 'Off';
$formData['rejection_reason'] = $request['rejection_reason'] ?? '';

// สร้างไฟล์ FDF (Form Data Format)
$fdfContent = createFDF($formData);

// เส้นทางไฟล์
$templatePath = '../../assets/templates/report.pdf';
$fdfPath = sys_get_temp_dir() . '/form_data_' . $request_id . '.fdf';
$outputPath = sys_get_temp_dir() . '/filled_form_' . $request_id . '.pdf';

// เขียนไฟล์ FDF
file_put_contents($fdfPath, $fdfContent);

// ใช้ PDFtk กรอกข้อมูล
$command = "pdftk {$templatePath} fill_form {$fdfPath} output {$outputPath} flatten";
$result = shell_exec($command);

// ตรวจสอบว่าไฟล์ถูกสร้างหรือไม่
if (file_exists($outputPath)) {
    // ส่งไฟล์ PDF ไปยังเบราว์เซอร์
    $filename = 'บันทึกข้อความ_คำร้อง_' . $request_id . '_' . $request['student_code'] . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outputPath));
    
    readfile($outputPath);
    
    // ลบไฟล์ชั่วคราว
    unlink($fdfPath);
    unlink($outputPath);
    
} else {
    die('เกิดข้อผิดพลาดในการสร้างไฟล์ PDF');
}

// ฟังก์ชันสร้างไฟล์ FDF
function createFDF($data) {
    $fdf = "%FDF-1.2\n";
    $fdf .= "1 0 obj\n";
    $fdf .= "<<\n";
    $fdf .= "/FDF\n";
    $fdf .= "<<\n";
    $fdf .= "/Fields [\n";
    
    foreach ($data as $key => $value) {
        $fdf .= "<<\n";
        $fdf .= "/T ({$key})\n";
        $fdf .= "/V (" . str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $value) . ")\n";
        $fdf .= ">>\n";
    }
    
    $fdf .= "]\n";
    $fdf .= ">>\n";
    $fdf .= ">>\n";
    $fdf .= "endobj\n";
    $fdf .= "trailer\n";
    $fdf .= "<<\n";
    $fdf .= "/Root 1 0 R\n";
    $fdf .= ">>\n";
    $fdf .= "%%EOF\n";
    
    return $fdf;
}

// ฟังก์ชันแปลงเดือน
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thai_months[$month] ?? '';
}
?>