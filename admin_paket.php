<?php
session_start();

$ADMIN_USER = 'admin';
$ADMIN_PASS = 'Pangandaran.in';

if (isset($_POST['login'])) {
    if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_paket.php');
    exit;
}

if (!isset($_SESSION['admin'])) { ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Pangandaran.in</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#00132f;color:#d6e3ff;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
        .bg-glow{position:fixed;inset:0;pointer-events:none}
        .glow1{position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(0,89,179,0.3),transparent 70%);top:-200px;left:-200px}
        .glow2{position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(0,54,66,0.25),transparent 70%);bottom:-150px;right:-100px}
        .card{position:relative;z-index:1;width:400px;background:rgba(5,27,57,0.8);border:1px solid rgba(162,231,255,0.1);border-radius:24px;padding:44px 40px;backdrop-filter:blur(20px)}
        .logo-area{margin-bottom:36px}
        .logo-name{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:#fff;letter-spacing:-0.5px}
        .logo-name span{color:#a2e7ff}
        .logo-desc{font-size:11px;color:rgba(162,231,255,0.4);margin-top:4px;text-transform:uppercase;letter-spacing:2px}
        h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:8px;letter-spacing:-0.5px}
        .subtitle{font-size:13px;color:rgba(214,227,255,0.5);margin-bottom:32px;line-height:1.5}
        .field{margin-bottom:16px}
        .field label{font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:1px;color:rgba(162,231,255,0.5);display:block;margin-bottom:8px}
        .field input{width:100%;padding:13px 16px;border-radius:12px;border:1px solid rgba(162,231,255,0.12);background:rgba(10,31,61,0.9);color:#d6e3ff;font-size:14px;font-family:'Inter',sans-serif;outline:none;transition:border 0.2s,box-shadow 0.2s}
        .field input:focus{border-color:rgba(162,231,255,0.3);box-shadow:0 0 0 3px rgba(162,231,255,0.06)}
        .error{background:rgba(255,100,100,0.1);border:1px solid rgba(255,100,100,0.2);color:#ff8080;padding:11px 16px;border-radius:10px;font-size:13px;margin-bottom:16px}
        .btn-login{width:100%;padding:14px;border-radius:12px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:14px;border:none;cursor:pointer;letter-spacing:0.3px;transition:all 0.2s}
        .btn-login:hover{transform:translateY(-1px);box-shadow:0 10px 30px rgba(0,89,179,0.4)}
    </style>
</head>
<body>
<div class="bg-glow"><div class="glow1"></div><div class="glow2"></div></div>
<div class="card">
    <div class="logo-area">
        <div class="logo-name">Pangandaran<span>.in</span></div>
        <div class="logo-desc">Admin Panel</div>
    </div>
    <h1>Masuk</h1>
    <p class="subtitle">Kelola paket wisata, harga, dan foto website.</p>
    <?php if (isset($loginError)): ?>
    <div class="error">Username atau password salah.</div>
    <?php endif; ?>
    <form method="POST">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" placeholder="admin" required autocomplete="off">
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <input type="hidden" name="login" value="1">
        <button type="submit" class="btn-login">Masuk ke Dashboard</button>
    </form>
</div>
</body>
</html>
<?php exit; }

$conn = new mysqli("localhost", "root", "", "pangandaran_db");
if($conn->connect_error){ die("Koneksi DB gagal: ".$conn->connect_error); }
$conn->set_charset("utf8mb4");

// Auto-create tabel paket jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS `paket` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT,
    `harga` INT DEFAULT 0,
    `kategori` VARCHAR(50) DEFAULT 'other',
    `gambar` VARCHAR(255),
    `aktif` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = '';
$messageType = '';

if (isset($_GET['hapus'])) {
    $hapusId = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM paket WHERE id=?");
    $stmt->bind_param("s", $hapusId);
    $stmt->execute();
    header("Location: admin_paket.php?msg=".urlencode("Paket berhasil dihapus.")."&mt=success");
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['mt'] ?? 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_paket'])) {
    $id       = strtolower(preg_replace('/[^a-z0-9]/', '_', trim($_POST['id_paket'])));
    $nama     = trim($_POST['nama']);
    $kategori = $_POST['kategori'];
    $subtitle = trim($_POST['subtitle']);
    $deskripsi= trim($_POST['deskripsi']);
    $harga    = intval($_POST['harga']);
    $unit     = $_POST['unit'];
    $durasi   = trim($_POST['durasi']);
    $wa       = '6287793827592';
    $foto = [];
    for ($i = 1; $i <= 3; $i++) {
        $foto[$i] = '';
        if (!empty($_FILES["foto$i"]['name'])) {
            $ext = strtolower(pathinfo($_FILES["foto$i"]['name'], PATHINFO_EXTENSION));
            $newName = "assets/uploads/paket_{$id}_{$i}.{$ext}";
            if (!is_dir('assets/uploads')) mkdir('assets/uploads', 0777, true);
            if (move_uploaded_file($_FILES["foto$i"]['tmp_name'], $newName)) $foto[$i] = $newName;
        }
    }
    $cek = $conn->prepare("SELECT id FROM paket WHERE id=?");
    $cek->bind_param("s", $id);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        $message = "ID '$id' sudah digunakan. Gunakan ID lain.";
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO paket (id,nama,kategori,subtitle,deskripsi,harga,unit,durasi,foto1,foto2,foto3,wa) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssissssss", $id,$nama,$kategori,$subtitle,$deskripsi,$harga,$unit,$durasi,$foto[1],$foto[2],$foto[3],$wa);
        if ($stmt->execute()) {
            header("Location: admin_paket.php?edit=$id&msg=".urlencode("Paket '$nama' berhasil ditambahkan.")."&mt=success");
            exit;
        } else {
            $message = "Gagal menyimpan: ".$stmt->error;
            $messageType = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_paket'])) {
    $id       = $_POST['id'];
    $nama     = trim($_POST['nama']);
    $subtitle = trim($_POST['subtitle']);
    $deskripsi= trim($_POST['deskripsi']);
    $harga    = intval($_POST['harga']);
    $unit     = $_POST['unit'];
    $durasi   = trim($_POST['durasi']);
    $foto = [];
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES["foto$i"]['name'])) {
            $ext = strtolower(pathinfo($_FILES["foto$i"]['name'], PATHINFO_EXTENSION));
            $newName = "assets/uploads/paket_{$id}_{$i}.{$ext}";
            if (!is_dir('assets/uploads')) mkdir('assets/uploads', 0777, true);
            move_uploaded_file($_FILES["foto$i"]['tmp_name'], $newName);
            $foto[$i] = $newName;
        } else {
            $foto[$i] = $_POST["foto{$i}_current"];
        }
    }
    $stmt = $conn->prepare("UPDATE paket SET nama=?,subtitle=?,deskripsi=?,harga=?,unit=?,durasi=?,foto1=?,foto2=?,foto3=? WHERE id=?");
    $stmt->bind_param("sssissssss", $nama,$subtitle,$deskripsi,$harga,$unit,$durasi,$foto[1],$foto[2],$foto[3],$id);
    if ($stmt->execute()) {
        header("Location: admin_paket.php?edit=$id&msg=".urlencode("Perubahan pada '$nama' berhasil disimpan.")."&mt=success");
        exit;
    } else {
        $message = "Gagal: ".$stmt->error;
        $messageType = 'error';
    }
}

