// admin/schedule_management.php
<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/ClassSchedule.php';
include_once '../classes/Teacher.php';
include_once '../classes/Course.php';
include_once '../classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$schedule = new ClassSchedule($db);
$teacher = new Teacher($db);
$course = new Course($db);
$courseRequest = new CourseRequest($db);

// Get current semester and academic year
// In production, this would likely be set in the settings table
$current_semester = "1";
$current_academic_year = "2568";

// Process form submission
$message = '';
$message_type = '';

// Handle adding teacher availability
if (isset($_POST['add_availability'])) {
    $teacher_id = $_POST['teacher_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Create teacher object
    $selected_teacher = new Teacher($db);
    $selected_teacher->id = $teacher_id;
    
    // Add availability
    if ($selected_teacher->addAvailability($day_of_week, $start_time, $end_time)) {
        $message = 'เพิ่มช่วงเวลาว่างเรียบร้อยแล้ว';
        $message_type = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการเพิ่มช่วงเวลาว่าง';
        $message_type = 'danger';
    }
}

// Handle removing teacher availability
if (isset($_POST['remove_availability'])) {
    $teacher_id = $_POST['teacher_id'];
    $availability_id = $_POST['availability_id'];
    
    // Create teacher object
    $selected_teacher = new Teacher($db);
    $selected_teacher->id = $teacher_id;
    
    // Remove availability
    if ($selected_teacher->deleteAvailability($availability_id)) {
        $message = 'ลบช่วงเวลาว่างเรียบร้อยแล้ว';
        $message_type = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการลบช่วงเวลาว่าง';
        $message_type = 'danger';
    }
}

// Handle generating schedule
if (isset($_POST['generate_schedule'])) {
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];
    
    // 1. Get all approved course requests for the semester
    $query = "SELECT cr.id, cr.student_id, cr.semester, cr.academic_year
              FROM course_requests cr
              WHERE cr.status = 'approved' 
              AND cr.semester = :semester 
              AND cr.academic_year = :academic_year";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':semester', $semester);
    $stmt->bindParam(':academic_year', $academic_year);
    $stmt->execute();
    
    $approved_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Get course items from each request
    $courses_to_schedule = [];
    foreach ($approved_requests as $request) {
        $courseRequest->id = $request['id'];
        $items = $courseRequest->getRequestItems();
        
        foreach ($items as $item) {
            $course_id = $item['course_id'];
            $teacher_id = $item['teacher_id'];
            
            // Check if this course is already in the array
            $found = false;
            foreach ($courses_to_schedule as $c) {
                if ($c['course_id'] == $course_id && $c['teacher_id'] == $teacher_id) {
                    $found = true;
                    break;
                }
            }
            
            // If not found, add it
            if (!$found) {
                $courses_to_schedule[] = [
                    'course_id' => $course_id,
                    'teacher_id' => $teacher_id,
                    'student_count' => 1
                ];
            } else {
                // Increment student count for existing course
                foreach ($courses_to_schedule as &$c) {
                    if ($c['course_id'] == $course_id && $c['teacher_id'] == $teacher_id) {
                        $c['student_count']++;
                        break;
                    }
                }
            }
        }
    }
    
    // 3. Get teacher availability for each course
    $scheduleResults = [];
    $scheduleErrors = [];
    
    foreach ($courses_to_schedule as $courseToSchedule) {
        $course_id = $courseToSchedule['course_id'];
        $teacher_id = $courseToSchedule['teacher_id'];
        
        // Get course details
        $course_obj = new Course($db);
        $course_obj->id = $course_id;
        $course_obj->getCourseById();
        
        // Get teacher availability
        $teacherObj = new Teacher($db);
        $teacherObj->id = $teacher_id;
        $teacherObj->getTeacherById();
        $availability = $teacherObj->getTeacherAvailability();
        
        // If teacher has no availability, continue to next course
        if (empty($availability)) {
            $scheduleErrors[] = "ไม่พบช่วงเวลาว่างสำหรับครู " . $teacherObj->name_prefix . $teacherObj->first_name . " " . $teacherObj->last_name . " (รายวิชา " . $course_obj->course_code . ")";
            continue;
        }
        
        // Calculate total hours needed based on course credit
        $totalHoursNeeded = $course_obj->total_hours;
        
        // Find suitable time slots
        $suitableSlots = [];
        
        // Sort availability by day and time
        usort($availability, function($a, $b) {
            if ($a['day_of_week'] == $b['day_of_week']) {
                return strtotime($a['start_time']) - strtotime($b['start_time']);
            }
            return $a['day_of_week'] - $b['day_of_week'];
        });
        
        // Try to find suitable slots to fit the course hours
        foreach ($availability as $slot) {
            $slotStartTime = strtotime($slot['start_time']);
            $slotEndTime = strtotime($slot['end_time']);
            
            // Calculate duration in hours
            $durationHours = ($slotEndTime - $slotStartTime) / 3600;
            
            // If this slot can fit part or all of the course hours
            if ($durationHours > 0) {
                // Check if this slot conflicts with existing schedules
                $schedule->day_of_week = $slot['day_of_week'];
                $schedule->start_time = $slot['start_time'];
                $schedule->end_time = $slot['end_time'];
                $schedule->teacher_id = $teacher_id;
                $schedule->semester = $semester;
                $schedule->academic_year = $academic_year;
                
                $conflicts = $schedule->checkConflicts();
                
                if (!$conflicts) {
                    $suitableSlots[] = [
                        'day_of_week' => $slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'duration' => $durationHours
                    ];
                    
                    // If we've found enough slots to fit the course hours, stop looking
                    $totalDuration = 0;
                    foreach ($suitableSlots as $suitableSlot) {
                        $totalDuration += $suitableSlot['duration'];
                    }
                    
                    if ($totalDuration >= $totalHoursNeeded) {
                        break;
                    }
                }
            }
        }
        
        // If we couldn't find suitable slots for the full course duration
        $totalDuration = 0;
        foreach ($suitableSlots as $suitableSlot) {
            $totalDuration += $suitableSlot['duration'];
        }
        
        if ($totalDuration < $totalHoursNeeded) {
            $scheduleErrors[] = "ไม่สามารถจัดตารางเรียนสำหรับรายวิชา " . $course_obj->course_code . " ได้เนื่องจากเวลาว่างของครูไม่เพียงพอ";
            continue;
        }
        
        // Create schedule for each suitable slot
        $hoursScheduled = 0;
        $classrooms = ["301", "302", "303", "304", "305", "IT-Lab"];
        
        foreach ($suitableSlots as $slot) {
            if ($hoursScheduled >= $totalHoursNeeded) {
                break;
            }
            
            // Determine how many hours to schedule in this slot
            $hoursToSchedule = min($slot['duration'], $totalHoursNeeded - $hoursScheduled);
            
            // Calculate end time based on hours to schedule
            $endTime = date('H:i:s', strtotime($slot['start_time']) + ($hoursToSchedule * 3600));
            
            // Select a random classroom
            $classroom = $classrooms[array_rand($classrooms)];
            
            // Create schedule entry
            $schedule->course_id = $course_id;
            $schedule->teacher_id = $teacher_id;
            $schedule->day_of_week = $slot['day_of_week'];
            $schedule->start_time = $slot['start_time'];
            $schedule->end_time = $endTime;
            $schedule->classroom = $classroom;
            $schedule->semester = $semester;
            $schedule->academic_year = $academic_year;
            
            if ($schedule->create()) {
                $scheduleResults[] = [
                    'course_code' => $course_obj->course_code,
                    'course_name' => $course_obj->course_name,
                    'teacher_name' => $teacherObj->name_prefix . $teacherObj->first_name . " " . $teacherObj->last_name,
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $endTime,
                    'classroom' => $classroom
                ];
                
                $hoursScheduled += $hoursToSchedule;
            } else {
                $scheduleErrors[] = "เกิดข้อผิดพลาดในการสร้างตารางเรียนสำหรับรายวิชา " . $course_obj->course_code;
            }
        }
    }
    
    // Prepare message about the results
    if (!empty($scheduleResults)) {
        $message = 'สร้างตารางเรียนสำเร็จจำนวน ' . count($scheduleResults) . ' รายการ';
        if (!empty($scheduleErrors)) {
            $message .= ' แต่พบข้อผิดพลาด ' . count($scheduleErrors) . ' รายการ';
            $message_type = 'warning';
        } else {
            $message_type = 'success';
        }
    } else {
        $message = 'ไม่สามารถสร้างตารางเรียนได้ พบข้อผิดพลาด ' . count($scheduleErrors) . ' รายการ';
        $message_type = 'danger';
    }
}

