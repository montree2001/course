<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Include database and necessary classes
include_once '../config/database.php';
include_once '../classes/PDF.php';

// Create database connection
$database = new Database();
$db = $database->connect();

// Create PDF object
$pdf = new PDF($db);

// Generate course summary PDF
// This will call the generateCourseSummaryPDF method in the PDF class
// which already has all the logic for generating the report
$pdf->generateCourseSummaryPDF();
?>