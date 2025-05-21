<?php
// Include database and necessary classes
include_once 'config/database.php';
include_once 'classes/CourseRequest.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create objects
$courseRequest = new CourseRequest($db);

// Get request ID from URL or POST
$request_id = isset($_GET['request_id']) ? $_GET['request_id'] : (isset($_POST['request_id']) ? $_POST['request_id'] : null);
$student_code = isset($_POST['student_code']) ? $_POST['student_code'] : null;

// Variables for display
$request_details = null;
$request_items = null;
$status_history = null;
$view_mode = false;
$error_message = '';

// If request ID is provided, get request details
if ($request_id) {
    $courseRequest->id = $request_id;
    $request_details = $courseRequest->getRequestById();
    
    if ($request_details) {
        $view_mode = true;
        $request_items = $courseRequest->getRequestItems();
        $status_history = $courseRequest->getStatusHistory();
    } else {
        $error_message = 'ไม่พบข้อมูลคำขอ กรุณาตรวจสอบรหัสคำขอให้ถูกต้อง';
    }
}
// If student code is provided, search for requests
elseif ($student_code) {
    // Query to get course requests by student code
    $query = "SELECT cr.* FROM course_requests cr
              INNER JOIN temp_students ts ON cr.student_id = ts.id
              WHERE ts.student_code = :student_code
              ORDER BY cr.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_code', $student_code);
    $stmt->execute();
    
    $student_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($student_requests)) {
        $error_message = 'ไม่พบข้อมูลคำขอสำหรับรหัสนักศึกษานี้';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบสถานะคำขอ - ระบบขอเปิดรายวิชา</title>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Logo" height="36" class="d-inline-block align-text-top me-2">
                ระบบขอเปิดรายวิชา
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_form.php">
                            <i class="fas fa-file-alt me-1"></i> ขอเปิดรายวิชา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="check_status.php">
                            <i class="fas fa-search me-1"></i> ตรวจสอบสถานะ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">
                            <i class="fas fa-calendar-alt me-1"></i> ตารางเรียน
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">
                            <i class="fas fa-user-lock me-1"></i> เข้าสู่ระบบ (สำหรับเจ้าหน้าที่)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php if ($view_mode): ?>
            <!-- View mode for a specific request -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="check_status.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> กลับ
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
                                        <a href="reports/course_request_pdf.php?id=<?php echo $request_details['id']; ?>" class="btn btn-success" target="_blank">
                                            <i class="fas fa-print me-2"></i> พิมพ์แบบฟอร์มคำขอ
                                        </a>
                                        <a href="schedules.php?request_id=<?php echo $request_details['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-calendar-alt me-2"></i> ดูตารางเรียน
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-3">
                                        <a href="reports/course_request_pdf.php?id=<?php echo $request_details['id']; ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-print me-2"></i> พิมพ์แบบฟอร์มคำขอ
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h6 class="mt-4 mb-3">ข้อมูลนักศึกษา</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%" class="bg-light">รหัสนักศึกษา</th>
                                        <td><?php echo $request_details['student_code']; ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">ชื่อ-นามสกุล</th>
                                        <td><?php echo $request_details['student_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">ระดับชั้น</th>
                                        <td><?php echo $request_details['education_level'] . ' ชั้นปีที่ ' . $request_details['year']; ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">สาขาวิชา</th>
                                        <td><?php echo $request_details['major']; ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">เบอร์โทรศัพท์</th>
                                        <td><?php echo $request_details['phone_number']; ?></td>
                                    </tr>
                                </table>
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
            
        <?php elseif (isset($student_requests) && !empty($student_requests)): ?>
            <!-- List of requests for a student -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">คำขอเปิดรายวิชาของรหัสนักศึกษา: <?php echo htmlspecialchars($student_code); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="requestsTable">
                                    <thead>
                                        <tr>
                                            <th>รหัสคำขอ</th>
                                            <th>วันที่ยื่นคำขอ</th>
                                            <th>ภาคเรียน/ปีการศึกษา</th>
                                            <th>สถานะ</th>
                                            <th>การดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($student_requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['id']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                            <td><?php echo $request['semester'] . '/' . $request['academic_year']; ?></td>
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
                                                <a href="check_status.php?request_id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </a>
                                                <a href="reports/course_request_pdf.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
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
                </div>
            </div>
        <?php else: ?>
            <!-- Search form -->
            <div class="row">
                <div class="col-md-7 mx-auto">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">ตรวจสอบสถานะคำขอเปิดรายวิชา</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title mb-0">ค้นหาด้วยรหัสคำขอ</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                <div class="mb-3">
                                                    <label for="request_id" class="form-label">รหัสคำขอ</label>
                                                    <input type="text" class="form-control" id="request_id" name="request_id" placeholder="กรอกรหัสคำขอ" required>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-search me-2"></i> ค้นหา
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title mb-0">ค้นหาด้วยรหัสนักศึกษา</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                <div class="mb-3">
                                                    <label for="student_code" class="form-label">รหัสนักศึกษา</label>
                                                    <input type="text" class="form-control" id="student_code" name="student_code" placeholder="กรอกรหัสนักศึกษา" required>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-search me-2"></i> ค้นหา
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p class="mb-1">หากยังไม่มีคำขอเปิดรายวิชา</p>
                                <a href="request_form.php" class="btn btn-success">
                                    <i class="fas fa-file-alt me-2"></i> ยื่นคำขอเปิดรายวิชา
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">ขั้นตอนการติดตามสถานะคำขอ</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="step-number mx-auto mb-3">1</div>
                                            <h6>กรอกรหัสคำขอหรือรหัสนักศึกษา</h6>
                                            <p class="small text-muted">ค้นหาคำขอของคุณจากรหัสคำขอที่ได้รับหรือรหัสประจำตัวนักศึกษา</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="step-number mx-auto mb-3">2</div>
                                            <h6>ตรวจสอบรายละเอียดคำขอ</h6>
                                            <p class="small text-muted">ดูข้อมูลรายละเอียดคำขอและรายวิชาที่คุณขอเปิด</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="step-number mx-auto mb-3">3</div>
                                            <h6>ติดตามสถานะการอนุมัติ</h6>
                                            <p class="small text-muted">ดูสถานะล่าสุดของคำขอและขั้นตอนการดำเนินการ</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <div class="step-number mx-auto mb-3">4</div>
                                            <h6>พิมพ์เอกสารและดูตารางเรียน</h6>
                                            <p class="small text-muted">เมื่อได้รับการอนุมัติ คุณสามารถพิมพ์เอกสารและดูตารางเรียนได้</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>วิทยาลัยการอาชีพปราสาท</h5>
                    <p class="small">ระบบขอเปิดรายวิชาภาคเรียนพิเศษ</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 small">© 2025 วิทยาลัยการอาชีพปราสาท</p>
                    <p class="small">"เรียนดี มีความสุข"</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable if it exists
            if ($('#requestsTable').length) {
                $('#requestsTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
                    },
                    "responsive": true,
                    "order": [[0, "desc"]]
                });
            }
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
        
        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</body>
</html>