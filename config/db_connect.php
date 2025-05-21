<?php
// config/db_connect.php
// ไฟล์สำหรับเชื่อมต่อฐานข้อมูล

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = 'localhost';
$dbname = 'course_request_system';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// สร้างการเชื่อมต่อ PDO
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    
    // สร้างตัวแปร $conn เพื่อความเข้ากันได้กับโค้ดบางส่วนที่อาจใช้ mysqli
    $conn = mysqli_connect($host, $username, $password, $dbname);
    mysqli_set_charset($conn, $charset);
    
    if (!$conn) {
        throw new Exception("เชื่อมต่อ mysqli ไม่สำเร็จ");
    }
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาดและแสดงข้อความที่เป็นมิตรกับผู้ใช้
    error_log('Connection Error: ' . $e->getMessage());
    die("ขออภัย เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล");
} catch (Exception $e) {
    error_log('Connection Error: ' . $e->getMessage());
    die("ขออภัย เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล");
}