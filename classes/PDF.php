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
        
        // Configure mPDF
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        
        // Create new mPDF instance with custom configuration
        $this->mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'fontDir' => array_merge($fontDirs, [
                '../assets/fonts',
            ]),
            'fontdata' => $fontData + [
                'thsarabun' => [
                    'R' => 'THSarabun.ttf',
                    'B' => 'THSarabunBold.ttf',
                    'I' => 'THSarabunItalic.ttf',
                    'BI' => 'THSarabunBoldItalic.ttf',
                ]
            ],
            'default_font' => 'thsarabun'
        ]);
        
        // Set document information
        $this->mpdf->SetTitle('ระบบขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท');
        $this->mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
        $this->mpdf->SetCreator('ระบบขอเปิดรายวิชา');
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
        
        // Start capturing output buffer
        ob_start();
        
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
        
        // HTML content for PDF
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ</title>
            <style>
                body {
                    font-family: 'thsarabun';
                    font-size: 16pt;
                    line-height: 1.3;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .memo-header {
                    text-align: center;
                    font-weight: bold;
                    font-size: 20pt;
                    margin-bottom: 10px;
                }
                .section {
                    margin-bottom: 15px;
                }
                .section-title {
                    font-weight: bold;
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
                    padding: 5px;
                }
                td {
                    padding: 5px;
                    text-align: center;
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
                    width: 49%;
                    float: left;
                    margin-bottom: 20px;
                }
                .final-approval {
                    clear: both;
                    border: 1px solid black;
                    padding: 10px;
                    margin-top: 20px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                }
                .checkbox {
                    border: 1px solid black;
                    width: 12px;
                    height: 12px;
                    display: inline-block;
                    margin-right: 5px;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="../assets/images/logo.png" width="60" />
            </div>
            
            <div class="memo-header">บันทึกข้อความ</div>
            
            <div class="section">
                <table style="border: none;">
                    <tr style="border: none;">
                        <td style="border: none; text-align: left; width: 15%;"><b>ส่วนราชการ</b></td>
                        <td style="border: none; text-align: left; border-bottom: 1px dotted #000;">วิทยาลัยการอาชีพปราสาท</td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: left;"><b>ที่</b></td>
                        <td style="border: none; text-align: left; border-bottom: 1px dotted #000;">
                            <span style="margin-right: 100px;"></span>
                            <b>วันที่</b> <?php echo $thai_date; ?> 
                            <span style="margin-right: 10px;"></span>
                            <span style="border-bottom: 1px dotted #000;"><?php echo $thai_month; ?></span>
                            <span style="margin-right: 10px;"></span>
                            พ.ศ. <?php echo $thai_year; ?>
                        </td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: left;"><b>เรื่อง</b></td>
                        <td style="border: none; text-align: left; border-bottom: 1px dotted #000;">คำร้องขอเปิดรายวิชาภาคเรียนพิเศษ (เรียนเพิ่ม/เรียนซ้ำ) ภาคเรียนที่ <?php echo $request_details['semester']; ?> ปีการศึกษา <?php echo $request_details['academic_year']; ?></td>
                    </tr>
                    <tr style="border: none;">
                        <td style="border: none; text-align: left;"><b>เรียน</b></td>
                        <td style="border: none; text-align: left; border-bottom: 1px dotted #000;">ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <div style="text-indent: 50px;">
                    ข้าพเจ้า (<?php echo $request_details['name_prefix']; ?>) <?php echo $request_details['first_name'] . ' ' . $request_details['last_name']; ?> รหัสประจำตัว <?php echo $request_details['student_code']; ?><br>
                    ระดับชั้น <?php echo $request_details['education_level']; ?> ชั้นปีที่ <?php echo $request_details['year']; ?> สาขาวิชา <?php echo $request_details['major']; ?><br>
                    เบอร์โทรศัพท์ที่ติดต่อได้ <?php echo $request_details['phone_number']; ?> มีความประสงค์ขอให้เปิดรายวิชา ดังนี้
                </div>
            </div>
            
            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th width="5%">ที่</th>
                            <th width="15%">รหัสวิชา</th>
                            <th width="40%">ชื่อรายวิชา</th>
                            <th width="5%">ทฤษฎี</th>
                            <th width="5%">ปฏิบัติ</th>
                            <th width="5%">หน่วยกิต</th>
                            <th width="5%">ชั่วโมง</th>
                            <th width="20%">ชื่อครูประจำวิชา<br>(ให้เขียนตัวบรรจง)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($request_items as $item):
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $item['course_code']; ?></td>
                            <td style="text-align: left;"><?php echo $item['course_name']; ?></td>
                            <td><?php echo $item['theory_hours']; ?></td>
                            <td><?php echo $item['practice_hours']; ?></td>
                            <td><?php echo $item['credits']; ?></td>
                            <td><?php echo $item['total_hours']; ?></td>
                            <td style="text-align: left;"><?php echo $item['teacher_name']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php
                        // Add empty rows if less than 8 items
                        $empty_rows = 8 - count($request_items);
                        for ($j = 0; $j < $empty_rows; $j++):
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right;"><b>รวม</b></td>
                            <td style="text-align: center;"><?php echo count($request_items); ?> วิชา</td>
                            <td colspan="5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="section" style="text-indent: 50px;">
                จึงเรียนมาเพื่อโปรดพิจารณา
            </div>
            
            <div class="signature-section">
                <div style="margin-bottom: 40px;"></div>
                <div>................................................. ผู้ยื่นคำร้อง</div>
                <div>(<?php echo $request_details['name_prefix'] . $request_details['first_name'] . ' ' . $request_details['last_name']; ?>)</div>
                <div>นักเรียน นักศึกษา</div>
            </div>
            
            <div class="approval-section">
                <div class="approval-box">
                    <div>1) เรียน ผู้อำนวยการ</div>
                    <div>เพื่อโปรดพิจารณา</div>
                    <div style="margin-bottom: 40px;"></div>
                    <div>................................................. ครูที่ปรึกษา</div>
                    <div>(.................................................)</div>
                </div>
                
                <div class="approval-box">
                    <div>2) เรียน ผู้อำนวยการ</div>
                    <div>เพื่อโปรดพิจารณา</div>
                    <div style="margin-bottom: 40px;"></div>
                    <div>................................................. หัวหน้าแผนกวิชา</div>
                    <div>(.................................................)</div>
                </div>
                
                <div class="approval-box">
                    <div>3) เรียน ผู้อำนวยการ</div>
                    <div>
                        <div style="margin-bottom: 10px;">
                            <span class="checkbox"></span> เห็นสมควรอนุมัติ<br>
                            <span class="checkbox"></span> ไม่สมควรอนุมัติ
                        </div>
                        <div style="margin-bottom: 20px;"></div>
                        <div>................................................. หัวหน้างานพัฒนาหลักสูตรฯ</div>
                        <div>(นายบุญลอด โคตรใต้)</div>
                        <div>............/................/................</div>
                    </div>
                </div>
                
                <div class="approval-box">
                    <div>4) เรียน ผู้อำนวยการ</div>
                    <div>
                        <div style="margin-bottom: 10px;">
                            <span class="checkbox"></span> เห็นสมควรอนุมัติ<br>
                            <span class="checkbox"></span> ไม่สมควรอนุมัติ เนื่องจาก..................................
                        </div>
                        <div style="margin-bottom: 20px;"></div>
                        <div>................................................. รองผู้อำนวยการฝ่ายวิชาการ</div>
                        <div>(นายสุทิศ รวดเร็ว)</div>
                        <div>............/................/................</div>
                    </div>
                </div>
            </div>
            
            <div class="final-approval">
                <div><b>คำพิจารณาสั่งการฯ ของผู้อำนวยการวิทยาลัยการอาชีพปราสาท</b></div>
                <div style="margin-top: 10px;">
                    <span class="checkbox"></span> อนุมัติ และมอบ<br>
                    <span style="margin-left: 20px;">1) งานพัฒนาหลักสูตรฯ จัดตารางเรียน-ตารางสอน</span><br>
                    <span style="margin-left: 20px;">2) งานทะเบียนดำเนินการให้นักเรียน นักศึกษาลงทะเบียนเรียน</span><br>
                    <span style="margin-left: 20px;">3) แจ้งครูที่ปรึกษา ครูประจำรายวิชา และนักเรียนนักศึกษาทราบ</span><br>
                    <span class="checkbox"></span> ไม่อนุมัติ เนื่องจาก........................................................................................................................................................................
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <div style="margin-bottom: 40px;"></div>
                    <div>(นายชูศักดิ์ ขุ่ยขะ)</div>
                    <div>ผู้อำนวยการวิทยาลัยการอาชีพปราสาท</div>
                    <div>............../................/................</div>
                </div>
            </div>
            
            <div class="footer">
                "เรียนดี มีความสุข"
            </div>
        </body>
        </html>
        <?php
        
        // Get the output buffer content
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $this->mpdf->WriteHTML($html);
        
        // Output PDF (D = download, I = inline view)
        $this->mpdf->Output('คำร้องขอเปิดรายวิชา_' . $request_id . '.pdf', 'I');
        
        return true;
    }
    
    // Generate course summary report PDF
    public function generateCourseSummaryPDF() {
        // Include necessary classes
        include_once '../classes/CourseRequest.php';
        
        // Create course request object
        $courseRequest = new CourseRequest($this->conn);
        
        // Get request summary by status
        $statusSummary = $courseRequest->getRequestSummaryByStatus();
        
        // Get request summary by course
        $courseSummary = $courseRequest->getRequestSummaryByCourse();
        
        // Get total requests
        $totalRequests = $courseRequest->getTotalRequests();
        $pendingRequests = $courseRequest->getRequestsByStatus('pending');
        $approvedRequests = $courseRequest->getRequestsByStatus('approved');
        $rejectedRequests = $courseRequest->getRequestsByStatus('rejected');
        
        // Set PDF properties
        $this->mpdf->SetTitle('รายงานสรุปคำขอเปิดรายวิชา');
        
        // Start capturing output buffer
        ob_start();
        
        // HTML content for PDF
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>รายงานสรุปคำขอเปิดรายวิชา</title>
            <style>
                body {
                    font-family: 'thsarabun';
                    font-size: 16pt;
                    line-height: 1.3;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .report-title {
                    text-align: center;
                    font-weight: bold;
                    font-size: 20pt;
                    margin-bottom: 20px;
                }
                .section {
                    margin-bottom: 20px;
                }
                .section-title {
                    font-weight: bold;
                    font-size: 18pt;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
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
                    padding: 5px;
                }
                td {
                    padding: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    font-style: italic;
                    font-size: 14pt;
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
                }
                .summary-value {
                    font-size: 24pt;
                    font-weight: bold;
                }
                .clearfix {
                    clear: both;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <img src="../assets/images/logo.png" width="60" />
            </div>
            
            <div class="report-title">รายงานสรุปคำขอเปิดรายวิชา</div>
            
            <div class="section">
                <div class="summary-box">
                    <div class="summary-title">คำขอทั้งหมด</div>
                    <div class="summary-value"><?php echo $totalRequests; ?></div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">รอดำเนินการ</div>
                    <div class="summary-value" style="color: #ff9800;"><?php echo $pendingRequests; ?></div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">อนุมัติแล้ว</div>
                    <div class="summary-value" style="color: #4caf50;"><?php echo $approvedRequests; ?></div>
                </div>
                
                <div class="summary-box">
                    <div class="summary-title">ไม่อนุมัติ</div>
                    <div class="summary-value" style="color: #f44336;"><?php echo $rejectedRequests; ?></div>
                </div>
                
                <div class="clearfix"></div>
            </div>
            
            <div class="section">
                <div class="section-title">สรุปตามรายวิชา</div>
                
                <table>
                    <thead>
                        <tr>
                            <th width="5%">ลำดับ</th>
                            <th width="15%">รหัสวิชา</th>
                            <th width="40%">ชื่อรายวิชา</th>
                            <th width="10%">จำนวนคำขอ</th>
                            <th width="10%">รอดำเนินการ</th>
                            <th width="10%">อนุมัติแล้ว</th>
                            <th width="10%">ไม่อนุมัติ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($courseSummary as $course):
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $i++; ?></td>
                            <td style="text-align: center;"><?php echo $course['course_code']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td style="text-align: center;"><?php echo $course['total_requests']; ?></td>
                            <td style="text-align: center;"><?php echo $course['pending_count']; ?></td>
                            <td style="text-align: center;"><?php echo $course['approved_count']; ?></td>
                            <td style="text-align: center;"><?php echo $course['rejected_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php
                        // If no data
                        if (count($courseSummary) == 0):
                        ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">ไม่พบข้อมูล</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="footer">
                รายงานนี้ออกจากระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท<br>
                วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </body>
        </html>
        <?php
        
        // Get the output buffer content
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $this->mpdf->WriteHTML($html);
        
        // Output PDF (D = download, I = inline view)
        $this->mpdf->Output('รายงานสรุปคำขอเปิดรายวิชา.pdf', 'I');
        
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
        
        if ($type === 'teacher') {
            // Group schedules by teacher
            $teacher = new Teacher($this->conn);
            $all_teachers = $teacher->getAllTeachers();
            
            // Set PDF properties
            $this->mpdf->SetTitle('ตารางสอนอาจารย์ ภาคเรียนที่ ' . $schedule->semester . ' ปีการศึกษา ' . $schedule->academic_year);
            
            // Start capturing output buffer
            ob_start();
            
            // HTML content for teacher schedule PDF
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>ตารางสอนอาจารย์</title>
                <style>
                    body {
                        font-family: 'thsarabun';
                        font-size: 14pt;
                        line-height: 1.3;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .report-title {
                        text-align: center;
                        font-weight: bold;
                        font-size: 18pt;
                        margin-bottom: 10px;
                    }
                    .report-subtitle {
                        text-align: center;
                        font-size: 16pt;
                        margin-bottom: 20px;
                    }
                    .teacher-section {
                        margin-bottom: 30px;
                        page-break-inside: avoid;
                    }
                    .teacher-name {
                        font-weight: bold;
                        font-size: 16pt;
                        margin-bottom: 10px;
                        border-bottom: 1px solid #ddd;
                        padding-bottom: 5px;
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
                        padding: 3px;
                        font-size: 12pt;
                    }
                    td {
                        padding: 3px;
                        font-size: 12pt;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        font-style: italic;
                        font-size: 12pt;
                    }
                    .day-header {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    .page-break {
                        page-break-after: always;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="../assets/images/logo.png" width="60" />
                </div>
                
                <div class="report-title">ตารางสอนอาจารย์</div>
                <div class="report-subtitle">ภาคเรียนที่ <?php echo $schedule->semester; ?> ปีการศึกษา <?php echo $schedule->academic_year; ?></div>
                
                <?php
                // Loop through each teacher
                foreach ($all_teachers as $teacher_index => $teacher_item):
                    // Filter schedules for this teacher
                    $teacher_schedules = array_filter($all_schedules, function($item) use ($teacher_item) {
                        return $item['teacher_id'] == $teacher_item['id'];
                    });
                    
                    // Skip if no schedules
                    if (empty($teacher_schedules)) {
                        continue;
                    }
                    
                    // Sort schedules by day and time
                    usort($teacher_schedules, function($a, $b) {
                        if ($a['day_of_week'] == $b['day_of_week']) {
                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                        }
                        return $a['day_of_week'] - $b['day_of_week'];
                    });
                    
                    // Group schedules by day
                    $schedules_by_day = [];
                    foreach ($teacher_schedules as $sch) {
                        $day = $sch['day_of_week'];
                        if (!isset($schedules_by_day[$day])) {
                            $schedules_by_day[$day] = [];
                        }
                        $schedules_by_day[$day][] = $sch;
                    }
                    
                    // Add page break if not first teacher
                    if ($teacher_index > 0) {
                        echo '<div class="page-break"></div>';
                    }
                ?>
                
                <div class="teacher-section">
                    <div class="teacher-name"><?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?></div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th width="15%">วัน/เวลา</th>
                                <th width="15%">รหัสวิชา</th>
                                <th width="35%">ชื่อรายวิชา</th>
                                <th width="10%">เวลา</th>
                                <th width="10%">ห้องเรียน</th>
                                <th width="15%">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Thai day names
                            $days = [
                                1 => 'จันทร์',
                                2 => 'อังคาร',
                                3 => 'พุธ',
                                4 => 'พฤหัสบดี',
                                5 => 'ศุกร์',
                                6 => 'เสาร์',
                                7 => 'อาทิตย์'
                            ];
                            
                            // Loop through each day
                            for ($day = 1; $day <= 7; $day++):
                                if (!isset($schedules_by_day[$day])) {
                                    continue;
                                }
                                
                                echo '<tr class="day-header"><td colspan="6">วัน' . $days[$day] . '</td></tr>';
                                
                                // Loop through schedules for this day
                                foreach ($schedules_by_day[$day] as $sch):
                                    $start_time = date('H:i', strtotime($sch['start_time']));
                                    $end_time = date('H:i', strtotime($sch['end_time']));
                            ?>
                            <tr>
                                <td></td>
                                <td style="text-align: center;"><?php echo $sch['course_code']; ?></td>
                                <td><?php echo $sch['course_name']; ?></td>
                                <td style="text-align: center;"><?php echo $start_time . '-' . $end_time; ?></td>
                                <td style="text-align: center;"><?php echo $sch['classroom']; ?></td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endfor; ?>
                            
                            <?php
                            // If no data
                            if (count($teacher_schedules) == 0):
                            ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">ไม่พบข้อมูลตารางสอน</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
                
                <div class="footer">
                    รายงานนี้ออกจากระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท<br>
                    วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </body>
            </html>
            <?php
            
        } else {
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
            
            // Set PDF properties
            $this->mpdf->SetTitle('ตารางเรียน ภาคเรียนที่ ' . $schedule->semester . ' ปีการศึกษา ' . $schedule->academic_year);
            
            // Start capturing output buffer
            ob_start();
            
            // HTML content for class schedule PDF
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>ตารางเรียน</title>
                <style>
                    body {
                        font-family: 'thsarabun';
                        font-size: 14pt;
                        line-height: 1.3;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .report-title {
                        text-align: center;
                        font-weight: bold;
                        font-size: 18pt;
                        margin-bottom: 10px;
                    }
                    .report-subtitle {
                        text-align: center;
                        font-size: 16pt;
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
                        padding: 3px;
                        font-size: 12pt;
                    }
                    td {
                        padding: 3px;
                        font-size: 10pt;
                        vertical-align: top;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        font-style: italic;
                        font-size: 12pt;
                    }
                    .schedule-item {
                        background-color: #e3f2fd;
                        padding: 3px;
                        border-radius: 3px;
                        margin-bottom: 5px;
                    }
                    .time-cell {
                        background-color: #f2f2f2;
                        font-weight: bold;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="../assets/images/logo.png" width="60" />
                </div>
                
                <div class="report-title">ตารางเรียน</div>
                <div class="report-subtitle">ภาคเรียนที่ <?php echo $schedule->semester; ?> ปีการศึกษา <?php echo $schedule->academic_year; ?></div>
                
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
                    <tbody>
                        <?php
                        // Define time slots (8:00 - 17:00, 1 hour intervals)
                        $start_hour = 8;
                        $end_hour = 17;
                        
                        // Generate rows for each hour
                        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                            $time_key = sprintf("%02d:00", $hour);
                            $next_hour = sprintf("%02d:00", $hour + 1);
                            echo "<tr>";
                            echo "<td class='time-cell'>{$time_key}-{$next_hour}</td>";
                            
                            // Generate cells for each day
                            for ($day = 1; $day <= 7; $day++) {
                                echo "<td>";
                                if (isset($schedule_by_time_and_day[$time_key][$day])) {
                                    $sch = $schedule_by_time_and_day[$time_key][$day];
                                    echo "<div class='schedule-item'>";
                                    echo "<strong>{$sch['course_code']}</strong><br>";
                                    echo "{$sch['course_name']}<br>";
                                    echo "อาจารย์: {$sch['teacher_name']}<br>";
                                    echo "ห้อง: {$sch['classroom']}<br>";
                                    echo date('H:i', strtotime($sch['start_time'])) . "-" . date('H:i', strtotime($sch['end_time']));
                                    echo "</div>";
                                }
                                echo "</td>";
                            }
                            
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="footer">
                    รายงานนี้ออกจากระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท<br>
                    วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
            </body>
            </html>
            <?php
        }
        
        // Get the output buffer content
        $html = ob_get_clean();
        
        // Write HTML to PDF
        $this->mpdf->WriteHTML($html);
        
        // Output PDF (D = download, I = inline view)
        if ($type === 'teacher') {
            $this->mpdf->Output('ตารางสอนอาจารย์_ภาคเรียน' . $schedule->semester . '_' . $schedule->academic_year . '.pdf', 'I');
        } else {
            $this->mpdf->Output('ตารางเรียน_ภาคเรียน' . $schedule->semester . '_' . $schedule->academic_year . '.pdf', 'I');
        }
        
        return true;
    }
}
?>