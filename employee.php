<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_SESSION['id'];

// Fetch existing deployment status from database
$sql = 'SELECT deployment FROM users WHERE id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$existingDeployment = $stmt->get_result()->fetch_assoc()['deployment'];
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle skills update
    if (isset($_POST['skillsData'])) {
        $skills = $_POST['skillsData'];
        $skillArray = array_map('trim', explode(',', $skills));

        // Fetch existing skills from database
        $sql = 'SELECT skills FROM users WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $existingSkills = $stmt->get_result()->fetch_assoc()['skills'];
        $stmt->close();

        $existingSkillArray = array_map('trim', explode(', ', $existingSkills));
        $uniqueSkills = array_unique(array_merge($existingSkillArray, $skillArray));
        $skills = implode(', ', $uniqueSkills); // Rebuild the skills string without duplicates

        $sql = 'UPDATE users SET skills = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $skills, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Handle CV upload
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cv']['tmp_name'];
        $fileName = $_FILES['cv']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Set allowed file extensions
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // Create user-specific directory if it doesn't exist
            $userCvDir = './uploads/cv/' . $id;
            if (!is_dir($userCvDir)) {
                mkdir($userCvDir, 0777, true);
            }
            $dest_path = $userCvDir . '/' . $fileName;

            // Move the uploaded file to the user-specific directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Update the 'uploads' column in the database
                $sql = 'UPDATE users SET uploads = ? WHERE id = ?';
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $dest_path, $id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['upload_success'] = "CV uploaded successfully.";
            } else {
                $_SESSION['upload_success'] = "There was an error moving the uploaded file.";
            }
        } else {
            $_SESSION['upload_success'] = "Unsupported file type.";
        }
    } else {
        $_SESSION['upload_success'] = "No CV file uploaded.";
    }

    // Handle deployment update (New functionality)
    if (isset($_POST['atClient']) && $_POST['atClient'] === 'on' && !empty($_POST['company'])) {
        $company = $_POST['company'];
        $sql = 'UPDATE users SET deployment = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $company, $id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['atClient']) && $_POST['atClient'] === 'on') {
        // Handle case where 'atClient' is checked but company field is empty
        $_SESSION['deployment_error'] = "Company name cannot be empty.";
    }

    // Preserve deployment status if it was previously set
    if ($existingDeployment !== null && (!isset($_POST['atClient']) || $_POST['atClient'] !== 'on')) {
        $sql = 'UPDATE users SET deployment = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $existingDeployment, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Clear the deployment column if not deployed and no existing deployment status
        if (empty($_POST['atClient'])) {
            $sql = 'UPDATE users SET deployment = NULL WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: employee.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Skill Update</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href=".\css\employee.css">
</head>
<body>
    <div class="employee-container">
        <h1 class="text-center">Update Your Skills</h1>
        <form id="employeeForm" action="employee.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="skills" class="form-label">Add Skills</label>
                <div class="input-group">
                    <input type="text" id="skills" name="skills" class="form-control" placeholder="Enter a skill">
                    <button type="button" id="addSkill" class="btn btn-primary">Add Skill</button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Skill Suggestions</label>
                <div class="skill-suggestions d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('JavaScript')">+ JavaScript</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('Python')">+ Python</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('Java')">+ Java</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('C++')">+ C++</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('Ruby')">+ Ruby</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('PHP')">+ PHP</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addSuggestedSkill('Swift')">+ Swift</button>
                </div>
            </div>
            <div id="skillsList" class="mb-3 d-flex flex-wrap gap-2"></div>

            <div class="mb-3">
                <label for="cv" class="form-label">Upload CV</label>
                <input type="file" id="cv" name="cv" class="form-control">
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="atClient" name="atClient">
                <label class="form-check-label" for="atClient">Deployed</label>
            </div>

            <div class="mb-3" id="companyField" style="display: none;">
                <label for="company" class="form-label">Company</label>
                <input type="text" id="company" name="company" class="form-control" placeholder="Enter the company name">
            </div>

            <input type="hidden" id="skillsData" name="skillsData">

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">Update</button>
                <a href="Viewprofile.php" class="btn btn-info">View Profile</a>
                <a href="index.php" class="btn btn-secondary">Exit</a>
            </div>
            <div id="errorMessage" class="alert alert-danger mt-3 d-none" role="alert">
                Please add at least one skill or upload a CV.
            </div>
            <?php if (isset($_SESSION['upload_success'])): ?>
                <div class="alert alert-info mt-3" role="alert">
                    <?php echo $_SESSION['upload_success']; unset($_SESSION['upload_success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['deployment_error'])): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo $_SESSION['deployment_error']; unset($_SESSION['deployment_error']); ?>
                </div>
            <?php endif; ?>
        </form>

        <!-- Rocket icon -->
        <div id="rocket" class="rocket">
            <img src="rocket.png" alt="Rocket" class="img-fluid">
            <div class="fire"></div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src=".\js\employee.js"></script>
</body>
</html>