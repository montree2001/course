<?php
// admin/includes/sidebar.php
// เมนูด้านข้างสำหรับการนำทางในส่วนผู้ดูแลระบบ (แก้ไขแล้ว)

// ตรวจสอบว่ามีตัวแปร $currentPage หรือไม่
if (!isset($currentPage)) {
    $currentPage = '';
}

// ฟังก์ชันสำหรับหา admin path
function getAdminPath() {
    $current_path = $_SERVER['REQUEST_URI'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // ตรวจสอบว่าอยู่ในโฟลเดอร์ admin หรือไม่
    if (strpos($script_name, '/admin/') !== false) {
        // ถ้าอยู่ใน admin subfolder
        $admin_path = dirname($script_name);
        if (basename($admin_path) === 'admin') {
            return './';
        } else {
            return '../';
        }
    } else {
        return './';
    }
}

// กำหนดเส้นทางปัจจุบัน
$current_path = $_SERVER['REQUEST_URI'];
?>
<ul class="nav flex-column">
    <!-- แดชบอร์ด -->
    <li class="nav-item">
        <a href="<?php echo getAdminPath(); ?>dashboard.php" class="sidebar-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 sidebar-icon"></i>
            <span class="sidebar-text">แดชบอร์ด</span>
        </a>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">จัดการคำร้อง</div>

    <!-- จัดการคำร้องขอเปิดรายวิชา -->
    <li class="nav-item">
        <a href="<?php echo getAdminPath(); ?>requests/index.php" class="sidebar-item <?php echo ($currentPage === 'requests') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text sidebar-icon"></i>
            <span class="sidebar-text">คำร้องขอเปิดรายวิชา</span>
        </a>
    </li>

    <!-- จัดการตารางเรียน -->
    <li class="nav-item">
        <a href="javascript:void(0)" 
           class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'schedules') !== false) ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" 
           data-bs-target="#collapseSchedules" 
           aria-expanded="<?php echo (strpos($currentPage, 'schedules') !== false) ? 'true' : 'false'; ?>"
           aria-controls="collapseSchedules">
            <i class="bi bi-calendar2-week sidebar-icon"></i>
            <span class="sidebar-text">จัดการตารางเรียน</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon ms-auto"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'schedules') !== false) ? 'show' : ''; ?>" 
             id="collapseSchedules"
             data-bs-parent="#sidebar">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../schedules/index.php" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-list') ? 'active' : ''; ?>">
                        <i class="bi bi-list-ul me-2"></i>รายการตารางเรียน
                    </a>
                </li>
                <li>
                    <a href="../schedules/create.php" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-create') ? 'active' : ''; ?>">
                        <i class="bi bi-plus-circle me-2"></i>สร้างตารางเรียน
                    </a>
                </li>
                <li>
                    <a href="../schedules/teacher_availability.php" class="sidebar-submenu-item <?php echo ($currentPage === 'schedules-teacher') ? 'active' : ''; ?>">
                        <i class="bi bi-person-check me-2"></i>เวลาว่างของครู
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">จัดการข้อมูล</div>

    <!-- จัดการข้อมูลนักเรียน -->
    <li class="nav-item">
        <a href="<?php echo getAdminPath(); ?>students/index.php" class="sidebar-item <?php echo ($currentPage === 'students') ? 'active' : ''; ?>">
            <i class="bi bi-mortarboard sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลนักเรียน</span>
        </a>
    </li>

    <!-- จัดการข้อมูลรายวิชา -->
    <li class="nav-item">
        <a href="<?php echo getAdminPath(); ?>courses/index.php" class="sidebar-item <?php echo ($currentPage === 'courses') ? 'active' : ''; ?>">
            <i class="bi bi-book sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลรายวิชา</span>
        </a>
    </li>

    <!-- จัดการข้อมูลครู -->
    <li class="nav-item">
        <a href="<?php echo getAdminPath(); ?>teachers/index.php" class="sidebar-item <?php echo ($currentPage === 'teachers') ? 'active' : ''; ?>">
            <i class="bi bi-person-badge sidebar-icon"></i>
            <span class="sidebar-text">ข้อมูลครู</span>
        </a>
    </li>

    <!-- จัดการสาขาวิชา -->
    <li class="nav-item">
        <a href="javascript:void(0)" 
           class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'departments') !== false) ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" 
           data-bs-target="#collapseDepartments" 
           aria-expanded="<?php echo (strpos($currentPage, 'departments') !== false) ? 'true' : 'false'; ?>"
           aria-controls="collapseDepartments">
            <i class="bi bi-building sidebar-icon"></i>
            <span class="sidebar-text">จัดการสาขาวิชา</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon ms-auto"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'departments') !== false) ? 'show' : ''; ?>" 
             id="collapseDepartments"
             data-bs-parent="#sidebar">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../departments/index.php" class="sidebar-submenu-item <?php echo ($currentPage === 'departments-list') ? 'active' : ''; ?>">
                        <i class="bi bi-list-ul me-2"></i>รายการสาขาวิชา
                    </a>
                </li>
                <li>
                    <a href="../departments/add.php" class="sidebar-submenu-item <?php echo ($currentPage === 'departments-add') ? 'active' : ''; ?>">
                        <i class="bi bi-plus-circle me-2"></i>เพิ่มสาขาวิชา
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">รายงาน</div>

    <!-- รายงานและสถิติ -->
    <li class="nav-item">
        <a href="javascript:void(0)" 
           class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'reports') !== false) ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" 
           data-bs-target="#collapseReports" 
           aria-expanded="<?php echo (strpos($currentPage, 'reports') !== false) ? 'true' : 'false'; ?>"
           aria-controls="collapseReports">
            <i class="bi bi-file-earmark-bar-graph sidebar-icon"></i>
            <span class="sidebar-text">รายงานและสถิติ</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon ms-auto"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'reports') !== false) ? 'show' : ''; ?>" 
             id="collapseReports"
             data-bs-parent="#sidebar">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../reports/course_summary.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-course') ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart me-2"></i>สรุปการขอเปิดรายวิชา
                    </a>
                </li>
                <li>
                    <a href="../reports/student_requests.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-student') ? 'active' : ''; ?>">
                        <i class="bi bi-person-lines-fill me-2"></i>รายงานคำร้องตามนักเรียน
                    </a>
                </li>
                <li>
                    <a href="../reports/teacher_workload.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-teacher') ? 'active' : ''; ?>">
                        <i class="bi bi-person-workspace me-2"></i>ภาระงานสอนของครู
                    </a>
                </li>
                <li>
                    <a href="../reports/schedule_summary.php" class="sidebar-submenu-item <?php echo ($currentPage === 'reports-schedule') ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-event me-2"></i>สรุปตารางเรียน
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-heading">ระบบ</div>

    <!-- การตั้งค่า -->
    <li class="nav-item">
        <a href="javascript:void(0)" 
           class="sidebar-item sidebar-dropdown-toggle <?php echo (strpos($currentPage, 'settings') !== false) ? 'active' : ''; ?>" 
           data-bs-toggle="collapse" 
           data-bs-target="#collapseSettings" 
           aria-expanded="<?php echo (strpos($currentPage, 'settings') !== false) ? 'true' : 'false'; ?>"
           aria-controls="collapseSettings">
            <i class="bi bi-gear sidebar-icon"></i>
            <span class="sidebar-text">การตั้งค่า</span>
            <i class="bi bi-chevron-right sidebar-dropdown-icon ms-auto"></i>
        </a>
        <div class="collapse <?php echo (strpos($currentPage, 'settings') !== false) ? 'show' : ''; ?>" 
             id="collapseSettings"
             data-bs-parent="#sidebar">
            <ul class="sidebar-submenu">
                <li>
                    <a href="../settings/general.php" class="sidebar-submenu-item <?php echo ($currentPage === 'settings-general') ? 'active' : ''; ?>">
                        <i class="bi bi-sliders me-2"></i>ตั้งค่าทั่วไป
                    </a>
                </li>
                <li>
                    <a href="../settings/academic_year.php" class="sidebar-submenu-item <?php echo ($currentPage === 'settings-academic') ? 'active' : ''; ?>">
                        <i class="bi bi-calendar3 me-2"></i>ปีการศึกษา
                    </a>
                </li>
                <li>
                    <a href="../settings/backup.php" class="sidebar-submenu-item <?php echo ($currentPage === 'settings-backup') ? 'active' : ''; ?>">
                        <i class="bi bi-cloud-download me-2"></i>สำรองข้อมูล
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <!-- เกี่ยวกับระบบ -->
    <li class="nav-item">
        <a href="../about.php" class="sidebar-item <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
            <i class="bi bi-info-circle sidebar-icon"></i>
            <span class="sidebar-text">เกี่ยวกับระบบ</span>
        </a>
    </li>

    <!-- ออกจากระบบ -->
    <li class="nav-item">
        <a href="../logout.php" class="sidebar-item text-danger" onclick="return confirmLogout()">
            <i class="bi bi-box-arrow-right sidebar-icon"></i>
            <span class="sidebar-text">ออกจากระบบ</span>
        </a>
    </li>
