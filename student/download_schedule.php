<?php
// student/download_schedule.php
// หน้าดาวน์โหลดตารางเรียนที่รองรับมือถือ

session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';
require_once '../mpdf/vendor/autoload.php';

// ตรวจสอบว่ามีการระบุ ID คำร้อง
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: track_status.php');
    exit;
}

$request_id = $_GET['id'];
$error = '';

// ดึงข้อมูลคำร้อง
try {
    $request = getCourseRequestById($pdo, $request_id);
    
    if (!$request) {
        $error = 'ไม่พบข้อมูลคำร้อง';
    } else if ($request['status'] !== 'อนุมัติ') {
        $error = 'คำร้องยังไม่ได้รับการอนุมัติ';
    } else {
        // ดึงข้อมูลรายละเอียดคำร้อง (รายวิชาที่ขอเปิด)
        $details = getRequestDetails($pdo, $request_id);
        
        // ดึงข้อมูลตารางเรียน
        $stmt = $pdo->prepare("
            SELECT cs.*, 
                   rd.detail_id,
                   c.course_code, 
                   c.course_name,
                   t.prefix as teacher_prefix, 
                   t.first_name as teacher_first_name, 
                   t.last_name as teacher_last_name
            FROM class_schedules cs
            JOIN request_details rd ON cs.detail_id = rd.detail_id
            JOIN courses c ON rd.course_id = c.course_id
            JOIN teachers t ON rd.teacher_id = t.teacher_id
            WHERE rd.request_id = :request_id
            ORDER BY FIELD(cs.day_of_week, 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'), cs.start_time
        ");
        $stmt->execute(['request_id' => $request_id]);
        $schedules = $stmt->fetchAll();
        
        // จัดกลุ่มตารางเรียนตามวัน
        $schedules_by_day = [];
        foreach ($schedules as $schedule) {
            if (!isset($schedules_by_day[$schedule['day_of_week']])) {
                $schedules_by_day[$schedule['day_of_week']] = [];
            }
            $schedules_by_day[$schedule['day_of_week']][] = $schedule;
        }
        
        // ตรวจสอบถ้าไม่มีตารางเรียน
        if (empty($schedules)) {
            $error = 'ยังไม่มีการจัดตารางเรียนสำหรับคำร้องนี้';
        }
    }
} catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}

// ถ้ามีการร้องขอให้พิมพ์ PDF
if (isset($_GET['print']) && $_GET['print'] === 'true' && empty($error)) {
    try {
        // เริ่มต้นสร้าง MPDF
        $mpdf = initMPDF();
        
        // กำหนดค่าเริ่มต้น
        $mpdf->SetTitle('ตารางเรียนภาคเรียนพิเศษ - ' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']);
        
        // เริ่มต้นเนื้อหา HTML
        $html = '
        <style>
            body {
                font-family: "thsarabun";
                font-size: 16pt;
            }
            h1 {
                font-size: 22pt;
                font-weight: bold;
                text-align: center;
                margin-bottom: 10pt;
            }
            h2 {
                font-size: 18pt;
                font-weight: bold;
                margin-bottom: 8pt;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15pt;
            }
            table, th, td {
                border: 1px solid #000;
            }
            th {
                font-weight: bold;
                text-align: center;
                padding: 5pt;
                background-color: #f2f2f2;
            }
            td {
                padding: 5pt;
                text-align: left;
            }
            .text-center {
                text-align: center;
            }
            .student-info {
                margin-bottom: 20pt;
            }
            .footer {
                text-align: center;
                font-size: 14pt;
                margin-top: 20pt;
            }
        </style>
        
        <h1>ตารางเรียนภาคเรียนพิเศษ</h1>
        <h2 class="text-center">ภาคเรียนที่ ' . $request['semester'] . ' ปีการศึกษา ' . $request['academic_year'] . '</h2>
        
        <div class="student-info">
            <table>
                <tr>
                    <th colspan="4">ข้อมูลนักเรียน/นักศึกษา</th>
                </tr>
                <tr>
                    <td width="25%"><strong>รหัสนักเรียน:</strong></td>
                    <td width="25%">' . $request['student_code'] . '</td>
                    <td width="25%"><strong>ชื่อ-นามสกุล:</strong></td>
                    <td width="25%">' . $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . '</td>
                </tr>
                <tr>
                    <td><strong>ระดับชั้น:</strong></td>
                    <td>' . $request['level'] . ' ปีที่ ' . $request['year'] . '</td>
                    <td><strong>สาขาวิชา:</strong></td>
                    <td>' . $request['department_name'] . '</td>
                </tr>
            </table>
        </div>
        
        <h2>รายวิชาที่ลงทะเบียน</h2>
        <table>
            <tr>
                <th width="10%">ลำดับ</th>
                <th width="15%">รหัสวิชา</th>
                <th width="45%">ชื่อรายวิชา</th>
                <th width="15%">หน่วยกิต</th>
                <th width="15%">ครูประจำวิชา</th>
            </tr>';
            
        foreach ($details as $index => $detail) {
            $html .= '
            <tr>
                <td class="text-center">' . ($index + 1) . '</td>
                <td>' . $detail['course_code'] . '</td>
                <td>' . $detail['course_name'] . '</td>
                <td class="text-center">' . $detail['credit_hours'] . '</td>
                <td>' . $detail['prefix'] . $detail['first_name'] . ' ' . $detail['last_name'] . '</td>
            </tr>';
        }
        
        $html .= '
        </table>
        
        <h2>ตารางเรียน</h2>';
        
        // สร้างตารางเรียนสำหรับแต่ละวัน
        $days_of_week = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'];
        
        foreach ($days_of_week as $day) {
            if (isset($schedules_by_day[$day])) {
                $html .= '
                <h3>วัน' . $day . '</h3>
                <table>
                    <tr>
                        <th width="20%">เวลา</th>
                        <th width="20%">รหัสวิชา</th>
                        <th width="30%">ชื่อรายวิชา</th>
                        <th width="15%">ห้องเรียน</th>
                        <th width="15%">ครูผู้สอน</th>
                    </tr>';
                
                foreach ($schedules_by_day[$day] as $schedule) {
                    $time_slot = timeThaiFormat($schedule['start_time']) . ' - ' . timeThaiFormat($schedule['end_time']);
                    $html .= '
                    <tr>
                        <td>' . $time_slot . '</td>
                        <td>' . $schedule['course_code'] . '</td>
                        <td>' . $schedule['course_name'] . '</td>
                        <td class="text-center">' . $schedule['room'] . '</td>
                        <td>' . $schedule['teacher_prefix'] . $schedule['teacher_first_name'] . '</td>
                    </tr>';
                }
                
                $html .= '
                </table>';
            }
        }
        
        $html .= '
        <div class="footer">
            <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</p>
            <p>วิทยาลัยการอาชีพปราสาท</p>
        </div>';
        
        // เพิ่มเนื้อหา HTML ลงใน MPDF
        $mpdf->WriteHTML($html);
        
        // ดาวน์โหลดไฟล์ PDF
        $filename = 'ตารางเรียน_' . $request['student_code'] . '_ภาคเรียนที่' . $request['semester'] . '_' . $request['academic_year'] . '.pdf';
        $mpdf->Output($filename, 'D');
        exit;
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาดในการสร้างไฟล์ PDF: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางเรียน - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS for Mobile -->
    <link href="../assets/css/mobile.css" rel="stylesheet">
    
    <style>
        .day-schedule {
            margin-bottom: 2rem;
        }
        
        .time-slot {
            border-left: 4px solid #0d6efd;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        
        .schedule-card {
            transition: transform 0.2s;
            margin-bottom: 1rem;
        }
        
        .schedule-card:hover {
            transform: translateY(-3px);
        }
        
        .schedule-time {
            font-weight: bold;
            color: #0d6efd;
        }
        
        .schedule-course {
            font-weight: bold;
        }
        
        .schedule-teacher {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .schedule-room {
            display: inline-block;
            background-color: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .day-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
        }
        
        .day-icon {
            margin-right: 0.5rem;
            font-size: 1.5rem;
            color: #0d6efd;
        }
        
        .info-card {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
            
            .day-header {
                break-inside: avoid;
            }
            
            .schedule-card {
                break-inside: avoid;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
        
        /* เพิ่ม CSS สำหรับมือถือ */
        @media (max-width: 767.98px) {
            .schedule-card .card-body {
                padding: 0.75rem;
            }
            
            .schedule-time {
                font-size: 0.9rem;
            }
            
            .schedule-course {
                font-size: 1rem;
                margin-bottom: 0.25rem;
            }
            
            .schedule-teacher {
                font-size: 0.85rem;
            }
            
            .schedule-room {
                font-size: 0.85rem;
                margin-top: 0.25rem;
            }
            
            .day-icon {
                font-size: 1.25rem;
            }
            
            .day-header h5 {
                font-size: 1.1rem;
                margin-bottom: 0;
            }
            
            .btn-group {
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .btn-group .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                ระบบขอเปิดรายวิชา
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="bi bi-house-door"></i> หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_form.php"><i class="bi bi-file-earmark-plus"></i> ยื่นคำร้อง</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track_status.php"><i class="bi bi-search"></i> ตรวจสอบสถานะ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-calendar2-week"></i> ตารางเรียนภาคเรียนพิเศษ</h5>
                        
                        <?php if (empty($error)): ?>
                            <div class="btn-group no-print d-none d-md-flex">
                                <a href="?id=<?php echo $request_id; ?>&print=true" class="btn btn-light btn-sm">
                                    <i class="bi bi-file-pdf"></i> ดาวน์โหลด PDF
                                </a>
                                <button onclick="window.print();" class="btn btn-light btn-sm">
                                    <i class="bi bi-printer"></i> พิมพ์
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="track_status.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-left"></i> กลับไปยังหน้าตรวจสอบสถานะ
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <h4 class="mb-1">ภาคเรียนที่ <?php echo $request['semester']; ?> ปีการศึกษา <?php echo $request['academic_year']; ?></h4>
                                <p class="text-muted">วันที่พิมพ์: <?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card info-card">
                                        <div class="card-header bg-light">
                                            <h5 class="card-title mb-0"><i class="bi bi-person-badge"></i> ข้อมูลนักเรียน/นักศึกษา</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 col-12">
                                                    <p class="mb-2"><strong>รหัสนักเรียน:</strong> <?php echo $request['student_code']; ?></p>
                                                    <p class="mb-2"><strong>ชื่อ-นามสกุล:</strong> <?php echo $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']; ?></p>
                                                </div>
                                                <div class="col-md-6 col-12">
                                                    <p class="mb-2"><strong>ระดับชั้น:</strong> <?php echo $request['level'] . ' ปีที่ ' . $request['year']; ?></p>
                                                    <p class="mb-0"><strong>สาขาวิชา:</strong> <?php echo $request['department_name']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- รายวิชาที่ลงทะเบียน -->
                            <h5 class="card-title mb-3"><i class="bi bi-book"></i> รายวิชาที่ลงทะเบียน</h5>
                            
                            <!-- สำหรับหน้าจอขนาดใหญ่ (PC) -->
                            <div class="table-responsive d-none d-md-block mb-4">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">ลำดับ</th>
                                            <th width="15%">รหัสวิชา</th>
                                            <th width="45%">ชื่อรายวิชา</th>
                                            <th width="15%" class="text-center">หน่วยกิต</th>
                                            <th width="15%">ครูประจำวิชา</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($details as $index => $detail): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $index + 1; ?></td>
                                                <td><?php echo $detail['course_code']; ?></td>
                                                <td><?php echo $detail['course_name']; ?></td>
                                                <td class="text-center"><?php echo $detail['credit_hours']; ?></td>
                                                <td><?php echo $detail['prefix'] . $detail['first_name'] . ' ' . $detail['last_name']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- สำหรับหน้าจอขนาดเล็ก (มือถือ) -->
                            <div class="d-md-none mb-4">
                                <?php foreach ($details as $index => $detail): ?>
                                    <div class="card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="schedule-course"><?php echo $detail['course_code']; ?></div>
                                                    <div><?php echo $detail['course_name']; ?></div>
                                                </div>
                                                <span class="badge bg-primary"><?php echo $detail['credit_hours']; ?> หน่วยกิต</span>
                                            </div>
                                            <div class="schedule-teacher mt-2">
                                                <i class="bi bi-person"></i> <?php echo $detail['prefix'] . $detail['first_name'] . ' ' . $detail['last_name']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <h5 class="card-title mb-3"><i class="bi bi-calendar-check"></i> ตารางเรียน</h5>
                            
                            <!-- ปุ่มสำหรับมือถือ -->
                            <div class="d-md-none no-print mb-3">
                                <div class="btn-group w-100">
                                    <a href="?id=<?php echo $request_id; ?>&print=true" class="btn btn-primary">
                                        <i class="bi bi-file-pdf"></i> ดาวน์โหลด PDF
                                    </a>
                                    <button onclick="window.print();" class="btn btn-secondary">
                                        <i class="bi bi-printer"></i> พิมพ์
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (empty($schedules)): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> ยังไม่มีการจัดตารางเรียนสำหรับคำร้องนี้
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php
                                    $days_of_week = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'];
                                    $day_icons = [
                                        'จันทร์' => 'bi-1-circle-fill',
                                        'อังคาร' => 'bi-2-circle-fill',
                                        'พุธ' => 'bi-3-circle-fill',
                                        'พฤหัสบดี' => 'bi-4-circle-fill', 
                                        'ศุกร์' => 'bi-5-circle-fill',
                                        'เสาร์' => 'bi-6-circle-fill',
                                        'อาทิตย์' => 'bi-7-circle-fill'
                                    ];
                                    
                                    foreach ($days_of_week as $day) {
                                        if (isset($schedules_by_day[$day])) {
                                            echo '<div class="col-md-12 day-schedule">';
                                            echo '<div class="day-header">';
                                            echo '<i class="bi ' . $day_icons[$day] . ' day-icon"></i>';
                                            echo '<h5>วัน' . $day . '</h5>';
                                            echo '</div>';
                                            
                                            foreach ($schedules_by_day[$day] as $schedule) {
                                                $time_slot = timeThaiFormat($schedule['start_time']) . ' - ' . timeThaiFormat($schedule['end_time']);
                                                
                                                echo '<div class="card schedule-card shadow-sm">';
                                                echo '<div class="card-body">';
                                                echo '<div class="row">';
                                                
                                                // เวลา
                                                echo '<div class="col-md-2 col-12 mb-md-0 mb-2">';
                                                echo '<div class="schedule-time"><i class="bi bi-clock"></i> ' . $time_slot . '</div>';
                                                echo '</div>';
                                                
                                                // รายละเอียดรายวิชา
                                                echo '<div class="col-md-8 col-12 mb-md-0 mb-2">';
                                                echo '<div class="schedule-course">' . $schedule['course_code'] . ' - ' . $schedule['course_name'] . '</div>';
                                                echo '<div class="schedule-teacher"><i class="bi bi-person"></i> ' . $schedule['teacher_prefix'] . $schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name'] . '</div>';
                                                echo '</div>';
                                                
                                                // ห้องเรียน
                                                echo '<div class="col-md-2 col-12 text-md-end">';
                                                echo '<div class="schedule-room"><i class="bi bi-door-open"></i> ห้อง ' . $schedule['room'] . '</div>';
                                                echo '</div>';
                                                
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                            
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 no-print">
                                <a href="track_status.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> กลับไปยังหน้าตรวจสอบสถานะ
                                </a>
                                <?php if (!empty($schedules)): ?>
                                    <a href="?id=<?php echo $request_id; ?>&print=true" class="btn btn-primary d-none d-md-inline-block">
                                        <i class="bi bi-file-pdf"></i> ดาวน์โหลด PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5 no-print">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>