// Get all teachers for dropdowns
$all_teachers = $teacher->getAllTeachers();

// Load specific teacher availability if selected
$selectedTeacher = null;
$teacherAvailability = [];

if (isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id'])) {
    $selectedTeacher = new Teacher($db);
    $selectedTeacher->id = $_GET['teacher_id'];
    
    if ($selectedTeacher->getTeacherById()) {
        $teacherAvailability = $selectedTeacher->getTeacherAvailability();
    }
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
    <title>จัดการตารางเรียน - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="../assets/css/select2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once '../includes/admin_sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">จัดการตารางเรียนและช่วงเวลาว่างของครู</h1>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                    <?php if (!empty($scheduleErrors)): ?>
                    <ul class="mt-2 mb-0">
                        <?php foreach ($scheduleErrors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="scheduleTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="teacher-tab" data-bs-toggle="tab" data-bs-target="#teacher-availability" type="button" role="tab" aria-controls="teacher-availability" aria-selected="true">
                                            <i class="fas fa-user-clock me-2"></i>ช่วงเวลาว่างของครู
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate-schedule" type="button" role="tab" aria-controls="generate-schedule" aria-selected="false">
                                            <i class="fas fa-calendar-plus me-2"></i>สร้างตารางเรียนอัตโนมัติ
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="scheduleTabContent">
                                    <!-- Teacher Availability Tab -->
                                    <div class="tab-pane fade show active" id="teacher-availability" role="tabpanel" aria-labelledby="teacher-tab">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card">
                                                    <div class="card-header bg-primary text-white">
                                                        <h5 class="card-title mb-0">เลือกครู</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <div class="mb-3">
                                                                <label for="teacher_id" class="form-label">ครูผู้สอน</label>
                                                                <select class="form-select select2" id="teacher_id" name="teacher_id" required>
                                                                    <option value="">-- เลือกครูผู้สอน --</option>
                                                                    <?php foreach ($all_teachers as $t): ?>
                                                                    <option value="<?php echo $t['id']; ?>" <?php echo (isset($_GET['teacher_id']) && $_GET['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo $t['name_prefix'] . $t['first_name'] . ' ' . $t['last_name']; ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="d-grid">
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="fas fa-search me-2"></i> แสดงข้อมูล
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($selectedTeacher): ?>
                                                <div class="card mt-4">
                                                    <div class="card-header bg-success text-white">
                                                        <h5 class="card-title mb-0">เพิ่มช่วงเวลาว่าง</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="teacher_id" value="<?php echo $selectedTeacher->id; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="day_of_week" class="form-label">วัน</label>
                                                                <select class="form-select" id="day_of_week" name="day_of_week" required>
                                                                    <option value="">-- เลือกวัน --</option>
                                                                    <option value="1">วันจันทร์</option>
                                                                    <option value="2">วันอังคาร</option>
                                                                    <option value="3">วันพุธ</option>
                                                                    <option value="4">วันพฤหัสบดี</option>
                                                                    <option value="5">วันศุกร์</option>
                                                                    <option value="6">วันเสาร์</option>
                                                                    <option value="7">วันอาทิตย์</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <label for="start_time" class="form-label">เวลาเริ่ม</label>
                                                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label for="end_time" class="form-label">เวลาสิ้นสุด</label>
                                                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="d-grid">
                                                                <button type="submit" name="add_availability" class="btn btn-success">
                                                                    <i class="fas fa-plus me-2"></i> เพิ่มช่วงเวลาว่าง
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-8">
                                                <?php if ($selectedTeacher): ?>
                                                <div class="card">
                                                    <div class="card-header bg-info text-white">
                                                        <h5 class="card-title mb-0">ช่วงเวลาว่างของ <?php echo $selectedTeacher->name_prefix . $selectedTeacher->first_name . ' ' . $selectedTeacher->last_name; ?></h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if (empty($teacherAvailability)): ?>
                                                        <div class="alert alert-warning" role="alert">
                                                            <i class="fas fa-exclamation-triangle me-2"></i> ยังไม่มีข้อมูลช่วงเวลาว่าง กรุณาเพิ่มช่วงเวลาว่าง
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered">
                                                                <thead class="table-primary">
                                                                    <tr>
                                                                        <th width="10%">ลำดับ</th>
                                                                        <th width="20%">วัน</th>
                                                                        <th width="25%">เวลาเริ่ม</th>
                                                                        <th width="25%">เวลาสิ้นสุด</th>
                                                                        <th width="20%">การดำเนินการ</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    // Sort availability by day and time
                                                                    usort($teacherAvailability, function($a, $b) {
                                                                        if ($a['day_of_week'] == $b['day_of_week']) {
                                                                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                                                                        }
                                                                        return $a['day_of_week'] - $b['day_of_week'];
                                                                    });
                                                                    
                                                                    $i = 1;
                                                                    foreach ($teacherAvailability as $availability):
                                                                    ?>
                                                                    <tr>
                                                                        <td class="text-center"><?php echo $i++; ?></td>
                                                                        <td>วัน<?php echo getDayName($availability['day_of_week']); ?></td>
                                                                        <td><?php echo formatTime($availability['start_time']); ?></td>
                                                                        <td><?php echo formatTime($availability['end_time']); ?></td>
                                                                        <td>
                                                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline">
                                                                                <input type="hidden" name="teacher_id" value="<?php echo $selectedTeacher->id; ?>">
                                                                                <input type="hidden" name="availability_id" value="<?php echo $availability['id']; ?>">
                                                                                <button type="submit" name="remove_availability" class="btn btn-sm btn-danger" onclick="return confirm('คุณต้องการลบช่วงเวลานี้ใช่หรือไม่?')">
                                                                                    <i class="fas fa-trash"></i> ลบ
                                                                                </button>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="card mt-4">
                                                    <div class="card-header bg-secondary text-white">
                                                        <h5 class="card-title mb-0">ตารางสอนปัจจุบัน</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php
                                                        // Get current schedules for this teacher
                                                        $schedule->teacher_id = $selectedTeacher->id;
                                                        $schedule->semester = $current_semester;
                                                        $schedule->academic_year = $current_academic_year;
                                                        $teacher_schedules = $schedule->getSchedulesByTeacher($selectedTeacher->id);
                                                        
                                                        if (empty($teacher_schedules)):
                                                        ?>
                                                        <div class="alert alert-info" role="alert">
                                                            <i class="fas fa-info-circle me-2"></i> ยังไม่มีตารางสอนในภาคเรียนนี้
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered">
                                                                <thead class="table-primary">
                                                                    <tr>
                                                                        <th width="10%">วัน</th>
                                                                        <th width="15%">เวลา</th>
                                                                        <th width="15%">รหัสวิชา</th>
                                                                        <th width="30%">ชื่อรายวิชา</th>
                                                                        <th width="10%">ห้องเรียน</th>
                                                                        <th width="20%">การดำเนินการ</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    // Sort schedules by day and time
                                                                    usort($teacher_schedules, function($a, $b) {
                                                                        if ($a['day_of_week'] == $b['day_of_week']) {
                                                                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                                                                        }
                                                                        return $a['day_of_week'] - $b['day_of_week'];
                                                                    });
                                                                    
                                                                    foreach ($teacher_schedules as $sch):
                                                                    ?>
                                                                    <tr>
                                                                        <td>วัน<?php echo getDayName($sch['day_of_week']); ?></td>
                                                                        <td><?php echo formatTime($sch['start_time']) . ' - ' . formatTime($sch['end_time']); ?></td>
                                                                        <td><?php echo $sch['course_code']; ?></td>
                                                                        <td><?php echo $sch['course_name']; ?></td>
                                                                        <td><?php echo $sch['classroom']; ?></td>
                                                                        <td>
                                                                            <a href="schedules.php?edit=<?php echo $sch['id']; ?>" class="btn btn-sm btn-warning">
                                                                                <i class="fas fa-edit"></i> แก้ไข
                                                                            </a>
                                                                            <form method="POST" action="schedules.php" class="d-inline">
                                                                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                                                <button type="submit" name="delete_schedule" class="btn btn-sm btn-danger" onclick="return confirm('คุณต้องการลบตารางนี้ใช่หรือไม่?')">
                                                                                    <i class="fas fa-trash"></i> ลบ
                                                                                </button>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <div class="alert alert-info" role="alert">
                                                    <i class="fas fa-info-circle me-2"></i> กรุณาเลือกครูผู้สอนเพื่อดูหรือแก้ไขช่วงเวลาว่าง
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Generate Schedule Tab -->
                                    <div class="tab-pane fade" id="generate-schedule" role="tabpanel" aria-labelledby="generate-tab">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="card">
                                                    <div class="card-header bg-primary text-white">
                                                        <h5 class="card-title mb-0">สร้างตารางเรียนอัตโนมัติ</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="generateScheduleForm">
                                                            <div class="alert alert-warning" role="alert">
                                                                <i class="fas fa-exclamation-triangle me-2"></i> การสร้างตารางเรียนอัตโนมัติจะใช้ข้อมูลจากคำขอเปิดรายวิชาที่ได้รับการอนุมัติและช่วงเวลาว่างของครูผู้สอน
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="semester" class="form-label">ภาคเรียน</label>
                                                                <select class="form-select" id="semester" name="semester" required>
                                                                    <option value="1" <?php echo $current_semester == '1' ? 'selected' : ''; ?>>1</option>
                                                                    <option value="2" <?php echo $current_semester == '2' ? 'selected' : ''; ?>>2</option>
                                                                    <option value="ฤดูร้อน" <?php echo $current_semester == 'ฤดูร้อน' ? 'selected' : ''; ?>>ฤดูร้อน</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="academic_year" class="form-label">ปีการศึกษา</label>
                                                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo $current_academic_year; ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-check mb-3">
                                                                <input class="form-check-input" type="checkbox" value="1" id="confirm_generate" required>
                                                                <label class="form-check-label" for="confirm_generate">
                                                                    ยืนยันการสร้างตารางเรียนใหม่ (ตารางเดิมจะไม่ถูกลบ)
                                                                </label>
                                                            </div>
                                                            
                                                            <div class="d-grid">
                                                                <button type="submit" name="generate_schedule" class="btn btn-primary" id="generateScheduleBtn">
                                                                    <i class="fas fa-calendar-plus me-2"></i> สร้างตารางเรียน
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-7">
                                                <div class="card">
                                                    <div class="card-header bg-info text-white">
                                                        <h5 class="card-title mb-0">สรุปข้อมูลเพื่อจัดตารางเรียน</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="card bg-light mb-3">
                                                                    <div class="card-body">
                                                                        <h6 class="card-title">คำขอที่ได้รับการอนุมัติ</h6>
                                                                        <?php
                                                                        // Count approved requests
                                                                        $query = "SELECT COUNT(*) as count FROM course_requests WHERE status = 'approved' AND semester = :semester AND academic_year = :academic_year";
                                                                        $stmt = $db->prepare($query);
                                                                        $stmt->bindParam(':semester', $current_semester);
                                                                        $stmt->bindParam(':academic_year', $current_academic_year);
                                                                        $stmt->execute();
                                                                        $approved_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                        ?>
                                                                        <p class="card-text display-6 text-center"><?php echo $approved_count; ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="card bg-light mb-3">
                                                                    <div class="card-body">
                                                                        <h6 class="card-title">รายวิชาที่ต้องจัดตาราง</h6>
                                                                        <?php
                                                                        // Count unique courses in approved requests
                                                                        $query = "SELECT COUNT(DISTINCT cri.course_id) as count 
                                                                                 FROM course_request_items cri 
                                                                                 JOIN course_requests cr ON cri.course_request_id = cr.id 
                                                                                 WHERE cr.status = 'approved' AND cr.semester = :semester AND cr.academic_year = :academic_year";
                                                                        $stmt = $db->prepare($query);
                                                                        $stmt->bindParam(':semester', $current_semester);
                                                                        $stmt->bindParam(':academic_year', $current_academic_year);
                                                                        $stmt->execute();
                                                                        $course_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                        ?>
                                                                        <p class="card-text display-6 text-center"><?php echo $course_count; ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="card bg-light mb-3">
                                                                    <div class="card-body">
                                                                        <h6 class="card-title">ครูผู้สอนที่มีช่วงเวลาว่าง</h6>
                                                                        <?php
                                                                        // Count teachers with availability
                                                                        $query = "SELECT COUNT(DISTINCT teacher_id) as count FROM teacher_availability";
                                                                        $stmt = $db->prepare($query);
                                                                        $stmt->execute();
                                                                        $teacher_with_availability = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                        ?>
                                                                        <p class="card-text display-6 text-center"><?php echo $teacher_with_availability; ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="card bg-light mb-3">
                                                                    <div class="card-body">
                                                                        <h6 class="card-title">ตารางเรียนที่สร้างแล้ว</h6>
                                                                        <?php
                                                                        // Count existing schedules
                                                                        $query = "SELECT COUNT(*) as count FROM class_schedules WHERE semester = :semester AND academic_year = :academic_year";
                                                                        $stmt = $db->prepare($query);
                                                                        $stmt->bindParam(':semester', $current_semester);
                                                                        $stmt->bindParam(':academic_year', $current_academic_year);
                                                                        $stmt->execute();
                                                                        $schedule_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                        ?>
                                                                        <p class="card-text display-6 text-center"><?php echo $schedule_count; ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="alert alert-info mt-3" role="alert">
                                                            <i class="fas fa-info-circle me-2"></i> คำแนะนำ: ตรวจสอบให้แน่ใจว่าครูผู้สอนทุกคนได้บันทึกช่วงเวลาว่างก่อนสร้างตารางเรียน
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($scheduleResults)): ?>
                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <div class="card-header bg-success text-white">
                                                        <h5 class="card-title mb-0">ผลการสร้างตารางเรียน</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered">
                                                                <thead class="table-primary">
                                                                    <tr>
                                                                        <th width="5%">ลำดับ</th>
                                                                        <th width="15%">รหัสวิชา</th>
                                                                        <th width="25%">ชื่อรายวิชา</th>
                                                                        <th width="20%">ครูผู้สอน</th>
                                                                        <th width="10%">วัน</th>
                                                                        <th width="15%">เวลา</th>
                                                                        <th width="10%">ห้องเรียน</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php 
                                                                    $i = 1;
                                                                    foreach ($scheduleResults as $result): 
                                                                    ?>
                                                                    <tr>
                                                                        <td class="text-center"><?php echo $i++; ?></td>
                                                                        <td><?php echo $result['course_code']; ?></td>
                                                                        <td><?php echo $result['course_name']; ?></td>
                                                                        <td><?php echo $result['teacher_name']; ?></td>
                                                                        <td>วัน<?php echo getDayName($result['day_of_week']); ?></td>
                                                                        <td><?php echo formatTime($result['start_time']) . ' - ' . formatTime($result['end_time']); ?></td>
                                                                        <td><?php echo $result['classroom']; ?></td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        
                                                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-3">
                                                            <a href="schedules.php" class="btn btn-primary">
                                                                <i class="fas fa-calendar-alt me-2"></i> ไปที่หน้าจัดการตารางเรียน
                                                            </a>
                                                            <a href="../reports/schedule_pdf.php" class="btn btn-secondary" target="_blank">
                                                                <i class="fas fa-print me-2"></i> พิมพ์ตารางเรียน
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="../assets/js/select2.min.js"></script>
    <!-- DataTables JS -->
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    <!-- Sweet Alert -->
    <script src="../assets/js/sweetalert2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();
            
            // Validate time input
            $('#end_time').on('change', function() {
                var startTime = $('#start_time').val();
                var endTime = $(this).val();
                
                if (startTime && endTime && startTime >= endTime) {
                    Swal.fire({
                        title: 'ข้อผิดพลาด',
                        text: 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    $(this).val('');
                }
            });
            
            // Confirm schedule generation
            $('#generateScheduleForm').on('submit', function(e) {
                if (!$('#confirm_generate').is(':checked')) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด',
                        text: 'กรุณายืนยันการสร้างตารางเรียนใหม่',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }
                
                if (!confirm('คุณต้องการสร้างตารางเรียนใหม่ใช่หรือไม่? ระบบจะใช้เวลาในการประมวลผล')) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading indicator
                $('#generateScheduleBtn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังประมวลผล...');
                $('#generateScheduleBtn').prop('disabled', true);
            });
            
            // Activate tab from URL hash
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Update URL when tab changes
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                window.location.hash = e.target.hash;
            });
        });
    </script>
</body>
</html>