<?php
// admin/requests/print.php
// ไฟล์สำหรับพิมพ์บันทึกราชการ

session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';

/* แสดง Error */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ไม่พบคำร้องที่ต้องการพิมพ์');
}

$request_id = $_GET['id'];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // ดึงข้อมูลคำร้อง
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               us.student_code, us.name_prefix, us.first_name, us.last_name, 
               us.education_level, us.year, us.major, us.phone_number,
               us.full_name, us.student_type
        FROM course_requests cr
        JOIN unified_students us ON cr.student_id = us.id
        WHERE cr.id = :id
    ");
    $stmt->execute(['id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        die('ไม่พบคำร้องที่ต้องการพิมพ์');
    }
    
    // ดึงรายละเอียดรายวิชา
    $stmt = $pdo->prepare("
        SELECT cri.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credits,
               t.name_prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM course_request_items cri
        JOIN courses c ON cri.course_id = c.id
        JOIN teachers t ON cri.teacher_id = t.id
        WHERE cri.course_request_id = :request_id
        ORDER BY c.course_code
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

// เรียกใช้ MPDF
require_once '../../vendor/autoload.php';

// สร้าง MPDF instance
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'margin_left' => 20,
    'margin_right' => 20,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'default_font' => 'thsarabun',
    'default_font_size' => 16
]);

// เพิ่มฟอนต์ไทย
$fontDir = __DIR__ . '/../../assets/fonts/THSarabun/';
$mpdf->fontDir = [$fontDir];
$mpdf->fontdata['thsarabun'] = [
    'R' => 'THSarabunIT๙.ttf',
    'B' => 'THSarabunIT๙ Bold.ttf',
    'I' => 'THSarabunIT๙ Italic.ttf',
    'BI' => 'THSarabunIT๙ BoldItalic.ttf'
];

// ตั้งค่าข้อมูลเอกสาร
$mpdf->SetTitle('บันทึกข้อความ - คำร้องขอเปิดรายวิชาพิเศษ');
$mpdf->SetAuthor('วิทยาลัยการอาชีพ');
$mpdf->SetCreator('ระบบจัดการคำร้องขอเปิดรายวิชา');

// โหลดค่าตำแหน่งจากไฟล์ JSON
$positions_file = '../../config/pdf_positions.json';
$positions = [];
if (file_exists($positions_file)) {
    $positions = json_decode(file_get_contents($positions_file), true) ?? [];
}

// ค่าเริ่มต้นของตำแหน่งหากไม่มีไฟล์ JSON
$default_positions = [
    'document_number' => ['x' => 40, 'y' => 45],
    'document_date_day' => ['x' => 160, 'y' => 45],
    'document_date_month' => ['x' => 185, 'y' => 45],
    'document_date_year' => ['x' => 220, 'y' => 45],
    'student_name' => ['x' => 85, 'y' => 85],
    'student_code' => ['x' => 170, 'y' => 85],
    'student_year' => ['x' => 90, 'y' => 95],
    'student_major' => ['x' => 140, 'y' => 95],
    'student_phone' => ['x' => 80, 'y' => 105],
    'signature_student' => ['x' => 150, 'y' => 200],
    'approval_checkbox_yes' => ['x' => 40, 'y' => 250],
    'approval_checkbox_no' => ['x' => 40, 'y' => 265],
    'rejection_reason' => ['x' => 120, 'y' => 265]
];

$positions = array_merge($default_positions, $positions);

// เตรียมข้อมูลวันที่
$request_date = new DateTime($request['request_date']);
$thai_day = $request_date->format('d');
$thai_month = getThaiMonth((int)$request_date->format('n'));
$thai_year = (int)$request_date->format('Y') + 543;

// คำนวณสรุปรายวิชา
$total_courses = count($request_items);
$total_theory = array_sum(array_column($request_items, 'theory_hours'));
$total_practice = array_sum(array_column($request_items, 'practice_hours'));
$total_credits = array_sum(array_column($request_items, 'credits'));