$pakets = [];
$result = $conn->query("SELECT * FROM paket ORDER BY kategori, nama");
if($result) while ($row = $result->fetch_assoc()) $pakets[] = $row;

$editPaket = null;
$mode = $_GET['mode'] ?? '';
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM paket WHERE id=?");
    $stmt->bind_param("s", $_GET['edit']);
    $stmt->execute();
    $gr = $stmt->get_result(); $editPaket = $gr ? $gr->fetch_assoc() : null;
}

$katNames = ['river'=>'River Adventure','sea'=>'Sea Adventure','multi'=>'Multi Day Trip','outbound'=>'Outbound'];
$katCount = [];
foreach($pakets as $p) $katCount[$p['kategori']] = ($katCount[$p['kategori']] ?? 0) + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Pangandaran.in</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --surface:#00132f;
            --surface-low:#051b39;
            --surface-container:#0a1f3d;
            --surface-high:#172a48;
            --border:rgba(162,231,255,0.1);
            --border-subtle:rgba(162,231,255,0.06);
            --cyan:#a2e7ff;
            --blue:#aac7ff;
            --text:#d6e3ff;
            --muted:rgba(214,227,255,0.45);
            --sidebar-w:268px
        }
        html,body{height:100%;overflow:hidden}
        body{font-family:'Inter',sans-serif;background:var(--surface);color:var(--text);display:flex;flex-direction:column}
        ::-webkit-scrollbar{width:3px}
        ::-webkit-scrollbar-thumb{background:rgba(162,231,255,0.1);border-radius:10px}

        /* NAV */
        .nav{height:56px;background:rgba(0,14,37,0.97);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;flex-shrink:0;position:relative;z-index:50}
        .nav-left{display:flex;align-items:center;gap:20px}
        .nav-brand{display:flex;align-items:center;gap:8px}
        .nav-brand-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan)}
        .nav-brand-name{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;color:#fff;letter-spacing:-0.3px}
        .nav-brand-name span{color:var(--cyan)}
        .nav-divider{width:1px;height:20px;background:var(--border)}
        .nav-label{font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);font-weight:500}
        .nav-right{display:flex;align-items:center;gap:8px}
        .nav-link{padding:7px 14px;border-radius:8px;font-size:13px;color:var(--muted);text-decoration:none;font-weight:400;transition:all 0.15s}
        .nav-link:hover{background:rgba(162,231,255,0.06);color:var(--text)}
        .nav-link.active{background:rgba(162,231,255,0.08);color:var(--text)}
        .nav-btn{padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:12px;text-decoration:none;letter-spacing:0.3px;transition:all 0.2s;border:none;cursor:pointer}
        .nav-btn:hover{opacity:0.9;transform:translateY(-1px)}
        .nav-logout{font-size:12px;color:rgba(255,150,150,0.5);text-decoration:none;padding:7px 12px;border-radius:8px;transition:all 0.15s}
        .nav-logout:hover{color:#ff8080;background:rgba(255,100,100,0.06)}

        /* BODY LAYOUT */
        .body{display:flex;flex:1;overflow:hidden}

        /* SIDEBAR */
        .sidebar{width:var(--sidebar-w);background:rgba(0,14,37,0.6);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;backdrop-filter:blur(12px)}
        .sidebar-header{padding:16px 18px 10px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-subtle)}
        .sidebar-title{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:var(--muted)}
        .sidebar-count{font-size:11px;background:rgba(162,231,255,0.08);color:var(--cyan);padding:2px 8px;border-radius:20px;border:1px solid rgba(162,231,255,0.12);font-weight:500}
        .sidebar-body{overflow-y:auto;flex:1;padding:8px 0}
        .kat-label{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:rgba(162,231,255,0.25);padding:14px 18px 5px}
        .pkg-item{display:flex;align-items:center;gap:10px;padding:8px 12px;margin:1px 8px;border-radius:10px;text-decoration:none;color:inherit;transition:all 0.15s;border:1px solid transparent;position:relative}
        .pkg-item:hover{background:rgba(162,231,255,0.04);border-color:rgba(162,231,255,0.06)}
        .pkg-item.active{background:rgba(162,231,255,0.08);border-color:rgba(162,231,255,0.1)}
        .pkg-img{width:34px;height:34px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid var(--border)}
        .pkg-img-ph{width:34px;height:34px;border-radius:8px;background:var(--surface-container);flex-shrink:0;border:1px solid var(--border);display:flex;align-items:center;justify-content:center}
        .pkg-img-ph-icon{width:14px;height:14px;opacity:0.2}
        .pkg-info{min-width:0;flex:1}
        .pkg-name{font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
        .pkg-price{font-size:11px;color:var(--cyan);margin-top:1px}
        .pkg-del{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:rgba(255,100,100,0.2);text-decoration:none;font-size:11px;font-weight:700;transition:all 0.15s;flex-shrink:0}
        .pkg-del:hover{background:rgba(255,100,100,0.08);color:#ff8080}

        /* MAIN */
        .main{flex:1;overflow-y:auto;padding:28px 32px;background:var(--surface)}

        /* TOAST */
        .toast{display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;margin-bottom:24px;font-size:13px;animation:fadeDown 0.3s ease}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .toast.success{background:rgba(111,220,150,0.08);border:1px solid rgba(111,220,150,0.18);color:#6fdc96}
        .toast.error{background:rgba(255,128,128,0.08);border:1px solid rgba(255,128,128,0.18);color:#ff8080}
        .toast-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
        .toast.success .toast-dot{background:#6fdc96}
        .toast.error .toast-dot{background:#ff8080}

        /* PLACEHOLDER */
        .placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;height:calc(100vh - 160px);text-align:center}
        .placeholder-icon{width:48px;height:48px;border-radius:14px;background:var(--surface-container);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
        .placeholder-icon svg{opacity:0.2}
        .placeholder h2{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:600;margin-bottom:8px}
        .placeholder p{font-size:13px;color:var(--muted);line-height:1.6;max-width:280px}

        /* PAGE HEADER */
        .page-header{margin-bottom:24px}
        .page-tag{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:var(--muted);margin-bottom:6px}
        .page-title{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;letter-spacing:-0.3px}
        .page-id{font-size:12px;color:var(--muted);margin-top:4px}

        /* FORM */
        .form-block{background:var(--surface-low);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:12px}
        .form-block-header{padding:16px 20px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:10px}
        .form-block-num{width:20px;height:20px;border-radius:6px;background:rgba(162,231,255,0.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--cyan);flex-shrink:0}
        .form-block-title{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted)}
        .form-block-body{padding:20px}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .fg{display:flex;flex-direction:column;gap:7px}
        .fg.full{grid-column:1/-1}
        .fg label{font-size:11px;font-weight:500;color:var(--muted);letter-spacing:0.3px}
        input[type=text],input[type=number],textarea,select{width:100%;padding:11px 14px;border-radius:10px;border:1px solid var(--border);background:rgba(10,31,61,0.9);color:var(--text);font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:border 0.15s,box-shadow 0.15s}
        input:focus,textarea:focus,select:focus{border-color:rgba(162,231,255,0.28);box-shadow:0 0 0 3px rgba(162,231,255,0.05)}
        textarea{resize:vertical;min-height:84px;line-height:1.6}
        select option{background:#0a1f3d}
        .field-hint{font-size:11px;color:rgba(162,231,255,0.28);line-height:1.4;margin-top:2px}
        .harga-display{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--cyan);margin-top:8px;letter-spacing:-0.5px}

        /* FOTO */
        .foto-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
        .foto-card{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--surface-container);transition:border 0.15s}
        .foto-card:hover{border-color:rgba(162,231,255,0.2)}
        .foto-preview{width:100%;height:110px;object-fit:cover;display:block}
        .foto-preview-ph{width:100%;height:110px;display:flex;align-items:center;justify-content:center;background:var(--surface-container)}
        .foto-preview-ph svg{opacity:0.15}
        .foto-card-body{padding:10px 12px}
        .foto-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);display:block;margin-bottom:6px}
        .foto-card input[type=file]{width:100%;font-size:11px;color:rgba(162,231,255,0.45);cursor:pointer}
        .foto-status{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#6fdc96;margin-top:4px;display:none}

        /* ACTIONS */
        .form-actions{display:flex;align-items:center;gap:10px;padding-top:4px}
        .btn-save{padding:11px 26px;border-radius:10px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;border:none;cursor:pointer;letter-spacing:0.3px;transition:all 0.2s}
        .btn-save:hover{opacity:0.9;transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,89,179,0.35)}
        .btn-cancel{padding:11px 20px;border-radius:10px;background:transparent;border:1px solid var(--border);color:var(--muted);font-size:13px;font-weight:400;text-decoration:none;transition:all 0.15s;font-family:'Inter',sans-serif}
        .btn-cancel:hover{background:rgba(162,231,255,0.04);color:var(--text);border-color:rgba(162,231,255,0.2)}

        @media(max-width:768px){
            .body{flex-direction:column}
            .sidebar{width:100%;height:200px}
            .form-grid{grid-template-columns:1fr}
            .foto-row{grid-template-columns:1fr 1fr}
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-left">
        <div class="nav-brand">
            <div class="nav-brand-dot"></div>
            <div class="nav-brand-name">Pangandaran<span>.in</span></div>
        </div>
        <div class="nav-divider"></div>
        <span class="nav-label">Admin</span>
    </div>
    <div class="nav-right">
        <a href="?mode=tambah" class="nav-btn">+ Tambah Paket</a>
        <a href="admin_booking.php" class="nav-link">Data Booking</a>
        <a href="index.html" target="_blank" class="nav-link">Website</a>
        <a href="?logout=1" class="nav-logout">Keluar</a>
    </div>
</nav>

<div class="body">

    <div class="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-title">Paket Wisata</span>
            <span class="sidebar-count"><?= count($pakets) ?></span>
        </div>
        <div class="sidebar-body">
            <?php
            $kat = '';
            foreach ($pakets as $p):
                if ($p['kategori'] !== $kat):
                    $kat = $p['kategori'];
                    echo "<div class='kat-label'>".htmlspecialchars($katNames[$kat] ?? $kat)."</div>";
                endif;
                $active = (isset($_GET['edit']) && $_GET['edit'] === $p['id']) ? 'active' : '';
                $hText = $p['harga'] > 0 ? 'Rp '.number_format($p['harga'],0,',','.') : 'Nego';
            ?>
            <a href="?edit=<?= $p['id'] ?>" class="pkg-item <?= $active ?>">
                <?php if ($p['foto1']): ?>
                    <img src="<?= htmlspecialchars($p['foto1']) ?>" class="pkg-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="pkg-img-ph" style="display:none">
                        <svg class="pkg-img-ph-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                <?php else: ?>
                    <div class="pkg-img-ph">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                <?php endif; ?>
                <div class="pkg-info">
                    <div class="pkg-name"><?= htmlspecialchars($p['nama']) ?></div>
                    <div class="pkg-price"><?= $hText ?></div>
                </div>
                <a href="?hapus=<?= $p['id'] ?>" class="pkg-del" onclick="return confirm('Hapus paket ini?')">x</a>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main">

        <?php if ($message): ?>
        <div class="toast <?= $messageType ?>">
            <span class="toast-dot"></span>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if (!$editPaket && $mode !== 'tambah'): ?>

        <div class="placeholder">
            <div class="placeholder-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <h2>Pilih paket untuk diedit</h2>
            <p>Klik nama paket di sidebar kiri, atau tambah paket baru dengan tombol di atas.</p>
        </div>

        <?php elseif ($mode === 'tambah'): ?>

        <div class="page-header">
            <div class="page-tag">Paket Baru</div>
            <div class="page-title">Tambah Paket Wisata</div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tambah_paket" value="1">

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">1</div>
                    <span class="form-block-title">Informasi Dasar</span>
                </div>
                <div class="form-block-body">
                    <div class="form-grid">
                        <div class="fg">
                            <label>ID Paket</label>
                            <input type="text" name="id_paket" placeholder="contoh: rafting_baru" required>
                            <span class="field-hint">Huruf kecil, tanpa spasi. Dipakai di URL ?paket=ID</span>
                        </div>
                        <div class="fg">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <option value="river">River Adventure</option>
                                <option value="sea">Sea Adventure</option>
                                <option value="multi">Multi Day Trip</option>
                                <option value="outbound">Outbound</option>
                            </select>
                        </div>
                        <div class="fg full">
                            <label>Nama Paket</label>
                            <input type="text" name="nama" placeholder="contoh: Green Canyon Premium Experience" required>
                        </div>
                        <div class="fg">
                            <label>Subtitle</label>
                            <input type="text" name="subtitle" placeholder="River Adventure • Durasi 2 jam">
                        </div>
                        <div class="fg">
                            <label>Durasi</label>
                            <input type="text" name="durasi" placeholder="2 jam">
                        </div>
                        <div class="fg full">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" placeholder="Tulis deskripsi menarik untuk paket ini..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">2</div>
                    <span class="form-block-title">Harga</span>
                </div>
                <div class="form-block-body">
                    <div class="form-grid">
                        <div class="fg">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" id="hNew" placeholder="0" min="0" oninput="prevNew()">
                            <div class="harga-display" id="hpNew">Nego</div>
                        </div>
                        <div class="fg">
                            <label>Satuan</label>
                            <select name="unit">
                                <option value="/ orang">/ orang</option>
                                <option value="/ ride">/ ride</option>
                                <option value="/ paket">/ paket</option>
                                <option value="/ sesi">/ sesi</option>
                                <option value="/ nego">/ nego</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">3</div>
                    <span class="form-block-title">Foto Paket</span>
                </div>
                <div class="form-block-body">
                    <div class="foto-row">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="foto-card" id="fcard<?= $i ?>">
                            <div class="foto-preview-ph" id="fph<?= $i ?>">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <img id="fimg<?= $i ?>" class="foto-preview" src="" style="display:none">
                            <div class="foto-card-body">
                                <span class="foto-label">Foto <?= $i ?></span>
                                <input type="file" name="foto<?= $i ?>" accept="image/*" onchange="previewFoto(this, <?= $i ?>)">
                                <div class="foto-status" id="fstatus<?= $i ?>">Foto dipilih</div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <p class="field-hint" style="margin-top:12px">Foto disimpan otomatis di folder assets/uploads/</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Tambah Paket</button>
                <a href="admin_paket.php" class="btn-cancel">Batal</a>
            </div>
        </form>

        <?php elseif ($editPaket): ?>

        <div class="page-header">
            <div class="page-tag"><?= htmlspecialchars($katNames[$editPaket['kategori']] ?? '') ?></div>
            <div class="page-title"><?= htmlspecialchars($editPaket['nama']) ?></div>
            <div class="page-id">ID: <?= $editPaket['id'] ?></div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_paket" value="1">
            <input type="hidden" name="id" value="<?= $editPaket['id'] ?>">

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">1</div>
                    <span class="form-block-title">Informasi Paket</span>
                </div>
                <div class="form-block-body">
                    <div class="form-grid">
                        <div class="fg full">
                            <label>Nama Paket</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($editPaket['nama']) ?>" required>
                        </div>
                        <div class="fg">
                            <label>Subtitle</label>
                            <input type="text" name="subtitle" value="<?= htmlspecialchars($editPaket['subtitle']) ?>">
                        </div>
                        <div class="fg">
                            <label>Durasi</label>
                            <input type="text" name="durasi" value="<?= htmlspecialchars($editPaket['durasi']) ?>">
                        </div>
                        <div class="fg full">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi"><?= htmlspecialchars($editPaket['deskripsi']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">2</div>
                    <span class="form-block-title">Harga</span>
                </div>
                <div class="form-block-body">
                    <div class="form-grid">
                        <div class="fg">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" id="hEdit" value="<?= $editPaket['harga'] ?>" min="0" oninput="prevEdit()">
                            <div class="harga-display" id="hpEdit"><?= $editPaket['harga'] > 0 ? 'Rp '.number_format($editPaket['harga'],0,',','.') : 'Nego' ?></div>
                        </div>
                        <div class="fg">
                            <label>Satuan</label>
                            <select name="unit">
                                <?php foreach (['/ orang','/ ride','/ paket','/ sesi','/ nego'] as $u): ?>
                                <option value="<?= $u ?>" <?= $editPaket['unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-num">3</div>
                    <span class="form-block-title">Foto Paket</span>
                </div>
                <div class="form-block-body">
                    <div class="foto-row">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="foto-card" id="fcard<?= $i ?>">
                            <?php if ($editPaket["foto$i"]): ?>
                                <img id="fimg<?= $i ?>" src="<?= htmlspecialchars($editPaket["foto$i"]) ?>" class="foto-preview" onerror="this.style.display='none';document.getElementById('fph<?= $i ?>').style.display='flex'">
                                <div class="foto-preview-ph" id="fph<?= $i ?>" style="display:none">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                            <?php else: ?>
                                <div class="foto-preview-ph" id="fph<?= $i ?>">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                </div>
                                <img id="fimg<?= $i ?>" class="foto-preview" src="" style="display:none">
                            <?php endif; ?>
                            <div class="foto-card-body">
                                <span class="foto-label">Foto <?= $i ?> <?= $editPaket["foto$i"] ? '— ada' : '— kosong' ?></span>
                                <input type="hidden" name="foto<?= $i ?>_current" value="<?= htmlspecialchars($editPaket["foto$i"]) ?>">
                                <input type="file" name="foto<?= $i ?>" accept="image/*" onchange="previewFoto(this, <?= $i ?>)">
                                <div class="foto-status" id="fstatus<?= $i ?>">Foto baru dipilih</div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <p class="field-hint" style="margin-top:12px">Kosongkan jika tidak ingin mengganti. Foto baru disimpan di assets/uploads/</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Simpan Perubahan</button>
                <a href="admin_paket.php" class="btn-cancel">Batal</a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
function prevNew() {
    const v = parseInt(document.getElementById('hNew').value) || 0;
    document.getElementById('hpNew').textContent = v > 0 ? 'Rp ' + v.toLocaleString('id-ID') : 'Nego';
}
function prevEdit() {
    const v = parseInt(document.getElementById('hEdit').value) || 0;
    document.getElementById('hpEdit').textContent = v > 0 ? 'Rp ' + v.toLocaleString('id-ID') : 'Nego';
}
function previewFoto(input, num) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('fimg' + num);
        const ph  = document.getElementById('fph'  + num);
        const st  = document.getElementById('fstatus' + num);
        img.src = e.target.result;
        img.style.display = 'block';
        if (ph) ph.style.display = 'none';
        if (st) st.style.display = 'block';
    };
    reader.readAsDataURL(file);
}
const toast = document.querySelector('.toast');
if (toast) setTimeout(() => {
    toast.style.transition = 'opacity 0.3s, transform 0.3s';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-6px)';
    setTimeout(() => toast.remove(), 300);
}, 3500);
</script>
</body>
</html>
<?php $conn->close(); ?>