<?php
// admin/includes/sidebar.php
// เมนูด้านข้างสำหรับการนำทางในส่วนผู้ดูแลระบบ

// ตรวจสอบว่ามีตัวแปร $currentPage หรือไม่
if (!isset($currentPage)) {
    $currentPage = '';
}
?>
<ul class="nav flex-column">
    <!-- แดชบอร์ด -->
    <li class="nav-item">
        <a href="../dashboard.php" class="sidebar-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 sidebar-icon"></i>
            <span class="sidebar-text">แดชบอร์ด</span>
        </a>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">จัดการคำร้อง</div>

    <!-- จัดการคำร้องขอเปิดรายวิชา -->
    <li class="nav-item">
        <a href="../requests/index.php" class="sidebar-item <?php echo ($currentPage === 'requests') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text sidebar-icon"></i>
            <span class="sidebar-text">คำร้องขอเปิดรายวิชา</span>
        </a>
    </li>

    <!-- จัดการตารางเรียน -->
    <li class="nav-item">
        <a href="#collapseSchedules" class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'schedules') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" aria-expanded="<?php echo (strpos($currentPage, 'schedules') !== false) ? 'true' : 'false'; ?>">
            <i class="bi bi-calendar2-week sidebar-icon"></i>
            <span class="sidebar-text">จัดการตารางเรียน</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'schedules') !== false) ? 'show' : ''; ?>" id="collapseSchedules">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../schedules/" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-list') ? 'active' : ''; ?>">
                        รายการตารางเรียน
                    </a>
                </li>
                <li>
                    <a href="../schedules/create.php" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-create') ? 'active' : ''; ?>">
                        สร้างตารางเรียน
                    </a>
                </li>
                <li>
                    <a href="../schedules/teacher_availability.php" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-teacher') ? 'active' : ''; ?>">
                        เวลาว่างของครู
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">จัดการข้อมูล</div>

    <!-- จัดการข้อมูลนักเรียน -->
    <li class="nav-item">
        <a href="../students/index.php" class="sidebar-item <?php echo ($currentPage === 'students') ? 'active' : ''; ?>">
            <i class="bi bi-mortarboard sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลนักเรียน</span>
        </a>
    </li>

    <!-- จัดการข้อมูลรายวิชา -->
    <li class="nav-item">
        <a href="../courses/index.php" class="sidebar-item <?php echo ($currentPage === 'courses') ? 'active' : ''; ?>">
            <i class="bi bi-book sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลรายวิชา</span>
        </a>
    </li>

    <!-- จัดการข้อมูลครู -->
    <li class="nav-item">
        <a href="../teachers/index.php" class="sidebar-item <?php echo ($currentPage === 'teachers') ? 'active' : ''; ?>">
            <i class="bi bi-person-badge sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลครู</span>
        </a>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">รายงาน</div>

    <!-- รายงานและสถิติ -->
    <li class="nav-item">
        <a href="#collapseReports" class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'reports') !== false) ? 'active' : ''; ?>" data-bs-toggle="collapse" aria-expanded="<?php echo (strpos($currentPage, 'reports') !== false) ? 'true' : 'false'; ?>">
            <i class="bi bi-file-earmark-bar-graph sidebar-icon"></i>
            <span class="sidebar-text">รายงานและสถิติ</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'reports') !== false) ? 'show' : ''; ?>" id="collapseReports">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../reports/course_summary.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-course') ? 'active' : ''; ?>">
                        สรุปการขอเปิดรายวิชา
                    </a>
                </li>
                <li>
                    <a href="../reports/student_requests.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-student') ? 'active' : ''; ?>">
                        รายงานคำร้องตามนักเรียน
                    </a>
                </li>
            </ul>
        </div>
    </li>
</ul>