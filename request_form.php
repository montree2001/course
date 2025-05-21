<?php
// แสดง Error สำหรับการพัฒนา
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include database และ classes ที่จำเป็น
include_once 'config/database.php';
include_once 'classes/Course.php';
include_once 'classes/Teacher.php';
include_once 'classes/CourseRequest.php';

// สร้าง database connection
$database = new Database();
$db = $database->connect();

// สร้าง objects
$course = new Course($db);
$teacher = new Teacher($db);
$courseRequest = new CourseRequest($db);

// กำหนดภาคเรียนและปีการศึกษาปัจจุบัน
$current_semester = "1";
$current_academic_year = "2568";

// ดึงข้อมูลรายวิชาทั้งหมดสำหรับ dropdown
$all_courses = $course->getAllCourses();

// ดึงข้อมูลครูทั้งหมดสำหรับ dropdown
$all_teachers = $teacher->getAllTeachers();

// ตัวแปรสำหรับแสดงข้อความต่างๆ
$success_message = '';
$error_message = '';
$request_id = null;

// เมื่อฟอร์มถูกส่ง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_code']) || 
            empty($_POST['name_prefix']) || 
            empty($_POST['first_name']) || 
            empty($_POST['last_name']) || 
            empty($_POST['education_level']) || 
            empty($_POST['year']) || 
            empty($_POST['major']) || 
            empty($_POST['phone_number'])) {
            
            throw new Exception('กรุณากรอกข้อมูลนักศึกษาให้ครบถ้วน');
        }
        
        // ตรวจสอบว่ามีข้อมูลรายวิชาที่เลือกหรือกรอกเอง
        $has_courses = false;
        $course_types = isset($_POST['course_type']) ? $_POST['course_type'] : [];
        
        if (empty($course_types)) {
            throw new Exception('กรุณาเลือกหรือกรอกข้อมูลรายวิชาอย่างน้อย 1 รายวิชา');
        }
        
        // ตรวจสอบความถูกต้องของข้อมูลรายวิชา
        foreach ($course_types as $index => $type) {
            if ($type === 'select') {
                // ตรวจสอบรายวิชาที่เลือกจากระบบ
                if (!isset($_POST['course_id'][$index]) || empty($_POST['course_id'][$index])) {
                    throw new Exception('กรุณาเลือกรายวิชาให้ครบถ้วน');
                }
                
                if (!isset($_POST['teacher_id'][$index]) || empty($_POST['teacher_id'][$index])) {
                    throw new Exception('กรุณาเลือกครูประจำวิชาให้ครบถ้วน');
                }
                
                $has_courses = true;
            } else if ($type === 'custom') {
                // ตรวจสอบรายวิชาที่กรอกเอง
                if (!isset($_POST['custom_course_code'][$index]) || empty($_POST['custom_course_code'][$index])) {
                    throw new Exception('กรุณากรอกรหัสวิชาให้ครบถ้วน');
                }
                
                if (!isset($_POST['custom_course_name'][$index]) || empty($_POST['custom_course_name'][$index])) {
                    throw new Exception('กรุณากรอกชื่อรายวิชาให้ครบถ้วน');
                }
                
                if (!isset($_POST['teacher_id'][$index]) || empty($_POST['teacher_id'][$index])) {
                    throw new Exception('กรุณาเลือกครูประจำวิชาให้ครบถ้วน');
                }
                
                $has_courses = true;
            }
        }
        
        if (!$has_courses) {
            throw new Exception('กรุณาเลือกหรือกรอกข้อมูลรายวิชาอย่างน้อย 1 รายวิชา');
        }
        
        // เริ่ม transaction
        $db->beginTransaction();
        
        // สร้างคำขอเปิดรายวิชาใหม่
        $courseRequest->student_id = 0; // ID ชั่วคราว จะอัปเดตในภายหลัง
        $courseRequest->semester = $current_semester;
        $courseRequest->academic_year = $current_academic_year;
        $courseRequest->request_date = date('Y-m-d');
        $courseRequest->status = 'pending';
        
        // ข้อมูลนักศึกษา
        $student_code = $_POST['student_code'];
        $name_prefix = $_POST['name_prefix'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $education_level = $_POST['education_level'];
        $year = $_POST['year'];
        $major = $_POST['major'];
        $phone_number = $_POST['phone_number'];
        
        // เพิ่มข้อมูลนักศึกษาชั่วคราวสำหรับผู้ที่ไม่ได้ login
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
        
        // อัปเดต student_id ในคำขอ
        $courseRequest->student_id = $temp_student_id;
        
        // แก้ไขใช้ createWithoutTransaction เพราะเราเริ่ม transaction เองแล้ว
        if (!$courseRequest->createWithoutTransaction()) {
            throw new Exception('เกิดข้อผิดพลาดในการสร้างคำขอ');
        }
        
        // รหัสคำขอสำหรับอ้างอิง
        $request_id = $courseRequest->id;
        
        // เพิ่มรายวิชาในคำขอ
        foreach ($course_types as $index => $course_type) {
            if ($course_type === 'select' && !empty($_POST['course_id'][$index]) && !empty($_POST['teacher_id'][$index])) {
                // กรณีเลือกจากรายวิชาที่มีอยู่แล้ว
                if (!$courseRequest->addRequestItem($_POST['course_id'][$index], $_POST['teacher_id'][$index])) {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มรายวิชา');
                }
            } 
            else if ($course_type === 'custom' && !empty($_POST['custom_course_code'][$index]) && !empty($_POST['custom_course_name'][$index]) && !empty($_POST['teacher_id'][$index])) {
                // กรณีกรอกรายวิชาเอง
                $custom_course_code = $_POST['custom_course_code'][$index];
                $custom_course_name = $_POST['custom_course_name'][$index];
                $theory_hours = $_POST['custom_theory_hours'][$index] ?? 0;
                $practice_hours = $_POST['custom_practice_hours'][$index] ?? 0;
                $credits = $_POST['custom_credits'][$index] ?? 0;
                $total_hours = $_POST['custom_total_hours'][$index] ?? 0;
                
                // ตรวจสอบว่ารายวิชามีอยู่แล้วหรือไม่
                $course->course_code = $custom_course_code;
                $course_exists = $course->getCourseByCode();
                
                if (!$course_exists) {
                    // เพิ่มรายวิชาใหม่
                    $course->course_code = $custom_course_code;
                    $course->course_name = $custom_course_name;
                    $course->theory_hours = $theory_hours;
                    $course->practice_hours = $practice_hours;
                    $course->credits = $credits;
                    $course->total_hours = $total_hours;
                    
                    if (!$course->create()) {
                        throw new Exception('เกิดข้อผิดพลาดในการเพิ่มรายวิชาใหม่');
                    }
                    
                    $course_id = $course->id;
                } else {
                    $course_id = $course->id;
                }
                
                // เพิ่มรายวิชาลงในคำขอ
                if (!$courseRequest->addRequestItem($course_id, $_POST['teacher_id'][$index])) {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มรายวิชา');
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $success_message = 'ส่งคำขอเปิดรายวิชาเรียบร้อยแล้ว';
        
    } catch (Exception $e) {
        // Rollback transaction ในกรณีที่มีข้อผิดพลาด
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    
    <style>
        /* Custom CSS for course request form */
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .course-item {
            transition: all 0.3s ease;
        }
        
        .course-item:hover {
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
        }
        
        .remove-course-btn {
            width: 38px;
        }
        
        /* Make sure select2 dropdowns display properly */
        .select2-container {
            z-index: 1060;
            width: 100% !important;
        }
        
        /* Customize placeholder fields */
        input[readonly].form-control {
            background-color: #f8f9fa;
        }
        
        /* Style for success message */
        .success-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Add spacing between elements */
        .form-label {
            margin-bottom: 0.3rem;
        }
        
        /* Custom styles for course type toggle */
        .course-type-toggle {
            margin-bottom: 10px;
        }
        
        .course-type-radio {
            margin-right: 10px;
        }
        
        .select-course-container,
        .custom-course-container {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .custom-course-container {
            border-left: 3px solid #0d6efd;
        }
    </style>
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
        <!-- แสดงข้อความเมื่อส่งฟอร์มสำเร็จ -->
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
        <!-- แสดงฟอร์มสำหรับกรอกข้อมูล -->
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
                            
                            <!-- Container สำหรับรายวิชาทั้งหมด -->
                            <div class="course-items">
                                <!-- Template สำหรับรายวิชา -->
                                <div class="card mb-3 course-item">
                                    <div class="card-body">
                                        <div class="course-type-toggle mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input course-type-radio" type="radio" name="course_type[0]" id="course_type_select_0" value="select" checked>
                                                <label class="form-check-label" for="course_type_select_0">เลือกจากรายการ</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input course-type-radio" type="radio" name="course_type[0]" id="course_type_custom_0" value="custom">
                                                <label class="form-check-label" for="course_type_custom_0">กรอกข้อมูลเอง</label>
                                            </div>
                                        </div>
                                        
                                        <!-- ส่วนเลือกรายวิชาจากระบบ -->
                                        <div class="select-course-container">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <label class="form-label">รายวิชา <span class="text-danger">*</span></label>
                                                    <select class="form-select course-select" name="course_id[0]">
                                                        <option value="">-- เลือกรายวิชา --</option>
                                                        <?php foreach ($all_courses as $course_item): ?>
                                                        <option value="<?php echo $course_item['id']; ?>" 
                                                            data-theory="<?php echo $course_item['theory_hours']; ?>" 
                                                            data-practice="<?php echo $course_item['practice_hours']; ?>" 
                                                            data-credit="<?php echo $course_item['credits']; ?>" 
                                                            data-hours="<?php echo $course_item['total_hours']; ?>">
                                                            <?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                                                    <select class="form-select teacher-select" name="teacher_id[0]">
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
                                                            <input type="text" class="form-control theory-hours" name="theory_hours[0]" placeholder="ท" readonly>
                                                        </div>
                                                        <div class="col-3">
                                                            <input type="text" class="form-control practice-hours" name="practice_hours[0]" placeholder="ป" readonly>
                                                        </div>
                                                        <div class="col-3">
                                                            <input type="text" class="form-control credits" name="credits[0]" placeholder="นก" readonly>
                                                        </div>
                                                        <div class="col-3">
                                                            <input type="text" class="form-control total-hours" name="total_hours[0]" placeholder="ชม" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="button" class="btn btn-danger remove-course-btn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- ส่วนกรอกรายวิชาเอง -->
                                        <div class="custom-course-container" style="display: none;">
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">รหัสวิชา <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control custom-course-code" name="custom_course_code[0]" placeholder="เช่น 20001-1001">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">ชื่อรายวิชา <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control custom-course-name" name="custom_course_name[0]" placeholder="เช่น ภาษาไทยพื้นฐาน">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                                                    <select class="form-select teacher-select-custom" name="teacher_id[0]">
                                                        <option value="">-- เลือกครูประจำวิชา --</option>
                                                        <?php foreach ($all_teachers as $teacher_item): ?>
                                                        <option value="<?php echo $teacher_item['id']; ?>">
                                                            <?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">จำนวนชั่วโมงทฤษฎี</label>
                                                    <input type="number" class="form-control custom-theory-hours" name="custom_theory_hours[0]" min="0" value="0">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">จำนวนชั่วโมงปฏิบัติ</label>
                                                    <input type="number" class="form-control custom-practice-hours" name="custom_practice_hours[0]" min="0" value="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">หน่วยกิต</label>
                                                    <input type="number" class="form-control custom-credits" name="custom_credits[0]" min="0" value="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">จำนวนชั่วโมงรวม</label>
                                                    <input type="number" class="form-control custom-total-hours" name="custom_total_hours[0]" min="0" value="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">&nbsp;</label>
                                                    <button type="button" class="btn btn-danger remove-course-btn d-block">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ปุ่มเพิ่มรายวิชา -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                                <button type="button" class="btn btn-success add-course-btn">
                                    <i class="fas fa-plus me-2"></i> เพิ่มรายวิชา
                                </button>
                            </div>
                            
                            <!-- ปุ่มส่งฟอร์ม -->
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
    
    <!-- Template สำหรับ Clone รายวิชา (ซ่อนไว้) -->
    <div id="courseItemTemplate" style="display: none;">
        <div class="card mb-3 course-item">
            <div class="card-body">
                <div class="course-type-toggle mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input course-type-radio" type="radio" name="course_type[INDEX]" id="course_type_select_INDEX" value="select" checked>
                        <label class="form-check-label" for="course_type_select_INDEX">เลือกจากรายการ</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input course-type-radio" type="radio" name="course_type[INDEX]" id="course_type_custom_INDEX" value="custom">
                        <label class="form-check-label" for="course_type_custom_INDEX">กรอกข้อมูลเอง</label>
                    </div>
                </div>
                
                <!-- ส่วนเลือกรายวิชาจากระบบ -->
                <div class="select-course-container">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">รายวิชา <span class="text-danger">*</span></label>
                            <select class="form-select course-select" name="course_id[INDEX]">
                                <option value="">-- เลือกรายวิชา --</option>
                                <?php foreach ($all_courses as $course_item): ?>
                                <option value="<?php echo $course_item['id']; ?>" 
                                    data-theory="<?php echo $course_item['theory_hours']; ?>" 
                                    data-practice="<?php echo $course_item['practice_hours']; ?>" 
                                    data-credit="<?php echo $course_item['credits']; ?>" 
                                    data-hours="<?php echo $course_item['total_hours']; ?>">
                                    <?php echo $course_item['course_code'] . ' - ' . $course_item['course_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                            <select class="form-select teacher-select" name="teacher_id[INDEX]">
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
                                    <input type="text" class="form-control theory-hours" name="theory_hours[INDEX]" placeholder="ท" readonly>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control practice-hours" name="practice_hours[INDEX]" placeholder="ป" readonly>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control credits" name="credits[INDEX]" placeholder="นก" readonly>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control total-hours" name="total_hours[INDEX]" placeholder="ชม" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger remove-course-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- ส่วนกรอกรายวิชาเอง -->
                <div class="custom-course-container" style="display: none;">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">รหัสวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control custom-course-code" name="custom_course_code[INDEX]" placeholder="เช่น 20001-1001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อรายวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control custom-course-name" name="custom_course_name[INDEX]" placeholder="เช่น ภาษาไทยพื้นฐาน">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ครูประจำวิชา <span class="text-danger">*</span></label>
                            <select class="form-select teacher-select-custom" name="teacher_id[INDEX]">
                                <option value="">-- เลือกครูประจำวิชา --</option>
                                <?php foreach ($all_teachers as $teacher_item): ?>
                                <option value="<?php echo $teacher_item['id']; ?>">
                                    <?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">จำนวนชั่วโมงทฤษฎี</label>
                            <input type="number" class="form-control custom-theory-hours" name="custom_theory_hours[INDEX]" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">จำนวนชั่วโมงปฏิบัติ</label>
                            <input type="number" class="form-control custom-practice-hours" name="custom_practice_hours[INDEX]" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">หน่วยกิต</label>
                            <input type="number" class="form-control custom-credits" name="custom_credits[INDEX]" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">จำนวนชั่วโมงรวม</label>
                            <input type="number" class="form-control custom-total-hours" name="custom_total_hours[INDEX]" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger remove-course-btn d-block">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
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
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Function to initialize Select2 on dropdowns
            function initializeSelects() {
                $('.course-select, .teacher-select, .teacher-select-custom').select2({
                    placeholder: "เลือกรายการ",
                    width: '100%',
                    dropdownParent: $('body')
                });
            }
            
            // Initialize Select2 dropdowns on page load
            initializeSelects();
            
            // Update course details when course is selected
            $(document).on('change', '.course-select', function() {
                var selectedOption = $(this).find('option:selected');
                var container = $(this).closest('.select-course-container');
                
                // Get course details from data attributes
                var theory = selectedOption.data('theory') || '';
                var practice = selectedOption.data('practice') || '';
                var credit = selectedOption.data('credit') || '';
                var hours = selectedOption.data('hours') || '';
                
                // Update fields
                container.find('.theory-hours').val(theory);
                container.find('.practice-hours').val(practice);
                container.find('.credits').val(credit);
                container.find('.total-hours').val(hours);
            });
            
            // คำนวณชั่วโมงรวมจากทฤษฎีและปฏิบัติสำหรับรายวิชาที่กรอกเอง
            $(document).on('input', '.custom-theory-hours, .custom-practice-hours', function() {
                var container = $(this).closest('.custom-course-container');
                var theory = parseInt(container.find('.custom-theory-hours').val()) || 0;
                var practice = parseInt(container.find('.custom-practice-hours').val()) || 0;
                var total = theory + practice;
                
                container.find('.custom-total-hours').val(total);
            });
            
            // Toggle between select course and custom course
            $(document).on('change', '.course-type-radio', function() {
                var container = $(this).closest('.course-item');
                var value = $(this).val();
                
                if (value === 'select') {
                    container.find('.select-course-container').show();
                    container.find('.custom-course-container').hide();
                    
                    // Enable required on select fields
                    container.find('.course-select').prop('required', true);
                    container.find('.teacher-select').prop('required', true);
                    
                    // Disable required on custom fields
                    container.find('.custom-course-code').prop('required', false);
                    container.find('.custom-course-name').prop('required', false);
                    container.find('.teacher-select-custom').prop('required', false);
                } else {
                    container.find('.select-course-container').hide();
                    container.find('.custom-course-container').show();
                    
                    // Disable required on select fields
                    container.find('.course-select').prop('required', false);
                    container.find('.teacher-select').prop('required', false);
                    
                    // Enable required on custom fields
                    container.find('.custom-course-code').prop('required', true);
                    container.find('.custom-course-name').prop('required', true);
                    container.find('.teacher-select-custom').prop('required', true);
                }
            });
            
            // Add course button click
            $('.add-course-btn').click(function() {
                // Get current index - count existing items to ensure unique index
                var currentIndex = $('.course-item').length;
                
                // Clone template HTML
                var template = $('#courseItemTemplate').html();
                
                // Replace all INDEX placeholders with the current index
                template = template.replace(/INDEX/g, currentIndex);
                
                // Append to container
                $('.course-items').append(template);
                
                // Initialize newly added elements
                var newItem = $('.course-items .course-item:last');
                
                // Destroy and re-initialize Select2 for the new elements
                newItem.find('.course-select, .teacher-select, .teacher-select-custom').select2({
                    placeholder: "เลือกรายการ",
                    width: '100%',
                    dropdownParent: $('body')
                });
                
                // Set up course type toggles for the new item
                newItem.find('.course-type-radio').first().prop('checked', true);
                newItem.find('.select-course-container').show();
                newItem.find('.custom-course-container').hide();
                
                // ให้ focus ที่ select รายวิชาของรายการใหม่
                setTimeout(function() {
                    newItem.find('.course-select').select2('focus');
                }, 100);
            });
            
            // Remove course button click (uses event delegation for dynamic elements)
            $(document).on('click', '.remove-course-btn', function() {
                // Only allow removal if there's more than one course item
                if ($('.course-item').length > 1) {
                    $(this).closest('.course-item').remove();
                    
                    // ไม่ต้อง re-index เพราะ server จะรับค่าตามที่ส่งมาโดยไม่ได้ใช้ index เป็นลำดับ
                    // แต่จะใช้ index เป็นตัวระบุตำแหน่งเท่านั้น
                } else {
                    Swal.fire({
                        title: 'ไม่สามารถลบได้',
                        text: 'ต้องมีรายวิชาอย่างน้อย 1 รายวิชา',
                        icon: 'warning',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });

            // Form validation before submission
            $('#courseRequestForm').submit(function(e) {
                var isValid = true;
                var errorMessages = [];
                
                // ตรวจสอบแต่ละรายวิชา
                $('.course-item').each(function(i) {
                    var visibleIndex = i + 1;  // ใช้ visibleIndex สำหรับการแสดงผลต่อผู้ใช้
                    var courseItem = $(this);
                    var courseType = courseItem.find('input[name^="course_type"]:checked').val();
                    
                    // ตรวจสอบเฉพาะส่วนที่กำลังแสดงผลตามประเภทที่เลือก
                    if (courseType === 'select') {
                        // ส่วนเลือกรายวิชาจากระบบจะถูกตรวจสอบเมื่อแสดงผล
                        if (courseItem.find('.select-course-container').is(':visible')) {
                            if (!courseItem.find('.course-select').val()) {
                                isValid = false;
                                errorMessages.push('กรุณาเลือกรายวิชาที่ ' + visibleIndex);
                            }
                            
                            if (!courseItem.find('.teacher-select').val()) {
                                isValid = false;
                                errorMessages.push('กรุณาเลือกครูประจำวิชาที่ ' + visibleIndex);
                            }
                        }
                    } else if (courseType === 'custom') {
                        // ส่วนกรอกข้อมูลรายวิชาเองจะถูกตรวจสอบเมื่อแสดงผล
                        if (courseItem.find('.custom-course-container').is(':visible')) {
                            if (!courseItem.find('.custom-course-code').val()) {
                                isValid = false;
                                errorMessages.push('กรุณากรอกรหัสวิชาที่ ' + visibleIndex);
                            }
                            
                            if (!courseItem.find('.custom-course-name').val()) {
                                isValid = false;
                                errorMessages.push('กรุณากรอกชื่อรายวิชาที่ ' + visibleIndex);
                            }
                            
                            if (!courseItem.find('.teacher-select-custom').val()) {
                                isValid = false;
                                errorMessages.push('กรุณาเลือกครูประจำวิชาที่ ' + visibleIndex);
                            }
                        }
                    }
                });
                
                // แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด
                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อมูลไม่ครบถ้วน',
                        html: errorMessages.join('<br>'),
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // Check for duplicate courses
                var courseCodes = [];
                var hasDuplicate = false;
                
                $('.course-item').each(function() {
                    var courseItem = $(this);
                    var courseType = courseItem.find('input[name^="course_type"]:checked').val();
                    var courseCode = '';
                    
                    if (courseType === 'select') {
                        var courseId = courseItem.find('.course-select').val();
                        if (courseId) {
                            var courseText = courseItem.find('.course-select option:selected').text();
                            courseCode = courseText.split(' - ')[0].trim();
                        }
                    } else {
                        courseCode = courseItem.find('.custom-course-code').val().trim();
                    }
                    
                    if (courseCode && courseCodes.includes(courseCode)) {
                        hasDuplicate = true;
                        return false; // break the loop
                    }
                    
                    if (courseCode) {
                        courseCodes.push(courseCode);
                    }
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