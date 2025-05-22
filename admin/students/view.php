<?php
// admin/students/view.php
// หน้าแสดงรายละเอียดข้อมูลนักเรียน

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'ข้อมูลนักเรียน';
$currentPage = 'students';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // กรณีไม่มี id ให้แสดงข้อความผิดพลาดและกลับไปยังหน้ารายการ
    $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการดู';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$student_id = $_GET['id'];
$error = '';
$student = null;
$department = null;

// ดึงข้อมูลนักเรียนที่ต้องการดู
try {
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name 
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE s.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $student_id]);
    $student = $stmt->fetch();
    
    // ถ้าไม่พบข้อมูลนักเรียน
    if (!$student) {
        $_SESSION['message'] = 'ไม่พบข้อมูลนักเรียนที่ต้องการดู';
        $_SESSION['message_type'] = 'danger';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}

// ดึงข้อมูลคำร้องขอเปิดรายวิชาของนักเรียนคนนี้
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
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลคำร้อง: ' . $e->getMessage();
    $requests = [];
}
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">ข้อมูลนักเรียน</h1>
    <div>
        <a href="edit.php?id=<?php echo $student_id; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> แก้ไขข้อมูล
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> กลับไปยังรายการ
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Student Information Card -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">ข้อมูลนักเรียน</h6>
                <span class="badge bg-primary">รหัสนักเรียน: <?php echo htmlspecialchars($student['student_code']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">รหัสนักเรียน</h6>
                        <p><?php echo htmlspecialchars($student['student_code']); ?></p>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">ชื่อ-นามสกุล</h6>
                        <p><?php echo htmlspecialchars($student['prefix'] . $student['first_name'] . ' ' . $student['last_name']); ?></p>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">เบอร์โทรศัพท์</h6>
                        <p><?php echo htmlspecialchars($student['phone'] ?: 'ไม่ระบุ'); ?></p>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">ระดับชั้น</h6>
                        <p><?php echo htmlspecialchars($student['level'] . ' ปีที่ ' . $student['year']); ?></p>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">สาขาวิชา</h6>
                        <p><?php echo htmlspecialchars($student['department_name'] ?: 'ไม่ระบุ'); ?></p>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <h6 class="font-weight-bold">วันที่สร้างข้อมูล</h6>
                        <p><?php echo date('d/m/Y H:i:s', strtotime($student['created_at'])); ?></p>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="row mt-3">
                    <div class="col-12">
                        <a href="edit.php?id=<?php echo $student_id; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> แก้ไขข้อมูล
                        </a>
                        <a href="../reports/student_requests.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary">
                            <i class="bi bi-file-text"></i> รายงานคำร้อง
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> กลับไปยังรายการ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request History Card -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ประวัติการขอเปิดรายวิชา</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="requestsTable">
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
                                            <?php if ($request['status'] === 'อนุมัติ'): ?>
                                                <a href="../schedules/index.php?request_id=<?php echo $request['request_id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-calendar-week"></i> ตารางเรียน
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">ไม่พบประวัติการขอเปิดรายวิชาของนักเรียนคนนี้</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#requestsTable').DataTable({
            order: [[0, 'desc']]
        });
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>