<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Request for Summer Course</h1>
        <form action="submit.php" method="post" class="mt-4">
            <div class="mb-3">
                <label for="student_name" class="form-label">Name (นาย/นางสาว):</label>
                <input type="text" id="student_name" name="student_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID (รหัสประจำตัว):</label>
                <input type="text" id="student_id" name="student_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="class_level" class="form-label">Class Level (ระดับชั้น):</label>
                <input type="text" id="class_level" name="class_level" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="major" class="form-label">Major (สาขาวิชา):</label>
                <input type="text" id="major" name="major" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="major" class="form-label">Advisory(ครูที่ปรึกษา):</label>
                <input type="text" id="advisory" name="advisory" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone (เบอร์โทรศัพท์):</label>
                <input type="text" id="phone" name="phone" class="form-control" required>
            </div>

            <h3>Course Details</h3>
            <div id="course-container">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <input type="text" name="course_code[]" class="form-control" placeholder="Course Code" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="course_name[]" class="form-control" placeholder="Course Name" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="theory[]" class="form-control" placeholder="Theory" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="practice[]" class="form-control" placeholder="Practice" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="credits[]" class="form-control" placeholder="Credits" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="hours[]" class="form-control" placeholder="Hours" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="instructor[]" class="form-control" placeholder="Instructor" required>
                    </div>
                </div>
            </div>
            <button type="button" id="add-course" class="btn btn-secondary mb-3">Add Course</button>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script>
        document.getElementById('add-course').addEventListener('click', function () {
            const container = document.getElementById('course-container');
            const row = document.createElement('div');
            row.classList.add('row', 'mb-3');
            row.innerHTML = `
                <div class="col-md-2">
                    <input type="text" name="course_code[]" class="form-control" placeholder="Course Code" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="course_name[]" class="form-control" placeholder="Course Name" required>
                </div>
                <div class="col-md-1">
                    <input type="number" name="theory[]" class="form-control" placeholder="Theory" required>
                </div>
                <div class="col-md-1">
                    <input type="number" name="practice[]" class="form-control" placeholder="Practice" required>
                </div>
                <div class="col-md-1">
                    <input type="number" name="credits[]" class="form-control" placeholder="Credits" required>
                </div>
                <div class="col-md-1">
                    <input type="number" name="hours[]" class="form-control" placeholder="Hours" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="instructor[]" class="form-control" placeholder="Instructor" required>
                </div>
            `;
            container.appendChild(row);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>