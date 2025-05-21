<?php
session_start();
// Check if user is logged in and is admin
/* if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
} */

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/CourseRequest.php';
include_once '../classes/Student.php';
include_once '../classes/Course.php';
include_once '../classes/Teacher.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$courseRequest = new CourseRequest($db);
$student = new Student($db);
$course = new Course($db);
$teacher = new Teacher($db);

// Check if viewing a specific request
$view_mode = false;
$edit_mode = false;
$request_id = null;
$request_details = null;
$request_items = null;

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $courseRequest->id = $request_id;
    
    // Get request details
    $request_details = $courseRequest->getRequestById();
    
    if (!$request_details) {
        header("Location: course_requests.php");
        exit;
    }
    
    // Get request items
    $request_items = $courseRequest->getRequestItems();
    
    $view_mode = true;
    
    // Check if in edit mode
    if (isset($_GET['edit']) && $_GET['edit'] == 'true') {
        $edit_mode = true;
    }
}

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $courseRequest->id = $_POST['request_id'];
    $courseRequest->status = $_POST['status'];
    
    if (isset($_POST['rejected_reason'])) {
        $courseRequest->rejected_reason = $_POST['rejected_reason'];
    }
    
    if ($courseRequest->updateStatus()) {
        // Log the status change
        $courseRequest->logStatusChange($_SESSION['user_id'], $_POST['status'], $_POST['comment'] ?? null);
        
        // Redirect to avoid form resubmission
        header("Location: course_requests.php?id={$_POST['request_id']}&success=1");
        exit;
    }
}

// Get all requests for listing
if (!$view_mode) {
    $all_requests = $courseRequest->getAllRequests();
}

