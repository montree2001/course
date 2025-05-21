<?php
// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/PDF.php';
include_once '../classes/CourseRequest.php';

// Get request ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ไม่พบรหัสคำขอเปิดรายวิชา");
}

// Create database connection
$database = new Database();
$db = $database->connect();

// Get request ID
$request_id = $_GET['id'];

// Create objects
$courseRequest = new CourseRequest($db);
$pdf = new PDF($db);

// Check if request exists
$courseRequest->id = $request_id;
$request_details = $courseRequest->getRequestById();

if (!$request_details) {
    die("ไม่พบคำขอเปิดรายวิชา");
}

// Generate PDF
$pdf->generateCourseRequestPDF($request_id);
?>