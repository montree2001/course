<?php
// admin/requests/print.php
// ไฟล์สำหรับพิมพ์บันทึกราชการเป็น PDF

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
/* แสดงผล Error */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// เรียกใช้ MPDF
require_once '../../vendor/autoload.php';

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

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'thsarabun'
]);

$mpdf->fontdata['thsarabun'] = [
    'R' => 'THSarabunIT๙.ttf',
    'B' => 'THSarabunIT๙ Bold.ttf',
    'I' => 'THSarabunIT๙ Italic.ttf',
    'BI' => 'THSarabunIT๙ BoldItalic.ttf'
];

$mpdf->SetDefaultFont('thsarabun');


$mpdf->default_font = 'thsarabun';




// ตั้งค่าข้อมูลเอกสาร
$mpdf->SetTitle('บันทึกข้อความขอเปิดรายวิชาภาคเรียนพิเศษ - คำร้อง #' . $request_id);
$mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
$mpdf->SetCreator('ระบบขอเปิดรายวิชา');

// สร้างเนื้อหา HTML
$html = '
<style>
    body {
        font-family: "thsarabun", sans-serif;
        font-size: 16pt;
        line-height: 1.4;
    }
    
    .header {
        text-align: center;
        margin-bottom: 20pt;
    }
    
    .logo {
        width: 80pt;
        height: 80pt;
        margin-bottom: 10pt;
    }
    
    .header h1 {
        font-size: 18pt;
        font-weight: bold;
        margin: 0;
        padding: 0;
    }
    
    .document-title {
        text-align: center;
        font-size: 18pt;
        font-weight: bold;
        margin: 20pt 0;
    }
    
    .form-section {
        margin-bottom: 15pt;
    }
    
    .form-row {
        margin-bottom: 8pt;
    }
    
    .form-label {
        display: inline-block;
        font-weight: bold;
    }
    
    .form-value {
        display: inline;
    }
    
    .underline {
        border-bottom: 1pt solid black;
        display: inline-block;
        min-width: 100pt;
        padding-bottom: 2pt;
        text-align: center;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10pt 0;
    }
    
    table, th, td {
        border: 1pt solid black;
    }
    
    th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
        padding: 8pt 4pt;
        font-size: 14pt;
    }
    
    td {
        padding: 6pt 4pt;
        vertical-align: top;
        font-size: 14pt;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-right {
        text-align: right;
    }
    
    .signature-section {
        margin-top: 30pt;
    }
    
    .signature-box {
        display: inline-block;
        width: 200pt;
        text-align: center;
        margin: 10pt;
        vertical-align: top;
    }
    
    .signature-line {
        border-bottom: 1pt solid black;
        height: 40pt;
        margin-bottom: 5pt;
    }
    
    .approval-section {
        margin-top: 20pt;
        border: 1pt solid black;
        padding: 10pt;
    }
    
    .approval-title {
        font-weight: bold;
        text-align: center;
        margin-bottom: 10pt;
    }
    
    .approval-item {
        margin-bottom: 15pt;
    }
    
    .status-' . strtolower($request['status']) . ' {
        color: ' . ($request['status'] === 'อนุมัติ' ? 'green' : ($request['status'] === 'ไม่อนุมัติ' ? 'red' : 'orange')) . ';
        font-weight: bold;
    }
</style>

<div class="header">
    <h1>บันทึกข้อความ</h1>
</div>

<div class="form-section">
    <div class="form-row">
        <span class="form-label">ส่วนราชการ</span>
        <span class="underline">วิทยาลัยการอาชีพปราสาท</span>
    </div>
    
    <div class="form-row" style="margin-top: 15pt;">
        <span class="form-label">ที่</span>
        <span class="underline">' . $request_id . '</span>
        <span style="margin-left: 100pt;">วันที่</span>
        <span class="underline">' . date('d', strtotime($request['request_date'])) . '</span>
        <span>เดือน</span>
        <span class="underline">' . getThaiMonth(date('n', strtotime($request['request_date']))) . '</span>
        <span>พ.ศ.</span>
        <span class="underline">' . (date('Y', strtotime($request['request_date'])) + 543) . '</span>
    </div>
</div>

<div class="form-section">
    <div class="form-row">
        <span class="form-label">เรื่อง</span>
        <span class="underline">คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ (เรียนเพิ่ม/เรียนซ้ำ) ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '</span>
    </div>
</div>

<div class="form-section">
    <div class="form-row">
        <span class="form-label">เรียน</span>
        <span class="underline">ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</span>
    </div>
</div>

<div class="form-section" style="margin-top: 20pt;">
    <div class="form-row">
        <span>ข้าพเจ้า (' . $request['prefix'] . ')</span>
        <span class="underline">' . $request['first_name'] . ' ' . $request['last_name'] . '</span>
        <span>รหัสประจำตัว</span>
        <span class="underline">' . $request['student_code'] . '</span>
    </div>
    
    <div class="form-row" style="margin-top: 10pt;">
        <span>ระดับชั้น ' . $request['level'] . ' ชั้นปีที่</span>
        <span class="underline">' . $request['year'] . '</span>
        <span>สาขาวิชา</span>
        <span class="underline">' . ($request['department_name'] ?? 'ไม่ระบุ') . '</span>
    </div>
    
    <div class="form-row" style="margin-top: 10pt;">
        <span>เบอร์โทรศัพท์ที่ติดต่อได้</span>
        <span class="underline">' . ($request['phone'] ?? 'ไม่ระบุ') . '</span>
        <span>มีความประสงค์ขอให้เปิดรายวิชา ดังนี้</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="8%">ที่</th>
            <th width="15%">รหัสวิชา</th>
            <th width="35%">ชื่อรายวิชา</th>
            <th width="8%">ทฤษฎี</th>
            <th width="8%">ปฏิบัติ</th>
            <th width="8%">หน่วยกิต</th>
            <th width="8%">ชั่วโมง</th>
            <th width="20%">ชื่อครูประจำรายวิชา</th>
        </tr>
    </thead>
    <tbody>';

$total_credit_hours = 0;
$total_hours = 0;

foreach ($request_details as $index => $detail) {
    $total_credit_hours += $detail['credit_hours'];
    $total_hours += ($detail['theory_hours'] + $detail['practice_hours']);
    
    $html .= '
        <tr>
            <td class="text-center">' . ($index + 1) . '</td>
            <td class="text-center">' . $detail['course_code'] . '</td>
            <td>' . $detail['course_name'] . '</td>
            <td class="text-center">' . $detail['theory_hours'] . '</td>
            <td class="text-center">' . $detail['practice_hours'] . '</td>
            <td class="text-center">' . $detail['credit_hours'] . '</td>
            <td class="text-center">' . ($detail['theory_hours'] + $detail['practice_hours']) . '</td>
            <td class="text-center">' . $detail['teacher_prefix'] . $detail['teacher_first_name'] . ' ' . $detail['teacher_last_name'] . '</td>
        </tr>';
}

// เพิ่มแถว "รวม"
$html .= '
        <tr style="font-weight: bold; background-color: #f0f0f0;">
            <td colspan="5" class="text-center">รวม ' . count($request_details) . ' วิชา</td>
            <td class="text-center">' . $total_credit_hours . '</td>
            <td class="text-center">' . $total_hours . '</td>
            <td></td>
        </tr>
    </tbody>
</table>

<div class="form-section" style="margin-top: 20pt;">
    <p>จึงเรียนมาเพื่อโปรดพิจารณา</p>
    
    <div class="text-right" style="margin-top: 40pt;">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ผู้ยื่นคำร้อง</div>
            <div>(' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . ')</div>
            <div>นักเรียน นักศึกษา</div>
        </div>
    </div>
</div>

<div class="approval-section">
    <div class="approval-title">การพิจารณาและสั่งการ</div>
    
    <div class="approval-item">
        <div style="display: inline-block; width: 45%; vertical-align: top;">
            <div><strong>1) เรียน ผู้อำนวยการ</strong></div>
            <div>เพื่อโปรดพิจารณา</div>
            <br>
            <div class="signature-line"></div>
            <div class="text-center">ครูที่ปรึกษา</div>
            <div class="text-center">(...................................)</div>
        </div>
        
        <div style="display: inline-block; width: 45%; vertical-align: top; margin-left: 5%;">
            <div><strong>2) เรียน ผู้อำนวยการ</strong></div>
            <div>เพื่อโปรดพิจารณา</div>
            <br>
            <div class="signature-line"></div>
            <div class="text-center">หัวหน้าแผนกวิชา</div>
            <div class="text-center">(...................................)</div>
        </div>
    </div>
    
    <div class="approval-item">
        <div style="display: inline-block; width: 45%; vertical-align: top;">
            <div><strong>3) เรียน ผู้อำนวยการ</strong></div>
            <div>☐ เห็นสมควรอนุมัติ</div>
            <div>☐ ไม่สมควรอนุมัติ เนื่องจาก........................</div>
            <br>
            <div class="signature-line"></div>
            <div class="text-center">หัวหน้างานพัฒนาหลักสูตรฯ</div>
            <div class="text-center">(นายบุญลอด โคตรใต้)</div>
            <div class="text-center">........../............../...............</div>
        </div>
        
        <div style="display: inline-block; width: 45%; vertical-align: top; margin-left: 5%;">
            <div><strong>4) เรียน ผู้อำนวยการ</strong></div>
            <div>☐ เห็นสมควรอนุมัติ</div>
            <div>☐ ไม่สมควรอนุมัติ</div>
            <br>
            <div class="signature-line"></div>
            <div class="text-center">รองผู้อำนวยการฝ่ายวิชาการ</div>
            <div class="text-center">(นายสุทิศ รวดเร็ว)</div>
            <div class="text-center">........../............../...............</div>
        </div>
    </div>
    
    <div class="approval-item" style="margin-top: 20pt;">
        <div><strong>คำพิจารณาสั่งการฯ ของผู้อำนวยการวิทยาลัยการอาชีพปราสาท</strong></div>
        <br>';

