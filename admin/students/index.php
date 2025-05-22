<?php
// admin/students/index.php
// หน้าแสดงรายการนักเรียนทั้งหมด

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการข้อมูลนักเรียน';
$currentPage = 'students';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// ดึงข้อมูลนักเรียนทั้งหมด
try {
    $stmt = $pdo->prepare("
        SELECT s.*, d.department_name 
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.department_id
        ORDER BY s.student_code
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    // แสดงข้อความข้อผิดพลาด
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage() . '</div>';
}

// ตรวจสอบการลบข้อมูล (ถ้ามี message จากการลบ)
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-' . $_SESSION['message_type'] . ' alert-dismissible fade show">';
    echo $_SESSION['message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    
    // ลบข้อความออกจาก session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">จัดการข้อมูลนักเรียน</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> เพิ่มนักเรียน
    </a>
</div>

<!-- Card with DataTable -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">รายการนักเรียนทั้งหมด</h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-three-dots-vertical text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                <div class="dropdown-header">ตัวเลือก:</div>
                <a class="dropdown-item" href="#" id="refreshTable"><i class="bi bi-arrow-clockwise"></i> รีเฟรชข้อมูล</a>
                <a class="dropdown-item" href="#" id="exportExcel"><i class="bi bi-file-excel"></i> ส่งออก Excel</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" id="importData" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bi bi-file-earmark-arrow-up"></i> นำเข้าข้อมูล
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="10%">รหัสนักเรียน</th>
                        <th width="25%">ชื่อ-นามสกุล</th>
                        <th width="10%">ระดับชั้น</th>
                        <th width="20%">สาขาวิชา</th>
                        <th width="15%">เบอร์โทรศัพท์</th>
                        <th width="20%">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['prefix'] . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['level'] . ' ปีที่ ' . $student['year']); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $student['student_id']; ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="ดูข้อมูล">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $student['student_id']; ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $student['student_id']; ?>" class="btn btn-danger btn-sm btn-delete" data-bs-toggle="tooltip" title="ลบ" data-item-name="นักเรียน <?php echo $student['prefix'] . $student['first_name'] . ' ' . $student['last_name']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <a href="../reports/student_requests.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" title="ดูคำร้อง">
                                        <i class="bi bi-file-text"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">นำเข้าข้อมูลนักเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="import.php" method="post" enctype="multipart/form-data" id="importForm">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">เลือกไฟล์ Excel (.xlsx, .xls)</label>
                        <input type="file" class="form-control" id="importFile" name="importFile" accept=".xlsx, .xls" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="overwriteExisting" name="overwriteExisting">
                            <label class="form-check-label" for="overwriteExisting">
                                แทนที่ข้อมูลที่มีอยู่แล้ว (ถ้ารหัสนักเรียนซ้ำกัน)
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> กรุณาดาวน์โหลด <a href="template/student_import_template.xlsx" class="alert-link">แบบฟอร์มนำเข้าข้อมูล</a> เพื่อดูรูปแบบที่ถูกต้อง
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" form="importForm" class="btn btn-primary">นำเข้าข้อมูล</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable with buttons
        const table = $('#studentsTable').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    title: 'รายการนักเรียนทั้งหมด',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> พิมพ์',
                    className: 'btn btn-info',
                    title: 'รายการนักเรียนทั้งหมด',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                }
            ]
        });
        
        // Add Export Excel button event
        $('#exportExcel').click(function() {
            table.button('.buttons-excel').trigger();
        });
        
        // Add Refresh button event
        $('#refreshTable').click(function() {
            location.reload();
        });
        
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>