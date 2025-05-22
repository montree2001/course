<?php
// admin/courses/edit.php
// หน้าสำหรับแก้ไขข้อมูลรายวิชา

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'แก้ไขข้อมูลรายวิชา';
$currentPage = 'courses';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // บันทึกข้อความแจ้งเตือน
    $_SESSION['error_message'] = 'ไม่พบรายวิชาที่ต้องการแก้ไข';
    
    // Redirect ไปยังหน้ารายการรายวิชา
    header('Location: index.php');
    exit;
}

$course_id = $_GET['id'];

// ดึงข้อมูลรายวิชาจาก ID
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = :course_id");
    $stmt->execute(['course_id' => $course_id]);
    $course = $stmt->fetch();
    
    // ถ้าไม่พบข้อมูลรายวิชา
    if (!$course) {
        // บันทึกข้อความแจ้งเตือน
        $_SESSION['error_message'] = 'ไม่พบข้อมูลรายวิชาที่ต้องการแก้ไข';
        
        // Redirect ไปยังหน้ารายการรายวิชา
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    // บันทึกข้อความแจ้งเตือน
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    
    // Redirect ไปยังหน้ารายการรายวิชา
    header('Location: index.php');
    exit;
}

// ตรวจสอบการส่งฟอร์มแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $course_code = $_POST['course_code'] ?? '';
    $course_name = $_POST['course_name'] ?? '';
    $theory_hours = $_POST['theory_hours'] ?? 0;
    $practice_hours = $_POST['practice_hours'] ?? 0;
    $credit_hours = $_POST['credit_hours'] ?? 0;

    // ตรวจสอบข้อมูล
    $errors = [];

    if (empty($course_code)) {
        $errors[] = 'กรุณาระบุรหัสวิชา';
    } else {
        // ตรวจสอบว่ารหัสวิชาซ้ำหรือไม่ (ยกเว้นรายวิชาปัจจุบัน)
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_code = :course_code AND course_id != :course_id");
        $stmt->execute([
            'course_code' => $course_code,
            'course_id' => $course_id
        ]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'รหัสวิชานี้มีอยู่ในระบบแล้ว';
        }
    }

    if (empty($course_name)) {
        $errors[] = 'กรุณาระบุชื่อรายวิชา';
    }

    if ($theory_hours < 0) {
        $errors[] = 'จำนวนชั่วโมงทฤษฎีต้องไม่น้อยกว่า 0';
    }

    if ($practice_hours < 0) {
        $errors[] = 'จำนวนชั่วโมงปฏิบัติต้องไม่น้อยกว่า 0';
    }

    if ($credit_hours <= 0) {
        $errors[] = 'จำนวนหน่วยกิตต้องมากกว่า 0';
    }

    // ถ้าไม่มีข้อผิดพลาด ให้บันทึกข้อมูล
    if (empty($errors)) {
        try {
            // เตรียมคำสั่ง SQL
            $stmt = $pdo->prepare("
                UPDATE courses 
                SET course_code = :course_code, 
                    course_name = :course_name, 
                    theory_hours = :theory_hours, 
                    practice_hours = :practice_hours, 
                    credit_hours = :credit_hours, 
                    updated_at = NOW()
                WHERE course_id = :course_id
            ");

            // ประมวลผลคำสั่ง SQL
            $result = $stmt->execute([
                'course_code' => $course_code,
                'course_name' => $course_name,
                'theory_hours' => $theory_hours,
                'practice_hours' => $practice_hours,
                'credit_hours' => $credit_hours,
                'course_id' => $course_id
            ]);

            if ($result) {
                // บันทึกข้อความแจ้งเตือน
                $_SESSION['success_message'] = 'แก้ไขข้อมูลรายวิชาเรียบร้อยแล้ว';
                
                // Redirect ไปยังหน้ารายการรายวิชา
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            }
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage();
        }
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการข้อมูลรายวิชา</a></li>
        <li class="breadcrumb-item active" aria-current="page">แก้ไขข้อมูลรายวิชา</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">แก้ไขข้อมูลรายวิชา</h1>
</div>

<!-- แสดงข้อผิดพลาด (ถ้ามี) -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>พบข้อผิดพลาด:</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Content Row -->
<div class="row">
    <div class="col-xl-12 col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">แก้ไขข้อมูลรายวิชา ID: <?php echo $course_id; ?></h6>
                <div>
                    <span class="text-xs text-muted">วันที่สร้าง: <?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></span>
                    <span class="text-xs text-muted ms-2">วันที่แก้ไขล่าสุด: <?php echo date('d/m/Y H:i', strtotime($course['updated_at'])); ?></span>
                </div>
            </div>
            <div class="card-body">
                <form action="edit.php?id=<?php echo $course_id; ?>" method="post" id="courseForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="course_code" class="form-label">รหัสวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : htmlspecialchars($course['course_code']); ?>" required>
                            <div class="form-text">ตัวอย่าง: 3000-1301</div>
                        </div>
                        <div class="col-md-8">
                            <label for="course_name" class="form-label">ชื่อรายวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo isset($_POST['course_name']) ? htmlspecialchars($_POST['course_name']) : htmlspecialchars($course['course_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="theory_hours" class="form-label">จำนวนชั่วโมงทฤษฎี</label>
                            <input type="number" class="form-control" id="theory_hours" name="theory_hours" min="0" value="<?php echo isset($_POST['theory_hours']) ? htmlspecialchars($_POST['theory_hours']) : htmlspecialchars($course['theory_hours']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="practice_hours" class="form-label">จำนวนชั่วโมงปฏิบัติ</label>
                            <input type="number" class="form-control" id="practice_hours" name="practice_hours" min="0" value="<?php echo isset($_POST['practice_hours']) ? htmlspecialchars($_POST['practice_hours']) : htmlspecialchars($course['practice_hours']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="credit_hours" class="form-label">จำนวนหน่วยกิต <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="credit_hours" name="credit_hours" min="1" value="<?php echo isset($_POST['credit_hours']) ? htmlspecialchars($_POST['credit_hours']) : htmlspecialchars($course['credit_hours']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-arrow-left"></i> ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Form Validation -->
<script>
    $(document).ready(function () {
        $('#courseForm').submit(function (event) {
            // รีเซ็ตข้อความแจ้งเตือน
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            
            let isValid = true;
            
            // ตรวจสอบรหัสวิชา
            const courseCode = $('#course_code').val().trim();
            if (courseCode === '') {
                $('#course_code').addClass('is-invalid');
                $('#course_code').after('<div class="invalid-feedback">กรุณาระบุรหัสวิชา</div>');
                isValid = false;
            }
            
            // ตรวจสอบชื่อรายวิชา
            const courseName = $('#course_name').val().trim();
            if (courseName === '') {
                $('#course_name').addClass('is-invalid');
                $('#course_name').after('<div class="invalid-feedback">กรุณาระบุชื่อรายวิชา</div>');
                isValid = false;
            }
            
            // ตรวจสอบจำนวนหน่วยกิต
            const creditHours = parseInt($('#credit_hours').val());
            if (isNaN(creditHours) || creditHours <= 0) {
                $('#credit_hours').addClass('is-invalid');
                $('#credit_hours').after('<div class="invalid-feedback">จำนวนหน่วยกิตต้องมากกว่า 0</div>');
                isValid = false;
            }
            
            // ถ้าข้อมูลไม่ถูกต้อง ให้ยกเลิกการส่งฟอร์ม
            if (!isValid) {
                event.preventDefault();
                
                // แสดง SweetAlert
                Swal.fire({
                    title: 'ข้อมูลไม่ถูกต้อง!',
                    text: 'กรุณาตรวจสอบข้อมูลในฟอร์มอีกครั้ง',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            } else {
                // แสดง loading
                showLoader();
            }
            
            return isValid;
        });
        
        // คำนวณหน่วยกิตอัตโนมัติเมื่อมีการเปลี่ยนแปลงจำนวนชั่วโมง
        $('#theory_hours, #practice_hours').on('change', function() {
            calculateCreditHours();
        });
        
        function calculateCreditHours() {
            const theoryHours = parseInt($('#theory_hours').val()) || 0;
            const practiceHours = parseInt($('#practice_hours').val()) || 0;
            
            // คำนวณหน่วยกิต (ทฤษฎี + ปฏิบัติ/2)
            let creditHours = theoryHours + (practiceHours / 2);
            
            // ปัดเศษ
            creditHours = Math.ceil(creditHours);
            
            // ถ้าหน่วยกิตเป็น 0 ให้เป็น 1
            if (creditHours <= 0 && (theoryHours > 0 || practiceHours > 0)) {
                creditHours = 1;
            }
            
            $('#credit_hours').val(creditHours);
        }
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>