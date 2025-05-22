<?php
// admin/teachers/edit.php
// หน้าแก้ไขข้อมูลครู

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'แก้ไขข้อมูลครู';
$currentPage = 'teachers';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการระบุ ID ครู
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_msg'] = 'ไม่พบข้อมูลครู';
    header('Location: index.php');
    exit;
}

$teacher_id = $_GET['id'];

// ดึงข้อมูลครู
try {
    $stmt = $pdo->prepare("
        SELECT t.*, d.department_name 
        FROM teachers t
        LEFT JOIN departments d ON t.department_id = d.department_id
        WHERE t.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        $_SESSION['error_msg'] = 'ไม่พบข้อมูลครู';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_msg'] = 'เกิดข้อผิดพลาดในการดึงข้อมูลครู: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลสาขาวิชา
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = 'เกิดข้อผิดพลาดในการดึงข้อมูลสาขาวิชา: ' . $e->getMessage();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $prefix = $_POST['prefix'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $department_id = $_POST['department_id'] ?? null;
    
    // ตรวจสอบข้อมูล
    $errors = [];
    
    if (empty($prefix)) {
        $errors[] = 'กรุณาเลือกคำนำหน้า';
    }
    
    if (empty($first_name)) {
        $errors[] = 'กรุณากรอกชื่อ';
    }
    
    if (empty($last_name)) {
        $errors[] = 'กรุณากรอกนามสกุล';
    }
    
    if (empty($department_id)) {
        $errors[] = 'กรุณาเลือกสาขาวิชา';
    }
    
    // ถ้าไม่มีข้อผิดพลาด ให้อัปเดตข้อมูล
    if (empty($errors)) {
        try {
            // อัปเดตข้อมูลครู
            $stmt = $pdo->prepare("
                UPDATE teachers 
                SET prefix = :prefix, 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    department_id = :department_id
                WHERE teacher_id = :teacher_id
            ");
            
            $stmt->execute([
                'prefix' => $prefix,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'department_id' => $department_id,
                'teacher_id' => $teacher_id
            ]);
            
            // สร้างข้อความแจ้งเตือน
            $_SESSION['success_msg'] = 'อัปเดตข้อมูลครูเรียบร้อยแล้ว';
            
            // Redirect กลับไปหน้ารายการครู
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error_msg = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage();
        }
    } else {
        $error_msg = implode('<br>', $errors);
    }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการข้อมูลครู</a></li>
        <li class="breadcrumb-item active" aria-current="page">แก้ไขข้อมูลครู</li>
    </ol>
</nav>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">แก้ไขข้อมูลครู</h1>
</div>

<!-- Alert messages -->
<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Teacher information card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ข้อมูลครู</h6>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-3">
                            <div style="width: 100px; height: 100px; background-color: #6c757d; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 2.5rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <h5 class="card-title text-center"><?php echo $teacher['prefix'] . $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h5>
                        <p class="card-text text-center text-muted"><?php echo $teacher['department_name'] ?? 'ไม่ระบุสาขาวิชา'; ?></p>
                        
                        <hr>
                        
                        <?php
                        // ดึงจำนวนรายวิชาที่ครูสอน
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM request_details WHERE teacher_id = ?");
                            $stmt->execute([$teacher_id]);
                            $course_count = $stmt->fetch()['count'];
                        } catch (PDOException $e) {
                            $course_count = 0;
                        }
                        ?>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>จำนวนรายวิชาที่สอน:</span>
                            <span class="badge bg-primary"><?php echo $course_count; ?> รายวิชา</span>
                        </div>
                        
                        <?php if ($course_count > 0): ?>
                            <div class="alert alert-info mb-0" role="alert">
                                <i class="bi bi-info-circle-fill"></i> ครูท่านนี้มีรายวิชาที่สอนอยู่ การเปลี่ยนแปลงข้อมูลจะมีผลต่อรายวิชาที่เกี่ยวข้อง
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8 col-md-6">
                <form method="post" id="teacherForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                <select class="form-select" id="prefix" name="prefix" required>
                                    <option value="">-- เลือกคำนำหน้า --</option>
                                    <option value="นาย" <?php echo ($teacher['prefix'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                    <option value="นาง" <?php echo ($teacher['prefix'] === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                    <option value="นางสาว" <?php echo ($teacher['prefix'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                    <option value="ว่าที่ร้อยตรี" <?php echo ($teacher['prefix'] === 'ว่าที่ร้อยตรี') ? 'selected' : ''; ?>>ว่าที่ร้อยตรี</option>
                                    <option value="ดร." <?php echo ($teacher['prefix'] === 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">สาขาวิชา <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="department_id" name="department_id" required>
                                    <option value="">-- เลือกสาขาวิชา --</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($teacher['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo $department['department_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- สำหรับข้อมูลเพิ่มเติมในอนาคต เช่น อีเมล, โทรศัพท์ -->
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($course_count > 0): ?>
            <!-- แสดงรายวิชาที่ครูสอน -->
            <div class="card mb-0">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">รายวิชาที่สอน</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="coursesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%">ลำดับ</th>
                                    <th width="15%">รหัสวิชา</th>
                                    <th width="40%">ชื่อรายวิชา</th>
                                    <th width="15%">หน่วยกิต</th>
                                    <th width="25%">นักเรียน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // ดึงข้อมูลรายวิชาที่ครูสอน
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT rd.detail_id, c.course_code, c.course_name, c.credit_hours,
                                               s.prefix as student_prefix, s.first_name as student_first_name, s.last_name as student_last_name
                                        FROM request_details rd
                                        JOIN courses c ON rd.course_id = c.course_id
                                        JOIN course_requests cr ON rd.request_id = cr.request_id
                                        JOIN students s ON cr.student_id = s.student_id
                                        WHERE rd.teacher_id = ?
                                        ORDER BY c.course_code
                                    ");
                                    $stmt->execute([$teacher_id]);
                                    $courses = $stmt->fetchAll();
                                    
                                    foreach ($courses as $index => $course):
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td class="text-center"><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo $course['student_prefix'] . $course['student_first_name'] . ' ' . $course['student_last_name']; ?></td>
                                </tr>
                                <?php
                                    endforeach;
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="5" class="text-center text-danger">เกิดข้อผิดพลาดในการดึงข้อมูลรายวิชา: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
    
    // Initialize DataTable for courses
    <?php if ($course_count > 0): ?>
    $('#coursesTable').DataTable({
        pageLength: 10,
        responsive: true
    });
    <?php endif; ?>
    
    // Form validation
    const teacherForm = document.getElementById('teacherForm');
    
    teacherForm.addEventListener('submit', function(event) {
        let isValid = true;
        
        // ตรวจสอบคำนำหน้า
        const prefix = document.getElementById('prefix').value;
        if (!prefix) {
            isValid = false;
            document.getElementById('prefix').classList.add('is-invalid');
        } else {
            document.getElementById('prefix').classList.remove('is-invalid');
        }
        
        // ตรวจสอบชื่อ
        const firstName = document.getElementById('first_name').value.trim();
        if (!firstName) {
            isValid = false;
            document.getElementById('first_name').classList.add('is-invalid');
        } else {
            document.getElementById('first_name').classList.remove('is-invalid');
        }
        
        // ตรวจสอบนามสกุล
        const lastName = document.getElementById('last_name').value.trim();
        if (!lastName) {
            isValid = false;
            document.getElementById('last_name').classList.add('is-invalid');
        } else {
            document.getElementById('last_name').classList.remove('is-invalid');
        }
        
        // ตรวจสอบสาขาวิชา
        const departmentId = document.getElementById('department_id').value;
        if (!departmentId) {
            isValid = false;
            document.getElementById('department_id').parentElement.classList.add('is-invalid');
        } else {
            document.getElementById('department_id').parentElement.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            event.preventDefault();
            
            // แสดง SweetAlert
            Swal.fire({
                title: 'ข้อมูลไม่ครบถ้วน',
                text: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        } else {
            <?php if ($course_count > 0): ?>
            // หากครูมีการสอนรายวิชา ให้แสดงการยืนยัน
            event.preventDefault();
            
            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                text: 'การแก้ไขข้อมูลจะมีผลต่อรายวิชาที่ครูท่านนี้สอนอยู่ ต้องการดำเนินการต่อหรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง loading
                    Swal.fire({
                        title: 'กำลังบันทึกข้อมูล',
                        text: 'กรุณารอสักครู่...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // ส่งฟอร์ม
                    teacherForm.submit();
                }
            });
            <?php else: ?>
            // แสดง loading
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล',
                text: 'กรุณารอสักครู่...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            <?php endif; ?>
        }
    });
});
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>