<?php
// admin/requests/print.php
// ไฟล์สำหรับพิมพ์บันทึกราชการโดยใช้ PDF Template + ข้อมูล

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

/* Error */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ไม่พบคำร้องที่ต้องการพิมพ์');
}

$request_id = $_GET['id'];
$request = null;
$request_details = [];

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
    $request_details = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

// เรียกใช้ FPDI + MPDF
require_once '../../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

// กำหนดเส้นทางไฟล์ font แบบ absolute path
$fontPath = __DIR__ . '/../../assets/fonts/';
define('FPDF_FONTPATH', $fontPath);

// สร้าง PDF instance
$pdf = new Fpdi();

// เพิ่ม font โดยใช้ชื่อไฟล์ที่ถูกต้อง
$pdf->AddFont('THSarabunIT9', '', 'THSarabunIT๙.ttf');
$pdf->AddFont('THSarabunIT9', 'B', 'THSarabunIT๙ Bold.ttf');
$pdf->AddFont('THSarabunIT9', 'I', 'THSarabunIT๙ Italic.ttf');
$pdf->AddFont('THSarabunIT9', 'BI', 'THSarabunIT๙ BoldItalic.ttf');

// กำหนดเส้นทางไฟล์ template
$templatePath = '../../assets/templates/report.pdf';

// ตรวจสอบว่าไฟล์ template มีอยู่หรือไม่
if (!file_exists($templatePath)) {
    die('ไม่พบไฟล์ template: ' . $templatePath);
}

// โหลด template
$pageCount = $pdf->setSourceFile($templatePath);

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    // เพิ่มหน้าใหม่
    $pdf->AddPage();
    
    // ใช้หน้าจาก template
    $templateId = $pdf->importPage($pageNo);
    $pdf->useTemplate($templateId);
    
    // ตั้งค่าฟอนต์ THSarabun
    
$pdf->SetFont('THSarabunIT9', '', 16);
    $pdf->SetTextColor(0, 0, 0);
    
    if ($pageNo == 1) { // หน้าแรก - ใส่ข้อมูลหลัก
        
        // รหัสคำร้อง (ที่)
        $pdf->SetXY(40, 45); // ปรับตำแหน่งตามต้องการ
        $pdf->Write(0, $request_id);
        
        // วันที่
        $request_date = new DateTime($request['request_date']);
        $day = $request_date->format('d');
        $month = getThaiMonth((int)$request_date->format('n'));
        $year = (int)$request_date->format('Y') + 543;
        
        $pdf->SetXY(160, 45); // วันที่
        $pdf->Write(0, $day);
        
        $pdf->SetXY(185, 45); // เดือน
        $pdf->Write(0, $month);
        
        $pdf->SetXY(220, 45); // ปี
        $pdf->Write(0, $year);
        
        // ข้อมูลนักเรียน
        $pdf->SetXY(85, 85); // ชื่อ-นามสกุล
        $pdf->Write(0, $request['first_name'] . ' ' . $request['last_name']);
        
        $pdf->SetXY(170, 85); // รหัสนักเรียน
        $pdf->Write(0, $request['student_code']);
        
        $pdf->SetXY(90, 95); // ชั้นปี
        $pdf->Write(0, $request['year']);
        
        $pdf->SetXY(140, 95); // สาขาวิชา
        $pdf->Write(0, $request['department_name'] ?? 'ไม่ระบุ');
        
        $pdf->SetXY(80, 105); // เบอร์โทร
        $pdf->Write(0, $request['phone'] ?? 'ไม่ระบุ');
        
        // รายวิชาในตาราง
        $startY = 130; // ตำแหน่ง Y เริ่มต้นของตาราง
        $rowHeight = 8; // ความสูงของแต่ละแถว
        
        foreach ($request_details as $index => $detail) {
            $currentY = $startY + ($index * $rowHeight);
            
            // ลำดับ
            $pdf->SetXY(25, $currentY);
            $pdf->Write(0, $index + 1);
            
            // รหัสวิชา
            $pdf->SetXY(35, $currentY);
            $pdf->Write(0, $detail['course_code']);
            
            // ชื่อรายวิชา
            $pdf->SetXY(65, $currentY);
            $pdf->Write(0, substr($detail['course_name'], 0, 30)); // จำกัดความยาว
            
            // ทฤษฎี
            $pdf->SetXY(135, $currentY);
            $pdf->Write(0, $detail['theory_hours']);
            
            // ปฏิบัติ
            $pdf->SetXY(150, $currentY);
            $pdf->Write(0, $detail['practice_hours']);
            
            // หน่วยกิต
            $pdf->SetXY(165, $currentY);
            $pdf->Write(0, $detail['credit_hours']);
            
            // ชื่อครู
            $pdf->SetXY(175, $currentY);
            $pdf->Write(0, $detail['teacher_prefix'] . $detail['teacher_first_name'] . ' ' . $detail['teacher_last_name']);
        }
        
        // แถวสรุป
        $summaryY = $startY + (8 * $rowHeight) + 5;
        $total_courses = count($request_details);
        $total_theory = array_sum(array_column($request_details, 'theory_hours'));
        $total_practice = array_sum(array_column($request_details, 'practice_hours'));
        $total_credit = array_sum(array_column($request_details, 'credit_hours'));
        
        $pdf->SetXY(65, $summaryY);
        $pdf->Write(0, 'รวม ' . $total_courses . ' วิชา');
        
        $pdf->SetXY(135, $summaryY);
        $pdf->Write(0, $total_theory);
        
        $pdf->SetXY(150, $summaryY);
        $pdf->Write(0, $total_practice);
        
        $pdf->SetXY(165, $summaryY);
        $pdf->Write(0, $total_credit);
        
        // ลายเซ็นผู้ยื่นคำร้อง
        $pdf->SetXY(150, 200); // ปรับตำแหน่งตามต้องการ
        $pdf->Write(0, $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']);
        
        // สถานะการอนุมัติ
        if ($request['status'] === 'อนุมัติ') {
            // ทำเครื่องหมายถูกที่ช่อง "อนุมัติ"
            $pdf->SetXY(40, 250); // ปรับตำแหน่งตามต้องการ
            $pdf->SetFont('thsarabun', 'B', 18);
            $pdf->Write(0, '✓');
            
        } elseif ($request['status'] === 'ไม่อนุมัติ') {
            // ทำเครื่องหมายถูกที่ช่อง "ไม่อนุมัติ"
            $pdf->SetXY(40, 265); // ปรับตำแหน่งตามต้องการ
            $pdf->SetFont('thsarabun', 'B', 18);
            $pdf->Write(0, '✓');
            
            // เหตุผลการไม่อนุมัติ
            if ($request['rejection_reason']) {
                $pdf->SetXY(120, 265);
                $pdf->SetFont('thsarabun', '', 14);
                $pdf->Write(0, $request['rejection_reason']);
            }
        }
        
        // ข้อมูลเพิ่มเติม (วันที่พิมพ์, ผู้พิมพ์)
        $pdf->SetXY(20, 280);
        $pdf->SetFont('thsarabun', '', 12);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Write(0, 'พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . ' โดย: ' . $_SESSION['admin_name']);
    }
}

// ฟังก์ชันสำหรับแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thai_months[$month] ?? '';
}

// กำหนดชื่อไฟล์
$filename = 'บันทึกข้อความ_คำร้อง_' . $request_id . '_' . $request['student_code'] . '.pdf';

// ส่งออกไฟล์ PDF
$pdf->Output('I', $filename); // 'I' = แสดงในเบราว์เซอร์, 'D' = ดาวน์โหลด
?>