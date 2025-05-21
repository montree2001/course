<?php
// student/request_form.php
// หน้าแบบฟอร์มยื่นคำร้องขอเปิดรายวิชา

session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

$error = '';
$success = '';

// ถ้ามีการส่งแบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // เริ่ม Transaction
        $pdo->beginTransaction();
        
        // ตรวจสอบข้อมูลนักเรียน
        $prefix = $_POST['prefix'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $student_code = $_POST['student_code'] ?? '';
        $level = $_POST['level'] ?? '';
        $year = $_POST['year'] ?? '';
        $department_id = $_POST['department_id'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($first_name) || empty($last_name) || empty($student_code) || 
            empty($level) || empty($year) || empty($department_id) || empty($phone)) {
            throw new Exception('กรุณากรอกข้อมูลส่วนตัวให้ครบถ้วน');
        }
        
        // ตรวจสอบว่าเพิ่มนักเรียนใหม่หรือใช้ข้อมูลที่มีอยู่
        $student = getStudentByCode($pdo, $student_code);
        
        if (!$student) {
            // เพิ่มข้อมูลนักเรียนใหม่
            $stmt = $pdo->prepare("
                INSERT INTO students (prefix, first_name, last_name, student_code, level, year, department_id, phone)
                VALUES (:prefix, :first_name, :last_name, :student_code, :level, :year, :department_id, :phone)
            ");
            
            $stmt->execute([
                'prefix' => $prefix,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'student_code' => $student_code,
                'level' => $level,
                'year' => $year,
                'department_id' => $department_id,
                'phone' => $phone
            ]);
            
            $student_id = $pdo->lastInsertId();
        } else {
            // ใช้ข้อมูลที่มีอยู่
            $student_id = $student['student_id'];
            
            // อัปเดตข้อมูลนักเรียน
            $stmt = $pdo->prepare("
                UPDATE students 
                SET prefix = :prefix, 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    level = :level, 
                    year = :year, 
                    department_id = :department_id, 
                    phone = :phone
                WHERE student_id = :student_id
            ");
            
            $stmt->execute([
                'prefix' => $prefix,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'level' => $level,
                'year' => $year,
                'department_id' => $department_id,
                'phone' => $phone,
                'student_id' => $student_id
            ]);
        }
        
        // เพิ่มข้อมูลคำร้อง
        $semester = $_POST['semester'] ?? '';
        $academic_year = $_POST['academic_year'] ?? '';
        $request_date = date('Y-m-d');
        
        if (empty($semester) || empty($academic_year)) {
            throw new Exception('กรุณาเลือกภาคเรียนและปีการศึกษา');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO course_requests (student_id, semester, academic_year, request_date, status)
            VALUES (:student_id, :semester, :academic_year, :request_date, 'รอดำเนินการ')
        ");
        
        $stmt->execute([
            'student_id' => $student_id,
            'semester' => $semester,
            'academic_year' => $academic_year,
            'request_date' => $request_date
        ]);
        
        $request_id = $pdo->lastInsertId();
        
        // ตรวจสอบว่ามีการเลือกรายวิชาหรือไม่
        if (empty($_POST['course_id']) || !is_array($_POST['course_id'])) {
            throw new Exception('กรุณาเลือกรายวิชาอย่างน้อย 1 วิชา');
        }
        
        // เพิ่มข้อมูลรายละเอียดคำร้อง (รายวิชาที่ขอเปิด)
        foreach ($_POST['course_id'] as $key => $course_id) {
            $teacher_id = $_POST['teacher_id'][$key] ?? null;
            
            if (empty($course_id) || empty($teacher_id)) {
                continue;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO request_details (request_id, course_id, teacher_id)
                VALUES (:request_id, :course_id, :teacher_id)
            ");
            
            $stmt->execute([
                'request_id' => $request_id,
                'course_id' => $course_id,
                'teacher_id' => $teacher_id
            ]);
        }
        
        // บันทึกการติดตามสถานะ
        $stmt = $pdo->prepare("
            INSERT INTO status_tracking (request_id, status, comment, updated_by)
            VALUES (:request_id, :status, :comment, :updated_by)
        ");
        
        $stmt->execute([
            'request_id' => $request_id,
            'status' => 'ยื่นคำร้องเรียบร้อยแล้ว',
            'comment' => 'รอการพิจารณาจากครูที่ปรึกษา',
            'updated_by' => 'ระบบ'
        ]);
        
        // Commit Transaction
        $pdo->commit();
        
        $success = 'ยื่นคำร้องเรียบร้อยแล้ว รหัสคำร้อง: ' . $request_id;
        
        // เคลียร์ข้อมูลในฟอร์ม
        $_POST = [];
    } catch (Exception $e) {
        // Rollback Transaction
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// ดึงข้อมูลสาขาวิชา
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// ดึงข้อมูลรายวิชา
$stmt = $pdo->query("SELECT * FROM courses ORDER BY course_code");
$courses = $stmt->fetchAll();

// ดึงข้อมูลครู
$stmt = $pdo->query("
    SELECT t.*, d.department_name 
    FROM teachers t
    JOIN departments d ON t.department_id = d.department_id
    ORDER BY t.first_name
");
$teachers = $stmt->fetchAll();

// ตั้งค่าค่าเริ่มต้นสำหรับฟอร์ม
$current_year = date('Y');
$thai_year = $current_year + 543;
$default_academic_year = $thai_year;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .form-section {
            margin-bottom: 30px;
        }
        
        .btn-remove-course {
            margin-top: 32px;
        }
        
        @media (max-width: 767.98px) {
            .btn-remove-course {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="request_form.php">ยื่นคำร้อง</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track_status.php">ตรวจสอบสถานะ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">แบบฟอร์มคำร้องขอเปิดรายวิชาภาคเรียนพิเศษ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" id="requestForm">
                            <!-- ข้อมูลนักเรียน -->
                            <div class="form-section">
                                <h5 class="mb-3">ข้อมูลนักเรียน/นักศึกษา</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label for="prefix" class="form-label">คำนำหน้า</label>
                                        <select class="form-select" id="prefix" name="prefix" required>
                                            <option value="นาย" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                            <option value="นางสาว" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <label for="first_name" class="form-label">ชื่อ</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <label for="last_name" class="form-label">นามสกุล</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="student_code" class="form-label">รหัสประจำตัว</label>
                                        <input type="text" class="form-control" id="student_code" name="student_code" value="<?php echo $_POST['student_code'] ?? ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="level" class="form-label">ระดับชั้น</label>
                                        <select class="form-select" id="level" name="level" required>
                                            <option value="">-- เลือกระดับชั้น --</option>
                                            <option value="ปวช." <?php echo (isset($_POST['level']) && $_POST['level'] === 'ปวช.') ? 'selected' : ''; ?>>ปวช.</option>
                                            <option value="ปวส." <?php echo (isset($_POST['level']) && $_POST['level'] === 'ปวส.') ? 'selected' : ''; ?>>ปวส.</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="year" class="form-label">ชั้นปีที่</label>
                                        <select class="form-select" id="year" name="year" required>
                                            <option value="">-- เลือกชั้นปี --</option>
                                            <option value="1" <?php echo (isset($_POST['year']) && $_POST['year'] === '1') ? 'selected' : ''; ?>>1</option>
                                            <option value="2" <?php echo (isset($_POST['year']) && $_POST['year'] === '2') ? 'selected' : ''; ?>>2</option>
                                            <option value="3" <?php echo (isset($_POST['year']) && $_POST['year'] === '3') ? 'selected' : ''; ?>>3</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="department_id" class="form-label">สาขาวิชา</label>
                                        <select class="form-select select2" id="department_id" name="department_id" required>
                                            <option value="">-- เลือกสาขาวิชา --</option>
                                            <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo $department['department_id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $department['department_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">เบอร์โทรศัพท์ที่ติดต่อได้</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ข้อมูลการขอเปิดรายวิชา -->
                            <div class="form-section">
                                <h5 class="mb-3">ข้อมูลการขอเปิดรายวิชา</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="semester" class="form-label">ภาคเรียนที่</label>
                                        <select class="form-select" id="semester" name="semester" required>
                                            <option value="">-- เลือกภาคเรียน --</option>
                                            <option value="1" <?php echo (isset($_POST['semester']) && $_POST['semester'] === '1') ? 'selected' : ''; ?>>1</option>
                                            <option value="2" <?php echo (isset($_POST['semester']) && $_POST['semester'] === '2') ? 'selected' : ''; ?>>2</option>
                                            <option value="3" <?php echo (isset($_POST['semester']) && $_POST['semester'] === '3') ? 'selected' : ''; ?>>ฤดูร้อน</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="academic_year" class="form-label">ปีการศึกษา</label>
                                        <select class="form-select" id="academic_year" name="academic_year" required>
                                            <option value="">-- เลือกปีการศึกษา --</option>
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <?php $year = $default_academic_year + $i; ?>
                                                <option value="<?php echo $year; ?>" <?php echo (isset($_POST['academic_year']) && $_POST['academic_year'] == $year) ? 'selected' : ($i === 0 ? 'selected' : ''); ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- รายวิชาที่ต้องการขอเปิด -->
                            <div class="form-section">
                                <h5 class="mb-3">รายวิชาที่ต้องการขอเปิด</h5>
                                
                                <div id="courseContainer">
                                    <!-- รายวิชาแรก -->
                                    <div class="course-item mb-3 p-3 border rounded">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">รายวิชา</label>
                                                <select class="form-select course-select select2" name="course_id[]" required>
                                                    <option value="">-- เลือกรายวิชา --</option>
                                                    <?php foreach ($courses as $course): ?>
                                                        <option value="<?php echo $course['course_id']; ?>">
                                                            <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-5">
                                                <label class="form-label">ครูประจำรายวิชา</label>
                                                <select class="form-select teacher-select select2" name="teacher_id[]" required>
                                                    <option value="">-- เลือกครูประจำรายวิชา --</option>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <option value="<?php echo $teacher['teacher_id']; ?>">
                                                            <?php echo $teacher['prefix'] . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-remove-course d-none">
                                                    <i class="bi bi-trash"></i> ลบ
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                    <button type="button" id="btnAddCourse" class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> เพิ่มรายวิชา
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="button" class="btn btn-secondary" onclick="window.location.href='../index.php'">
                                    <i class="bi bi-x-circle"></i> ยกเลิก
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> ยื่นคำร้อง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
            
            // แสดงข้อความสำเร็จด้วย SweetAlert2
            <?php if (!empty($success)): ?>
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: '<?php echo $success; ?>',
                    icon: 'success',
                    confirmButtonText: 'ตกลง'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'track_status.php';
                    }
                });
            <?php endif; ?>
            
            // เพิ่มรายวิชา
            $('#btnAddCourse').click(function() {
                const courseItem = $('.course-item').first().clone();
                courseItem.find('select').val('');
                courseItem.find('.btn-remove-course').removeClass('d-none');
                
                // เพิ่มลงในคอนเทนเนอร์
                $('#courseContainer').append(courseItem);
                
                // Initialize Select2 for new elements
                courseItem.find('.select2').select2({
                    theme: 'bootstrap-5'
                });
            });
            
            // ลบรายวิชา
            $(document).on('click', '.btn-remove-course', function() {
                $(this).closest('.course-item').remove();
            });
            
            // ตรวจสอบข้อมูลก่อนส่งฟอร์ม
            $('#requestForm').submit(function(e) {
                const courseSelects = $('.course-select');
                const teacherSelects = $('.teacher-select');
                
                // ตรวจสอบว่าเลือกรายวิชาอย่างน้อย 1 วิชา
                if (courseSelects.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: 'กรุณาเลือกรายวิชาอย่างน้อย 1 วิชา',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // ตรวจสอบว่าเลือกรายวิชาและครูครบทุกช่อง
                let hasEmptyFields = false;
                
                courseSelects.each(function(index) {
                    if ($(this).val() === '') {
                        hasEmptyFields = true;
                        return false;
                    }
                });
                
                teacherSelects.each(function(index) {
                    if ($(this).val() === '') {
                        hasEmptyFields = true;
                        return false;
                    }
                });
                
                if (hasEmptyFields) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: 'กรุณาเลือกรายวิชาและครูประจำรายวิชาให้ครบทุกช่อง',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // ตรวจสอบว่าไม่มีรายวิชาซ้ำ
                const courseValues = [];
                let hasDuplicate = false;
                
                courseSelects.each(function() {
                    const value = $(this).val();
                    
                    if (value !== '' && courseValues.includes(value)) {
                        hasDuplicate = true;
                        return false;
                    }
                    
                    courseValues.push(value);
                });
                
                if (hasDuplicate) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: 'พบรายวิชาซ้ำในรายการ กรุณาเลือกรายวิชาที่ไม่ซ้ำกัน',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
            });
            
            // ค้นหาข้อมูลนักเรียนจากรหัสประจำตัว
            $('#student_code').blur(function() {
                const studentCode = $(this).val();
                
                if (studentCode === '') {
                    return;
                }
                
                $.ajax({
                    url: '../api/student_info.php',
                    type: 'POST',
                    data: { student_code: studentCode },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#prefix').val(response.data.prefix);
                            $('#first_name').val(response.data.first_name);
                            $('#last_name').val(response.data.last_name);
                            $('#level').val(response.data.level);
                            $('#year').val(response.data.year);
                            $('#department_id').val(response.data.department_id).trigger('change');
                            $('#phone').val(response.data.phone);
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>