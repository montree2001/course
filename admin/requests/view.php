<?php
// admin/requests/view.php
// หน้าดูรายละเอียดคำร้องขอเปิดรายวิชา

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'รายละเอียดคำร้องขอเปิดรายวิชา';
$currentPage = 'requests';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ไม่พบคำร้องที่ต้องการดู';
    header('Location: index.php');
    exit;
}

$request_id = $_GET['id'];
$error = '';
$request = null;
$request_details = [];
$tracking_history = [];

// ตรวจสอบว่ามีการส่งข้อมูลอัปเดตสถานะมาหรือไม่ (สำหรับกรณี fallback)
if (isset($_GET['new_status']) && !empty($_GET['new_status'])) {
    $new_status = $_GET['new_status'];
    $comment = $_GET['comment'] ?? '';
    $rejection_reason = $_GET['rejection_reason'] ?? '';
    
    // Redirect ไป update_status.php
    $params = http_build_query([
        'request_id' => $request_id,
        'new_status' => $new_status,
        'comment' => $comment,
        'rejection_reason' => $rejection_reason
    ]);
    
    header('Location: update_status.php?' . $params);
    exit;
}

try {
    // ดึงข้อมูลคำร้อง
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               s.student_code, s.prefix, s.first_name, s.last_name, 
               s.level, s.year, s.phone,
               d.department_name
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE cr.request_id = :request_id
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $_SESSION['error_message'] = 'ไม่พบคำร้องที่ต้องการดู';
        header('Location: index.php');
        exit;
    }
    
    // ดึงรายละเอียดรายวิชาที่ขอเปิด
    $stmt = $pdo->prepare("
        SELECT rd.*, 
               c.course_code, c.course_name, c.theory_hours, c.practice_hours, c.credit_hours,
               t.prefix as teacher_prefix, t.first_name as teacher_first_name, t.last_name as teacher_last_name
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        JOIN teachers t ON rd.teacher_id = t.teacher_id
        WHERE rd.request_id = :request_id
        ORDER BY c.course_code
    ");
    $stmt->execute(['request_id' => $request_id]);
    $request_details = $stmt->fetchAll();
    
    // ดึงประวัติการติดตามสถานะ
    $stmt = $pdo->prepare("
        SELECT * FROM status_tracking 
        WHERE request_id = :request_id 
        ORDER BY created_at ASC
    ");
    $stmt->execute(['request_id' => $request_id]);
    $tracking_history = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการคำร้องขอเปิดรายวิชา</a></li>
        <li class="breadcrumb-item active" aria-current="page">รายละเอียดคำร้อง #<?php echo $request_id; ?></li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">รายละเอียดคำร้อง #<?php echo $request_id; ?></h1>
    <div>
        <?php if ($request && $request['status'] === 'รอดำเนินการ'): ?>
            <button type="button" class="btn btn-warning btn-sm" onclick="updateStatus(<?php echo $request_id; ?>)">
                <i class="bi bi-pencil-square"></i> อัปเดตสถานะ
            </button>
        <?php endif; ?>
        <a href="print.php?id=<?php echo $request_id; ?>" class="btn btn-primary btn-sm" target="_blank">
            <i class="bi bi-printer"></i> พิมพ์บันทึก
        </a>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($request): ?>
    <div class="row">
        <!-- ข้อมูลคำร้อง -->
        <div class="col-lg-8">
            <!-- Request Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">ข้อมูลคำร้อง</h6>
                    <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                        <span class="badge bg-warning text-dark fs-6">รอดำเนินการ</span>
                    <?php elseif ($request['status'] === 'อนุมัติ'): ?>
                        <span class="badge bg-success fs-6">อนุมัติแล้ว</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6">ไม่อนุมัติ</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">รหัสคำร้อง</h6>
                            <p class="text-gray-800"><?php echo $request['request_id']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">วันที่ยื่นคำร้อง</h6>
                            <p class="text-gray-800"><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">ภาคเรียน/ปีการศึกษา</h6>
                            <p class="text-gray-800">ภาคเรียนที่ <?php echo $request['semester'] . '/' . $request['academic_year']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">สถานะปัจจุบัน</h6>
                            <p class="text-gray-800">
                                <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                                    <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                <?php elseif ($request['status'] === 'อนุมัติ'): ?>
                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">ไม่อนุมัติ</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($request['status'] === 'ไม่อนุมัติ' && $request['rejection_reason']): ?>
                            <div class="col-12 mb-3">
                                <h6 class="font-weight-bold text-danger">เหตุผลที่ไม่อนุมัติ</h6>
                                <div class="alert alert-danger">
                                    <?php echo nl2br(htmlspecialchars($request['rejection_reason'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ข้อมูลนักเรียน</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">รหัสนักเรียน</h6>
                            <p class="text-gray-800"><?php echo $request['student_code']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">ชื่อ-นามสกุล</h6>
                            <p class="text-gray-800"><?php echo $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">ระดับชั้น</h6>
                            <p class="text-gray-800"><?php echo $request['level'] . ' ปีที่ ' . $request['year']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">สาขาวิชา</h6>
                            <p class="text-gray-800"><?php echo $request['department_name'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                        <?php if ($request['phone']): ?>
                            <div class="col-md-6 mb-3">
                                <h6 class="font-weight-bold">เบอร์โทรศัพท์</h6>
                                <p class="text-gray-800">
                                    <a href="tel:<?php echo $request['phone']; ?>"><?php echo $request['phone']; ?></a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Course Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">รายวิชาที่ขอเปิด</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">ลำดับ</th>
                                    <th width="15%">รหัสวิชา</th>
                                    <th width="35%">ชื่อรายวิชา</th>
                                    <th width="15%">ทฤษฎี/ปฏิบัติ/หน่วยกิต</th>
                                    <th width="25%">ครูประจำวิชา</th>
                                    <th width="5%">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($request_details as $index => $detail): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        <td><?php echo $detail['course_code']; ?></td>
                                        <td><?php echo $detail['course_name']; ?></td>
                                        <td class="text-center"><?php echo $detail['theory_hours'] . '/' . $detail['practice_hours'] . '/' . $detail['credit_hours']; ?></td>
                                        <td><?php echo $detail['teacher_prefix'] . $detail['teacher_first_name'] . ' ' . $detail['teacher_last_name']; ?></td>
                                        <td class="text-center">
                                            <?php if ($detail['teacher_approval'] === null): ?>
                                                <span class="badge bg-secondary">รอครูอนุมัติ</span>
                                            <?php elseif ($detail['teacher_approval'] == 1): ?>
                                                <span class="badge bg-success">ครูอนุมัติ</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ครูไม่อนุมัติ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <strong>รวม: <?php echo count($request_details); ?> รายวิชา</strong>
                        <strong class="ms-3">รวมหน่วยกิต: <?php echo array_sum(array_column($request_details, 'credit_hours')); ?> หน่วยกิต</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Tracking -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ติดตามสถานะ</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($tracking_history)): ?>
                        <div class="timeline">
                            <?php foreach ($tracking_history as $track): ?>
                                <div class="timeline-item <?php echo ($track === end($tracking_history)) ? 'active' : ''; ?>">
                                    <div class="timeline-content">
                                        <h6 class="timeline-title"><?php echo $track['status']; ?></h6>
                                        <?php if ($track['comment']): ?>
                                            <p class="timeline-description"><?php echo nl2br(htmlspecialchars($track['comment'])); ?></p>
                                        <?php endif; ?>
                                        <small class="timeline-date text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($track['created_at'])); ?>
                                            <?php if ($track['updated_by']): ?>
                                                <br>โดย: <?php echo $track['updated_by']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">ยังไม่มีประวัติการติดตามสถานะ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">การดำเนินการ</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                            <button type="button" class="btn btn-warning" onclick="updateStatus(<?php echo $request_id; ?>)">
                                <i class="bi bi-pencil-square"></i> อัปเดตสถานะ
                            </button>
                        <?php endif; ?>
                        
                        <a href="print.php?id=<?php echo $request_id; ?>" class="btn btn-primary" target="_blank">
                            <i class="bi bi-printer"></i> พิมพ์บันทึกราชการ
                        </a>
                        
                        <?php if ($request['status'] === 'อนุมัติ'): ?>
                            <a href="../schedules/index.php?request_id=<?php echo $request_id; ?>" class="btn btn-success">
                                <i class="bi bi-calendar-week"></i> จัดตารางเรียน
                            </a>
                        <?php endif; ?>
                        
                        <a href="../students/view.php?id=<?php echo $request['student_id']; ?>" class="btn btn-info">
                            <i class="bi bi-person"></i> ดูข้อมูลนักเรียน
                        </a>
                        
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> กลับไปรายการ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">อัปเดตสถานะคำร้อง #<?php echo $request_id; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStatusForm" method="post" action="update_status.php">
                <div class="modal-body">
                    <input type="hidden" id="request_id" name="request_id" value="<?php echo $request_id; ?>">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">สถานะใหม่ <span class="text-danger">*</span></label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="">-- เลือกสถานะ --</option>
                            <option value="อนุมัติ">อนุมัติ</option>
                            <option value="ไม่อนุมัติ">ไม่อนุมัติ</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="กรอกหมายเหตุ (ถ้ามี)"></textarea>
                    </div>
                    
                    <div class="mb-3" id="rejection_reason_group" style="display: none;">
                        <label for="rejection_reason" class="form-label">เหตุผลที่ไม่อนุมัติ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="กรอกเหตุผลที่ไม่อนุมัติ"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="submitStatusUpdate">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 0;
        list-style: none;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1.5rem;
    }
    
    .timeline-item:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #6c757d;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #6c757d;
    }
    
    .timeline-item.active:before {
        background-color: #28a745;
        box-shadow: 0 0 0 2px #28a745;
    }
    
    .timeline-item:after {
        content: '';
        position: absolute;
        left: 5px;
        top: 16px;
        width: 2px;
        height: calc(100% + 8px);
        background-color: #dee2e6;
    }
    
    .timeline-item:last-child:after {
        display: none;
    }
    
    .timeline-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #495057;
    }
    
    .timeline-description {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .timeline-date {
        font-size: 0.75rem;
        color: #adb5bd;
    }
</style>

<script>
    $(document).ready(function() {
        // Update status modal
        $('#new_status').on('change', function() {
            if ($(this).val() === 'ไม่อนุมัติ') {
                $('#rejection_reason_group').show();
                $('#rejection_reason').prop('required', true);
            } else {
                $('#rejection_reason_group').hide();
                $('#rejection_reason').prop('required', false);
                $('#rejection_reason').val('');
            }
        });
        
        // Handle update status form submission
        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            // ตรวจสอบข้อมูล
            const newStatus = $('#new_status').val();
            const rejectionReason = $('#rejection_reason').val();
            
            if (!newStatus) {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'กรุณาเลือกสถานะใหม่',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
            
            if (newStatus === 'ไม่อนุมัติ' && !rejectionReason.trim()) {
                Swal.fire({
                    title: 'ข้อผิดพลาด!',
                    text: 'กรุณากรอกเหตุผลที่ไม่อนุมัติ',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
            
            // แสดยืนยัน
            const confirmText = newStatus === 'อนุมัติ' ? 'ยืนยันการอนุมัติคำร้องนี้?' : 'ยืนยันการไม่อนุมัติคำร้องนี้?';
            
            Swal.fire({
                title: 'ยืนยันการดำเนินการ',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitUpdateStatus();
                }
            });
        });
        
        // ฟังก์ชันส่งข้อมูลอัปเดตสถานะ
        function submitUpdateStatus() {
            const formData = new FormData($('#updateStatusForm')[0]);
            
            // แสดง loading
            Swal.fire({
                title: 'กำลังอัปเดตสถานะ',
                text: 'กรุณารอสักครู่...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // ส่งข้อมูลผ่าน AJAX
            $.ajax({
                url: 'update_status.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000, // 30 วินาที
                success: function(response) {
                    console.log('Response:', response);
                    
                    Swal.close();
                    
                    if (response && response.success) {
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: response.message || 'อัปเดตสถานะเรียบร้อยแล้ว',
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            // รีโหลดหน้า
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'ข้อผิดพลาด!',
                            text: response.message || 'เกิดข้อผิดพลาดในการอัปเดตสถานะ',
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    
                    Swal.close();
                    
                    let errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                    
                    if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            // ถ้าไม่ใช่ JSON ให้ใช้ fallback แบบ GET
                            fallbackToGet();
                            return;
                        }
                    }
                    
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        }
        
        // ฟังก์ชัน fallback สำหรับกรณี AJAX ไม่ทำงาน
        function fallbackToGet() {
            const requestId = $('#request_id').val();
            const newStatus = $('#new_status').val();
            const comment = $('#comment').val();
            const rejectionReason = $('#rejection_reason').val();
            
            const params = new URLSearchParams({
                request_id: requestId,
                new_status: newStatus,
                comment: comment,
                rejection_reason: rejectionReason
            });
            
            window.location.href = 'update_status.php?' + params.toString();
        }
    });
    
    // Function to open update status modal
    function updateStatus(requestId) {
        // รีเซ็ตฟอร์ม
        $('#request_id').val(requestId);
        $('#new_status').val('');
        $('#comment').val('');
        $('#rejection_reason').val('');
        $('#rejection_reason_group').hide();
        $('#rejection_reason').prop('required', false);
        
        // แสดง modal
        $('#updateStatusModal').modal('show');
    }
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>