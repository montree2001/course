<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/Course.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create course object
$course = new Course($db);

// Initialize variables
$edit_mode = false;
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new course
    if (isset($_POST['add_course'])) {
        $course->course_code = $_POST['course_code'];
        $course->course_name = $_POST['course_name'];
        $course->theory_hours = $_POST['theory_hours'];
        $course->practice_hours = $_POST['practice_hours'];
        $course->credits = $_POST['credits'];
        $course->total_hours = $_POST['total_hours'];
        
        // Check if course code already exists
        if ($course->isCourseCodeExists()) {
            $error_message = 'รหัสวิชานี้มีอยู่ในระบบแล้ว';
        } else {
            if ($course->create()) {
                $success_message = 'เพิ่มรายวิชาเรียบร้อยแล้ว';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเพิ่มรายวิชา';
            }
        }
    }
    
    // Update existing course
    if (isset($_POST['update_course'])) {
        $course->id = $_POST['course_id'];
        $course->course_code = $_POST['course_code'];
        $course->course_name = $_POST['course_name'];
        $course->theory_hours = $_POST['theory_hours'];
        $course->practice_hours = $_POST['practice_hours'];
        $course->credits = $_POST['credits'];
        $course->total_hours = $_POST['total_hours'];
        
        // Check if course code already exists (but ignore current course)
        $current_course = new Course($db);
        $current_course->id = $course->id;
        $current_course->getCourseById();
        
        if ($current_course->course_code !== $course->course_code && $course->isCourseCodeExists()) {
            $error_message = 'รหัสวิชานี้มีอยู่ในระบบแล้ว';
        } else {
            if ($course->update()) {
                $success_message = 'อัพเดทรายวิชาเรียบร้อยแล้ว';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการอัพเดทรายวิชา';
            }
        }
    }
    
    // Delete course
    if (isset($_POST['delete_course'])) {
        $course->id = $_POST['course_id'];
        
        if ($course->delete()) {
            $success_message = 'ลบรายวิชาเรียบร้อยแล้ว';
        } else {
            $error_message = 'เกิดข้อผิดพลาดในการลบรายวิชา';
        }
    }
}

// Check if in edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $course->id = $_GET['edit'];
    
    if ($course->getCourseById()) {
        $edit_mode = true;
    } else {
        header("Location: courses.php");
        exit;
    }
}

// Get all courses
$all_courses = $course->getAllCourses();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลรายวิชา - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                    <h1 class="h2">จัดการข้อมูลรายวิชา</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="fas fa-plus me-1"></i> เพิ่มรายวิชา
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">รายการรายวิชาทั้งหมด</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="coursesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>รหัสวิชา</th>
                                        <th>ชื่อรายวิชา</th>
                                        <th>ทฤษฎี</th>
                                        <th>ปฏิบัติ</th>
                                        <th>หน่วยกิต</th>
                                        <th>ชั่วโมงรวม</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_courses as $c): ?>
                                    <tr>
                                        <td><?php echo $c['course_code']; ?></td>
                                        <td><?php echo $c['course_name']; ?></td>
                                        <td><?php echo $c['theory_hours']; ?></td>
                                        <td><?php echo $c['practice_hours']; ?></td>
                                        <td><?php echo $c['credits']; ?></td>
                                        <td><?php echo $c['total_hours']; ?></td>
                                        <td>
                                            <a href="courses.php?edit=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-course" data-id="<?php echo $c['id']; ?>">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
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
    
    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCourseModalLabel">เพิ่มรายวิชา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course_code" class="form-label">รหัสวิชา</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="course_name" class="form-label">ชื่อรายวิชา</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="theory_hours" class="form-label">ชั่วโมงทฤษฎี</label>
                                <input type="number" class="form-control" id="theory_hours" name="theory_hours" min="0" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="practice_hours" class="form-label">ชั่วโมงปฏิบัติ</label>
                                <input type="number" class="form-control" id="practice_hours" name="practice_hours" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="credits" class="form-label">หน่วยกิต</label>
                                <input type="number" class="form-control" id="credits" name="credits" min="0" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label for="total_hours" class="form-label">ชั่วโมงรวม</label>
                                <input type="number" class="form-control" id="total_hours" name="total_hours" min="0" value="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_course" class="btn btn-primary">เพิ่มรายวิชา</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Course Modal -->
    <?php if ($edit_mode): ?>
    <div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCourseModalLabel">แก้ไขรายวิชา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course->id; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_course_code" class="form-label">รหัสวิชา</label>
                            <input type="text" class="form-control" id="edit_course_code" name="course_code" value="<?php echo $course->course_code; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_course_name" class="form-label">ชื่อรายวิชา</label>
                            <input type="text" class="form-control" id="edit_course_name" name="course_name" value="<?php echo $course->course_name; ?>" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_theory_hours" class="form-label">ชั่วโมงทฤษฎี</label>
                                <input type="number" class="form-control" id="edit_theory_hours" name="theory_hours" min="0" value="<?php echo $course->theory_hours; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_practice_hours" class="form-label">ชั่วโมงปฏิบัติ</label>
                                <input type="number" class="form-control" id="edit_practice_hours" name="practice_hours" min="0" value="<?php echo $course->practice_hours; ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_credits" class="form-label">หน่วยกิต</label>
                                <input type="number" class="form-control" id="edit_credits" name="credits" min="0" value="<?php echo $course->credits; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_total_hours" class="form-label">ชั่วโมงรวม</label>
                                <input type="number" class="form-control" id="edit_total_hours" name="total_hours" min="0" value="<?php echo $course->total_hours; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="update_course" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Course Form (Hidden) -->
    <form id="deleteCourseForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: none;">
        <input type="hidden" id="delete_course_id" name="course_id">
        <input type="hidden" name="delete_course" value="1">
    </form>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    <!-- Sweet Alert -->
    <script src="../assets/js/sweetalert2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#coursesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                }
            });
            
            // Show edit modal on page load if in edit mode
            <?php if ($edit_mode): ?>
            $('#editCourseModal').modal('show');
            <?php endif; ?>
            
            // Calculate total hours automatically
            $('#theory_hours, #practice_hours, #edit_theory_hours, #edit_practice_hours').on('input', function() {
                var form = $(this).closest('form');
                var theory = parseInt(form.find('[name="theory_hours"]').val()) || 0;
                var practice = parseInt(form.find('[name="practice_hours"]').val()) || 0;
                var total = theory + practice;
                form.find('[name="total_hours"]').val(total);
            });
            
            // Delete course confirmation
            $('.delete-course').click(function() {
                var courseId = $(this).data('id');
                
                Swal.fire({
                    title: 'ยืนยันการลบ',
                    text: "คุณต้องการลบรายวิชานี้ใช่หรือไม่? การกระทำนี้ไม่สามารถเปลี่ยนแปลงได้",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่, ลบเลย!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete_course_id').val(courseId);
                        $('#deleteCourseForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>