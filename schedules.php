<?php
session_start();
/* แสดงผล Error */
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check if user is logged in

// Include database and necessary classes
include_once 'config/database.php';
include_once 'classes/Student.php';
include_once 'classes/ClassSchedule.php';
include_once 'classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$student = new Student($db);
$schedule = new ClassSchedule($db);
$courseRequest = new CourseRequest($db);

// Get current semester and academic year
// In production, this would likely be set in the settings table
$current_semester = "1";
$current_academic_year = "2568";

// Get student information
$student->user_id = $_SESSION['user_id'];
if (!$student->getStudentByUserId()) {
    // Redirect to profile creation if student profile doesn't exist
    header("Location: create_profile.php");
    exit;
}

// Get approved course requests for this student
$student_course_requests = $courseRequest->getRequestsByStudentId($student->id);
$approved_requests = array_filter($student_course_requests, function($request) {
    return $request['status'] === 'approved';
});

// Get course IDs from approved requests
$course_ids = [];
foreach ($approved_requests as $request) {
    $courseRequest->id = $request['id'];
    $items = $courseRequest->getRequestItems();
    foreach ($items as $item) {
        $course_ids[] = $item['course_id'];
    }
}

// Get schedules for these courses
$schedules = [];
if (!empty($course_ids)) {
    $schedule->semester = $current_semester;
    $schedule->academic_year = $current_academic_year;
    $schedules = $schedule->getSchedulesByCourses($course_ids);
}

// Function to get day name in Thai
function getDayName($day_number) {
    $days = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์'
    ];
    
    return $days[$day_number] ?? '';
}

// Function to format time
function formatTime($time) {
    return date('H:i', strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางเรียน - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once '../includes/student_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">ตารางเรียน ภาคเรียนที่ <?php echo $current_semester; ?> ปีการศึกษา <?php echo $current_academic_year; ?></h5>
                        <a href="../reports/schedule_pdf.php" class="btn btn-sm btn-light" target="_blank">
                            <i class="fas fa-print me-2"></i> พิมพ์ตารางเรียน
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedules)): ?>
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>
                                    ยังไม่มีตารางเรียน หรือยังไม่มีคำขอเปิดรายวิชาที่ได้รับการอนุมัติ
                                    <a href="course_request.php" class="alert-link ms-2">ยื่นคำขอเปิดรายวิชา</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Week Schedule View -->
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-primary">
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
                                        
                                        // Process schedules into a structured format
                                        $schedule_by_time_and_day = [];
                                        
                                        foreach ($schedules as $sch) {
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
                                        
                                        // Generate rows for each hour
                                        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                                            $time_key = sprintf("%02d:00", $hour);
                                            $next_hour = sprintf("%02d:00", $hour + 1);
                                            echo "<tr>";
                                            echo "<td class='table-light text-center align-middle'><strong>{$time_key}-{$next_hour}</strong></td>";
                                            
                                            // Generate cells for each day
                                            for ($day = 1; $day <= 7; $day++) {
                                                echo "<td>";
                                                if (isset($schedule_by_time_and_day[$time_key][$day])) {
                                                    $sch = $schedule_by_time_and_day[$time_key][$day];
                                                    echo "<div class='p-1 bg-info text-white rounded'>";
                                                    echo "<div class='small fw-bold'>{$sch['course_code']}</div>";
                                                    echo "<div class='small'>{$sch['course_name']}</div>";
                                                    echo "<div class='small'>อาจารย์: {$sch['teacher_name']}</div>";
                                                    echo "<div class='small'>ห้อง: {$sch['classroom']}</div>";
                                                    echo "<div class='small'>" . formatTime($sch['start_time']) . " - " . formatTime($sch['end_time']) . "</div>";
                                                    echo "</div>";
                                                }
                                                echo "</td>";
                                            }
                                            
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- List View -->
                            <h5 class="mb-3">รายการวิชาทั้งหมด</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-primary">
                                        <tr>
                                            <th width="5%">ลำดับ</th>
                                            <th width="15%">รหัสวิชา</th>
                                            <th width="25%">ชื่อรายวิชา</th>
                                            <th width="10%">หน่วยกิต</th>
                                            <th width="15%">วัน</th>
                                            <th width="15%">เวลา</th>
                                            <th width="15%">ห้องเรียน</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        // Sort schedules by day and time
                                        usort($schedules, function($a, $b) {
                                            if ($a['day_of_week'] == $b['day_of_week']) {
                                                return strtotime($a['start_time']) - strtotime($b['start_time']);
                                            }
                                            return $a['day_of_week'] - $b['day_of_week'];
                                        });
                                        
                                        foreach ($schedules as $sch):
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i++; ?></td>
                                            <td class="text-center"><?php echo $sch['course_code']; ?></td>
                                            <td><?php echo $sch['course_name']; ?></td>
                                            <td class="text-center"><?php echo $sch['credits']; ?></td>
                                            <td><?php echo 'วัน' . getDayName($sch['day_of_week']); ?></td>
                                            <td><?php echo formatTime($sch['start_time']) . ' - ' . formatTime($sch['end_time']); ?></td>
                                            <td><?php echo $sch['classroom']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>