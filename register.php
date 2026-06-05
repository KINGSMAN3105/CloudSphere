<?php
require_once 'includes/db_functions.php';

$success_message = "";
$error_message = "";

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = "All fields are required";
    } elseif (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error_message = "Username can only contain letters, numbers, and underscores";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        $existingUser = getUserByUsername($username);
        if ($existingUser) {
            $error_message = "Username already taken. Please choose another.";
        } else {
            $userId = uniqid('user_');
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $result = createUser($userId, $username, $hashed_password);
            if ($result['success']) {
                $success_message = "Account created! Redirecting to sign in...";
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CloudSphere — Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --sky-deep:   #04111f;
            --sky-mid:    #061e36;
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

        .sky-layer {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 100% 60% at 50% 0%, rgba(13,60,100,0.9) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 20% 30%, rgba(14,165,233,0.1) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 70%, rgba(6,30,54,0.8) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        .cloud-wisp {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            will-change: transform;
        }
        .wisp-1 {
            width: 700px; height: 200px;
            background: radial-gradient(ellipse, rgba(56,189,248,0.06) 0%, transparent 70%);
            top: 5%; right: -10%;
            animation: driftCloud 28s infinite alternate ease-in-out;
            z-index: 0;
        }
        .wisp-2 {
            width: 500px; height: 140px;
            background: radial-gradient(ellipse, rgba(14,165,233,0.07) 0%, transparent 70%);
            bottom: 15%; left: -5%;
            animation: driftCloud 20s infinite alternate-reverse ease-in-out;
            z-index: 0;
        }
        @keyframes driftCloud {
            0%   { transform: translateX(0) translateY(0); }
            100% { transform: translateX(30px) translateY(12px); }
        }

        .particles {
            position: fixed; inset: 0;
            z-index: 0; pointer-events: none; overflow: hidden;
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
            0%   { opacity: 0; transform: translateY(0); }
            10%  { opacity: 0.5; }
            90%  { opacity: 0.1; }
            100% { opacity: 0; transform: translateY(-80vh); }
        }

        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
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
            animation: cardRise 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes cardRise {
            from { opacity:0; transform: translateY(24px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: -1px; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cloud-teal), var(--cloud-cyan), transparent);
            border-radius: 2px;
        }

        .header { text-align: center; margin-bottom: 2rem; }

        .logo-mark {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 52px; height: 52px;
            background: linear-gradient(145deg, rgba(14,165,233,0.2), rgba(56,189,248,0.1));
            border: 1px solid var(--border-mid);
            border-radius: 1rem;
            margin-bottom: 1rem;
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
            align-items: center; gap: 0.4rem;
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

        .sub {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .input-group {
            margin-bottom: 1.1rem;
            position: relative;
        }
        .input-group .icon {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.9rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 2;
        }
        /* For password group with strength meter, icon is at top of input not middle */
        .input-group.has-meter .icon {
            top: 1.1rem;
            transform: none;
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
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        input::placeholder { color: var(--text-dim); }

        /* Password strength */
        .strength-row {
            display: flex;
            gap: 4px;
            margin-top: 6px;
        }
        .strength-seg {
            flex: 1;
            height: 3px;
            background: rgba(255,255,255,0.07);
            border-radius: 3px;
            transition: background 0.25s;
        }
        .strength-label {
            font-size: 0.62rem;
            color: var(--text-dim);
            text-align: right;
            margin-top: 3px;
            transition: color 0.2s;
        }

        .register-btn {
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
            position: relative; overflow: hidden;
        }
        .register-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transform: skewX(-20deg);
            transition: left 0.4s;
        }
        .register-btn:hover::before { left: 160%; }
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(14,165,233,0.45);
        }
        .register-btn:active { transform: translateY(0); }

        .message {
            border-radius: 0.65rem;
            padding: 0.7rem 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.82rem;
        }
        .message-success {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.25);
            color: #6ee7b7;
        }
        .message-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }
        .message i { font-size: 0.85rem; flex-shrink: 0; }

        .login-link {
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
        .login-link span { color: var(--accent); transition: color 0.2s; }
        .login-link:hover { color: var(--text-primary); }
        .login-link:hover span { color: var(--cloud-cyan); }

        footer {
            text-align: center;
            font-size: 0.62rem;
            margin-top: 1.8rem;
            color: var(--text-dim);
            letter-spacing: 0.5px;
        }

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
            .register-container { padding: 1.8rem 1.4rem; }
            .brand-name { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
<div class="sky-layer"></div>
<div class="cloud-wisp wisp-1"></div>
<div class="cloud-wisp wisp-2"></div>
<div class="particles" id="particles"></div>

<div class="register-container">
    <div class="header">
        <div class="logo-mark"><i class="fas fa-cloud"></i></div>
        <div class="badge"><i class="fas fa-user-plus"></i> New Account</div>
        <div class="brand-name">CloudSphere</div>
        <div class="sub">Create your secure cloud storage account</div>
    </div>

    <form method="POST" id="registerForm">
        <div class="input-group">
            <input type="text" name="username" id="username" placeholder="Username (min. 3 chars)" required autocomplete="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <i class="fas fa-user icon"></i>
        </div>

        <div class="input-group has-meter">
            <input type="password" name="password" id="password" placeholder="Password (min. 6 chars)" required autocomplete="new-password">
            <i class="fas fa-lock icon"></i>
            <div class="strength-row" id="strengthRow">
                <div class="strength-seg" id="seg1"></div>
                <div class="strength-seg" id="seg2"></div>
                <div class="strength-seg" id="seg3"></div>
                <div class="strength-seg" id="seg4"></div>
                <div class="strength-seg" id="seg5"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Password strength</div>
        </div>

        <div class="input-group">
            <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required autocomplete="off">
            <i class="fas fa-check-circle icon"></i>
        </div>

        <button type="submit" id="registerBtn" class="register-btn">
            <i class="fas fa-cloud-arrow-up"></i>
            <span>Create Account</span>
        </button>
    </form>

    <?php if(!empty($success_message)): ?>
    <div class="message message-success" id="successMessage">
        <i class="fas fa-circle-check"></i>
        <span><?php echo htmlspecialchars($success_message); ?></span>
    </div>
    <script>setTimeout(() => { window.location.href = "login.php"; }, 2000);</script>
    <?php endif; ?>

    <?php if(!empty($error_message)): ?>
    <div class="message message-error" id="errorMessage">
        <i class="fas fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error_message); ?></span>
    </div>
    <?php endif; ?>

    <a href="login.php" class="login-link">
        Already have an account? <span>Sign in →</span>
    </a>

    <footer>End-to-end encrypted · Powered by AWS S3 & DynamoDB</footer>
</div>

<script>
    // Particles
    const container = document.getElementById('particles');
    for (let i = 0; i < 15; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `left:${Math.random()*100}%; bottom:${Math.random()*20}%; animation-duration:${8+Math.random()*14}s; animation-delay:${Math.random()*10}s;`;
        container.appendChild(p);
    }

    // Password strength
    const passwordInput = document.getElementById('password');
    const confirmInput  = document.getElementById('confirmPassword');
    const segs = [document.getElementById('seg1'), document.getElementById('seg2'), document.getElementById('seg3'), document.getElementById('seg4'), document.getElementById('seg5')];
    const strengthLabel = document.getElementById('strengthLabel');
    const colors = ['#ef4444','#f97316','#eab308','#22c55e','#0ea5e9'];
    const labels = ['Very Weak','Weak','Fair','Strong','Excellent'];

    function checkStrength(pw) {
        let s = 0;
        if (pw.length >= 6)  s++;
        if (pw.length >= 10) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return Math.min(5, s);
    }

    passwordInput.addEventListener('input', function() {
        const pw = this.value;
        if (pw.length === 0) {
            segs.forEach(s => s.style.background = 'rgba(255,255,255,0.07)');
            strengthLabel.textContent = 'Password strength';
            strengthLabel.style.color = 'var(--text-dim)';
            return;
        }
        const score = checkStrength(pw);
        const color = colors[Math.max(0, score-1)];
        segs.forEach((seg, i) => {
            seg.style.background = i < score ? color : 'rgba(255,255,255,0.07)';
        });
        strengthLabel.textContent = labels[Math.max(0, score-1)];
        strengthLabel.style.color = color;
    });

    confirmInput.addEventListener('input', function() {
        if (this.value.length === 0) { this.style.borderColor = 'rgba(56,189,248,0.18)'; return; }
        const match = passwordInput.value === this.value;
        this.style.borderColor = match ? '#22c55e' : '#ef4444';
        this.style.boxShadow   = match ? '0 0 0 3px rgba(34,197,94,0.12)' : '0 0 0 3px rgba(239,68,68,0.1)';
    });

    // Submit
    const registerForm = document.getElementById('registerForm');
    const registerBtn  = document.getElementById('registerBtn');
    const origBtnHTML  = registerBtn.innerHTML;

    registerForm.addEventListener('submit', function(e) {
        const u = document.getElementById('username').value.trim();
        const p = passwordInput.value;
        const c = confirmInput.value;
        if (u.length < 3 || p.length < 6 || p !== c) { e.preventDefault(); return false; }
        registerBtn.innerHTML = '<span class="spinner"></span> Creating account...';
        registerBtn.disabled = true;
        setTimeout(() => { if(registerBtn.disabled) { registerBtn.innerHTML = origBtnHTML; registerBtn.disabled = false; } }, 8000);
    });

    const errorMsgDiv = document.getElementById('errorMessage');
    if (errorMsgDiv && !document.getElementById('successMessage')) {
        setTimeout(() => { errorMsgDiv.style.opacity = '0'; setTimeout(() => { if(errorMsgDiv) errorMsgDiv.style.display='none'; }, 300); }, 4000);
    }
</script>
</body>
</html>
