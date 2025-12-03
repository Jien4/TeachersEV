<?php
require_once 'functions.php';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$user || !$pass) {
        $err = 'Fill required fields.';
    } else {
        $s = $conn->prepare('SELECT * FROM admins WHERE username = ?');
        $s->execute([$user]);
        $a = $s->fetch(PDO::FETCH_ASSOC);

        if ($a && password_verify($pass, $a['password_hash'])) {
            $_SESSION['admin_id'] = $a['id'];
            $_SESSION['admin_user'] = $a['username'];
            audit($conn, 'admin', $a['id'], 'admin_login', 'admin logged in');
            header('Location: admin_dashboard_full.php');
            exit;
        } else {
            $err = 'Invalid admin credentials.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — TeacherEval</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root {
    --accent: #0a43b8;
    --accent2: #052a73;
    --accent-light: #0d6efd;
    --muted: #adb5bd;
    --card-bg: #ffffff;
  }

  html, body {
    margin: 0;
    height: 100%;
    font-family: Inter, system-ui, sans-serif;
  }

  body {
    background: linear-gradient(135deg, var(--accent2) 0%, #0c3b9d 45%, #0d47c3 100%);
    color: #212529;
    position: relative;
    overflow-x: hidden;
  }

  body::before {
    content: "";
    position: absolute;
    top: -20%;
    left: -25%;
    width: 170%;
    height: 70%;
    background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    transform: rotate(-8deg);
    border-radius: 20px;
    z-index: 0;
  }

  body::after {
    content: "";
    position: absolute;
    bottom: -25%;
    right: -30%;
    width: 160%;
    height: 65%;
    background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.01));
    transform: rotate(10deg);
    border-radius: 20px;
    z-index: 0;
  }

  .topbar {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(6px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: white;
    position: relative;
    z-index: 10;
  }

  .topbar a { color: #dbe4ff; text-decoration: none; }
  .topbar a:hover { color: white; }

  .brand {
    color: #ffffff;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .55rem;
  }

  /* circular logo */
  .brand-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.15);
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    background: #fff;
  }

  .auth-card {
    max-width: 480px;
    margin: 70px auto;
    padding: 28px;
    border-radius: 14px;
    background: var(--card-bg);
    box-shadow: 0 18px 50px rgba(0,0,0,0.4);
    animation: fadeUp .5s ease;
    position: relative;
    z-index: 10;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .form-label { font-weight: 600; }
  .form-control:focus {
    border-color: var(--accent-light);
    box-shadow: 0 0 0 .15rem rgba(13,110,253,.25);
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border: none;
    padding: 10px 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
  }

  .btn-primary:hover {
    background: linear-gradient(135deg, #06328d, #041f58);
  }
</style>
</head>
<body>

<header class="topbar py-2">
  <div class="container d-flex justify-content-between align-items-center">

    <!-- UPDATED WITH CIRCULAR LOGO -->
    <div class="brand fs-5">
      <img src="mav.jpg" class="brand-logo" alt="School Logo">
      <span><i class="bi bi-shield-lock-fill me-1"></i> Teacher's Evaluations</span>
    </div>

    <nav class="small">
      <a href="login.php" class="me-3">Student</a>
      <a href="register.php">Register</a>
    </nav>
  </div>
</header>

<div class="auth-card">
  <div class="mb-3 text-center">
    <h4 class="mb-0">Admin Sign in</h4>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="d-grid mb-3">
      <button class="btn btn-primary" type="submit">
        <i class="bi bi-box-arrow-in-right me-1"></i> Login as Admin
      </button>
    </div>

    <div class="d-flex justify-content-between small text-muted mb-2">
      <a href="login.php">Back to Student Login</a>
      <a href="student_change_password.php">Forgot password?</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
