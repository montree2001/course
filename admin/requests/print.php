<?php
// admin/requests/print.php
// ไฟล์สำหรับพิมพ์บันทึกราชการเป็น PDF ตามแบบฟอร์มต้นฉบับ พร้อมฟอนต์ THSarabunIT๙

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
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
    
    // ดึงรายละเอียดรายวิชาที่ขอเปิด (จำกัด 8 วิชา)
    $stmt = $pdo->prepare("
        SELECT rd.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        WHERE rd.request_id = :request_id
        ORDER BY c.course_code
        LIMIT 8
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request_details = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

// กำหนดพาธของโฟลเดอร์ฟอนต์
$fontDir = __DIR__ . '/../../assets/fonts/';

// สร้าง MPDF instance พร้อมตั้งค่าฟอนต์ THSarabunIT๙
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 20,
    'margin_right' => 20,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'default_font_size' => 16,
    'fontDir' => [$fontDir],
    'fontdata' => [
        'thsarabun' => [
            'R' => 'THSarabunIT๙.ttf',
            'B' => 'THSarabunIT๙ Bold.ttf',
            'I' => 'THSarabunIT๙ Italic.ttf',
            'BI' => 'THSarabunIT๙ BoldItalic.ttf'
        ]
    ],
    'default_font' => 'thsarabun'
]);

// ตั้งค่าข้อมูลเอกสาร
$mpdf->SetTitle('บันทึกข้อความขอเปิดรายวิชาภาคเรียนพิเศษ - คำร้อง #' . $request_id);
$mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
$mpdf->SetCreator('ระบบขอเปิดรายวิชา');

// สร้าง SVG ตราครุฑ
$garuda_svg = '
<svg width="80" height="80" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <g fill="#000" stroke="#000" stroke-width="0.8">
    <!-- ตราครุฑแบบสมบูรณ์ -->
    <!-- หัวครุฑ -->
    <circle cx="50" cy="30" r="6" fill="none" stroke-width="1"/>
    <path d="M44 30 L50 22 L56 30" fill="none" stroke-width="1"/>
    <path d="M47 28 L50 25 L53 28" fill="none" stroke-width="0.8"/>
    
    <!-- ลำตัวครุฑ -->
    <ellipse cx="50" cy="45" rx="12" ry="8" fill="none" stroke-width="1"/>
    <path d="M38 45 Q50 35 62 45 Q50 55 38 45" fill="none" stroke-width="1"/>
    
    <!-- ปีกซ้าย -->
    <path d="M35 40 Q25 35 20 45 Q25 50 35 48 Q40 45 35 40" fill="none" stroke-width="1"/>
    <path d="M30 42 Q22 40 18 46 Q22 48 30 47" fill="none" stroke-width="0.6"/>
    
    <!-- ปีกขวา -->
    <path d="M65 40 Q75 35 80 45 Q75 50 65 48 Q60 45 65 40" fill="none" stroke-width="1"/>
    <path d="M70 42 Q78 40 82 46 Q78 48 70 47" fill="none" stroke-width="0.6"/>
    
    <!-- ส่วนท้อง -->
    <ellipse cx="50" cy="58" rx="8" ry="5" fill="none" stroke-width="1"/>
    <path d="M42 58 Q50 52 58 58 Q50 64 42 58" fill="none" stroke-width="0.8"/>
    
    <!-- ขา -->
    <path d="M45 63 L45 75 M47 75 L43 75" stroke-width="1"/>
    <path d="M55 63 L55 75 M57 75 L53 75" stroke-width="1"/>
    
    <!-- หาง -->
    <path d="M50 65 Q50 72 50 78 Q48 80 50 82 Q52 80 50 78" fill="none" stroke-width="0.8"/>
    
    <!-- รายละเอียดตกแต่ง -->
    <circle cx="46" cy="30" r="1.5" fill="#000"/>
    <circle cx="54" cy="30" r="1.5" fill="#000"/>
    <path d="M50 32 Q48 34 50 36 Q52 34 50 32" fill="none" stroke-width="0.6"/>
    
    <!-- ลวดลายบนลำตัว -->
    <path d="M44 42 Q50 40 56 42" fill="none" stroke-width="0.6"/>
    <path d="M44 48 Q50 46 56 48" fill="none" stroke-width="0.6"/>
  </g>
