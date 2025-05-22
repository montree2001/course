<?php
// admin/schedules/index.php
// หน้าแสดงรายการตารางเรียนทั้งหมด

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการตารางเรียน';
$currentPage = 'schedules-list';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการกรองข้อมูลหรือไม่
$request_id_filter = $_GET['request_id'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$academic_year_filter = $_GET['academic_year'] ?? '';

// สร้าง WHERE clause สำหรับการกรอง
$where_conditions = [];
$params = [];

if (!empty($request_id_filter)) {
    $where_conditions[] = "cr.request_id = :request_id";
    $params['request_id'] = $request_id_filter;
}

if (!empty($semester_filter)) {
    $where_conditions[] = "cr.semester = :semester";
    $params['semester'] = $semester_filter;
}

if (!empty($academic_year_filter)) {
    $where_conditions[] = "cr.academic_year = :academic_year";
    $params['academic_year'] = $academic_year_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'AND ' . implode(' AND ', $where_conditions);
}

// ดึงข้อมูลตารางเรียนทั้งหมด
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            cr.request_id, 
            cr.semester, 
            cr.academic_year, 
            cr.status,
            s.student_code, 
            s.prefix, 
            s.first_name, 
            s.last_name,
            d.department_name,
            COUNT(DISTINCT rd.detail_id) as total_courses,
            COUNT(DISTINCT cs.class_schedule_id) as scheduled_courses,
            MAX(cr.updated_at) as last_updated
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN request_details rd ON cr.request_id = rd.request_id
        LEFT JOIN class_schedules cs ON rd.detail_id = cs.detail_id
        WHERE cr.status = 'อนุมัติ' {$where_clause}
        GROUP BY cr.request_id, cr.semester, cr.academic_year, s.student_code, s.prefix, s.first_name, s.last_name, d.department_name
        ORDER BY cr.academic_year DESC, cr.semester DESC, cr.request_id DESC
    ");
    
    $stmt->execute($params);
    $schedules = $stmt->fetchAll();
    
    // ดึงข้อมูลสถิติ
    $total_approved = count($schedules);
    $total_scheduled = 0;
    $total_pending = 0;
    
    foreach ($schedules as $schedule) {
        if ($schedule['scheduled_courses'] > 0) {
            $total_scheduled++;
        } else {
            $total_pending++;
        }
    }
    
} catch (PDOException $e) {
    $error_message = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    $schedules = [];
    $total_approved = $total_scheduled = $total_pending = 0;
}

// ดึงข้อมูลสำหรับ dropdown filter
try {
    // ปีการศึกษา
    $stmt = $pdo->query("SELECT DISTINCT academic_year FROM course_requests WHERE status = 'อนุมัติ' ORDER BY academic_year DESC");
    $academic_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $academic_years = [];
}

