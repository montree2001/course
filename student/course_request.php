<?php
session_start();
// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/Student.php';
include_once '../classes/Course.php';
include_once '../classes/Teacher.php';
include_once '../classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$student = new Student($db);
$course = new Course($db);
$teacher = new Teacher($db);
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

// Get all courses for dropdown
$all_courses = $course->getAllCourses();

// Get all teachers for dropdown
$all_teachers = $teacher->getAllTeachers();

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Create new course request
        $courseRequest->student_id = $student->id;
        $courseRequest->semester = $current_semester;
        $courseRequest->academic_year = $current_academic_year;
        $courseRequest->request_date = date('Y-m-d');
        $courseRequest->status = 'pending';
        
        if ($courseRequest->create()) {
            // Add course request items
            $course_count = count($_POST['course_id']);
            
            for ($i = 0; $i < $course_count; $i++) {
                if (!empty($_POST['course_id'][$i]) && !empty($_POST['teacher_id'][$i])) {
                    $courseRequest->addRequestItem($_POST['course_id'][$i], $_POST['teacher_id'][$i]);
                }
            }
            
            // Commit transaction
            $db->commit();
            
            $success_message = 'ส่งคำขอเปิดรายวิชาเรียบร้อยแล้ว';
        } else {
            // Rollback transaction
            $db->rollBack();
            
            $error_message = 'เกิดข้อผิดพลาดในการส่งคำขอ';
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ขอเปิดรายวิชา - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="../assets/css/select2.min.css" rel="stylesheet">
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">ขอเปิดรายวิชาภาคเรียนพิเศษ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> กรุณากรอกข้อมูลรายวิชาที่ต้องการขอเปิด สำหรับภาคเรียนที่ <?php echo $current_semester; ?> ปีการศึกษา <?php echo $current_academic_year; ?>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="courseRequestForm">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">รหัสนักศึกษา</label>
                                        <input type="text" class="form-control" value="<?php echo $student->student_code; ?>" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ชื่อ-นามสกุล</label>
                                        <input type="text" class="form-control" value="<?php echo $student->name_prefix . $student->first_name . ' ' . $student->last_name; ?>" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ระดับชั้น</label>
                                        <input type="text" class="form-control" value="<?php echo $student->education_level . ' ชั้นปีที่ ' . $student->year . ' สาขา' . $student->major; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="course-items">
                                <div class="card mb-3 course-item">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label class="form-label">รายวิชา <span class="text-danger">*</span></label>
                                                <select class="form-select course-select" name="course_id[]" required>
                                                    <option value="">-- เลือกรายวิชา --</option>
                                                    <?php foreach ($all_courses as $course_item): ?>
                                                    <option value="<?php echo $course_item['id']; ?>" data-theory="<?php echo $course_item['theory_hours']; ?>" data-practice="<?php echo $course_item['practice_hours']; ?>" data-credit="<?php echo $course_item['credits']; ?>" data-hours="<?php echo $course_item['total_hours']; ?>">
                                                        <?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                                                <select class="form-select teacher-select" name="teacher_id[]" required>
                                                    <option value="">-- เลือกครูประจำวิชา --</option>
                                                    <?php foreach ($all_teachers as $teacher_item): ?>
                                                    <option value="<?php echo $teacher_item['id']; ?>">
                                                        <?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <input type="text" class="form-control theory-hours" placeholder="ท" readonly>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="text" class="form-control practice-hours" placeholder="ป" readonly>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="text" class="form-control credits" placeholder="นก" readonly>
                                                    </div>
                                                    <div class="col-3">
                                                        <input type="text" class="form-control total-hours" placeholder="ชม" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                                <button type="button" class="btn btn-success add-course-btn">
                                    <i class="fas fa-plus me-2"></i> เพิ่มรายวิชา
                                </button>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i> ส่งคำขอเปิดรายวิชา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="courseItemTemplate" style="display: none;">
        <div class="card mb-3 course-item">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">รายวิชา <span class="text-danger">*</span></label>
                        <select class="form-select course-select" name="course_id[]" required>
                            <option value="">-- เลือกรายวิชา --</option>
                            <?php foreach ($all_courses as $course_item): ?>
                            <option value="<?php echo $course_item['id']; ?>" data-theory="<?php echo $course_item['theory_hours']; ?>" data-practice="<?php echo $course_item['practice_hours']; ?>" data-credit="<?php echo $course_item['credits']; ?>" data-hours="<?php echo $course_item['total_hours']; ?>">
                                <?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                        <select class="form-select teacher-select" name="teacher_id[]" required>
                            <option value="">-- เลือกครูประจำวิชา --</option>
                            <?php foreach ($all_teachers as $teacher_item): ?>
                            <option value="<?php echo $teacher_item['id']; ?>">
                                <?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">รายละเอียด</label>
                        <div class="row">
                            <div class="col-3">
                                <input type="text" class="form-control theory-hours" placeholder="ท" readonly>
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control practice-hours" placeholder="ป" readonly>
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control credits" placeholder="นก" readonly>
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control total-hours" placeholder="ชม" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger remove-course-btn d-block">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="../assets/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="../assets/js/sweetalert2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Function to initialize Select2 on course and teacher selects
            function initializeSelects() {
                $('.course-select').select2({
                    placeholder: "เลือกรายวิชา",
                    width: '100%'
                });
                
                $('.teacher-select').select2({
                    placeholder: "เลือกครูประจำวิชา",
                    width: '100%'
                });
            }
            
            // Initialize Select2 on page load
            initializeSelects();
            
            // Update course details when course is selected
            $(document).on('change', '.course-select', function() {
                var selectedOption = $(this).find('option:selected');
                var container = $(this).closest('.course-item');
                
                // Get course details from data attributes
                var theory = selectedOption.data('theory');
                var practice = selectedOption.data('practice');
                var credit = selectedOption.data('credit');
                var hours = selectedOption.data('hours');
                
                // Update fields
                container.find('.theory-hours').val(theory);
                container.find('.practice-hours').val(practice);
                container.find('.credits').val(credit);
                container.find('.total-hours').val(hours);
            });
            
            // Add course button click
            $('.add-course-btn').click(function() {
                // Get template
                var template = $('#courseItemTemplate').html();
                
                // Add template to container
                $('.course-items').append(template);
                
                // Initialize Select2 for new elements
                initializeSelects();
            });
            
            // Remove course button click
            $(document).on('click', '.remove-course-btn', function() {
                $(this).closest('.course-item').remove();
            });
            
            // Form submission validation
            $('#courseRequestForm').submit(function(e) {
                // Check if at least one course is selected
                if ($('.course-item').length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด',
                        text: 'กรุณาเพิ่มรายวิชาอย่างน้อย 1 รายวิชา',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // Check for duplicate courses
                var courses = [];
                var hasDuplicate = false;
                
                $('.course-select').each(function() {
                    var courseId = $(this).val();
                    if (courseId && courses.includes(courseId)) {
                        hasDuplicate = true;
                        return false; // Break the loop
                    }
                    courses.push(courseId);
                });
                
                if (hasDuplicate) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด',
                        text: 'มีการเลือกรายวิชาซ้ำกัน กรุณาตรวจสอบข้อมูล',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // Confirm submission
                e.preventDefault();
                Swal.fire({
                    title: 'ยืนยันการส่งคำขอ',
                    text: 'คุณต้องการส่งคำขอเปิดรายวิชานี้ใช่หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ใช่, ส่งคำขอ',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).unbind('submit').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>