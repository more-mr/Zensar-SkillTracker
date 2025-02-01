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
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('download.png');
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background-color: rgb(70, 69, 69);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 500px;
            color: #fefefe;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-family: serif;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .password-container {
            position: relative;
        }

        .password-container .form-control {
            padding-right: 40px; /* Adjust this based on the size of the icon */
        }

        .toggle-password {
            position: absolute;
            right: 10px; /* Adjust this to position the icon as needed */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            font-size: 16px; /* Adjust size as needed */
        }

        .form-check {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
        }

        .form-check label {
            margin-right: 15px;
        }

        .alert {
            margin-top: 10px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button[type="submit"], #searchButton {
            background-color: #007bff;
            color: white;
        }

        button[type="button"] {
            background-color: #6c757d;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .register-link {
            margin-left: 10px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            align-self: center;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            align-items: center;
        }

        .remember-me {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
        }

        .remember-me input {
            margin-right: 5px;
        }

        .login-as {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
        }

        .login-as label {
            margin-right: 15px;
        }
    </style>
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
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = this.textContent === '👁️' ? '🙈' : '👁️';
        });
    </script>
</body>
</html>
