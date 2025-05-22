<?php
// admin/schedules/print.php
// ไฟล์สำหรับพิมพ์ตารางเรียนเป็น PDF

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

// ตรวจสอบว่ามี request_id
$request_id = $_GET['request_id'] ?? '';

if (empty($request_id)) {
    die('ไม่พบข้อมูลคำร้องที่ต้องการพิมพ์ตาราง');
}

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
        WHERE cr.request_id = :request_id AND cr.status = 'อนุมัติ'
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        die('ไม่พบคำร้องที่อนุมัติแล้วหรือคำร้องไม่ได้รับการอนุมัติ');
    }
    
    // ดึงรายละเอียดรายวิชาและตารางเรียน
    $stmt = $pdo->prepare("
        SELECT rd.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
               cs.day_of_week, cs.start_time, cs.end_time, cs.room
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        LEFT JOIN class_schedules cs ON rd.detail_id = cs.detail_id
        WHERE rd.request_id = :request_id
        ORDER BY c.course_code
    ");
    $stmt->execute(['request_id' => $request_id]);
    $course_details = $stmt->fetchAll();
    
    if (empty($course_details)) {
        die('ไม่พบรายวิชาในคำร้องนี้');
    }
    
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
}

// สร้าง MPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'thsarabun'
]);

// กำหนดฟอนต์ภาษาไทย
$mpdf->fontdata['thsarabun'] = [
    'R' => 'THSarabunIT๙.ttf',
    'B' => 'THSarabunIT๙ Bold.ttf',
    'I' => 'THSarabunIT๙ Italic.ttf',
    'BI' => 'THSarabunIT๙ BoldItalic.ttf'
];

$mpdf->SetDefaultFont('thsarabun');
$mpdf->default_font = 'thsarabun';

