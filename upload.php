<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db_functions.php';

// =============================================
$bucketName = ""; // Bucket Name
// =============================================

$message = "";
$messageType = "";
$STORAGE_LIMIT = 500 * 1024 * 1024; // 500 MB per user

// Get user folder prefix
$userFolder = "user_" . $_SESSION['user_id'] . "/";

// FIRST: Ensure user folder exists in bucket (create if not)
$checkCommand = "gsutil ls gs://{$bucketName}/{$userFolder} 2>&1";
exec($checkCommand, $checkOutput, $checkReturn);
if ($checkReturn !== 0) {
    // Folder doesn't exist, create it
    $createCommand = "gsutil mkdir gs://{$bucketName}/{$userFolder} 2>&1";
    exec($createCommand);
}

if(isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
    global $bucket;
    $user_id    = $_SESSION['user_id'];
    $uploaded   = 0;
    $failed     = 0;
    $totalFiles = count($_FILES['files']['name']);

    // Check current storage used
    $storageUsed = getUserStorageUsed($user_id);
    $newFilesSize = 0;
    for($i = 0; $i < $totalFiles; $i++) {
        if($_FILES['files']['error'][$i] === 0) {
            $newFilesSize += $_FILES['files']['size'][$i];
        }
    }

    if(($storageUsed + $newFilesSize) > $STORAGE_LIMIT) {
        $usedMB  = round($storageUsed / 1024 / 1024, 1);
        $message     = "Storage limit reached. You have used {$usedMB} MB of your 500 MB quota.";
        $messageType = "error";
    } else {
        for($i = 0; $i < $totalFiles; $i++) {
            if($_FILES['files']['error'][$i] !== 0) { $failed++; continue; }
            $fileTmp      = $_FILES['files']['tmp_name'][$i];
            $originalName = basename($_FILES['files']['name'][$i]);
            $fileExt      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileSize     = $_FILES['files']['size'][$i];
            
            // IMPORTANT: Add user folder to filename
            $uniqueName = time() . "_" . $i . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
            $fileName = $userFolder . $uniqueName;  // <-- THIS IS THE FIX
            
            try {
                $bucket->upload(fopen($fileTmp, 'r'), ['name' => $fileName]);
                $url = "https://storage.googleapis.com/" . $bucketName . "/" . $fileName;
                $fileId = uniqid('file_');
                $saveResult = saveFileMetadata($user_id, $fileId, $originalName, $url, $fileSize, $fileExt);
                if($saveResult['success']) { $uploaded++; } else { $failed++; }
            } catch (Exception $e) { $failed++; }
        }

        if($uploaded > 0 && $failed === 0) {
            $message     = $uploaded === 1 ? "File uploaded successfully." : "$uploaded files uploaded successfully.";
            $messageType = "success";
        } elseif($uploaded > 0) {
            $message     = "$uploaded uploaded, $failed failed.";
            $messageType = "error";
        } else {
            $message     = "All uploads failed. Please try again.";
            $messageType = "error";
        }
    }
}

