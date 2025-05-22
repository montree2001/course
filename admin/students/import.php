<?php
// admin/students/import.php
// ไฟล์สำหรับนำเข้าข้อมูลนักเรียนจากไฟล์ Excel

// เริ่ม session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../../config/db_connect.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
require_once '../check_login.php';

// ตรวจสอบว่าเป็นการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = 'การเข้าถึงไม่ถูกต้อง';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['message'] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// ตรวจสอบประเภทไฟล์
$allowedTypes = [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv'
];

$fileType = $_FILES['importFile']['type'];
if (!in_array($fileType, $allowedTypes)) {
    $_SESSION['message'] = 'กรุณาอัปโหลดไฟล์ Excel หรือ CSV เท่านั้น';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// ตรวจสอบการกำหนดค่า overwriteExisting
$overwriteExisting = isset($_POST['overwriteExisting']) && $_POST['overwriteExisting'] === 'on';

// อ่านไฟล์ Excel ด้วย SimpleXLSX หรือ PHPExcel (ต้องติดตั้ง composer package)
// ในตัวอย่างนี้ใช้ PhpSpreadsheet ซึ่งต้องติดตั้งด้วย composer
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    // อ่านไฟล์ Excel
    $spreadsheet = IOFactory::load($_FILES['importFile']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // ตรวจสอบว่ามีข้อมูลในไฟล์หรือไม่
    if (count($rows) <= 1) { // ตรวจสอบว่ามีข้อมูลนอกเหนือจากหัวตาราง
        throw new Exception('ไม่พบข้อมูลในไฟล์ Excel');
    }
    
    // ตรวจสอบคอลัมน์หัวตาราง (แถวแรก)
    $headerRow = $rows[0];
    $requiredColumns = ['รหัสนักเรียน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ระดับชั้น', 'ชั้นปีที่', 'สาขาวิชา', 'เบอร์โทรศัพท์'];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $headerRow)) {
            throw new Exception('รูปแบบไฟล์ไม่ถูกต้อง ไม่พบคอลัมน์ "' . $column . '"');
        }
    }
    
    // เริ่ม Transaction
    $pdo->beginTransaction();
    
    // สร้าง array เก็บรหัสแผนกจากชื่อแผนก
    $departmentMap = [];
    $stmt = $pdo->query("SELECT department_id, department_name FROM departments");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($departments as $department) {
        $departmentMap[$department['department_name']] = $department['department_id'];
    }
    
    // ประมวลผลข้อมูลในแต่ละแถว (ข้ามแถวหัวตาราง)
    $successCount = 0;
    $errorCount = 0;
    $updateCount = 0;
    
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // หาตำแหน่งคอลัมน์จากหัวตาราง
        $studentCodeIndex = array_search('รหัสนักเรียน', $headerRow);
        $prefixIndex = array_search('คำนำหน้า', $headerRow);
        $firstNameIndex = array_search('ชื่อ', $headerRow);
        $lastNameIndex = array_search('นามสกุล', $headerRow);
        $levelIndex = array_search('ระดับชั้น', $headerRow);
        $yearIndex = array_search('ชั้นปีที่', $headerRow);
        $departmentIndex = array_search('สาขาวิชา', $headerRow);
        $phoneIndex = array_search('เบอร์โทรศัพท์', $headerRow);
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($row[$studentCodeIndex]) || empty($row[$firstNameIndex]) || empty($row[$lastNameIndex])) {
            $errorCount++;
            continue; // ข้ามแถวที่ข้อมูลไม่ครบ
        }
        
        $studentCode = trim($row[$studentCodeIndex]);
        $prefix = trim($row[$prefixIndex] ?? 'นาย');
        $firstName = trim($row[$firstNameIndex]);
        $lastName = trim($row[$lastNameIndex]);
        $level = trim($row[$levelIndex] ?? 'ปวช.');
        $year = trim($row[$yearIndex] ?? '1');
        $departmentName = trim($row[$departmentIndex] ?? '');
        $phone = trim($row[$phoneIndex] ?? '');
        
        // หา department_id จากชื่อสาขาวิชา
        $departmentId = null;
        if (!empty($departmentName) && isset($departmentMap[$departmentName])) {
            $departmentId = $departmentMap[$departmentName];
        }
        
        // ตรวจสอบว่ามีนักเรียนคนนี้ในฐานข้อมูลหรือไม่
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_code = :student_code");
        $stmt->execute(['student_code' => $studentCode]);
        $existingStudent = $stmt->fetch();
        
        if ($existingStudent) {
            // ถ้ามีนักเรียนอยู่แล้ว และกำหนดให้แทนที่ข้อมูลเดิม
            if ($overwriteExisting) {
                $sql = "UPDATE students SET 
                        prefix = :prefix,
                        first_name = :first_name, 
                        last_name = :last_name,
                        level = :level,
                        year = :year,
                        department_id = :department_id,
                        phone = :phone
                        WHERE student_id = :student_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'prefix' => $prefix,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'level' => $level,
                    'year' => $year,
                    'department_id' => $departmentId,
                    'phone' => $phone,
                    'student_id' => $existingStudent['student_id']
                ]);
                
                $updateCount++;
            }
            // ถ้าไม่กำหนดให้แทนที่ ให้ข้ามไป
        } else {
            // เพิ่มนักเรียนใหม่
            $sql = "INSERT INTO students (student_code, prefix, first_name, last_name, level, year, department_id, phone)
                    VALUES (:student_code, :prefix, :first_name, :last_name, :level, :year, :department_id, :phone)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'student_code' => $studentCode,
                'prefix' => $prefix,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'level' => $level,
                'year' => $year,
                'department_id' => $departmentId,
                'phone' => $phone
            ]);
            
            $successCount++;
        }
    }
    
    // Commit Transaction
    $pdo->commit();
    
    // แสดงข้อความสำเร็จ
    $message = "นำเข้าข้อมูลเรียบร้อยแล้ว\n";
    $message .= "- เพิ่มข้อมูลใหม่: $successCount รายการ\n";
    
    if ($overwriteExisting) {
        $message .= "- อัปเดตข้อมูลเดิม: $updateCount รายการ\n";
    }
    
    if ($errorCount > 0) {
        $message .= "- ข้ามข้อมูลที่ไม่สมบูรณ์: $errorCount รายการ";
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['message'] = 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// กลับไปยังหน้ารายการนักเรียน
header('Location: index.php');
exit;
?>