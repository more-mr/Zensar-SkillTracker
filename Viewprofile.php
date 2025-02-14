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
$profile_picture = $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : '.\images\default_profile.png';
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
    <link rel="stylesheet" href=".\css\viewprofile.css">
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
    <script src=".\js\viewprofile.js"></script> 
</body>
</html>