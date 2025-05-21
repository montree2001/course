<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-3">
            <img src="../assets/images/logo.png" alt="Logo" class="img-fluid" style="max-width: 60px;">
            <h6 class="text-white mt-2">ระบบขอเปิดรายวิชา</h6>
            <p class="text-white-50 small">วิทยาลัยการอาชีพปราสาท</p>
        </div>
        <hr class="text-white">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    แดชบอร์ด
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'course_requests.php' ? 'active' : ''; ?>" href="course_requests.php">
                    <i class="fas fa-file-alt me-2"></i>
                    จัดการคำขอเปิดรายวิชา
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="fas fa-user-graduate me-2"></i>
                    จัดการข้อมูลนักศึกษา
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : ''; ?>" href="teachers.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    จัดการข้อมูลครู
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>" href="courses.php">
                    <i class="fas fa-book me-2"></i>
                    จัดการข้อมูลรายวิชา
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'active' : ''; ?>" href="schedules.php">
                    <i class="fas fa-calendar-alt me-2"></i>
                    จัดการตารางเรียน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-print me-2"></i>
                    รายงาน
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    ตั้งค่าระบบ
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    ออกจากระบบ
                </a>
            </li>
        </ul>
    </div>
</nav>