// ตั้งค่าข้อมูลเอกสาร
$mpdf->SetTitle('ตารางเรียนภาคเรียนพิเศษ - ' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']);
$mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
$mpdf->SetCreator('ระบบขอเปิดรายวิชา');

// จัดกลุ่มตารางตามวัน
$schedules_by_day = [];
$courses_with_schedule = [];
$courses_without_schedule = [];

foreach ($course_details as $course) {
    if ($course['day_of_week']) {
        $courses_with_schedule[] = $course;
        if (!isset($schedules_by_day[$course['day_of_week']])) {
            $schedules_by_day[$course['day_of_week']] = [];
        }
        $schedules_by_day[$course['day_of_week']][] = $course;
    } else {
        $courses_without_schedule[] = $course;
    }
}

// เรียงลำดับตามวัน
$day_order = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'];
$ordered_schedules = [];
foreach ($day_order as $day) {
    if (isset($schedules_by_day[$day])) {
        // เรียงตามเวลา
        usort($schedules_by_day[$day], function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });
        $ordered_schedules[$day] = $schedules_by_day[$day];
    }
}

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
    
    .header h1 {
        font-size: 20pt;
        font-weight: bold;
        margin: 5pt 0;
    }
    
    .header h2 {
        font-size: 18pt;
        font-weight: bold;
        margin: 5pt 0;
        color: #333;
    }
    
    .student-info {
        margin-bottom: 20pt;
        border: 1pt solid #333;
        padding: 10pt;
    }
    
    .student-info table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .student-info td {
        padding: 5pt;
        font-size: 14pt;
    }
    
    .section-title {
        font-size: 18pt;
        font-weight: bold;
        margin: 15pt 0 10pt 0;
        color: #333;
        border-bottom: 2pt solid #333;
        padding-bottom: 5pt;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10pt 0;
    }
    
    table, th, td {
        border: 1pt solid #333;
    }
    
    th {
        background-color: #f8f9fa;
        font-weight: bold;
        text-align: center;
        padding: 8pt 5pt;
        font-size: 14pt;
    }
    
    td {
        padding: 6pt 5pt;
        vertical-align: top;
        font-size: 14pt;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-right {
        text-align: right;
    }
    
    .day-schedule {
        margin-bottom: 15pt;
        page-break-inside: avoid;
    }
    
    .day-title {
        font-size: 16pt;
        font-weight: bold;
        background-color: #e9ecef;
        padding: 8pt;
        margin: 10pt 0 5pt 0;
        text-align: center;
        border: 1pt solid #333;
    }
    
    .course-item {
        margin-bottom: 5pt;
        padding: 5pt;
        border: 1pt solid #ddd;
        background-color: #fafafa;
    }
    
    .no-schedule {
        color: #dc3545;
        font-style: italic;
    }
    
    .footer {
        text-align: center;
        font-size: 14pt;
        margin-top: 20pt;
        border-top: 1pt solid #333;
        padding-top: 10pt;
    }
    
    .page-break {
        page-break-before: always;
    }
</style>

<div class="header">
    <h1>ตารางเรียนภาคเรียนพิเศษ</h1>
    <h2>ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '</h2>
</div>

<div class="student-info">
    <table>
        <tr>
            <td style="width: 25%; font-weight: bold;">รหัสนักเรียน:</td>
            <td style="width: 25%;">' . $request['student_code'] . '</td>
            <td style="width: 25%; font-weight: bold;">ชื่อ-นามสกุล:</td>
            <td style="width: 25%;">' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . '</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">ระดับชั้น:</td>
            <td>' . $request['level'] . ' ปีที่ ' . $request['year'] . '</td>
            <td style="font-weight: bold;">สาขาวิชา:</td>
            <td>' . ($request['department_name'] ?? 'ไม่ระบุ') . '</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">เบอร์โทรศัพท์:</td>
            <td>' . ($request['phone'] ?? 'ไม่ระบุ') . '</td>
            <td style="font-weight: bold;">วันที่พิมพ์:</td>
            <td>' . date('d/m/Y H:i:s') . '</td>
        </tr>
    </table>
</div>

<div class="section-title">รายวิชาที่ลงทะเบียน</div>
<table>
    <thead>
        <tr>
            <th width="8%">ลำดับ</th>
            <th width="15%">รหัสวิชา</th>
            <th width="35%">ชื่อรายวิชา</th>
            <th width="8%">ทฤษฎี</th>
            <th width="8%">ปฏิบัติ</th>
            <th width="8%">หน่วยกิต</th>
            <th width="18%">ครูผู้สอน</th>
        </tr>
    </thead>
    <tbody>';

$total_credit_hours = 0;
foreach ($course_details as $index => $course) {
    $total_credit_hours += $course['credit_hours'];
    
    $html .= '
        <tr>
            <td class="text-center">' . ($index + 1) . '</td>
            <td class="text-center">' . $course['course_code'] . '</td>
            <td>' . $course['course_name'] . '</td>
            <td class="text-center">' . $course['theory_hours'] . '</td>
            <td class="text-center">' . $course['practice_hours'] . '</td>
            <td class="text-center">' . $course['credit_hours'] . '</td>
            <td class="text-center">' . $course['teacher_prefix'] . $course['teacher_first_name'] . ' ' . $course['teacher_last_name'] . '</td>
        </tr>';
}

$html .= '
        <tr style="font-weight: bold; background-color: #f8f9fa;">
            <td colspan="5" class="text-center">รวม ' . count($course_details) . ' วิชา</td>
            <td class="text-center">' . $total_credit_hours . '</td>
            <td></td>
        </tr>
    </tbody>
</table>';

// แสดงตารางเรียนแยกตามวัน
if (!empty($ordered_schedules)) {
    $html .= '<div class="section-title">ตารางเรียนรายวัน</div>';
    
    foreach ($ordered_schedules as $day => $courses) {
        $html .= '<div class="day-schedule">';
        $html .= '<div class="day-title">วัน' . $day . '</div>';
        $html .= '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th width="20%">เวลา</th>';
        $html .= '<th width="15%">รหัสวิชา</th>';
        $html .= '<th width="35%">ชื่อรายวิชา</th>';
        $html .= '<th width="15%">ห้องเรียน</th>';
        $html .= '<th width="15%">ครูผู้สอน</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($courses as $course) {
            $time_range = date('H:i', strtotime($course['start_time'])) . ' - ' . date('H:i', strtotime($course['end_time']));
            
            $html .= '<tr>';
            $html .= '<td class="text-center">' . $time_range . '</td>';
            $html .= '<td class="text-center">' . $course['course_code'] . '</td>';
            $html .= '<td>' . $course['course_name'] . '</td>';
            $html .= '<td class="text-center">' . ($course['room'] ?? 'ไม่ระบุ') . '</td>';
            $html .= '<td class="text-center">' . $course['teacher_prefix'] . $course['teacher_first_name'] . ' ' . $course['teacher_last_name'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
    }
}

// แสดงรายวิชาที่ยังไม่จัดตาราง (ถ้ามี)
if (!empty($courses_without_schedule)) {
    $html .= '<div class="section-title">รายวิชาที่ยังไม่ได้จัดตาราง</div>';
    $html .= '<table>';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th width="15%">รหัสวิชา</th>';
    $html .= '<th width="40%">ชื่อรายวิชา</th>';
    $html .= '<th width="10%">หน่วยกิต</th>';
    $html .= '<th width="35%">ครูผู้สอน</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($courses_without_schedule as $course) {
        $html .= '<tr>';
        $html .= '<td class="text-center">' . $course['course_code'] . '</td>';
        $html .= '<td>' . $course['course_name'] . '</td>';
        $html .= '<td class="text-center">' . $course['credit_hours'] . '</td>';
        $html .= '<td class="text-center">' . $course['teacher_prefix'] . $course['teacher_first_name'] . ' ' . $course['teacher_last_name'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '<p class="no-schedule"><strong>หมายเหตุ:</strong> รายวิชาเหล่านี้ยังไม่ได้จัดตารางเรียน กรุณาติดต่อเจ้าหน้าที่</p>';
}

// สร้างตารางสัปดาห์ (Weekly Schedule)
if (!empty($ordered_schedules)) {
    $html .= '<div class="page-break"></div>';
    $html .= '<div class="section-title">ตารางเรียนประจำสัปดาห์</div>';
    
    // สร้างตารางเวลา
    $time_slots = [];
    $all_times = [];
    
    foreach ($courses_with_schedule as $course) {
        $start_hour = date('H', strtotime($course['start_time']));
        $end_hour = date('H', strtotime($course['end_time']));
        for ($h = $start_hour; $h <= $end_hour; $h++) {
            $all_times[] = sprintf('%02d:00', $h);
        }
    }
    
    $all_times = array_unique($all_times);
    sort($all_times);
    
    // สร้างช่วงเวลา
    for ($i = 0; $i < count($all_times) - 1; $i++) {
        $time_slots[] = $all_times[$i] . '-' . $all_times[$i + 1];
    }
    
    if (empty($time_slots)) {
        $time_slots = ['08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00'];
    }
    
    $html .= '<table>';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th width="15%">เวลา</th>';
    foreach ($day_order as $day) {
        $html .= '<th width="14%">วัน' . $day . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($time_slots as $time_slot) {
        list($start_time, $end_time) = explode('-', $time_slot);
        
        $html .= '<tr>';
        $html .= '<td class="text-center" style="font-weight: bold;">' . $time_slot . '</td>';
        
        foreach ($day_order as $day) {
            $cell_content = '';
            
            if (isset($ordered_schedules[$day])) {
                foreach ($ordered_schedules[$day] as $course) {
                    $course_start = date('H:i', strtotime($course['start_time']));
                    $course_end = date('H:i', strtotime($course['end_time']));
                    
                    if ($course_start <= $start_time && $course_end >= $end_time) {
                        $cell_content = '<div style="font-size: 12pt;"><strong>' . $course['course_code'] . '</strong><br>' . 
                                       ($course['room'] ? 'ห้อง ' . $course['room'] : '') . '</div>';
                        break;
                    }
                }
            }
            
            $html .= '<td class="text-center">' . $cell_content . '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
}

$html .= '
<div class="footer">
    <div style="margin-bottom: 10pt;">
        <strong>วิทยาลัยการอาชีพปราสาท</strong><br>
        เลขที่ 62 หมู่ 7 ตำบลกังแอน อำเภอปราสาท จังหวัดสุรินทร์ 32140<br>
        โทรศัพท์: 044-551-161
    </div>
    <div style="border-top: 1pt solid #333; padding-top: 10pt;">
        <div>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</div>
        <div>โดย: ' . $_SESSION['admin_name'] . '</div>
    </div>
</div>';

// เขียนเนื้อหาลงใน PDF
$mpdf->WriteHTML($html);

// กำหนดชื่อไฟล์
$filename = 'ตารางเรียน_' . $request['student_code'] . '_ภาคเรียนที่' . $request['semester'] . '_' . $request['academic_year'] . '.pdf';

// ส่งออกไฟล์ PDF
$mpdf->Output($filename, 'I'); // 'I' = แสดงในเบราว์เซอร์, 'D' = ดาวน์โหลด
?>