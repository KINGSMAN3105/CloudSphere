<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db_functions.php';

$error_message = "";
$success_message = "";

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password";
    } else {
        $user = getUserByUsername($username);
        
        if ($user && password_verify($password, $user['passwordHash'])) {
            $_SESSION['user_id'] = $user['userId'];
            $_SESSION['username'] = $user['username'];
            header("Location: upload.php");
            exit();
        } else {
            $error_message = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CloudSphere — Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --sky-deep:   #04111f;
            --sky-mid:    #061e36;
            --sky-light:  #0d2d4a;
            --cloud-blue: #1d7fd4;
            --cloud-cyan: #22d3ee;
            --cloud-teal: #0ea5e9;
            --accent:     #38bdf8;
            --accent-glow:rgba(56,189,248,0.25);
            --text-primary:#e8f4ff;
            --text-muted:  #6b90b0;
            --text-dim:    #304d66;
            --border:      rgba(56,189,248,0.15);
            --border-mid:  rgba(56,189,248,0.3);
            --glass:       rgba(4,20,38,0.82);
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            min-height: 100vh;
            background: var(--sky-deep);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Layered sky background */
        .sky-layer {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 100% 60% at 50% 0%, rgba(13,60,100,0.9) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(14,165,233,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 20% 80%, rgba(6,30,54,0.8) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Animated cloud wisps */
        .cloud-wisp {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            will-change: transform;
        }
        .wisp-1 {
            width: 700px; height: 200px;
            background: radial-gradient(ellipse, rgba(56,189,248,0.06) 0%, transparent 70%);
            top: 10%; left: -10%;
            animation: driftCloud 30s infinite alternate ease-in-out;
            z-index: 0;
        }
        .wisp-2 {
            width: 500px; height: 150px;
            background: radial-gradient(ellipse, rgba(14,165,233,0.08) 0%, transparent 70%);
            top: 35%; right: -5%;
            animation: driftCloud 22s infinite alternate-reverse ease-in-out;
            z-index: 0;
        }
        .wisp-3 {
            width: 400px; height: 120px;
            background: radial-gradient(ellipse, rgba(34,211,238,0.05) 0%, transparent 70%);
            bottom: 20%; left: 15%;
            animation: driftCloud 26s 2s infinite alternate ease-in-out;
            z-index: 0;
        }
        @keyframes driftCloud {
            0%   { transform: translateX(0) translateY(0); }
            100% { transform: translateX(40px) translateY(15px); }
        }

        /* Floating particles */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0;
            animation: particleRise linear infinite;
        }
        @keyframes particleRise {
            0%   { opacity: 0; transform: translateY(0) translateX(0); }
            10%  { opacity: 0.6; }
            90%  { opacity: 0.2; }
            100% { opacity: 0; transform: translateY(-80vh) translateX(20px); }
        }

        /* Horizon line */
        .horizon {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56,189,248,0.3), transparent);
            z-index: 0;
        }

        /* Card */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            border: 1px solid var(--border);
            box-shadow:
                0 0 0 1px rgba(56,189,248,0.05),
                0 30px 60px rgba(0,0,0,0.5),
                0 0 80px rgba(14,165,233,0.06) inset;
            padding: 2.2rem 2rem 2.4rem;
            animation: cardRise 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes cardRise {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Top accent bar */
        .login-container::before {
            content: '';
            position: absolute;
            top: -1px; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cloud-teal), var(--cloud-cyan), transparent);
            border-radius: 2px;
        }

        /* Header */
        .header { text-align: center; margin-bottom: 2.2rem; }

        .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px; height: 52px;
            background: linear-gradient(145deg, rgba(14,165,233,0.2), rgba(56,189,248,0.1));
            border: 1px solid var(--border-mid);
            border-radius: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .logo-mark::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 1.2rem;
            background: linear-gradient(145deg, rgba(14,165,233,0.15), transparent);
            z-index: -1;
        }
        .logo-mark i {
            font-size: 1.6rem;
            background: linear-gradient(135deg, var(--cloud-teal), var(--cloud-cyan));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 1.9rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 30%, var(--accent) 80%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.03em;
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(56,189,248,0.08);
            padding: 0.25rem 0.9rem;
            border-radius: 40px;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: var(--accent);
            border: 1px solid rgba(56,189,248,0.2);
            text-transform: uppercase;
            margin-bottom: 0.7rem;
        }
        .badge i { font-size: 0.55rem; }

        .sub {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 400;
            margin-top: 0.3rem;
        }

        /* Inputs */
        .input-group {
            margin-bottom: 1.1rem;
            position: relative;
        }
        .input-group .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.9rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 2;
        }
        input {
            width: 100%;
            background: rgba(4,17,35,0.7);
            border: 1.5px solid rgba(56,189,248,0.18);
            border-radius: 0.75rem;
            padding: 0.85rem 1rem 0.85rem 2.7rem;
            font-size: 0.9rem;
            color: var(--text-primary);
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus {
            border-color: var(--cloud-teal);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12), 0 0 12px rgba(14,165,233,0.08);
        }
        input:focus + .icon, .input-group:has(input:focus) .icon { color: var(--accent); }
        input::placeholder { color: var(--text-dim); }

        /* Button */
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border: none;
            padding: 0.9rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s, filter 0.15s;
            margin-top: 0.8rem;
            box-shadow: 0 4px 20px rgba(14,165,233,0.35);
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
        }
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transform: skewX(-20deg);
            transition: left 0.4s;
        }
        .login-btn:hover::before { left: 160%; }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(14,165,233,0.45);
            filter: brightness(1.05);
        }
        .login-btn:active { transform: translateY(0); }

        /* Error */
        .message {
            border-radius: 0.65rem;
            padding: 0.7rem 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.82rem;
            transition: opacity 0.3s;
        }
        .message-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }
        .message i { font-size: 0.85rem; flex-shrink: 0; }

        /* Footer link */
        .register-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .register-link span { color: var(--accent); transition: color 0.2s; }
        .register-link:hover { color: var(--text-primary); }
        .register-link:hover span { color: var(--cloud-cyan); }

        footer {
            text-align: center;
            font-size: 0.62rem;
            margin-top: 1.8rem;
            color: var(--text-dim);
            letter-spacing: 0.5px;
        }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .login-container { padding: 1.8rem 1.4rem; }
            .brand-name { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
<div class="sky-layer"></div>
<div class="cloud-wisp wisp-1"></div>
<div class="cloud-wisp wisp-2"></div>
<div class="cloud-wisp wisp-3"></div>
<div class="horizon"></div>

<!-- Particles -->
<div class="particles" id="particles"></div>

<div class="login-container">
    <div class="header">
        <div class="logo-mark">
            <i class="fas fa-cloud"></i>
        </div>
        <div class="badge"><i class="fas fa-shield-alt"></i> Secure Access</div>
        <div class="brand-name">CloudSphere</div>
        <div class="sub">Sign in to your personal cloud drive</div>
    </div>

    <form method="POST" id="loginForm">
        <div class="input-group">
            <input type="text" name="username" placeholder="Username" required autocomplete="username">
            <i class="fas fa-user icon"></i>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <i class="fas fa-lock icon"></i>
        </div>
        
        <button type="submit" id="loginBtn" class="login-btn">
            <i class="fas fa-arrow-right-to-bracket"></i>
            <span>Sign In</span>
        </button>
    </form>

    <?php if(!empty($error_message)): ?>
    <div class="message message-error" id="errorMessage">
        <i class="fas fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error_message); ?></span>
    </div>
    <?php endif; ?>

    <a href="register.php" class="register-link">
        Don't have an account? <span>Create one →</span>
    </a>

    <footer>End-to-end encrypted · Powered by AWS S3 & DynamoDB</footer>
</div>

<script>
    // Generate particles
    const container = document.getElementById('particles');
    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            bottom: ${Math.random() * 20}%;
            animation-duration: ${8 + Math.random() * 14}s;
            animation-delay: ${Math.random() * 10}s;
            opacity: 0;
            width: ${1 + Math.random() * 2}px;
            height: ${1 + Math.random() * 2}px;
        `;
        container.appendChild(p);
    }

    // Form handling
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const originalBtnHTML = loginBtn.innerHTML;

    loginForm.addEventListener('submit', function(e) {
        const username = loginForm.querySelector('input[name="username"]').value.trim();
        const password = loginForm.querySelector('input[name="password"]').value;
        if (!username || !password) { e.preventDefault(); return false; }
        loginBtn.innerHTML = '<span class="spinner"></span> Authenticating...';
        loginBtn.disabled = true;
        setTimeout(() => {
            if (loginBtn.disabled) { loginBtn.innerHTML = originalBtnHTML; loginBtn.disabled = false; }
        }, 8000);
    });

    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', () => {
                errorDiv.style.opacity = '0';
                setTimeout(() => { if(errorDiv) errorDiv.style.display = 'none'; }, 300);
            });
        });
    }
</script>
</body>
</html>
