<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Check admin credentials first
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    }

    // Check teacher credentials if not admin
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $teacher = $stmt->fetch();

    if ($teacher && password_verify($password, $teacher['password'])) {
        $_SESSION['teacher_id'] = $teacher['id'];
        header("Location: teacher_dashboard.php");
        exit();
    }

    // Check student credentials if not admin or teacher
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :username");
    $stmt->execute(['username' => $username]);
    $student = $stmt->fetch();

    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['id'];
        header("Location: student.php");
        exit();
    }

    // If no match found
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="login_styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" autocomplete="username" required>
            <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
