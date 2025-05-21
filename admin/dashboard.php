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
include_once '../classes/Student.php';
include_once '../classes/Course.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$courseRequest = new CourseRequest($db);
$student = new Student($db);
$course = new Course($db);

// Get summary counts
$totalRequests = $courseRequest->getTotalRequests();
$pendingRequests = $courseRequest->getRequestsByStatus('pending');
$approvedRequests = $courseRequest->getRequestsByStatus('approved');
$rejectedRequests = $courseRequest->getRequestsByStatus('rejected');
$totalStudents = $student->getTotalStudents();
$totalCourses = $course->getTotalCourses();

// Get recent requests
$recentRequests = $courseRequest->getRecentRequests(5);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - ระบบขอเปิดรายวิชา</title>
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
                    <h1 class="h2">แดชบอร์ด</h1>
                </div>

                <!-- Dashboard stats -->
                <div class="row mt-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">คำขอทั้งหมด</h6>
                                        <h2 class="mb-0"><?php echo $totalRequests; ?></h2>
                                    </div>
                                    <i class="fas fa-file-alt fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">รอดำเนินการ</h6>
                                        <h2 class="mb-0"><?php echo $pendingRequests; ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">อนุมัติแล้ว</h6>
                                        <h2 class="mb-0"><?php echo $approvedRequests; ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">ไม่อนุมัติ</h6>
                                        <h2 class="mb-0"><?php echo $rejectedRequests; ?></h2>
                                    </div>
                                    <i class="fas fa-times-circle fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">คำขอล่าสุด</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>รหัสคำขอ</th>
                                                <th>นักศึกษา</th>
                                                <th>วันที่ขอ</th>
                                                <th>สถานะ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentRequests as $request): ?>
                                            <tr>
                                                <td><?php echo $request['id']; ?></td>
                                                <td><?php echo $request['student_name']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $request['status'];
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    
                                                    switch($status) {
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            $statusText = 'รอดำเนินการ';
                                                            break;
                                                        case 'approved_advisor':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'ที่ปรึกษาอนุมัติ';
                                                            break;
                                                        case 'approved_department':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'หัวหน้าแผนกอนุมัติ';
                                                            break;
                                                        case 'approved_curriculum':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'หัวหน้างานพัฒนาหลักสูตรอนุมัติ';
                                                            break;
                                                        case 'approved_deputy':
                                                            $statusClass = 'bg-info';
                                                            $statusText = 'รองผู้อำนวยการฝ่ายวิชาการอนุมัติ';
                                                            break;
                                                        case 'approved':
                                                            $statusClass = 'bg-success';
                                                            $statusText = 'อนุมัติแล้ว';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'bg-danger';
                                                            $statusText = 'ไม่อนุมัติ';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td>
                                                    <a href="course_requests.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">ดูรายละเอียด</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">ข้อมูลสรุป</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <div>
                                        <h6>จำนวนนักศึกษาทั้งหมด</h6>
                                        <h3><?php echo $totalStudents; ?> คน</h3>
                                    </div>
                                    <i class="fas fa-users fa-3x text-info"></i>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>จำนวนรายวิชาทั้งหมด</h6>
                                        <h3><?php echo $totalCourses; ?> รายวิชา</h3>
                                    </div>
                                    <i class="fas fa-book fa-3x text-info"></i>
                                </div>
                                <div class="mt-4">
                                    <a href="reports.php" class="btn btn-primary">พิมพ์รายงานสรุป</a>
                                    <a href="course_requests.php" class="btn btn-outline-secondary">ดูรายการคำขอทั้งหมด</a>
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
</body>
</html>