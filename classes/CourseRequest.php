<?php
class CourseRequest {
    // Database connection and table names
    private $conn;
    private $table_name = "course_requests";
    private $items_table = "course_request_items";
    private $status_logs_table = "status_logs";
    private $students_table = "students";
    private $courses_table = "courses";
    private $teachers_table = "teachers";
    
    // Properties
    public $id;
    public $student_id;
    public $semester;
    public $academic_year;
    public $request_date;
    public $status;
    public $rejected_reason;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new course request
    public function create() {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Query to insert course request
            $query = "INSERT INTO " . $this->table_name . " 
                      (student_id, semester, academic_year, request_date, status, created_at, updated_at) 
                      VALUES 
                      (:student_id, :semester, :academic_year, :request_date, :status, NOW(), NOW())";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $this->student_id = htmlspecialchars(strip_tags($this->student_id));
            $this->semester = htmlspecialchars(strip_tags($this->semester));
            $this->academic_year = htmlspecialchars(strip_tags($this->academic_year));
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            // Set default request date to now if not provided
            if (empty($this->request_date)) {
                $this->request_date = date('Y-m-d');
            }
            
            // Bind parameters
            $stmt->bindParam(':student_id', $this->student_id);
            $stmt->bindParam(':semester', $this->semester);
            $stmt->bindParam(':academic_year', $this->academic_year);
            $stmt->bindParam(':request_date', $this->request_date);
            $stmt->bindParam(':status', $this->status);
            
            // Execute query
            if ($stmt->execute()) {
                // Get the last inserted ID
                $this->id = $this->conn->lastInsertId();
                
                // Log initial status
                $this->logStatusChange($this->student_id, $this->status, 'การยื่นคำขอเปิดรายวิชา');
                
                // Commit transaction
                $this->conn->commit();
                
                return true;
            }
            
            // Rollback transaction if something went wrong
            $this->conn->rollBack();
            return false;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }
    
