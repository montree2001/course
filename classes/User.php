<?php
class User {
    // Database connection and table name
    private $conn;
    private $table_name = "users";
    
    // Properties
    public $id;
    public $username;
    public $password;
    public $role;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    

    
    // Get user by ID
    public function getUserById() {
        // Query to read single user
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch user data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set user properties
            $this->username = $row['username'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
   
    
    // แก้ไขฟังก์ชัน login() ในไฟล์ classes/User.php
public function login() {
    // Prepare query to check if username exists
    $query = "SELECT id, username, password, role FROM " . $this->table_name . " WHERE username = :username";
    $stmt = $this->conn->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':username', $this->username);
    
    // Execute query
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password using MD5 instead of password_verify
        if (md5($this->password) === $row['password']) {
            // Set user properties
            $this->id = $row['id'];
            $this->role = $row['role'];
            
            return true;
        }
    }
    
    return false;
}

// แก้ไขฟังก์ชัน create() ในไฟล์ classes/User.php
public function create() {
    // Query to insert user
    $query = "INSERT INTO " . $this->table_name . " 
              (username, password, role, created_at, updated_at) 
              VALUES 
              (:username, :password, :role, NOW(), NOW())";
    
    // Prepare statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize input
    $this->username = htmlspecialchars(strip_tags($this->username));
    $this->role = htmlspecialchars(strip_tags($this->role));
    
    // Create MD5 hash for password
    $password_hash = md5($this->password);
    
    // Bind parameters
    $stmt->bindParam(':username', $this->username);
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':role', $this->role);
    
    // Execute query
    if ($stmt->execute()) {
        // Get the last inserted ID
        $this->id = $this->conn->lastInsertId();
        return true;
    }
    
    return false;
}

// แก้ไขฟังก์ชัน updatePassword() ในไฟล์ classes/User.php
public function updatePassword() {
    // Query to update password
    $query = "UPDATE " . $this->table_name . " 
              SET password = :password, updated_at = NOW()
              WHERE id = :id";
    
    // Prepare statement
    $stmt = $this->conn->prepare($query);
    
    // Hash password with MD5
    $password_hash = md5($this->password);
    
    // Bind parameters
    $stmt->bindParam(':password', $password_hash);
    $stmt->bindParam(':id', $this->id);
    
    // Execute query
    if ($stmt->execute()) {
        return true;
    }
    
    return false;
}

    // Update user
    public function update() {
        // Query to update user
        $query = "UPDATE " . $this->table_name . " 
                  SET username = :username, role = :role, updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    

    
    // Delete user
    public function delete() {
        // Query to delete user
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
    
    // Check if username exists
    public function isUsernameExists() {
        // Query to check if username exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // Bind parameter
        $stmt->bindParam(':username', $this->username);
        
        // Execute query
        $stmt->execute();
        
        // Check if any row returned
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
    
    // Get all users
    public function getAllUsers() {
        // Query to get all users
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get user count by role
    public function getUserCountByRole($role) {
        // Query to count users by role
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE role = :role";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':role', $role);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
}
?>