<?php
session_start();
/* // Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
} */

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/ClassSchedule.php';
include_once '../classes/Teacher.php';
include_once '../classes/Course.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$schedule = new ClassSchedule($db);
$teacher = new Teacher($db);
$course = new Course($db);

// Get current semester and academic year
// In production, this would likely be set in the settings table
$current_semester = "1";
$current_academic_year = "2568";

// Process form submission for adding a schedule
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        // Add new schedule
        $schedule->course_id = $_POST['course_id'];
        $schedule->teacher_id = $_POST['teacher_id'];
        $schedule->day_of_week = $_POST['day_of_week'];
        $schedule->start_time = $_POST['start_time'];
        $schedule->end_time = $_POST['end_time'];
        $schedule->classroom = $_POST['classroom'];
        $schedule->semester = $_POST['semester'];
        $schedule->academic_year = $_POST['academic_year'];
        
        if ($schedule->create()) {
            $message = 'เพิ่มตารางเรียนเรียบร้อยแล้ว';
            $message_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการเพิ่มตารางเรียน';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['update_schedule'])) {
        // Update existing schedule
        $schedule->id = $_POST['schedule_id'];
        $schedule->course_id = $_POST['course_id'];
        $schedule->teacher_id = $_POST['teacher_id'];
        $schedule->day_of_week = $_POST['day_of_week'];
        $schedule->start_time = $_POST['start_time'];
        $schedule->end_time = $_POST['end_time'];
        $schedule->classroom = $_POST['classroom'];
        $schedule->semester = $_POST['semester'];
        $schedule->academic_year = $_POST['academic_year'];
        
        if ($schedule->update()) {
            $message = 'อัพเดทตารางเรียนเรียบร้อยแล้ว';
            $message_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการอัพเดทตารางเรียน';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['delete_schedule'])) {
        // Delete schedule
        $schedule->id = $_POST['schedule_id'];
        
        if ($schedule->delete()) {
            $message = 'ลบตารางเรียนเรียบร้อยแล้ว';
            $message_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการลบตารางเรียน';
            $message_type = 'danger';
        }
    }
}

// Get all teachers for dropdown
$all_teachers = $teacher->getAllTeachers();

// Get all courses for dropdown
$all_courses = $course->getAllCourses();

// Get all schedules for current semester
$schedule->semester = $current_semester;
$schedule->academic_year = $current_academic_year;
$all_schedules = $schedule->getSchedulesBySemester();

