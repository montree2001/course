<?php
// admin/requests/index.php
// หน้าแสดงรายการคำร้องขอเปิดรายวิชาทั้งหมด

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
    $stmt = $pdo->query("
        SELECT cr.*, 
               s.student_code, s.prefix, s.first_name, s.last_name, 
               s.level, s.year, s.phone,
               d.department_name,
               COUNT(rd.detail_id) as course_count
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        LEFT JOIN request_details rd ON cr.request_id = rd.request_id
        GROUP BY cr.request_id
        ORDER BY cr.request_date DESC, cr.request_id DESC
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
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="อัปเดตสถานะ" onclick="updateStatus(<?php echo $request['request_id']; ?>)">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">อัปเดตสถานะคำร้อง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="request_id" name="request_id">
                    
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
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
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
                { orderable: false, targets: 7 } // คอลัมน์การจัดการไม่ต้องเรียงลำดับ
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
        
        // Custom filters
        $('#statusFilter, #levelFilter, #semesterFilter').on('change', function() {
            applyFilters();
        });
        
        $('#clearFilters').on('click', function() {
            $('#statusFilter, #levelFilter, #semesterFilter').val('');
            table.search('').columns().search('').draw();
        });
        
        function applyFilters() {
            const statusFilter = $('#statusFilter').val();
            const levelFilter = $('#levelFilter').val();
            const semesterFilter = $('#semesterFilter').val();
            
            // Apply filters
            table.column(6).search(statusFilter); // สถานะ
            table.column(3).search(levelFilter); // ระดับชั้น
            table.column(4).search(semesterFilter); // ภาคเรียน
            
            table.draw();
        }
        
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Refresh data
        $('#refreshData').on('click', function() {
            location.reload();
        });
        
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
            
            const formData = new FormData(this);
            
            // Show loading
            Swal.fire({
                title: 'กำลังอัปเดตสถานะ',
                text: 'กรุณารอสักครู่...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form via AJAX
            $.ajax({
                url: 'update_status.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    
                    if (response.success) {
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'ข้อผิดพลาด!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        });
    });
    
    // Function to open update status modal
    function updateStatus(requestId) {
        $('#request_id').val(requestId);
        $('#new_status').val('');
        $('#comment').val('');
        $('#rejection_reason').val('');
        $('#rejection_reason_group').hide();
        $('#rejection_reason').prop('required', false);
        
        $('#updateStatusModal').modal('show');
    }
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>