<?php
class Student {
    // Database connection and table name
    private $conn;
    private $table_name = "students";
    
    // Properties
    public $id;
    public $user_id;
    public $student_code;
    public $name_prefix;
    public $first_name;
    public $last_name;
    public $education_level;
    public $year;
    public $major;
    public $phone_number;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new student
    public function create() {
        // Query to insert student
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, student_code, name_prefix, first_name, last_name, education_level, year, major, phone_number, created_at, updated_at) 
                  VALUES 
                  (:user_id, :student_code, :name_prefix, :first_name, :last_name, :education_level, :year, :major, :phone_number, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->student_code = htmlspecialchars(strip_tags($this->student_code));
        $this->name_prefix = htmlspecialchars(strip_tags($this->name_prefix));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->education_level = htmlspecialchars(strip_tags($this->education_level));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->major = htmlspecialchars(strip_tags($this->major));
        $this->phone_number = htmlspecialchars(strip_tags($this->phone_number));
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':student_code', $this->student_code);
        $stmt->bindParam(':name_prefix', $this->name_prefix);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':education_level', $this->education_level);
        $stmt->bindParam(':year', $this->year);
        $stmt->bindParam(':major', $this->major);
        $stmt->bindParam(':phone_number', $this->phone_number);
        
        // Execute query
        if ($stmt->execute()) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update student
    public function update() {
        // Query to update student
        $query = "UPDATE " . $this->table_name . " 
                  SET name_prefix = :name_prefix, first_name = :first_name, last_name = :last_name, 
                  education_level = :education_level, year = :year, major = :major, 
                  phone_number = :phone_number, updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->name_prefix = htmlspecialchars(strip_tags($this->name_prefix));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->education_level = htmlspecialchars(strip_tags($this->education_level));
        $this->year = htmlspecialchars(strip_tags($this->year));
        $this->major = htmlspecialchars(strip_tags($this->major));
        $this->phone_number = htmlspecialchars(strip_tags($this->phone_number));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name_prefix', $this->name_prefix);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':education_level', $this->education_level);
        $stmt->bindParam(':year', $this->year);
        $stmt->bindParam(':major', $this->major);
        $stmt->bindParam(':phone_number', $this->phone_number);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete student
    public function delete() {
        // Query to delete student
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
    
    // Get student by ID
    public function getStudentById() {
        // Query to read single student
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch student data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set student properties
            $this->user_id = $row['user_id'];
            $this->student_code = $row['student_code'];
            $this->name_prefix = $row['name_prefix'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->education_level = $row['education_level'];
            $this->year = $row['year'];
            $this->major = $row['major'];
            $this->phone_number = $row['phone_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get student by user ID
    public function getStudentByUserId() {
        // Query to read student by user ID
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        // Bind user ID
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch student data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set student properties
            $this->id = $row['id'];
            $this->student_code = $row['student_code'];
            $this->name_prefix = $row['name_prefix'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->education_level = $row['education_level'];
            $this->year = $row['year'];
            $this->major = $row['major'];
            $this->phone_number = $row['phone_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get student by student code
    public function getStudentByStudentCode() {
        // Query to read student by student code
        $query = "SELECT * FROM " . $this->table_name . " WHERE student_code = :student_code";
        $stmt = $this->conn->prepare($query);
        
        // Bind student code
        $stmt->bindParam(':student_code', $this->student_code);
        
        // Execute query
        $stmt->execute();
        
        // Fetch student data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set student properties
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->name_prefix = $row['name_prefix'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->education_level = $row['education_level'];
            $this->year = $row['year'];
            $this->major = $row['major'];
            $this->phone_number = $row['phone_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all students
    public function getAllStudents() {
        // Query to get all students
        $query = "SELECT s.*, CONCAT(s.name_prefix, s.first_name, ' ', s.last_name) as fullname 
                  FROM " . $this->table_name . " s 
                  ORDER BY s.id ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total number of students
    public function getTotalStudents() {
        // Query to count all students
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Check if student code exists
    public function isStudentCodeExists() {
        // Query to check if student code exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE student_code = :student_code";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->student_code = htmlspecialchars(strip_tags($this->student_code));
        
        // Bind parameter
        $stmt->bindParam(':student_code', $this->student_code);
        
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