</svg>';

// เข้ารหัส SVG เป็น base64
$garuda_base64 = 'data:image/svg+xml;base64,' . base64_encode($garuda_svg);

// สร้างเนื้อหา HTML
$html = '
<style>
    body {
        font-family: "thsarabun", sans-serif;
        font-size: 16pt;
        line-height: 1.4;
        margin: 0;
        padding: 0;
    }
    
    .header {
        text-align: center;
        margin-bottom: 15pt;
    }
    
    .garuda {
        width: 60pt;
        height: 60pt;
        margin: 0 auto 10pt auto;
        display: block;
    }
    
    .header h1 {
        font-family: "thsarabun", sans-serif;
        font-size: 24pt;
        font-weight: bold;
        margin: 10pt 0;
        padding: 0;
    }
    
    .form-line {
        font-family: "thsarabun", sans-serif;
        margin-bottom: 8pt;
        line-height: 1.8;
    }
    
    .underline {
        border-bottom: 1pt solid black;
        display: inline-block;
        min-width: 80pt;
        padding-bottom: 1pt;
        text-align: center;
        margin: 0 3pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .long-underline {
        border-bottom: 1pt solid black;
        display: inline-block;
        min-width: 200pt;
        padding-bottom: 1pt;
        text-align: center;
        margin: 0 3pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .course-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15pt 0;
        font-size: 14pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .course-table, .course-table th, .course-table td {
        border: 1pt solid black;
    }
    
    .course-table th {
        background-color: #f8f8f8;
        font-weight: bold;
        text-align: center;
        padding: 6pt 3pt;
        font-size: 12pt;
        vertical-align: middle;
        font-family: "thsarabun", sans-serif;
    }
    
    .course-table td {
        padding: 4pt 3pt;
        vertical-align: middle;
        font-size: 12pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-right {
        text-align: right;
    }
    
    .signature-section {
        margin: 20pt 0;
        font-family: "thsarabun", sans-serif;
    }
    
    .signature-line {
        border-bottom: 1pt solid black;
        height: 25pt;
        margin-bottom: 3pt;
        width: 150pt;
        display: inline-block;
    }
    
    .approval-grid {
        width: 100%;
        margin-top: 15pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .approval-box {
        border: 1pt solid black;
        padding: 8pt;
        margin-bottom: 10pt;
        display: inline-block;
        width: 48%;
        vertical-align: top;
        box-sizing: border-box;
        font-family: "thsarabun", sans-serif;
    }
    
    .approval-box-left {
        margin-right: 2%;
    }
    
    .approval-full {
        border: 1pt solid black;
        padding: 10pt;
        margin-top: 10pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .checkbox {
        display: inline-block;
        width: 12pt;
        height: 12pt;
        border: 1pt solid black;
        margin-right: 5pt;
        vertical-align: middle;
        position: relative;
    }
    
    .checkbox.checked::after {
        content: "✓";
        position: absolute;
        left: 1pt;
        top: -2pt;
        font-size: 10pt;
        font-weight: bold;
    }
    
    .approval-title {
        font-weight: bold;
        text-align: center;
        margin-bottom: 10pt;
        font-size: 16pt;
        font-family: "thsarabun", sans-serif;
    }
    
    .footer-motto {
        text-align: center;
        font-weight: bold;
        font-size: 18pt;
        margin-top: 20pt;
        font-family: "thsarabun", sans-serif;
    }
    
    strong {
        font-family: "thsarabun", sans-serif;
        font-weight: bold;
    }
</style>

<!-- ตราครุฑและหัวข้อ -->
<div class="header">
    <img src="' . $garuda_base64 . '" class="garuda" alt="ตราครุฑ">
    <h1>บันทึกข้อความ</h1>
</div>

<!-- ข้อมูลหัวเอกสาร -->
<div class="form-line">
    <strong>ส่วนราชการ</strong> วิทยาลัยการอาชีพปราสาท
    <span class="underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
</div>

<div class="form-line">
    <strong>ที่</strong>
    <span class="underline">' . str_pad($request_id, 20, '&nbsp;', STR_PAD_BOTH) . '</span>
    <span style="margin-left: 100pt;"><strong>วันที่</strong></span>
    <span class="underline">' . str_pad(date('d', strtotime($request['request_date'])), 8, '&nbsp;', STR_PAD_BOTH) . '</span>
    <strong>เดือน</strong>
    <span class="underline">' . str_pad(getThaiMonth(date('n', strtotime($request['request_date']))), 15, '&nbsp;', STR_PAD_BOTH) . '</span>
    <strong>พ.ศ.</strong>
    <span class="underline">' . str_pad((date('Y', strtotime($request['request_date'])) + 543), 8, '&nbsp;', STR_PAD_BOTH) . '</span>
</div>

<div class="form-line">
    <strong>เรื่อง</strong> คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ (เรียนเพิ่ม/เรียนซ้ำ) ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '
</div>

<div class="form-line">
    <strong>เรียน</strong> ผู้อำนวยการวิทยาลัยการอาชีพปราสาท
</div>

<!-- ข้อมูลนักเรียน -->
<div style="margin: 15pt 0;">
    <div class="form-line">
        ข้าพเจ้า (' . $request['prefix'] . ')
        <span class="long-underline">' . $request['first_name'] . ' ' . $request['last_name'] . '</span>
        รหัสประจำตัว
        <span class="underline">' . $request['student_code'] . '</span>
    </div>
    
    <div class="form-line">
        ระดับชั้น ' . $request['level'] . ' ชั้นปีที่
        <span class="underline">' . str_pad($request['year'], 8, '&nbsp;', STR_PAD_BOTH) . '</span>
        สาขาวิชา
        <span class="long-underline">' . ($request['department_name'] ?? 'ไม่ระบุ') . '</span>
    </div>
    
    <div class="form-line">
        เบอร์โทรศัพท์ที่ติดต่อได้
        <span class="long-underline">' . ($request['phone'] ?? 'ไม่ระบุ') . '</span>
        มีความประสงค์ขอให้เปิดรายวิชา ดังนี้
    </div>
</div>

<!-- ตารางรายวิชา -->
<table class="course-table">
    <thead>
        <tr>
            <th rowspan="2" width="5%">ที่</th>
            <th rowspan="2" width="12%">รหัสวิชา</th>
            <th rowspan="2" width="25%">ชื่อรายวิชา</th>
            <th colspan="3" width="18%">จำนวน</th>
            <th rowspan="2" width="20%">ชื่อครูประจำรายวิชา<br>(ให้เขียนตัวบรรจง)</th>
            <th rowspan="2" width="15%">ลงชื่อครู<br>ประจำรายวิชา</th>
        </tr>
        <tr>
            <th width="6%">ทฤษฎี</th>
            <th width="6%">ปฏิบัติ</th>
            <th width="6%">หน่วย<br>กิต</th>
        </tr>
    </thead>
    <tbody>';

$total_credit_hours = 0;

// สร้างแถวรายวิชา (รองรับสูงสุด 8 วิชา)
for ($i = 0; $i < 8; $i++) {
    if (isset($request_details[$i])) {
        $detail = $request_details[$i];
        $total_credit_hours += $detail['credit_hours'];
        
        $html .= '
        <tr>
            <td class="text-center">' . ($i + 1) . '</td>
            <td class="text-center">' . $detail['course_code'] . '</td>
            <td>' . $detail['course_name'] . '</td>
            <td class="text-center">' . $detail['theory_hours'] . '</td>
            <td class="text-center">' . $detail['practice_hours'] . '</td>
            <td class="text-center">' . $detail['credit_hours'] . '</td>
            <td class="text-center">' . $detail['teacher_prefix'] . $detail['teacher_first_name'] . ' ' . $detail['teacher_last_name'] . '</td>
            <td>&nbsp;</td>
        </tr>';
    } else {
        // แถวว่าง
        $html .= '
        <tr>
            <td class="text-center">' . ($i + 1) . '</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>';
    }
}

// แถวรวม
$html .= '
        <tr style="font-weight: bold;">
            <td colspan="2" class="text-center">รวม</td>
            <td class="text-center">' . count($request_details) . ' วิชา</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-center">' . $total_credit_hours . '</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </tbody>
</table>

<div class="form-line">
    จึงเรียนมาเพื่อโปรดพิจารณา
</div>

<!-- ลายเซ็นผู้ยื่นคำร้อง -->
<div class="text-right signature-section">
    <div class="signature-line"></div><br>
    ผู้ยื่นคำร้อง<br>
    (' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . ')<br>
    นักเรียน นักศึกษา
</div>

<!-- ส่วนการพิจารณา -->
<div class="approval-grid">
    <div class="approval-box approval-box-left">
        <strong>๑) เรียน ผู้อำนวยการ</strong><br>
        เพื่อโปรดพิจารณา<br><br><br>
        <div class="signature-line"></div><br>
        <div class="text-center">ครูที่ปรึกษา</div>
        <div class="text-center">(...................................)</div>
    </div>
    
    <div class="approval-box">
        <strong>๒) เรียน ผู้อำนวยการ</strong><br>
        เพื่อโปรดพิจารณา<br><br><br>
        <div class="signature-line"></div><br>
        <div class="text-center">หัวหน้าแผนกวิชา</div>
        <div class="text-center">(...................................)</div>
    </div>
</div>

<div class="approval-grid">
    <div class="approval-box approval-box-left">
        <strong>๓) เรียน ผู้อำนวยการ</strong><br>
        <div class="checkbox"></div> เห็นสมควรอนุมัติ<br>
        <div class="checkbox"></div> ไม่สมควรอนุมัติ เนื่องจาก.....................<br><br>
        <div class="signature-line"></div><br>
        <div class="text-center">หัวหน้างานพัฒนาหลักสูตรฯ</div>
        <div class="text-center">(นายบุญลอด โคตรใต้)</div>
        <div class="text-center">........../............../...............</div>
    </div>
    
    <div class="approval-box">
        <strong>๔) เรียน ผู้อำนวยการ</strong><br>
        <div class="checkbox"></div> เห็นสมควรอนุมัติ<br>
        <div class="checkbox"></div> ไม่สมควรอนุมัติ<br><br><br>
        <div class="signature-line"></div><br>
        <div class="text-center">รองผู้อำนวยการฝ่ายวิชาการ</div>
        <div class="text-center">(นายสุทิศ รวดเร็ว)</div>
        <div class="text-center">........../............../...............</div>
    </div>
</div>

<!-- คำพิจารณาสั่งการของผู้อำนวยการ -->
<div class="approval-full">
    <div class="approval-title">คำพิจารณาสั่งการฯ ของผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
    
    <div>';

// แสดงสถานะการอนุมัติ
if ($request['status'] === 'อนุมัติ') {
    $html .= '<div class="checkbox checked"></div> <strong>อนุมัติ</strong> และมอบ';
} else {
    $html .= '<div class="checkbox"></div> อนุมัติ และมอบ';
}

$html .= '
        <div style="margin-left: 20pt; margin: 10pt 0;">
            ๑) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน<br>
            ๒) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน<br>
            ๓) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ
        </div>
        
        <div>';

if ($request['status'] === 'ไม่อนุมัติ') {
    $html .= '<div class="checkbox checked"></div> <strong>ไม่อนุมัติ</strong> เนื่องจาก<span class="long-underline">' . ($request['rejection_reason'] ?? '') . '</span>';
} else {
    $html .= '<div class="checkbox"></div> ไม่อนุมัติ เนื่องจาก<span class="long-underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
}

$html .= '
        </div>
        
        <div style="text-align: center; margin-top: 30pt;">
            <div class="signature-line"></div><br>
            (นายชูศักดิ์ ขุ่ยขะ)<br>
            ผู้อำนวยการวิทยาลัยการอaชีพปราสาท<br>
            ........../............../...............
        </div>
    </div>
</div>

<div class="footer-motto">
    "เรียนดี มีความสุข"
</div>';

// เขียนเนื้อหาลงใน PDF
$mpdf->WriteHTML($html);

// กำหนดชื่อไฟล์
$filename = 'บันทึกข้อความขอเปิดรายวิชาภาคเรียนพิเศษ_คำร้อง_' . $request_id . '_' . $request['student_code'] . '.pdf';

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