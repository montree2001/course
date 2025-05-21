<?php
// admin/dashboard.php
// หน้าแดชบอร์ดหลักแสดงสถิติรวม

// กำหนดค่าตัวแปรที่ใช้ในหน้านี้
$pageTitle = 'แดชบอร์ด';
$currentPage = 'dashboard';
/* แสดง Error */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// เรียกใช้ไฟล์ header.php
require_once 'includes/header.php';

// ดึงข้อมูลสถิติต่างๆ จากฐานข้อมูล
try {
    // จำนวนคำร้องทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_requests");
    $total_requests = $stmt->fetch()['total'];
    
    // จำนวนคำร้องที่อนุมัติแล้ว
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_requests WHERE status = 'อนุมัติ'");
    $approved_requests = $stmt->fetch()['total'];
    
    // จำนวนคำร้องที่รอดำเนินการ
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_requests WHERE status = 'รอดำเนินการ'");
    $pending_requests = $stmt->fetch()['total'];
    
    // จำนวนคำร้องที่ไม่อนุมัติ
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM course_requests WHERE status = 'ไม่อนุมัติ'");
    $rejected_requests = $stmt->fetch()['total'];
    
    // จำนวนนักเรียนทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $total_students = $stmt->fetch()['total'];
    
    // จำนวนรายวิชาทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt->fetch()['total'];
    
    // จำนวนครูทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM teachers");
    $total_teachers = $stmt->fetch()['total'];
    
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
    
    // คำร้องล่าสุด 5 รายการ
    $stmt = $pdo->query("
        SELECT cr.request_id, cr.request_date, cr.status, 
               s.student_code, s.prefix, s.first_name, s.last_name,
               COUNT(rd.detail_id) as course_count
        FROM course_requests cr
        JOIN students s ON cr.student_id = s.student_id
        JOIN request_details rd ON cr.request_id = rd.request_id
        GROUP BY cr.request_id
        ORDER BY cr.request_date DESC
        LIMIT 5
    ");
    $latest_requests = $stmt->fetchAll();
    
    // ข้อมูลสำหรับกราฟแสดงจำนวนคำร้องตามเดือน
    $current_year = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT MONTH(request_date) as month, COUNT(*) as request_count 
        FROM course_requests 
        WHERE YEAR(request_date) = ?
        GROUP BY MONTH(request_date)
        ORDER BY MONTH(request_date)
    ");
    $stmt->execute([$current_year]);
    $monthly_requests = $stmt->fetchAll();
    
    // สร้างข้อมูลสำหรับกราฟเป็น array
    $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $monthly_data = array_fill(0, 12, 0);
    
    foreach ($monthly_requests as $request) {
        $month_index = $request['month'] - 1;
        $monthly_data[$month_index] = (int) $request['request_count'];
    }
    
    // ข้อมูลสำหรับกราฟวงกลมแสดงสัดส่วนสถานะคำร้อง
    $pie_chart_data = [
        ['name' => 'อนุมัติแล้ว', 'value' => $approved_requests, 'color' => '#28a745'],
        ['name' => 'รอดำเนินการ', 'value' => $pending_requests, 'color' => '#ffc107'],
        ['name' => 'ไม่อนุมัติ', 'value' => $rejected_requests, 'color' => '#dc3545']
    ];
    
} catch (PDOException $e) {
    // แสดงข้อความข้อผิดพลาด
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage() . '</div>';
}
?>

<!-- Page heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">แดชบอร์ด</h1>
    <div>
        <button class="btn btn-primary" id="refreshDashboard">
            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
        </button>
    </div>
</div>

<!-- Cards -->
<div class="row">
    <!-- Total Requests Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">คำร้องทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_requests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-text fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Requests Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">คำร้องที่อนุมัติแล้ว</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved_requests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">คำร้องที่รอดำเนินการ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_requests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass-split fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejected Requests Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">คำร้องที่ไม่อนุมัติ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejected_requests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-x-circle fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Monthly Requests Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">จำนวนคำร้องรายเดือน</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlyRequestsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie Chart (Request Status) -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">สัดส่วนสถานะคำร้อง</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="requestStatusChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="bi bi-circle-fill text-success"></i> อนุมัติแล้ว
                    </span>
                    <span class="mr-2">
                        <i class="bi bi-circle-fill text-warning"></i> รอดำเนินการ
                    </span>
                    <span class="mr-2">
                        <i class="bi bi-circle-fill text-danger"></i> ไม่อนุมัติ
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Latest Requests and Top Courses Row -->
<div class="row">
    <!-- Latest Requests -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">คำร้องล่าสุด</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">ตัวเลือก:</div>
                        <a class="dropdown-item" href="requests/index.php">ดูทั้งหมด</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>รหัสคำร้อง</th>
                                <th>นักเรียน</th>
                                <th>วันที่ยื่น</th>
                                <th>จำนวนวิชา</th>
                                <th>สถานะ</th>
                                <th>ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($latest_requests)): ?>
                                <?php foreach ($latest_requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['request_id']; ?></td>
                                        <td><?php echo $request['prefix'] . $request['first_name'] . ' ' . $request['last_name'] . ' (' . $request['student_code'] . ')'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                        <td><?php echo $request['course_count']; ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'อนุมัติ'): ?>
                                                <span class="badge bg-success">อนุมัติแล้ว</span>
                                            <?php elseif ($request['status'] === 'รอดำเนินการ'): ?>
                                                <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ไม่อนุมัติ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="requests/view.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> ดู
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">ไม่พบข้อมูลคำร้อง</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Courses -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">รายวิชายอดนิยม</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink2">
                        <div class="dropdown-header">ตัวเลือก:</div>
                        <a class="dropdown-item" href="reports/course_summary.php">ดูรายงานเต็ม</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($top_courses)): ?>
                    <?php foreach ($top_courses as $course): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?php echo $course['course_code'] . ' - ' . $course['course_name']; ?></span>
                                <span class="badge bg-primary"><?php echo $course['request_count']; ?> คำร้อง</span>
                            </div>
                            <div class="progress">
                                <?php
                                    // คำนวณเปอร์เซ็นต์
                                    $percentage = ($total_requests > 0) ? round(($course['request_count'] / $total_requests) * 100) : 0;
                                ?>
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $percentage; ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">ยังไม่มีข้อมูลรายวิชา</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- General Stats Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ข้อมูลทั่วไป</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="bi bi-people text-info me-2"></i> จำนวนนักเรียนทั้งหมด</span>
                    <span class="badge bg-info"><?php echo $total_students; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="bi bi-book text-success me-2"></i> จำนวนรายวิชาทั้งหมด</span>
                    <span class="badge bg-success"><?php echo $total_courses; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-badge text-warning me-2"></i> จำนวนครูทั้งหมด</span>
                    <span class="badge bg-warning"><?php echo $total_teachers; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เรียกใช้ Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
    // ข้อมูลสำหรับกราฟแท่งรายเดือน
    const months = <?php echo json_encode($months); ?>;
    const monthlyData = <?php echo json_encode($monthly_data); ?>;
    
    // ข้อมูลสำหรับกราฟวงกลม
    const pieChartData = <?php echo json_encode($pie_chart_data); ?>;
    
    // เมื่อ DOM โหลดเสร็จ
    document.addEventListener('DOMContentLoaded', function() {
        // สร้างกราฟแท่งรายเดือน
        const monthlyCtx = document.getElementById('monthlyRequestsChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'จำนวนคำร้อง',
                    data: monthlyData,
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // สร้างกราฟวงกลมสถานะคำร้อง
        const pieCtx = document.getElementById('requestStatusChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: pieChartData.map(item => item.name),
                datasets: [{
                    data: pieChartData.map(item => item.value),
                    backgroundColor: pieChartData.map(item => item.color),
                    hoverBackgroundColor: pieChartData.map(item => item.color),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%',
                elements: {
                    arc: {
                        borderWidth: 0
                    }
                }
            }
        });
        
        // รีเฟรชข้อมูล
        document.getElementById('refreshDashboard').addEventListener('click', function() {
            window.location.reload();
        });
    });
</script>

<?php
// เรียกใช้ไฟล์ footer.php
require_once 'includes/footer.php';
?>