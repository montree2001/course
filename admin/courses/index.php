<?php
// admin/courses/index.php
// หน้าจัดการข้อมูลรายวิชา แสดงรายการรายวิชาทั้งหมด

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการข้อมูลรายวิชา';
$currentPage = 'courses';

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

// ดึงข้อมูลรายวิชาทั้งหมด
try {
    $stmt = $pdo->query("
        SELECT * FROM courses 
        ORDER BY course_code
    ");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item active" aria-current="page">จัดการข้อมูลรายวิชา</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">จัดการข้อมูลรายวิชา</h1>
    <a href="add.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> เพิ่มรายวิชาใหม่
    </a>
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

<!-- Content Row -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">รายการรายวิชาทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="coursesDataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>รหัสวิชา</th>
                        <th>ชื่อรายวิชา</th>
                        <th class="text-center">ทฤษฎี (ชม.)</th>
                        <th class="text-center">ปฏิบัติ (ชม.)</th>
                        <th class="text-center">หน่วยกิต</th>
                        <th>วันที่สร้าง</th>
                        <th>วันที่แก้ไข</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($course['theory_hours']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($course['practice_hours']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($course['credit_hours']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($course['updated_at'])); ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="edit.php?id=<?php echo $course['course_id']; ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $course['course_id']; ?>" class="btn btn-danger btn-sm btn-delete" data-bs-toggle="tooltip" title="ลบ" data-item-name="รายวิชา <?php echo htmlspecialchars($course['course_code']); ?>">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">ไม่พบข้อมูลรายวิชา</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables Initialization Script -->
<script>
    $(document).ready(function() {
        $('#coursesDataTable').DataTable({
            "order": [[0, 'asc']], // เรียงตามรหัสวิชา
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                    className: 'btn btn-success',
                    title: 'รายการรายวิชาทั้งหมด',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> พิมพ์',
                    className: 'btn btn-info',
                    title: 'รายการรายวิชาทั้งหมด',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                }
            ],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>" +
                 "<'row'<'col-sm-12'B>>",
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

        // เปิดใช้งาน tooltip
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>