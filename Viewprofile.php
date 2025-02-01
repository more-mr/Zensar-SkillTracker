<?php
session_start();

// Check if user session is set
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

// Include the database connection
require_once 'database.php';

// Get user ID from session
$id = $_SESSION['id'];

// Fetch user details from the database
$sql = 'SELECT first_name, last_name, employee_number, profile_picture, uploads, skills, feedback FROM users WHERE id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if user data is found
if (!$user) {
    die("User not found.");
}

// Sanitize data
$first_name = htmlspecialchars($user['first_name']);
$last_name = htmlspecialchars($user['last_name']);
$employee_number = htmlspecialchars($user['employee_number']);
$profile_picture = $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : 'default_profile.png';
$uploaded_cv = $user['uploads'] ? htmlspecialchars(basename($user['uploads'])) : null; // Get the filename from the uploads path
$skills = htmlspecialchars($user['skills']);
$feedback = htmlspecialchars($user['feedback']); // Sanitize the feedback

// Count notifications
$notification_count = $feedback ? 1 : 0;

// Set success message
$upload_success = '';
if (isset($_SESSION['upload_success'])) {
    $upload_success = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}

// Define the upload directories
$user_cv_dir = __DIR__ . "/uploads/cv/{$id}"; // User-specific CV directory

// Function to get uploaded files
function get_uploaded_files($dir) {
    return is_dir($dir) ? array_diff(scandir($dir), ['.', '..']) : [];
}

// Get uploaded files
$cv_files = get_uploaded_files($user_cv_dir); // Get only user-specific CV files

// Handle delete request for skill or CV
if (isset($_GET['delete'])) {
    $item_to_delete = $_GET['delete'];
    $file_path = '';

    if (in_array($item_to_delete, $cv_files)) {
        $file_path = $user_cv_dir . "/" . $item_to_delete; // Check in user-specific directory
        $sql = 'UPDATE users SET uploads = NULL WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
    } else {
        // Handle skill deletion
        $sql = 'SELECT skills FROM users WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user_skills = $stmt->get_result()->fetch_assoc()['skills'];
        $skillArray = array_map('trim', explode(', ', $user_skills));
        $filteredSkills = array_filter($skillArray, function($skill) use ($item_to_delete) {
            return $skill !== $item_to_delete;
        });
        $newSkills = implode(', ', $filteredSkills);
        $sql = 'UPDATE users SET skills = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $newSkills, $id);
        $stmt->execute();
    }

    // Delete file if it exists
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }

    $_SESSION['upload_success'] = ucfirst($item_to_delete) . " deleted successfully.";
    header('Location: Viewprofile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .profile-container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            padding: 20px;
            color: #333;
            position: relative;
            overflow: hidden;
        }
        .back-arrow {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 20px;
            color: #007bff;
            text-decoration: none;
            background: #e9ecef;
            padding: 6px 10px;
            border-radius: 4px;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-image: url('<?php echo $profile_picture; ?>');
            background-size: cover;
            background-position: center;
            margin: 0 auto 20px;
            border: 4px solid #007bff; /* Blue border */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .profile-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-info p {
            margin: 6px 0;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e9ecef;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff; /* Blue header */
            color: #ffffff;
        }
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            font-weight: bold;
        }
        .exit-button {
            background: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            display: block;
            margin: 20px auto;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .exit-button:hover {
            background: #0056b3;
        }
        .dropdown {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            box-sizing: border-box;
            font-size: 14px;
            background-color: #ffffff;
            color: #495057;
        }
        .success-message {
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }
        /* Notification Bell */
        .notification-bell {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 20px;
            color: #007bff;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .notification-count {
            background-color: #dc3545;
            color: #ffffff;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .notification-popup {
            display: none;
            position: absolute;
            top: 45px;
            right: 15px;
            background: #343a40;
            color: #ffffff;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 8px;
            width: 250px;
            z-index: 1000;
        }
        .notification-popup.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="employee.php" class="back-arrow">← Back</a>
        <div class="notification-bell" onclick="toggleNotificationPopup()">
            🔔
            <?php if ($notification_count > 0): ?>
                <span class="notification-count"><?php echo $notification_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="notification-popup" id="notificationPopup">
            <p id="notificationContent"><?php echo $feedback ? $feedback : 'No new notifications.'; ?></p>
        </div>
        <h2 style="text-align: center; margin-bottom: 20px;">Profile</h2>
        <div class="profile-pic"></div>
        <div class="profile-info">
            <p><strong><?php echo $first_name . ' ' . $last_name; ?></strong></p>
            <p><?php echo $employee_number; ?></p>
        </div>
        <?php if ($upload_success): ?>
            <div class="success-message"><?php echo htmlspecialchars($upload_success); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="skillsTableBody">
                <tr>
                    <td>Skill</td>
                    <td>
                        <select class="dropdown" id="skillDropdown">
                            <option value="">Select Skill</option>
                            <?php foreach (explode(', ', $skills) as $skill): ?>
                                <option value="<?php echo htmlspecialchars($skill); ?>"><?php echo htmlspecialchars($skill); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><span class="delete-btn" onclick="confirmAndDelete('skill')">Delete</span></td>
                </tr>
                <tr>
                    <td>CV</td>
                    <td>
                        <select class="dropdown" id="cvDropdown">
                            <option value="">Select CV</option>
                            <?php foreach ($cv_files as $cv_file): ?>
                                <option value="<?php echo htmlspecialchars($cv_file); ?>"><?php echo htmlspecialchars($cv_file); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><span class="delete-btn" onclick="confirmAndDelete('cv')">Delete</span></td>
                </tr>
            </tbody>
        </table>
        <button class="exit-button" onclick="logout()">Exit</button>
    </div>

    <script>
        function confirmAndDelete(type) {
            let selectedItem = '';
            let url = 'Viewprofile.php?delete=';

            if (type === 'skill') {
                selectedItem = document.getElementById('skillDropdown').value;
            } else if (type === 'cv') {
                selectedItem = document.getElementById('cvDropdown').value;
            }

            if (!selectedItem) {
                alert('Please select an item to delete.');
                return;
            }

            const confirmation = confirm(`Are you sure you want to delete this ${type}?`);
            if (confirmation) {
                window.location.href = url + encodeURIComponent(selectedItem);
            }
        }

        function logout() {
            window.location.href = 'logout.php';
        }

        function toggleNotificationPopup() {
            const popup = document.getElementById('notificationPopup');
            popup.classList.toggle('active');
        }
    </script>
</body>
</html>
