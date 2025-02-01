<?php
session_start();
require_once "database.php";

// Ensure the upload directory exists
$target_dir = "uploads/images";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// Fetch groups from the database
$sql = "SELECT id, display_name FROM dropdownoptions";
$result = mysqli_query($conn, $sql);
$groups = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row;
    }
}

$registrationSuccess = false;
$errors = array();

if (isset($_POST["submit"])) {
    $firstName = $_POST["firstname"];
    $lastName = $_POST["lastname"];
    $employeenumber = $_POST["employeenumber"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $passwordRepeat = $_POST["repeat_password"];
    $group = $_POST['group'];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if (empty($firstName) OR empty($lastName) OR empty($employeenumber) OR empty($email) OR empty($password) OR empty($passwordRepeat) OR empty($group)) {
        array_push($errors, "All fields are required");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "Email is not valid");
    }
    if (strlen($password) < 8) {
        array_push($errors, "Password must be at least 8 characters long");
    }
    if ($password !== $passwordRepeat) {
        array_push($errors, "Password does not match");
    }
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        array_push($errors, "Email already exists!");
    }

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is an actual image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (5MB max)
            if ($_FILES["profile_picture"]["size"] <= 5000000) {
                // Allow certain file formats
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        // File uploaded successfully
                    } else {
                        array_push($errors, "Sorry, there was an error uploading your file.");
                    }
                } else {
                    array_push($errors, "Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                }
            } else {
                array_push($errors, "Sorry, your file is too large.");
            }
        } else {
            array_push($errors, "File is not an image.");
        }
    } else {
        array_push($errors, "No file was uploaded or there was an error uploading the file.");
    }

    if (count($errors) == 0) {
        // Use backticks around the reserved keyword
        $sql = "INSERT INTO users (first_name, last_name, employee_number, email, password, `group`, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);
        $prepareStmt = mysqli_stmt_prepare($stmt, $sql);
        if ($prepareStmt) {
            mysqli_stmt_bind_param($stmt, "sssssss", $firstName, $lastName, $employeenumber, $email, $passwordHash, $group, $target_file);
            mysqli_stmt_execute($stmt);
            $registrationSuccess = true;
        } else {
            array_push($errors, "Something went wrong. Please try again.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 400px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            appearance: none;
            background: #fff url('https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/img/arrow-down.svg') no-repeat right 10px center;
            background-size: 12px 12px;
            padding-right: 40px;
        }
        .form-control:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.25);
        }
        .form-label {
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-btn {
            display: flex;
            justify-content: center;
        }
        .form-btn input {
            width: 100%;
        }
        .alert {
            margin-top: 15px;
        }
        .password-container {
            position: relative;
        }
        .password-container .eye-icon {
            position: absolute;
            top: 70%; /* Adjusted value for better alignment */
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10; /* Ensures the icon is above other elements */
        }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center mb-4">Create Account</h3>
        <?php if (count($errors) > 0): ?>
            <?php foreach ($errors as $error): ?>
                <div class='alert alert-danger'><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <form action="registration.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="firstname" class="form-label">First Name</label>
                <input type="text" class="form-control" name="firstname" id="firstname" required>
            </div>
            <div class="form-group">
                <label for="lastname" class="form-label">Last Name</label>
                <input type="text" class="form-control" name="lastname" id="lastname" required>
            </div>
            <div class="form-group">
                <label for="employeenumber" class="form-label">Employee Number</label>
                <input type="text" class="form-control" name="employeenumber" id="employeenumber" required>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email" pattern="[a-zA-Z0-9._%+-]+@gmail\.com" required>
            </div>
            <div class="form-group password-container">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password" required>
                <i class="eye-icon" id="togglePassword">
                    <img src="https://img.icons8.com/material-outlined/24/000000/visible.png" alt="Show" />
                </i>
            </div>
            <div class="form-group password-container">
                <label for="repeat_password" class="form-label">Repeat Password</label>
                <input type="password" class="form-control" name="repeat_password" id="repeat_password" required>
                <i class="eye-icon" id="toggleRepeatPassword">
                    <img src="https://img.icons8.com/material-outlined/24/000000/visible.png" alt="Show" />
                </i>
            </div>
            <div class="form-group">
                <label for="group" class="form-label">Group</label>
                <select class="form-control" id="group" name="group" required>
                    <option value="">Select Group</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group['id']); ?>"><?php echo htmlspecialchars($group['display_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" name="profile_picture" id="profile_picture" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
        <div class="text-center mt-3">
            <p>Already Registered? <a href="index.php">Login Here</a></p>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
    <script type="text/javascript">
    $(document).ready(function() {
        var registrationSuccess = <?php echo json_encode($registrationSuccess); ?>;
        if (registrationSuccess) {
            Swal.fire({
                title: 'Successful',
                text: 'Welcome to SkillTracker',
                icon: 'success'
            }).then(function() {
                window.location.href = 'index.php';
            });
        } else if (registrationSuccess === false && <?php echo count($errors); ?> > 0) {
            Swal.fire({
                title: 'Error',
                text: 'There was an issue with your registration. Please try again.',
                icon: 'error'
            });
        }

        // Password visibility toggle
        $('#togglePassword').click(function() {
            let passwordField = $('#password');
            let icon = $(this).find('img');
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.attr('src', 'https://img.icons8.com/material-outlined/24/000000/invisible.png');
            } else {
                passwordField.attr('type', 'password');
                icon.attr('src', 'https://img.icons8.com/material-outlined/24/000000/visible.png');
            }
        });

        $('#toggleRepeatPassword').click(function() {
            let passwordField = $('#repeat_password');
            let icon = $(this).find('img');
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.attr('src', 'https://img.icons8.com/material-outlined/24/000000/invisible.png');
            } else {
                passwordField.attr('type', 'password');
                icon.attr('src', 'https://img.icons8.com/material-outlined/24/000000/visible.png');
            }
        });
    });
    </script>
</body>
</html>