// Get status history if viewing a request
$status_history = null;
if ($view_mode) {
    $status_history = $courseRequest->getStatusHistory();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำขอเปิดรายวิชา - ระบบขอเปิดรายวิชา</title>
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
                <?php if ($view_mode): ?>
                    <!-- View mode for a specific request -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <a href="course_requests.php" class="text-decoration-none text-secondary me-2">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            รายละเอียดคำขอเปิดรายวิชา #<?php echo $request_details['id']; ?>
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="../reports/course_request_pdf.php?id=<?php echo $request_details['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-print me-1"></i> พิมพ์แบบฟอร์ม
                                </a>
                                <?php if (!$edit_mode): ?>
                                <a href="course_requests.php?id=<?php echo $request_details['id']; ?>&edit=true" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i> แก้ไข
                                </a>
                                <?php else: ?>
                                <a href="course_requests.php?id=<?php echo $request_details['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> ยกเลิกการแก้ไข
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success" role="alert">
                        อัพเดทสถานะคำขอเรียบร้อยแล้ว
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">ข้อมูลคำขอ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">รหัสคำขอ:</div>
                                        <div class="col-md-8"><?php echo $request_details['id']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">วันที่ยื่นคำขอ:</div>
                                        <div class="col-md-8"><?php echo date('d/m/Y', strtotime($request_details['request_date'])); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">ภาคเรียน:</div>
                                        <div class="col-md-8"><?php echo $request_details['semester']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">ปีการศึกษา:</div>
                                        <div class="col-md-8"><?php echo $request_details['academic_year']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">สถานะ:</div>
                                        <div class="col-md-8">
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
                                        </div>
                                    </div>
                                    
                                    <?php if ($request_details['status'] === 'rejected' && !empty($request_details['rejected_reason'])): ?>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">เหตุผลที่ไม่อนุมัติ:</div>
                                        <div class="col-md-8"><?php echo $request_details['rejected_reason']; ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">ข้อมูลนักศึกษา</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">รหัสนักศึกษา:</div>
                                        <div class="col-md-8"><?php echo $request_details['student_code']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">ชื่อ-นามสกุล:</div>
                                        <div class="col-md-8"><?php echo $request_details['student_name']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">ระดับชั้น:</div>
                                        <div class="col-md-8"><?php echo $request_details['education_level'] . ' ชั้นปีที่ ' . $request_details['year']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">สาขาวิชา:</div>
                                        <div class="col-md-8"><?php echo $request_details['major']; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4 fw-bold">เบอร์โทรศัพท์:</div>
                                        <div class="col-md-8"><?php echo $request_details['phone_number']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">รายวิชาที่ขอเปิด</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">ลำดับ</th>
                                            <th width="15%">รหัสวิชา</th>
                                            <th width="25%">ชื่อรายวิชา</th>
                                            <th width="10%">ทฤษฎี</th>
                                            <th width="10%">ปฏิบัติ</th>
                                            <th width="10%">หน่วยกิต</th>
                                            <th width="10%">ชั่วโมง</th>
                                            <th width="15%">ครูประจำรายวิชา</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $i = 1;
                                        foreach ($request_items as $item): 
                                        ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo $item['course_code']; ?></td>
                                            <td><?php echo $item['course_name']; ?></td>
                                            <td><?php echo $item['theory_hours']; ?></td>
                                            <td><?php echo $item['practice_hours']; ?></td>
                                            <td><?php echo $item['credits']; ?></td>
                                            <td><?php echo $item['total_hours']; ?></td>
                                            <td><?php echo $item['teacher_name']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-7 mb-4">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="card-title mb-0">ประวัติการดำเนินการ</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <?php foreach ($status_history as $history): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <?php 
                                                    $status = $history['status'];
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
                                                    <span class="badge <?php echo $statusClass; ?> me-2"><?php echo $statusText; ?></span>
                                                    <span><?php echo $history['user_name']; ?></span>
                                                    <?php if (!empty($history['comment'])): ?>
                                                    <p class="mt-1 mb-0 text-muted"><?php echo $history['comment']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <small><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></small>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5 mb-4">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0">อัพเดทสถานะคำขอ</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $request_details['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">สถานะ</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="">-- เลือกสถานะ --</option>
                                                <option value="pending" <?php echo $request_details['status'] === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                                <option value="approved_advisor" <?php echo $request_details['status'] === 'approved_advisor' ? 'selected' : ''; ?>>ที่ปรึกษาอนุมัติ</option>
                                                <option value="approved_department" <?php echo $request_details['status'] === 'approved_department' ? 'selected' : ''; ?>>หัวหน้าแผนกอนุมัติ</option>
                                                <option value="approved_curriculum" <?php echo $request_details['status'] === 'approved_curriculum' ? 'selected' : ''; ?>>หัวหน้างานพัฒนาหลักสูตรอนุมัติ</option>
                                                <option value="approved_deputy" <?php echo $request_details['status'] === 'approved_deputy' ? 'selected' : ''; ?>>รองผู้อำนวยการฝ่ายวิชาการอนุมัติ</option>
                                                <option value="approved" <?php echo $request_details['status'] === 'approved' ? 'selected' : ''; ?>>อนุมัติแล้ว</option>
                                                <option value="rejected" <?php echo $request_details['status'] === 'rejected' ? 'selected' : ''; ?>>ไม่อนุมัติ</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3 rejected-reason" style="display: <?php echo $request_details['status'] === 'rejected' ? 'block' : 'none'; ?>;">
                                            <label for="rejected_reason" class="form-label">เหตุผลที่ไม่อนุมัติ</label>
                                            <textarea class="form-control" id="rejected_reason" name="rejected_reason" rows="3"><?php echo $request_details['rejected_reason']; ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="comment" class="form-label">หมายเหตุ (ถ้ามี)</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="update_status" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- List view for all requests -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">จัดการคำขอเปิดรายวิชา</h1>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">รายการคำขอเปิดรายวิชา</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="requestsTable">
                                    <thead>
                                        <tr>
                                            <th>รหัสคำขอ</th>
                                            <th>รหัสนักศึกษา</th>
                                            <th>ชื่อ-นามสกุล</th>
                                            <th>วันที่ยื่นคำขอ</th>
                                            <th>ภาคเรียน/ปีการศึกษา</th>
                                            <th>จำนวนรายวิชา</th>
                                            <th>สถานะ</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo $request['student_code']; ?></td>
                                            <td><?php echo $request['student_name']; ?></td>
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
                                                <a href="course_requests.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </a>
                                                <a href="../reports/course_request_pdf.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="fas fa-print"></i> พิมพ์
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
            $('#requestsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                },
                "order": [[0, "desc"]]
            });
            
            // Show/hide rejected reason field based on status
            $('#status').change(function() {
                if ($(this).val() === 'rejected') {
                    $('.rejected-reason').show();
                } else {
                    $('.rejected-reason').hide();
                }
            });
        });
    </script>
</body>
</html>