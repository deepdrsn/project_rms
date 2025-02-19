<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle delete operations
if (isset($_GET['delete_student_id'])) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_student_id']]);
    header("Location: admin.php");
    exit();
}

if (isset($_GET['delete_teacher_id'])) {
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_teacher_id']]);
    header("Location: admin.php");
    exit();
}

// Fetch semesters
$semesters = $conn->query("SELECT * FROM semesters")->fetchAll();

// Pagination for students
$students_per_page = 10;
$student_page = isset($_GET['student_page']) ? max(1, intval($_GET['student_page'])) : 1;
$student_offset = ($student_page - 1) * $students_per_page;

// Get total students count
$total_students = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_student_pages = ceil($total_students / $students_per_page);

// Fetch students with their semesters
$students = $conn->query("
    SELECT s.*, sem.name AS semester_name 
    FROM students s 
    JOIN semesters sem ON s.semester_id = sem.id
    LIMIT $students_per_page OFFSET $student_offset
")->fetchAll();


// Check if a semester_id is set for filtering
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;

// Fetch existing schedules based on the selected semester
if ($semester_id) {
    $stmt = $conn->prepare("SELECT s.id, s.time_slot, t.name AS teacher_name, sem.name AS semester_name, s.subject 
    FROM schedules s 
    JOIN teachers t ON s.teacher_id = t.id 
    JOIN semesters sem ON s.semester_id = sem.id 
    WHERE s.semester_id = :semester_id");
    $stmt->execute(['semester_id' => $semester_id]);
} else {
    // Fetch all schedules if no semester is selected
    $stmt = $conn->prepare("SELECT s.id, s.time_slot, t.name AS teacher_name, sem.name AS semester_name, s.subject 
    FROM schedules s 
    JOIN teachers t ON s.teacher_id = t.id 
    JOIN semesters sem ON s.semester_id = sem.id");
    $stmt->execute();
}

$schedules = $stmt->fetchAll();

// Fetch students and teachers
$students = $conn->query("SELECT * FROM students")->fetchAll();
$teachers = $conn->query("SELECT * FROM teachers")->fetchAll();

// Handle delete operations for schedules
if (isset($_GET['delete_schedule_id'])) {
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = :id");
    $stmt->execute(['id' => $_GET['delete_schedule_id']]);
    header("Location: admin.php");
    exit();
}
// Pagination for teachers
$teachers_per_page = 10;
$teacher_page = isset($_GET['teacher_page']) ? max(1, intval($_GET['teacher_page'])) : 1;
$teacher_offset = ($teacher_page - 1) * $teachers_per_page;

// Get total teachers count
$total_teachers = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$total_teacher_pages = ceil($total_teachers / $teachers_per_page);

// Fetch teachers with their details
$teachers = $conn->query("SELECT * FROM teachers LIMIT $teachers_per_page OFFSET $teacher_offset")->fetchAll();

// Pagination for schedules
$schedules_per_page = 10;
$schedule_page = isset($_GET['schedule_page']) ? max(1, intval($_GET['schedule_page'])) : 1;
$schedule_offset = ($schedule_page - 1) * $schedules_per_page;

// Get total schedules count
$total_schedules = $conn->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
$total_schedule_pages = ceil($total_schedules / $schedules_per_page);

// Fetch schedules with their details
$schedules = $conn->query("SELECT * FROM schedules LIMIT $schedules_per_page OFFSET $schedule_offset")->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    padding: 0;
    background-color: #f8f9fa;
    color: #333;
}

h1, h2 {
    color: #222;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 15px;
    margin: 5px;
    background:rgb(0, 117, 241);
    color: white;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn:hover {
    background: #0056b3;
}

/* Tables */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

table th {
    background-color: #007bff;
    color: white;
}

table tr:nth-child(even) {
    background-color: #f2f2f2;
}

/* Forms */
form {
    background: white;
    padding: 20px;
    margin-top: 10px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

input, select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
}

button[type='submit'] {
    width: 100%;
    background: #007bff;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button[type='submit']:hover {
    background: #1e40af;
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
    border-radius: 5px;
}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="#students">Students</a></li>
            <li><a href="#teachers">Teachers</a></li>
            <li><a href="#schedules">Schedules</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Admin Panel</h1>

<section id="students>
    <h2>Students</h2>
    <div class="pagination">
        <?php if ($student_page > 1): ?>
            <a href="?student_page=<?php echo $student_page - 1; ?>" class="btn">Previous</a>
        <?php endif; ?>
        Page <?php echo $student_page; ?> of <?php echo $total_student_pages; ?>
        <?php if ($student_page < $total_student_pages): ?>
            <a href="?student_page=<?php echo $student_page + 1; ?>" class="btn">Next</a>
        <?php endif; ?>
    </div>
    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Semester</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['semester_id']); ?></td>
                    <td>
                        <a href="admin.php?delete_student_id=<?php echo $student['id']; ?>" 
                           class="btn" 
                           onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                        <button onclick="openStudentPasswordModal(<?php echo $student['id']; ?>)" class="btn">Change Password</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Add New Student</h2>
    <form method="POST" action="add_student.php">
        <input type="text" name="id_number" placeholder="Student ID" required>
        <input type="text" name="name" placeholder="Name" required>
        <select name="semester_id" required>
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?php echo htmlspecialchars($semester['id']); ?>">
                    <?php echo htmlspecialchars($semester['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Add Student</button>
    </form>
            </section>
    <section id="teachers">
    <h2>Teachers</h2>
    <div class="pagination">
        <?php if ($teacher_page > 1): ?>
            <a href="?teacher_page=<?php echo $teacher_page - 1; ?>" class="btn">Previous</a>
        <?php endif; ?>
        Page <?php echo $teacher_page; ?> of <?php echo $total_teacher_pages; ?>
        <?php if ($teacher_page < $total_teacher_pages): ?>
            <a href="?teacher_page=<?php echo $teacher_page + 1; ?>" class="btn">Next</a>
        <?php endif; ?>
    </div>
    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $teacher): ?>
                <tr>
                    <td><?php echo htmlspecialchars($teacher['id']); ?></td>
                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                    <td>
                        <a href="admin.php?delete_teacher_id=<?php echo $teacher['id']; ?>" 
                           class="btn" 
                           onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
                        <button onclick="openTeacherPasswordModal(<?php echo $teacher['id']; ?>)" class="btn">Change Password</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Add New Teacher</h2>
    <form method="POST" action="add_teacher.php" class="form-inline">
        <input type="text" name="name" placeholder="Name" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Add Teacher</button>
    </form>
            
    
    <section id= "schedules">
    <h2>Filter Schedules by Semester</h2>
    <form method="GET" action="admin.php">
        <select name="semester_id" required>
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?php echo htmlspecialchars($semester['id']); ?>">
                    <?php echo htmlspecialchars($semester['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>
            
    <h2>Existing Schedules</h2>
    <div class="pagination">
        <?php if ($schedule_page > 1): ?>
            <a href="?schedule_page=<?php echo $schedule_page - 1; ?><?php echo $semester_id ? '&semester_id='.$semester_id : ''; ?>" class="btn">Previous</a>
        <?php endif; ?>
        Page <?php echo $schedule_page; ?> of <?php echo $total_schedule_pages; ?>
        <?php if ($schedule_page < $total_schedule_pages): ?>
            <a href="?schedule_page=<?php echo $schedule_page + 1; ?><?php echo $semester_id ? '&semester_id='.$semester_id : ''; ?>" class="btn">Next</a>
        <?php endif; ?>
    </div>
    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Time Slot</th>
                <th>Teacher</th>
                <th>Semester</th>
                <th>Subject</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($schedule['id']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['time_slot']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['teacher_id']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['class']); ?></td>
                    <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                    <td>
                        <a href="admin.php?delete_schedule_id=<?php echo $schedule['id']; ?>" 
                           class="btn" 
                           onclick="return confirm('Are you sure you want to delete this schedule?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Add New Schedule</h2>
    <form method="POST" action="add_schedule.php">
        <input type="text" name="time_slot" placeholder="Time Slot (e.g., 10:00 AM - 11:00 AM)" required>
        <select name="teacher_id" required>
            <option value="">Select Teacher</option>
            <?php foreach ($teachers as $teacher): ?>
                <option value="<?php echo htmlspecialchars($teacher['id']); ?>">
                    <?php echo htmlspecialchars($teacher['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="semester_id" required>
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?php echo htmlspecialchars($semester['id']); ?>">
                    <?php echo htmlspecialchars($semester['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="subject" placeholder="Subject" required>
        <button type="submit">Add Schedule</button>
    </form>
            </section>
    <!-- Modal for changing teacher password -->
    <div id="teacherPasswordModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
        <h2>Change Teacher Password</h2>
        <form method="POST" action="update_teacher_password.php">
            <input type="hidden" name="teacher_id" id="modalTeacherId">
            <input type="password" name="new_password" placeholder="New Password" required>
            <button type="submit" class="btn">Update Password</button>
            <button type="button" onclick="closeTeacherPasswordModal()" class="btn">Cancel</button>
        </form>
    </div>

    <!-- Modal for changing student password -->
    <div id="studentPasswordModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5);">
        <h2>Change Student Password</h2>
        <form method="POST" action="update_student_password.php">
            <input type="hidden" name="student_id" id="modalStudentId">
            <input type="password" name="new_password" placeholder="New Password" required>
            <button type="submit" class="btn">Update Password</button>
            <button type="button" onclick="closeStudentPasswordModal()" class="btn">Cancel</button>
        </form>
    </div>

    <script>
        function openTeacherPasswordModal(teacherId) {
            document.getElementById('modalTeacherId').value = teacherId;
