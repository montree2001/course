<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/Teacher.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create teacher object
$teacher = new Teacher($db);

// Process form submission for adding/editing teacher
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher']) || isset($_POST['update_teacher'])) {
        // Set teacher properties
        if (isset($_POST['teacher_id'])) {
            $teacher->id = $_POST['teacher_id'];
        }
        $teacher->name_prefix = $_POST['name_prefix'];
        $teacher->first_name = $_POST['first_name'];
        $teacher->last_name = $_POST['last_name'];
        $teacher->department = $_POST['department'];
        
        // Add or update teacher
        if (isset($_POST['add_teacher'])) {
            if ($teacher->create()) {
                $success_message = 'เพิ่มข้อมูลครูเรียบร้อยแล้ว';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเพิ่มข้อมูลครู';
            }
        } else {
            if ($teacher->update()) {
                $success_message = 'อัปเดตข้อมูลครูเรียบร้อยแล้ว';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูลครู';
            }
        }
    } elseif (isset($_POST['delete_teacher'])) {
        // Delete teacher
        $teacher->id = $_POST['teacher_id'];
        
        if ($teacher->delete()) {
            $success_message = 'ลบข้อมูลครูเรียบร้อยแล้ว';
        } else {
            $error_message = 'เกิดข้อผิดพลาดในการลบข้อมูลครู';
        }
    }
}

// Get all teachers
$all_teachers = $teacher->getAllTeachers();

// Get all departments for filter
$all_departments = $teacher->getAllDepartments();

// For editing a specific teacher
$edit_mode = false;
$edit_teacher = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $teacher->id = $_GET['edit'];
    if ($teacher->getTeacherById()) {
        $edit_mode = true;
        $edit_teacher = [
            'id' => $teacher->id,
            'name_prefix' => $teacher->name_prefix,
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'department' => $teacher->department
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลครู - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="../assets/css/select2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once '../includes/admin_sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">จัดการข้อมูลครู</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-plus me-1"></i> เพิ่มครู
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Teacher List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">รายชื่อครูทั้งหมด</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group">
                                    <label class="input-group-text" for="departmentFilter">แผนกวิชา:</label>
                                    <select class="form-select" id="departmentFilter">
                                        <option value="">ทั้งหมด</option>
                                        <?php foreach ($all_departments as $dept): ?>
                                        <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="teachersTable">
                                <thead>
                                    <tr>
                                        <th width="5%">ลำดับ</th>
                                        <th width="15%">คำนำหน้า</th>
                                        <th width="20%">ชื่อ</th>
                                        <th width="20%">นามสกุล</th>
                                        <th width="25%">แผนกวิชา</th>
                                        <th width="15%">การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $i = 1;
                                    foreach ($all_teachers as $teacher_item): 
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i++; ?></td>
                                        <td><?php echo $teacher_item['name_prefix']; ?></td>
                                        <td><?php echo $teacher_item['first_name']; ?></td>
                                        <td><?php echo $teacher_item['last_name']; ?></td>
                                        <td><?php echo $teacher_item['department']; ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="teachers.php?edit=<?php echo $teacher_item['id']; ?>" class="btn btn-warning">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </a>
                                                <button type="button" class="btn btn-danger delete-teacher" data-id="<?php echo $teacher_item['id']; ?>" data-name="<?php echo $teacher_item['name_prefix'] . $teacher_item['first_name'] . ' ' . $teacher_item['last_name']; ?>">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTeacherModalLabel">เพิ่มข้อมูลครู</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name_prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                            <select class="form-select" id="name_prefix" name="name_prefix" required>
                                <option value="">-- เลือก --</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ว่าที่ร้อยตรี">ว่าที่ร้อยตรี</option>
                                <option value="ดร.">ดร.</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="department" class="form-label">แผนกวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="department" name="department" required list="departmentList">
                            <datalist id="departmentList">
                                <?php foreach ($all_departments as $dept): ?>
                                <option value="<?php echo $dept; ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" name="add_teacher">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <?php if ($edit_mode && $edit_teacher): ?>
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTeacherModalLabel">แก้ไขข้อมูลครู</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="teacher_id" value="<?php echo $edit_teacher['id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name_prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_name_prefix" name="name_prefix" required>
                                <option value="">-- เลือก --</option>
                                <option value="นาย" <?php echo $edit_teacher['name_prefix'] === 'นาย' ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo $edit_teacher['name_prefix'] === 'นาง' ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo $edit_teacher['name_prefix'] === 'นางสาว' ? 'selected' : ''; ?>>นางสาว</option>
                                <option value="ว่าที่ร้อยตรี" <?php echo $edit_teacher['name_prefix'] === 'ว่าที่ร้อยตรี' ? 'selected' : ''; ?>>ว่าที่ร้อยตรี</option>
                                <option value="ดร." <?php echo $edit_teacher['name_prefix'] === 'ดร.' ? 'selected' : ''; ?>>ดร.</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" value="<?php echo $edit_teacher['first_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" value="<?php echo $edit_teacher['last_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_department" class="form-label">แผนกวิชา <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_department" name="department" value="<?php echo $edit_teacher['department']; ?>" required list="editDepartmentList">
                            <datalist id="editDepartmentList">
                                <?php foreach ($all_departments as $dept): ?>
                                <option value="<?php echo $dept; ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" name="update_teacher">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Delete Teacher Form (Hidden) -->
    <form id="deleteTeacherForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: none;">
        <input type="hidden" id="delete_teacher_id" name="teacher_id">
        <input type="hidden" name="delete_teacher" value="1">
    </form>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 JS -->
    <script src="../assets/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="../assets/js/sweetalert2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var teachersTable = $('#teachersTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                },
                "order": [[0, "asc"]]
            });
            
            // Show edit modal on page load if in edit mode
            <?php if ($edit_mode): ?>
            $('#editTeacherModal').modal('show');
            <?php endif; ?>
            
            // Filter by department
            $('#departmentFilter').change(function() {
                var department = $(this).val();
                teachersTable.column(4).search(department).draw();
            });
            
            // Delete teacher confirmation
            $('.delete-teacher').click(function() {
                var teacherId = $(this).data('id');
                var teacherName = $(this).data('name');
                
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    text: "คุณต้องการลบข้อมูลครู '" + teacherName + "' ใช่หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ลบเลย!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete_teacher_id').val(teacherId);
                        $('#deleteTeacherForm').submit();
                    }
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>