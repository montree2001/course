<?php
require 'vendor/autoload.php'; // โหลด mPDF

use Mpdf\Mpdf;

// รับข้อมูลจากฟอร์ม
$student_name = $_POST['student_name'];
$student_id = $_POST['student_id'];
$class_level = $_POST['class_level'];
$major = $_POST['major'];
$phone = $_POST['phone'];
$request_date = date('Y-m-d');

$course_codes = $_POST['course_code'];
$course_names = $_POST['course_name'];
$theories = $_POST['theory'];
$practices = $_POST['practice'];
$credits = $_POST['credits'];
$hours = $_POST['hours'];
$instructors = $_POST['instructor'];
$advisory = $_POST['advisory'];

$mpdf = new Mpdf([
    'default_font' => 'thsarabun', // ตั้งฟอนต์เริ่มต้นเป็น THSarabun
    'fontdata' => [
        'thsarabun' => [
            'R' => 'THSarabunIT๙.ttf', // ฟอนต์ปกติ
            'B' => 'THSarabunIT๙ Bold.ttf', // ฟอนต์ตัวหนา
            'I' => 'THSarabunIT๙ Italic.ttf', // ฟอนต์ตัวเอียง
            'BI' => 'THSarabunIT๙ BoldItalic.ttf', // ฟอนต์ตัวหนาและเอียง
        ],
    ],
    'tempDir' => __DIR__ . '/tmp' // กำหนดโฟลเดอร์สำหรับไฟล์ชั่วคราว
]);

// ตั้งค่าพื้นหลังเป็น PDF ต้นฉบับ
$pdfTemplate = __DIR__ . '/temp.pdf';
$pagecount = $mpdf->SetSourceFile($pdfTemplate);
$tplId = $mpdf->ImportPage($pagecount);
$mpdf->SetPageTemplate($tplId);

// เพิ่มหน้า PDF
$mpdf->AddPage();

// เติมข้อมูลลงในตำแหน่งที่ต้องการ
$html = "
<style>
    body {
        font-family: 'thsarabun';
        font-size: 15pt;
    }
  
    .text { top: 500px; left: 150px; }

     table {
        width: 90%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
        text-align: center;
        padding: 4px; /* ลด padding */
        line-height: 0.8; /* ลดความสูงของบรรทัด */
    }
</style>

<body>

<div style='position: absolute; top: 210px; left: 100px; right: 0px;'>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้า $student_name รหัสนักศึกษา $student_id ระดับชั้น $class_level สาขาวิชา $major เบอร์โทรศัพท์ที่ติดต่อได้ $phone มีความประสงค์ขอให้เปิดรายวิชา ดังนี้
</div>
<div style='position: absolute; top: 280px; left: 85px; right: 40px;'>
<table>

        <thead>
            <tr>
                <th rowspan='2'>ที่</th>
                <th rowspan='2'>รหัสวิชา</th>
                <th rowspan='2' >ชื่อรายวิชา</th>
                <th colspan='4'>จำนวน</th>
                <th rowspan='2'>ชื่อครูประจำรายวิชา <br> (ให้เขียนตัวบรรจง)</th>
                <th rowspan='2'>ลงชื่อครูประจำรายวิชา</th>
            </tr>
            <tr>
                
                <th>ทฤษฎี</th>
                <th>ปฏิบัติ</th>
                <th>หน่วย <br>กิต</th>
                <th>ชั่วโมง</th>
                
            </tr>
        </thead>
        <tbody>
            <!-- Repeat this row for each subject -->
            <tr>
                <td>1</td>
                <td>2000121</td>
                <td>ภาษาไทยเพื่อการสื่อสาร และการสื่อสาร</td>
                <td>1</td>
                <td>3</td>
                <td>1</td>
                <td>6</td>
                <td>นายมนตรี  ศรีสุข นายมนตรี  ศรีสุข</td>
                <td></td>
            </tr>
            
          
           
          
            <!-- Add more rows as needed -->
        </tbody>
        <tfoot>
            <tr>
                <td colspan='2'>รวม</td>
                <td>........วิชา</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td colspan='2'></td>
            </tr>
        </tfoot>
    </table>
</div>
</body>


";

// เขียน HTML ลงใน PDF
$mpdf->WriteHTML($html);

// ส่งออก PDF
$pdf_file_name = "Request_Form_$student_id.pdf";
$mpdf->Output($pdf_file_name, \Mpdf\Output\Destination::INLINE);