// For editing a specific schedule
$edit_mode = false;
$edit_schedule = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $schedule->id = $_GET['edit'];
    $edit_schedule = $schedule->getScheduleById();
    
    if ($edit_schedule) {
        $edit_mode = true;
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
    <!-- DataTables CSS -->
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="../assets/css/select2.min.css" rel="stylesheet">
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
                    <h1 class="h2">จัดการตารางเรียน</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../reports/schedule_pdf.php" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-print me-1"></i> พิมพ์ตารางเรียน
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                <i class="fas fa-plus me-1"></i> เพิ่มตารางเรียน
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Schedule Calendar View -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">ตารางเรียน ภาคเรียนที่ <?php echo $current_semester; ?> ปีการศึกษา <?php echo $current_academic_year; ?></h5>
                    </div>
                    <div class="card-body">
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
                                    
                                    // Generate rows for each hour
                                    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                                        $time_key = sprintf("%02d:00", $hour);
                                        $next_hour = sprintf("%02d:00", $hour + 1);
                                        echo "<tr>";
                                        echo "<td class='table-light'>{$time_key}-{$next_hour}</td>";
                                        
                                        // Generate cells for each day
                                        for ($day = 1; $day <= 7; $day++) {
                                            echo "<td>";
                                            if (isset($schedule_by_time_and_day[$time_key][$day])) {
                                                $sch = $schedule_by_time_and_day[$time_key][$day];
                                                echo "<div class='p-1 bg-info text-white rounded'>";
                                                echo "<strong>{$sch['course_code']}</strong><br>";
                                                echo "{$sch['course_name']}<br>";
                                                echo "อาจารย์: {$sch['teacher_name']}<br>";
                                                echo "ห้อง: {$sch['classroom']}<br>";
                                                echo formatTime($sch['start_time']) . " - " . formatTime($sch['end_time']);
                                                echo "<div class='mt-1'>";
                                                echo "<a href='schedules.php?edit={$sch['id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a> ";
                                                echo "<button type='button' class='btn btn-sm btn-danger delete-schedule' data-id='{$sch['id']}'><i class='fas fa-trash'></i></button>";
                                                echo "</div>";
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
                    </div>
                </div>
                
                <!-- Schedule List View -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">รายการตารางเรียนทั้งหมด</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="schedulesTable">
                                <thead>
                                    <tr>
                                        <th>รหัสวิชา</th>
                                        <th>ชื่อรายวิชา</th>
                                        <th>อาจารย์ผู้สอน</th>
                                        <th>วัน</th>
                                        <th>เวลา</th>
                                        <th>ห้องเรียน</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_schedules as $schedule_item): ?>
                                    <tr>
                                        <td><?php echo $schedule_item['course_code']; ?></td>
                                        <td><?php echo $schedule_item['course_name']; ?></td>
                                        <td><?php echo $schedule_item['teacher_name']; ?></td>
                                        <td><?php echo getDayName($schedule_item['day_of_week']); ?></td>
                                        <td><?php echo formatTime($schedule_item['start_time']) . ' - ' . formatTime($schedule_item['end_time']); ?></td>
                                        <td><?php echo $schedule_item['classroom']; ?></td>
                                        <td>
                                            <a href="schedules.php?edit=<?php echo $schedule_item['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger delete-schedule" data-id="<?php echo $schedule_item['id']; ?>">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">เพิ่มตารางเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="course_id" class="form-label">รายวิชา</label>
                                <select class="form-select select2" id="course_id" name="course_id" required>
                                    <option value="">-- เลือกรายวิชา --</option>
                                    <?php foreach ($all_courses as $course_item): ?>
                                    <option value="<?php echo $course_item['id']; ?>"><?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="teacher_id" class="form-label">อาจารย์ผู้สอน</label>
                                <select class="form-select select2" id="teacher_id" name="teacher_id" required>
                                    <option value="">-- เลือกอาจารย์ --</option>
                                    <?php foreach ($all_teachers as $teacher_item): ?>
                                    <option value="<?php echo $teacher_item['id']; ?>"><?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label for="start_time" class="form-label">เวลาเริ่ม</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_time" class="form-label">เวลาสิ้นสุด</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="classroom" class="form-label">ห้องเรียน</label>
                                <input type="text" class="form-control" id="classroom" name="classroom" required>
                            </div>
                            <div class="col-md-4">
                                <label for="semester" class="form-label">ภาคเรียน</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="1" <?php echo $current_semester == '1' ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo $current_semester == '2' ? 'selected' : ''; ?>>2</option>
                                    <option value="ฤดูร้อน" <?php echo $current_semester == 'ฤดูร้อน' ? 'selected' : ''; ?>>ฤดูร้อน</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="academic_year" class="form-label">ปีการศึกษา</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo $current_academic_year; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" name="add_schedule">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Schedule Modal -->
    <?php if ($edit_mode && $edit_schedule): ?>
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขตารางเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="schedule_id" value="<?php echo $edit_schedule['id']; ?>">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_course_id" class="form-label">รายวิชา</label>
                                <select class="form-select select2" id="edit_course_id" name="course_id" required>
                                    <option value="">-- เลือกรายวิชา --</option>
                                    <?php foreach ($all_courses as $course_item): ?>
                                    <option value="<?php echo $course_item['id']; ?>" <?php echo $course_item['id'] == $edit_schedule['course_id'] ? 'selected' : ''; ?>>
                                        <?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_teacher_id" class="form-label">อาจารย์ผู้สอน</label>
                                <select class="form-select select2" id="edit_teacher_id" name="teacher_id" required>
                                    <option value="">-- เลือกอาจารย์ --</option>
                                    <?php foreach ($all_teachers as $teacher_item): ?>
                                    <option value="<?php echo $teacher_item['id']; ?>" <?php echo $teacher_item['id'] == $edit_schedule['teacher_id'] ? 'selected' : ''; ?>>
                                        <?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="edit_day_of_week" class="form-label">วัน</label>
                                <select class="form-select" id="edit_day_of_week" name="day_of_week" required>
                                    <option value="">-- เลือกวัน --</option>
                                    <option value="1" <?php echo $edit_schedule['day_of_week'] == 1 ? 'selected' : ''; ?>>วันจันทร์</option>
                                    <option value="2" <?php echo $edit_schedule['day_of_week'] == 2 ? 'selected' : ''; ?>>วันอังคาร</option>
                                    <option value="3" <?php echo $edit_schedule['day_of_week'] == 3 ? 'selected' : ''; ?>>วันพุธ</option>
                                    <option value="4" <?php echo $edit_schedule['day_of_week'] == 4 ? 'selected' : ''; ?>>วันพฤหัสบดี</option>
                                    <option value="5" <?php echo $edit_schedule['day_of_week'] == 5 ? 'selected' : ''; ?>>วันศุกร์</option>
                                    <option value="6" <?php echo $edit_schedule['day_of_week'] == 6 ? 'selected' : ''; ?>>วันเสาร์</option>
                                    <option value="7" <?php echo $edit_schedule['day_of_week'] == 7 ? 'selected' : ''; ?>>วันอาทิตย์</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_start_time" class="form-label">เวลาเริ่ม</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time" value="<?php echo date('H:i', strtotime($edit_schedule['start_time'])); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_end_time" class="form-label">เวลาสิ้นสุด</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" value="<?php echo date('H:i', strtotime($edit_schedule['end_time'])); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="edit_classroom" class="form-label">ห้องเรียน</label>
                                <input type="text" class="form-control" id="edit_classroom" name="classroom" value="<?php echo $edit_schedule['classroom']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_semester" class="form-label">ภาคเรียน</label>
                                <select class="form-select" id="edit_semester" name="semester" required>
                                    <option value="1" <?php echo $edit_schedule['semester'] == '1' ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo $edit_schedule['semester'] == '2' ? 'selected' : ''; ?>>2</option>
                                    <option value="ฤดูร้อน" <?php echo $edit_schedule['semester'] == 'ฤดูร้อน' ? 'selected' : ''; ?>>ฤดูร้อน</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="edit_academic_year" class="form-label">ปีการศึกษา</label>
                                <input type="text" class="form-control" id="edit_academic_year" name="academic_year" value="<?php echo $edit_schedule['academic_year']; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" name="update_schedule">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Schedule Form (Hidden) -->
    <form id="deleteScheduleForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: none;">
        <input type="hidden" id="delete_schedule_id" name="schedule_id">
        <input type="hidden" name="delete_schedule" value="1">
    </form>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 JS -->
    <script src="../assets/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="../assets/js/sweetalert2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#schedulesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                }
            });
            
            // Initialize Select2
            $('.select2').select2({
                dropdownParent: $('#addScheduleModal')
            });
            
            // Show edit modal on page load if in edit mode
            <?php if ($edit_mode): ?>
            $('#editScheduleModal').modal('show');
            
            // Initialize Select2 for edit form
            $('#edit_course_id, #edit_teacher_id').select2({
                dropdownParent: $('#editScheduleModal')
            });
            <?php endif; ?>
            
            // Delete schedule confirmation
            $('.delete-schedule').click(function() {
                var scheduleId = $(this).data('id');
                
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    text: "คุณต้องการลบตารางเรียนนี้ใช่หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ลบเลย!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete_schedule_id').val(scheduleId);
                        $('#deleteScheduleForm').submit();
                    }
                });
            });
            
            // Validate time input
            $('#end_time, #edit_end_time').on('change', function() {
                var startTime = $(this).closest('form').find('[name="start_time"]').val();
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
        });
    </script>
</body>
</html>