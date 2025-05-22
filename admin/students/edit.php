<?php
// admin/students/edit.php
// หน้าแก้ไขข้อมูลนักเรียน

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'แก้ไขข้อมูลนักเรียน';
$currentPage = 'students';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // กรณีไม่มี id ให้แสดงข้อความผิดพลาดและกลับไปยังหน้ารายการ
    $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการแก้ไข';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$student_id = $_GET['id'];
$error = '';
$success = '';
$student = null;

// ดึงข้อมูลนักเรียนที่ต้องการแก้ไข
try {
    $stmt = $pdo->prepare("
        SELECT * FROM students 
        WHERE student_id = :student_id
    ");
    $stmt->execute(['student_id' => $student_id]);
    $student = $stmt->fetch();
    
    // ถ้าไม่พบข้อมูลนักเรียน
    if (!$student) {
        $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการแก้ไข';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบการกรอกข้อมูลที่จำเป็น
        if (empty($_POST['student_code']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
            throw new Exception('กรุณากรอกข้อมูลที่จำเป็น (รหัสนักเรียน, ชื่อ, นามสกุล)');
        }

        // ตรวจสอบว่ารหัสนักเรียนซ้ำหรือไม่ (ยกเว้นรหัสของนักเรียนคนนี้)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_code = :student_code AND student_id != :student_id");
        $stmt->execute([
            'student_code' => $_POST['student_code'],
            'student_id' => $student_id
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('รหัสนักเรียนนี้มีอยู่ในระบบแล้ว กรุณาตรวจสอบอีกครั้ง');
        }

        // เตรียมข้อมูลสำหรับอัปเดต
        $data = [
            'student_id' => $student_id,
            'student_code' => $_POST['student_code'],
            'prefix' => $_POST['prefix'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'level' => $_POST['level'],
            'year' => $_POST['year'],
            'department_id' => $_POST['department_id'] ?: null,
            'phone' => $_POST['phone'] ?: null
        ];

        // อัปเดตข้อมูลนักเรียน
        $sql = "UPDATE students SET 
                student_code = :student_code,
                prefix = :prefix,
                first_name = :first_name, 
                last_name = :last_name,
                level = :level,
                year = :year,
                department_id = :department_id,
                phone = :phone
                WHERE student_id = :student_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        // อัปเดตสำเร็จ
        $success = 'อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว';
        
        // ดึงข้อมูลนักเรียนที่อัปเดตแล้ว
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $student_id]);
        $student = $stmt->fetch();
        
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
    <h1 class="h3 mb-0">แก้ไขข้อมูลนักเรียน</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> กลับไปยังรายการ
    </a>
</div>

<!-- Form Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">แก้ไขข้อมูลนักเรียน</h6>
        <span class="badge bg-primary">รหัสนักเรียน: <?php echo htmlspecialchars($student['student_code']); ?></span>
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
                    <input type="text" class="form-control" id="student_code" name="student_code" value="<?php echo htmlspecialchars($student['student_code']); ?>" required>
                </div>
                
                <div class="col-md-2">
                    <label for="prefix" class="form-label">คำนำหน้า</label>
                    <select class="form-select" id="prefix" name="prefix">
                        <option value="นาย" <?php echo ($student['prefix'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                        <option value="นางสาว" <?php echo ($student['prefix'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                        <option value="นาง" <?php echo ($student['prefix'] === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="level" class="form-label">ระดับชั้น</label>
                    <select class="form-select" id="level" name="level">
                        <option value="ปวช." <?php echo ($student['level'] === 'ปวช.') ? 'selected' : ''; ?>>ปวช.</option>
                        <option value="ปวส." <?php echo ($student['level'] === 'ปวส.') ? 'selected' : ''; ?>>ปวส.</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="year" class="form-label">ชั้นปีที่</label>
                    <select class="form-select" id="year" name="year">
                        <option value="1" <?php echo ($student['year'] === '1') ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo ($student['year'] === '2') ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo ($student['year'] === '3') ? 'selected' : ''; ?>>3</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="department_id" class="form-label">สาขาวิชา</label>
                    <select class="form-select select2" id="department_id" name="department_id">
                        <option value="">-- เลือกสาขาวิชา --</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['department_id']; ?>" <?php echo ($student['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">วันที่บันทึกข้อมูล</label>
                    <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i:s', strtotime($student['created_at'])); ?>" readonly>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">อัปเดตล่าสุด</label>
                    <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i:s', strtotime($student['updated_at'])); ?>" readonly>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> บันทึกข้อมูล
                    </button>
                    <a href="view.php?id=<?php echo $student_id; ?>" class="btn btn-info">
                        <i class="bi bi-eye"></i> ดูข้อมูล
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- คำร้องของนักเรียนคนนี้ -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">คำร้องขอเปิดรายวิชาของนักเรียนคนนี้</h6>
    </div>
    <div class="card-body">
        <?php
        // ดึงคำร้องของนักเรียนคนนี้
        try {
            $stmt = $pdo->prepare("
                SELECT cr.*, COUNT(rd.detail_id) as course_count
                FROM course_requests cr
                LEFT JOIN request_details rd ON cr.request_id = rd.request_id
                WHERE cr.student_id = :student_id
                GROUP BY cr.request_id
                ORDER BY cr.request_date DESC
            ");
            $stmt->execute(['student_id' => $student_id]);
            $requests = $stmt->fetchAll();
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูลคำร้อง: ' . $e->getMessage() . '</div>';
            $requests = [];
        }
        ?>
        
        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>รหัสคำร้อง</th>
                            <th>วันที่ยื่น</th>
                            <th>ภาคเรียน/ปีการศึกษา</th>
                            <th>จำนวนรายวิชา</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['request_id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                <td>ภาคเรียนที่ <?php echo $request['semester'] . '/' . $request['academic_year']; ?></td>
                                <td class="text-center"><?php echo $request['course_count']; ?></td>
                                <td>
                                    <?php if ($request['status'] === 'อนุมัติ'): ?>
                                        <span class="badge bg-success">อนุมัติแล้ว</span>
                                    <?php elseif ($request['status'] === 'รอดำเนินการ'): ?>
                                        <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ไม่อนุมัติ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../requests/view.php?id=<?php echo $request['request_id']; ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye"></i> ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">ไม่พบข้อมูลคำร้องขอเปิดรายวิชาของนักเรียนคนนี้</div>
        <?php endif; ?>
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