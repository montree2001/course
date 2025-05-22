<?php
// admin/teachers/index.php
// หน้าจัดการข้อมูลครู

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'จัดการข้อมูลครู';
$currentPage = 'teachers';

// เรียกใช้ไฟล์ header.php
require_once '../includes/header.php';

// คำสั่ง SQL สำหรับดึงข้อมูลครูทั้งหมด
$sql = "
    SELECT t.*, d.department_name 
    FROM teachers t
    LEFT JOIN departments d ON t.department_id = d.department_id
    ORDER BY t.first_name ASC
";

try {
    $stmt = $pdo->query($sql);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
}

// ตรวจสอบว่ามีข้อความแจ้งเตือนที่ส่งมาจากหน้าอื่นหรือไม่
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : '';

// ลบข้อความออกจาก session เพื่อไม่ให้แสดงซ้ำ
unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">จัดการข้อมูลครู</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> เพิ่มครู
    </a>
</div>

<!-- Alert messages -->
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Teachers data card -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">รายการข้อมูลครูทั้งหมด</h6>
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> ส่งออกข้อมูล
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                <li><button class="dropdown-item" type="button" id="exportExcel"><i class="bi bi-file-excel"></i> ส่งออกเป็น Excel</button></li>
                <li><button class="dropdown-item" type="button" id="exportPDF"><i class="bi bi-file-pdf"></i> ส่งออกเป็น PDF</button></li>
                <li><button class="dropdown-item" type="button" id="printData"><i class="bi bi-printer"></i> พิมพ์รายการ</button></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="teachersTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="20%">ชื่อ-นามสกุล</th>
                        <th width="25%">สาขาวิชา</th>
                        <th width="20%">จำนวนรายวิชาที่สอน</th>
                        <th width="15%">สถานะ</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($teachers)): ?>
                        <?php foreach ($teachers as $index => $teacher): ?>
                            <?php
                            // ดึงจำนวนรายวิชาที่ครูสอน
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM request_details WHERE teacher_id = ?");
                                $stmt->execute([$teacher['teacher_id']]);
                                $course_count = $stmt->fetch()['count'];
                            } catch (PDOException $e) {
                                $course_count = 0;
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td><?php echo $teacher['prefix'] . $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                <td><?php echo $teacher['department_name'] ?? 'ไม่ระบุ'; ?></td>
                                <td class="text-center"><?php echo $course_count; ?> รายวิชา</td>
                                <td class="text-center">
                                    <?php if ($course_count > 0): ?>
                                        <span class="badge bg-success">มีการสอน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ยังไม่มีการสอน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i> แก้ไข
                                    </a>
                                    <a href="delete.php?id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-danger btn-sm btn-delete" data-item-name="ครู <?php echo $teacher['prefix'] . $teacher['first_name']; ?>">
                                        <i class="bi bi-trash"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">ไม่พบข้อมูลครู</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const teachersTable = $('#teachersTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-excel"></i> Excel',
                className: 'btn btn-success',
                title: 'รายการข้อมูลครู',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-danger',
                title: 'รายการข้อมูลครู',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
                customize: function(doc) {
                    doc.defaultStyle.font = 'THSarabun';
                    doc.defaultStyle.fontSize = 16;
                    doc.pageMargins = [40, 60, 40, 60];
                    doc.content[0].text = 'รายการข้อมูลครูทั้งหมด';
                    doc.content[0].alignment = 'center';
                    doc.content[0].fontSize = 20;
                    
                    // Add header and footer
                    doc['header'] = function() {
                        return {
                            columns: [
                                {
                                    text: 'วิทยาลัยการอาชีพปราสาท',
                                    alignment: 'center',
                                    fontSize: 18,
                                    margin: [0, 20, 0, 0]
                                }
                            ]
                        };
                    };
                    
                    doc['footer'] = function(currentPage, pageCount) {
                        return {
                            columns: [
                                {
                                    text: 'หน้า ' + currentPage.toString() + ' จาก ' + pageCount.toString(),
                                    alignment: 'center'
                                }
                            ],
                            margin: [0, 0, 0, 20]
                        };
                    };
                }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> พิมพ์',
                className: 'btn btn-info',
                title: 'รายการข้อมูลครู',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            }
        ]
    });
    
    // Link custom export buttons to DataTables buttons
    document.getElementById('exportExcel').addEventListener('click', function() {
        teachersTable.button('.buttons-excel').trigger();
    });
    
    document.getElementById('exportPDF').addEventListener('click', function() {
        teachersTable.button('.buttons-pdf').trigger();
    });
    
    document.getElementById('printData').addEventListener('click', function() {
        teachersTable.button('.buttons-print').trigger();
    });
});
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once '../includes/footer.php';
?>