// ตรวจสอบข้อความแจ้งเตือน
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item active" aria-current="page">จัดการตารางเรียน</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">จัดการตารางเรียน</h1>
    <div>
        <a href="create.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> สร้างตารางเรียนใหม่
        </a>
        <a href="teacher_availability.php" class="btn btn-success btn-sm">
            <i class="bi bi-clock"></i> จัดการเวลาว่างครู
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">คำร้องที่อนุมัติแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_approved; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">จัดตารางแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_scheduled; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-check" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">รอจัดตาราง</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pending; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass-split" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">กรองข้อมูล</h6>
    </div>
    <div class="card-body">
        <form method="get" action="index.php" class="row g-3">
            <div class="col-md-3">
                <label for="request_id" class="form-label">รหัสคำร้อง</label>
                <input type="number" class="form-control" id="request_id" name="request_id" value="<?php echo htmlspecialchars($request_id_filter); ?>" placeholder="กรอกรหัสคำร้อง">
            </div>
            
            <div class="col-md-3">
                <label for="semester" class="form-label">ภาคเรียน</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">ทั้งหมด</option>
                    <option value="1" <?php echo $semester_filter === '1' ? 'selected' : ''; ?>>ภาคเรียนที่ 1</option>
                    <option value="2" <?php echo $semester_filter === '2' ? 'selected' : ''; ?>>ภาคเรียนที่ 2</option>
                    <option value="3" <?php echo $semester_filter === '3' ? 'selected' : ''; ?>>ภาคเรียนพิเศษ</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="academic_year" class="form-label">ปีการศึกษา</label>
                <select class="form-select" id="academic_year" name="academic_year">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($academic_years as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $academic_year_filter === $year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> ล้าง
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Schedules Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">รายการตารางเรียน</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="schedulesTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>รหัสคำร้อง</th>
                        <th>ข้อมูลนักเรียน</th>
                        <th>ภาคเรียน/ปีการศึกษา</th>
                        <th>จำนวนรายวิชา</th>
                        <th>สถานะตาราง</th>
                        <th>อัปเดตล่าสุด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $schedule['request_id']; ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $schedule['prefix'] . $schedule['first_name'] . ' ' . $schedule['last_name']; ?></strong>
                                    </div>
                                    <small class="text-muted">รหัส: <?php echo $schedule['student_code']; ?></small>
                                    <?php if ($schedule['department_name']): ?>
                                        <br><small class="text-muted"><?php echo $schedule['department_name']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    ภาคเรียนที่ <?php echo $schedule['semester'] . '/' . $schedule['academic_year']; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $schedule['total_courses']; ?> วิชา</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($schedule['scheduled_courses'] > 0): ?>
                                        <?php if ($schedule['scheduled_courses'] == $schedule['total_courses']): ?>
                                            <span class="badge bg-success">จัดตารางครบแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">จัดตารางบางส่วน</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ยังไม่จัดตาราง</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($schedule['last_updated'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="../requests/view.php?id=<?php echo $schedule['request_id']; ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="ดูรายละเอียดคำร้อง">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($schedule['scheduled_courses'] == 0): ?>
                                            <a href="create.php?request_id=<?php echo $schedule['request_id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="สร้างตารางเรียน">
                                                <i class="bi bi-calendar-plus"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="edit.php?request_id=<?php echo $schedule['request_id']; ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="แก้ไขตารางเรียน">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($schedule['scheduled_courses'] > 0): ?>
                                            <a href="print.php?request_id=<?php echo $schedule['request_id']; ?>" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="พิมพ์ตารางเรียน" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="ลบตารางเรียน" onclick="deleteSchedule(<?php echo $schedule['request_id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <?php if (!empty($request_id_filter) || !empty($semester_filter) || !empty($academic_year_filter)): ?>
                                    ไม่พบข้อมูลตารางเรียนตามเงื่อนไขที่กำหนด
                                <?php else: ?>
                                    ยังไม่มีคำร้องที่อนุมัติแล้วสำหรับจัดตารางเรียน
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#schedulesTable').DataTable({
            order: [[0, 'desc']], // เรียงตามรหัสคำร้อง
            columnDefs: [
                { orderable: false, targets: 6 } // คอลัมน์การจัดการไม่ต้องเรียงลำดับ
            ],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
                infoEmpty: 'แสดง 0 ถึง 0 จาก 0 รายการ',
                infoFiltered: '(กรองจากทั้งหมด _MAX_ รายการ)',
                zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
                paginate: {
                    first: 'แรก',
                    previous: 'ก่อนหน้า',
                    next: 'ถัดไป',
                    last: 'สุดท้าย'
                }
            }
        });
        
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    
    // Function to delete schedule
    function deleteSchedule(requestId) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบตารางเรียนของคำร้อง #' + requestId + ' ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'กำลังลบตารางเรียน',
                    text: 'กรุณารอสักครู่...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit delete request
                window.location.href = 'delete.php?request_id=' + requestId;
            }
        });
    }
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>