$user = getUser($_SESSION['user_id']);
$username = $user['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CloudSphere — Upload Files</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --sky-deep:   #04111f;
            --cloud-teal: #0ea5e9;
            --cloud-cyan: #22d3ee;
            --accent:     #38bdf8;
            --accent-glow:rgba(56,189,248,0.25);
            --text-primary:#e8f4ff;
            --text-muted:  #6b90b0;
            --text-dim:    #304d66;
            --border:      rgba(56,189,248,0.15);
            --border-mid:  rgba(56,189,248,0.3);
            --glass:       rgba(4,20,38,0.85);
            --glass-dark:  rgba(2,12,24,0.75);
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            min-height: 100vh;
            background: var(--sky-deep);
            font-family: 'DM Sans', sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        .sky-layer {
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 100% 50% at 50% 0%, rgba(13,60,100,0.85) 0%, transparent 65%),
                radial-gradient(ellipse 60% 40% at 85% 15%, rgba(14,165,233,0.1) 0%, transparent 55%),
                radial-gradient(ellipse 50% 60% at 15% 85%, rgba(6,30,54,0.7) 0%, transparent 55%);
            pointer-events: none; z-index: 0;
        }

        .cloud-wisp {
            position: fixed; border-radius: 50%;
            pointer-events: none; will-change: transform;
        }
        .wisp-1 {
            width: 700px; height: 200px;
            background: radial-gradient(ellipse, rgba(56,189,248,0.055) 0%, transparent 70%);
            top: 8%; left: -8%;
            animation: driftCloud 32s infinite alternate ease-in-out; z-index: 0;
        }
        .wisp-2 {
            width: 500px; height: 150px;
            background: radial-gradient(ellipse, rgba(14,165,233,0.07) 0%, transparent 70%);
            top: 40%; right: -8%;
            animation: driftCloud 24s infinite alternate-reverse ease-in-out; z-index: 0;
        }
        .wisp-3 {
            width: 400px; height: 120px;
            background: radial-gradient(ellipse, rgba(34,211,238,0.04) 0%, transparent 70%);
            bottom: 15%; left: 20%;
            animation: driftCloud 28s 3s infinite alternate ease-in-out; z-index: 0;
        }
        @keyframes driftCloud {
            0%   { transform: translateX(0) translateY(0); }
            100% { transform: translateX(35px) translateY(12px); }
        }

        /* ─── Navbar ─── */
        .navbar {
            position: relative; z-index: 20;
            background: rgba(2,10,22,0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            display: flex; align-items: center; gap: 0.65rem;
            text-decoration: none;
        }
        .logo-icon {
            width: 34px; height: 34px;
            background: linear-gradient(145deg, rgba(14,165,233,0.25), rgba(56,189,248,0.12));
            border: 1px solid var(--border-mid);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-icon i {
            font-size: 1rem;
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 800;
            background: linear-gradient(135deg, #fff 40%, var(--accent));
            -webkit-background-clip: text; background-clip: text; color: transparent;
            letter-spacing: -0.03em;
        }

        .nav-center {
            display: flex; align-items: center; gap: 0.5rem;
        }
        .nav-pill {
            display: flex; align-items: center; gap: 0.45rem;
            padding: 0.4rem 0.9rem;
            border-radius: 40px;
            font-size: 0.82rem; font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .nav-pill:hover, .nav-pill.active {
            background: rgba(56,189,248,0.08);
            border-color: var(--border);
            color: var(--text-primary);
        }
        .nav-pill.active { color: var(--accent); }
        .nav-pill i { font-size: 0.75rem; }

        /* User dropdown */
        .user-dropdown { position: relative; }
        .user-btn {
            display: flex; align-items: center; gap: 0.55rem;
            background: rgba(56,189,248,0.07);
            border: 1px solid var(--border);
            padding: 0.45rem 1rem;
            border-radius: 40px;
            color: var(--text-muted);
            font-size: 0.85rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .user-btn:hover {
            background: rgba(56,189,248,0.12);
            border-color: var(--border-mid);
            color: var(--text-primary);
        }
        .user-btn .chevron { font-size: 0.6rem; transition: transform 0.2s; }
        .user-dropdown.active .chevron { transform: rotate(180deg); }

        .dropdown-content {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: rgba(3,14,28,0.97);
            backdrop-filter: blur(16px);
            border-radius: 0.85rem;
            border: 1px solid var(--border);
            min-width: 175px;
            overflow: hidden;
            opacity: 0; visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 100;
            box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .user-dropdown.active .dropdown-content {
            opacity: 1; visibility: visible; transform: translateY(0);
        }
        .dropdown-item {
            padding: 0.7rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            display: flex; align-items: center; gap: 0.7rem;
            transition: all 0.15s;
            cursor: pointer; border: none;
            background: none; width: 100%;
            text-align: left; font-size: 0.82rem;
            font-family: 'DM Sans', sans-serif;
        }
        .dropdown-item:hover { background: rgba(56,189,248,0.08); color: var(--text-primary); }
        .dropdown-item i { width: 14px; text-align: center; }
        .dropdown-item.logout {
            color: #f87171;
            border-top: 1px solid var(--border);
        }
        .dropdown-item.logout:hover { background: rgba(239,68,68,0.1); }

        /* ─── Main Layout ─── */
        .main {
            position: relative; z-index: 10;
            max-width: 640px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 1.8rem;
        }
        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.75rem; font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.03em;
            margin-bottom: 0.3rem;
        }
        .page-title span {
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        /* Upload Card */
        .upload-card {
            background: var(--glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            position: relative;
        }
        .upload-card::before {
            content: '';
            position: absolute;
            top: -1px; left: 8%; right: 8%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cloud-teal), var(--cloud-cyan), transparent);
        }

        /* Dropzone */
        .dropzone {
            margin: 1.5rem;
            background: rgba(4,17,35,0.6);
            border: 2px dashed rgba(56,189,248,0.25);
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .dropzone::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 50% 50%, rgba(56,189,248,0.04) 0%, transparent 70%);
            opacity: 0; transition: opacity 0.3s;
        }
        .dropzone:hover { border-color: rgba(56,189,248,0.5); }
        .dropzone:hover::before { opacity: 1; }
        .dropzone.drag-over {
            border-color: var(--cloud-teal);
            background: rgba(14,165,233,0.08);
            transform: scale(0.99);
        }
        .dropzone.drag-over::before { opacity: 1; }

        .dz-icon {
            position: relative;
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 70px; height: 70px;
            margin-bottom: 1.2rem;
        }
        .dz-icon-bg {
            position: absolute; inset: 0;
            background: linear-gradient(145deg, rgba(14,165,233,0.15), rgba(56,189,248,0.08));
            border: 1px solid rgba(56,189,248,0.25);
            border-radius: 1.1rem;
            transition: all 0.3s;
        }
        .dropzone:hover .dz-icon-bg, .dropzone.drag-over .dz-icon-bg {
            background: linear-gradient(145deg, rgba(14,165,233,0.25), rgba(56,189,248,0.15));
            border-color: rgba(56,189,248,0.5);
            box-shadow: 0 0 20px rgba(14,165,233,0.2);
        }
        .dz-icon i {
            position: relative; z-index: 1;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text; background-clip: text; color: transparent;
            transition: transform 0.3s;
        }
        .dropzone:hover .dz-icon i { transform: translateY(-3px); }

        .dz-title {
            font-size: 1rem; font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
        }
        .dz-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .dz-sub span { color: var(--accent); }
        .dz-limits {
            display: flex; justify-content: center; gap: 1rem;
            margin-top: 1rem; flex-wrap: wrap;
        }
        .dz-limit-tag {
            display: flex; align-items: center; gap: 0.4rem;
            font-size: 0.7rem; color: var(--text-dim);
            background: rgba(255,255,255,0.04);
            padding: 0.25rem 0.7rem;
            border-radius: 40px;
            border: 1px solid rgba(255,255,255,0.07);
        }
        #fileInput { display: none; }

        /* File preview */
        .file-preview {
            margin: 0 1.5rem 1rem;
            background: rgba(4,17,35,0.5);
            border-radius: 0.85rem;
            padding: 1rem 1.2rem;
            display: flex; align-items: center; gap: 1rem;
            flex-wrap: wrap;
            justify-content: space-between;
            border: 1px solid var(--border);
        }
        .file-info { display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0; }
        .file-type-icon {
            width: 42px; height: 42px; flex-shrink: 0;
            background: linear-gradient(145deg, rgba(14,165,233,0.15), rgba(56,189,248,0.08));
            border: 1px solid var(--border);
            border-radius: 0.6rem;
            display: flex; align-items: center; justify-content: center;
        }
        .file-type-icon i { font-size: 1.2rem; }
        .file-details { flex: 1; min-width: 0; }
        .file-name {
            color: var(--text-primary); font-weight: 500;
            font-size: 0.88rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .file-size { font-size: 0.72rem; color: var(--text-dim); margin-top: 0.15rem; }
        .remove-file {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.2);
            color: #f87171; padding: 0.4rem 0.9rem;
            border-radius: 40px; cursor: pointer;
            transition: all 0.2s; font-size: 0.78rem;
            font-family: 'DM Sans', sans-serif;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .remove-file:hover { background: rgba(239,68,68,0.22); border-color: rgba(239,68,68,0.4); }

        /* Upload button */
        .upload-btn {
            width: calc(100% - 3rem);
            margin: 0 1.5rem 1.5rem;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border: none;
            padding: 0.95rem;
            border-radius: 0.85rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            display: flex; align-items: center;
            justify-content: center; gap: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(14,165,233,0.3);
            font-family: 'DM Sans', sans-serif;
            position: relative; overflow: hidden;
        }
        .upload-btn::before {
            content: '';
            position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transform: skewX(-20deg); transition: left 0.4s;
        }
        .upload-btn:hover::before { left: 160%; }
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(14,165,233,0.42);
        }
        .upload-btn:disabled { opacity: 0.6; transform: none; cursor: not-allowed; }

        /* Nav links below card */
        .card-footer-links {
            display: flex; justify-content: center;
            padding: 0 1.5rem 1.5rem;
        }
        .card-link {
            display: flex; align-items: center; gap: 0.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.82rem; font-weight: 500;
            padding: 0.45rem 1rem;
            border-radius: 40px;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .card-link:hover {
            background: rgba(56,189,248,0.08);
            border-color: var(--border-mid);
            color: var(--accent);
        }
        .card-link i { font-size: 0.75rem; }

        /* Toast */
        .toast-msg {
            position: fixed; bottom: 2rem; left: 50%;
            transform: translateX(-50%) translateY(120%);
            background: rgba(2,12,24,0.97);
            backdrop-filter: blur(12px);
            padding: 0.8rem 1.6rem;
            border-radius: 0.85rem;
            color: white; font-weight: 500; z-index: 200;
            transition: transform 0.3s cubic-bezier(0.2,0.9,0.4,1.1);
            display: flex; align-items: center; gap: 0.8rem;
            border: 1px solid; box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            font-size: 0.88rem;
        }
        .toast-msg.show { transform: translateX(-50%) translateY(0); }
        .toast-success { border-color: rgba(34,197,94,0.4); }
        .toast-success i { color: #4ade80; }
        .toast-error   { border-color: rgba(239,68,68,0.4); }
        .toast-error i { color: #f87171; }

        footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-dim);
            font-size: 0.68rem;
            position: relative; z-index: 10;
            letter-spacing: 0.3px;
        }

        .spinner {
            display: inline-block;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 600px) {
            .navbar { padding: 0.75rem 1rem; }
            .nav-center { display: none; }
            .main { margin: 1.5rem auto; }
            .dropzone { padding: 2rem 1rem; margin: 1rem; }
            .upload-btn { margin: 0 1rem 1.2rem; width: calc(100% - 2rem); }
            .file-preview { margin: 0 1rem 0.8rem; }
            .card-footer-links { padding: 0 1rem 1.2rem; }
        }
    </style>
</head>
<body>
<div class="sky-layer"></div>
<div class="cloud-wisp wisp-1"></div>
<div class="cloud-wisp wisp-2"></div>
<div class="cloud-wisp wisp-3"></div>

<!-- Navbar -->
<nav class="navbar">
    <a href="upload.php" class="logo">
        <div class="logo-icon"><i class="fas fa-cloud"></i></div>
        <span class="logo-text">CloudSphere</span>
    </a>

    <div class="nav-center">
        <a href="upload.php" class="nav-pill active">
            <i class="fas fa-cloud-arrow-up"></i> Upload
        </a>
        <a href="view.php" class="nav-pill">
            <i class="fas fa-folder-open"></i> My Files
        </a>
    </div>

    <div class="user-dropdown" id="userDropdown">
        <button class="user-btn" onclick="toggleDropdown()">
            <i class="fas fa-circle-user"></i>
            <?php echo htmlspecialchars($username); ?>
            <i class="fas fa-chevron-down chevron"></i>
        </button>
        <div class="dropdown-content">
            <a href="view.php" class="dropdown-item">
                <i class="fas fa-folder-open"></i> My Files
            </a>
            <a href="upload.php" class="dropdown-item">
                <i class="fas fa-cloud-arrow-up"></i> Upload
            </a>
            <button class="dropdown-item logout" onclick="logout()">
                <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
            </button>
        </div>
    </div>
</nav>

<!-- Main -->
<div class="main">
    <div class="page-header">
        <div class="page-title">Upload to <span>CloudSphere</span></div>
        <div class="page-subtitle">Welcome back, <strong style="color:var(--text-primary)"><?php echo htmlspecialchars($username); ?></strong> — securely store your files on Google Cloud Storage.</div>
    </div>

    <div class="upload-card">
        <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <div class="dropzone" id="dropzone">
                <div class="dz-icon">
                    <div class="dz-icon-bg"></div>
                    <i class="fas fa-cloud-arrow-up"></i>
                </div>
                <div class="dz-title">Drag & drop your file here</div>
                <div class="dz-sub">or <span>click to browse</span> — select multiple files at once</div>
                <div class="dz-limits">
                    <span class="dz-limit-tag"><i class="fas fa-weight-hanging"></i> Max 50 MB each</span>
                    <span class="dz-limit-tag"><i class="fas fa-file"></i> All file types</span>
                    <span class="dz-limit-tag"><i class="fas fa-lock"></i> Encrypted</span>
                </div>
                <input type="file" name="files[]" id="fileInput" multiple>
            </div>

            <!-- Storage bar -->
            <?php
                $storageUsed  = getUserStorageUsed($_SESSION['user_id']);
                $storagePct   = min(100, round($storageUsed / (500*1024*1024) * 100, 1));
                $storageUsedMB = round($storageUsed / 1024 / 1024, 1);
                $barColor = $storagePct > 85 ? '#ef4444' : ($storagePct > 60 ? '#f59e0b' : '#38bdf8');
            ?>
            <div style="margin:0.75rem 0 0.25rem; font-size:0.78rem; color:var(--text-muted); display:flex; justify-content:space-between;">
                <span><i class="fas fa-database" style="margin-right:0.3rem;"></i>Storage used</span>
                <span style="color:var(--text-primary)"><?php echo $storageUsedMB; ?> MB / 500 MB</span>
            </div>
            <div style="height:5px;background:rgba(56,189,248,0.1);border-radius:99px;margin-bottom:1rem;overflow:hidden;">
                <div style="height:100%;width:<?php echo $storagePct; ?>%;background:<?php echo $barColor; ?>;border-radius:99px;transition:width 0.4s;"></div>
            </div>

            <!-- Multi-file list -->
            <div id="fileList" style="display:none; flex-direction:column; gap:0.5rem; margin-bottom:0.75rem;"></div>

            <button type="submit" id="submitBtn" class="upload-btn">
                <i class="fas fa-cloud-arrow-up"></i>
                <span>Upload File</span>
            </button>
        </form>

        <div class="card-footer-links">
            <a href="view.php" class="card-link">
                <i class="fas fa-folder-open"></i> View My Files
            </a>
        </div>
    </div>

    <footer>Secured by Google Cloud Storage · Files stored in <?php echo htmlspecialchars($bucketName); ?></footer>
</div>

<script>
    function toggleDropdown() {
        document.getElementById('userDropdown').classList.toggle('active');
    }
    document.addEventListener('click', function(e) {
        var dd = document.getElementById('userDropdown');
        if (dd && !dd.contains(e.target)) dd.classList.remove('active');
    });
    function logout() {
        fetch('logout.php', { method: 'POST' })
            .then(function() { window.location.href = 'login.php'; })
            .catch(function() { window.location.href = 'login.php'; });
    }

    var dropzone    = document.getElementById('dropzone');
    var fileInput   = document.getElementById('fileInput');
    var fileList    = document.getElementById('fileList');
    var uploadForm  = document.getElementById('uploadForm');
    var submitBtn   = document.getElementById('submitBtn');
    var selectedFiles = [];

    var iconMap = {
        image:   { icon: 'fa-image',      color: '#818cf8' },
        video:   { icon: 'fa-video',       color: '#ec4899' },
        audio:   { icon: 'fa-music',       color: '#06b6d4' },
        pdf:     { icon: 'fa-file-pdf',    color: '#ef4444' },
        doc:     { icon: 'fa-file-word',   color: '#3b82f6' },
        archive: { icon: 'fa-file-zipper', color: '#10b981' },
        code:    { icon: 'fa-code',        color: '#f59e0b' },
        default: { icon: 'fa-file',        color: '#38bdf8' }
    };
    function getFileType(ext) {
        if (['jpg','jpeg','png','gif','webp','svg','bmp'].indexOf(ext) > -1) return 'image';
        if (['mp4','webm','mov','avi','mkv'].indexOf(ext) > -1) return 'video';
        if (['mp3','wav','m4a','flac','ogg'].indexOf(ext) > -1) return 'audio';
        if (ext === 'pdf') return 'pdf';
        if (['doc','docx','txt','md'].indexOf(ext) > -1) return 'doc';
        if (['zip','rar','7z','tar','gz'].indexOf(ext) > -1) return 'archive';
        if (['php','html','js','css','json','py','java','c','cpp','ts'].indexOf(ext) > -1) return 'code';
        return 'default';
    }
    function formatSize(bytes) {
        if (!bytes) return '0 B';
        var k = 1024, s = ['B','KB','MB','GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + s[i];
    }

    function syncInputFiles() {
        var dt = new DataTransfer();
        selectedFiles.forEach(function(f) { dt.items.add(f); });
        fileInput.files = dt.files;
    }

    function renderFileList() {
        fileList.innerHTML = '';
        if (!selectedFiles.length) { fileList.style.display = 'none'; return; }
        fileList.style.display = 'flex';
        selectedFiles.forEach(function(f, idx) {
            var ext = f.name.split('.').pop().toLowerCase();
            var map = iconMap[getFileType(ext)];
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:0.6rem;padding:0.5rem 0.75rem;background:rgba(56,189,248,0.05);border:1px solid rgba(56,189,248,0.12);border-radius:8px;';
            row.innerHTML = '<i class="fas ' + map.icon + '" style="color:' + map.color + ';font-size:1rem;flex-shrink:0;"></i>'
                + '<span style="flex:1;min-width:0;font-size:0.82rem;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="' + f.name + '">' + f.name + '</span>'
                + '<span style="font-size:0.75rem;color:var(--text-muted);flex-shrink:0;">' + formatSize(f.size) + '</span>'
                + '<button type="button" data-idx="' + idx + '" style="background:none;border:none;cursor:pointer;color:#f87171;font-size:0.85rem;padding:0 0.2rem;flex-shrink:0;" title="Remove"><i class="fas fa-xmark"></i></button>';
            row.querySelector('button').addEventListener('click', function() {
                selectedFiles.splice(parseInt(this.getAttribute('data-idx')), 1);
                syncInputFiles();
                renderFileList();
                updateBtn();
            });
            fileList.appendChild(row);
        });
        updateBtn();
    }

    function updateBtn() {
        var span = submitBtn.querySelector('span');
        if (!span) return;
        span.textContent = selectedFiles.length === 0 ? 'Upload File'
            : selectedFiles.length === 1 ? 'Upload File'
            : 'Upload ' + selectedFiles.length + ' Files';
    }

    dropzone.addEventListener('click', function() { fileInput.click(); });
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            selectedFiles = Array.from(this.files);
            renderFileList();
        }
    });

    ['dragenter','dragover','dragleave','drop'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) { e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter','dragover'].forEach(function(ev) {
        dropzone.addEventListener(ev, function() { dropzone.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(function(ev) {
        dropzone.addEventListener(ev, function() { dropzone.classList.remove('drag-over'); });
    });
    dropzone.addEventListener('drop', function(e) {
        var files = e.dataTransfer.files;
        if (files.length) {
            selectedFiles = Array.from(files);
            syncInputFiles();
            renderFileList();
        }
    });

    function showToast(msg, type) {
        type = type || 'success';
        document.querySelectorAll('.toast-msg').forEach(function(t) { t.remove(); });
        var toast = document.createElement('div');
        toast.className = 'toast-msg toast-' + type;
        var icon = type === 'success' ? '<i class="fas fa-circle-check"></i>' : '<i class="fas fa-circle-exclamation"></i>';
        toast.innerHTML = icon + ' ' + msg;
        document.body.appendChild(toast);
        setTimeout(function() { toast.classList.add('show'); }, 10);
        setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 400); }, 4000);
    }

    <?php if($message): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
        <?php if($messageType === 'success'): ?>
        setTimeout(function() { window.location.href = 'view.php'; }, 2000);
        <?php endif; ?>
    });
    <?php endif; ?>

    uploadForm.addEventListener('submit', function(e) {
        if (!selectedFiles.length) { e.preventDefault(); showToast('Please select a file first', 'error'); return false; }
        var oversized = selectedFiles.find(function(f) { return f.size > 50 * 1024 * 1024; });
        if (oversized) { e.preventDefault(); showToast(oversized.name + ' exceeds 50 MB limit', 'error'); return false; }
        var count = selectedFiles.length;
        submitBtn.innerHTML = '<span class="spinner"></span> Uploading ' + count + ' file' + (count > 1 ? 's' : '') + '...';
        submitBtn.disabled = true;
    });
</script>
</body>
</html>
