<?php
// student/track_status.php
// หน้าติดตามสถานะคำร้องที่รองรับมือถือ

session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

$error = '';
$student = null;
$requests = [];

// ถ้ามีการค้นหา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_type = $_POST['search_type'] ?? '';
    $search_value = $_POST['search_value'] ?? '';
    
    if (empty($search_value)) {
        $error = 'กรุณากรอกข้อมูลที่ต้องการค้นหา';
    } else {
        try {
            // ค้นหาตามประเภทที่เลือก
            if ($search_type === 'student_code') {
                // ค้นหาด้วยรหัสนักเรียน
                $stmt = $pdo->prepare("
                    SELECT * FROM students WHERE student_code = :search_value
                ");
                $stmt->execute(['search_value' => $search_value]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    $error = 'ไม่พบข้อมูลนักเรียนจากรหัสที่ระบุ';
                }
            } else if ($search_type === 'name') {
                // ค้นหาด้วยชื่อ-นามสกุล
                $stmt = $pdo->prepare("
                    SELECT * FROM students 
                    WHERE CONCAT(first_name, ' ', last_name) LIKE :search_value OR CONCAT(prefix, first_name, ' ', last_name) LIKE :search_value
                ");
                $stmt->execute(['search_value' => "%$search_value%"]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    $error = 'ไม่พบข้อมูลนักเรียนจากชื่อ-นามสกุลที่ระบุ';
                }
            } else if ($search_type === 'request_id') {
                // ค้นหาด้วยรหัสคำร้อง
                $stmt = $pdo->prepare("
                    SELECT s.* FROM students s
                    JOIN course_requests cr ON s.student_id = cr.student_id
                    WHERE cr.request_id = :search_value
                ");
                $stmt->execute(['search_value' => $search_value]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    $error = 'ไม่พบข้อมูลคำร้องตามรหัสที่ระบุ';
                }
            }
            
            // ถ้าพบข้อมูลนักเรียน ให้ค้นหาคำร้องของนักเรียนนั้น
            if ($student) {
                // ค้นหาคำร้องทั้งหมดของนักเรียน
                $stmt = $pdo->prepare("
                    SELECT cr.*, 
                           COUNT(rd.detail_id) as course_count,
                           d.department_name
                    FROM course_requests cr
                    JOIN request_details rd ON cr.request_id = rd.request_id
                    JOIN students s ON cr.student_id = s.student_id
                    JOIN departments d ON s.department_id = d.department_id
                    WHERE cr.student_id = :student_id
                    GROUP BY cr.request_id
                    ORDER BY cr.request_date DESC
                ");
                $stmt->execute(['student_id' => $student['student_id']]);
                $requests = $stmt->fetchAll();
                
                // หากค้นหาด้วยรหัสคำร้อง ให้กรองเฉพาะคำร้องที่ตรงกับรหัสที่ค้นหา
                if ($search_type === 'request_id') {
                    $filtered_requests = [];
                    foreach ($requests as $request) {
                        if ($request['request_id'] == $search_value) {
                            $filtered_requests[] = $request;
                        }
                    }
                    $requests = $filtered_requests;
                }
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาดในการค้นหา: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะคำร้อง - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Mobile -->
    <link href="../assets/css/mobile.css" rel="stylesheet">
    
    <style>
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem;
        }
        
        .timeline {
            list-style-type: none;
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline:before {
            content: ' ';
            background: #dee2e6;
            display: inline-block;
            position: absolute;
            left: 0;
            width: 2px;
            height: 100%;
        }
        
        .timeline-item {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .timeline-item:before {
            content: ' ';
            background: white;
            display: inline-block;
            position: absolute;
            border-radius: 50%;
            border: 2px solid #28a745;
            left: -2.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
        }
        
        .timeline-item.active:before {
            background: #28a745;
        }
        
        .timeline-item.pending:before {
            border-color: #ffc107;
            background: white;
        }
        
        .timeline-item.rejected:before {
            border-color: #dc3545;
            background: white;
        }
        
        .timeline-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* สไตล์เพิ่มเติมสำหรับมือถือ */
        @media (max-width: 767.98px) {
            .table-responsive {
                border: 0;
            }
            
            #requestsTable th:nth-child(2), 
            #requestsTable td:nth-child(2) {
                display: none;
            }
            
            .mobile-card {
                margin-bottom: 15px;
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .mobile-card .card-body {
                padding: 15px;
            }
            
            .mobile-card .status-badge {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            
            .mobile-card .mobile-info {
                margin-bottom: 5px;
                font-size: 0.9rem;
            }
            
            .mobile-card .mobile-title {
                font-weight: bold;
                font-size: 1.1rem;
                margin-bottom: 8px;
            }
            
            .mobile-card .mobile-buttons {
                margin-top: 10px;
                display: flex;
                gap: 5px;
            }
            
            .search-group {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                ระบบขอเปิดรายวิชา
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="bi bi-house-door"></i> หน้าหลัก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_form.php"><i class="bi bi-file-earmark-plus"></i> ยื่นคำร้อง</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="track_status.php"><i class="bi bi-search"></i> ตรวจสอบสถานะ</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-search"></i> ติดตามสถานะคำร้องขอเปิดรายวิชา</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <form method="post" class="row g-3">
                                    <div class="col-md-4 col-12 search-group">
                                        <label for="search_type" class="form-label">ค้นหาด้วย</label>
                                        <select class="form-select" id="search_type" name="search_type" required>
                                            <option value="student_code" <?php echo (isset($_POST['search_type']) && $_POST['search_type'] === 'student_code') ? 'selected' : ''; ?>>รหัสนักเรียน</option>
                                            <option value="name" <?php echo (isset($_POST['search_type']) && $_POST['search_type'] === 'name') ? 'selected' : ''; ?>>ชื่อ-นามสกุล</option>
                                            <option value="request_id" <?php echo (isset($_POST['search_type']) && $_POST['search_type'] === 'request_id') ? 'selected' : ''; ?>>รหัสคำร้อง</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 col-12 search-group">
                                        <label for="search_value" class="form-label">คำค้นหา</label>
                                        <input type="text" class="form-control" id="search_value" name="search_value" value="<?php echo $_POST['search_value'] ?? ''; ?>" placeholder="ระบุข้อมูลที่ต้องการค้นหา" required>
                                    </div>
                                    
                                    <div class="col-md-2 col-12 search-group">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-search"></i> ค้นหา
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($student): ?>
                            <div class="alert alert-info">
                                <h5 class="d-flex align-items-center"><i class="bi bi-person-circle me-2"></i> ข้อมูลนักเรียน</h5>
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <p class="mb-md-0 mb-1"><strong>รหัสนักเรียน:</strong> <?php echo $student['student_code']; ?></p>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <p class="mb-md-0 mb-1"><strong>ชื่อ-นามสกุล:</strong> <?php echo $student['prefix'] . $student['first_name'] . ' ' . $student['last_name']; ?></p>
                                    </div>
                                    <div class="col-12">
                                        <p class="mb-0"><strong>ระดับชั้น:</strong> <?php echo $student['level'] . ' ปีที่ ' . $student['year']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (empty($requests)): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> ไม่พบประวัติการยื่นคำร้องขอเปิดรายวิชา
                                </div>
                            <?php else: ?>
                                <h5 class="card-title mb-3">ประวัติการยื่นคำร้องขอเปิดรายวิชา</h5>
                                
                                <!-- สำหรับหน้าจอขนาดใหญ่ (PC) -->
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-bordered table-hover" id="requestsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>รหัสคำร้อง</th>
                                                <th>วันที่ยื่นคำร้อง</th>
                                                <th>ภาคเรียน/ปีการศึกษา</th>
                                                <th>จำนวนรายวิชา</th>
                                                <th>สถานะ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td><?php echo $request['request_id']; ?></td>
                                                    <td><?php echo dateThaiFormat($request['request_date']); ?></td>
                                                    <td>
                                                        <?php 
                                                            echo 'ภาคเรียนที่ ' . $request['semester'] . '/' . $request['academic_year'];
                                                        ?>
                                                    </td>
                                                    <td class="text-center"><?php echo $request['course_count']; ?></td>
                                                    <td>
                                                        <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                                                            <span class="badge bg-warning status-badge">รอดำเนินการ</span>
                                                        <?php elseif ($request['status'] === 'อนุมัติ'): ?>
                                                            <span class="badge bg-success status-badge">อนุมัติแล้ว</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger status-badge">ไม่อนุมัติ</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-info view-details" data-id="<?php echo $request['request_id']; ?>">
                                                            <i class="bi bi-info-circle"></i> รายละเอียด
                                                        </button>
                                                        
                                                        <?php if ($request['status'] === 'อนุมัติ'): ?>
                                                            <a href="download_schedule.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-success">
                                                                <i class="bi bi-download"></i> ตารางเรียน
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- สำหรับหน้าจอขนาดเล็ก (มือถือ) -->
                                <div class="d-md-none">
                                    <?php foreach ($requests as $request): ?>
                                        <div class="card mobile-card">
                                            <div class="card-body">
                                                <?php if ($request['status'] === 'รอดำเนินการ'): ?>
                                                    <span class="badge bg-warning status-badge">รอดำเนินการ</span>
                                                <?php elseif ($request['status'] === 'อนุมัติ'): ?>
                                                    <span class="badge bg-success status-badge">อนุมัติแล้ว</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger status-badge">ไม่อนุมัติ</span>
                                                <?php endif; ?>
                                                
                                                <div class="mobile-title">
                                                    รหัสคำร้อง: <?php echo $request['request_id']; ?>
                                                </div>
                                                
                                                <div class="mobile-info">
                                                    <i class="bi bi-calendar3"></i> <?php echo dateThaiFormat($request['request_date']); ?>
                                                </div>
                                                
                                                <div class="mobile-info">
                                                    <i class="bi bi-mortarboard"></i> ภาคเรียนที่ <?php echo $request['semester'] . '/' . $request['academic_year']; ?>
                                                </div>
                                                
                                                <div class="mobile-info">
                                                    <i class="bi bi-book"></i> จำนวน <?php echo $request['course_count']; ?> รายวิชา
                                                </div>
                                                
                                                <div class="mobile-buttons">
                                                    <button type="button" class="btn btn-sm btn-info view-details w-100" data-id="<?php echo $request['request_id']; ?>">
                                                        <i class="bi bi-info-circle"></i> รายละเอียด
                                                    </button>
                                                    
                                                    <?php if ($request['status'] === 'อนุมัติ'): ?>
                                                        <a href="download_schedule.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-success w-100">
                                                            <i class="bi bi-download"></i> ตารางเรียน
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <i class="bi bi-search" style="font-size: 4rem; color: #6c757d;"></i>
                                <h4 class="mt-3">ค้นหาคำร้องของคุณ</h4>
                                <p class="text-muted">กรอกข้อมูลและกดปุ่มค้นหาเพื่อตรวจสอบสถานะคำร้อง</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดคำร้อง -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detailsModalLabel">รายละเอียดคำร้องขอเปิดรายวิชา</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 col-12 mb-3">
                            <h6>ข้อมูลคำร้อง</h6>
                            <ul class="list-group mb-md-4">
                                <li class="list-group-item"><strong>รหัสคำร้อง:</strong> <span id="request-id"></span></li>
                                <li class="list-group-item"><strong>วันที่ยื่นคำร้อง:</strong> <span id="request-date"></span></li>
                                <li class="list-group-item"><strong>ภาคเรียน/ปีการศึกษา:</strong> <span id="semester-year"></span></li>
                                <li class="list-group-item"><strong>สถานะ:</strong> <span id="request-status"></span></li>
                            </ul>
                        </div>
                        <div class="col-md-6 col-12 mb-3">
                            <h6>ข้อมูลนักเรียน</h6>
                            <ul class="list-group mb-md-4">
                                <li class="list-group-item"><strong>รหัสนักเรียน:</strong> <span id="student-code"></span></li>
                                <li class="list-group-item"><strong>ชื่อ-นามสกุล:</strong> <span id="student-name"></span></li>
                                <li class="list-group-item"><strong>ระดับชั้น:</strong> <span id="student-level"></span></li>
                                <li class="list-group-item"><strong>สาขาวิชา:</strong> <span id="student-department"></span></li>
                            </ul>
                        </div>
                    </div>
                    
                    <h6>รายวิชาที่ขอเปิด</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered" id="courses-table">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสวิชา</th>
                                    <th>ชื่อรายวิชา</th>
                                    <th>ทฤษฎี/ปฏิบัติ/หน่วยกิต</th>
                                    <th>ครูประจำวิชา</th>
                                </tr>
                            </thead>
                            <tbody id="courses-list">
                                <!-- จะถูกเติมด้วย JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <h6>ติดตามสถานะ</h6>
                    <ul class="timeline" id="status-timeline">
                        <!-- จะถูกเติมด้วย JavaScript -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <a href="#" id="download-pdf" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> ดาวน์โหลด PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable เฉพาะบนจอ PC
            if ($(window).width() > 767) {
                $('#requestsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
                    },
                    responsive: true,
                    order: [[0, 'desc']], // เรียงตามรหัสคำร้อง
                    columnDefs: [
                        { orderable: false, targets: 5 } // คอลัมน์การดำเนินการไม่ต้องเรียงลำดับ
                    ]
                });
            }
            
            // แสดงรายละเอียดคำร้อง
            $('.view-details').click(function() {
                const requestId = $(this).data('id');
                
                // แสดง loading
                Swal.fire({
                    title: 'กำลังโหลดข้อมูล',
                    text: 'กรุณารอสักครู่...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // ดึงข้อมูลรายละเอียดคำร้อง
                $.ajax({
                    url: '../api/request_details.php',
                    type: 'GET',
                    data: { id: requestId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            const data = response.data;
                            
                            // เติมข้อมูลคำร้อง
                            $('#request-id').text(data.request.request_id);
                            $('#request-date').text(data.request.request_date_thai);
                            $('#semester-year').text('ภาคเรียนที่ ' + data.request.semester + '/' + data.request.academic_year);
                            
                            // แสดงสถานะ
                            let statusBadge = '';
                            if (data.request.status === 'รอดำเนินการ') {
                                statusBadge = '<span class="badge bg-warning">รอดำเนินการ</span>';
                            } else if (data.request.status === 'อนุมัติ') {
                                statusBadge = '<span class="badge bg-success">อนุมัติแล้ว</span>';
                            } else {
                                statusBadge = '<span class="badge bg-danger">ไม่อนุมัติ</span>';
                            }
                            $('#request-status').html(statusBadge);
                            
                            // เติมข้อมูลนักเรียน
                            $('#student-code').text(data.student.student_code);
                            $('#student-name').text(data.student.prefix + data.student.first_name + ' ' + data.student.last_name);
                            $('#student-level').text(data.student.level + ' ปีที่ ' + data.student.year);
                            $('#student-department').text(data.student.department_name);
                            
                            // เติมข้อมูลรายวิชา
                            $('#courses-list').empty();
                            data.courses.forEach(function(course) {
                                $('#courses-list').append(`
                                    <tr>
                                        <td>${course.course_code}</td>
                                        <td>${course.course_name}</td>
                                        <td class="text-center">${course.theory_hours}/${course.practice_hours}/${course.credit_hours}</td>
                                        <td>${course.teacher_prefix}${course.teacher_first_name} ${course.teacher_last_name}</td>
                                    </tr>
                                `);
                            });
                            
                            // เติมข้อมูลการติดตามสถานะ
                            $('#status-timeline').empty();
                            data.tracking.forEach(function(track, index) {
                                let timelineClass = 'timeline-item';
                                
                                // ตรวจสอบสถานะปัจจุบัน
                                if (track.status.includes('อนุมัติเรียบร้อยแล้ว')) {
                                    timelineClass += ' active';
                                } else if (track.status.includes('ไม่ได้รับการอนุมัติ')) {
                                    timelineClass += ' rejected';
                                } else if (index === data.tracking.length - 1) {
                                    timelineClass += ' pending';
                                }
                                
                                $('#status-timeline').append(`
                                    <li class="${timelineClass}">
                                        <div>
                                            <strong>${track.status}</strong>
                                            <p class="mb-0">${track.comment || 'ไม่มีข้อความเพิ่มเติม'}</p>
                                            <span class="timeline-date">${track.created_at}</span>
                                        </div>
                                    </li>
                                `);
                            });
                            
                            // อัปเดตลิงก์ดาวน์โหลด PDF
                            $('#download-pdf').attr('href', '../api/print_request.php?id=' + data.request.request_id);
                            
                            // แสดง Modal
                            $('#detailsModal').modal('show');
                        } else {
                            Swal.fire({
                                title: 'ข้อผิดพลาด!',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            title: 'ข้อผิดพลาด!',
                            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                            icon: 'error'
                        });
                    }
                });
            });
            
            // อนิเมชั่นการแสดง Modal
            $('#detailsModal').on('show.bs.modal', function() {
                $('.modal-dialog').css('opacity', 0);
                setTimeout(function() {
                    $('.modal-dialog').css({
                        'opacity': 1,
                        'transition': 'opacity 0.3s ease'
                    });
                }, 10);
            });
            
            // ปรับแต่งการค้นหาให้เข้ากับมือถือ
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                // ปรับฟอร์มค้นหาให้ใช้งานง่ายบนมือถือ
                $('#search_value').on('focus', function() {
                    $('html, body').animate({
                        scrollTop: $(this).offset().top - 100
                    }, 200);
                });
                
                // เพิ่มปุ่มล้างข้อมูลค้นหา
                $('#search_value').after('<button type="button" id="btnClearSearch" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" style="z-index: 5;"><i class="bi bi-x-circle"></i></button>');
                
                $('#btnClearSearch').click(function() {
                    $('#search_value').val('').focus();
                });
            }
        });
    </script>
</body>
</html>