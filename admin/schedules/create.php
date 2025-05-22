<?php
// admin/schedules/create.php
// หน้าสร้างตารางเรียนจากคำร้องที่อนุมัติแล้ว

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'สร้างตารางเรียน';
$currentPage = 'schedules-create';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการส่ง request_id มาหรือไม่
$request_id = $_GET['request_id'] ?? '';
$error_message = '';
$success_message = '';

if (empty($request_id)) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลคำร้องที่ต้องการสร้างตาราง';
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลคำร้องและรายละเอียด
try {
    // ตรวจสอบว่าคำร้องได้รับการอนุมัติแล้วหรือไม่
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               s.student_code, s.prefix, s.first_name, s.last_name, 
               s.level, s.year, s.phone,
               d.department_name
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE cr.request_id = :request_id AND cr.status = 'อนุมัติ'
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $_SESSION['error_message'] = 'ไม่พบคำร้องที่อนุมัติแล้วหรือคำร้องไม่ได้รับการอนุมัติ';
        header('Location: index.php');
        exit;
    }
    
    // ดึงรายละเอียดรายวิชา
    $stmt = $pdo->prepare("
        SELECT rd.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.teacher_id, t.prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
               (SELECT COUNT(*) FROM class_schedules cs WHERE cs.detail_id = rd.detail_id) as has_schedule
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        WHERE rd.request_id = :request_id
        ORDER BY c.course_code
    ");
    $stmt->execute(['request_id' => $request_id]);
    $course_details = $stmt->fetchAll();
    
    if (empty($course_details)) {
        $_SESSION['error_message'] = 'ไม่พบรายวิชาในคำร้องนี้';
        header('Location: index.php');
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $schedules_data = $_POST['schedules'] ?? [];
        
        if (empty($schedules_data)) {
            throw new Exception('กรุณากำหนดตารางเรียนอย่างน้อย 1 รายวิชา');
        }
        
        $created_count = 0;
        $updated_count = 0;
        
        foreach ($schedules_data as $detail_id => $schedule_info) {
            if (empty($schedule_info['day']) || empty($schedule_info['start_time']) || empty($schedule_info['end_time'])) {
                continue; // ข้ามรายวิชาที่ไม่ได้กำหนดตาราง
            }
            
            // ตรวจสอบว่ามีตารางอยู่แล้วหรือไม่
            $stmt = $pdo->prepare("SELECT class_schedule_id FROM class_schedules WHERE detail_id = :detail_id");
            $stmt->execute(['detail_id' => $detail_id]);
            $existing_schedule = $stmt->fetch();
            
            if ($existing_schedule) {
                // อัปเดตตารางที่มีอยู่
                $stmt = $pdo->prepare("
                    UPDATE class_schedules 
                    SET day_of_week = :day_of_week,
                        start_time = :start_time,
                        end_time = :end_time,
                        room = :room,
                        updated_at = NOW()
                    WHERE detail_id = :detail_id
                ");
                
                $stmt->execute([
                    'day_of_week' => $schedule_info['day'],
                    'start_time' => $schedule_info['start_time'],
                    'end_time' => $schedule_info['end_time'],
                    'room' => $schedule_info['room'] ?? '',
                    'detail_id' => $detail_id
                ]);
                
                $updated_count++;
            } else {
                // สร้างตารางใหม่
                $stmt = $pdo->prepare("
                    INSERT INTO class_schedules (detail_id, day_of_week, start_time, end_time, room, created_at, updated_at)
                    VALUES (:detail_id, :day_of_week, :start_time, :end_time, :room, NOW(), NOW())
                ");
                
                $stmt->execute([
                    'detail_id' => $detail_id,
                    'day_of_week' => $schedule_info['day'],
                    'start_time' => $schedule_info['start_time'],
                    'end_time' => $schedule_info['end_time'],
                    'room' => $schedule_info['room'] ?? ''
                ]);
                
                $created_count++;
            }
        }
        
        // บันทึกการติดตามสถานะ
        $stmt = $pdo->prepare("
            INSERT INTO status_tracking (request_id, status, comment, updated_by, created_at)
            VALUES (:request_id, :status, :comment, :updated_by, NOW())
        ");
        
        $comment = "จัดตารางเรียนเรียบร้อยแล้ว - สร้างใหม่ {$created_count} รายการ";
        if ($updated_count > 0) {
            $comment .= ", อัปเดต {$updated_count} รายการ";
        }
        
        $stmt->execute([
            'request_id' => $request_id,
            'status' => 'จัดตารางเรียนเรียบร้อยแล้ว',
            'comment' => $comment,
            'updated_by' => $_SESSION['admin_name']
        ]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = 'สร้างตารางเรียนเรียบร้อยแล้ว';
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// ดึงตารางเรียนที่มีอยู่แล้ว (ถ้ามี)
$existing_schedules = [];
try {
    $stmt = $pdo->prepare("
        SELECT cs.*, rd.detail_id
        FROM class_schedules cs
        JOIN request_details rd ON cs.detail_id = rd.detail_id
        WHERE rd.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    $existing_data = $stmt->fetchAll();
    
    foreach ($existing_data as $schedule) {
        $existing_schedules[$schedule['detail_id']] = $schedule;
    }
} catch (PDOException $e) {
    // ไม่ต้องแสดงข้อผิดพลาด
}

// ดึงข้อมูลเวลาว่างของครู
$teacher_availability = [];
try {
    $teacher_ids = array_column($course_details, 'teacher_id');
    if (!empty($teacher_ids)) {
        $placeholders = implode(',', array_fill(0, count($teacher_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT teacher_id, day_of_week, start_time, end_time, is_available
            FROM teacher_schedules 
            WHERE teacher_id IN ({$placeholders}) AND is_available = 1
            ORDER BY teacher_id, FIELD(day_of_week, 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'), start_time
        ");
        $stmt->execute($teacher_ids);
        $availability_data = $stmt->fetchAll();
        
        foreach ($availability_data as $avail) {
            if (!isset($teacher_availability[$avail['teacher_id']])) {
                $teacher_availability[$avail['teacher_id']] = [];
            }
            $teacher_availability[$avail['teacher_id']][] = $avail;
        }
    }
} catch (PDOException $e) {
    // ไม่ต้องแสดงข้อผิดพลาด
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการตารางเรียน</a></li>
        <li class="breadcrumb-item active" aria-current="page">สร้างตารางเรียน</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">สร้างตารางเรียน</h1>
    <a href="index.php" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> กลับ
    </a>
</div>

<!-- Alerts -->
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Request Information -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ข้อมูลคำร้อง #<?php echo $request_id; ?></h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>นักเรียน:</strong> <?php echo $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']; ?></p>
                <p><strong>รหัสนักเรียน:</strong> <?php echo $request['student_code']; ?></p>
                <p><strong>ระดับชั้น:</strong> <?php echo $request['level'] . ' ปีที่ ' . $request['year']; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>สาขาวิชา:</strong> <?php echo $request['department_name'] ?? 'ไม่ระบุ'; ?></p>
                <p><strong>ภาคเรียน:</strong> ภาคเรียนที่ <?php echo $request['semester'] . '/' . $request['academic_year']; ?></p>
                <p><strong>เบอร์โทรศัพท์:</strong> <?php echo $request['phone'] ?? 'ไม่ระบุ'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Form -->
<form method="post" id="scheduleForm">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">กำหนดตารางเรียน</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">รายวิชา</th>
                            <th width="15%">ครูผู้สอน</th>
                            <th width="15%">วัน</th>
                            <th width="15%">เวลาเริ่ม</th>
                            <th width="15%">เวลาสิ้นสุด</th>
                            <th width="15%">ห้องเรียน</th>
                            <th width="5%">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_details as $course): ?>
                            <?php 
                                $existing = $existing_schedules[$course['detail_id']] ?? null;
                                $teacher_avail = $teacher_availability[$course['teacher_id']] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $course['course_code']; ?></div>
                                    <div class="small text-muted"><?php echo $course['course_name']; ?></div>
                                    <div class="small">
                                        <span class="badge bg-info"><?php echo $course['theory_hours']; ?>ท</span>
                                        <span class="badge bg-success"><?php echo $course['practice_hours']; ?>ป</span>
                                        <span class="badge bg-primary"><?php echo $course['credit_hours']; ?>น</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $course['teacher_prefix'] . $course['teacher_first_name'] . ' ' . $course['teacher_last_name']; ?></div>
                                    <?php if (!empty($teacher_avail)): ?>
                                        <div class="small text-success">
                                            <i class="bi bi-check-circle"></i> มีเวลาว่าง <?php echo count($teacher_avail); ?> ช่วง
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-warning">
                                            <i class="bi bi-exclamation-triangle"></i> ไม่มีข้อมูลเวลาว่าง
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="schedules[<?php echo $course['detail_id']; ?>][day]" onchange="updateTimeOptions(this, <?php echo $course['teacher_id']; ?>)">
                                        <option value="">-- เลือกวัน --</option>
                                        <option value="จันทร์" <?php echo ($existing && $existing['day_of_week'] === 'จันทร์') ? 'selected' : ''; ?>>จันทร์</option>
                                        <option value="อังคาร" <?php echo ($existing && $existing['day_of_week'] === 'อังคาร') ? 'selected' : ''; ?>>อังคาร</option>
                                        <option value="พุธ" <?php echo ($existing && $existing['day_of_week'] === 'พุธ') ? 'selected' : ''; ?>>พุธ</option>
                                        <option value="พฤหัสบดี" <?php echo ($existing && $existing['day_of_week'] === 'พฤหัสบดี') ? 'selected' : ''; ?>>พฤหัสบดี</option>
                                        <option value="ศุกร์" <?php echo ($existing && $existing['day_of_week'] === 'ศุกร์') ? 'selected' : ''; ?>>ศุกร์</option>
                                        <option value="เสาร์" <?php echo ($existing && $existing['day_of_week'] === 'เสาร์') ? 'selected' : ''; ?>>เสาร์</option>
                                        <option value="อาทิตย์" <?php echo ($existing && $existing['day_of_week'] === 'อาทิตย์') ? 'selected' : ''; ?>>อาทิตย์</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" name="schedules[<?php echo $course['detail_id']; ?>][start_time]" 
                                           value="<?php echo $existing ? date('H:i', strtotime($existing['start_time'])) : ''; ?>">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" name="schedules[<?php echo $course['detail_id']; ?>][end_time]" 
                                           value="<?php echo $existing ? date('H:i', strtotime($existing['end_time'])) : ''; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="schedules[<?php echo $course['detail_id']; ?>][room]" 
                                           placeholder="ห้องเรียน" value="<?php echo $existing['room'] ?? ''; ?>">
                                </td>
                                <td class="text-center">
                                    <?php if ($course['has_schedule'] > 0): ?>
                                        <span class="badge bg-success">มีตารางแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">ยังไม่มีตาราง</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Teacher Availability Info -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">ข้อมูลเวลาว่างของครู</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($course_details as $course): ?>
                    <?php $teacher_avail = $teacher_availability[$course['teacher_id']] ?? []; ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo $course['teacher_prefix'] . $course['teacher_first_name'] . ' ' . $course['teacher_last_name']; ?></h6>
                                <small class="text-muted"><?php echo $course['course_code'] . ' - ' . $course['course_name']; ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($teacher_avail)): ?>
                                    <div class="row">
                                        <?php foreach ($teacher_avail as $avail): ?>
                                            <div class="col-6 mb-2">
                                                <div class="small">
                                                    <strong><?php echo $avail['day_of_week']; ?></strong><br>
                                                    <?php echo date('H:i', strtotime($avail['start_time'])); ?> - 
                                                    <?php echo date('H:i', strtotime($avail['end_time'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="bi bi-exclamation-triangle"></i> ไม่มีข้อมูลเวลาว่าง<br>
                                        <a href="teacher_availability.php?teacher_id=<?php echo $course['teacher_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            กำหนดเวลาว่าง
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> บันทึกตารางเรียน
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    // ข้อมูลเวลาว่างของครู
    const teacherAvailability = <?php echo json_encode($teacher_availability); ?>;
    
    $(document).ready(function() {
        // Form validation
        $('#scheduleForm').on('submit', function(e) {
            e.preventDefault();
            
            // ตรวจสอบว่ามีการกำหนดตารางอย่างน้อย 1 รายวิชา
            let hasSchedule = false;
            $('select[name*="[day]"]').each(function() {
                if ($(this).val() !== '') {
                    hasSchedule = true;
                    return false;
                }
            });
            
            if (!hasSchedule) {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'กรุณากำหนดตารางเรียนอย่างน้อย 1 รายวิชา',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
            
            // ตรวจสอบความถูกต้องของเวลา
            let isValid = true;
            let errorMessage = '';
            
            $('select[name*="[day]"]').each(function() {
                if ($(this).val() !== '') {
                    const row = $(this).closest('tr');
                    const startTime = row.find('input[name*="[start_time]"]').val();
                    const endTime = row.find('input[name*="[end_time]"]').val();
                    
                    if (!startTime || !endTime) {
                        isValid = false;
                        errorMessage = 'กรุณากำหนดเวลาเริ่มและเวลาสิ้นสุดให้ครบถ้วน';
                        return false;
                    }
                    
                    if (startTime >= endTime) {
                        isValid = false;
                        errorMessage = 'เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด';
                        return false;
                    }
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
            
            // แสดงการยืนยัน
            Swal.fire({
                title: 'ยืนยันการบันทึก?',
                text: 'คุณต้องการบันทึกตารางเรียนนี้ใช่หรือไม่?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, บันทึกเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง loading
                    Swal.fire({
                        title: 'กำลังบันทึกตารางเรียน',
                        text: 'กรุณารอสักครู่...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // ส่งฟอร์ม
                    this.submit();
                }
            });
        });
    });
    
    // Function to update time options based on teacher availability
    function updateTimeOptions(daySelect, teacherId) {
        const selectedDay = daySelect.value;
        const row = daySelect.closest('tr');
        const startTimeInput = row.querySelector('input[name*="[start_time]"]');
        const endTimeInput = row.querySelector('input[name*="[end_time]"]');
        
        if (selectedDay && teacherAvailability[teacherId]) {
            const availability = teacherAvailability[teacherId].find(avail => avail.day_of_week === selectedDay);
            
            if (availability) {
                startTimeInput.value = availability.start_time.substring(0, 5);
                endTimeInput.value = availability.end_time.substring(0, 5);
                
                // แสดงข้อความแจ้งเตือน
                $(row).find('td').eq(1).append('<div class="small text-success mt-1"><i class="bi bi-info-circle"></i> ใช้เวลาว่างของครู</div>');
                
                setTimeout(() => {
                    $(row).find('.small.text-success').fadeOut();
                }, 3000);
            }
        }
    }
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>