// สร้าง HTML Content แบบใช้ตำแหน่งจากไฟล์ JSON
$html = '
<style>
    @page {
        margin: 0;
        size: A4;
    }
    
    body {
        font-family: "thsarabun", sans-serif;
        font-size: 16pt;
        margin: 0;
        padding: 20pt;
        position: relative;
        width: 595pt;
        height: 842pt;
    }
    
    .positioned-element {
        position: absolute;
        font-size: 16pt;
        color: #000;
    }
    
    .static-content {
        position: absolute;
        font-size: 16pt;
        color: #000;
    }
    
    .header {
        position: absolute;
        top: 20pt;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 24pt;
        font-weight: bold;
    }
    
    .checkbox {
        width: 18pt;
        height: 18pt;
        border: 2pt solid #000;
        display: inline-block;
        text-align: center;
        font-weight: bold;
        line-height: 14pt;
        font-size: 14pt;
    }
    
    .checkbox.checked {
        background-color: #000;
        color: #fff;
    }
    
    .courses-table {
        position: absolute;
        top: 300pt;
        left: 40pt;
        width: 515pt;
        border-collapse: collapse;
        border: 1pt solid #000;
    }
    
    .courses-table th,
    .courses-table td {
        border: 1pt solid #000;
        padding: 6pt;
        text-align: center;
        font-size: 12pt;
        vertical-align: middle;
    }
    
    .courses-table th {
        background-color: #e9ecef;
        font-weight: bold;
    }
    
    .courses-table td.left {
        text-align: left;
    }
</style>

<div class="header">บันทึกข้อความ - คำร้องขอเปิดรายวิชาพิเศษ</div>

<!-- Static Labels -->
<div class="static-content" style="top: 70pt; left: 40pt;">ที่: ศธ ๐๕๐๙.๓/</div>
<div class="static-content" style="top: 70pt; left: 400pt;">วันที่:</div>
<div class="static-content" style="top: 90pt; left: 40pt;">เรื่อง: ขออนุญาตเปิดรายวิชาพิเศษ ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '</div>
<div class="static-content" style="top: 110pt; left: 40pt;">เรียน: ผู้อำนวยการวิทยาลัยการอาชีพ</div>

<div class="static-content" style="top: 150pt; left: 40pt;">ข้อมูลผู้ยื่นคำร้อง</div>
<div class="static-content" style="top: 180pt; left: 40pt;">ชื่อ-นามสกุล:</div>
<div class="static-content" style="top: 180pt; left: 320pt;">รหัสนักเรียน:</div>
<div class="static-content" style="top: 200pt; left: 40pt;">ชั้นปี:</div>
<div class="static-content" style="top: 200pt; left: 200pt;">สาขาวิชา:</div>
<div class="static-content" style="top: 220pt; left: 40pt;">เบอร์โทรศัพท์:</div>

<div class="static-content" style="top: 270pt; left: 40pt; font-weight: bold;">รายวิชาที่ขอเปิด</div>

<!-- Dynamic Positioned Elements -->
<div class="positioned-element" style="left: ' . $positions['document_number']['x'] . 'pt; top: ' . $positions['document_number']['y'] . 'pt;">' . $request_id . '</div>

<div class="positioned-element" style="left: ' . $positions['document_date_day']['x'] . 'pt; top: ' . $positions['document_date_day']['y'] . 'pt;">' . $thai_day . '</div>

<div class="positioned-element" style="left: ' . $positions['document_date_month']['x'] . 'pt; top: ' . $positions['document_date_month']['y'] . 'pt;">' . $thai_month . '</div>

<div class="positioned-element" style="left: ' . $positions['document_date_year']['x'] . 'pt; top: ' . $positions['document_date_year']['y'] . 'pt;">พ.ศ. ' . $thai_year . '</div>

<div class="positioned-element" style="left: ' . $positions['student_name']['x'] . 'pt; top: ' . $positions['student_name']['y'] . 'pt;">' . $request['full_name'] . '</div>

<div class="positioned-element" style="left: ' . $positions['student_code']['x'] . 'pt; top: ' . $positions['student_code']['y'] . 'pt;">' . $request['student_code'] . '</div>

<div class="positioned-element" style="left: ' . $positions['student_year']['x'] . 'pt; top: ' . $positions['student_year']['y'] . 'pt;">' . $request['education_level'] . ' ปีที่ ' . $request['year'] . '</div>

<div class="positioned-element" style="left: ' . $positions['student_major']['x'] . 'pt; top: ' . $positions['student_major']['y'] . 'pt;">' . $request['major'] . '</div>

<div class="positioned-element" style="left: ' . $positions['student_phone']['x'] . 'pt; top: ' . $positions['student_phone']['y'] . 'pt;">' . ($request['phone_number'] ?: 'ไม่ระบุ') . '</div>

