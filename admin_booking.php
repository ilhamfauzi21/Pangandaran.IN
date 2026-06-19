<?php
// ================================================================
// ADMIN BOOKING — Pangandaran.in
// ================================================================
session_start();

// ── LOGIN
if(isset($_POST['do_login'])){
    if(trim($_POST['u']??'')==='admin' && ($_POST['p']??'')==='Pangandaran.in'){
        $_SESSION['adm_bk']=true;
        header('Location: admin_booking.php'); exit;
    }
    $_SESSION['login_err']='Username atau password salah.';
    header('Location: admin_booking.php'); exit;
}
if(isset($_GET['logout'])){ session_destroy(); header('Location: admin_booking.php'); exit; }

if(!isset($_SESSION['adm_bk'])):
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#00132f;min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:rgba(5,27,57,.9);border:1px solid rgba(162,231,255,.12);border-radius:22px;padding:44px 40px;width:380px}
.logo-row{display:flex;align-items:center;gap:10px;margin-bottom:26px}
.logo-img{width:42px;height:42px;border-radius:50%;object-fit:cover}
.logo-fb{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#0059b3,#a2e7ff);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:16px;flex-shrink:0}
.brand{font-family:'Space Grotesk',sans-serif;font-size:17px;font-weight:800;color:#fff}
.brand-sub{font-size:10px;color:rgba(162,231,255,.4);text-transform:uppercase;letter-spacing:1.5px;display:block}
h1{font-size:22px;font-weight:700;color:#fff;margin-bottom:4px;font-family:'Space Grotesk',sans-serif}
.sub{font-size:12px;color:rgba(214,227,255,.4);margin-bottom:26px}
.err{background:rgba(255,80,80,.1);border:1px solid rgba(255,80,80,.2);color:#ff9090;padding:10px 14px;border-radius:10px;font-size:12px;margin-bottom:16px}
label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:rgba(162,231,255,.4);display:block;margin-bottom:6px}
input{width:100%;padding:12px 16px;border-radius:11px;border:1px solid rgba(162,231,255,.12);background:rgba(0,14,37,.8);color:#d6e3ff;font-size:14px;margin-bottom:16px;outline:none;font-family:'Inter',sans-serif}
input:focus{border-color:rgba(162,231,255,.3)}
button{width:100%;padding:13px;border-radius:11px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:14px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif}
</style></head><body>
<div class="card">
  <div class="logo-row">
    <img class="logo-img" src="assets/logo.png" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="logo-fb" style="display:none">P</div>
    <div><div class="brand">Pangandaran.in</div><span class="brand-sub">Admin Panel</span></div>
  </div>
  <h1>Masuk</h1>
  <p class="sub">Sistem Manajemen Booking</p>
  <?php if(isset($_SESSION['login_err'])){echo"<div class='err'>".$_SESSION['login_err']."</div>";unset($_SESSION['login_err']);}?>
  <form method="POST">
    <input type="hidden" name="do_login" value="1">
    <label>Username</label><input type="text" name="u" placeholder="admin" required autocomplete="username">
    <label>Password</label><input type="password" name="p" placeholder="••••••••" required autocomplete="current-password">
    <button type="submit">Masuk ke Dashboard</button>
  </form>
</div></body></html>
<?php exit; endif;

// ── DATABASE
$db = new mysqli("localhost","root","","pangandaran_db");
if($db->connect_error) die("DB Error: ".$db->connect_error);
$db->set_charset("utf8mb4");

// ── AUTO-CREATE TABEL
$db->query("CREATE TABLE IF NOT EXISTS `booking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_invoice` VARCHAR(30),
  `nama` VARCHAR(255),
  `email` VARCHAR(100),
  `whatsapp` VARCHAR(30),
  `medsos` VARCHAR(100),
  `tanggal` DATE,
  `waktu_kegiatan` VARCHAR(100),
  `catatan` TEXT,
  `sumber` VARCHAR(30) DEFAULT 'booking',
  `total_harga` INT DEFAULT 0,
  `discount` INT DEFAULT 0,
  `total_invoice` INT DEFAULT 0,
  `dp` INT DEFAULT 0,
  `sisa_bayar` INT DEFAULT 0,
  `status_bayar` VARCHAR(30) DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("CREATE TABLE IF NOT EXISTS `booking_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `produk` VARCHAR(255),
  `harga` INT DEFAULT 0,
  `qty` INT DEFAULT 1,
  `subtotal` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("CREATE TABLE IF NOT EXISTS `paket_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(255),
  `harga` INT DEFAULT 0,
  `satuan` VARCHAR(30) DEFAULT 'orang',
  `kategori` VARCHAR(50) DEFAULT 'sea',
  `aktif` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function addColumnIfNotExists($db, $table, $column, $definition){
    $table_safe = $db->real_escape_string($table);
    $column_safe = $db->real_escape_string($column);

    $cek = $db->query("SHOW COLUMNS FROM `$table_safe` LIKE '$column_safe'");

    if($cek && $cek->num_rows == 0){
        $db->query("ALTER TABLE `$table_safe` ADD COLUMN `$column_safe` $definition");
    }
}

addColumnIfNotExists($db, 'booking', 'total_invoice', 'INT DEFAULT 0');
addColumnIfNotExists($db, 'booking', 'dp', 'INT DEFAULT 0');
addColumnIfNotExists($db, 'booking', 'sisa_bayar', 'INT DEFAULT 0');
addColumnIfNotExists($db, 'booking', 'tanggal_pelunasan', 'DATE DEFAULT NULL');
addColumnIfNotExists($db, 'booking', 'discount', 'INT DEFAULT 0');
addColumnIfNotExists($db, 'booking', 'waktu_kegiatan', 'VARCHAR(100) DEFAULT NULL');
addColumnIfNotExists($db, 'booking', 'medsos', 'VARCHAR(100) DEFAULT NULL');
addColumnIfNotExists($db, 'booking', 'sumber', "VARCHAR(30) DEFAULT 'booking'");
addColumnIfNotExists($db, 'booking', 'metode_bayar', "VARCHAR(20) DEFAULT 'full'");

// TAMBAHAN UNTUK AUTO PAKET / ITEM
addColumnIfNotExists($db, 'booking', 'paket', 'VARCHAR(255) DEFAULT NULL');
addColumnIfNotExists($db, 'booking', 'peserta', 'INT DEFAULT 1');
addColumnIfNotExists($db, 'booking', 'harga_satuan', 'INT DEFAULT 0');

// AUTO REPAIR DATA LAMA:
// kalau booking_items kosong, ambil dari booking.paket agar kolom Paket / Item tidak kosong
$db->query("
  INSERT INTO booking_items (booking_id, produk, harga, qty, subtotal)
  SELECT 
    b.id,
    b.paket,
    COALESCE(NULLIF(b.harga_satuan,0), b.total_invoice, b.total_harga, 0),
    COALESCE(NULLIF(b.peserta,0), 1),
    COALESCE(NULLIF(b.total_harga,0), b.total_invoice, 0)
  FROM booking b
  LEFT JOIN booking_items bi ON bi.booking_id = b.id
  WHERE bi.id IS NULL
    AND b.paket IS NOT NULL
    AND b.paket <> ''
");

$act = $_POST['act'] ?? $_GET['act'] ?? '';

if($act==='get_items_json'){
    header('Content-Type: application/json');
    $rows=[];
    $r=$db->query("SELECT * FROM paket_item WHERE aktif=1 ORDER BY kategori,nama");
    if($r) while($row=$r->fetch_assoc()) $rows[]=$row;
    echo json_encode($rows); exit;
}

if($act==='update_status'){
    header('Content-Type: application/json');

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    $dp_input = intval($_POST['dp'] ?? 0);

    $bk = $db->query("SELECT total_invoice, dp, sisa_bayar FROM booking WHERE id=$id");
    $bkr = $bk ? $bk->fetch_assoc() : null;

    if(!$bkr){
        echo json_encode(['ok'=>false,'msg'=>'Data booking tidak ditemukan.']);
        exit;
    }

    $tinv = intval($bkr['total_invoice'] ?? 0);

    if($status === 'Lunas' || $status === 'Paid Off'){
        $dp_baru = $tinv;
        $sisa = 0;
        $status = 'Lunas';
        $tgl_sql = "CURDATE()";
    } elseif($status === 'DP'){
        $dp_baru = min($dp_input, $tinv);
        $sisa = max(0, $tinv - $dp_baru);

        if($tinv > 0 && $dp_baru >= $tinv){
            $dp_baru = $tinv;
            $sisa = 0;
            $status = 'Lunas';
            $tgl_sql = "CURDATE()";
        } else {
            $status = 'DP';
            $tgl_sql = "NULL";
        }
    } else {
        $dp_baru = min($dp_input, $tinv);
        $sisa = max(0, $tinv - $dp_baru);
        $tgl_sql = "NULL";
    }

    $status_safe = $db->real_escape_string($status);

    $update = $db->query("
        UPDATE booking SET
            dp = $dp_baru,
            sisa_bayar = $sisa,
            status_bayar = '$status_safe',
            tanggal_pelunasan = $tgl_sql,
            updated_at = NOW()
        WHERE id = $id
    ");

    if(!$update){
        echo json_encode(['ok'=>false,'msg'=>'Gagal update: '.$db->error]);
        exit;
    }

    echo json_encode(['ok'=>true]);
    exit;
}

if($act==='delete_booking'){
    header('Content-Type: application/json');
    $id=intval($_POST['id']??0);
    $db->query("DELETE FROM booking_items WHERE booking_id=$id");
    $db->query("DELETE FROM booking WHERE id=$id");
    echo json_encode(['ok'=>true]); exit;
}

if($act==='get_booking'){
    header('Content-Type: application/json');
    $id=intval($_GET['id']??0);
    $bk=$db->query("SELECT * FROM booking WHERE id=$id");
    $bkr=$bk?$bk->fetch_assoc():null;
    if(!$bkr){echo json_encode(['ok'=>false]);exit;}
    $items=[];
    $ir=$db->query("SELECT * FROM booking_items WHERE booking_id=$id ORDER BY id");
    if($ir) while($r=$ir->fetch_assoc()) $items[]=$r;
    $bkr['items']=$items;
    echo json_encode(['ok'=>true,'data'=>$bkr]); exit;
}

if($act==='save_booking'){
    header('Content-Type: application/json');
    $bid=intval($_POST['bid']??0);
    $nama=trim($_POST['nama']??'');
    $email=trim($_POST['email']??'');
    $medsos=trim($_POST['medsos']??'');
    $wa=trim($_POST['whatsapp']??'');
    $tgl=$_POST['tanggal']??date('Y-m-d');
    $waktu=trim($_POST['waktu']??'');
    $catatan=trim($_POST['catatan']??'');
    $disc=intval($_POST['discount']??0);
    $dp=intval($_POST['dp']??0);
    $status=$db->real_escape_string($_POST['status']??'Pending');
    $items=json_decode($_POST['items']??'[]',true)?:[];
    if(!$nama){echo json_encode(['ok'=>false,'msg'=>'Nama wajib diisi']);exit;}

    // Email tidak wajib. Jika kosong, gunakan placeholder internal.
    // Placeholder ini tidak ditampilkan pada tabel admin jika media sosial tersedia.
    if($email==='') $email='noemail@pangandaran.in';

    if($email !== 'noemail@pangandaran.in' && !filter_var($email,FILTER_VALIDATE_EMAIL)){
        echo json_encode(['ok'=>false,'msg'=>'Format email tidak valid']);
        exit;
    }
    $total=0;
    foreach($items as &$it){
        $it['harga']=intval($it['harga']??0);
        $it['qty']=max(1,intval($it['qty']??1));
        $it['subtotal']=$it['harga']*$it['qty'];
        $total+=$it['subtotal'];
    }
    $tinv=max(0,$total-$disc);
    $sisa=max(0,$tinv-$dp);
    if($bid){
        $s=$db->prepare("UPDATE booking SET nama=?,email=?,whatsapp=?,medsos=?,tanggal=?,waktu_kegiatan=?,catatan=?,total_harga=?,discount=?,total_invoice=?,dp=?,sisa_bayar=?,status_bayar=?,updated_at=NOW() WHERE id=?");
        $s->bind_param("sssssssiiiiisi",$nama,$email,$wa,$medsos,$tgl,$waktu,$catatan,$total,$disc,$tinv,$dp,$sisa,$status,$bid);
        $s->execute();
        $db->query("DELETE FROM booking_items WHERE booking_id=$bid");
    } else {
        $no='PNT'.date('ymd').str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $src='admin';
        $s=$db->prepare("INSERT INTO booking (no_invoice,nama,email,whatsapp,medsos,tanggal,waktu_kegiatan,catatan,sumber,total_harga,discount,total_invoice,dp,sisa_bayar,status_bayar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssssssssiiiiis",$no,$nama,$email,$wa,$medsos,$tgl,$waktu,$catatan,$src,$total,$disc,$tinv,$dp,$sisa,$status);
        $s->execute();
        $bid=$db->insert_id;
    }
    $si=$db->prepare("INSERT INTO booking_items (booking_id,produk,harga,qty,subtotal) VALUES (?,?,?,?,?)");
    foreach($items as $it){
        $si->bind_param("isiii",$bid,$it['produk'],$it['harga'],$it['qty'],$it['subtotal']);
        $si->execute();
    }
    echo json_encode(['ok'=>true]); exit;
}

if($act==='export_excel'){
    while(ob_get_level()) ob_end_clean();
    date_default_timezone_set('Asia/Jakarta');

    $fs   = $db->real_escape_string($_GET['fs'] ?? '');
    $fq   = $db->real_escape_string($_GET['q'] ?? '');
    $from = $db->real_escape_string($_GET['from'] ?? '');
    $to   = $db->real_escape_string($_GET['to'] ?? '');

    // Export mengambil SEMUA data booking dari database sesuai filter yang dipilih,
    // bukan hanya data yang sedang tampil pada halaman pagination.
    $wh = "WHERE 1=1";
    if($fs){
        $wh .= " AND b.status_bayar='$fs'";
    }
    if($fq){
        $wh .= " AND (b.nama LIKE '%$fq%' OR b.no_invoice LIKE '%$fq%' OR b.whatsapp LIKE '%$fq%' OR b.email LIKE '%$fq%' OR b.medsos LIKE '%$fq%')";
    }
    if($from){
        $wh .= " AND b.tanggal>='$from'";
    }
    if($to){
        $wh .= " AND b.tanggal<='$to'";
    }

    $statusLabel = $fs ?: 'Semua Status';
    if($from || $to){
        $periodeLabel = ($from ?: 'Awal Data') . ' s/d ' . ($to ?: 'Sekarang');
    } else {
        $periodeLabel = 'Semua Periode';
    }

    // Waktu export otomatis mengikuti waktu server Indonesia / WIB.
    // Contoh hasil: Dicetak: 12/06/2026 02:36 WIB
    $dicetak = date('d/m/Y');

    $sum = $db->query("SELECT COUNT(*) c, COALESCE(SUM(total_invoice),0) total_invoice, COALESCE(SUM(dp),0) total_terbayar, COALESCE(SUM(sisa_bayar),0) sisa FROM booking b $wh");
    $sum = $sum ? $sum->fetch_assoc() : ['c'=>0,'total_invoice'=>0,'total_terbayar'=>0,'sisa'=>0];

    $perStatus = $db->query("SELECT b.status_bayar, COUNT(*) jumlah, COALESCE(SUM(b.total_invoice),0) total_invoice, COALESCE(SUM(b.dp),0) total_terbayar, COALESCE(SUM(b.sisa_bayar),0) sisa FROM booking b $wh GROUP BY b.status_bayar ORDER BY b.status_bayar");

    $topLayanan = $db->query("SELECT bi.produk, COUNT(*) jumlah_dipesan, COALESCE(SUM(bi.subtotal),0) total_pendapatan FROM booking b LEFT JOIN booking_items bi ON bi.booking_id=b.id $wh AND bi.produk IS NOT NULL AND bi.produk<>'' GROUP BY bi.produk ORDER BY total_pendapatan DESC, jumlah_dipesan DESC LIMIT 15");

    $detail = $db->query("SELECT b.*, COALESCE(NULLIF(GROUP_CONCAT(CONCAT(bi.produk,' x',bi.qty) SEPARATOR ', '), ''), NULLIF(b.paket, ''), '—') AS ilist FROM booking b LEFT JOIN booking_items bi ON bi.booking_id=b.id $wh GROUP BY b.id ORDER BY b.created_at DESC");

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Laporan_Data_Booking_Pangandaran_'.date('Ymd_His').'.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";

    function ex($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
    function exrp($n){ return number_format(intval($n ?? 0),0,',','.'); }
    function exdash($v){ $v=trim((string)($v ?? '')); return $v!=='' ? $v : '—'; }
    ?>
    <html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
      <meta charset="UTF-8">
      <style>
        table{border-collapse:collapse;margin-bottom:14px}
        .title{font-size:16px;font-weight:bold;color:#00132f}
        .sub{font-size:10px;color:#555}
        .sec{font-size:12px;font-weight:bold;color:#fff;background:#00132f;padding:6px 10px}
        th{background:#0059b3;color:#fff;font-weight:bold;border:1px solid #2a4d80;padding:6px 8px;text-align:center}
        td{border:1px solid #bbb;padding:4px 8px;font-size:11px;vertical-align:top}
        .num{mso-number-format:"\#\,\#\#0";text-align:right}
        .txt{mso-number-format:"\@"}
        .lbl{font-weight:bold;background:#eaf2fc}
        .tot td{font-weight:bold;background:#eaf2fc}
      </style>
    </head>
    <body>

    <table>
      <tr><td colspan="8" class="title">LAPORAN OMZET &amp; DATA BOOKING — Pangandaran.in</td></tr>
      <tr><td colspan="8" class="sub">CV Pangandaran in Group&nbsp;|&nbsp;Periode: <?=ex($periodeLabel)?>&nbsp;|&nbsp;Status: <?=ex($statusLabel)?>&nbsp;|&nbsp;Dicetak: <?=ex($dicetak)?></td></tr>
    </table>

    <table>
      <tr><td colspan="2" class="sec">RINGKASAN OMZET</td></tr>
      <tr><td class="lbl">Total Transaksi</td><td class="num"><?=exrp($sum['c'] ?? 0)?></td></tr>
      <tr><td class="lbl">Total Invoice</td><td class="num"><?=exrp($sum['total_invoice'] ?? 0)?></td></tr>
      <tr><td class="lbl">Total Terbayar</td><td class="num"><?=exrp($sum['total_terbayar'] ?? 0)?></td></tr>
      <tr><td class="lbl">Sisa Tagihan</td><td class="num"><?=exrp($sum['sisa'] ?? 0)?></td></tr>
      <tr><td class="lbl">Waktu Export</td><td class="txt"><?=ex($dicetak)?></td></tr>
    </table>

    <table>
      <tr><td colspan="5" class="sec">PER STATUS PEMBAYARAN</td></tr>
      <tr><th>Status</th><th>Jumlah</th><th>Total Invoice</th><th>Terbayar</th><th>Sisa</th></tr>
      <?php if($perStatus) while($s=$perStatus->fetch_assoc()): ?>
      <tr>
        <td class="txt"><?=exdash($s['status_bayar'] ?? '')?></td>
        <td class="num"><?=exrp($s['jumlah'] ?? 0)?></td>
        <td class="num"><?=exrp($s['total_invoice'] ?? 0)?></td>
        <td class="num"><?=exrp($s['total_terbayar'] ?? 0)?></td>
        <td class="num"><?=exrp($s['sisa'] ?? 0)?></td>
      </tr>
      <?php endwhile; ?>
    </table>

    <table>
      <tr><td colspan="3" class="sec">TOP LAYANAN YANG DIPESAN</td></tr>
      <tr><th>Layanan</th><th>Jumlah Dipesan</th><th>Total Pendapatan</th></tr>
      <?php if($topLayanan) while($t=$topLayanan->fetch_assoc()): ?>
      <tr>
        <td><?=exdash($t['produk'] ?? '')?></td>
        <td class="num"><?=exrp($t['jumlah_dipesan'] ?? 0)?></td>
        <td class="num"><?=exrp($t['total_pendapatan'] ?? 0)?></td>
      </tr>
      <?php endwhile; ?>
    </table>

    <table>
      <tr><td colspan="20" class="sec">DETAIL DATA BOOKING</td></tr>
      <tr>
        <th>No</th>
        <th>No Invoice</th>
        <th>Nama</th>
        <th>Media Sosial / IG</th>
        <th>WhatsApp</th>
        <th>Email</th>
        <th>Tanggal Trip</th>
        <th>Waktu Trip</th>
        <th>Item Paket</th>
        <th>Total</th>
        <th>Diskon</th>
        <th>Total Invoice</th>
        <th>DP/Terbayar</th>
        <th>Sisa</th>
        <th>Metode Bayar</th>
        <th>Status</th>
        <th>Sumber</th>
        <th>Tanggal Lunas</th>
        <th>Tgl Booking</th>
        <th>Catatan</th>
      </tr>
      <?php $no=0; if($detail) while($r=$detail->fetch_assoc()): $no++; ?>
      <?php
        $email = trim((string)($r['email'] ?? ''));
        if($email === 'noemail@pangandaran.in') $email = '';
        $metode = $r['metode_bayar'] ?? '';
        $metodeLabel = $metode === 'dp' ? 'DP' : ($metode === 'full' ? 'Full Payment' : ($metode ?: '—'));
      ?>
      <tr>
        <td class="num"><?=$no?></td>
        <td class="txt"><?=exdash($r['no_invoice'] ?? '')?></td>
        <td><?=exdash($r['nama'] ?? '')?></td>
        <td class="txt"><?=exdash($r['medsos'] ?? '')?></td>
        <td class="txt"><?=exdash($r['whatsapp'] ?? '')?></td>
        <td class="txt"><?=exdash($email)?></td>
        <td class="txt"><?=exdash($r['tanggal'] ?? '')?></td>
        <td class="txt"><?=exdash($r['waktu_kegiatan'] ?? '')?></td>
        <td><?=exdash($r['ilist'] ?? '')?></td>
        <td class="num"><?=exrp($r['total_harga'] ?? 0)?></td>
        <td class="num"><?=exrp($r['discount'] ?? 0)?></td>
        <td class="num"><?=exrp($r['total_invoice'] ?? 0)?></td>
        <td class="num"><?=exrp($r['dp'] ?? 0)?></td>
        <td class="num"><?=exrp($r['sisa_bayar'] ?? 0)?></td>
        <td class="txt"><?=exdash($metodeLabel)?></td>
        <td class="txt"><?=exdash($r['status_bayar'] ?? '')?></td>
        <td class="txt"><?=exdash($r['sumber'] ?? '')?></td>
        <td class="txt"><?=exdash($r['tanggal_pelunasan'] ?? '')?></td>
        <td class="txt"><?=exdash($r['created_at'] ?? '')?></td>
        <td><?=exdash($r['catatan'] ?? '')?></td>
      </tr>
      <?php endwhile; ?>
      <tr class="tot">
        <td colspan="11" style="text-align:right">TOTAL (<?=$no?> transaksi)</td>
        <td class="num"><?=exrp($sum['total_invoice'] ?? 0)?></td>
        <td class="num"><?=exrp($sum['total_terbayar'] ?? 0)?></td>
        <td class="num"><?=exrp($sum['sisa'] ?? 0)?></td>
        <td colspan="6"></td>
      </tr>
    </table>

    </body></html>
    <?php
    exit;
}

$fs=$db->real_escape_string($_GET['fs']??'');
$fq=$db->real_escape_string($_GET['q']??'');
$page=max(1,intval($_GET['page']??1));
$pp=15; $offset=($page-1)*$pp;
$wh="WHERE 1=1";
if($fs) $wh.=" AND status_bayar='$fs'";
if($fq) $wh.=" AND (nama LIKE '%$fq%' OR no_invoice LIKE '%$fq%' OR whatsapp LIKE '%$fq%' OR email LIKE '%$fq%' OR medsos LIKE '%$fq%')";
$tr=$db->query("SELECT COUNT(*) c FROM booking $wh");
$total_rows=$tr?intval($tr->fetch_assoc()['c']):0;
$total_pages=max(1,ceil($total_rows/$pp));
$rows=[];
$res=$db->query("
  SELECT 
    b.id,
    b.no_invoice,
    b.nama,
    b.email,
    b.whatsapp,
    b.medsos,
    b.tanggal,
    b.waktu_kegiatan,
    b.catatan,
    b.sumber,
    b.metode_bayar,
    b.paket,
    b.total_harga,
    b.discount,
    b.total_invoice,
    b.dp,
    b.sisa_bayar,
    b.status_bayar,
    b.tanggal_pelunasan,
    b.created_at,
    COALESCE(
      NULLIF(GROUP_CONCAT(CONCAT(bi.produk,' x',bi.qty) SEPARATOR ' | '), ''),
      NULLIF(b.paket, ''),
      '—'
    ) AS ilist
  FROM booking b
  LEFT JOIN booking_items bi ON bi.booking_id = b.id
  $wh
  GROUP BY b.id
  ORDER BY b.created_at DESC
  LIMIT $pp OFFSET $offset
");
if($res) while($r=$res->fetch_assoc()) $rows[]=$r;

$st=['tb'=>0,'menunggu'=>0,'lunas'=>0,'omzet'=>0,'dp_amt'=>0,'sisa'=>0];
$sr=$db->query("SELECT COUNT(*) tb,COALESCE(SUM(CASE WHEN status_bayar IN('Pending','DP') THEN 1 ELSE 0 END),0) menunggu,COALESCE(SUM(CASE WHEN status_bayar IN('Lunas','Paid Off') THEN 1 ELSE 0 END),0) lunas,COALESCE(SUM(total_invoice),0) omzet,COALESCE(SUM(dp),0) dp_amt,COALESCE(SUM(sisa_bayar),0) sisa FROM booking");
if($sr){$tmp=$sr->fetch_assoc();if($tmp)$st=$tmp;}

function rp($n){return number_format(intval($n??0),0,',','.');}
function badge($s){
    $m=['Lunas'=>['#22c55e','rgba(34,197,94,.12)','rgba(34,197,94,.22)'],'Paid Off'=>['#22c55e','rgba(34,197,94,.12)','rgba(34,197,94,.22)'],'DP'=>['#f59e0b','rgba(245,158,11,.12)','rgba(245,158,11,.22)'],'Pending'=>['#94a3b8','rgba(148,163,184,.1)','rgba(148,163,184,.18)'],'Batal'=>['#ef4444','rgba(239,68,68,.1)','rgba(239,68,68,.2)'],'Refund'=>['#a78bfa','rgba(167,139,250,.1)','rgba(167,139,250,.2)']];
    [$c,$bg,$bord]=$m[$s]??['#94a3b8','rgba(148,163,184,.1)','rgba(148,163,184,.18)'];
    return "<span style='background:$bg;color:$c;border:1px solid $bord;font-size:9.5px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.3px;text-transform:uppercase'>$s</span>";
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Booking — Pangandaran.in</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#00132f;--low:#051b39;--c:#0a1f3d;--border:rgba(162,231,255,.1);--cyan:#a2e7ff;--text:#d6e3ff;--muted:rgba(214,227,255,.45)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
::-webkit-scrollbar{width:4px;height:4px}::-webkit-scrollbar-thumb{background:rgba(162,231,255,.12);border-radius:10px}
.topbar{height:54px;background:rgba(0,14,37,.96);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:100;backdrop-filter:blur(20px)}
.brand{display:flex;align-items:center;gap:10px}
.brand img{width:32px;height:32px;border-radius:50%;object-fit:cover}
.brand-fb{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0059b3,#a2e7ff);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px;flex-shrink:0}
.brand-name{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:800;color:#fff}
.badge-admin{font-size:9px;background:rgba(162,231,255,.1);color:var(--cyan);padding:2px 8px;border-radius:20px;letter-spacing:1px;text-transform:uppercase;border:1px solid rgba(162,231,255,.18);margin-left:4px}
.nav a{padding:7px 12px;border-radius:9px;font-size:13px;color:var(--muted);text-decoration:none;transition:.15s;font-weight:500}
.nav a:hover{background:rgba(162,231,255,.06);color:var(--text)}
.nav a.on{background:rgba(162,231,255,.1);color:var(--cyan)}
.topbar-r{display:flex;align-items:center;gap:8px}
.btn-p{padding:8px 18px;border-radius:9px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:12px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif;transition:.2s;white-space:nowrap}
.btn-p:hover{opacity:.9;transform:translateY(-1px)}
.btn-s{padding:7px 14px;border-radius:9px;background:rgba(162,231,255,.07);border:1px solid var(--border);color:var(--muted);font-size:12px;cursor:pointer;transition:.15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-s:hover{background:rgba(162,231,255,.12);color:var(--text)}
.btn-d{padding:5px 11px;border-radius:8px;background:rgba(255,80,80,.07);border:1px solid rgba(255,80,80,.18);color:#ff8080;font-size:11px;cursor:pointer;transition:.15s}
.btn-d:hover{background:rgba(255,80,80,.14)}
.content{max-width:1240px;margin:0 auto;padding:24px}
.pg-tag{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:var(--muted);margin-bottom:4px}
.pg-title{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;letter-spacing:-.3px;margin-bottom:22px}
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.sc{background:var(--low);border:1px solid var(--border);border-radius:14px;padding:18px 22px}
.sc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.sc-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted)}
.sc-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sc-val{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:800;line-height:1;letter-spacing:-.5px;color:#fff}
.sc-sub{font-size:11px;color:var(--muted);margin-top:6px}
.sg2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:22px}
.sc2{background:var(--low);border:1px solid var(--border);border-radius:14px;padding:16px 22px;display:flex;justify-content:space-between;align-items:center}
.sc2-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
/* TABLE — FIX: overflow-x scroll */
.tw{background:var(--low);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.tw-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
.th-bar{padding:13px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px}
.si{padding:8px 13px;border-radius:9px;border:1px solid var(--border);background:var(--c);color:var(--text);font-size:12px;outline:none;min-width:220px}
.si:focus{border-color:rgba(162,231,255,.3)}
.sf{padding:8px 12px;border-radius:9px;border:1px solid var(--border);background:var(--c);color:var(--text);font-size:12px;outline:none;cursor:pointer}
table{width:100%;border-collapse:collapse;min-width:900px}
thead th{padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left;background:rgba(10,31,61,.5);border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid rgba(162,231,255,.04);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(162,231,255,.02)}
td{padding:11px 14px;font-size:12.5px;vertical-align:middle;white-space:nowrap}
.es{text-align:center;padding:50px;color:var(--muted);font-size:14px}
.pg-nav{display:flex;align-items:center;justify-content:space-between;padding:11px 18px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.pb{padding:5px 11px;border-radius:7px;border:1px solid var(--border);background:var(--c);color:var(--muted);font-size:11px;cursor:pointer;text-decoration:none;transition:.15s}
.pb:hover,.pb.on{background:rgba(162,231,255,.1);color:var(--cyan);border-color:rgba(162,231,255,.2)}
.mo{position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:500;display:none;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px);overflow-y:auto}
.mo.show{display:flex}
.md{background:#0a1f3d;border:1px solid var(--border);border-radius:18px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;margin:auto}
.mh{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#0a1f3d;z-index:1}
.mt{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;color:#fff}
.mb{padding:20px}
.mf{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;position:sticky;bottom:0;background:#0a1f3d}
.mx{background:none;border:none;color:var(--muted);font-size:22px;cursor:pointer;line-height:1;padding:2px 6px}
.mx:hover{color:#fff}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.fg label{font-size:11px;font-weight:500;color:var(--muted)}
.fg input,.fg select,.fg textarea{padding:10px 13px;border-radius:10px;border:1px solid var(--border);background:rgba(0,14,37,.8);color:var(--text);font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:.15s}
.fg input:focus,.fg select:focus{border-color:rgba(162,231,255,.3)}
.fg select option{background:#0a1f3d}
.fg textarea{resize:vertical;min-height:60px;line-height:1.5}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.full{grid-column:1/-1}
.irow{display:grid;grid-template-columns:1fr 80px 50px auto;gap:7px;margin-bottom:8px;align-items:end}
.irow input,.irow select{padding:9px 11px;border-radius:9px;border:1px solid var(--border);background:rgba(0,14,37,.8);color:var(--text);font-size:12px;outline:none;width:100%}
.irow select option{background:#0a1f3d}
#moInv .md{background:#fff;border:none;max-width:740px;width:min(740px,calc(100vw - 32px));overflow:hidden}
#moInvBody{overflow-x:hidden;background:#fff}
#moInvBody #inv-print-area{box-sizing:border-box;width:100%;max-width:100%;overflow:hidden}
#moInvBody table,#moInv table{min-width:0!important}
#toast{position:fixed;top:70px;left:50%;transform:translateX(-50%) translateY(-12px);background:rgba(5,27,57,.97);border:1px solid rgba(162,231,255,.25);color:var(--text);padding:11px 22px;border-radius:11px;font-size:12.5px;font-weight:500;z-index:9999;opacity:0;transition:.3s;pointer-events:none;white-space:nowrap}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
@media(max-width:768px){.sg{grid-template-columns:1fr 1fr}.nav{display:none}}
</style>
</head>
<body>
<div id="toast"></div>

<!-- ===== MODALS ===== -->
<!-- Detail -->
<div class="mo" id="moDet"><div class="md" style="max-width:660px">
  <div class="mh"><div class="mt" id="moDetT">Detail Booking</div><button class="mx" onclick="closeM('moDet')">&#215;</button></div>
  <div class="mb" id="moDetB"></div>
  <div class="mf" id="moDetF"></div>
</div></div>

<!-- Invoice -->
<div class="mo" id="moInv"><div class="md">
  <div id="moInvBody"></div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;border-top:1.5px solid #e0e0e0">
    <button id="invPDF" style="padding:14px;background:linear-gradient(135deg,#0059b3,#3a8fe8);color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;border:none;cursor:pointer;border-radius:0 0 0 18px;display:flex;align-items:center;justify-content:center;gap:7px">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Download PDF
    </button>
    <button id="invWA" style="padding:14px;background:#25D366;color:#fff;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px">
      <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zm-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884z"/></svg>
      Kirim WA
    </button>
    <button onclick="closeM('moInv')" style="padding:14px;background:#f0f0f0;color:#555;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;border:none;cursor:pointer;border-radius:0 0 18px 0">Tutup</button>
  </div>
  <button onclick="closeM('moInv')" style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:26px;cursor:pointer;color:#bbb;z-index:1;padding:2px 8px">&#215;</button>
</div></div>

<!-- Buat/Edit Booking -->
<div class="mo" id="moBk"><div class="md" style="max-width:760px">
  <div class="mh"><div class="mt" id="moBkT">Buat Booking Baru</div><button class="mx" onclick="closeM('moBk')">&#215;</button></div>
  <div class="mb">
    <input type="hidden" id="bkId">
    <div class="fgrid">
      <div class="fg full"><label>Nama Lengkap *</label><input id="bkNama" placeholder="Nama client"></div>
      <div class="fg">
        <label>Email (Opsional)</label>
        <input id="bkEmail" type="email" placeholder="boleh dikosongkan" oninput="valEmail(this)" autocomplete="off">
        <div id="ehint" style="font-size:11px;margin-top:4px;display:none"></div>
      </div>
      <div class="fg">
        <label>WhatsApp</label>
        <input id="bkWA" type="tel" placeholder="+62 8xx-xxxx-xxxx" oninput="fmtWA(this)">
      </div>
      <div class="fg">
        <label>Media Sosial / Instagram</label>
        <input id="bkMedsos" type="text" placeholder="@username" oninput="fmtMedsos(this)" autocomplete="off">
      </div>
      <div class="fg"><label>Tanggal Kegiatan</label><input id="bkTgl" type="date" style="color-scheme:dark"></div>
      <div class="fg"><label>Waktu Kegiatan</label><input id="bkWaktu" placeholder="09.00 - 11.00 WIB"></div>
      <div class="fg"><label>Diskon (Rp)</label><input id="bkDisc" type="number" min="0" value="0" oninput="recalcBk()"></div>
      <div class="fg"><label>DP / Terbayar (Rp)</label><input id="bkDP" type="number" min="0" value="0" oninput="recalcBk()"></div>
      <div class="fg"><label>Status Pembayaran</label>
        <select id="bkSts">
          <option value="Pending">Pending</option><option value="DP">DP</option>
          <option value="Lunas">Lunas</option><option value="Paid Off">Paid Off</option>
          <option value="Batal">Batal</option><option value="Refund">Refund</option>
        </select></div>
      <div class="fg full"><label>Catatan</label><textarea id="bkCat" placeholder="Catatan tambahan..."></textarea></div>
    </div>
    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Item / Paket Dipesan</div>
    <div id="bkItems"></div>
    <button onclick="addBkRow()" class="btn-s" style="width:100%;padding:9px;justify-content:center;margin-top:6px;font-size:12px">+ Tambah Item</button>
    <div style="background:rgba(0,14,37,.6);border:1px solid var(--border);border-radius:11px;padding:13px 15px;margin-top:14px">
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Subtotal</span><span id="bkSub">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Diskon</span><span id="bkDiscD">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#fff;border-top:1px solid var(--border);padding-top:8px;margin-bottom:4px"><span>Total Invoice</span><span id="bkTI" style="color:var(--cyan)">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:3px"><span>DP Terbayar</span><span id="bkDPD">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700"><span style="color:var(--muted)">Sisa Tagihan</span><span id="bkSisa" style="color:#f59e0b">Rp 0</span></div>
    </div>
  </div>
  <div class="mf"><button class="btn-s" onclick="closeM('moBk')">Batal</button><button class="btn-p" onclick="saveBk()">Simpan Booking</button></div>
</div></div>

<!-- Update Status -->
<div class="mo" id="moSts"><div class="md" style="max-width:360px">
  <div class="mh"><div class="mt">Update Status Pembayaran</div><button class="mx" onclick="closeM('moSts')">&#215;</button></div>
  <div class="mb">
    <input type="hidden" id="stsId">
    <div class="fg" style="margin-bottom:14px"><label>Status Baru</label>
      <select id="stsVal">
        <option value="Pending">Pending</option><option value="DP">DP — Sebagian Terbayar</option>
        <option value="Lunas">Lunas</option><option value="Paid Off">Paid Off</option>
        <option value="Batal">Batal</option><option value="Refund">Refund</option>
      </select></div>
    <div class="fg"><label>Jumlah DP / Terbayar (Rp)</label><input id="stsDP" type="number" min="0" placeholder="0"></div>
  </div>
  <div class="mf"><button class="btn-s" onclick="closeM('moSts')">Batal</button><button class="btn-p" onclick="saveSts()">Update</button></div>
</div></div>

<!-- ===== TOPBAR ===== -->
<div class="topbar">
  <div class="brand">
    <img src="assets/logo.png" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="brand-fb" style="display:none">P</div>
    <div class="brand-name">Pangandaran.in</div>
    <span class="badge-admin">ADMIN</span>
  </div>
  <div class="nav" style="display:flex;gap:2px">
    <a href="admin_paket.php">Kelola Paket</a>
    <a href="admin_booking.php" class="on">Data Booking</a>
    <a href="index.html" target="_blank">Website</a>
    <a href="admin.php">Dashboard Utama</a>
  </div>
  <div class="topbar-r">
    <a href="?act=export_excel&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>" class="btn-s">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Excel
    </a>
    <button class="btn-p" onclick="openNewBk()">+ Buat Booking</button>
    <a href="?logout=1" style="color:rgba(255,100,100,.45);font-size:12px;text-decoration:none;padding:7px 12px;border-radius:8px;transition:.15s">Keluar</a>
  </div>
</div>

<!-- ===== CONTENT ===== -->
<div class="content">

<div class="pg-tag">Manajemen</div>
<div class="pg-title">Data Booking</div>

<!-- STATS ROW 1 -->
<div class="sg">
  <div class="sc">
    <div class="sc-top">
      <span class="sc-label">Total Booking</span>
      <div class="sc-icon" style="background:rgba(162,231,255,.08);border:1px solid rgba(162,231,255,.12)">
        <svg width="16" height="16" fill="none" stroke="var(--cyan)" stroke-width="1.6" viewBox="0 0 24 24"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg>
      </div>
    </div>
    <div class="sc-val"><?= intval($st['tb']) ?></div>
    <div class="sc-sub">transaksi tercatat</div>
  </div>
  <div class="sc">
    <div class="sc-top">
      <span class="sc-label">Menunggu Konfirmasi</span>
      <div class="sc-icon" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.18)">
        <svg width="16" height="16" fill="none" stroke="#f59e0b" stroke-width="1.6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
    </div>
    <div class="sc-val" style="color:#f59e0b"><?= intval($st['menunggu']) ?></div>
    <div class="sc-sub">pending &amp; DP</div>
  </div>
  <div class="sc" style="border-color:rgba(34,197,94,.15)">
    <div class="sc-top">
      <span class="sc-label">Transaksi Lunas</span>
      <div class="sc-icon" style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.18)">
        <svg width="16" height="16" fill="none" stroke="#22c55e" stroke-width="1.6" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
    </div>
    <div class="sc-val" style="color:#22c55e"><?= intval($st['lunas']) ?></div>
    <div class="sc-sub">pembayaran lunas</div>
  </div>
  <div class="sc">
    <div class="sc-top">
      <span class="sc-label">Total Nilai</span>
      <div class="sc-icon" style="background:rgba(162,231,255,.08);border:1px solid rgba(162,231,255,.12)">
        <svg width="16" height="16" fill="none" stroke="var(--cyan)" stroke-width="1.6" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
    </div>
    <div class="sc-val" style="font-size:18px">Rp <?= rp($st['omzet']) ?></div>
    <div class="sc-sub">total invoice</div>
  </div>
</div>

<!-- STATS ROW 2 -->
<div class="sg2">
  <div class="sc2">
    <div>
      <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:8px">Total Terbayar (DP + Lunas)</div>
      <div style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:800;color:#22c55e;letter-spacing:-.3px">Rp <?= rp($st['dp_amt']) ?></div>
    </div>
    <div class="sc2-icon" style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.15)">
      <svg width="18" height="18" fill="none" stroke="#22c55e" stroke-width="1.6" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    </div>
  </div>
  <div class="sc2">
    <div>
      <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:8px">Total Sisa Tagihan</div>
      <div style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:800;color:#f59e0b;letter-spacing:-.3px">Rp <?= rp($st['sisa']) ?></div>
    </div>
    <div class="sc2-icon" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.15)">
      <svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="1.6" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
  </div>
</div>

<!-- TABLE -->
<div class="tw">
  <div class="th-bar">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input class="si" name="q" placeholder="Cari nama / invoice / WA / medsos..." value="<?= htmlspecialchars($fq) ?>">
      <select class="sf" name="fs" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach(['Pending','DP','Lunas','Paid Off','Batal','Refund'] as $s): ?>
        <option value="<?=$s?>" <?=$fs===$s?'selected':''?>><?=$s?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-s" style="padding:8px 14px">Filter</button>
      <?php if($fq||$fs): ?><a href="admin_booking.php" class="btn-s" style="padding:8px 14px">Reset</a><?php endif; ?>
    </form>
    <span style="font-size:12px;color:var(--muted)"><?= $total_rows ?> data</span>
  </div>

  <!-- FIX: Wrapper scroll horizontal -->
  <div class="tw-scroll">
  <table>
    <thead><tr>
      <th>No. Invoice</th>
      <th>Nama &amp; Kontak</th>
      <th>Paket / Item</th>
      <th>Tgl &amp; Waktu Trip</th>
      <th style="text-align:right">Total Invoice</th>
      <th style="text-align:right">DP</th>
      <th style="text-align:right">Sisa</th>
      <th>Status</th>
      <th>TGL LUNAS</th>
      <th>Metode</th>
      <th>Sumber</th>
      <th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(empty($rows)): ?>
    <tr><td colspan="12" class="es">
      <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="opacity:.3;display:block;margin:0 auto 10px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Belum ada booking<?= $fq||$fs?' yang cocok dengan filter':'' ?>.
    </td></tr>
    <?php else: foreach($rows as $b):
      $tinv = intval($b['total_invoice'] ?? 0);
      $tdp  = intval($b['dp']           ?? 0);
      $sisa = intval($b['sisa_bayar']   ?? 0);
    ?>
    <tr>
      <td>
        <button type="button"
          onclick="openInvoice(<?= $b['id'] ?>)"
          title="Klik untuk lihat invoice"
          style="background:none;border:none;padding:0;margin:0;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:11px;color:var(--cyan);cursor:pointer;text-align:left">
          <?= htmlspecialchars($b['no_invoice']??'—') ?>
        </button>
        <div style="font-size:10px;color:var(--muted);margin-top:2px"><?= date('d M Y',strtotime($b['created_at'])) ?></div>
      </td>
      <td>
        <div style="font-weight:600;color:#fff;font-size:13px"><?= htmlspecialchars($b['nama']) ?></div>
        <?php
          $medsosTampil = trim($b['medsos'] ?? '');
          $emailTampil  = trim($b['email'] ?? '');
          $showEmail    = ($emailTampil !== '' && strtolower($emailTampil) !== 'noemail@pangandaran.in');
        ?>
        <?php if($medsosTampil !== ''): ?>
          <div style="font-size:11px;color:var(--cyan);margin-top:2px"><?= htmlspecialchars($medsosTampil) ?></div>
        <?php elseif($showEmail): ?>
          <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= htmlspecialchars($emailTampil) ?></div>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($b['whatsapp']??'—') ?></div>
      </td>
      <td style="font-size:11px;color:var(--muted);max-width:150px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($b['ilist']??'') ?>"><?= htmlspecialchars($b['ilist']??'—') ?></td>
      <td>
        <div style="font-size:12px;color:var(--text)"><?= $b['tanggal'] ?></div>
        <?php if(!empty($b['waktu_kegiatan'])): ?><div style="font-size:10px;color:var(--muted)"><?= htmlspecialchars($b['waktu_kegiatan']) ?></div><?php endif; ?>
      </td>
      <td style="text-align:right;font-family:'Space Grotesk',sans-serif;font-weight:700;color:var(--cyan)">Rp <?= rp($tinv) ?></td>
      <td style="text-align:right;font-size:12px;color:#22c55e;font-weight:600">Rp <?= rp($tdp) ?></td>
      <td style="text-align:right;font-weight:700;color:<?= $sisa>0?'#f59e0b':'#22c55e' ?>">
        <?= $sisa>0 ? 'Rp '.rp($sisa) : '<span style="color:#22c55e">Lunas &#10003;</span>' ?>
      </td>
      <td><?= badge($b['status_bayar']) ?></td>

      <td>
        <?php if(!empty($b['tanggal_pelunasan']) && $b['tanggal_pelunasan'] !== '0000-00-00'): ?>
          <span style="color:#22c55e;font-weight:700;font-size:11px">
            <?= date('Y-m-d', strtotime($b['tanggal_pelunasan'])) ?>
          </span>
        <?php else: ?>
          <span style="color:rgba(214,227,255,.45)">—</span>
        <?php endif; ?>
      </td>

      <td>
        <?php
        $mb = $b['metode_bayar'] ?? 'full';
        $mb_map = ['full'=>'Full Payment','dp'=>'DP','tempo'=>'Bayar di Tempat'];
        $mb_clr = ['full'=>'#22c55e','dp'=>'#f59e0b','tempo'=>'#94a3b8'];
        $mb_lbl = $mb_map[$mb] ?? strtoupper($mb);
        $mb_c   = $mb_clr[$mb] ?? '#94a3b8';
        ?>
        <span style="background:<?=$mb_c?>18;color:<?=$mb_c?>;border:1px solid <?=$mb_c?>35;font-size:9.5px;font-weight:700;padding:3px 9px;border-radius:20px;white-space:nowrap"><?=$mb_lbl?></span>
      </td>
      <td>
        <?php
        $src=htmlspecialchars($b['sumber']??'');
        $sc=$src==='booking'?'var(--cyan)':($src==='admin'?'#a78bfa':'var(--muted)');
        ?>
        <span style="background:rgba(162,231,255,.05);color:<?=$sc?>;font-size:9px;font-weight:700;padding:2px 8px;border-radius:8px;text-transform:uppercase;border:1px solid <?=$sc?>30"><?= strtoupper($src?:'—') ?></span>
      </td>
      <td>
        <div style="display:flex;gap:4px;position:relative;z-index:2">
          <button type="button" class="btn-s" style="padding:5px 9px;font-size:11px;cursor:pointer" onclick="openDetail(<?= $b['id'] ?>)">Detail</button>
          <button type="button" class="btn-s" style="padding:5px 9px;font-size:11px;cursor:pointer" onclick="openInvoice(<?= $b['id'] ?>)">Invoice</button>
          <button type="button" class="btn-s" style="padding:5px 9px;font-size:11px;cursor:pointer" onclick="openSts(<?= $b['id'] ?>,<?= $tdp ?>,'<?= addslashes($b['status_bayar']) ?>')">Status</button>
          <button type="button" class="btn-d" style="cursor:pointer" onclick="hapus(<?= $b['id'] ?>,'<?= addslashes($b['no_invoice']??'') ?>')">Hapus</button>
        </div>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div><!-- end tw-scroll -->

  <?php if($total_pages>1): ?>
  <div class="pg-nav">
    <span><?= $total_rows ?> data &mdash; halaman <?= $page ?>/<?= $total_pages ?></span>
    <div style="display:flex;gap:4px">
      <?php if($page>1): ?><a href="?page=<?=$page-1?>&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>" class="pb">&#8249; Prev</a><?php endif; ?>
      <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
      <a href="?page=<?=$i?>&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>" class="pb <?=$i===$page?'on':''?>"><?=$i?></a>
      <?php endfor; ?>
      <?php if($page<$total_pages): ?><a href="?page=<?=$page+1?>&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>" class="pb">Next &#8250;</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>

<script>
const LOGO_URI="assets/logo.png";
function toast(m,t,d){const el=document.getElementById('toast');el.textContent=m;el.style.borderColor=t==='ok'?'rgba(34,197,94,.3)':t==='err'?'rgba(255,80,80,.3)':'rgba(162,231,255,.25)';el.classList.add('show');setTimeout(()=>el.classList.remove('show'),d||2800);}
function openM(id){document.getElementById(id).classList.add('show');}
function closeM(id){document.getElementById(id).classList.remove('show');}
document.querySelectorAll('.mo').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('show');}));
const fRp=n=>(parseInt(n)||0).toLocaleString('id-ID');

function valEmail(input){
  const v=input.value.trim(),h=document.getElementById('ehint');if(!h)return;
  if(!v){
    h.style.display='block';
    h.innerHTML='<span style="color:rgba(162,231,255,.45)">Email opsional. Jika kosong, sistem memakai placeholder internal.</span>';
    input.style.borderColor='';
    return;
  }
  const ok=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);h.style.display='block';
  if(!ok){h.innerHTML='<span style="color:#f59e0b">&#9888; '+(v.includes('@')?'Format belum lengkap &mdash; contoh: nama@gmail.com':'Belum ada @ &mdash; contoh: nama@gmail.com')+'</span>';input.style.borderColor='rgba(245,158,11,.35)';}
  else{h.innerHTML='<span style="color:#22c55e">&#10003; '+v+'</span>';input.style.borderColor='rgba(34,197,94,.35)';}
}
function fmtWA(input){
  let v=input.value.replace(/\s/g,'');
  if(v.startsWith('08')){input.value='+628'+v.slice(2);}
  else if(v.startsWith('628')){input.value='+'+v;}
  else if(v.startsWith('8')&&v.length>=9){input.value='+62'+v;}
}

function fmtMedsos(input){
  let v=input.value.trim();
  if(!v) return;
  if(v.length > 1 && !v.startsWith('@') && !v.startsWith('http')) input.value='@'+v;
  if(input.value.includes(' ')) input.value=input.value.replace(/\s+/g,'');
}

let piList=[];
async function loadPI(){try{const r=await fetch('admin_booking.php?act=get_items_json');piList=await r.json();}catch(e){piList=[];}}
loadPI();

function mkSel(val){
  val=val||'';
  const g={};piList.forEach(it=>{(g[it.kategori]=g[it.kategori]||[]).push(it);});
  const kl={river:'River Adventure',sea:'Sea Adventure',multi:'Multi Day Trip',outbound:'Outbound'};
  let h='<option value="" data-h="0">Pilih item...</option>';
  Object.keys(g).forEach(k=>{h+='<optgroup label="'+(kl[k]||k)+'" style="background:#0a1f3d">';g[k].forEach(it=>{const lbl=it.harga>0?'Rp '+fRp(it.harga)+'/'+it.satuan:'Nego';h+='<option value="'+it.nama+'" data-h="'+it.harga+'" '+(it.nama===val?'selected':'')+'>'+it.nama+' &mdash; '+lbl+'</option>';});h+='</optgroup>';});
  return h;
}

let bc=0;
function openNewBk(){
  document.getElementById('bkId').value='';
  document.getElementById('moBkT').textContent='Buat Booking Baru';
  ['bkNama','bkEmail','bkWA','bkMedsos','bkCat','bkWaktu'].forEach(function(i){var el=document.getElementById(i);if(el){el.value='';el.style.borderColor='';}});
  document.getElementById('bkTgl').value='';
  document.getElementById('bkDisc').value=0;
  document.getElementById('bkDP').value=0;
  document.getElementById('bkSts').value='Pending';
  document.getElementById('bkItems').innerHTML='';
  bc=0;
  var h=document.getElementById('ehint');if(h)h.style.display='none';
  addBkRow();
  recalcBk();
  openM('moBk');
}

async function openEditBk(id){
  const r=await(await fetch('admin_booking.php?act=get_booking&id='+id)).json();if(!r.ok)return;const d=r.data;
  document.getElementById('bkId').value=d.id;document.getElementById('moBkT').textContent='Edit — '+d.no_invoice;
  document.getElementById('bkNama').value=d.nama;document.getElementById('bkEmail').value=(d.email==='noemail@pangandaran.in'?'':d.email);document.getElementById('bkWA').value=d.whatsapp;document.getElementById('bkMedsos').value=d.medsos||'';
  document.getElementById('bkTgl').value=d.tanggal;document.getElementById('bkWaktu').value=d.waktu_kegiatan||'';document.getElementById('bkCat').value=d.catatan||'';
  document.getElementById('bkDisc').value=d.discount||0;document.getElementById('bkDP').value=d.dp||0;document.getElementById('bkSts').value=d.status_bayar;
  document.getElementById('bkItems').innerHTML='';bc=0;(d.items||[]).forEach(function(it){addBkRow(it);});if(!(d.items||[]).length)addBkRow();recalcBk();openM('moBk');
}

function addBkRow(data){
  data=data||null;bc++;
  var id='br'+bc;
  var div=document.createElement('div');
  div.className='irow';div.id=id;
  div.innerHTML='<div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Produk</label>'
    +'<select onchange="onSel(this,\''+id+'\')" style="width:100%;background:rgba(0,14,37,.8);border:1px solid var(--border);border-radius:9px;padding:9px 11px;color:var(--text);font-size:12px;outline:none">'+mkSel(data?data.produk:'')+'</select></div>'
    +'<div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Harga</label>'
    +'<input type="number" id="'+id+'h" value="'+(data?data.harga:0)+'" min="0" oninput="recalcBk()" style="background:rgba(0,14,37,.8);border:1px solid var(--border);border-radius:9px;padding:9px 10px;color:var(--cyan);font-size:11px;font-weight:700;outline:none"></div>'
    +'<div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Qty</label>'
    +'<input type="number" id="'+id+'q" value="'+(data?data.qty:1)+'" min="1" oninput="recalcBk()" style="background:rgba(0,14,37,.8);border:1px solid var(--border);border-radius:9px;padding:9px 8px;color:var(--text);font-size:12px;text-align:center;outline:none"></div>'
    +'<div style="padding-bottom:1px"><button onclick="document.getElementById(\''+id+'\').remove();recalcBk()" style="width:28px;height:34px;border-radius:7px;background:rgba(255,80,80,.07);border:1px solid rgba(255,80,80,.18);color:#ff8080;cursor:pointer;font-size:16px;font-weight:700">&#215;</button></div>';
  document.getElementById('bkItems').appendChild(div);
  recalcBk();
}

function onSel(sel,id){var h=parseInt(sel.options[sel.selectedIndex].getAttribute('data-h')||0);document.getElementById(id+'h').value=h;recalcBk();}

function recalcBk(){
  var tot=0;
  document.querySelectorAll('#bkItems .irow').forEach(function(row){
    var h=parseInt(row.querySelector('input[id$="h"]')?row.querySelector('input[id$="h"]').value:0);
    var q=parseInt(row.querySelector('input[id$="q"]')?row.querySelector('input[id$="q"]').value:1);
    tot+=h*q;
  });
  var disc=parseInt(document.getElementById('bkDisc').value||0);
  var dp=parseInt(document.getElementById('bkDP').value||0);
  var tinv=Math.max(0,tot-disc);var sisa=Math.max(0,tinv-dp);
  document.getElementById('bkSub').textContent='Rp '+fRp(tot);
  document.getElementById('bkDiscD').textContent='Rp '+fRp(disc);
  document.getElementById('bkTI').textContent='Rp '+fRp(tinv);
  document.getElementById('bkDPD').textContent='Rp '+fRp(dp);
  document.getElementById('bkSisa').textContent='Rp '+fRp(sisa);
}

async function saveBk(){
  var nama=document.getElementById('bkNama').value.trim();
  var email=document.getElementById('bkEmail').value.trim();
  var medsos=document.getElementById('bkMedsos').value.trim();
  if(!nama){toast('Nama wajib diisi.','err');document.getElementById('bkNama').focus();return;}
  if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){toast('Format email tidak valid.','err');document.getElementById('bkEmail').focus();return;}
  if(!email) email='noemail@pangandaran.in';
  var items=[];
  document.querySelectorAll('#bkItems .irow').forEach(function(row){
    var p=row.querySelector('select')?row.querySelector('select').value:'';if(!p)return;
    var h=parseInt(row.querySelector('input[id$="h"]')?row.querySelector('input[id$="h"]').value:0);
    var q=parseInt(row.querySelector('input[id$="q"]')?row.querySelector('input[id$="q"]').value:1);
    items.push({produk:p,harga:h,qty:q,subtotal:h*q});
  });
  if(!items.length){toast('Minimal 1 item.','err');return;}
  var body=new URLSearchParams({act:'save_booking',bid:document.getElementById('bkId').value||0,nama:nama,email:email,medsos:medsos,whatsapp:document.getElementById('bkWA').value,tanggal:document.getElementById('bkTgl').value,waktu:document.getElementById('bkWaktu').value,catatan:document.getElementById('bkCat').value,discount:document.getElementById('bkDisc').value||0,dp:document.getElementById('bkDP').value||0,status:document.getElementById('bkSts').value,items:JSON.stringify(items)});
  var r=await(await fetch('admin_booking.php',{method:'POST',body:body})).json();
  if(r.ok){toast('Tersimpan!','ok');closeM('moBk');setTimeout(function(){location.reload();},1100);}else toast(r.msg||'Gagal.','err');
}

async function openDetail(id){
  var r=await(await fetch('admin_booking.php?act=get_booking&id='+id)).json();if(!r.ok)return;var d=r.data;var items=d.items||[];
  var tinv=parseInt(d.total_invoice||0),dp=parseInt(d.dp||0),sisa=parseInt(d.sisa_bayar||0);
  var mbMap={'full':'Full Payment','dp':'DP / Uang Muka','tempo':'Bayar di Tempat'};
  var rows=items.map(function(it){return '<tr><td style="padding:7px 0;border-bottom:1px solid rgba(162,231,255,.05);font-size:12px">'+it.produk+'</td><td style="padding:7px 0;text-align:right;font-size:12px;border-bottom:1px solid rgba(162,231,255,.05)">'+fRp(it.harga)+'</td><td style="padding:7px 0;text-align:center;font-size:12px;border-bottom:1px solid rgba(162,231,255,.05)">'+it.qty+'</td><td style="padding:7px 0;text-align:right;color:var(--cyan);font-weight:600;font-size:12px;border-bottom:1px solid rgba(162,231,255,.05)">'+fRp(it.subtotal)+'</td></tr>';}).join('');
  document.getElementById('moDetT').textContent='Detail — '+d.no_invoice;
  document.getElementById('moDetB').innerHTML=
    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">'
    +'<div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Data Client</div>'
    +'<div style="font-size:12px;line-height:2.1"><strong style="color:#fff;font-size:14px">'+d.nama+'</strong><br><span style="color:var(--cyan)">'+(d.medsos||((d.email&&d.email!=='noemail@pangandaran.in')?d.email:'&mdash;'))+'</span><br>'+(d.whatsapp||'&mdash;')+'</div></div>'
    +'<div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Info Booking</div>'
    +'<div style="font-size:12px;line-height:2.1">Tanggal: <strong style="color:#fff">'+d.tanggal+'</strong><br>Waktu: '+(d.waktu_kegiatan||'&mdash;')+'<br>Sumber: <span style="color:var(--cyan);text-transform:uppercase">'+(d.sumber||'&mdash;')+'</span><br>'
    +'Metode Bayar: <strong style="color:#fff">'+(mbMap[d.metode_bayar]||d.metode_bayar||'&mdash;')+'</strong></div></div></div>'
    +'<div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Item Dipesan</div>'
    +'<table style="width:100%;margin-bottom:12px"><thead><tr>'
    +'<th style="font-size:10px;color:var(--muted);text-align:left;padding-bottom:6px">Produk</th>'
    +'<th style="font-size:10px;color:var(--muted);text-align:right;padding-bottom:6px">Harga</th>'
    +'<th style="font-size:10px;color:var(--muted);text-align:center;padding-bottom:6px">Qty</th>'
    +'<th style="font-size:10px;color:var(--muted);text-align:right;padding-bottom:6px">Subtotal</th>'
    +'</tr></thead><tbody>'+rows+'</tbody></table>'
    +'<div style="background:rgba(0,14,37,.6);border:1px solid var(--border);border-radius:10px;padding:12px 14px">'
    +'<div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Total</span><span>Rp '+fRp(d.total_harga)+'</span></div>'
    +'<div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Diskon</span><span>Rp '+fRp(d.discount)+'</span></div>'
    +'<div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#fff;border-top:1px solid var(--border);padding-top:8px;margin-bottom:4px"><span>Total Invoice</span><span style="color:var(--cyan)">Rp '+fRp(tinv)+'</span></div>'
    +'<div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:3px"><span>DP Terbayar</span><span style="color:#22c55e">Rp '+fRp(dp)+'</span></div>'
    +'<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700"><span style="color:var(--muted)">Sisa Tagihan</span><span style="color:#f59e0b">Rp '+fRp(sisa)+'</span></div></div>'
    +(d.catatan?'<div style="margin-top:10px;background:rgba(0,14,37,.5);border:1px solid var(--border);border-radius:9px;padding:10px 13px;font-size:12px;color:var(--muted)"><strong style="color:var(--text)">Catatan:</strong> '+d.catatan+'</div>':'');
  document.getElementById('moDetF').innerHTML=
    '<button class="btn-s" onclick="closeM(\'moDet\')">Tutup</button>'
    +'<button class="btn-s" onclick="closeM(\'moDet\');openSts('+d.id+','+dp+',\''+d.status_bayar+'\')">Update Status</button>'
    +'<button class="btn-s" onclick="closeM(\'moDet\');openEditBk('+d.id+')">Edit</button>'
    +'<button class="btn-p" onclick="closeM(\'moDet\');openInvoice('+d.id+')">Lihat Invoice</button>';
  openM('moDet');
}

function openSts(id,dp,status){document.getElementById('stsId').value=id;document.getElementById('stsDP').value=dp||0;document.getElementById('stsVal').value=status||'Pending';openM('moSts');}
async function saveSts(){
  var id=document.getElementById('stsId').value;
  var body=new URLSearchParams({act:'update_status',id:id,status:document.getElementById('stsVal').value,dp:document.getElementById('stsDP').value||0});
  var r=await(await fetch('admin_booking.php',{method:'POST',body:body})).json();
  if(r.ok){toast('Status diperbarui!','ok');closeM('moSts');setTimeout(function(){location.reload();},1100);}
}

async function hapus(id,inv){
  if(!confirm('Hapus booking '+inv+'?\nSemua data item akan ikut terhapus.'))return;
  var r=await(await fetch('admin_booking.php',{method:'POST',body:new URLSearchParams({act:'delete_booking',id:id})})).json();
  if(r.ok){toast('Booking dihapus.','ok',2000);setTimeout(function(){location.reload();},900);}
}

function buildInvHTML(d, items){
  var tot   = parseInt(d.total_harga   || 0);
  var disc  = parseInt(d.discount      || 0);
  var tinv  = parseInt(d.total_invoice || 0) || Math.max(0, tot - disc);
  var dp    = parseInt(d.dp || 0);
  var isLunas = (d.status_bayar==='Lunas' || d.status_bayar==='Paid Off');
  var sisa  = isLunas ? 0 : (parseInt(d.sisa_bayar || 0) || Math.max(0, tinv - dp));
  if(isLunas) dp = tinv;
  var sc    = isLunas ? '#16a34a' : '#d97706';
  var tgl   = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
  function fD(n){return(parseInt(n)||0).toLocaleString('id-ID');}

  var rows=(items||[]).map(function(it){
    return '<tr>'
      +'<td style="padding:12px 0;font-size:13px;color:#333;border-bottom:1px solid #e8e8e8;font-family:Georgia,serif;vertical-align:top;line-height:1.5">'+it.produk+'</td>'
      +'<td style="padding:12px 14px;font-size:13px;text-align:right;color:#333;border-bottom:1px solid #e8e8e8;font-family:Georgia,serif;white-space:nowrap;vertical-align:top">'+fD(it.harga)+'</td>'
      +'<td style="padding:12px 10px;font-size:13px;text-align:center;color:#333;border-bottom:1px solid #e8e8e8;font-family:Georgia,serif;vertical-align:top">'+it.qty+'</td>'
      +'<td style="padding:12px 0 12px 14px;font-size:13px;text-align:right;color:#333;border-bottom:1px solid #e8e8e8;font-family:Georgia,serif;white-space:nowrap;vertical-align:top">'+fD(it.subtotal)+'</td>'
      +'</tr>';
  }).join('');

  return '<div id="inv-print-area" style="padding:44px 52px 56px;font-family:Georgia,serif;background:#fff;color:#333;font-size:13px">'

    // ===== HEADER =====
    +'<div style="display:flex;justify-content:space-between;align-items:flex-start">'
    // Logo + Brand
    +'<div style="display:flex;align-items:center;gap:18px">'
    +'<img src="assets/logo.png" width="84" height="84" style="border-radius:50%;object-fit:cover;flex-shrink:0;display:block" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">'
    +'<div style="display:none;width:84px;height:84px;border-radius:50%;background:linear-gradient(135deg,#0059b3,#a2e7ff);align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:28px;flex-shrink:0">P</div>'
    +'<div>'
    +'<div style="font-family:Arial,Helvetica,sans-serif;font-size:26px;font-weight:900;color:#000;letter-spacing:-.3px;line-height:1.1">Pangandaran.in</div>'
    +'<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#888;margin-top:4px">CV. Pangandaran in Group</div>'
    +'</div></div>'
    // Kontak + Receipt kanan
    +'<div style="text-align:right">'
    +'<table style="border-collapse:collapse;margin-left:auto">'
    +'<tr><td style="font-size:12px;color:#555;padding:3px 0;text-align:right;white-space:nowrap;font-family:Arial,sans-serif">https://pangandaran.in</td>'
    +'<td style="padding:3px 0 3px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #aaa;border-radius:50%;color:#aaa"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span></td></tr>'
    +'<tr><td style="font-size:12px;color:#555;padding:3px 0;text-align:right;white-space:nowrap;font-family:Arial,sans-serif">pangandaraningroup@gmail.com</td>'
    +'<td style="padding:3px 0 3px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #aaa;border-radius:3px;color:#aaa"><svg width="11" height="9" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 18"><rect x="2" y="2" width="20" height="14" rx="2"/><polyline points="2,2 12,11 22,2"/></svg></span></td></tr>'
    +'<tr><td style="font-size:12px;color:#555;padding:3px 0;text-align:right;white-space:nowrap;font-family:Arial,sans-serif">@pangandaran.in</td>'
    +'<td style="padding:3px 0 3px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #aaa;border-radius:5px;color:#aaa"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></span></td></tr>'
    +'</table>'
    +'<div style="font-family:Georgia,serif;font-size:36px;font-weight:700;color:#000;margin-top:12px">Receipt</div>'
    +'</div></div>'

    // Divider
    +'<div style="border-top:1px solid #ccc;margin:18px 0 28px"></div>'

    // ===== BILL TO + PAYMENT DETAILS =====
    +'<div style="display:grid;grid-template-columns:1fr 1fr;gap:0;margin-bottom:32px">'
    +'<div><div style="font-family:Georgia,serif;font-size:20px;font-weight:700;color:#000;margin-bottom:14px">Bill to</div>'
    +'<table style="border-collapse:collapse">'
    +'<tr><td style="color:#777;padding:5px 0;width:78px;font-family:Georgia,serif;font-size:13px;vertical-align:top">Name</td><td style="color:#777;padding:5px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="color:#222;padding:5px 0;font-family:Georgia,serif;font-size:13px;font-weight:600">'+(d.nama||'—')+'</td></tr>'
    +'<tr><td style="color:#777;padding:5px 0;font-family:Georgia,serif;font-size:13px">Media Sosial</td><td style="color:#777;padding:5px 8px">:</td><td style="color:#222;padding:5px 0;font-family:Georgia,serif;font-size:13px">'+(d.medsos||'—')+'</td></tr>'
    +'<tr><td style="color:#777;padding:5px 0;font-family:Georgia,serif;font-size:13px">WhatsApp</td><td style="color:#777;padding:5px 8px">:</td><td style="color:#222;padding:5px 0;font-family:Georgia,serif;font-size:13px">'+(d.whatsapp||'—')+'</td></tr>'
    +'</table></div>'
    +'<div><div style="font-family:Georgia,serif;font-size:20px;font-weight:700;color:#000;margin-bottom:14px">Payment Details</div>'
    +'<table style="border-collapse:collapse">'
    +'<tr><td style="color:#777;padding:4px 0;width:112px;font-family:Georgia,serif;font-size:13px">No. Order</td><td style="color:#777;padding:4px 8px">:</td><td style="color:#0059b3;padding:4px 0;font-family:Georgia,serif;font-size:13px;font-weight:700">'+(d.no_invoice||'—')+'</td></tr>'
    +'<tr><td style="color:#777;padding:4px 0;font-family:Georgia,serif;font-size:13px">Status</td><td style="color:#777;padding:4px 8px">:</td><td style="padding:4px 0;font-family:Georgia,serif;font-size:13px;font-weight:700;color:'+sc+'">'+(d.status_bayar||'Pending')+'</td></tr>'
    +'<tr><td style="color:#777;padding:4px 0;font-family:Georgia,serif;font-size:13px">Booking Date</td><td style="color:#777;padding:4px 8px">:</td><td style="color:#222;padding:4px 0;font-family:Georgia,serif;font-size:13px">'+tgl+'</td></tr>'
    +'<tr><td style="color:#777;padding:4px 0;font-family:Georgia,serif;font-size:13px">Trip Date</td><td style="color:#777;padding:4px 8px">:</td><td style="color:#222;padding:4px 0;font-family:Georgia,serif;font-size:13px">'+(d.tanggal||'—')+'</td></tr>'
    +'<tr><td style="color:#777;padding:4px 0;font-family:Georgia,serif;font-size:13px">Trip Time</td><td style="color:#777;padding:4px 8px">:</td><td style="color:#222;padding:4px 0;font-family:Georgia,serif;font-size:13px">'+(d.waktu_kegiatan||'—')+'</td></tr>'
    +'<tr><td style="color:#777;padding:4px 0;font-family:Georgia,serif;font-size:13px">Payment Method</td><td style="color:#777;padding:4px 8px">:</td><td style="color:#222;padding:4px 0;font-family:Georgia,serif;font-size:13px">'+((d.metode_bayar==='dp')?'DP':'Full Payment')+'</td></tr>'
    +'</table></div></div>'

    // ===== ORDER DETAILS =====
    +'<div style="font-family:Georgia,serif;font-size:20px;font-weight:700;color:#000;margin-bottom:12px">Order Details</div>'
    +'<table style="width:100%;border-collapse:collapse">'
    +'<thead><tr style="border-top:1.5px solid #222;border-bottom:1.5px solid #222">'
    +'<th style="padding:12px 0;font-size:13px;font-weight:700;text-align:left;color:#000;font-family:Georgia,serif">Product</th>'
    +'<th style="padding:12px 14px;font-size:13px;font-weight:700;text-align:right;color:#000;font-family:Georgia,serif">Price<br>(Rp)</th>'
    +'<th style="padding:12px 10px;font-size:13px;font-weight:700;text-align:center;color:#000;font-family:Georgia,serif">QTY</th>'
    +'<th style="padding:12px 0 12px 14px;font-size:13px;font-weight:700;text-align:right;color:#000;font-family:Georgia,serif">Total<br>(Rp)</th>'
    +'</tr></thead><tbody>'+rows+'</tbody></table>'

    // ===== SUMMARY =====
    +'<table style="width:100%;border-collapse:collapse">'
    +'<tr><td style="width:55%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Total</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">'+fD(tot)+'</td></tr>'
    +'<tr><td style="width:55%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Discount (Rp.)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">'+fD(disc)+'</td></tr>'
    +'<tr style="border-top:1.5px solid #222"><td style="width:55%;padding:0"></td><td style="padding:11px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Total Invoice Amount</td><td style="padding:11px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">'+fD(tinv)+'</td></tr>'
    +'<tr><td style="width:55%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Ammount Paid (DP)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">'+((!isLunas && dp>0)?fD(dp):'—')+'</td></tr>'
    +'<tr><td style="width:55%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Amount Paid (Final)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">'+(isLunas?fD(tinv):'—')+'</td></tr>'
    +'<tr style="border-top:1.5px solid #222;border-bottom:1.5px solid #222"><td style="width:55%;padding:0"></td><td style="padding:12px 0;font-weight:700;font-size:14px;color:#000;font-family:Georgia,serif">Outstanding Balance</td><td style="padding:12px 0;font-weight:900;font-size:16px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">'+fD(sisa)+'</td></tr>'
    +'</table>'
    +'<div style="height:80px"></div>'
    +'<div style="text-align:right">'
    +'<div style="font-weight:700;font-size:14px;color:#000;font-family:Georgia,serif;margin-bottom:10px">Best Regards</div>'
    +'<div style="font-weight:900;font-size:18px;color:#000;font-family:Arial,Helvetica,sans-serif">Pangandaran.in</div>'
    +'</div></div>';
}

// FIX: invPDF dan invWA sesuai dengan ID di HTML
async function openInvoice(id){
  var r=await(await fetch('admin_booking.php?act=get_booking&id='+id)).json();
  if(!r.ok)return;
  var d=r.data; var items=d.items||[];
  document.getElementById('moInvBody').innerHTML=buildInvHTML(d,items);

  // Wire tombol PDF dan WA
  var btnPDF=document.getElementById('invPDF');
  var btnWA=document.getElementById('invWA');
  if(btnPDF) btnPDF.onclick=function(){genPDF(d,items);};
  if(btnWA) btnWA.onclick=function(){
    var fD=function(n){return(parseInt(n)||0).toLocaleString('id-ID');};
    var tinv=parseInt(d.total_invoice||0),dp=parseInt(d.dp||0),sisa=parseInt(d.sisa_bayar||0)||Math.max(0,tinv-dp);
    var li=items.map(function(it){return '- '+it.produk+' x'+it.qty+' = Rp '+fD(it.subtotal);}).join('\n');
    if(d.status_bayar==='Lunas'||d.status_bayar==='Paid Off'){ dp=tinv; sisa=0; }
    var msg='Invoice Pangandaran.in\n\nNo. Invoice: '+d.no_invoice+'\nNama: '+d.nama+'\nWhatsApp: '+(d.whatsapp||'-')+'\nMedia Sosial: '+(d.medsos||'-')+'\nTanggal Kegiatan: '+(d.tanggal||'-')+'\nWaktu Kegiatan: '+(d.waktu_kegiatan||'-')+'\nStatus: '+d.status_bayar+'\n\nItem Dipesan:\n'+li+'\n\nTotal Invoice : Rp '+fD(tinv)+'\nTerbayar      : Rp '+fD(dp)+'\nSisa Tagihan  : Rp '+fD(sisa);
    var num='6285930478524';
    alert('Nomor WA yang dipakai: '+num);
    window.open('https://web.whatsapp.com/send?phone='+num+'&text='+encodeURIComponent(msg),'_blank');
  };
  openM('moInv');
}

async function genPDF(d, itemsArg){
  var jspdf = window.jspdf;
  var doc = new jspdf.jsPDF({unit:'mm', format:'a4'});
  var items = itemsArg || d.items || [];
  var tot   = parseInt(d.total_harga   || 0);
  var disc  = parseInt(d.discount      || 0);
  var tinv  = parseInt(d.total_invoice || 0) || Math.max(0, tot - disc);
  var dp    = parseInt(d.dp            || 0);
  var isLunas = (d.status_bayar==='Lunas' || d.status_bayar==='Paid Off');
  var sisa  = isLunas ? 0 : (parseInt(d.sisa_bayar || 0) || Math.max(0, tinv - dp));
  if(isLunas) dp = tinv;
  var sc    = isLunas ? [22,163,74] : [217,119,6];
  var tgl   = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
  function fD(n){return(parseInt(n)||0).toLocaleString('id-ID');}

  // ── Logo circular crop ──
  await new Promise(function(resolve){
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function(){
      try{
        var cv = document.createElement('canvas');
        cv.width = img.naturalWidth || 120; cv.height = img.naturalHeight || 120;
        var ctx = cv.getContext('2d');
        ctx.beginPath(); ctx.arc(cv.width/2, cv.height/2, cv.width/2, 0, Math.PI*2); ctx.clip();
        ctx.drawImage(img, 0, 0, cv.width, cv.height);
        doc.addImage(cv.toDataURL('image/png'), 'PNG', 13, 10, 24, 24);
      } catch(e){}
      resolve();
    };
    img.onerror = resolve;
    img.src = 'assets/logo.png';
    setTimeout(resolve, 3000);
  });

  // ── Brand kiri ──
  doc.setFont('helvetica','bold'); doc.setFontSize(18); doc.setTextColor(0);
  doc.text('Pangandaran.in', 41, 18);
  doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(130);
  doc.text('CV. Pangandaran in Group', 41, 24);

  // ── Kontak kanan (teks saja, rapi) ──
  doc.setFontSize(8); doc.setTextColor(100);
  doc.text('https://pangandaran.in',       196, 12, {align:'right'});
  doc.text('pangandaraningroup@gmail.com', 196, 17, {align:'right'});
  doc.text('@pangandaran.in',              196, 22, {align:'right'});

  // ── Receipt ──
  doc.setFont('helvetica','bold'); doc.setFontSize(24); doc.setTextColor(0);
  doc.text('Receipt', 196, 34, {align:'right'});

  // ── Divider ──
  doc.setDrawColor(180); doc.setLineWidth(0.5); doc.line(13, 41, 196, 41);

  // ── Bill to ──
  doc.setFont('helvetica','bold'); doc.setFontSize(12); doc.setTextColor(0);
  doc.text('Bill to', 13, 52);
  var billRows = [['Name', d.nama||'—'],['Media Sosial', d.medsos||'—'],['WhatsApp', d.whatsapp||'—']];
  doc.setFontSize(9);
  billRows.forEach(function(row, i){
    var y = 60 + i*8;
    doc.setFont('helvetica','normal'); doc.setTextColor(120); doc.text(row[0], 13, y);
    doc.setTextColor(120); doc.text(':', 35, y);
    doc.setFont('helvetica','bold'); doc.setTextColor(30); doc.text(row[1]||'—', 39, y);
  });

  // ── Payment Details ──
  doc.setFont('helvetica','bold'); doc.setFontSize(12); doc.setTextColor(0);
  doc.text('Payment Details', 108, 52);
  var payRows = [
    ['No. Order',       d.no_invoice||'—',                      [0,89,179]],
    ['Status',          d.status_bayar||'—',                     sc],
    ['Booking Date',    tgl,                                     [30,30,30]],
    ['Trip Date',       d.tanggal||'—',                          [30,30,30]],
    ['Trip Time',       d.waktu_kegiatan||'—',                   [30,30,30]],
    ['Payment Method',  (d.metode_bayar==='dp'?'DP':'Full Payment'), [30,30,30]],
  ];
  doc.setFontSize(9);
  payRows.forEach(function(row, i){
    var y = 60 + i*8;
    doc.setFont('helvetica','normal'); doc.setTextColor(120); doc.text(row[0], 108, y);
    doc.setTextColor(120); doc.text(':', 140, y);
    doc.setFont('helvetica','bold'); doc.setTextColor(row[2][0], row[2][1], row[2][2]); doc.text(row[1], 144, y);
  });

  // ── Order Details title ──
  var tableStartY = 116;
  doc.setFont('helvetica','bold'); doc.setFontSize(12); doc.setTextColor(0);
  doc.text('Order Details', 13, tableStartY - 4);

  // ── Table ──
  doc.autoTable({
    startY: tableStartY,
    head: [['Product', 'Price\n(Rp)', 'QTY', 'Total\n(Rp)']],
    body: items.map(function(it){
      return [it.produk||'—', fD(it.harga), String(it.qty||1), fD(it.subtotal)];
    }),
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:[40], cellPadding:4 },
    headStyles: {
      fillColor:[255,255,255], textColor:[0], fontStyle:'bold', fontSize:9,
      lineWidth:{top:0.6, bottom:0.6}, lineColor:[40]
    },
    columnStyles: {
      0: { cellWidth:'auto', halign:'left' },
      1: { cellWidth:28, halign:'right' },
      2: { cellWidth:14, halign:'center' },
      3: { cellWidth:28, halign:'right' },
    },
    bodyStyles: { lineWidth:{bottom:0.2}, lineColor:[200] },
    margin: {left:13, right:13},
  });

  // ── Summary ──
  var y = doc.lastAutoTable.finalY + 5;
  var sumRows = [
    ['Total',                fD(tot),                 false, false],
    ['Discount (Rp.)',       fD(disc),                false, false],
    ['Total Invoice Amount', fD(tinv),                true,  false],
    ['Ammount Paid (DP)',    (!isLunas && dp>0) ? fD(dp) : '—', false, false],
    ['Amount Paid (Final)',  isLunas ? fD(tinv) : '—',false, false],
    ['Outstanding Balance',  fD(sisa),                false, true ],
  ];

  sumRows.forEach(function(row){
    var label=row[0], val=row[1], topLine=row[2], bottomLine=row[3];
    if(topLine){
      doc.setDrawColor(40); doc.setLineWidth(0.5); doc.line(118, y-3, 197, y-3);
      y += 2;
    }
    doc.setFont('helvetica','bold');
    doc.setFontSize(bottomLine ? 10.5 : 9);
    doc.setTextColor(0);
    doc.text(label, 118, y);
    doc.text(val,   197, y, {align:'right'});
    y += 8;
    if(bottomLine){
      doc.setDrawColor(40); doc.setLineWidth(0.5); doc.line(118, y-3, 197, y-3);
    }
  });

  // ── Footer ──
  y += 18;
  doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(80);
  doc.text('Best Regards', 197, y, {align:'right'});
  doc.setFontSize(13); doc.setTextColor(0);
  doc.text('Pangandaran.in', 197, y+8, {align:'right'});

  doc.save('Receipt_' + (d.no_invoice||'Invoice') + '.pdf');
}

</script>
</body>
</html>