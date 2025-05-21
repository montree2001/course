<?php
// admin/index.php
// หน้าเข้าสู่ระบบสำหรับผู้ดูแลระบบ

session_start();
require_once '../config/db_connect.php';
require_once '../config/functions.php';

// ถ้ามีการ login อยู่แล้ว ให้ redirect ไปที่หน้า dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

// ถ้ามีการส่งแบบฟอร์ม login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ตรวจสอบข้อมูลว่างหรือไม่
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            // ตรวจสอบข้อมูลผู้ใช้ในฐานข้อมูล
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username AND password = MD5(:password)");
            $stmt->execute([
                'username' => $username,
                'password' => $password
            ]);
            
            $admin = $stmt->fetch();
            
            if ($admin) {
                // สร้าง session สำหรับการ login
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_username'] = $admin['username'];
                
                // redirect ไปที่หน้า dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแล - ระบบขอเปิดรายวิชา</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-form {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        
        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        
        .logo {
            max-width: 80px;
            margin-bottom: 15px;
        }
        
        .btn-login {
            font-weight: bold;
            padding: 10px 20px;
        }
        
        .login-footer {
            font-size: 0.8rem;
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        .input-group-text {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="Logo" class="logo">
            <h3 class="mb-0">วิทยาลัยการอาชีพปราสาท</h3>
            <p class="text-muted">ระบบขอเปิดรายวิชาภาคเรียนพิเศษ</p>
        </div>
        
        <div class="card login-card">
            <div class="card-header login-header bg-primary text-white">
                <h4 class="mb-0 text-center"><i class="bi bi-person-lock"></i> เข้าสู่ระบบผู้ดูแล</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="กรอกชื่อผู้ใช้" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                            <span class="input-group-text" id="togglePassword"><i class="bi bi-eye-slash-fill"></i></span>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="login-footer">
            <p class="mb-0">กลับไปยัง <a href="../index.php">หน้าหลัก</a></p>
            <p class="mb-0">© <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา วิทยาลัยการอาชีพปราสาท</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // แสดง/ซ่อนรหัสผ่าน
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const icon = $(this).find('i');
                
                // สลับประเภทของช่องรหัสผ่าน
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                }
            });
            
            // ตรวจสอบการส่งฟอร์ม
            $('#loginForm').submit(function() {
                const username = $('#username').val().trim();
                const password = $('#password').val().trim();
                
                if (username === '' || password === '') {
                    Swal.fire({
                        title: 'ข้อผิดพลาด!',
                        text: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>