<?php
class ClassSchedule {
    // Database connection and table name
    private $conn;
    private $table_name = "class_schedules";
    
    // Properties
    public $id;
    public $course_id;
    public $teacher_id;
    public $day_of_week;
    public $start_time;
    public $end_time;
    public $classroom;
    public $semester;
    public $academic_year;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new class schedule
    public function create() {
        // Query to insert class schedule
        $query = "INSERT INTO " . $this->table_name . " 
                  (course_id, teacher_id, day_of_week, start_time, end_time, classroom, semester, academic_year, created_at, updated_at) 
                  VALUES 
                  (:course_id, :teacher_id, :day_of_week, :start_time, :end_time, :classroom, :semester, :academic_year, NOW(), NOW())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->teacher_id = htmlspecialchars(strip_tags($this->teacher_id));
        $this->day_of_week = htmlspecialchars(strip_tags($this->day_of_week));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->classroom = htmlspecialchars(strip_tags($this->classroom));
        $this->semester = htmlspecialchars(strip_tags($this->semester));
        $this->academic_year = htmlspecialchars(strip_tags($this->academic_year));
        
        // Bind parameters
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':teacher_id', $this->teacher_id);
        $stmt->bindParam(':day_of_week', $this->day_of_week);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->bindParam(':classroom', $this->classroom);
        $stmt->bindParam(':semester', $this->semester);
        $stmt->bindParam(':academic_year', $this->academic_year);
        
        // Execute query
        if ($stmt->execute()) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update class schedule
    public function update() {
        // Query to update class schedule
        $query = "UPDATE " . $this->table_name . " 
                  SET course_id = :course_id, teacher_id = :teacher_id, 
                  day_of_week = :day_of_week, start_time = :start_time, 
                  end_time = :end_time, classroom = :classroom, 
                  semester = :semester, academic_year = :academic_year, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->teacher_id = htmlspecialchars(strip_tags($this->teacher_id));
        $this->day_of_week = htmlspecialchars(strip_tags($this->day_of_week));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->classroom = htmlspecialchars(strip_tags($this->classroom));
        $this->semester = htmlspecialchars(strip_tags($this->semester));
        $this->academic_year = htmlspecialchars(strip_tags($this->academic_year));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':teacher_id', $this->teacher_id);
        $stmt->bindParam(':day_of_week', $this->day_of_week);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->bindParam(':classroom', $this->classroom);
        $stmt->bindParam(':semester', $this->semester);
        $stmt->bindParam(':academic_year', $this->academic_year);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete class schedule
    public function delete() {
        // Query to delete class schedule
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
    
    // Get schedule by ID
    public function getScheduleById() {
        // Query to read single schedule
        $query = "SELECT cs.*, 
                  c.course_code, c.course_name, c.credits,
                  CONCAT(t.name_prefix, t.first_name, ' ', t.last_name) as teacher_name
                  FROM " . $this->table_name . " cs
                  LEFT JOIN courses c ON cs.course_id = c.id
                  LEFT JOIN teachers t ON cs.teacher_id = t.id
                  WHERE cs.id = :id";
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Fetch schedule data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row;
        }
        
        return false;
    }
    
    // Get schedules by semester and academic year
    public function getSchedulesBySemester() {
        // Query to get schedules by semester and academic year
        $query = "SELECT cs.*, 
                  c.course_code, c.course_name, c.credits,
                  CONCAT(t.name_prefix, t.first_name, ' ', t.last_name) as teacher_name
                  FROM " . $this->table_name . " cs
                  LEFT JOIN courses c ON cs.course_id = c.id
                  LEFT JOIN teachers t ON cs.teacher_id = t.id
                  WHERE cs.semester = :semester AND cs.academic_year = :academic_year
                  ORDER BY cs.day_of_week ASC, cs.start_time ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':semester', $this->semester);
        $stmt->bindParam(':academic_year', $this->academic_year);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get schedules by teacher
    public function getSchedulesByTeacher($teacher_id) {
        // Query to get schedules by teacher
        $query = "SELECT cs.*, 
                  c.course_code, c.course_name, c.credits
                  FROM " . $this->table_name . " cs
                  LEFT JOIN courses c ON cs.course_id = c.id
                  WHERE cs.teacher_id = :teacher_id AND cs.semester = :semester AND cs.academic_year = :academic_year
                  ORDER BY cs.day_of_week ASC, cs.start_time ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':semester', $this->semester);
        $stmt->bindParam(':academic_year', $this->academic_year);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get schedules by course
    public function getSchedulesByCourse($course_id) {
        // Query to get schedules by course
        $query = "SELECT cs.*, 
                  CONCAT(t.name_prefix, t.first_name, ' ', t.last_name) as teacher_name
                  FROM " . $this->table_name . " cs
                  LEFT JOIN teachers t ON cs.teacher_id = t.id
                  WHERE cs.course_id = :course_id AND cs.semester = :semester AND cs.academic_year = :academic_year
                  ORDER BY cs.day_of_week ASC, cs.start_time ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':semester', $this->semester);
        $stmt->bindParam(':academic_year', $this->academic_year);
        
