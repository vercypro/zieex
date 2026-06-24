<?php
declare(strict_types=1);

// Prevent direct access after installation
if (file_exists(__DIR__ . '/.installed')) {
    header('Location: /');
    exit;
}

$error   = '';
$success = false;
$step    = 'form'; // 'form' | 'done'

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    // Validate required fields
    $required = ['app_name', 'app_url', 'db_driver', 'db_host', 'db_name', 'admin_email', 'admin_password', 'admin_username'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $error = "Field '{$field}' is required.";
            break;
        }
    }

    if (!$error && !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid admin email address.";
    }

    if (!$error && strlen($data['admin_password']) < 8) {
        $error = "Admin password must be at least 8 characters.";
    }

    if (!$error) {
        // Test DB connection
        try {
            $driver = $data['db_driver'];
            $dsn = match ($driver) {
                'mysql'  => "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4",
                'pgsql'  => "pgsql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']}",
                'sqlite' => 'sqlite:' . BASE_PATH . '/storage/' . $data['db_name'] . '.sqlite',
                default  => throw new \Exception("Unsupported driver: {$driver}"),
            };

            $pdo = new PDO($dsn, $data['db_user'] ?? '', $data['db_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at DATETIME,
    updated_at DATETIME
)");

            $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255),
                ran_at DATETIME
            )");

            // Insert admin user
            $uuid = bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(4)) . '-4' .
        substr(bin2hex(random_bytes(2)), 1) . '-' . dechex(mt_rand(8, 11)) .
        substr(bin2hex(random_bytes(2)), 1) . '-' . bin2hex(random_bytes(6));

