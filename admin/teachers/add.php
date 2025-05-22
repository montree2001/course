<?php
// admin/teachers/add.php
// หน้าเพิ่มข้อมูลครู

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'เพิ่มข้อมูลครู';
$currentPage = 'teachers';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

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
    
    // ถ้าไม่มีข้อผิดพลาด ให้บันทึกข้อมูล
    if (empty($errors)) {
        try {
            // เพิ่มข้อมูลครู
            $stmt = $pdo->prepare("
                INSERT INTO teachers (prefix, first_name, last_name, department_id)
                VALUES (:prefix, :first_name, :last_name, :department_id)
            ");
            
            $stmt->execute([
                'prefix' => $prefix,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'department_id' => $department_id
            ]);
            
            // สร้างข้อความแจ้งเตือน
            $_SESSION['success_msg'] = 'เพิ่มข้อมูลครูเรียบร้อยแล้ว';
            
            // Redirect กลับไปหน้ารายการครู
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error_msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
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
        <li class="breadcrumb-item active" aria-current="page">เพิ่มข้อมูลครู</li>
    </ol>
</nav>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">เพิ่มข้อมูลครู</h1>
</div>

<!-- Alert messages -->
<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Teacher form card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">กรอกข้อมูลครู</h6>
    </div>
    <div class="card-body">
        <form method="post" id="teacherForm">
            <div class="row">
                <div class="col-md-2">
                    <div class="mb-3">
                        <label for="prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                        <select class="form-select" id="prefix" name="prefix" required>
                            <option value="">-- เลือกคำนำหน้า --</option>
                            <option value="นาย" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                            <option value="นาง" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                            <option value="นางสาว" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                            <option value="ว่าที่ร้อยตรี" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'ว่าที่ร้อยตรี') ? 'selected' : ''; ?>>ว่าที่ร้อยตรี</option>
                            <option value="ดร." <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
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
                                <option value="<?php echo $department['department_id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
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
                    <i class="bi bi-save"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
    
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
        }
    });
});
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>