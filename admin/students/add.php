<?php
// admin/students/add.php
// หน้าเพิ่มข้อมูลนักเรียนใหม่

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'เพิ่มข้อมูลนักเรียน';
$currentPage = 'students';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบการส่งฟอร์ม
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบการกรอกข้อมูลที่จำเป็น
        if (empty($_POST['student_code']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
            throw new Exception('กรุณากรอกข้อมูลที่จำเป็น (รหัสนักเรียน, ชื่อ, นามสกุล)');
        }

        // ตรวจสอบว่ารหัสนักเรียนซ้ำหรือไม่
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_code = :student_code");
        $stmt->execute(['student_code' => $_POST['student_code']]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('รหัสนักเรียนนี้มีอยู่ในระบบแล้ว กรุณาตรวจสอบอีกครั้ง');
        }

        // เตรียมข้อมูลสำหรับบันทึก
        $data = [
            'student_code' => $_POST['student_code'],
            'prefix' => $_POST['prefix'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'level' => $_POST['level'],
            'year' => $_POST['year'],
            'department_id' => $_POST['department_id'] ?: null,
            'phone' => $_POST['phone'] ?: null
        ];

        // บันทึกข้อมูลนักเรียนใหม่
        $sql = "INSERT INTO students (student_code, prefix, first_name, last_name, level, year, department_id, phone)
                VALUES (:student_code, :prefix, :first_name, :last_name, :level, :year, :department_id, :phone)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        // บันทึกสำเร็จ
        $student_id = $pdo->lastInsertId();
        $success = 'เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว';

        // เคลียร์ฟอร์ม
        $_POST = [];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ดึงข้อมูลสาขาวิชาสำหรับ dropdown
try {
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลสาขาวิชา: ' . $e->getMessage();
    $departments = [];
}
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">เพิ่มข้อมูลนักเรียน</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> กลับไปยังรายการ
    </a>
</div>

<!-- Form Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">กรอกข้อมูลนักเรียน</h6>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" id="studentForm">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="student_code" class="form-label">รหัสนักเรียน <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_code" name="student_code" value="<?php echo isset($_POST['student_code']) ? htmlspecialchars($_POST['student_code']) : ''; ?>" required>
                </div>
                
                <div class="col-md-2">
                    <label for="prefix" class="form-label">คำนำหน้า</label>
                    <select class="form-select" id="prefix" name="prefix">
                        <option value="นาย" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                        <option value="นางสาว" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                        <option value="นาง" <?php echo (isset($_POST['prefix']) && $_POST['prefix'] === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="level" class="form-label">ระดับชั้น</label>
                    <select class="form-select" id="level" name="level">
                        <option value="ปวช." <?php echo (isset($_POST['level']) && $_POST['level'] === 'ปวช.') ? 'selected' : ''; ?>>ปวช.</option>
                        <option value="ปวส." <?php echo (isset($_POST['level']) && $_POST['level'] === 'ปวส.') ? 'selected' : ''; ?>>ปวส.</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="year" class="form-label">ชั้นปีที่</label>
                    <select class="form-select" id="year" name="year">
                        <option value="1" <?php echo (isset($_POST['year']) && $_POST['year'] === '1') ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo (isset($_POST['year']) && $_POST['year'] === '2') ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo (isset($_POST['year']) && $_POST['year'] === '3') ? 'selected' : ''; ?>>3</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="department_id" class="form-label">สาขาวิชา</label>
                    <select class="form-select select2" id="department_id" name="department_id">
                        <option value="">-- เลือกสาขาวิชา --</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['department_id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> บันทึกข้อมูล
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> ล้างฟอร์ม
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        // Form validation
        $('#studentForm').submit(function(e) {
            // ตรวจสอบการกรอกข้อมูลที่จำเป็น
            const studentCode = $('#student_code').val().trim();
            const firstName = $('#first_name').val().trim();
            const lastName = $('#last_name').val().trim();
            
            if (!studentCode || !firstName || !lastName) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'กรุณากรอกข้อมูลที่จำเป็น (รหัสนักเรียน, ชื่อ, นามสกุล)',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                
                return false;
            }
            
            // ตรวจสอบรูปแบบรหัสนักเรียน (ถ้ามีการกำหนดรูปแบบเฉพาะ)
            // const studentCodePattern = /^\d{10}$/; // ตัวอย่างรูปแบบรหัสนักเรียน 10 หลัก
            // if (!studentCodePattern.test(studentCode)) {
            //     e.preventDefault();
            //     
            //     Swal.fire({
            //         title: 'ข้อผิดพลาด!',
            //         text: 'รหัสนักเรียนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง',
            //         icon: 'error',
            //         confirmButtonText: 'ตกลง'
            //     });
            //     
            //     return false;
            // }
            
            // แสดง loading ระหว่างการส่งฟอร์ม
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            return true;
        });
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>