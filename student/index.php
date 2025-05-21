<?php
// student/index.php
// หน้าหลักสำหรับนักเรียน

session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// ดึงข้อมูลสถิติการขอเปิดรายวิชา
try {
    // จำนวนคำร้องทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_requests");
    $total_requests = $stmt->fetch()['total'];
    
    // จำนวนคำร้องที่อนุมัติแล้ว
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM course_requests WHERE status = 'อนุมัติ'");
    $approved_requests = $stmt->fetch()['approved'];
    
    // จำนวนคำร้องที่รอดำเนินการ
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM course_requests WHERE status = 'รอดำเนินการ'");
    $pending_requests = $stmt->fetch()['pending'];
    
    // จำนวนคำร้องที่ไม่อนุมัติ
    $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM course_requests WHERE status = 'ไม่อนุมัติ'");
    $rejected_requests = $stmt->fetch()['rejected'];
    
    // รายวิชาที่มีการขอเปิดมากที่สุด 5 อันดับแรก
    $stmt = $pdo->query("
        SELECT c.course_code, c.course_name, COUNT(rd.course_id) as request_count
        FROM request_details rd
        JOIN courses c ON rd.course_id = c.course_id
        GROUP BY rd.course_id
        ORDER BY request_count DESC
        LIMIT 5
    ");
    $top_courses = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .feature-card {
            transition: transform 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .stats-card {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .hero-section {
            background-color: #f8f9fa;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 767.98px) {
            .hero-section {
                text-align: center;
            }
            
            .hero-image {
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_form.php">ยื่นคำร้อง</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="track_status.php">ตรวจสอบสถานะ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-5 fw-bold mb-3">ระบบขอเปิดรายวิชาภาคเรียนพิเศษ</h1>
                    <p class="lead mb-4">สำหรับนักเรียน นักศึกษาที่ต้องการเรียนเพิ่มหรือเรียนซ้ำในภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="request_form.php" class="btn btn-primary btn-lg me-md-2">
                            <i class="bi bi-file-earmark-plus"></i> ยื่นคำร้อง
                        </a>
                        <a href="track_status.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-search"></i> ตรวจสอบสถานะ
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="../assets/images/study.svg" alt="ยื่นคำร้องขอเปิดรายวิชา" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </section>

    <div class="container py-4">
        <!-- ขั้นตอนการยื่นคำร้อง -->
        <section class="mb-5">
            <h2 class="text-center mb-4">ขั้นตอนการยื่นคำร้อง</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <h5 class="card-title">1. กรอกข้อมูล</h5>
                            <p class="card-text">กรอกข้อมูลส่วนตัวและรายวิชาที่ต้องการขอเปิด</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h5 class="card-title">2. ยื่นคำร้อง</h5>
                            <p class="card-text">ส่งคำร้องเข้าสู่ระบบเพื่อรอการพิจารณา</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <h5 class="card-title">3. รอการอนุมัติ</h5>
                            <p class="card-text">ติดตามสถานะคำร้องผ่านระบบตรวจสอบสถานะ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-calendar2-week"></i>
                            </div>
                            <h5 class="card-title">4. รับตารางเรียน</h5>
                            <p class="card-text">ดาวน์โหลดตารางเรียนหลังจากได้รับการอนุมัติ</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- สถิติการยื่นคำร้อง -->
        <section class="mb-5">
            <h2 class="text-center mb-4">สถิติการยื่นคำร้อง</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary text-white shadow">
                        <div class="card-body text-center">
                            <h5 class="card-title">คำร้องทั้งหมด</h5>
                            <h2 class="display-4"><?php echo $total_requests; ?></h2>
                            <p class="card-text">รายการ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white shadow">
                        <div class="card-body text-center">
                            <h5 class="card-title">อนุมัติแล้ว</h5>
                            <h2 class="display-4"><?php echo $approved_requests; ?></h2>
                            <p class="card-text">รายการ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning text-dark shadow">
                        <div class="card-body text-center">
                            <h5 class="card-title">รอดำเนินการ</h5>
                            <h2 class="display-4"><?php echo $pending_requests; ?></h2>
                            <p class="card-text">รายการ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-danger text-white shadow">
                        <div class="card-body text-center">
                            <h5 class="card-title">ไม่อนุมัติ</h5>
                            <h2 class="display-4"><?php echo $rejected_requests; ?></h2>
                            <p class="card-text">รายการ</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- รายวิชาที่มีการขอเปิดมากที่สุด -->
        <section class="mb-5">
            <h2 class="text-center mb-4">รายวิชายอดนิยม</h2>
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">รายวิชาที่มีการขอเปิดมากที่สุด 5 อันดับแรก</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อรายวิชา</th>
                                    <th>จำนวนคำร้อง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_courses)): ?>
                                    <?php foreach ($top_courses as $index => $course): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo $course['course_code']; ?></td>
                                            <td><?php echo $course['course_name']; ?></td>
                                            <td><?php echo $course['request_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">ยังไม่มีข้อมูล</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- ประกาศและข่าวสาร -->
        <section class="mb-5">
            <h2 class="text-center mb-4">ประกาศและข่าวสาร</h2>
            <div class="card shadow">
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <h5><i class="bi bi-info-circle"></i> เปิดให้ลงทะเบียนเรียนภาคเรียนพิเศษ</h5>
                        <p class="mb-0">
                            วิทยาลัยการอาชีพปราสาทเปิดให้นักเรียนนักศึกษาลงทะเบียนเรียนภาคเรียนพิเศษ ภาคเรียนที่ 1 ปีการศึกษา 2568
                            ตั้งแต่วันที่ 1 พฤษภาคม - 30 มิถุนายน 2568
                        </p>
                    </div>
                    
                    <div class="alert alert-warning mb-3">
                        <h5><i class="bi bi-exclamation-triangle"></i> คำเตือนสำหรับนักเรียนที่ต้องเรียนซ้ำ</h5>
                        <p class="mb-0">
                            นักเรียนนักศึกษาที่มีผลการเรียนไม่ผ่านและต้องเรียนซ้ำรายวิชาใด ให้รีบดำเนินการยื่นคำร้องโดยเร็ว
                            เพื่อให้ทันการจัดตารางเรียนในภาคเรียนพิเศษ
                        </p>
                    </div>
                    
                    <div class="alert alert-success mb-0">
                        <h5><i class="bi bi-check2-circle"></i> การเปิดรายวิชาเพิ่มเติม</h5>
                        <p class="mb-0">
                            หากมีนักเรียนนักศึกษายื่นคำร้องขอเปิดรายวิชาเดียวกันจำนวนมากพอ ทางวิทยาลัยจะพิจารณาเปิดการเรียนการสอนเป็นชั้นเรียนปกติ
                            แทนการเรียนแบบรายบุคคล
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>วิทยาลัยการอาชีพปราสาท</h5>
                    <p>
                        เลขที่ 62 หมู่ 7 ตำบลกังแอน อำเภอปราสาท จังหวัดสุรินทร์ 32140<br>
                        โทรศัพท์: 044-551-161<br>
                        อีเมล: prasat@vec.mail.go.th
                    </p>
                </div>
                <div class="col-md-3">
                    <h5>ลิงก์ด่วน</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white">หน้าหลัก</a></li>
                        <li><a href="request_form.php" class="text-white">ยื่นคำร้อง</a></li>
                        <li><a href="track_status.php" class="text-white">ตรวจสอบสถานะ</a></li>
                        <li><a href="../admin/index.php" class="text-white">สำหรับผู้ดูแลระบบ</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>ติดต่อเรา</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white"><i class="bi bi-facebook"></i> Facebook</a></li>
                        <li><a href="#" class="text-white"><i class="bi bi-globe"></i> เว็บไซต์</a></li>
                        <li><a href="#" class="text-white"><i class="bi bi-telephone"></i> ติดต่อฝ่ายทะเบียน</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">© <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>