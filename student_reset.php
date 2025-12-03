<?php
// student_reset.php
require_once 'functions.php'; // must provide $conn (PDO), audit(), config starts session
$err = '';
$success = '';
$show_form = false;

// Get uid/token from GET or POST (POST when submitting)
$uid = $_REQUEST['uid'] ?? '';
$token = $_REQUEST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validate presence
    if (empty($uid) || empty($token)) {
        $err = 'Invalid or missing reset link.';
    } else {
        try {
            // Fetch stored reset row
            $stmt = $conn->prepare('SELECT * FROM password_resets WHERE student_id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $err = 'Invalid or expired reset link.';
            } else {
                // Verify token against hashed value
                if (!password_verify($token, $row['token'])) {
                    $err = 'Invalid or expired reset link.';
                } elseif (strtotime($row['expires_at']) < time()) {
                    $err = 'This reset link has expired.';
                } else {
                    // OK: show form
                    $show_form = true;
                }
            }
        } catch (PDOException $ex) {
            // Generic message
            $err = 'An error occurred. Please try again later.';
            // optionally log $ex->getMessage()
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We expect uid and token to be in POST as hidden fields
    $uid = $_POST['uid'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($uid) || empty($token)) {
        $err = 'Invalid request.';
    } elseif (empty($password) || empty($password2)) {
        $err = 'Please fill required fields.';
    } elseif ($password !== $password2) {
        $err = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $err = 'Password must be at least 6 characters.';
    } else {
        try {
            $stmt = $conn->prepare('SELECT * FROM password_resets WHERE student_id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $err = 'Invalid or expired reset link.';
            } elseif (!password_verify($token, $row['token'])) {
                $err = 'Invalid or expired reset link.';
            } elseif (strtotime($row['expires_at']) < time()) {
                $err = 'This reset link has expired.';
            } else {
                // Update student's password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $u = $conn->prepare('UPDATE students SET password = ? WHERE id = ?');
                $u->execute([$hash, $uid]);

                // Delete the reset token
                $d = $conn->prepare('DELETE FROM password_resets WHERE student_id = ?');
                $d->execute([$uid]);

                // Audit
                if (function_exists('audit')) {
                    try {
                        audit($conn, 'student', $uid, 'password_reset', 'password changed via reset link');
                    } catch (Throwable $_) {
                        // ignore audit errors
                    }
                }

                $success = 'Your password has been updated. You may now sign in.';
                // do not show form after success
                $show_form = false;
            }
        } catch (PDOException $ex) {
            $err = 'An unexpected error occurred. Please try again later.';
            // optionally log $ex->getMessage()
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset password — TeacherEval</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --blue1:#052a73;
      --blue2:#0c3b9d;
      --blue3:#0d47c3;
      --muted:#adb5bd;
      --card-bg:#ffffff;
    }

    html,body{
      height:100%;
      margin:0;
      font-family:Inter,system-ui,sans-serif;
      background:linear-gradient(135deg,var(--blue1) 0%,var(--blue2) 45%,var(--blue3) 100%);
      overflow-x:hidden;
      position:relative;
    }

    /* top slanted shine (to match other pages) */
    body::before{
      content:"";
      position:absolute;
      top:-20%;
      left:-25%;
      width:170%;
      height:70%;
      background:linear-gradient(135deg,rgba(255,255,255,0.07),rgba(255,255,255,0.03));
      transform:rotate(-8deg);
      border-radius:20px;
      z-index:0;
    }

    body::after{
      content:"";
      position:absolute;
      bottom:-25%;
      right:-30%;
      width:160%;
      height:65%;
      background:linear-gradient(135deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02));
      transform:rotate(10deg);
      border-radius:20px;
      z-index:0;
    }

    /* HEADER (same as other pages) */
    .topbar{
      background:rgba(255,255,255,0.08);
      backdrop-filter:blur(6px);
      border-bottom:1px solid rgba(255,255,255,0.08);
      box-shadow:0 2px 8px rgba(0,0,0,0.18);
      color:white;
      position:relative;
      z-index:20;
    }
    .topbar a{ color:#dce4ff; text-decoration:none; }
    .topbar a:hover{ color:#ffffff; }

    .brand{
      color:white;
      font-weight:700;
    }

    .brand-logo{
      width:40px;
      height:40px;
      border-radius:50%;
      object-fit:cover;
      border:2px solid rgba(255,255,255,0.15);
      box-shadow:0 4px 10px rgba(0,0,0,0.25);
      margin-right:10px;
      background:#fff;
    }

    @media(max-width:420px){
      .brand-logo{ width:32px; height:32px; }
    }

    /* card */
    .auth-card{
      max-width:520px;
      margin:6vh auto;
      padding:24px;
      border-radius:.85rem;
      box-shadow:0 16px 45px rgba(0,0,0,0.35);
      background:var(--card-bg);
      position:relative;
      z-index:10;
      animation:fadeUp .45s ease;
    }

    @keyframes fadeUp{
      from{opacity:0; transform:translateY(12px);}
      to{opacity:1; transform:translateY(0);}
    }

    .lead{ color:var(--muted); margin-bottom:16px; }
    .small-muted{ color:var(--muted); }

    /* form focus */
    .form-control:focus, .form-select:focus{
      border-color:#0d6efd;
      box-shadow:0 0 0 .12rem rgba(13,110,253,0.18);
    }

    .btn-primary{
      background:linear-gradient(135deg,#0a43b8,#052a73);
      border:0;
      padding:10px 14px;
      box-shadow:0 8px 20px rgba(0,0,0,0.28);
      font-weight:600;
    }
    .btn-primary:hover{
      background:linear-gradient(135deg,#06328d,#041f58);
    }

    .alert a{ color:inherit; text-decoration:underline; }
  </style>
</head>
<body>

  <!-- topbar header (same look as other pages) -->
  <header class="topbar py-2 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand fs-5 d-flex align-items-center">
        <img src="mav.jpg" class="brand-logo" alt="School Logo">
        <span><i class="bi bi-person-badge-fill"></i> Teacher's Evaluations</span>
      </div>
      <div class="small">
        <a href="login.php" class="me-3">Login</a>
        <a href="student_register.php">Register</a>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="auth-card">
      <h4 class="mb-1">Reset password</h4>
      <p class="lead">Choose a new password for your account.</p>

      <?php if ($err): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="text-center mt-3">
          <a href="login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Sign in</a>
        </div>
      <?php endif; ?>

      <?php if ($show_form): ?>
        <form method="post" novalidate>
          <input type="hidden" name="uid" value="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">At least 6 characters.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password2" class="form-control" required>
          </div>

          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle"></i> Update password</button>
        </form>
      <?php endif; ?>

      <div class="mt-3 small text-center">
        <a href="login.php">Back to login</a>
      </div>
    </div>
  </div>

</body>
</html>