if ($request['status'] === 'อนุมัติ') {
    $html .= '
        <div><strong>☑ อนุมัติ</strong> และมอบ</div>
        <div style="margin-left: 20pt;">
            <div>1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน</div>
            <div>2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน</div>
            <div>3) แจ้งครูที่ปรึกษา ครุประจำรายวิชา และนักเรียนนักศึกษาทราบ</div>
        </div>
        <div>☐ ไม่อนุมัติ เนื่องจาก................................................................</div>';
} elseif ($request['status'] === 'ไม่อนุมัติ') {
    $html .= '
        <div>☐ อนุมัติ และมอบ</div>
        <div style="margin-left: 20pt;">
            <div>1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน</div>
            <div>2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน</div>
            <div>3) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ</div>
        </div>
        <div><strong>☑ ไม่อนุมัติ</strong> เนื่องจาก <span class="underline">' . ($request['rejection_reason'] ?? '') . '</span></div>';
} else {
    $html .= '
        <div>☐ อนุมัติ และมอบ</div>
        <div style="margin-left: 20pt;">
            <div>1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน</div>
            <div>2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน</div>
            <div>3) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ</div>
        </div>
        <div>☐ ไม่อนุมัติ เนื่องจาก................................................................</div>';
}

$html .= '
        <br><br>
        <div class="text-center">
            <div class="signature-line" style="width: 200pt; margin: 0 auto;"></div>
            <div>(นายชูศักดิ์ ขุ่ยขะ)</div>
            <div>ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
            <div>........../............../...............</div>
        </div>
    </div>
</div>

<div class="text-center" style="margin-top: 30pt; font-weight: bold; font-size: 14pt;">
    "เรียนดี มีความสุข"
</div>

<div class="text-center" style="margin-top: 20pt; font-size: 12pt; color: #666;">
    <div>สถานะปัจจุบัน: <span class="status-' . strtolower($request['status']) . '">' . $request['status'] . '</span></div>
    <div>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</div>
    <div>โดย: ' . $_SESSION['admin_name'] . '</div>
</div>';

// เขียนเนื้อหาลงใน PDF
$mpdf->WriteHTML($html);

// กำหนดชื่อไฟล์
$filename = 'บันทึกข้อความ_คำร้อง_' . $request_id . '_' . $request['student_code'] . '.pdf';

// ส่งออกไฟล์ PDF
$mpdf->Output($filename, 'I'); // 'I' = แสดงในเบราว์เซอร์, 'D' = ดาวน์โหลด

// ฟังก์ชันสำหรับแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    return $thai_months[$month] ?? '';
}
?>