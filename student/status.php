<?php
session_start();
// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/Student.php';
include_once '../classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$student = new Student($db);
$courseRequest = new CourseRequest($db);

// Get student information
$student->user_id = $_SESSION['user_id'];
if (!$student->getStudentByUserId()) {
    // Redirect to profile creation if student profile doesn't exist
    header("Location: create_profile.php");
    exit;
}

// Get request ID if provided
$view_mode = false;
$request_id = null;
$request_details = null;
$request_items = null;
$status_history = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $request_id = $_GET['id'];
    $courseRequest->id = $request_id;
    
    // Get request details
    $request_details = $courseRequest->getRequestById();
    
    // Check if this request belongs to this student
    if ($request_details && $request_details['student_id'] == $student->id) {
        $view_mode = true;
        
        // Get request items
        $request_items = $courseRequest->getRequestItems();
        
        // Get status history
        $status_history = $courseRequest->getStatusHistory();
    } else {
        // Redirect if request doesn't exist or doesn't belong to this student
        header("Location: status.php");
        exit;
    }
}

// Get all requests for this student
$all_requests = $courseRequest->getRequestsByStudentId($student->id);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะคำขอ - ระบบขอเปิดรายวิชา</title>
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
    <?php include_once '../includes/student_navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if ($view_mode): ?>
            <!-- View mode for a specific request -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="status.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> กลับไปยังรายการคำขอทั้งหมด
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">รายละเอียดคำขอเปิดรายวิชา #<?php echo $request_details['id']; ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>รหัสคำขอ:</strong> <?php echo $request_details['id']; ?></p>
                                    <p class="mb-1"><strong>วันที่ยื่นคำขอ:</strong> <?php echo date('d/m/Y', strtotime($request_details['request_date'])); ?></p>
                                    <p class="mb-1"><strong>ภาคเรียน:</strong> <?php echo $request_details['semester']; ?></p>
                                    <p class="mb-1"><strong>ปีการศึกษา:</strong> <?php echo $request_details['academic_year']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>สถานะ:</strong>
                                        <?php 
                                        $status = $request_details['status'];
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
                                    </p>
                                    <?php if ($request_details['status'] === 'rejected' && !empty($request_details['rejected_reason'])): ?>
                                    <p class="mb-1"><strong>เหตุผลที่ไม่อนุมัติ:</strong> <?php echo $request_details['rejected_reason']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($request_details['status'] === 'approved'): ?>
                                    <div class="mt-3">
                                        <a href="../reports/course_request_pdf.php?id=<?php echo $request_details['id']; ?>" class="btn btn-success" target="_blank">
                                            <i class="fas fa-print me-2"></i> พิมพ์แบบฟอร์มคำขอ
                                        </a>
                                        <a href="schedule.php" class="btn btn-info">
                                            <i class="fas fa-calendar-alt me-2"></i> ดูตารางเรียน
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-3">
                                        <a href="../reports/course_request_pdf.php?id=<?php echo $request_details['id']; ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-print me-2"></i> พิมพ์แบบฟอร์มคำขอ
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h6 class="mt-4 mb-3">รายวิชาที่ขอเปิด</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">ลำดับ</th>
                                            <th width="15%">รหัสวิชา</th>
                                            <th width="35%">ชื่อรายวิชา</th>
                                            <th width="10%">ทฤษฎี-ปฏิบัติ</th>
                                            <th width="10%">หน่วยกิต</th>
                                            <th width="25%">ครูประจำวิชา</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $i = 1;
                                        foreach ($request_items as $item): 
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i++; ?></td>
                                            <td class="text-center"><?php echo $item['course_code']; ?></td>
                                            <td><?php echo $item['course_name']; ?></td>
                                            <td class="text-center"><?php echo $item['theory_hours'] . '-' . $item['practice_hours']; ?></td>
                                            <td class="text-center"><?php echo $item['credits']; ?></td>
                                            <td><?php echo $item['teacher_name']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">การติดตามสถานะ</h5>
                        </div>
                        <div class="card-body">
                            <div class="tracking-timeline">
                                <?php if (count($status_history) > 0): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($status_history as $index => $history): ?>
                                    <li class="timeline-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="timeline-badge">
                                            <?php 
                                            $icon = 'check';
                                            switch ($history['status']) {
                                                case 'pending':
                                                    $icon = 'clock';
                                                    break;
                                                case 'approved':
                                                    $icon = 'check-double';
                                                    break;
                                                case 'rejected':
                                                    $icon = 'times';
                                                    break;
                                                default:
                                                    $icon = 'check';
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php 
                                                    $statusText = '';
                                                    switch($history['status']) {
                                                        case 'pending':
                                                            $statusText = 'รอดำเนินการ';
                                                            break;
                                                        case 'approved_advisor':
                                                            $statusText = 'ที่ปรึกษาอนุมัติ';
                                                            break;
                                                        case 'approved_department':
                                                            $statusText = 'หัวหน้าแผนกอนุมัติ';
                                                            break;
                                                        case 'approved_curriculum':
                                                            $statusText = 'หัวหน้างานพัฒนาหลักสูตรอนุมัติ';
                                                            break;
                                                        case 'approved_deputy':
                                                            $statusText = 'รองผู้อำนวยการฝ่ายวิชาการอนุมัติ';
                                                            break;
                                                        case 'approved':
                                                            $statusText = 'อนุมัติแล้ว';
                                                            break;
                                                        case 'rejected':
                                                            $statusText = 'ไม่อนุมัติ';
                                                            break;
                                                    }
                                                    echo $statusText;
                                                    ?>
                                                </h6>
                                                <small><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo $history['user_name']; ?></p>
                                            <?php if (!empty($history['comment'])): ?>
                                            <p class="text-muted small mb-0"><?php echo $history['comment']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">ยังไม่มีประวัติการดำเนินการ</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- List view for all requests -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">ติดตามสถานะคำขอเปิดรายวิชา</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="requestsTable">
                                    <thead>
                                        <tr>
                                            <th>รหัสคำขอ</th>
                                            <th>วันที่ยื่นคำขอ</th>
                                            <th>ภาคเรียน/ปีการศึกษา</th>
                                            <th>จำนวนรายวิชา</th>
                                            <th>สถานะ</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($all_requests) > 0): ?>
                                            <?php foreach ($all_requests as $request): ?>
                                            <tr>
                                                <td><?php echo $request['id']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                                <td><?php echo $request['semester'] . '/' . $request['academic_year']; ?></td>
                                                <td><?php echo $request['course_count']; ?></td>
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
                                                    <a href="status.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> ดูรายละเอียด
                                                    </a>
                                                    <a href="../reports/course_request_pdf.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                        <i class="fas fa-print"></i> พิมพ์
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">ยังไม่มีคำขอเปิดรายวิชา <a href="course_request.php" class="btn btn-sm btn-primary ms-2">ยื่นคำขอเปิดรายวิชา</a></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- jQuery -->
    <script src="../assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#requestsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                },
                "responsive": true,
                "order": [[0, "desc"]]
            });
        });
    </script>
    
    <style>
        .tracking-timeline ul {
            padding-left: 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 15px;
        }
        
        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            text-align: center;
            line-height: 30px;
            color: #6c757d;
        }
        
        .timeline-item.active .timeline-badge {
            background-color: #0d6efd;
            color: white;
        }
        
        .timeline-content {
            border-left: 2px solid #dee2e6;
            padding-left: 15px;
            padding-bottom: 15px;
        }
        
        .timeline-item:last-child .timeline-content {
            padding-bottom: 0;
            border-left: 2px solid transparent;
        }
    </style>
</body>
</html>