<?php
class Teacher {
    // Database connection and table name
    private $conn;
    private $table_name = "teachers";
    
    // Properties
    public $id;
    public $name_prefix;
    public $first_name;
    public $last_name;
    public $department;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new teacher
    public function create() {
        // Query to insert teacher
        $query = "INSERT INTO " . $this->table_name . " 
                  (name_prefix, first_name, last_name, department, created_at, updated_at) 
                  VALUES 
                  (:name_prefix, :first_name, :last_name, :department, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->name_prefix = htmlspecialchars(strip_tags($this->name_prefix));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->department = htmlspecialchars(strip_tags($this->department));
        
        // Bind parameters
        $stmt->bindParam(':name_prefix', $this->name_prefix);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':department', $this->department);
        
        // Execute query
        if ($stmt->execute()) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update teacher
    public function update() {
        // Query to update teacher
        $query = "UPDATE " . $this->table_name . " 
                  SET name_prefix = :name_prefix, first_name = :first_name, 
                  last_name = :last_name, department = :department, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->name_prefix = htmlspecialchars(strip_tags($this->name_prefix));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->department = htmlspecialchars(strip_tags($this->department));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name_prefix', $this->name_prefix);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':department', $this->department);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete teacher
    public function delete() {
        // Query to delete teacher
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
    
    // Get teacher by ID
    public function getTeacherById() {
        // Query to read single teacher
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch teacher data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set teacher properties
            $this->name_prefix = $row['name_prefix'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->department = $row['department'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all teachers
    public function getAllTeachers() {
        // Query to get all teachers
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY department ASC, first_name ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get teachers by department
    public function getTeachersByDepartment($department) {
        // Query to get teachers by department
        $query = "SELECT * FROM " . $this->table_name . " WHERE department = :department ORDER BY first_name ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind department
        $stmt->bindParam(':department', $department);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search teachers
    public function searchTeachers($keywords) {
        // Query to search teachers
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE name_prefix LIKE :keywords OR first_name LIKE :keywords OR last_name LIKE :keywords OR department LIKE :keywords 
                  ORDER BY department ASC, first_name ASC";
        
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
    
    // Get all departments
    public function getAllDepartments() {
        // Query to get all distinct departments
        $query = "SELECT DISTINCT department FROM " . $this->table_name . " ORDER BY department ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        $departments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $departments[] = $row['department'];
        }
        
        return $departments;
    }
    
    // Get teacher availability
    public function getTeacherAvailability() {
        // Query to get teacher availability
        $query = "SELECT ta.* FROM teacher_availability ta 
                  WHERE ta.teacher_id = :teacher_id 
                  ORDER BY ta.day_of_week ASC, ta.start_time ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind teacher ID
        $stmt->bindParam(':teacher_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add teacher availability
    public function addAvailability($day_of_week, $start_time, $end_time) {
        // Query to insert teacher availability
        $query = "INSERT INTO teacher_availability 
                  (teacher_id, day_of_week, start_time, end_time, created_at, updated_at) 
                  VALUES 
                  (:teacher_id, :day_of_week, :start_time, :end_time, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind parameters
        $day_of_week = htmlspecialchars(strip_tags($day_of_week));
        $start_time = htmlspecialchars(strip_tags($start_time));
        $end_time = htmlspecialchars(strip_tags($end_time));
        
        $stmt->bindParam(':teacher_id', $this->id);
        $stmt->bindParam(':day_of_week', $day_of_week);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete teacher availability
    public function deleteAvailability($availability_id) {
        // Query to delete teacher availability
        $query = "DELETE FROM teacher_availability WHERE id = :id AND teacher_id = :teacher_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind parameters
        $availability_id = htmlspecialchars(strip_tags($availability_id));
        
        $stmt->bindParam(':id', $availability_id);
        $stmt->bindParam(':teacher_id', $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>