<!-- Courses Table -->
<table class="courses-table">
    <thead>
        <tr>
            <th style="width: 8%;">ลำดับ</th>
            <th style="width: 15%;">รหัสวิชา</th>
            <th style="width: 35%;">ชื่อรายวิชา</th>
            <th style="width: 10%;">ทฤษฎี</th>
            <th style="width: 10%;">ปฏิบัติ</th>
            <th style="width: 10%;">หน่วยกิต</th>
            <th style="width: 12%;">ครูผู้สอน</th>
        </tr>
    </thead>
    <tbody>';

foreach ($request_items as $index => $item) {
    $html .= '
        <tr>
            <td>' . ($index + 1) . '</td>
            <td>' . $item['course_code'] . '</td>
            <td class="left">' . $item['course_name'] . '</td>
            <td>' . $item['theory_hours'] . '</td>
            <td>' . $item['practice_hours'] . '</td>
            <td>' . $item['credits'] . '</td>
            <td class="left">' . $item['teacher_prefix'] . $item['teacher_first_name'] . ' ' . $item['teacher_last_name'] . '</td>
        </tr>';
}

$html .= '
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <td colspan="2">รวม ' . $total_courses . ' รายวิชา</td>
            <td></td>
            <td>' . $total_theory . '</td>
            <td>' . $total_practice . '</td>
            <td>' . $total_credits . '</td>
            <td></td>
        </tr>
    </tbody>
</table>

<!-- Approval Section -->
<div class="static-content" style="top: 520pt; left: 40pt; font-weight: bold; font-size: 18pt;">ผลการพิจารณา</div>

<div class="static-content" style="top: 550pt; left: 80pt;">อนุมัติให้เปิดรายวิชาพิเศษตามที่ขอ</div>
<div class="positioned-element" style="left: ' . $positions['approval_checkbox_yes']['x'] . 'pt; top: ' . $positions['approval_checkbox_yes']['y'] . 'pt;">
    <span class="checkbox' . ($request['status'] === 'approved' ? ' checked' : '') . '">' . ($request['status'] === 'approved' ? '✓' : '') . '</span>
</div>

<div class="static-content" style="top: 580pt; left: 80pt;">ไม่อนุมัติ เนื่องจาก</div>
<div class="positioned-element" style="left: ' . $positions['approval_checkbox_no']['x'] . 'pt; top: ' . $positions['approval_checkbox_no']['y'] . 'pt;">
    <span class="checkbox' . ($request['status'] === 'rejected' ? ' checked' : '') . '">' . ($request['status'] === 'rejected' ? '✓' : '') . '</span>
</div>';

if ($request['status'] === 'rejected' && !empty($request['rejected_reason'])) {
    $html .= '
<div class="positioned-element" style="left: ' . $positions['rejection_reason']['x'] . 'pt; top: ' . $positions['rejection_reason']['y'] . 'pt; max-width: 200pt;">
    ' . nl2br(htmlspecialchars($request['rejected_reason'])) . '
</div>';
}

$html .= '
<!-- Signature Section -->
<div class="positioned-element" style="left: ' . $positions['signature_student']['x'] . 'pt; top: ' . $positions['signature_student']['y'] . 'pt; text-align: center;">
    <div>ลงชื่อ ................................................</div>
    <div style="margin-top: 8pt;">(' . $request['full_name'] . ')</div>
    <div style="margin-top: 4pt;">ผู้ยื่นคำร้อง</div>
</div>

<div class="positioned-element" style="left: 350pt; top: ' . ($positions['signature_student']['y'] + 50) . 'pt; text-align: center;">
    <div>ลงชื่อ ................................................</div>
    <div style="margin-top: 8pt;">(......................................)</div>
    <div style="margin-top: 4pt;">ผู้อำนวยการวิทยาลัยการอาชีพ</div>
</div>

<!-- Footer -->
<div class="static-content" style="bottom: 20pt; left: 40pt; right: 40pt; text-align: center; font-size: 12pt; color: #666;">
    <div>วิทยาลัยการอาชีพ - ระบบจัดการคำร้องขอเปิดรายวิชาพิเศษ</div>
    <div>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . ' โดย: ' . ($_SESSION['admin_name'] ?? 'ผู้ดูแลระบบ') . '</div>
</div>';

// เขียน HTML ลงใน PDF
$mpdf->WriteHTML($html);

// กำหนดชื่อไฟล์
$filename = 'บันทึกข้อความ_คำร้อง_' . $request_id . '_' . $request['student_code'] . '.pdf';

// ส่งออกไฟล์ PDF
$mpdf->Output($filename, 'I');

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $months[$month] ?? '';
}
?>