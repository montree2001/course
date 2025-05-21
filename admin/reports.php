<?php
session_start();
/* // Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
} */

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/CourseRequest.php';
include_once '../classes/Course.php';
include_once '../classes/ClassSchedule.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$courseRequest = new CourseRequest($db);
$course = new Course($db);
$schedule = new ClassSchedule($db);

// Get current semester and academic year
// In production, this would likely be set in the settings table
$current_semester = "1";
$current_academic_year = "2568";

// Get summary counts
$totalRequests = $courseRequest->getTotalRequests();
$pendingRequests = $courseRequest->getRequestsByStatus('pending');
$approvedRequests = $courseRequest->getRequestsByStatus('approved');
$rejectedRequests = $courseRequest->getRequestsByStatus('rejected');

// Get course requests summary by status
$statusSummary = $courseRequest->getRequestSummaryByStatus();

// Get course requests summary by course
$courseSummary = $courseRequest->getRequestSummaryByCourse();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน - ระบบขอเปิดรายวิชา</title>
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
                    <h1 class="h2">รายงาน</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">รายงานสรุปคำขอเปิดรายวิชา</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-around mb-4">
                                    <div class="text-center">
                                        <div class="display-6"><?php echo $totalRequests; ?></div>
                                        <div>คำขอทั้งหมด</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="display-6 text-warning"><?php echo $pendingRequests; ?></div>
                                        <div>รอดำเนินการ</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="display-6 text-success"><?php echo $approvedRequests; ?></div>
                                        <div>อนุมัติแล้ว</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="display-6 text-danger"><?php echo $rejectedRequests; ?></div>
                                        <div>ไม่อนุมัติ</div>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="../reports/course_summary_pdf.php" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-print me-2"></i> พิมพ์รายงานสรุปคำขอเปิดรายวิชา
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">รายงานตารางเรียน</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="form-group row mb-3">
                                        <label for="semester" class="col-sm-3 col-form-label">ภาคเรียน</label>
                                        <div class="col-sm-9">
                                            <select class="form-select" id="semester" name="semester">
                                                <option value="1" <?php echo $current_semester == '1' ? 'selected' : ''; ?>>1</option>
                                                <option value="2" <?php echo $current_semester == '2' ? 'selected' : ''; ?>>2</option>
                                                <option value="ฤดูร้อน" <?php echo $current_semester == 'ฤดูร้อน' ? 'selected' : ''; ?>>ฤดูร้อน</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <label for="academic_year" class="col-sm-3 col-form-label">ปีการศึกษา</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo $current_academic_year; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="../reports/schedule_pdf.php" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-print me-2"></i> พิมพ์ตารางเรียน
                                    </a>
                                    <a href="../reports/schedule_pdf.php?type=teacher" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-print me-2"></i> พิมพ์ตารางสอนอาจารย์
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">รายงานสรุปตามรายวิชา</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="courseSummaryTable">
                                        <thead>
                                            <tr>
                                                <th>รหัสวิชา</th>
                                                <th>ชื่อรายวิชา</th>
                                                <th>จำนวนคำขอทั้งหมด</th>
                                                <th>รอดำเนินการ</th>
                                                <th>อนุมัติแล้ว</th>
                                                <th>ไม่อนุมัติ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courseSummary as $summary): ?>
                                            <tr>
                                                <td><?php echo $summary['course_code']; ?></td>
                                                <td><?php echo $summary['course_name']; ?></td>
                                                <td><?php echo $summary['total_requests']; ?></td>
                                                <td><?php echo $summary['pending_count']; ?></td>
                                                <td><?php echo $summary['approved_count']; ?></td>
                                                <td><?php echo $summary['rejected_count']; ?></td>
                                                <td>
                                                    <a href="../reports/course_detail_pdf.php?course_id=<?php echo $summary['course_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-print"></i> พิมพ์รายงาน
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">พิมพ์แบบฟอร์มคำขอเปิดรายวิชา</h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">รหัสคำขอ</span>
                                            <input type="text" class="form-control" id="request_id" placeholder="กรอกรหัสคำขอที่ต้องการพิมพ์">
                                            <button class="btn btn-primary" type="button" id="printRequestForm">พิมพ์</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="course_requests.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-search me-2"></i> ค้นหาคำขอเปิดรายวิชา
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

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
            $('#courseSummaryTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                }
            });
            
            // Print schedule report with selected semester and academic year
            $('#printScheduleReport').click(function() {
                var semester = $('#semester').val();
                var academicYear = $('#academic_year').val();
                window.open('../reports/schedule_pdf.php?semester=' + semester + '&academic_year=' + academicYear, '_blank');
            });
            
            // Print course request form
            $('#printRequestForm').click(function() {
                var requestId = $('#request_id').val();
                if (requestId) {
                    window.open('../reports/course_request_pdf.php?id=' + requestId, '_blank');
                } else {
                    Swal.fire({
                        title: 'ข้อผิดพลาด',
                        text: 'กรุณากรอกรหัสคำขอ',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            });
        });
    </script>
</body>
</html>