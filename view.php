<?php
session_start();
require_once 'includes/db_functions.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = null;

if ($user_id) {
    $user = getUser($user_id);
    $username = $user['username'] ?? 'User';
    $filesData = getUserFiles($user_id);
} else {
    $username = 'Guest';
    $filesData = [];
}

function getFileInfo($url) {
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    $imageExts   = ['jpg','jpeg','png','gif','webp','bmp','svg','ico'];
    $videoExts   = ['mp4','webm','ogg','mov','avi','mkv','flv','m4v'];
    $audioExts   = ['mp3','wav','ogg','m4a','flac','aac'];
    $pdfExts     = ['pdf'];
    $docExts     = ['doc','docx','txt','md','rtf','odt'];
    $archiveExts = ['zip','rar','7z','tar','gz','bz2'];
    $codeExts    = ['php','html','htm','js','css','json','xml','py','java','c','cpp','rb','go','rs','ts','jsx','tsx'];

    if (in_array($extension, $imageExts))   return ['type'=>'image',   'icon'=>'fa-image',       'color'=>'#818cf8','label'=>'Images'];
    if (in_array($extension, $videoExts))   return ['type'=>'video',   'icon'=>'fa-video',        'color'=>'#ec4899','label'=>'Videos'];
    if (in_array($extension, $audioExts))   return ['type'=>'audio',   'icon'=>'fa-music',        'color'=>'#06b6d4','label'=>'Audio'];
    if (in_array($extension, $pdfExts))     return ['type'=>'pdf',     'icon'=>'fa-file-pdf',     'color'=>'#ef4444','label'=>'PDF Documents'];
    if (in_array($extension, $docExts))     return ['type'=>'document','icon'=>'fa-file-lines',   'color'=>'#f59e0b','label'=>'Documents'];
    if (in_array($extension, $archiveExts)) return ['type'=>'archive', 'icon'=>'fa-file-zipper',  'color'=>'#10b981','label'=>'Archives'];
    if (in_array($extension, $codeExts))    return ['type'=>'code',    'icon'=>'fa-code',         'color'=>'#38bdf8','label'=>'Code Files'];
    return ['type'=>'generic','icon'=>'fa-file','color'=>'#64748b','label'=>'Other'];
}

$files = [];
$categories = ['pdf','image','video','audio','document','archive','code','generic'];
$categoryLabels = [
    'pdf'=>'PDF Documents','image'=>'Images','video'=>'Videos','audio'=>'Audio Files',
    'document'=>'Documents','archive'=>'Archives','code'=>'Code Files','generic'=>'Other Files'
];
$categoryIcons = [
    'pdf'=>'fa-file-pdf','image'=>'fa-images','video'=>'fa-video','audio'=>'fa-headphones',
    'document'=>'fa-file-lines','archive'=>'fa-file-zipper','code'=>'fa-code','generic'=>'fa-file'
];

foreach ($filesData as $row) {
    $file_url  = $row['fileUrl'];  // 
    $file_info = getFileInfo($row['fileName']);
    $category  = $file_info['type'];
    if (!isset($files[$category])) $files[$category] = [];

    $filePath   = $row['fileName'];
    $folderName = (strpos($filePath, '/') !== false) ? explode('/', $filePath)[0] : null;

    $files[$category][] = [
        'id'     => $row['fileId'],
        'url'    => $file_url,
        'name'   => $row['fileName'],
        'folder' => $folderName,
        'info'   => $file_info,
    ];
}

