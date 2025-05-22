<?php
// admin/requests/index.php
// หน้าแสดงรายการคำร้องขอเปิดรายวิชาทั้งหมด (แก้ไขแล้ว)

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการคำร้องขอเปิดรายวิชา';
$currentPage = 'requests';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ตรวจสอบข้อความแจ้งเตือน
$successMessage = '';
$errorMessage = '';

if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// ดึงข้อมูลคำร้องทั้งหมด
try {
      // ดึงข้อมูลคำร้อง
      $stmt = $pdo->prepare("
      SELECT cr.*, 
             s.student_code, s.prefix, s.first_name, s.last_name, 
             s.level, s.year, s.phone,
             d.department_name,
             advisor.prefix as advisor_prefix, advisor.first_name as advisor_first_name, advisor.last_name as advisor_last_name,
             dept_head.prefix as dept_head_prefix, dept_head.first_name as dept_head_first_name, dept_head.last_name as dept_head_last_name
      FROM course_requests cr
      JOIN students s ON cr.student_id = s.student_id
      LEFT JOIN departments d ON s.department_id = d.department_id
      LEFT JOIN teachers advisor ON cr.advisor_id = advisor.teacher_id
      LEFT JOIN teachers dept_head ON cr.department_head_id = dept_head.teacher_id
      WHERE cr.request_id = :request_id
  ");
    $requests = $stmt->fetchAll();
    
    // ดึงข้อมูลสถิติ
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    foreach ($requests as $request) {
        $stats['total']++;
        switch ($request['status']) {
            case 'รอดำเนินการ':
                $stats['pending']++;
                break;
            case 'อนุมัติ':
                $stats['approved']++;
                break;
            case 'ไม่อนุมัติ':
                $stats['rejected']++;
                break;
        }
    }
    
} catch (PDOException $e) {
    $errorMessage = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    $requests = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item active" aria-current="page">จัดการคำร้องขอเปิดรายวิชา</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">จัดการคำร้องขอเปิดรายวิชา</h1>
    <div>
        <button class="btn btn-info btn-sm" id="refreshData">
            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
        </button>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">คำร้องทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-text" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">รอดำเนินการ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass-split" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">อนุมัติแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['approved']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">ไม่อนุมัติ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rejected']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-x-circle" style="font-size: 2rem; color: #5a5c69;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">รายการคำร้องขอเปิดรายวิชาทั้งหมด</h6>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="statusFilter" class="form-label">กรองตามสถานะ</label>
                <select class="form-select" id="statusFilter">
                    <option value="">ทั้งหมด</option>
                    <option value="รอดำเนินการ">รอดำเนินการ</option>
                    <option value="อนุมัติ">อนุมัติ</option>
                    <option value="ไม่อนุมัติ">ไม่อนุมัติ</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="levelFilter" class="form-label">กรองตามระดับชั้น</label>
                <select class="form-select" id="levelFilter">
                    <option value="">ทั้งหมด</option>
                    <option value="ปวช.">ปวช.</option>
                    <option value="ปวส.">ปวส.</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="semesterFilter" class="form-label">กรองตามภาคเรียน</label>
                <select class="form-select" id="semesterFilter">
                    <option value="">ทั้งหมด</option>
                    <option value="1">ภาคเรียนที่ 1</option>
                    <option value="2">ภาคเรียนที่ 2</option>
                    <option value="3">ภาคเรียนพิเศษ</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-secondary w-100" id="clearFilters">
                    <i class="bi bi-x-circle"></i> ล้างตัวกรอง
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="requestsDataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>รหัสคำร้อง</th>
                        <th>วันที่ยื่น</th>
                        <th>ข้อมูลนักเรียน</th>
                        <th>ระดับชั้น</th>
                        <th>ภาคเรียน/ปีการศึกษา</th>
                        <th>จำนวนรายวิชา</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $request['request_id']; ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo $request['prefix'] . $request['first_name'] . ' ' . $request['last_name']; ?></strong>
                                    </div>
                                    <small class="text-muted">รหัส: <?php echo $request['student_code']; ?></small>
                                    <?php if ($request['phone']): ?>
                                        <br><small class="text-muted">โทร: <?php echo $request['phone']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $request['level'] . ' ปีที่ ' . $request['year']; ?></td>
                                <td>
                                    <?php 
                                        echo 'ภาคเรียนที่ ' . $request['semester'] . '/' . $request['academic_year'];
                                    ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $request['course_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                                        <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                    <?php elseif ($request['status'] === 'อนุมัติ'): ?>
                                        <span class="badge bg-success">อนุมัติแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ไม่อนุมัติ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $request['request_id']; ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                                            <button type="button" class="btn btn-warning btn-sm update-status-btn" data-bs-toggle="tooltip" title="อัปเดตสถานะ" data-request-id="<?php echo $request['request_id']; ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="print.php?id=<?php echo $request['request_id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="พิมพ์บันทึก" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if ($request['status'] === 'อนุมัติ'): ?>
                                            <a href="../schedules/index.php?request_id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="จัดตารางเรียน">
                                                <i class="bi bi-calendar-week"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">ไม่พบข้อมูลคำร้อง</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">อัปเดตสถานะคำร้อง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="modal_request_id" name="request_id">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>คำร้องหมายเลข: <span id="modal_request_number"></span></strong>
                    </div>
                    
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
                        <div class="form-text text-danger">* กรุณาระบุเหตุผลที่ไม่อนุมัติอย่างชัดเจน</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" id="submitStatusBtn">
                        <i class="bi bi-check-circle"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables and Scripts -->
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#requestsDataTable').DataTable({
        order: [[0, 'desc']], // เรียงตามรหัสคำร้อง (ใหม่ที่สุดก่อน)
        columnDefs: [
            { orderable: false, targets: 7 }, // คอลัมน์การจัดการไม่ต้องเรียงลำดับ
            { width: "10%", targets: 0 },
            { width: "10%", targets: 1 },
            { width: "20%", targets: 2 },
            { width: "10%", targets: 3 },
            { width: "15%", targets: 4 },
            { width: "8%", targets: 5 },
            { width: "12%", targets: 6 },
            { width: "15%", targets: 7 }
        ],
        pageLength: 25,
        responsive: true,
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
    
    // Custom filters - แก้ไขให้ใช้งานได้
    $('#statusFilter').on('change', function() {
        const value = $(this).val();
        if (value === '') {
            table.column(6).search('').draw();
        } else {
            table.column(6).search(value).draw();
        }
    });
    
    $('#levelFilter').on('change', function() {
        const value = $(this).val();
        if (value === '') {
            table.column(3).search('').draw();
        } else {
            table.column(3).search(value).draw();
        }
    });
    
    $('#semesterFilter').on('change', function() {
        const value = $(this).val();
        if (value === '') {
            table.column(4).search('').draw();
        } else {
            table.column(4).search('ภาคเรียนที่ ' + value).draw();
        }
    });
    
    $('#clearFilters').on('click', function() {
        $('#statusFilter, #levelFilter, #semesterFilter').val('');
        table.search('').columns().search('').draw();
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Refresh data
    $('#refreshData').on('click', function() {
        Swal.fire({
            title: 'กำลังรีเฟรชข้อมูล',
            text: 'กรุณารอสักครู่...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
    
    // Update status button click - แก้ไขให้ทำงานได้
    $(document).on('click', '.update-status-btn', function(e) {
        e.preventDefault();
        const requestId = $(this).data('request-id');
        console.log('Update status for request ID:', requestId); // Debug
        
        // Reset form
        $('#modal_request_id').val(requestId);
        $('#modal_request_number').text(requestId);
        $('#new_status').val('');
        $('#comment').val('');
        $('#rejection_reason').val('');
        $('#rejection_reason_group').hide();
        $('#rejection_reason').prop('required', false);
        
        // Show modal
        $('#updateStatusModal').modal('show');
    });
    
    // Handle status change in modal
    $('#new_status').on('change', function() {
        const status = $(this).val();
        if (status === 'ไม่อนุมัติ') {
            $('#rejection_reason_group').slideDown();
            $('#rejection_reason').prop('required', true);
        } else {
            $('#rejection_reason_group').slideUp();
            $('#rejection_reason').prop('required', false);
            $('#rejection_reason').val('');
        }
    });
    
    // Handle update status form submission
    $('#updateStatusForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const requestId = $('#modal_request_id').val();
        const newStatus = $('#new_status').val();
        const comment = $('#comment').val();
        const rejectionReason = $('#rejection_reason').val();
        
        console.log('Form submit triggered:', { requestId, newStatus, comment, rejectionReason });
        
        // Validation
        if (!requestId || !newStatus) {
            Swal.fire({
                title: 'ข้อผิดพลาด!',
                text: 'กรุณาเลือกสถานะใหม่',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }
        
        if (newStatus === 'ไม่อนุมัติ' && !rejectionReason.trim()) {
            Swal.fire({
                title: 'ข้อผิดพลาด!',
                text: 'กรุณากรอกเหตุผลที่ไม่อนุมัติ',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return false;
        }
        
        // Confirm before submit
        const confirmText = newStatus === 'อนุมัติ' ? 
            'ต้องการอนุมัติคำร้องนี้หรือไม่?' : 
            'ต้องการไม่อนุมัติคำร้องนี้หรือไม่?';
        
        Swal.fire({
            title: 'ยืนยันการดำเนินการ',
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'อนุมัติ' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                submitStatusUpdate();
            }
        });
        
        return false;
    });
    
    // Handle submit button click separately
    $('#submitStatusBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#updateStatusForm').trigger('submit');
        return false;
    });
    
    function submitStatusUpdate() {
        // Get form data manually to ensure we have control
        const formData = {
            request_id: $('#modal_request_id').val(),
            new_status: $('#new_status').val(),
            comment: $('#comment').val(),
            rejection_reason: $('#rejection_reason').val()
        };
        
        console.log('Submitting form data:', formData);
        
        // Show loading
        Swal.fire({
            title: 'กำลังอัปเดตสถานะ',
            text: 'กรุณารอสักครู่...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit form via AJAX
        $.ajax({
            url: 'update_status.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('AJAX Success Response:', response);
                
                Swal.close();
                
                if (response && response.success) {
                    $('#updateStatusModal').modal('hide');
                    
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: response.message || 'อัปเดตสถานะเรียบร้อยแล้ว',
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: response.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState,
                    statusText: xhr.statusText
                });
                
                Swal.close();
                
                let errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.message || errorMessage;
                    } catch (e) {
                        errorMessage = 'เกิดข้อผิดพลาดในการประมวลผล';
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
});
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>