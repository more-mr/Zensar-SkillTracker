<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit('POST request method required');
}

if (empty($_FILES)) {
    exit('$_FILES is empty - is file_uploads set to "Off" in php.ini?');
}

// Function to handle file upload
function handle_file_upload($file, $upload_dir) {
    if ($file["error"] !== UPLOAD_ERR_OK) {
        switch ($file["error"]) {
            case UPLOAD_ERR_PARTIAL:
                exit('File only partially uploaded');
                break;
            case UPLOAD_ERR_NO_FILE:
                exit('No file was uploaded');
                break;
            case UPLOAD_ERR_EXTENSION:
                exit('File upload stopped by a PHP extension');
                break;
            case UPLOAD_ERR_FORM_SIZE:
                exit('File exceeds MAX_FILE_SIZE in the HTML form');
                break;
            case UPLOAD_ERR_INI_SIZE:
                exit('File exceeds upload_max_filesize in php.ini');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                exit('Temporary folder not found');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                exit('Failed to write file');
                break;
            default:
                exit('Unknown upload error');
                break;
        }
    }

    // Reject uploaded file larger than 1MB
    if ($file["size"] > 1048576) {
        exit('File too large (max 1MB)');
    }

    // Use fileinfo to get the mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file["tmp_name"]);

    $mime_types = ["application/pdf"];

    if (!in_array($mime_type, $mime_types)) {
        exit("Invalid file type");
    }

    // Replace any characters not \w- in the original filename
    $pathinfo = pathinfo($file["name"]);
    $base = $pathinfo["filename"];
    $base = preg_replace("/[^\w-]/", "_", $base);
    $filename = $base . "." . $pathinfo["extension"];
    $destination = $upload_dir . "/" . $filename;

    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Add a numeric suffix if the file already exists
    $i = 1;
    while (file_exists($destination)) {
        $filename = $base . "($i)." . $pathinfo["extension"];
        $destination = $upload_dir . "/" . $filename;
        $i++;
    }

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        exit("Can't move uploaded file");
    }

    return $filename;
}

// Handle certificates
$upload_dir = __DIR__ . "/uploads/certificates";
if (isset($_FILES['certificates'])) {
    foreach ($_FILES['certificates']['name'] as $key => $name) {
        $file = [
            'name' => $name,
            'type' => $_FILES['certificates']['type'][$key],
            'tmp_name' => $_FILES['certificates']['tmp_name'][$key],
            'error' => $_FILES['certificates']['error'][$key],
            'size' => $_FILES['certificates']['size'][$key],
        ];
        handle_file_upload($file, $upload_dir);
    }
}

// Handle CV
$upload_dir = __DIR__ . "/uploads/cv";
if (isset($_FILES['cv'])) {
    handle_file_upload($_FILES['cv'], $upload_dir);
}

// Set success message and redirect
$_SESSION['upload_success'] = 'Files uploaded successfully.';
header('Location: Viewprofile.php');
exit;
?>