</ul>

<script>
// JavaScript สำหรับการทำงานของ Sidebar
document.addEventListener('DOMContentLoaded', function() {
    // จัดการการคลิก dropdown
    const dropdownToggles = document.querySelectorAll('.sidebar-dropdown-toggle');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            const icon = this.querySelector('.sidebar-dropdown-icon');
            
            if (target) {
                // Toggle collapse
                const bsCollapse = new bootstrap.Collapse(target, {
                    toggle: true
                });
                
                // จัดการไอคอน
                target.addEventListener('show.bs.collapse', function () {
                    icon.style.transform = 'rotate(90deg)';
                });
                
                target.addEventListener('hide.bs.collapse', function () {
                    icon.style.transform = 'rotate(0deg)';
                });
            }
        });
    });
    
    // เพิ่ม event listener สำหรับการแสดง/ซ่อน submenu เมื่อ sidebar collapse
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    const dropdowns = document.querySelectorAll('.collapse.show');
                    
                    if (isCollapsed) {
                        // ซ่อน dropdown เมื่อ sidebar ย่อ
                        dropdowns.forEach(function(dropdown) {
                            bootstrap.Collapse.getInstance(dropdown)?.hide();
                        });
                    }
                }
            });
        });
        
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    // เพิ่มการจัดการ hover effect สำหรับ sidebar items
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(function(item) {
        item.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.backgroundColor = '';
            }
        });
    });
    
    // เพิ่มการจัดการ active state
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-item[href], .sidebar-submenu-item[href]');
    
    sidebarLinks.forEach(function(link) {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
            
            // ถ้าเป็น submenu item ให้เปิด parent dropdown
            if (link.classList.contains('sidebar-submenu-item')) {
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    parentCollapse.classList.add('show');
                    const parentToggle = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
                    if (parentToggle) {
                        parentToggle.classList.add('active');
                        const icon = parentToggle.querySelector('.sidebar-dropdown-icon');
                        if (icon) {
                            icon.style.transform = 'rotate(90deg)';
                        }
                    }
                }
            }
        }
    });
});

