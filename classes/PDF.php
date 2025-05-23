<?php
// Include mPDF library
require_once '../vendor/autoload.php';

class PDF {
    // Database connection
    private $conn;
    private $mpdf;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
        
        try {
            // Create new mPDF instance with simpler configuration
            $this->mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
                'default_font' => 'tahoma',
                'default_font_size' => 14,
                'autoScriptToLang' => true,
                'autoLangToFont' => true
            ]);
            
            // Set document information
            $this->mpdf->SetTitle('ระบบขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท');
            $this->mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
            $this->mpdf->SetCreator('ระบบขอเปิดรายวิชา');
            
        } catch (Exception $e) {
            // Fallback to basic configuration if font loading fails
            $this->mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'default_font' => 'dejavusanscondensed',
                'default_font_size' => 14
            ]);
        }
    }
    
    // Generate course request form PDF
    public function generateCourseRequestPDF($request_id) {
        // Include necessary classes
        include_once '../classes/CourseRequest.php';
        include_once '../classes/Student.php';
        
        // Create course request object
        $courseRequest = new CourseRequest($this->conn);
        $courseRequest->id = $request_id;
        
        // Get request details
        $request_details = $courseRequest->getRequestById();
        
        if (!$request_details) {
            return false;
        }
        
        // Get request items
        $request_items = $courseRequest->getRequestItems();
        
        // Set PDF properties
        $this->mpdf->SetTitle('คำร้องขอเปิดรายวิชา #' . $request_id);
        
        // Thai date formatter
        $thai_month_arr = array(
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        );
        
        $request_date = date_create($request_details['request_date']);
        $thai_date = date_format($request_date, "d");
        $thai_month = $thai_month_arr[date_format($request_date, "m")];
        $thai_year = date_format($request_date, "Y") + 543;
        
        // HTML content for PDF with inline CSS
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ</title>
            <style>
                body {
                    font-family: tahoma, sans-serif;
                    font-size: 14pt;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .memo-header {
                    text-align: center;
                    font-weight: bold;
                    font-size: 18pt;
                    margin-bottom: 15px;
                }
                .section {
                    margin-bottom: 15px;
                }
                .info-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                }
                .info-table td {
                    padding: 3px 5px;
                    vertical-align: top;
                }
                .info-table .label {
                    font-weight: bold;
                    width: 15%;
                }
                .info-table .underline {
                    border-bottom: 1px dotted #000;
                    min-height: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th {
                    background-color: #f2f2f2;
                    text-align: center;
                    font-weight: bold;
                    padding: 8px 4px;
                    font-size: 12pt;
                }
                td {
                    padding: 6px 4px;
                    text-align: center;
                    font-size: 12pt;
                }
                .text-left {
                    text-align: left !important;
                }
                .signature-section {
                    margin-top: 30px;
                    text-align: center;
                }
                .approval-section {
                    margin-top: 20px;
                    width: 100%;
                }
                .approval-box {
                    width: 48%;
                    float: left;
                    margin-bottom: 25px;
                    margin-right: 2%;
                    border: 1px solid #ddd;
                    padding: 10px;
                    min-height: 120px;
                }
                .final-approval {
                    clear: both;
                    border: 2px solid black;
                    padding: 15px;
                    margin-top: 30px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                    font-weight: bold;
                }
                .checkbox {
                    display: inline-block;
                    width: 12px;
                    height: 12px;
                    border: 1px solid black;
                    margin-right: 5px;
                    vertical-align: middle;
                }
                .checkbox.checked {
                    background-color: #000;
                }
                .clearfix {
                    clear: both;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div style="text-align: center; margin-bottom: 10px;">
                    📋 <!-- Simple icon replacement for logo -->
                </div>
            </div>
            
            <div class="memo-header">บันทึกข้อความ</div>
            
            <table class="info-table">
                <tr>
                    <td class="label">ส่วนราชการ</td>
                    <td class="underline">วิทยาลัยการอาชีพปราสาท</td>
                </tr>
                <tr>
                    <td class="label">ที่</td>
                    <td class="underline">
                        <span style="margin-right: 150px;"></span>
                        <strong>วันที่</strong> ' . $thai_date . ' 
                        <span style="margin: 0 20px; border-bottom: 1px dotted #000; padding-bottom: 2px;">' . $thai_month . '</span>
                        พ.ศ. ' . $thai_year . '
                    </td>
                </tr>
                <tr>
                    <td class="label">เรื่อง</td>
                    <td class="underline">คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ (เรียนเพิ่ม/เรียนซ้ำ) ภาคเรียนที่ ' . $request_details['semester'] . ' ปีการศึกษา ' . $request_details['academic_year'] . '</td>
                </tr>
                <tr>
                    <td class="label">เรียน</td>
                    <td class="underline">ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</td>
                </tr>
            </table>
            
            <div class="section" style="text-indent: 30px; margin: 20px 0;">
                ข้าพเจ้า (' . $request_details['name_prefix'] . ') ' . $request_details['first_name'] . ' ' . $request_details['last_name'] . ' รหัสประจำตัว ' . $request_details['student_code'] . '<br>
                ระดับชั้น ' . $request_details['education_level'] . ' ชั้นปีที่ ' . $request_details['year'] . ' สาขาวิชา ' . $request_details['major'] . '<br>
                เบอร์โทรศัพท์ที่ติดต่อได้ ' . $request_details['phone_number'] . ' มีความประสงค์ขอให้เปิดรายวิชา ดังนี้
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="5%">ที่</th>
                        <th width="12%">รหัสวิชา</th>
                        <th width="30%">ชื่อรายวิชา</th>
                        <th width="6%">ทฤษฎี</th>
                        <th width="6%">ปฏิบัติ</th>
                        <th width="6%">หน่วยกิต</th>
                        <th width="6%">ชั่วโมง</th>
                        <th width="25%">ชื่อครูประจำวิชา<br>(ให้เขียนตัวบรรจง)</th>
                    </tr>
                </thead>
                <tbody>';
        
        $i = 1;
        foreach ($request_items as $item) {
            $html .= '<tr>
                        <td>' . $i++ . '</td>
                        <td>' . htmlspecialchars($item['course_code']) . '</td>
                        <td class="text-left">' . htmlspecialchars($item['course_name']) . '</td>
                        <td>' . $item['theory_hours'] . '</td>
                        <td>' . $item['practice_hours'] . '</td>
                        <td>' . $item['credits'] . '</td>
                        <td>' . $item['total_hours'] . '</td>
                        <td class="text-left">' . htmlspecialchars($item['teacher_name']) . '</td>
                    </tr>';
        }
        
        // Add empty rows if less than 8 items
        $empty_rows = 8 - count($request_items);
        for ($j = 0; $j < $empty_rows; $j++) {
            $html .= '<tr>
                        <td>' . $i++ . '</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>';
        }
        
        $html .= '
                    <tr>
                        <td colspan="2" style="text-align: right; font-weight: bold;">รวม</td>
                        <td style="text-align: center; font-weight: bold;">' . count($request_items) . ' วิชา</td>
                        <td colspan="5">&nbsp;</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="section" style="text-indent: 30px; margin: 20px 0;">
                จึงเรียนมาเพื่อโปรดพิจารณา
            </div>
            
            <div class="signature-section">
                <div style="margin-bottom: 50px;"></div>
                <div>................................................. ผู้ยื่นคำร้อง</div>
                <div>(' . htmlspecialchars($request_details['name_prefix'] . $request_details['first_name'] . ' ' . $request_details['last_name']) . ')</div>
                <div>นักเรียน นักศึกษา</div>
            </div>
            
            <div class="approval-section">
                <div class="approval-box">
                    <div><strong>1) เรียน ผู้อำนวยการ</strong></div>
                    <div>เพื่อโปรดพิจารณา</div>
                    <div style="margin: 30px 0;"></div>
                    <div>................................................. ครูที่ปรึกษา</div>
                    <div>(.................................................)</div>
                </div>
                
                <div class="approval-box">
                    <div><strong>2) เรียน ผู้อำนวยการ</strong></div>
                    <div>เพื่อโปรดพิจารณา</div>
                    <div style="margin: 30px 0;"></div>
                    <div>................................................. หัวหน้าแผนกวิชา</div>
                    <div>(.................................................)</div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="approval-box">
                    <div><strong>3) เรียน ผู้อำนวยการ</strong></div>
                    <div style="margin-top: 10px;">
                        <span class="checkbox' . (in_array($request_details['status'], ['approved_curriculum', 'approved_deputy', 'approved']) ? ' checked' : '') . '"></span> เห็นสมควรอนุมัติ<br>
                        <span class="checkbox' . ($request_details['status'] === 'rejected' ? ' checked' : '') . '"></span> ไม่สมควรอนุมัติ
                    </div>
                    <div style="margin: 20px 0;"></div>
                    <div>................................................. หัวหน้างานพัฒนาหลักสูตรฯ</div>
                    <div>(นายบุญลอด โคตรใต้)</div>
                    <div>............/................/................</div>
                </div>
                
                <div class="approval-box">
                    <div><strong>4) เรียน ผู้อำนวยการ</strong></div>
                    <div style="margin-top: 10px;">
                        <span class="checkbox' . (in_array($request_details['status'], ['approved_deputy', 'approved']) ? ' checked' : '') . '"></span> เห็นสมควรอนุมัติ<br>
                        <span class="checkbox' . ($request_details['status'] === 'rejected' ? ' checked' : '') . '"></span> ไม่สมควรอนุมัติ เนื่องจาก..................................
                    </div>
                    <div style="margin: 20px 0;"></div>
                    <div>................................................. รองผู้อำนวยการฝ่ายวิชาการ</div>
                    <div>(นายสุทิศ รวดเร็ว)</div>
                    <div>............/................/................</div>
                </div>
            </div>
            
            <div class="clearfix"></div>
            
            <div class="final-approval">
                <div style="font-weight: bold; margin-bottom: 15px;">คำพิจารณาสั่งการฯ ของผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
                <div style="margin: 15px 0;">
                    <span class="checkbox' . ($request_details['status'] === 'approved' ? ' checked' : '') . '"></span> อนุมัติ และมอบ<br>
                    <div style="margin-left: 20px; margin-top: 5px;">
                        1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน<br>
                        2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน<br>
                        3) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ
                    </div>
                    <span class="checkbox' . ($request_details['status'] === 'rejected' ? ' checked' : '') . '"></span> ไม่อนุมัติ เนื่องจาก' . ($request_details['status'] === 'rejected' && !empty($request_details['rejected_reason']) ? $request_details['rejected_reason'] : '......................................................') . '
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <div style="margin-bottom: 50px;"></div>
                    <div>(นายชูศักดิ์ ขุ่ยขะ)</div>
                    <div>ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
                    <div>............../................/................</div>
                </div>
            </div>
            
            <div class="footer">
                "เรียนดี มีความสุข"
            </div>
        </body>
        </html>';
        
        // Write HTML to PDF
        try {
            $this->mpdf->WriteHTML($html);
            
            // Output PDF (I = inline view, D = download)
            $this->mpdf->Output('คำร้องขอเปิดรายวิชา_' . $request_id . '.pdf', 'I');
            
        } catch (Exception $e) {
            // If still having issues, output as HTML
            echo $html;
        }
        
        return true;
    }
    
    // Generate course summary report PDF
    public function generateCourseSummaryPDF() {
        // Include necessary classes
        include_once '../classes/CourseRequest.php';
        
        // Create course request object
        $courseRequest = new CourseRequest($this->conn);
        
        // Get request summary by course
        $courseSummary = $courseRequest->getRequestSummaryByCourse();
        
        // Get total requests
        $totalRequests = $courseRequest->getTotalRequests();
        $pendingRequests = $courseRequest->getRequestsByStatus('pending');
        $approvedRequests = $courseRequest->getRequestsByStatus('approved');
        $rejectedRequests = $courseRequest->getRequestsByStatus('rejected');
        
        // Set PDF properties
        $this->mpdf->SetTitle('รายงานสรุปคำขอเปิดรายวิชา');
        
        // HTML content for PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>รายงานสรุปคำขอเปิดรายวิชา</title>
            <style>
                body {
                    font-family: tahoma, sans-serif;
                    font-size: 14pt;
                    line-height: 1.4;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .report-title {
                    text-align: center;
                    font-weight: bold;
                    font-size: 18pt;
                    margin-bottom: 20px;
                }
                .summary-box {
                    width: 23%;
                    float: left;
                    margin-right: 2%;
                    margin-bottom: 15px;
                    border: 1px solid #ddd;
                    padding: 10px;
                    text-align: center;
                }
                .summary-title {
                    font-weight: bold;
                    margin-bottom: 5px;
                    font-size: 12pt;
                }
                .summary-value {
                    font-size: 20pt;
                    font-weight: bold;
                }
                .clearfix {
                    clear: both;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th {
                    background-color: #f2f2f2;
                    text-align: center;
                    font-weight: bold;
                    padding: 8px 4px;
                    font-size: 12pt;
                }
                td {
                    padding: 6px 4px;
                    font-size: 12pt;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                    font-size: 12pt;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div style="text-align: center; margin-bottom: 10px;">
                    📊 <!-- Simple icon replacement for logo -->
                </div>
            </div>
            
            <div class="report-title">รายงานสรุปคำขอเปิดรายวิชา</div>
            
            <div class="section">
                <div class="summary-box">
                    <div class="summary-title">คำขอทั้งหมด</div>
                    <div class="summary-value">' . $totalRequests . '</div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">รอดำเนินการ</div>
                    <div class="summary-value" style="color: #ff9800;">' . $pendingRequests . '</div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">อนุมัติแล้ว</div>
                    <div class="summary-value" style="color: #4caf50;">' . $approvedRequests . '</div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">ไม่อนุมัติ</div>
                    <div class="summary-value" style="color: #f44336;">' . $rejectedRequests . '</div>
                </div>
                
                <div class="clearfix"></div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">สรุปตามรายวิชา</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th width="5%">ลำดับ</th>
                            <th width="15%">รหัสวิชา</th>
                            <th width="35%">ชื่อรายวิชา</th>
                            <th width="11%">จำนวนคำขอ</th>
                            <th width="11%">รอดำเนินการ</th>
                            <th width="11%">อนุมัติแล้ว</th>
                            <th width="12%">ไม่อนุมัติ</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (count($courseSummary) > 0) {
            $i = 1;
            foreach ($courseSummary as $course) {
                $html .= '<tr>
                            <td style="text-align: center;">' . $i++ . '</td>
                            <td style="text-align: center;">' . htmlspecialchars($course['course_code']) . '</td>
                            <td>' . htmlspecialchars($course['course_name']) . '</td>
                            <td style="text-align: center;">' . $course['total_requests'] . '</td>
                            <td style="text-align: center;">' . $course['pending_count'] . '</td>
                            <td style="text-align: center;">' . $course['approved_count'] . '</td>
                            <td style="text-align: center;">' . $course['rejected_count'] . '</td>
                        </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" style="text-align: center;">ไม่พบข้อมูล</td></tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="footer">
                รายงานนี้ออกจากระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท<br>
                วันที่พิมพ์: ' . date('d/m/Y H:i:s') . '
            </div>
        </body>
        </html>';
        
        // Write HTML to PDF
        try {
            $this->mpdf->WriteHTML($html);
            $this->mpdf->Output('รายงานสรุปคำขอเปิดรายวิชา.pdf', 'I');
        } catch (Exception $e) {
            echo $html;
        }
        
        return true;
    }
    
    // Generate schedule PDF
    public function generateSchedulePDF($semester, $academic_year, $type = 'class') {
        // Include necessary classes
        include_once '../classes/ClassSchedule.php';
        include_once '../classes/Teacher.php';
        
        // Create class schedule object
        $schedule = new ClassSchedule($this->conn);
        $schedule->semester = $semester ?: '1';
        $schedule->academic_year = $academic_year ?: date('Y') + 543;
        
        // Get schedules by semester
        $all_schedules = $schedule->getSchedulesBySemester();
        
        if (empty($all_schedules)) {
            echo '<div style="text-align: center; margin: 50px; font-size: 18pt;">ไม่พบข้อมูลตารางเรียนสำหรับภาคเรียนนี้</div>';
            return false;
        }
        
        // Set PDF properties
        $this->mpdf->SetTitle('ตารางเรียน ภาคเรียนที่ ' . $schedule->semester . ' ปีการศึกษา ' . $schedule->academic_year);
        
        // Process schedules into a structured format
        $schedule_by_time_and_day = [];
        
        foreach ($all_schedules as $sch) {
            $start_time = date('H:i', strtotime($sch['start_time']));
            $end_time = date('H:i', strtotime($sch['end_time']));
            $day = $sch['day_of_week'];
            
            // Create a key for each hour that this schedule spans
            $start_hour_int = (int)date('H', strtotime($start_time));
            $end_hour_int = (int)date('H', strtotime($end_time));
            
            for ($h = $start_hour_int; $h < $end_hour_int; $h++) {
                $time_key = sprintf("%02d:00", $h);
                if (!isset($schedule_by_time_and_day[$time_key])) {
                    $schedule_by_time_and_day[$time_key] = [];
                }
                $schedule_by_time_and_day[$time_key][$day] = $sch;
            }
        }
        
        // HTML content for class schedule PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>ตารางเรียน</title>
            <style>
                body {
                    font-family: tahoma, sans-serif;
                    font-size: 12pt;
                    line-height: 1.3;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .report-title {
                    text-align: center;
                    font-weight: bold;
                    font-size: 16pt;
                    margin-bottom: 10px;
                }
                .report-subtitle {
                    text-align: center;
                    font-size: 14pt;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th {
                    background-color: #f2f2f2;
                    text-align: center;
                    font-weight: bold;
                    padding: 6px 2px;
                    font-size: 10pt;
                }
                td {
                    padding: 4px 2px;
                    font-size: 9pt;
                    vertical-align: top;
                }
                .time-cell {
                    background-color: #f2f2f2;
                    font-weight: bold;
                    text-align: center;
                    font-size: 10pt;
                }
                .schedule-item {
                    background-color: #e3f2fd;
                    padding: 2px;
                    border-radius: 2px;
                    margin-bottom: 2px;
                    font-size: 8pt;
                    line-height: 1.2;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                    font-size: 10pt;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div style="text-align: center; margin-bottom: 10px;">
                    📅 <!-- Simple icon replacement for logo -->
                </div>
            </div>
            
            <div class="report-title">ตารางเรียน</div>
            <div class="report-subtitle">ภาคเรียนที่ ' . $schedule->semester . ' ปีการศึกษา ' . $schedule->academic_year . '</div>
            
            <table>
                <thead>
                    <tr>
                        <th width="8%">เวลา/วัน</th>
                        <th width="13%">จันทร์</th>
                        <th width="13%">อังคาร</th>
                        <th width="13%">พุธ</th>
                        <th width="13%">พฤหัสบดี</th>
                        <th width="13%">ศุกร์</th>
                        <th width="13%">เสาร์</th>
                        <th width="13%">อาทิตย์</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Define time slots (8:00 - 17:00, 1 hour intervals)
        $start_hour = 8;
        $end_hour = 17;
        
        // Generate rows for each hour
        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            $time_key = sprintf("%02d:00", $hour);
            $next_hour = sprintf("%02d:00", $hour + 1);
            $html .= "<tr>";
            $html .= "<td class='time-cell'>{$time_key}-{$next_hour}</td>";
            
            // Generate cells for each day
            for ($day = 1; $day <= 7; $day++) {
                $html .= "<td>";
                if (isset($schedule_by_time_and_day[$time_key][$day])) {
                    $sch = $schedule_by_time_and_day[$time_key][$day];
                    $html .= "<div class='schedule-item'>";
                    $html .= "<strong>" . htmlspecialchars($sch['course_code']) . "</strong><br>";
                    $html .= htmlspecialchars($sch['course_name']) . "<br>";
                    $html .= "อาจารย์: " . htmlspecialchars($sch['teacher_name']) . "<br>";
                    $html .= "ห้อง: " . htmlspecialchars($sch['classroom']) . "<br>";
                    $html .= date('H:i', strtotime($sch['start_time'])) . "-" . date('H:i', strtotime($sch['end_time']));
                    $html .= "</div>";
                }
                $html .= "</td>";
            }
            
            $html .= "</tr>";
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                รายงานนี้ออกจากระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท<br>
                วันที่พิมพ์: ' . date('d/m/Y H:i:s') . '
            </div>
        </body>
        </html>';
        
        // Write HTML to PDF
        try {
            $this->mpdf->WriteHTML($html);
            $this->mpdf->Output('ตารางเรียน_ภาคเรียน' . $schedule->semester . '_' . $schedule->academic_year . '.pdf', 'I');
        } catch (Exception $e) {
            echo $html;
        }
        
        return true;
    }
}
?>