$hash = password_hash($data['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);
$now  = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("INSERT INTO users 
    (uuid, username, email, password, role, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
    $uuid,
    $data['admin_username'],
    $data['admin_email'],
    $hash,
    'admin',
    $now,
    $now
]);
        } catch (\Throwable $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }

    if (!$error) {
        // Write .env
        $appKey  = bin2hex(random_bytes(32));
        $jwtKey  = bin2hex(random_bytes(32));
        $env = <<<ENV
APP_NAME={$data['app_name']}
APP_URL={$data['app_url']}
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false

DB_DRIVER={$data['db_driver']}
DB_HOST={$data['db_host']}
DB_PORT={$data['db_port']}
DB_NAME={$data['db_name']}
DB_USER={$data['db_user']}
DB_PASS={$data['db_pass']}

JWT_SECRET={$jwtKey}

MAIL_DRIVER=mail
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME={$data['app_name']}
ENV;
        file_put_contents(BASE_PATH . '/.env', $env);

        // Mark installed
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        $step = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install Zieex</title>
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--bg:#0f0f1a;--surface:#1a1a2e;--surface2:#16213e;--accent:#6366f1;--accent2:#818cf8;--danger:#f38ba8;--success:#a6e3a1;--text:#cdd6f4;--muted:#6c7086;--border:#313244;--radius:10px}
  body{background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
  .container{width:100%;max-width:680px}
  .logo{text-align:center;margin-bottom:2rem}
  .logo h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
  .logo p{color:var(--muted);margin-top:.25rem;font-size:.9rem}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;margin-bottom:1.5rem}
  .section-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--accent2);margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid var(--border)}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
  .field{margin-bottom:1rem}
  .field label{display:block;font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:.4rem}
  .field input,.field select{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:.6rem .85rem;border-radius:6px;font-size:.9rem;transition:border-color .2s}
  .field input:focus,.field select:focus{outline:none;border-color:var(--accent)}
  .field select option{background:var(--surface2)}
  .field .hint{font-size:.75rem;color:var(--muted);margin-top:.3rem}
  .full{grid-column:1/-1}
  .alert{padding:.85rem 1rem;border-radius:6px;margin-bottom:1.5rem;font-size:.9rem}
  .alert--error{background:#2d1a1a;border:1px solid var(--danger);color:var(--danger)}
  .alert--success{background:#1a2d1a;border:1px solid var(--success);color:var(--success)}
  .btn{display:block;width:100%;padding:.85rem;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s;margin-top:.5rem}
  .btn:hover{background:var(--accent2)}
  .success-card{text-align:center;padding:3rem 2rem}
  .success-icon{font-size:3.5rem;margin-bottom:1rem}
  .success-card h2{font-size:1.75rem;font-weight:800;margin-bottom:.5rem}
  .success-card p{color:var(--muted);margin-bottom:1.5rem}
  .btn-outline{display:inline-block;padding:.75rem 2rem;border:1px solid var(--accent);color:var(--accent);border-radius:6px;text-decoration:none;font-weight:600;transition:all .2s}
  .btn-outline:hover{background:var(--accent);color:#fff}
  .db-fields{display:none}
  .db-fields.active{display:contents}
  .req-check{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem}
  .req-item{display:flex;align-items:center;gap:.5rem;font-size:.85rem}
  .req-item .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
  .dot-ok{background:var(--success)}.dot-warn{background:var(--danger)}
</style>
</head>
<body>
<div class="container">
  <div class="logo">
    <h1>⚡ Zieex</h1>
    <p>Lightweight PHP Framework — Installation</p>
  </div>

<?php if ($step === 'done'): ?>
  <div class="card success-card">
    <div class="success-icon">🎉</div>
    <h2>Installation Complete!</h2>
    <p>Zieex has been installed successfully. Your admin account is ready and the database has been configured.</p>
    <a href="/" class="btn-outline">Go to Application →</a>
  </div>
<?php else: ?>

  <?php
    // Requirements check
    $checks = [
      'PHP 8.2+' => PHP_VERSION_ID >= 80200,
      'PDO extension' => extension_loaded('pdo'),
      'Writable /storage' => is_writable(BASE_PATH . '/storage'),
      'Writable /install' => is_writable(__DIR__),
    ];
  ?>

  <div class="card">
    <div class="section-title">System Requirements</div>
    <div class="req-check">
      <?php foreach ($checks as $label => $ok): ?>
        <div class="req-item">
          <div class="dot <?= $ok ? 'dot-ok' : 'dot-warn' ?>"></div>
          <span><?= htmlspecialchars($label) ?> <?= $ok ? '' : '— <b style="color:var(--danger)">Not met</b>' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert--error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="card">
      <div class="section-title">Application</div>
      <div class="grid">
        <div class="field">
          <label>App Name</label>
          <input type="text" name="app_name" value="<?= htmlspecialchars($_POST['app_name'] ?? 'My App') ?>" required>
        </div>
        <div class="field">
          <label>App URL</label>
          <input type="url" name="app_url" value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost') ?>" required>
          <div class="hint">e.g. https://yourdomain.com (no trailing slash)</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="section-title">Database</div>
      <div class="grid">
        <div class="field full">
          <label>Database Driver</label>
          <select name="db_driver" id="db_driver" onchange="toggleDbFields(this.value)">
            <option value="mysql" <?= ($_POST['db_driver']??'mysql')==='mysql'?'selected':'' ?>>MySQL</option>
            <option value="pgsql" <?= ($_POST['db_driver']??'')==='pgsql'?'selected':'' ?>>PostgreSQL</option>
            <option value="sqlite" <?= ($_POST['db_driver']??'')==='sqlite'?'selected':'' ?>>SQLite</option>
          </select>
        </div>
        <div id="mysql-fields" class="db-fields active">
          <div class="field">
            <label>Host</label>
            <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '127.0.0.1') ?>">
          </div>
          <div class="field">
            <label>Port</label>
            <input type="text" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
          </div>
          <div class="field">
            <label>Database Name</label>
            <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'zieex') ?>">
          </div>
          <div class="field">
            <label>Username</label>
            <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
          </div>
          <div class="field">
            <label>Password</label>
            <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="section-title">Admin Account</div>
      <div class="grid">
        <div class="field">
          <label>Username</label>
          <input type="text" name="admin_username" value="<?= htmlspecialchars($_POST['admin_username'] ?? 'admin') ?>" required>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
        </div>
        <div class="field full">
          <label>Password</label>
          <input type="password" name="admin_password" minlength="8" required>
          <div class="hint">Minimum 8 characters</div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn">Install Zieex →</button>
  </form>
<?php endif; ?>
</div>

<script>
function toggleDbFields(driver) {
  document.querySelectorAll('.db-fields').forEach(el => el.classList.remove('active'));
  const map = { mysql: 'mysql', pgsql: 'mysql', sqlite: 'sqlite' };
  const el = document.getElementById((driver === 'sqlite' ? 'sqlite' : 'mysql') + '-fields');
  if (el) el.classList.add('active');
}
</script>
</body>
</html>
