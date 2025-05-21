<?php
class Course {
    // Database connection and table name
    private $conn;
    private $table_name = "courses";
    
    // Properties
    public $id;
    public $course_code;
    public $course_name;
    public $theory_hours;
    public $practice_hours;
    public $credits;
    public $total_hours;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new course
    public function create() {
        // Query to insert course
        $query = "INSERT INTO " . $this->table_name . " 
                  (course_code, course_name, theory_hours, practice_hours, credits, total_hours, created_at, updated_at) 
                  VALUES 
                  (:course_code, :course_name, :theory_hours, :practice_hours, :credits, :total_hours, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->course_code = htmlspecialchars(strip_tags($this->course_code));
        $this->course_name = htmlspecialchars(strip_tags($this->course_name));
        $this->theory_hours = htmlspecialchars(strip_tags($this->theory_hours));
        $this->practice_hours = htmlspecialchars(strip_tags($this->practice_hours));
        $this->credits = htmlspecialchars(strip_tags($this->credits));
        $this->total_hours = htmlspecialchars(strip_tags($this->total_hours));
        
        // Bind parameters
        $stmt->bindParam(':course_code', $this->course_code);
        $stmt->bindParam(':course_name', $this->course_name);
        $stmt->bindParam(':theory_hours', $this->theory_hours);
        $stmt->bindParam(':practice_hours', $this->practice_hours);
        $stmt->bindParam(':credits', $this->credits);
        $stmt->bindParam(':total_hours', $this->total_hours);
        
        // Execute query
        if ($stmt->execute()) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update course
    public function update() {
        // Query to update course
        $query = "UPDATE " . $this->table_name . " 
                  SET course_code = :course_code, course_name = :course_name, 
                  theory_hours = :theory_hours, practice_hours = :practice_hours, 
                  credits = :credits, total_hours = :total_hours, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->course_code = htmlspecialchars(strip_tags($this->course_code));
        $this->course_name = htmlspecialchars(strip_tags($this->course_name));
        $this->theory_hours = htmlspecialchars(strip_tags($this->theory_hours));
        $this->practice_hours = htmlspecialchars(strip_tags($this->practice_hours));
        $this->credits = htmlspecialchars(strip_tags($this->credits));
        $this->total_hours = htmlspecialchars(strip_tags($this->total_hours));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':course_code', $this->course_code);
        $stmt->bindParam(':course_name', $this->course_name);
        $stmt->bindParam(':theory_hours', $this->theory_hours);
        $stmt->bindParam(':practice_hours', $this->practice_hours);
        $stmt->bindParam(':credits', $this->credits);
        $stmt->bindParam(':total_hours', $this->total_hours);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete course
    public function delete() {
        // Query to delete course
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get course by ID
    public function getCourseById() {
        // Query to read single course
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch course data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set course properties
            $this->course_code = $row['course_code'];
            $this->course_name = $row['course_name'];
            $this->theory_hours = $row['theory_hours'];
            $this->practice_hours = $row['practice_hours'];
            $this->credits = $row['credits'];
            $this->total_hours = $row['total_hours'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get course by course code
    public function getCourseByCode() {
        // Query to read course by course code
        $query = "SELECT * FROM " . $this->table_name . " WHERE course_code = :course_code";
        $stmt = $this->conn->prepare($query);
        
        // Bind course code
        $stmt->bindParam(':course_code', $this->course_code);
        
        // Execute query
        $stmt->execute();
        
        // Fetch course data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set course properties
            $this->id = $row['id'];
            $this->course_name = $row['course_name'];
            $this->theory_hours = $row['theory_hours'];
            $this->practice_hours = $row['practice_hours'];
            $this->credits = $row['credits'];
            $this->total_hours = $row['total_hours'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all courses
    public function getAllCourses() {
        // Query to get all courses
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY course_code ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search courses
    public function searchCourses($keywords) {
        // Query to search courses
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE course_code LIKE :keywords OR course_name LIKE :keywords 
                  ORDER BY course_code ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        $stmt->bindParam(':keywords', $keywords);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total number of courses
    public function getTotalCourses() {
        // Query to count all courses
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Check if course code exists
    public function isCourseCodeExists() {
        // Query to check if course code exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE course_code = :course_code";
        
        if ($this->id) {
            $query .= " AND id != :id";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->course_code = htmlspecialchars(strip_tags($this->course_code));
        
        // Bind parameter
        $stmt->bindParam(':course_code', $this->course_code);
        
        if ($this->id) {
            $stmt->bindParam(':id', $this->id);
        }
        
        // Execute query
        $stmt->execute();
        
        // Check if any row returned
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
}
?>