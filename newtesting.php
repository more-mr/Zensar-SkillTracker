<?php
session_start();
require_once "database.php"; // Ensure this file contains your database connection

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

// Define the mapping of group numbers to group names
$groupNames = [
    '1' => 'Zensar Java 2023',
    '2' => 'C# & .Net Interns 2022',
    '3' => 'Liberty Admin Interns',
    '4' => 'Mainframe 2022',
    '5' => 'Mobile App Development',
    '6' => 'RPA Interns 2022',
    '7' => 'The Zen Squad',
    '8' => 'Zensar Cyber Security Int',
    '9' => 'Zensar Java 2024',
    '10' => 'Zensar Sales Project Interns'
];

// Handle CV view
if (isset($_GET['cv_download'])) { // Note: kept 'cv_download' for consistency, but it's now for viewing
    $userId = intval($_GET['cv_download']); // Single user ID for CV viewing
    $sql = "SELECT uploads FROM users WHERE id = $userId";
    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $filePath = $row['uploads'];
        
        if (file_exists($filePath)) {
            // Output the file contents for viewing
            header('Content-Type: application/pdf'); // Assuming PDFs, adjust as necessary
            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid user ID.";
    }
    exit;
}

// Handle CSV download
if (isset($_POST['download_csv'])) {
    $selectedIds = $_POST['selected_ids'];
    $csvData = "Employee Number,Employee Name,Skills\n";

    foreach ($selectedIds as $userId) {
        $userId = intval($userId);
        $sql = "SELECT employee_number, CONCAT(first_name, ' ', last_name) AS full_name, skills FROM users WHERE id = $userId";
        $result = mysqli_query($conn, $sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            $csvData .= '"' . $row['employee_number'] . '","' . $row['full_name'] . '","' . $row['skills'] . "\"\n";
        }
    }

    // Send CSV file for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users.csv"');
    echo $csvData;
    exit;
}

// Handle file delete
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    $sql = "SELECT profile_picture FROM users WHERE id = $userId";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $filePath = $row['profile_picture'];
        
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file
        }
        
        // Now, delete the user record from the database (optional)
        $sql = "DELETE FROM users WHERE id = $userId";
        if (mysqli_query($conn, $sql)) {
            header('Location: ' . $_SERVER['PHP_SELF']); // Refresh the page after deletion
            exit;
        } else {
            echo "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        echo "Invalid user ID.";
    }
    exit;
}

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $userId = intval($_POST['user_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);

    if (!empty($feedback)) {
        $sql = "UPDATE users SET feedback = '$feedback' WHERE id = $userId";
        if (mysqli_query($conn, $sql)) {
            echo "Feedback updated successfully.";
        } else {
            echo "Error updating feedback: " . mysqli_error($conn);
        }
    } else {
        echo "Feedback cannot be empty.";
    }
    exit;
}

// Get filter and search criteria
$searchSkill = isset($_GET['search_skill']) ? mysqli_real_escape_string($conn, $_GET['search_skill']) : '';
$filterGroup = isset($_GET['filter_group']) ? mysqli_real_escape_string($conn, $_GET['filter_group']) : '';

// Build the SQL query
$sql = "SELECT id, first_name, last_name, employee_number, `group`, profile_picture, skills, uploads, deployment, feedback FROM users WHERE 1";

if ($searchSkill) {
    $skillsArray = array_map('trim', explode(',', $searchSkill));
    $skillsConditions = [];
    foreach ($skillsArray as $skill) {
        $skillsConditions[] = "LOWER(skills) LIKE '%" . strtolower(mysqli_real_escape_string($conn, $skill)) . "%'";
    }
    $sql .= " AND (" . implode(' OR ', $skillsConditions) . ")";
}

if ($filterGroup) {
    $sql .= " AND `group` = '" . mysqli_real_escape_string($conn, array_search($filterGroup, $groupNames)) . "'";
}

$result = mysqli_query($conn, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Replace group number with group name
        if (isset($groupNames[$row['group']])) {
            $row['group'] = $groupNames[$row['group']];
        }
        // Ensure skills are available
        $row['skills'] = $row['skills'] ? $row['skills'] : 'No skills added';
        // Set deployment status
        $row['status'] = $row['deployment'] ? $row['deployment'] : 'Not Deployed';
        $users[] = $row;
    }
} else {
    die("Error fetching user data: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href=".\css\newtesting.css">
</head>
<body>
    <div class="sidebar">
        <h2><img src=".\css\newtesting.css" alt="Logo">SkillTraker</h2>
        <a href="newtesting.php">Associates</a>
        <a href="index.php">Sign out</a>
    </div>
    <div class="main-content">
        <div class="filter">
            <form method="GET" action="">
                <input type="text" id="searchSkill" name="search_skill" placeholder="Search by skill" value="<?php echo htmlspecialchars($searchSkill); ?>">
                <select id="filterGroup" name="filter_group">
                    <option value="">All Groups</option>
                    <?php foreach ($groupNames as $groupNumber => $groupName): ?>
                        <option value="<?php echo htmlspecialchars($groupName); ?>" <?php echo $filterGroup === $groupName ? 'selected' : ''; ?>><?php echo htmlspecialchars($groupName); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>
        <div class="user-table">
            <form id="csvForm" method="POST" action="">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Employee Number</th>
                            <th>Full Name</th>
                            <th>Skills</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row" data-group="<?php echo htmlspecialchars($user['group']); ?>" data-skills="<?php echo htmlspecialchars($user['skills']); ?>" data-status="<?php echo htmlspecialchars($user['status']); ?>">
                                <td><input type="checkbox" class="user-checkbox" name="selected_ids[]" value="<?php echo $user['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($user['employee_number']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <?php if ($user['profile_picture']): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['skills']); ?></td>
                                <td><?php echo htmlspecialchars($user['group']); ?></td>
                                <td><?php echo htmlspecialchars($user['status']); ?></td>
                                <td>
                                    <div class="action-icons">
                                        <a href="?cv_download=<?php echo $user['id']; ?>"><i class="fas fa-eye"></i></a>
                                        <a href="#" onclick="return confirmDelete(<?php echo $user['id']; ?>);"><i class="fas fa-trash"></i></a>
                                        <a href="#" class="message-icon" data-user-id="<?php echo $user['id']; ?>"><i class="fas fa-envelope"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="actions">
                    <button type="submit" name="download_csv">Download CSV of Selected</button>
                </div>
            </form>
        </div>
        <div class="pagination">
            <a href="#">&#8592; Previous</a>
            <a href="#">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">Next &#8594;</a>
        </div>
    </div>

    <!-- Message popup -->
    <div class="message-popup" id="messagePopup">
        <button class="close-btn">&times;</button>
        <h3>Send Message</h3>
        <textarea id="messageContent" placeholder="Write your message here..."></textarea>
        <button id="sendMessageBtn">Send Message</button>
    </div>

    <script src=".\js\newtesting.js"></script>
</body>
</html>