// ฟังก์ชันยืนยันการออกจากระบบ
function confirmLogout() {
    return confirm('คุณต้องการออกจากระบบหรือไม่?');
}

// ฟังก์ชันสำหรับการจัดการ tooltip (ถ้าต้องการ)
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// เรียกใช้ tooltip initialization หลังจากโหลดหน้าเสร็จ
if (typeof bootstrap !== 'undefined') {
    document.addEventListener('DOMContentLoaded', initializeTooltips);
}
</script>

<style>
/* CSS เพิ่มเติมสำหรับ Sidebar */
.sidebar-dropdown-icon {
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}

.sidebar-item.sidebar-dropdown-toggle {
    position: relative;
}

.sidebar-submenu {
    background-color: rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
    margin: 0;
    list-style: none;
}

.sidebar-submenu-item {
    display: block;
    padding: 0.6rem 1rem 0.6rem 3rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.sidebar-submenu-item:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.05);
    text-decoration: none;
}

.sidebar-submenu-item.active {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
    border-left-color: #0d6efd;
}

.sidebar-submenu-item i {
    font-size: 0.8rem;
    opacity: 0.7;
}

/* สำหรับหน้าจอมือถือ */
@media (max-width: 991.98px) {
    .sidebar.collapsed .sidebar-submenu {
        display: block !important;
    }
    
    .sidebar.collapsed .sidebar-dropdown-icon {
        display: block;
    }
}

/* Animation สำหรับ collapse */
.collapse {
    transition: height 0.35s ease;
}

.collapsing {
    transition: height 0.35s ease;
}

/* ปรับปรุง hover effect */
.sidebar-item:hover .sidebar-dropdown-icon {
    color: white;
}

.nav-item:last-child .sidebar-item {
    margin-bottom: 1rem;
}
</style>