    // Add course request item
    public function addRequestItem($course_id, $teacher_id) {
        // Query to insert course request item
        $query = "INSERT INTO " . $this->items_table . " 
                  (course_request_id, course_id, teacher_id, created_at, updated_at) 
                  VALUES 
                  (:course_request_id, :course_id, :teacher_id, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $course_id = htmlspecialchars(strip_tags($course_id));
        $teacher_id = htmlspecialchars(strip_tags($teacher_id));
        
        // Bind parameters
        $stmt->bindParam(':course_request_id', $this->id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update course request status
    public function updateStatus() {
        // Query to update course request status
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, updated_at = NOW()";
        
        // Add rejected reason if status is 'rejected'
        if ($this->status === 'rejected' && !empty($this->rejected_reason)) {
            $query .= ", rejected_reason = :rejected_reason";
        }
        
        $query .= " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':status', $this->status);
        
        // Bind rejected reason if status is 'rejected'
        if ($this->status === 'rejected' && !empty($this->rejected_reason)) {
            $this->rejected_reason = htmlspecialchars(strip_tags($this->rejected_reason));
            $stmt->bindParam(':rejected_reason', $this->rejected_reason);
        }
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Log status change
    public function logStatusChange($user_id, $status, $comment = null) {
        // Query to insert status log
        $query = "INSERT INTO " . $this->status_logs_table . " 
                  (course_request_id, status, user_id, comment, created_at) 
                  VALUES 
                  (:course_request_id, :status, :user_id, :comment, NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $user_id = htmlspecialchars(strip_tags($user_id));
        $status = htmlspecialchars(strip_tags($status));
        if (!empty($comment)) {
            $comment = htmlspecialchars(strip_tags($comment));
        }
        
        // Bind parameters
        $stmt->bindParam(':course_request_id', $this->id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':comment', $comment);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get course request by ID
    public function getRequestById() {
        // Query to read single course request with student details
        $query = "SELECT cr.*, 
                  s.student_code, 
                  s.name_prefix, 
                  s.first_name, 
                  s.last_name, 
                  CONCAT(s.name_prefix, s.first_name, ' ', s.last_name) as student_name,
                  s.education_level, 
                  s.year, 
                  s.major, 
                  s.phone_number
                  FROM " . $this->table_name . " cr
                  LEFT JOIN " . $this->students_table . " s ON cr.student_id = s.id
                  WHERE cr.id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Check if any row returned
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    // Get course request items
    public function getRequestItems() {
        // Query to get course request items with course and teacher details
        $query = "SELECT cri.*, 
                  c.course_code, 
                  c.course_name, 
                  c.theory_hours, 
                  c.practice_hours, 
                  c.credits, 
                  c.total_hours,
                  CONCAT(t.name_prefix, t.first_name, ' ', t.last_name) as teacher_name
                  FROM " . $this->items_table . " cri
                  LEFT JOIN " . $this->courses_table . " c ON cri.course_id = c.id
                  LEFT JOIN " . $this->teachers_table . " t ON cri.teacher_id = t.id
                  WHERE cri.course_request_id = :course_request_id
                  ORDER BY cri.id ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind course request ID
        $stmt->bindParam(':course_request_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get status history
    public function getStatusHistory() {
        // Query to get status logs with user details
        $query = "SELECT sl.*, 
                  CASE 
                    WHEN u.role = 'admin' THEN 'ผู้ดูแลระบบ'
                    WHEN s.id IS NOT NULL THEN CONCAT(s.name_prefix, s.first_name, ' ', s.last_name)
                    ELSE u.username
                  END as user_name
                  FROM " . $this->status_logs_table . " sl
                  LEFT JOIN users u ON sl.user_id = u.id
                  LEFT JOIN " . $this->students_table . " s ON sl.user_id = s.user_id
                  WHERE sl.course_request_id = :course_request_id
                  ORDER BY sl.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind course request ID
        $stmt->bindParam(':course_request_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all course requests
    public function getAllRequests() {
        // Query to get all course requests with student details and course count
        $query = "SELECT cr.*, 
                  s.student_code, 
                  CONCAT(s.name_prefix, s.first_name, ' ', s.last_name) as student_name,
                  (SELECT COUNT(*) FROM " . $this->items_table . " WHERE course_request_id = cr.id) as course_count
                  FROM " . $this->table_name . " cr
                  LEFT JOIN " . $this->students_table . " s ON cr.student_id = s.id
                  ORDER BY cr.id DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get course requests by student ID
    public function getRequestsByStudentId($student_id) {
        // Query to get course requests by student ID
        $query = "SELECT cr.*, 
                  (SELECT COUNT(*) FROM " . $this->items_table . " WHERE course_request_id = cr.id) as course_count
                  FROM " . $this->table_name . " cr
                  WHERE cr.student_id = :student_id
                  ORDER BY cr.id DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind student ID
        $stmt->bindParam(':student_id', $student_id);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get recent course requests
    public function getRecentRequests($limit = 5) {
        // Query to get recent course requests with student details
        $query = "SELECT cr.*, 
                  CONCAT(s.name_prefix, s.first_name, ' ', s.last_name) as student_name
                  FROM " . $this->table_name . " cr
                  LEFT JOIN " . $this->students_table . " s ON cr.student_id = s.id
                  ORDER BY cr.id DESC
                  LIMIT :limit";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind limit
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total number of course requests
    public function getTotalRequests() {
        // Query to count all course requests
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Get course requests by status
    public function getRequestsByStatus($status) {
        // Query to count course requests by status
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = :status";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind status
        $stmt->bindParam(':status', $status);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Get course request summary by status
    public function getRequestSummaryByStatus() {
        // Query to get course request count grouped by status
        $query = "SELECT 
                  status,
                  COUNT(*) as count
                  FROM " . $this->table_name . "
                  GROUP BY status
                  ORDER BY COUNT(*) DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get course request summary by course
    public function getRequestSummaryByCourse() {
        // Query to get course request count grouped by course
        $query = "SELECT 
                  c.id as course_id,
                  c.course_code,
                  c.course_name,
                  COUNT(cri.id) as total_requests,
                  SUM(CASE WHEN cr.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN cr.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                  SUM(CASE WHEN cr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                  FROM " . $this->items_table . " cri
                  JOIN " . $this->courses_table . " c ON cri.course_id = c.id
                  JOIN " . $this->table_name . " cr ON cri.course_request_id = cr.id
                  GROUP BY c.id, c.course_code, c.course_name
                  ORDER BY total_requests DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Delete course request
    public function delete() {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Delete course request items first
            $query1 = "DELETE FROM " . $this->items_table . " WHERE course_request_id = :course_request_id";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->bindParam(':course_request_id', $this->id);
            $stmt1->execute();
            
            // Delete status logs
            $query2 = "DELETE FROM " . $this->status_logs_table . " WHERE course_request_id = :course_request_id";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->bindParam(':course_request_id', $this->id);
            $stmt2->execute();
            
            // Delete course request
            $query3 = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt3 = $this->conn->prepare($query3);
            $stmt3->bindParam(':id', $this->id);
            
            if ($stmt3->execute()) {
                // Commit transaction
                $this->conn->commit();
                return true;
            }
            
            // Rollback transaction if something went wrong
            $this->conn->rollBack();
            return false;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            return false;
        }
    }
}
?>