<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/PDF.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Get parameters
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '2568';
$type = isset($_GET['type']) ? $_GET['type'] : 'class';

// Create PDF object
$pdf = new PDF($db);

// Generate schedule PDF
$pdf->generateSchedulePDF($semester, $academic_year, $type);
?>