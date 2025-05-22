<?php
// admin/schedules/teacher_availability.php
// หน้าจัดการตารางว่างของครู (ย้ายมาจาก teachers/)

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการตารางว่างของครู';
$currentPage = 'schedules-teacher';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ดึงข้อมูลครูทั้งหมด
try {
    $stmt = $pdo->query("
        SELECT t.*, d.department_name 
        FROM teachers t
        LEFT JOIN departments d ON t.department_id = d.department_id
        ORDER BY t.first_name ASC
    ");
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
}

// ตรวจสอบว่ามีการเลือกครู
$selected_teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : null;
$selected_teacher = null;
$teacher_schedules = [];

if ($selected_teacher_id) {
    try {
        // ดึงข้อมูลครู
        $stmt = $pdo->prepare("
            SELECT t.*, d.department_name 
            FROM teachers t
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE t.teacher_id = ?
        ");
        $stmt->execute([$selected_teacher_id]);
        $selected_teacher = $stmt->fetch();
        
        // ดึงตารางว่างของครู
        $stmt = $pdo->prepare("
            SELECT * FROM teacher_schedules 
            WHERE teacher_id = ?
            ORDER BY FIELD(day_of_week, 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'), start_time
        ");
        $stmt->execute([$selected_teacher_id]);
        $teacher_schedules = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
    }
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    $teacher_id = $_POST['teacher_id'] ?? null;
    $day = $_POST['day'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($teacher_id) || empty($day) || empty($start_time) || empty($end_time)) {
        $error_msg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        try {
            // ตรวจสอบว่ามีช่วงเวลาซ้อนทับหรือไม่
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM teacher_schedules 
                WHERE teacher_id = ? AND day_of_week = ? 
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))
            ");
            $stmt->execute([$teacher_id, $day, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
            $overlap_count = $stmt->fetch()['count'];
            
            if ($overlap_count > 0) {
                $error_msg = 'มีช่วงเวลาซ้อนทับกับตารางที่มีอยู่แล้ว';
            } else {
                // เพิ่มตารางว่างของครู
                $stmt = $pdo->prepare("
                    INSERT INTO teacher_schedules (teacher_id, day_of_week, start_time, end_time, is_available)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([$teacher_id, $day, $start_time, $end_time, $is_available]);
                
                $success_msg = 'บันทึกข้อมูลตารางว่างของครูเรียบร้อยแล้ว';
                
                // Redirect เพื่อรีเฟรชข้อมูล
                header("Location: teacher_availability.php?teacher_id=$teacher_id&success=1");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
        }
    }
}

// ตรวจสอบการแก้ไขตารางว่าง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $is_available = isset($_POST['is_available_' . $schedule_id]) ? 1 : 0;
    
    if (empty($schedule_id)) {
        $error_msg = 'ไม่พบข้อมูลตารางว่าง';
    } else {
        try {
            // อัปเดตสถานะว่าง
            $stmt = $pdo->prepare("
                UPDATE teacher_schedules 
                SET is_available = ?
                WHERE schedule_id = ?
            ");
            
            $stmt->execute([$is_available, $schedule_id]);
            
            $success_msg = 'อัปเดตสถานะตารางว่างเรียบร้อยแล้ว';
            
            // Redirect เพื่อรีเฟรชข้อมูล
            header("Location: teacher_availability.php?teacher_id=$selected_teacher_id&success=1");
            exit;
        } catch (PDOException $e) {
            $error_msg = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage();
        }
    }
}

// ตรวจสอบการลบตารางว่าง
if (isset($_GET['delete_schedule']) && !empty($_GET['delete_schedule'])) {
    $schedule_id = $_GET['delete_schedule'];
    
    try {
        // ลบตารางว่าง
        $stmt = $pdo->prepare("DELETE FROM teacher_schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        
        $success_msg = 'ลบข้อมูลตารางว่างเรียบร้อยแล้ว';
        
        // Redirect เพื่อรีเฟรชข้อมูล
        header("Location: teacher_availability.php?teacher_id=$selected_teacher_id&success=1");
        exit;
    } catch (PDOException $e) {
        $error_msg = 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage();
    }
}

// รับข้อความแจ้งเตือนจาก URL
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_msg = 'ดำเนินการเรียบร้อยแล้ว';
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการตารางเรียน</a></li>
        <li class="breadcrumb-item active" aria-current="page">จัดการตารางว่างของครู</li>
    </ol>
</nav>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">จัดการตารางว่างของครู</h1>
    <a href="index.php" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> กลับ
    </a>
</div>

<!-- Alert messages -->
<?php if (isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Select teacher card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">เลือกครูที่ต้องการจัดการตารางว่าง</h6>
    </div>
    <div class="card-body">
        <form method="get" action="teacher_availability.php" id="selectTeacherForm">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">เลือกครู</label>
                        <select class="form-select select2" id="teacher_id" name="teacher_id" required onchange="this.form.submit()">
                            <option value="">-- เลือกครู --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo ($selected_teacher_id == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                    <?php echo $teacher['prefix'] . $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['department_name'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> แสดงข้อมูล
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_teacher): ?>
    <!-- Teacher schedule card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">ตารางว่างของ <?php echo $selected_teacher['prefix'] . $selected_teacher['first_name'] . ' ' . $selected_teacher['last_name']; ?></h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="bi bi-plus-circle"></i> เพิ่มตารางว่าง
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($teacher_schedules)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> ยังไม่มีข้อมูลตารางว่าง กรุณาเพิ่มตารางว่างโดยคลิกที่ปุ่ม "เพิ่มตารางว่าง"
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="scheduleTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">ลำดับ</th>
                                <th width="15%">วัน</th>
                                <th width="20%">เวลาเริ่ม</th>
                                <th width="20%">เวลาสิ้นสุด</th>
                                <th width="15%">สถานะ</th>
                                <th width="25%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teacher_schedules as $index => $schedule): ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td>วัน<?php echo $schedule['day_of_week']; ?></td>
                                    <td><?php echo date('H:i', strtotime($schedule['start_time'])); ?> น.</td>
                                    <td><?php echo date('H:i', strtotime($schedule['end_time'])); ?> น.</td>
                                    <td>
                                        <form method="post" action="teacher_availability.php" class="status-form">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                            <input type="hidden" name="update_schedule" value="1">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" id="is_available_<?php echo $schedule['schedule_id']; ?>" name="is_available_<?php echo $schedule['schedule_id']; ?>" <?php echo $schedule['is_available'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_available_<?php echo $schedule['schedule_id']; ?>">
                                                    <?php echo $schedule['is_available'] ? 'ว่าง' : 'ไม่ว่าง'; ?>
                                                </label>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm edit-schedule" 
                                                data-schedule-id="<?php echo $schedule['schedule_id']; ?>" 
                                                data-day="<?php echo $schedule['day_of_week']; ?>" 
                                                data-start="<?php echo $schedule['start_time']; ?>" 
                                                data-end="<?php echo $schedule['end_time']; ?>" 
                                                data-available="<?php echo $schedule['is_available']; ?>">
                                            <i class="bi bi-pencil"></i> แก้ไข
                                        </button>
                                        <a href="teacher_availability.php?teacher_id=<?php echo $selected_teacher_id; ?>&delete_schedule=<?php echo $schedule['schedule_id']; ?>" 
                                           class="btn btn-danger btn-sm btn-delete" 
                                           data-item-name="ตารางว่างวัน<?php echo $schedule['day_of_week']; ?> เวลา <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['end_time'])); ?> น.">
                                            <i class="bi bi-trash"></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Weekly schedule visualization -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ตารางสอนประจำสัปดาห์</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-schedule">
                    <thead>
                        <tr>
                            <th width="15%">เวลา</th>
                            <th width="14%">วันจันทร์</th>
                            <th width="14%">วันอังคาร</th>
                            <th width="14%">วันพุธ</th>
                            <th width="14%">วันพฤหัสบดี</th>
                            <th width="14%">วันศุกร์</th>
                            <th width="15%">วันเสาร์-อาทิตย์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // สร้างตารางเวลา
                        $time_slots = [
                            '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00',
                            '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00',
                            '16:00-17:00', '17:00-18:00'
                        ];
                        
                        $days = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์,อาทิตย์'];
                        
                        // จัดกลุ่มตารางตามวัน
                        $schedules_by_day = [];
                        foreach ($teacher_schedules as $schedule) {
                            $day = $schedule['day_of_week'];
                            if (!isset($schedules_by_day[$day])) {
                                $schedules_by_day[$day] = [];
                            }
                            $schedules_by_day[$day][] = $schedule;
                        }
                        
                        // แสดงตาราง
                        foreach ($time_slots as $time_slot):
                            list($start_time, $end_time) = explode('-', $time_slot);
                            $start_time_formatted = $start_time . ':00';
                            $end_time_formatted = $end_time . ':00';
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?php echo $time_slot; ?></td>
                            <?php foreach ($days as $day): 
                                $saturday_sunday = strpos($day, ',') !== false;
                                $day_classes = $saturday_sunday ? ['เสาร์', 'อาทิตย์'] : [$day];
                                
                                $is_available = false;
                                $schedule_info = '';
                                
                                foreach ($day_classes as $day_class) {
                                    if (isset($schedules_by_day[$day_class])) {
                                        foreach ($schedules_by_day[$day_class] as $schedule) {
                                            $schedule_start = date('H:i', strtotime($schedule['start_time']));
                                            $schedule_end = date('H:i', strtotime($schedule['end_time']));
                                            
                                            // ตรวจสอบว่าเวลาในตารางตรงกับช่วงเวลาว่างหรือไม่
                                            if (($schedule_start <= $start_time && $schedule_end > $start_time) ||
                                                ($schedule_start < $end_time && $schedule_end >= $end_time) ||
                                                ($schedule_start >= $start_time && $schedule_end <= $end_time)) {
                                                
                                                $is_available = $schedule['is_available'];
                                                $schedule_info = $is_available ? 'ว่าง' : 'ไม่ว่าง';
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            ?>
                            <td class="text-center <?php echo $is_available ? 'bg-success text-white' : ($schedule_info ? 'bg-danger text-white' : ''); ?>">
                                <?php echo $schedule_info; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <div class="d-flex justify-content-center">
                    <div class="me-4">
                        <span class="badge bg-success">&nbsp;</span> ว่าง
                    </div>
                    <div>
                        <span class="badge bg-danger">&nbsp;</span> ไม่ว่าง
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มตารางว่าง -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">เพิ่มตารางว่าง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="teacher_availability.php" id="addScheduleForm">
                        <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                        <input type="hidden" name="save_schedule" value="1">
                        
                        <div class="mb-3">
                            <label for="day" class="form-label">วัน <span class="text-danger">*</span></label>
                            <select class="form-select" id="day" name="day" required>
                                <option value="">-- เลือกวัน --</option>
                                <option value="จันทร์">วันจันทร์</option>
                                <option value="อังคาร">วันอังคาร</option>
                                <option value="พุธ">วันพุธ</option>
                                <option value="พฤหัสบดี">วันพฤหัสบดี</option>
                                <option value="ศุกร์">วันศุกร์</option>
                                <option value="เสาร์">วันเสาร์</option>
                                <option value="อาทิตย์">วันอาทิตย์</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">เวลาเริ่ม <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_available" name="is_available" checked>
                            <label class="form-check-label" for="is_available">สถานะว่าง</label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal แก้ไขตารางว่าง -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">แก้ไขตารางว่าง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="teacher_availability.php" id="editScheduleForm">
                        <input type="hidden" name="teacher_id" value="<?php echo $selected_teacher_id; ?>">
                        <input type="hidden" name="schedule_id" id="edit_schedule_id">
                        <input type="hidden" name="save_schedule" value="1">
                        
                        <div class="mb-3">
                            <label for="edit_day" class="form-label">วัน <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_day" name="day" required>
                                <option value="">-- เลือกวัน --</option>
                                <option value="จันทร์">วันจันทร์</option>
                                <option value="อังคาร">วันอังคาร</option>
                                <option value="พุธ">วันพุธ</option>
                                <option value="พฤหัสบดี">วันพฤหัสบดี</option>
                                <option value="ศุกร์">วันศุกร์</option>
                                <option value="เสาร์">วันเสาร์</option>
                                <option value="อาทิตย์">วันอาทิตย์</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_time" class="form-label">เวลาเริ่ม <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_time" class="form-label">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_available" name="is_available">
                            <label class="form-check-label" for="edit_is_available">สถานะว่าง</label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#scheduleTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[1, 'asc'], [2, 'asc']]
        });
        
        // Form validation - เพิ่มตารางว่าง
        const addScheduleForm = document.getElementById('addScheduleForm');
        
        addScheduleForm.addEventListener('submit', function(event) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime >= endTime) {
                event.preventDefault();
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        });
        
        // Form validation - แก้ไขตารางว่าง
        const editScheduleForm = document.getElementById('editScheduleForm');
        
        editScheduleForm.addEventListener('submit', function(event) {
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;
            
            if (startTime >= endTime) {
                event.preventDefault();
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        });
        
        // Toggle status
        const statusToggles = document.querySelectorAll('.status-toggle');
        
        statusToggles.forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                const form = this.closest('form');
                const label = form.querySelector('label');
                
                label.textContent = this.checked ? 'ว่าง' : 'ไม่ว่าง';
                
                Swal.fire({
                    title: 'กำลังอัปเดตสถานะ',
                    text: 'กรุณารอสักครู่...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                form.submit();
            });
        });
        
        // แก้ไขตารางว่าง
        const editButtons = document.querySelectorAll('.edit-schedule');
        
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const scheduleId = this.dataset.scheduleId;
                const day = this.dataset.day;
                const startTime = this.dataset.start.substring(0, 5);
                const endTime = this.dataset.end.substring(0, 5);
                const isAvailable = parseInt(this.dataset.available);
                
                document.getElementById('edit_schedule_id').value = scheduleId;
                document.getElementById('edit_day').value = day;
                document.getElementById('edit_start_time').value = startTime;
                document.getElementById('edit_end_time').value = endTime;
                document.getElementById('edit_is_available').checked = isAvailable === 1;
                
                const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                editModal.show();
            });
        });
    });
    </script>
<?php endif; ?>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>