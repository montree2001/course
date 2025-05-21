<?php
// Include database and necessary classes

/* แสดง Error */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


include_once 'config/database.php';
include_once 'classes/Course.php';
include_once 'classes/Teacher.php';
include_once 'classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$course = new Course($db);
$teacher = new Teacher($db);
$courseRequest = new CourseRequest($db);

// Get current semester and academic year
// In production, this would likely be set in the settings table
$current_semester = "1";
$current_academic_year = "2568";

// Get all courses for dropdown
$all_courses = $course->getAllCourses();

// Get all teachers for dropdown
$all_teachers = $teacher->getAllTeachers();

// Process form submission
$success_message = '';
$error_message = '';
$request_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Validate required fields
        if (empty($_POST['student_code']) || 
            empty($_POST['name_prefix']) || 
            empty($_POST['first_name']) || 
            empty($_POST['last_name']) || 
            empty($_POST['education_level']) || 
            empty($_POST['year']) || 
            empty($_POST['major']) || 
            empty($_POST['phone_number']) || 
            empty($_POST['course_id']) || 
            empty($_POST['teacher_id'])) {
            
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }
        
        // Create new course request
        $courseRequest->student_id = 0; // Temporary ID, will be updated later
        $courseRequest->semester = $current_semester;
        $courseRequest->academic_year = $current_academic_year;
        $courseRequest->request_date = date('Y-m-d');
        $courseRequest->status = 'pending';
        
        // Student data
        $student_code = $_POST['student_code'];
        $name_prefix = $_POST['name_prefix'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $education_level = $_POST['education_level'];
        $year = $_POST['year'];
        $major = $_POST['major'];
        $phone_number = $_POST['phone_number'];
        
        // Insert temporary student data for non-logged in students
        $query = "INSERT INTO temp_students 
                  (student_code, name_prefix, first_name, last_name, education_level, year, major, phone_number, created_at) 
                  VALUES 
                  (:student_code, :name_prefix, :first_name, :last_name, :education_level, :year, :major, :phone_number, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_code', $student_code);
        $stmt->bindParam(':name_prefix', $name_prefix);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':education_level', $education_level);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':major', $major);
        $stmt->bindParam(':phone_number', $phone_number);
        
        if (!$stmt->execute()) {
            throw new Exception('เกิดข้อผิดพลาดในการบันทึกข้อมูลนักศึกษา');
        }
        
        $temp_student_id = $db->lastInsertId();
        
        // Update student_id in course request
        $courseRequest->student_id = $temp_student_id;
        
        if (!$courseRequest->create()) {
            throw new Exception('เกิดข้อผิดพลาดในการสร้างคำขอ');
        }
        
        // Request ID for reference
        $request_id = $courseRequest->id;
        
        // Add course request items
        $course_count = count($_POST['course_id']);
        
        for ($i = 0; $i < $course_count; $i++) {
            if (!empty($_POST['course_id'][$i]) && !empty($_POST['teacher_id'][$i])) {
                if (!$courseRequest->addRequestItem($_POST['course_id'][$i], $_POST['teacher_id'][$i])) {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มรายวิชา');
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $success_message = 'ส่งคำขอเปิดรายวิชาเรียบร้อยแล้ว';
        
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
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="assets/css/select2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Logo" height="36" class="d-inline-block align-text-top me-2">
                ระบบขอเปิดรายวิชา
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="request_form.php">
                            <i class="fas fa-file-alt me-1"></i> ขอเปิดรายวิชา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="check_status.php">
                            <i class="fas fa-search me-1"></i> ตรวจสอบสถานะ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="fas fa-calendar-alt me-1"></i> ตารางเรียน
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">
                            <i class="fas fa-user-lock me-1"></i> เข้าสู่ระบบ (สำหรับเจ้าหน้าที่)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php if (!empty($success_message) && $request_id): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> ส่งคำขอเรียบร้อยแล้ว!</h4>
                    <p>คำขอเปิดรายวิชาของคุณได้รับการบันทึกเรียบร้อยแล้ว รหัสคำขอของคุณคือ: <strong><?php echo $request_id; ?></strong></p>
                    <hr>
                    <p class="mb-0">กรุณาจดรหัสคำขอไว้เพื่อติดตามสถานะของคำขอในภายหลัง</p>
                    <div class="mt-3">
                        <a href="check_status.php?request_id=<?php echo $request_id; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i> ตรวจสอบสถานะคำขอ
                        </a>
                        <a href="reports/course_request_pdf.php?id=<?php echo $request_id; ?>" class="btn btn-secondary" target="_blank">
                            <i class="fas fa-print me-1"></i> พิมพ์แบบฟอร์มคำขอ
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif(!$request_id): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">ขอเปิดรายวิชาภาคเรียนพิเศษ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i> กรุณากรอกข้อมูลรายวิชาที่ต้องการขอเปิด สำหรับภาคเรียนที่ <?php echo $current_semester; ?> ปีการศึกษา <?php echo $current_academic_year; ?>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="courseRequestForm">
                            <h5 class="mb-3">ข้อมูลนักศึกษา</h5>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="student_code" class="form-label">รหัสนักศึกษา <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_code" name="student_code" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="name_prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                    <select class="form-select" id="name_prefix" name="name_prefix" required>
                                        <option value="">-- เลือก --</option>
                                        <option value="นาย">นาย</option>
                                        <option value="นางสาว">นางสาว</option>
                                        <option value="นาง">นาง</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="education_level" class="form-label">ระดับชั้น <span class="text-danger">*</span></label>
                                    <select class="form-select" id="education_level" name="education_level" required>
                                        <option value="">-- เลือก --</option>
                                        <option value="ปวช.">ปวช.</option>
                                        <option value="ปวส.">ปวส.</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="year" class="form-label">ชั้นปีที่ <span class="text-danger">*</span></label>
                                    <select class="form-select" id="year" name="year" required>
                                        <option value="">-- เลือก --</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="major" class="form-label">สาขาวิชา <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="major" name="major" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="phone_number" class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">รายวิชาที่ต้องการขอเปิด</h5>
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
        <?php endif; ?>
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

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>วิทยาลัยการอาชีพปราสาท</h5>
                    <p class="small">ระบบขอเปิดรายวิชาภาคเรียนพิเศษ</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 small">© 2025 วิทยาลัยการอาชีพปราสาท</p>
                    <p class="small">"เรียนดี มีความสุข"</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="assets/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="assets/js/sweetalert2.min.js"></script>
    
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