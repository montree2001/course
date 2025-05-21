<?php
// api/print_request.php
// API สำหรับพิมพ์คำร้องในรูปแบบ PDF

require_once '../config/db_connect.php';
require_once '../config/functions.php';
require_once '../mpdf/vendor/autoload.php';

// ตรวจสอบว่ามีการระบุ ID คำร้องหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('กรุณาระบุรหัสคำร้อง');
}

$request_id = $_GET['id'];

try {
    // ดึงข้อมูลคำร้อง
    $request = getCourseRequestById($pdo, $request_id);
    
    if (!$request) {
        die('ไม่พบข้อมูลคำร้องที่ระบุ');
    }
    
    // ดึงข้อมูลรายละเอียดคำร้อง (รายวิชาที่ขอเปิด)
    $details = getRequestDetails($pdo, $request_id);
    
    // เริ่มต้นสร้าง MPDF
    $mpdf = initMPDF();
    
    // กำหนดค่าเริ่มต้น
    $mpdf->SetTitle('คำร้องขอเปิดรายวิชา - ' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']);
    
    // แยกวันที่
    $request_date = explode('-', $request['request_date']);
    $year_th = (int)$request_date[0] + 543;
    $month = (int)$request_date[1];
    
    // แปลงเดือนเป็นภาษาไทย
    $thai_month = [
        '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม',
        'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน',
        'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    $day = (int)$request_date[2];
    
    // เริ่มต้นเนื้อหา HTML
    $html = '
    <style>
        body {
            font-family: "thsarabun";
            font-size: 16pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20pt;
        }
        .form-title {
            font-size: 20pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15pt;
        }
        .form-header {
            width: 100%;
        }
        .form-header td {
            vertical-align: top;
            padding: 2pt;
        }
        .form-header .label {
            font-weight: bold;
            width: 100pt;
        }
        .form-header .dots {
            border-bottom: 1px dotted #000;
        }
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10pt;
            margin-bottom: 15pt;
        }
        .form-table, .form-table th, .form-table td {
            border: 1px solid #000;
        }
        .form-table th {
            font-weight: bold;
            text-align: center;
            padding: 5pt;
        }
        .form-table td {
            padding: 5pt;
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .signature-section {
            margin-top: 20pt;
        }
        .signature {
            text-align: center;
            margin-top: 40pt;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200pt;
            margin: 5pt auto;
        }
        .approval-section {
            margin-top: 20pt;
            page-break-inside: avoid;
        }
        .approval-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20pt;
        }
        .approval-box {
            width: 45%;
            text-align: center;
        }
        .checkbox {
            width: 14pt;
            height: 14pt;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5pt;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
    
    <div class="header">
        <img src="../assets/images/logo.png" width="70" height="70" />
        <div style="font-size: 20pt; font-weight: bold;">บันทึกข้อความ</div>
    </div>
    
    <table class="form-header">
        <tr>
            <td class="label">ส่วนราชการ</td>
            <td class="dots" colspan="3">วิทยาลัยการอาชีพปราสาท</td>
        </tr>
        <tr>
            <td class="label">ที่</td>
            <td class="dots"></td>
            <td class="label">วันที่</td>
            <td class="dots">' . $day . ' เดือน ' . $thai_month[$month] . ' พ.ศ. ' . $year_th . '</td>
        </tr>
        <tr>
            <td class="label">เรื่อง</td>
            <td class="dots" colspan="3">คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ (เรียนเพิ่ม/เรียนซ้ำ) ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '</td>
        </tr>
        <tr>
            <td class="label">เรียน</td>
            <td class="dots" colspan="3">ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</td>
        </tr>
    </table>
    
    <div style="margin-left: 30pt;">
        <p>ข้าพเจ้า (นาย/นางสาว) <u>' . $request['first_name'] . ' ' . $request['last_name'] . '</u> รหัสประจำตัว <u>' . $request['student_code'] . '</u></p>
        
        <p>ระดับชั้น 
           <u>' . ($request['level'] === 'ปวช.' ? 'ปวช.ชั้นปีที่ ' . $request['year'] : '') . '</u>
           <u>' . ($request['level'] === 'ปวส.' ? 'ปวส.ชั้นปีที่ ' . $request['year'] : '') . '</u>
           สาขาวิชา <u>' . $request['department_name'] . '</u><br>
           เบอร์โทรศัพท์ที่ติดต่อได้ <u>' . $request['phone'] . '</u> มีความประสงค์ขอให้เปิดรายวิชา ดังนี้
        </p>
    </div>
    
    <table class="form-table">
        <tr>
            <th width="5%">ที่</th>
            <th width="15%">รหัสวิชา</th>
            <th width="30%">ชื่อรายวิชา</th>
            <th width="8%">ทฤษฎี</th>
            <th width="8%">ปฏิบัติ</th>
            <th width="8%">หน่วยกิต</th>
            <th width="8%">ชั่วโมง</th>
            <th width="18%">ชื่อครูประจำรายวิชา<br>(ให้เขียนตัวบรรจง)</th>
            <th width="10%">ลงชื่อครูประจำรายวิชา</th>
        </tr>';
    
    // เพิ่มข้อมูลรายวิชา
    foreach ($details as $index => $detail) {
        $total_hours = (int)$detail['theory_hours'] + (int)$detail['practice_hours'];
        $html .= '
        <tr>
            <td class="text-center">' . ($index + 1) . '</td>
            <td>' . $detail['course_code'] . '</td>
            <td>' . $detail['course_name'] . '</td>
            <td class="text-center">' . $detail['theory_hours'] . '</td>
            <td class="text-center">' . $detail['practice_hours'] . '</td>
            <td class="text-center">' . $detail['credit_hours'] . '</td>
            <td class="text-center">' . $total_hours . '</td>
            <td>' . $detail['prefix'] . $detail['first_name'] . ' ' . $detail['last_name'] . '</td>
            <td></td>
        </tr>';
    }
    
    // เพิ่มแถวรวม
    $html .= '
        <tr>
            <td colspan="7" class="text-center"><strong>รวม</strong></td>
            <td class="text-center"><strong>' . count($details) . ' วิชา</strong></td>
            <td></td>
        </tr>
    </table>
    
    <div style="text-align: center; margin-top: 15pt;">จึงเรียนมาเพื่อโปรดพิจารณา</div>
    
    <div class="signature">
        <div style="margin-right: 80pt; text-align: right;">
            .............................................................. ผู้ยื่นคำร้อง<br>
            (' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . ')<br>
            นักเรียน นักศึกษา
        </div>
    </div>
    
    <table width="100%" style="margin-top: 30pt;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 10pt;">
                <div style="border: 1px solid #000; padding: 10pt;">
                    <div>1) เรียน ผู้อำนวยการ</div>
                    <div style="margin-left: 10pt; margin-top: 5pt;">เพื่อโปรดพิจารณา</div>
                    <div style="margin-top: 30pt; text-align: center;">
                        .............................................................. ครูที่ปรึกษา<br>
                        (..............................................................)
                    </div>
                </div>
            </td>
            <td width="50%" style="vertical-align: top; padding-left: 10pt;">
                <div style="border: 1px solid #000; padding: 10pt;">
                    <div>2) เรียน ผู้อำนวยการ</div>
                    <div style="margin-left: 10pt; margin-top: 5pt;">เพื่อโปรดพิจารณา</div>
                    <div style="margin-top: 30pt; text-align: center;">
                        .............................................................. หัวหน้าแผนกวิชา<br>
                        (..............................................................)
                    </div>
                </div>
            </td>
        </tr>
    </table>
    
    <table width="100%" style="margin-top: 15pt;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 10pt;">
                <div style="border: 1px solid #000; padding: 10pt;">
                    <div>3) เรียน ผู้อำนวยการ</div>
                    <div style="margin-left: 10pt; margin-top: 5pt;">
                        <div><input type="checkbox" /> เห็นสมควรอนุมัติ</div>
                        <div><input type="checkbox" /> ไม่สมควรอนุมัติ</div>
                    </div>
                    <div style="margin-top: 30pt; text-align: center;">
                        .............................................................. หัวหน้างานพัฒนาหลักสูตรฯ<br>
                        (นายบุญลอด โคตรใต้)
                    </div>
                </div>
            </td>
            <td width="50%" style="vertical-align: top; padding-left: 10pt;">
                <div style="border: 1px solid #000; padding: 10pt;">
                    <div>4) เรียน ผู้อำนวยการ</div>
                    <div style="margin-left: 10pt; margin-top: 5pt;">
                        <div><input type="checkbox" /> เห็นสมควรอนุมัติ</div>
                        <div><input type="checkbox" /> ไม่สมควรอนุมัติ เนื่องจาก................................</div>
                    </div>
                    <div style="margin-top: 30pt; text-align: center;">
                        .............................................................. รองผู้อำนวยการฝ่ายวิชาการ<br>
                        (นายสุทิศ รวดเร็ว)
                    </div>
                </div>
            </td>
        </tr>
    </table>
    
    <div style="border: 1px solid #000; padding: 10pt; margin-top: 15pt;">
        <div style="font-weight: bold;">คำพิจารณาสั่งการฯ ของผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
        <div style="margin-left: 10pt; margin-top: 5pt;">
            <div><input type="checkbox" /> อนุมัติ และมอบ</div>
            <div style="margin-left: 20pt;">
                <div>1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน</div>
                <div>2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน</div>
                <div>3) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ</div>
            </div>
            <div><input type="checkbox" /> ไม่อนุมัติ เนื่องจาก.........................................................................................................</div>
        </div>
        <div style="margin-top: 30pt; text-align: center;">
            .............................................................. <br>
            (นายชูศักดิ์ ขุ่ยขะ)<br>
            ผู้อำนวยการวิทยาลัยการอาชีพปราสาท
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 15pt; font-weight: bold;">"เรียนดี มีความสุข"</div>
    ';
    
    // เพิ่มเนื้อหา HTML ลงใน MPDF
    $mpdf->WriteHTML($html);
    
    // ดาวน์โหลดไฟล์ PDF
    $filename = 'คำร้องขอเปิดรายวิชา_' . $request['student_code'] . '.pdf';
    $mpdf->Output($filename, 'D');
    
} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการสร้างไฟล์ PDF: ' . $e->getMessage());
}