<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = $_POST["role"]; // Get selected role

    // Hardcoded credentials for Manager
    $managerEmail = "manager@gmail.com";
    $managerPassword = "Manager@123";

    if ($role === "manager" && $email === $managerEmail && $password === $managerPassword) {
        $_SESSION["user"] = "yes";
        header("Location: newtesting.php");
        exit();
    }

    require_once "database.php";
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_array($result, MYSQLI_ASSOC);

    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["user"] = "yes";
            if ($role === "manager") {
                header("Location: newtesting.php");
            } else if ($role === "employee") {
                header("Location: employee.php");
            }
            exit();
        } else {
            $error = "Password does not match";
        }
    } else {
        $error = "Email does not match";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zensar Skill Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href=".\css\login001.css">
</head>
<body>
    <div class="container-fluid d-flex align-items-center justify-content-center vh-100">
        <div class="login-box text-center">
            <h2>Zensar Skill Tracker</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="index.php" method="post">
                <div class="login-as mb-3">
                    <label>Login as:</label>
                    <div class="d-flex justify-content-center">
                        <input type="radio" name="role" value="manager" id="manager" class="form-check-input" required>
                        <label for="manager" class="form-check-label me-3">Manager</label>
                        <input type="radio" name="role" value="employee" id="employee" class="form-check-input" required>
                        <label for="employee" class="form-check-label">Employee</label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <input type="email" placeholder="Enter Email:" name="email" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <div class="password-container">
                        <input type="password" placeholder="Enter Password:" name="password" id="password" class="form-control" required>
                        <span id="togglePassword" class="toggle-password">👁️</span>
                    </div>
                </div>
                <div class="form-check remember-me mb-3">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember Me</label>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="login">LOGIN</button>
                    <button type="button" id="forgotPassword">FORGOT PASSWORD</button>
                    <a href="registration.php" class="register-link">SIGN UP</a>
                </div>
            </form>
        </div>
    </div>
    <script src=".\js\login001.js"></script>
</body>
</html>