        // Execute query
        $stmt->execute();
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get schedules by multiple courses
    public function getSchedulesByCourses($course_ids) {
        if (empty($course_ids)) {
            return [];
        }
        
        // Convert array of IDs to string of placeholders
        $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
        
        // Query to get schedules by multiple courses
        $query = "SELECT cs.*, 
                  c.course_code, c.course_name, c.credits,
                  CONCAT(t.name_prefix, t.first_name, ' ', t.last_name) as teacher_name
                  FROM " . $this->table_name . " cs
                  LEFT JOIN courses c ON cs.course_id = c.id
                  LEFT JOIN teachers t ON cs.teacher_id = t.id
                  WHERE cs.course_id IN ({$placeholders}) AND cs.semester = ? AND cs.academic_year = ?
                  ORDER BY cs.day_of_week ASC, cs.start_time ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters - course IDs first, then semester and academic year
        $params = array_merge($course_ids, [$this->semester, $this->academic_year]);
        $stmt->execute($params);
        
        // Return all rows
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check for schedule conflicts
    public function checkConflicts() {
        // Check teacher conflicts
        $query_teacher = "SELECT * FROM " . $this->table_name . " 
                         WHERE teacher_id = :teacher_id AND day_of_week = :day_of_week AND semester = :semester AND academic_year = :academic_year
                         AND ((start_time <= :start_time AND end_time > :start_time) OR 
                             (start_time < :end_time AND end_time >= :end_time) OR
                             (start_time >= :start_time AND end_time <= :end_time))";
        
        if ($this->id) {
            $query_teacher .= " AND id != :id";
        }
        
        $stmt_teacher = $this->conn->prepare($query_teacher);
        
        // Bind parameters
        $stmt_teacher->bindParam(':teacher_id', $this->teacher_id);
        $stmt_teacher->bindParam(':day_of_week', $this->day_of_week);
        $stmt_teacher->bindParam(':semester', $this->semester);
        $stmt_teacher->bindParam(':academic_year', $this->academic_year);
        $stmt_teacher->bindParam(':start_time', $this->start_time);
        $stmt_teacher->bindParam(':end_time', $this->end_time);
        
        if ($this->id) {
            $stmt_teacher->bindParam(':id', $this->id);
        }
        
        $stmt_teacher->execute();
        
        // Check classroom conflicts
        $query_classroom = "SELECT * FROM " . $this->table_name . " 
                           WHERE classroom = :classroom AND day_of_week = :day_of_week AND semester = :semester AND academic_year = :academic_year
                           AND ((start_time <= :start_time AND end_time > :start_time) OR 
                               (start_time < :end_time AND end_time >= :end_time) OR
                               (start_time >= :start_time AND end_time <= :end_time))";
        
        if ($this->id) {
            $query_classroom .= " AND id != :id";
        }
        
        $stmt_classroom = $this->conn->prepare($query_classroom);
        
        // Bind parameters
        $stmt_classroom->bindParam(':classroom', $this->classroom);
        $stmt_classroom->bindParam(':day_of_week', $this->day_of_week);
        $stmt_classroom->bindParam(':semester', $this->semester);
        $stmt_classroom->bindParam(':academic_year', $this->academic_year);
        $stmt_classroom->bindParam(':start_time', $this->start_time);
        $stmt_classroom->bindParam(':end_time', $this->end_time);
        
        if ($this->id) {
            $stmt_classroom->bindParam(':id', $this->id);
        }
        
        $stmt_classroom->execute();
        
        // Return conflicts if found
        $conflicts = [];
        
        if ($stmt_teacher->rowCount() > 0) {
            $conflicts['teacher'] = $stmt_teacher->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($stmt_classroom->rowCount() > 0) {
            $conflicts['classroom'] = $stmt_classroom->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return empty($conflicts) ? false : $conflicts;
    }
}
?>