$total_files = array_sum(array_map('count', $files));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CloudSphere — My Files</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        :root {
            --sky-deep:   #04111f;
            --cloud-teal: #0ea5e9;
            --cloud-cyan: #22d3ee;
            --accent:     #38bdf8;
            --text-primary:#e8f4ff;
            --text-muted:  #6b90b0;
            --text-dim:    #304d66;
            --border:      rgba(56,189,248,0.15);
            --border-mid:  rgba(56,189,248,0.3);
            --glass:       rgba(4,20,38,0.85);
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
                radial-gradient(ellipse 55% 40% at 85% 20%, rgba(14,165,233,0.09) 0%, transparent 55%),
                radial-gradient(ellipse 55% 60% at 15% 80%, rgba(6,30,54,0.7) 0%, transparent 55%);
            pointer-events: none; z-index: 0;
        }
        .cloud-wisp {
            position: fixed; border-radius: 50%;
            pointer-events: none; will-change: transform;
        }
        .wisp-1 {
            width: 700px; height: 200px;
            background: radial-gradient(ellipse, rgba(56,189,248,0.05) 0%, transparent 70%);
            top: 6%; right: -8%;
            animation: driftCloud 30s infinite alternate ease-in-out; z-index: 0;
        }
        .wisp-2 {
            width: 500px; height: 150px;
            background: radial-gradient(ellipse, rgba(14,165,233,0.06) 0%, transparent 70%);
            bottom: 20%; left: -5%;
            animation: driftCloud 22s infinite alternate-reverse ease-in-out; z-index: 0;
        }
        @keyframes driftCloud {
            0% { transform: translateX(0) translateY(0); }
            100%{ transform: translateX(35px) translateY(12px); }
        }

        .navbar {
            position: relative; z-index: 20;
            background: rgba(2,10,22,0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 2rem;
            display: flex; justify-content: space-between;
            align-items: center; gap: 1rem;
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
            padding: 0.4rem 0.9rem; border-radius: 40px;
            font-size: 0.82rem; font-weight: 500;
            color: var(--text-muted); text-decoration: none;
            transition: all 0.2s; border: 1px solid transparent;
        }
        .nav-pill:hover, .nav-pill.active {
            background: rgba(56,189,248,0.08);
            border-color: var(--border); color: var(--text-primary);
        }
        .nav-pill.active { color: var(--accent); }
        .nav-pill i { font-size: 0.75rem; }

        .user-dropdown { position: relative; }
        .user-btn {
            display: flex; align-items: center; gap: 0.55rem;
            background: rgba(56,189,248,0.07);
            border: 1px solid var(--border);
            padding: 0.45rem 1rem; border-radius: 40px;
            color: var(--text-muted);
            font-size: 0.85rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .user-btn:hover {
            background: rgba(56,189,248,0.12);
            border-color: var(--border-mid); color: var(--text-primary);
        }
        .user-btn .chevron { font-size: 0.6rem; transition: transform 0.2s; }
        .user-dropdown.active .chevron { transform: rotate(180deg); }

        .dropdown-content {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: rgba(3,14,28,0.97);
            backdrop-filter: blur(16px);
            border-radius: 0.85rem; border: 1px solid var(--border);
            min-width: 175px; overflow: hidden;
            opacity: 0; visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 100; box-shadow: 0 16px 40px rgba(0,0,0,0.5);
        }
        .user-dropdown.active .dropdown-content {
            opacity: 1; visibility: visible; transform: translateY(0);
        }
        .dropdown-item {
            padding: 0.7rem 1rem;
            color: var(--text-muted); text-decoration: none;
            display: flex; align-items: center; gap: 0.7rem;
            transition: all 0.15s; cursor: pointer;
            border: none; background: none; width: 100%;
            text-align: left; font-size: 0.82rem;
            font-family: 'DM Sans', sans-serif;
        }
        .dropdown-item:hover { background: rgba(56,189,248,0.08); color: var(--text-primary); }
        .dropdown-item i { width: 14px; text-align: center; }
        .dropdown-item.logout { color: #f87171; border-top: 1px solid var(--border); }
        .dropdown-item.logout:hover { background: rgba(239,68,68,0.1); }

        .tabs-bar {
            position: relative; z-index: 10;
            background: rgba(2,10,22,0.75);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex; align-items: flex-end;
            gap: 0.25rem;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .tabs-bar::-webkit-scrollbar { display: none; }
        .tab-btn {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            background: none; border: none;
            color: var(--text-muted);
            font-size: 0.82rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            font-family: 'DM Sans', sans-serif;
        }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--cloud-teal);
        }
        .tab-btn i { font-size: 0.8rem; }
        .tab-count {
            background: rgba(56,189,248,0.12);
            color: var(--accent);
            padding: 0.1rem 0.45rem;
            border-radius: 20px; font-size: 0.65rem;
            font-weight: 600;
        }
        .tab-btn.active .tab-count {
            background: rgba(14,165,233,0.2);
        }

        .gallery-wrap {
            position: relative; z-index: 10;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 2rem 3rem;
        }

        .gallery-hero {
            display: flex; align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap; gap: 1rem;
            margin-bottom: 2rem;
        }
        .hero-eyebrow {
            font-size: 0.7rem; font-weight: 600;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--cloud-teal); margin-bottom: 0.4rem;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .hero-eyebrow i { font-size: 0.6rem; }
        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.03em;
            line-height: 1.1;
        }
        .hero-title span {
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero-sub {
            color: var(--text-muted); font-size: 0.88rem; margin-top: 0.4rem;
        }
        .hero-right {
            display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;
        }
        .stat-pill {
            display: flex; align-items: center; gap: 0.5rem;
            background: rgba(4,17,35,0.7);
            border: 1px solid var(--border);
            padding: 0.5rem 1rem; border-radius: 0.65rem;
        }
        .stat-pill-num {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 800;
            color: var(--accent);
        }
        .stat-pill-label {
            font-size: 0.72rem; color: var(--text-muted);
            line-height: 1.2;
        }

        .upload-btn-hero {
            display: flex; align-items: center; gap: 0.6rem;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border: none; padding: 0.6rem 1.2rem;
            border-radius: 0.65rem;
            color: white; font-size: 0.85rem; font-weight: 600;
            text-decoration: none; cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(14,165,233,0.3);
            font-family: 'DM Sans', sans-serif;
        }
        .upload-btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14,165,233,0.4);
        }

        .category-section { display: none; }
        .category-section.active { display: block; }

        .section-header {
            display: flex; align-items: center; gap: 0.7rem;
            margin-bottom: 1.2rem;
        }
        .section-header-icon {
            width: 32px; height: 32px;
            background: rgba(56,189,248,0.1);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .section-header-icon i { font-size: 0.9rem; color: var(--accent); }
        .section-header h2 {
            font-size: 1rem; font-weight: 600; color: var(--text-primary);
        }
        .section-count {
            font-size: 0.72rem; color: var(--text-muted);
            background: rgba(56,189,248,0.07);
            padding: 0.15rem 0.5rem; border-radius: 20px;
            border: 1px solid var(--border);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.2rem;
        }

        .file-card {
            background: rgba(4,18,35,0.75);
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s;
            cursor: pointer;
            position: relative;
            will-change: transform;
        }
        .file-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4), 0 0 0 1px rgba(56,189,248,0.2);
            border-color: rgba(56,189,248,0.3);
        }

        .file-preview {
            position: relative;
            width: 100%; padding-top: 68%;
            overflow: hidden;
            background: rgba(2,12,24,0.8);
        }
        .file-preview img {
            position: absolute; top:0; left:0;
            width:100%; height:100%; object-fit: cover;
            transition: transform 0.4s ease;
        }
        .file-card:hover .file-preview img { transform: scale(1.04); }
        .file-icon-preview {
            position: absolute; inset:0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 0.6rem;
        }
        .file-icon-preview i { font-size: 3.2rem; }
        .file-icon-preview span {
            font-size: 0.65rem; color: var(--text-muted);
            background: rgba(0,0,0,0.5);
            padding: 0.15rem 0.55rem; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .file-overlay {
            position: absolute; bottom:0; left:0; right:0;
            background: linear-gradient(to top, rgba(2,10,22,0.95), rgba(2,10,22,0.3) 70%, transparent);
            padding: 0.8rem;
            opacity: 0; transition: opacity 0.2s;
        }
        .file-card:hover .file-overlay { opacity: 1; }
        .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .action-btn {
            display: flex; align-items: center; gap: 0.35rem;
            background: rgba(4,17,35,0.85);
            border: 1px solid rgba(56,189,248,0.2);
            padding: 0.35rem 0.75rem;
            border-radius: 40px; color: var(--text-muted);
            font-size: 0.7rem; cursor: pointer;
            transition: all 0.15s; text-decoration: none;
            font-family: 'DM Sans', sans-serif;
        }
        .action-btn:hover {
            background: rgba(14,165,233,0.2);
            border-color: var(--cloud-teal); color: var(--text-primary);
        }
        .download-btn:hover {
            background: rgba(34,197,94,0.2) !important;
            border-color: #22c55e !important;
        }
        .delete-btn:hover {
            background: rgba(239,68,68,0.2) !important;
            border-color: #ef4444 !important;
            color: #f87171 !important;
        }

        .card-footer {
            padding: 0.75rem 1rem;
            display: flex; justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(56,189,248,0.07);
        }
        .file-name {
            font-size: 0.82rem; color: var(--text-primary); font-weight: 500;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 170px;
        }
        .file-type-badge {
            font-size: 0.62rem; padding: 0.15rem 0.5rem; border-radius: 20px;
            color: var(--text-dim);
            background: rgba(56,189,248,0.06); margin-top: 0.2rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .expand-icon {
            color: var(--text-dim); font-size: 0.75rem;
            transition: transform 0.2s, color 0.2s;
        }
        .file-card:hover .expand-icon { transform: translateX(3px); color: var(--accent); }

        .lightbox {
            display: none; position: fixed; inset:0;
            background: rgba(0,0,0,0.96);
            backdrop-filter: blur(14px);
            z-index: 1000; justify-content: center;
            align-items: center; flex-direction: column;
            cursor: pointer;
        }
        .lightbox.active { display: flex; }
        .lightbox-content {
            max-width: 90%; max-height: 80%;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            border: 1px solid rgba(56,189,248,0.15);
            overflow: auto;
        }
        .lightbox-img, .lightbox-video { max-width:90vw; max-height:80vh; border-radius:1rem; }
        .lightbox-pdf { width:80vw; height:80vh; background:white; border-radius:1rem; }
        .code-viewer {
            background: #060d19;
            padding: 1.5rem; border-radius: 1rem;
            max-width: 85vw; max-height: 75vh;
            overflow: auto;
            font-family: 'Monaco','Menlo','Courier New',monospace;
            font-size: 0.8rem; line-height: 1.6;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .code-viewer pre { margin:0; white-space:pre-wrap; word-wrap:break-word; }
        .lightbox-close {
            position: absolute; top:1.5rem; right:1.5rem;
            font-size: 1.2rem; color: white;
            background: rgba(4,17,35,0.8);
            border: 1px solid var(--border);
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s;
        }
        .lightbox-close:hover { background: rgba(239,68,68,0.8); border-color: #ef4444; transform: scale(1.05); }
        .lightbox-caption {
            margin-top: 1rem; color: var(--text-muted);
            font-size: 0.82rem; text-align: center;
            display: flex; gap: 1rem; align-items: center;
            flex-wrap: wrap; justify-content: center;
        }
        .lightbox-download {
            display: inline-flex; align-items: center; gap: 0.45rem;
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.3);
            padding: 0.35rem 0.9rem; border-radius: 40px;
            text-decoration: none; color: #4ade80;
            font-size: 0.78rem; transition: all 0.15s;
        }
        .lightbox-download:hover { background: rgba(34,197,94,0.25); }

        .empty-state {
            text-align: center; padding: 4rem 2rem;
            background: rgba(4,17,35,0.4);
            border-radius: 1.25rem;
            border: 1px solid var(--border);
        }
        .empty-icon {
            width: 72px; height: 72px;
            background: linear-gradient(145deg, rgba(14,165,233,0.12), rgba(56,189,248,0.07));
            border: 1px solid var(--border-mid);
            border-radius: 1.2rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .empty-icon i {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .empty-state h3 { color: var(--text-primary); font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .empty-state p { color: var(--text-muted); font-size: 0.88rem; }
        .empty-cta {
            display: inline-flex; align-items: center; gap: 0.6rem;
            margin-top: 1.3rem;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            padding: 0.65rem 1.4rem; border-radius: 0.7rem;
            color: white; text-decoration: none; font-weight: 600;
            font-size: 0.88rem;
            box-shadow: 0 4px 14px rgba(14,165,233,0.3);
            transition: all 0.2s;
        }
        .empty-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,165,233,0.4); }

        .toast-msg {
            position: fixed; bottom:2rem; left:50%;
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
            text-align: center; padding: 1.5rem;
            color: var(--text-dim); font-size: 0.68rem;
            position: relative; z-index: 10; letter-spacing: 0.3px;
        }

        @media (max-width: 700px) {
            .navbar { padding: 0.75rem 1rem; }
            .nav-center { display: none; }
            .tabs-bar { padding: 0 1rem; }
            .gallery-wrap { padding: 1.5rem 1rem 2.5rem; }
            .gallery-hero { gap: 1.2rem; }
            .hero-title { font-size: 1.5rem; }
            .gallery-grid { grid-template-columns: 1fr; gap: 1rem; }
            .lightbox-pdf { width: 95vw; height: 70vh; }
            .code-viewer { max-width: 95vw; font-size: 0.72rem; }
        }
    </style>
</head>
<body>
<div class="sky-layer"></div>
<div class="cloud-wisp wisp-1"></div>
<div class="cloud-wisp wisp-2"></div>

<nav class="navbar">
    <a href="upload.php" class="logo">
        <div class="logo-icon"><i class="fas fa-cloud"></i></div>
        <span class="logo-text">CloudSphere</span>
    </a>
    <div class="nav-center">
        <a href="upload.php" class="nav-pill">
            <i class="fas fa-cloud-arrow-up"></i> Upload
        </a>
        <a href="view.php" class="nav-pill active">
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
            <a href="upload.php" class="dropdown-item">
                <i class="fas fa-cloud-arrow-up"></i> Upload File
            </a>
            <button class="dropdown-item logout" onclick="logout()">
                <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
            </button>
        </div>
    </div>
</nav>

<div class="tabs-bar" id="categoryTabs">
    <?php
    $first = true;
    foreach ($categories as $cat):
        if (isset($files[$cat]) && count($files[$cat]) > 0):
    ?>
    <button class="tab-btn <?php echo $first ? 'active' : ''; ?>" data-category="<?php echo $cat; ?>" onclick="switchCategory('<?php echo $cat; ?>')">
        <i class="fas <?php echo $categoryIcons[$cat]; ?>"></i>
        <?php echo $categoryLabels[$cat]; ?>
        <span class="tab-count"><?php echo count($files[$cat]); ?></span>
    </button>
    <?php
        $first = false;
        endif;
    endforeach;
    ?>
</div>

<div class="gallery-wrap">
    <div class="gallery-hero">
        <div class="hero-left">
            <div class="hero-eyebrow"><i class="fas fa-folder-open"></i> Personal Cloud Drive</div>
            <div class="hero-title">My <span>Files</span></div>
            <div class="hero-sub">Securely stored on Google Cloud Storage · <?php echo $total_files; ?> file<?php echo $total_files !== 1 ? 's' : ''; ?> in your vault</div>
        </div>
        <div class="hero-right">
            <?php if($total_files > 0): ?>
            <?php foreach ($categories as $cat): if (isset($files[$cat]) && count($files[$cat]) > 0): ?>
            <div class="stat-pill">
                <span class="stat-pill-num"><?php echo count($files[$cat]); ?></span>
                <span class="stat-pill-label"><?php echo strtolower($categoryLabels[$cat]); ?></span>
            </div>
            <?php endif; endforeach; ?>
            <?php endif; ?>
            <a href="upload.php" class="upload-btn-hero">
                <i class="fas fa-cloud-arrow-up"></i> Upload
            </a>
            <a href="view.php" class="upload-btn-hero" style="background:rgba(56,189,248,0.08);border-color:rgba(56,189,248,0.3);">
                <i class="fas fa-rotate-right"></i> Refresh
            </a>
        </div>
    </div>

    <?php if($total_files > 0): ?>
    <?php
    $isFirst = true;
    foreach ($categories as $cat):
        if (isset($files[$cat]) && count($files[$cat]) > 0):
    ?>
    <div class="category-section <?php echo $isFirst ? 'active' : ''; ?>" id="section-<?php echo $cat; ?>">
        <div class="section-header">
            <div class="section-header-icon">
                <i class="fas <?php echo $categoryIcons[$cat]; ?>"></i>
            </div>
            <h2><?php echo $categoryLabels[$cat]; ?></h2>
            <span class="section-count"><?php echo count($files[$cat]); ?> file<?php echo count($files[$cat]) !== 1 ? 's' : ''; ?></span>
        </div>
        <div class="gallery-grid">
            <?php foreach ($files[$cat] as $file): ?>
            <div class="file-card"
                 data-file-id="<?php echo $file['id']; ?>"
                 data-file-url="<?php echo $file['url']; ?>"
                 data-file-name="<?php echo htmlspecialchars($file['name']); ?>"
                 data-file-type="<?php echo $file['info']['type']; ?>"
                 data-file-ext="<?php echo strtolower(pathinfo($file['url'], PATHINFO_EXTENSION)); ?>">
                <div class="file-preview">
                    <?php if($file['info']['type'] == 'image'): ?>
                        <img src="<?php echo $file['url']; ?>" alt="<?php echo htmlspecialchars($file['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="file-icon-preview">
                            <i class="fas <?php echo $file['info']['icon']; ?>" style="color:<?php echo $file['info']['color']; ?>"></i>
                            <span><?php echo strtoupper(pathinfo($file['url'], PATHINFO_EXTENSION) ?: 'FILE'); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="file-overlay">
                        <div class="action-buttons">
                            <a href="<?php echo $file['url']; ?>" download class="action-btn download-btn">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button class="action-btn" onclick="event.stopPropagation(); copySignedLink(this, '<?php echo $file['id']; ?>', '<?php echo $file['url']; ?>')" title="Generate shareable link (expires in 60s)">
        <i class="fas fa-link"></i> Copy Link
    </button>
    <button class="action-btn view-btn" onclick="event.stopPropagation(); openFileLightbox(this)">
        <i class="fas fa-eye"></i> View
    </button>
    <button class="action-btn delete-btn" onclick="event.stopPropagation(); deleteFile(this, '<?php echo $file['id']; ?>', '<?php echo $file['url']; ?>')">
        <i class="fas fa-trash"></i> Delete
    </button>
</div>
                    </div>
                </div>
                <div class="card-footer">
                    <div style="min-width:0;">
                        <div class="file-name" title="<?php echo htmlspecialchars($file['name']); ?>">
                            <i class="fas <?php echo $file['info']['icon']; ?>" style="color:<?php echo $file['info']['color']; ?>; margin-right:0.35rem; font-size:0.75rem;"></i>
                            <?php
                                $displayName = basename($file['name']);
                                echo htmlspecialchars(strlen($displayName) > 26 ? substr($displayName, 0, 23).'...' : $displayName);
                            ?>
                        </div>
                        <?php if($file['folder']): ?>
                        <div class="file-type-badge" style="background:rgba(56,189,248,0.1);color:#38bdf8;border-color:rgba(56,189,248,0.3);">
                            <i class="fas fa-folder" style="font-size:0.65rem;"></i> <?php echo htmlspecialchars($file['folder']); ?>
                        </div>
                        <?php else: ?>
                        <div class="file-type-badge"><?php echo $file['info']['type']; ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="expand-icon"><i class="fas fa-arrow-right"></i></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
        $isFirst = false;
        endif;
    endforeach;
    ?>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-cloud-arrow-up"></i></div>
        <h3>Your drive is empty</h3>
        <p>Upload your first file to get started with CloudSphere.</p>
        <a href="upload.php" class="empty-cta">
            <i class="fas fa-cloud-arrow-up"></i> Upload your first file
        </a>
    </div>
    <?php endif; ?>
</div>

<footer>Secured by Google Cloud Storage &amp; Firestore · All file types supported</footer>

<div id="lightbox" class="lightbox">
    <div class="lightbox-close" id="lightboxClose"><i class="fas fa-xmark"></i></div>
    <div id="lightboxContent" class="lightbox-content" style="display:flex;justify-content:center;align-items:center;"></div>
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<script>
    function showToast(message, type = 'success') {
        document.querySelectorAll('.toast-msg').forEach(t => t.remove());
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        const icon = type === 'success' ? '<i class="fas fa-circle-check"></i>' : '<i class="fas fa-circle-exclamation"></i>';
        toast.innerHTML = `${icon} ${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 4000);
    }

    async function deleteFile(button, fileId, fileUrl) {
        if (!confirm('Are you sure you want to delete this file?\n\nThis will permanently remove it from CloudSphere and Google Cloud Storage.')) return;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        try {
            const formData = new FormData();
            formData.append('file_id', fileId);
            formData.append('file_url', fileUrl);
            const response = await fetch('delete_file.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                const fileCard = button.closest('.file-card');
                const section = fileCard.closest('.category-section');
                const category = section.id.replace('section-', '');
                fileCard.style.transition = 'opacity 0.25s, transform 0.25s';
                fileCard.style.opacity = '0';
                fileCard.style.transform = 'scale(0.94)';
                setTimeout(() => {
                    fileCard.remove();
                    showToast(result.message, 'success');
                    const remaining = section.querySelectorAll('.file-card').length;
                    const tabBtn = document.querySelector(`.tab-btn[data-category="${category}"]`);
                    if (tabBtn) {
                        tabBtn.querySelector('.tab-count').textContent = remaining;
                        if (remaining === 0) {
                            tabBtn.style.display = 'none';
                            section.remove();
                            const firstTab = document.querySelector('.tab-btn:not([style*="display: none"])');
                            firstTab ? firstTab.click() : location.reload();
                        }
                    }
                    updateStats();
                }, 250);
            } else {
                showToast(result.message, 'error');
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        } catch(e) {
            showToast('Network error. Please try again.', 'error');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    }

    async function copySignedLink(button, fileId, fileUrl) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('file_url', fileUrl);

            const response = await fetch('includes/signed_url.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success && result.url) {
                // HTTP fallback: create temporary textarea
                const textarea = document.createElement('textarea');
                textarea.value = result.url;
                textarea.style.position = 'fixed';
                textarea.style.top = '0';
                textarea.style.left = '0';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showToast('Shareable link copied! Expires in 60 seconds.', 'success');
                } catch (err) {
                    showToast('Link generated! (Copy manually: ' + result.url.substring(0, 50) + '...)', 'success');
                }
                document.body.removeChild(textarea);
            } else {
                showToast(result.error || 'Failed to generate link.', 'error');
            }
        } catch (e) {
            showToast('Failed to generate link. Check console for details.', 'error');
            console.error(e);
        }

        button.innerHTML = originalHTML;
        button.disabled = false;
    }

    function updateStats() {
        const total = document.querySelectorAll('.file-card').length;
        const sub = document.querySelector('.hero-sub');
        if (sub) sub.textContent = `Securely stored on Google Cloud Storage · ${total} file${total !== 1 ? 's' : ''} in your vault`;
    }

    function toggleDropdown() {
        document.getElementById('userDropdown').classList.toggle('active');
    }
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('userDropdown');
        if (dd && !dd.contains(e.target)) dd.classList.remove('active');
    });
    function logout() {
        fetch('logout.php', { method: 'POST' })
            .then(() => { window.location.href = 'login.php'; })
            .catch(() => { window.location.href = 'login.php'; });
    }

    function switchCategory(category) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-category') === category);
        });
        document.querySelectorAll('.category-section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById('section-' + category);
        if (target) target.classList.add('active');
    }

    async function displayCodeContent(fileUrl, fileName) {
        try {
            const response = await fetch(fileUrl);
            const codeContent = await response.text();
            const escaped = codeContent.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const div = document.createElement('div');
            div.className = 'code-viewer';
            div.innerHTML = `<pre>${escaped}</pre>`;
            return div;
        } catch(e) {
            const div = document.createElement('div');
            div.className = 'code-viewer';
            div.style.color = '#ef4444';
            div.innerHTML = '<p>Unable to load file content. The file may not be publicly accessible.</p>';
            return div;
        }
    }

    const lightbox = document.getElementById('lightbox');
    const lightboxContent = document.getElementById('lightboxContent');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const closeBtn = document.getElementById('lightboxClose');

    async function openFileLightbox(element) {
        const card = element.closest('.file-card');
        const fileUrl = card.getAttribute('data-file-url');
        const fileName = card.getAttribute('data-file-name');
        const fileType = card.getAttribute('data-file-type');
        lightboxContent.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#38bdf8;"></i><p style="margin-top:1rem;color:#6b90b0;">Loading...</p></div>';
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';

        if (fileType === 'image') {
            const img = document.createElement('img');
            img.className = 'lightbox-img';
            img.src = fileUrl;
            img.style.cssText = 'max-width:90vw;max-height:80vh;border-radius:1rem;';
            img.onload = () => { lightboxContent.innerHTML = ''; lightboxContent.appendChild(img); };
            img.onerror = () => { lightboxContent.innerHTML = '<div style="text-align:center;padding:2rem;color:#f87171;"><i class="fas fa-circle-exclamation" style="font-size:3rem;display:block;margin-bottom:.8rem;"></i>Failed to load image</div>'; };
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(img);
        } else if (fileType === 'video') {
            const video = document.createElement('video');
            video.src = fileUrl;
            video.controls = true;
            video.autoplay = false;
            video.style.cssText = 'max-width:90vw;max-height:80vh;border-radius:1rem;';
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(video);
        } else if (fileType === 'pdf') {
            const embed = document.createElement('embed');
            embed.src = fileUrl;
            embed.type = 'application/pdf';
            embed.style.cssText = 'width:80vw;height:80vh;border-radius:1rem;';
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(embed);
        } else if (fileType === 'audio') {
            const div = document.createElement('div');
            div.style.cssText = 'text-align:center;padding:2.5rem;background:rgba(4,17,35,0.8);border-radius:1rem;border:1px solid rgba(56,189,248,0.15);';
            div.innerHTML = `<i class="fas fa-music" style="font-size:4rem;color:#06b6d4;display:block;margin-bottom:1.2rem;"></i><audio controls style="width:300px;max-width:80vw;"><source src="${fileUrl}" type="audio/mpeg">Your browser does not support audio.</audio>`;
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(div);
        } else if (fileType === 'code') {
            const codeViewer = await displayCodeContent(fileUrl, fileName);
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(codeViewer);
        } else {
            const div = document.createElement('div');
            div.style.cssText = 'text-align:center;padding:2.5rem;background:rgba(4,17,35,0.8);border-radius:1rem;border:1px solid rgba(56,189,248,0.15);';
            div.innerHTML = `<i class="fas fa-file" style="font-size:4rem;color:#64748b;display:block;margin-bottom:1rem;"></i><p style="margin-bottom:1.2rem;color:#6b90b0;">Preview unavailable for this file type</p><a href="${fileUrl}" download class="lightbox-download"><i class="fas fa-download"></i> Download ${fileName}</a>`;
            lightboxContent.innerHTML = '';
            lightboxContent.appendChild(div);
        }
        lightboxCaption.innerHTML = `<span style="color:var(--text-muted)">${fileName}</span><a href="${fileUrl}" download class="lightbox-download"><i class="fas fa-download"></i> Download</a>`;
    }

    document.querySelectorAll('.file-card').forEach(card => {
        card.addEventListener('click', async (e) => {
            if (e.target.closest('.action-btn')) return;
            const fileType = card.getAttribute('data-file-type');
            const fileUrl = card.getAttribute('data-file-url');
            const fileName = card.getAttribute('data-file-name');
            lightboxContent.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#38bdf8;"></i><p style="margin-top:1rem;color:#6b90b0;">Loading...</p></div>';
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';

            if (fileType === 'image') {
                const img = document.createElement('img');
                img.className = 'lightbox-img';
                img.src = fileUrl;
                img.style.cssText = 'max-width:90vw;max-height:80vh;border-radius:1rem;';
                img.onload = () => { lightboxContent.innerHTML = ''; lightboxContent.appendChild(img); };
                img.onerror = () => { lightboxContent.innerHTML = '<div style="padding:2rem;text-align:center;color:#f87171;"><i class="fas fa-circle-exclamation" style="font-size:3rem;display:block;margin-bottom:.8rem;"></i>Failed to load image</div>'; };
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(img);
            } else if (fileType === 'video') {
                const video = document.createElement('video');
                video.src = fileUrl;
                video.controls = true;
                video.autoplay = false;
                video.style.cssText = 'max-width:90vw;max-height:80vh;border-radius:1rem;';
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(video);
            } else if (fileType === 'pdf') {
                const embed = document.createElement('embed');
                embed.src = fileUrl;
                embed.type = 'application/pdf';
                embed.style.cssText = 'width:80vw;height:80vh;border-radius:1rem;';
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(embed);
            } else if (fileType === 'audio') {
                const div = document.createElement('div');
                div.style.cssText = 'text-align:center;padding:2.5rem;background:rgba(4,17,35,0.8);border-radius:1rem;border:1px solid rgba(56,189,248,0.15);';
                div.innerHTML = `<i class="fas fa-music" style="font-size:4rem;color:#06b6d4;display:block;margin-bottom:1.2rem;"></i><audio controls style="width:300px;max-width:80vw;"><source src="${fileUrl}" type="audio/mpeg"></audio>`;
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(div);
            } else if (fileType === 'code') {
                const codeViewer = await displayCodeContent(fileUrl, fileName);
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(codeViewer);
            } else {
                const div = document.createElement('div');
                div.style.cssText = 'text-align:center;padding:2.5rem;background:rgba(4,17,35,0.8);border-radius:1rem;border:1px solid rgba(56,189,248,0.15);';
                div.innerHTML = `<i class="fas fa-file" style="font-size:4rem;color:#64748b;display:block;margin-bottom:1rem;"></i><p style="margin-bottom:1.2rem;color:#6b90b0;">Preview unavailable for this file type</p><a href="${fileUrl}" download class="lightbox-download"><i class="fas fa-download"></i> Download ${fileName}</a>`;
                lightboxContent.innerHTML = '';
                lightboxContent.appendChild(div);
            }
            lightboxCaption.innerHTML = `<span style="color:var(--text-muted)">${fileName}</span><a href="${fileUrl}" download class="lightbox-download"><i class="fas fa-download"></i> Download</a>`;
        });
    });

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        const video = lightboxContent.querySelector('video');
        if (video) video.pause();
        const audio = lightboxContent.querySelector('audio');
        if (audio) audio.pause();
        setTimeout(() => { lightboxContent.innerHTML = ''; }, 200);
    }
    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox(); });

    document.querySelectorAll('.file-preview img').forEach(img => {
        if (img.complete) {
            img.style.opacity = '1';
        } else {
            img.style.opacity = '0';
            img.addEventListener('load', function() {
                this.style.transition = 'opacity 0.3s';
                this.style.opacity = '1';
            });
        }
    });
</script>
</body>
</html>
