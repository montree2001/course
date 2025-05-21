<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท</title>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i> หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_form.php">
                            <i class="fas fa-file-alt me-1"></i> ขอเปิดรายวิชา
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="check_status.php">
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

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Hero Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-lg border-0 mb-4">
                    <div class="card-body p-5 text-center">
                        <img src="assets/images/logo.png" alt="Logo" height="100" class="mb-3">
                        <h1 class="display-5 fw-bold text-primary">ระบบขอเปิดรายวิชาภาคเรียนพิเศษ</h1>
                        <p class="lead">วิทยาลัยการอาชีพปราสาท</p>
                        <p class="mb-4">สำหรับนักเรียน นักศึกษาที่ต้องการขอเปิดรายวิชาเพิ่ม/เรียนซ้ำ ภาคเรียนที่ 1 ปีการศึกษา 2568</p>
                        <a href="request_form.php" class="btn btn-primary btn-lg px-4 me-2">
                            <i class="fas fa-file-alt me-2"></i> ยื่นคำขอเปิดรายวิชา
                        </a>
                        <a href="check_status.php" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-search me-2"></i> ตรวจสอบสถานะคำขอ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-file-alt fa-2x p-3"></i>
                        </div>
                        <h5 class="card-title">ขอเปิดรายวิชา</h5>
                        <p class="card-text">กรอกแบบฟอร์มคำร้องขอเปิดรายวิชาภาคเรียนพิเศษออนไลน์ได้อย่างสะดวกรวดเร็ว</p>
                        <a href="request_form.php" class="btn btn-primary mt-2">เริ่มกรอกแบบฟอร์ม</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-search fa-2x p-3"></i>
                        </div>
                        <h5 class="card-title">ตรวจสอบสถานะ</h5>
                        <p class="card-text">ติดตามสถานะคำขอของคุณได้ทุกขั้นตอน ตั้งแต่เริ่มยื่นจนถึงการอนุมัติ</p>
                        <a href="check_status.php" class="btn btn-success mt-2">ตรวจสอบสถานะ</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle mb-3">
                            <i class="fas fa-calendar-alt fa-2x p-3"></i>
                        </div>
                        <h5 class="card-title">ตารางเรียน</h5>
                        <p class="card-text">ดูและพิมพ์ตารางเรียนได้เมื่อคำขอเปิดรายวิชาของคุณได้รับการอนุมัติแล้ว</p>
                        <a href="schedules.php" class="btn btn-info text-white mt-2">ดูตารางเรียน</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">ขั้นตอนการขอเปิดรายวิชา</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="steps">
                                    <div class="step d-flex">
                                        <div class="step-number bg-primary text-white">1</div>
                                        <div class="step-content ms-3">
                                            <h6>กรอกแบบฟอร์มขอเปิดรายวิชา</h6>
                                            <p class="text-muted">กรอกข้อมูลส่วนตัวและรายวิชาที่ต้องการขอเปิดให้ครบถ้วน</p>
                                        </div>
                                    </div>
                                    <div class="step d-flex">
                                        <div class="step-number bg-primary text-white">2</div>
                                        <div class="step-content ms-3">
                                            <h6>รอการอนุมัติจากครูที่ปรึกษา</h6>
                                            <p class="text-muted">เมื่อยื่นคำขอแล้ว ครูที่ปรึกษาจะเป็นผู้พิจารณาในขั้นตอนแรก</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="steps">
                                    <div class="step d-flex">
                                        <div class="step-number bg-primary text-white">3</div>
                                        <div class="step-content ms-3">
                                            <h6>ตรวจสอบสถานะคำขอ</h6>
                                            <p class="text-muted">ติดตามสถานะคำขอได้โดยใช้รหัสคำขอ หรือรหัสนักศึกษาของคุณ</p>
                                        </div>
                                    </div>
                                    <div class="step d-flex">
                                        <div class="step-number bg-primary text-white">4</div>
                                        <div class="step-content ms-3">
                                            <h6>พิมพ์คำร้องและตารางเรียน</h6>
                                            <p class="text-muted">เมื่อได้รับการอนุมัติ สามารถพิมพ์คำร้องและตารางเรียนได้</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">ติดต่อสอบถาม</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-map-marker-alt text-primary me-2"></i> ที่อยู่</h6>
                                <p>วิทยาลัยการอาชีพปราสาท<br>อำเภอปราสาท จังหวัดสุรินทร์<br>รหัสไปรษณีย์ 32140</p>
                                
                                <h6><i class="fas fa-phone text-primary me-2"></i> โทรศัพท์</h6>
                                <p>044-551-050</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-envelope text-primary me-2"></i> อีเมล</h6>
                                <p>info@prasartac.ac.th</p>
                                
                                <h6><i class="fas fa-clock text-primary me-2"></i> เวลาทำการ</h6>
                                <p>จันทร์ - ศุกร์: 08:00 - 16:30 น.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto">
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
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
        }
        
        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: bold;
        }
        
        .step {
            margin-bottom: 20px;
        }
        
        .steps {
            padding: 10px 0;
        }
    </style>
</body>
</html>