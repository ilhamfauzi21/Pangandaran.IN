<?php
session_start();

// ============================================================
// AUTH
// ============================================================
define('ADMIN_USER','admin');
define('ADMIN_PASS','Pangandaran.in');

if(isset($_POST['login'])){
    if($_POST['u']===ADMIN_USER && $_POST['p']===ADMIN_PASS) $_SESSION['adm']=true;
    else $_SESSION['err']='Username atau password salah.';
}
if(isset($_GET['logout'])){ session_destroy(); header('Location: admin.php'); exit; }

if(!isset($_SESSION['adm'])):
?><!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Pangandaran.in</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#00132f;color:#d6e3ff;min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:rgba(5,27,57,.88);border:1px solid rgba(162,231,255,.12);border-radius:22px;padding:44px 40px;width:380px;backdrop-filter:blur(20px)}
.logo{display:flex;align-items:center;gap:10px;margin-bottom:30px}
.logo img{width:46px;height:46px;border-radius:50%;object-fit:cover}
.logo-t{font-size:18px;font-weight:700;color:#fff;font-family:Segoe UI}
.logo-s{font-size:10px;color:rgba(162,231,255,.4);text-transform:uppercase;letter-spacing:1.5px;display:block;margin-top:2px}
h1{font-size:22px;font-weight:700;margin-bottom:6px}
p.sub{font-size:12px;color:rgba(214,227,255,.5);margin-bottom:28px}
label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:rgba(162,231,255,.5);display:block;margin-bottom:7px}
input{width:100%;padding:12px 16px;border-radius:11px;border:1px solid rgba(162,231,255,.12);background:rgba(10,31,61,.9);color:#d6e3ff;font-size:14px;margin-bottom:16px;outline:none;transition:.2s}
input:focus{border-color:rgba(162,231,255,.3)}
.err{background:rgba(255,80,80,.1);border:1px solid rgba(255,80,80,.2);color:#ff8080;padding:10px 14px;border-radius:10px;font-size:12px;margin-bottom:16px}
button{width:100%;padding:14px;border-radius:11px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:14px;border:none;cursor:pointer;transition:.2s}
button:hover{opacity:.9;transform:translateY(-1px)}
</style></head>
<body>
<div class="card">
  <div class="logo">
    <img src="assets/logo.png" onerror="this.style.display='none'">
    <div><span class="logo-t">Pangandaran.in</span><span class="logo-s">Admin Panel</span></div>
  </div>
  <h1>Masuk</h1>
  <p class="sub">Sistem Operasional Utama Pangandaran.in</p>
  <?php if(isset($_SESSION['err'])){echo"<div class='err'>".$_SESSION['err']."</div>";unset($_SESSION['err']);}?>
  <form method="POST"><input type="hidden" name="login" value="1">
    <label>Username</label><input type="text" name="u" placeholder="admin" required>
    <label>Password</label><input type="password" name="p" placeholder="••••••" required>
    <button type="submit">Masuk ke Dashboard</button>
  </form>
</div>
</body></html>
<?php exit; endif;

// ============================================================
// DB
// ============================================================
$db = new mysqli("localhost","root","","pangandaran_db");

$db->query("CREATE TABLE IF NOT EXISTS paket_item (id INT AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(255),harga INT DEFAULT 0,satuan VARCHAR(30) DEFAULT 'orang',kategori VARCHAR(50) DEFAULT 'sea',aktif TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("CREATE TABLE IF NOT EXISTS booking (id INT AUTO_INCREMENT PRIMARY KEY,no_invoice VARCHAR(30),nama VARCHAR(255),email VARCHAR(100),whatsapp VARCHAR(30),medsos VARCHAR(100),tanggal DATE,waktu_kegiatan VARCHAR(100),catatan TEXT,sumber VARCHAR(30) DEFAULT 'booking',total_harga INT DEFAULT 0,discount INT DEFAULT 0,total_invoice INT DEFAULT 0,dp INT DEFAULT 0,sisa_bayar INT DEFAULT 0,status_bayar VARCHAR(30) DEFAULT 'Pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("CREATE TABLE IF NOT EXISTS booking_items (id INT AUTO_INCREMENT PRIMARY KEY,booking_id INT,produk VARCHAR(255),harga INT DEFAULT 0,qty INT DEFAULT 1,subtotal INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Pastikan kolom tanggal_pelunasan ada
$cekTglPelunasan = $db->query("SHOW COLUMNS FROM booking LIKE 'tanggal_pelunasan'");

if($cekTglPelunasan && $cekTglPelunasan->num_rows == 0){
    $db->query("ALTER TABLE booking ADD COLUMN tanggal_pelunasan DATE NULL AFTER sisa_bayar");
}

$cnt = $db->query("SELECT COUNT(*) as c FROM paket_item")->fetch_assoc()['c'];
if($cnt == 0){
    $db->query("INSERT INTO paket_item (nama,harga,satuan,kategori) VALUES
    ('Green Canyon Body Rafting',250000,'orang','river'),('Ciwayang Body Rafting',150000,'orang','river'),
    ('Citumang Body Rafting',150000,'orang','river'),('Santirah River Tubing',150000,'orang','river'),
    ('One Day Tour Package',260000,'orang','multi'),('2 Day 1 Night Package',700000,'orang','multi'),
    ('3 Day 2 Night Package',700000,'orang','multi'),('Custom Trip',0,'nego','multi'),
    ('Jetski SeaDoo 15 Menit',350000,'unit','sea'),('Jetski + Drone Aerial Premium',650000,'unit','sea'),
    ('Jetski + FPV Drone Exclusive',1200000,'unit','sea'),('Water Sport 3 Wahana',150000,'orang','sea'),
    ('Stand Up Paddle',350000,'orang','sea'),('Snorkeling',150000,'orang','sea'),
    ('Dokumentasi Drone Aerial',250000,'sesi','sea'),('Dokumentasi FPV Drone',400000,'sesi','sea'),
    ('Photo Session',200000,'sesi','outbound'),('Fun Game',150000,'orang','outbound'),
    ('Team Building',200000,'orang','outbound')");
}

// ============================================================
// AUTO SYNC PAKET WEBSITE KE ITEM BOOKING
// ============================================================
// Tujuan:
// Paket yang dibuat di admin_paket.php tersimpan di tabel `paket`.
// Kelola Item Booking membaca tabel `paket_item`.
// Bagian ini menyamakan isi `paket` ke `paket_item` setiap admin.php dibuka,
// tanpa mengubah struktur tampilan admin.

$cekPaketIdCol = $db->query("SHOW COLUMNS FROM paket_item LIKE 'paket_id'");
if($cekPaketIdCol && $cekPaketIdCol->num_rows == 0){
    $db->query("ALTER TABLE paket_item ADD COLUMN paket_id VARCHAR(50) NULL AFTER id");
}

$cekPaketTable = $db->query("SHOW TABLES LIKE 'paket'");
if($cekPaketTable && $cekPaketTable->num_rows > 0){

    // Pastikan kolom yang dibutuhkan dari tabel paket tersedia.
    $cekUnitPaket = $db->query("SHOW COLUMNS FROM paket LIKE 'unit'");
    if($cekUnitPaket && $cekUnitPaket->num_rows == 0){
        $db->query("ALTER TABLE paket ADD COLUMN unit VARCHAR(30) DEFAULT '/ orang'");
    }

    $cekHargaPaket = $db->query("SHOW COLUMNS FROM paket LIKE 'harga'");
    if($cekHargaPaket && $cekHargaPaket->num_rows == 0){
        $db->query("ALTER TABLE paket ADD COLUMN harga INT DEFAULT 0");
    }

    $cekKategoriPaket = $db->query("SHOW COLUMNS FROM paket LIKE 'kategori'");
    if($cekKategoriPaket && $cekKategoriPaket->num_rows == 0){
        $db->query("ALTER TABLE paket ADD COLUMN kategori VARCHAR(50) DEFAULT 'sea'");
    }

    // Bersihkan beberapa item default lama yang tidak lagi mengikuti nama paket website.
    // Ini hanya menghapus item lama yang belum terhubung ke paket_id.
    $db->query("
        DELETE FROM paket_item
        WHERE paket_id IS NULL
        AND LOWER(TRIM(nama)) IN (
            'jetski seadoo 15 menit',
            'jetski + drone aerial premium',
            'jetski + fpv drone exclusive',
            'water sport 3 wahana',
            'dokumentasi drone aerial',
            'dokumentasi fpv drone',
            'ilham fauzi',
            'qwww'
        )
    ");

    $paketRows = $db->query("
        SELECT id, nama, harga, unit, kategori
        FROM paket
        WHERE nama IS NOT NULL
        AND TRIM(nama) <> ''
    ");

    if($paketRows){
        while($p = $paketRows->fetch_assoc()){
            $paket_id = trim($p['id'] ?? '');
            $nama     = trim($p['nama'] ?? '');
            $harga    = intval($p['harga'] ?? 0);
            $satuan   = trim(str_replace('/', '', $p['unit'] ?? 'orang'));
            $kategori = trim($p['kategori'] ?? 'sea');

            if($paket_id === '' || $nama === '') continue;
            if($satuan === '') $satuan = 'orang';
            if($kategori === '') $kategori = 'sea';

            // 1. Kalau item sudah terhubung dengan paket_id, update saja.
            $cek = $db->prepare("SELECT id FROM paket_item WHERE paket_id = ? LIMIT 1");
            $cek->bind_param("s", $paket_id);
            $cek->execute();
            $ada = $cek->get_result();

            if($ada && $ada->num_rows > 0){
                $row = $ada->fetch_assoc();
                $item_id = intval($row['id']);

                $up = $db->prepare("
                    UPDATE paket_item
                    SET nama = ?, harga = ?, satuan = ?, kategori = ?, aktif = 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $up->bind_param("sissi", $nama, $harga, $satuan, $kategori, $item_id);
                $up->execute();
                continue;
            }

            // 2. Kalau data lama sudah ada dengan nama yang sama, hubungkan ke paket_id.
            $cekNama = $db->prepare("
                SELECT id FROM paket_item
                WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?))
                LIMIT 1
            ");
            $cekNama->bind_param("s", $nama);
            $cekNama->execute();
            $adaNama = $cekNama->get_result();

            if($adaNama && $adaNama->num_rows > 0){
                $rowNama = $adaNama->fetch_assoc();
                $item_id = intval($rowNama['id']);

                $up = $db->prepare("
                    UPDATE paket_item
                    SET paket_id = ?, nama = ?, harga = ?, satuan = ?, kategori = ?, aktif = 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $up->bind_param("ssissi", $paket_id, $nama, $harga, $satuan, $kategori, $item_id);
                $up->execute();
                continue;
            }

            // 3. Kalau belum ada, buat item baru.
            $in = $db->prepare("
                INSERT INTO paket_item (paket_id, nama, harga, satuan, kategori, aktif)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $in->bind_param("ssiss", $paket_id, $nama, $harga, $satuan, $kategori);
            $in->execute();
        }
    }

    // Hapus item yang dulu tersambung ke paket website, tetapi paketnya sudah dihapus.
    $db->query("
        DELETE pi FROM paket_item pi
        LEFT JOIN paket p ON p.id = pi.paket_id
        WHERE pi.paket_id IS NOT NULL
        AND p.id IS NULL
    ");
}


// ============================================================
// ITEM BOOKING MAINTENANCE
// ============================================================
// Perbaikan ini tidak mengubah struktur tampilan. Fungsinya hanya:
// 1) membersihkan item dobel,
// 2) menghapus data dummy yang tidak valid,
// 3) mencegah item yang sama masuk dua kali lagi.

// Hapus data dummy / salah input jika pernah masuk ke item booking.
$db->query("DELETE FROM paket_item WHERE LOWER(TRIM(nama)) = 'ilham fauzi'");

// Hapus duplikat item booking yang memiliki nama, harga, satuan, dan kategori yang sama.
// Data yang dipertahankan adalah baris dengan id paling kecil.
$db->query("
    DELETE p1 FROM paket_item p1
    INNER JOIN paket_item p2
        ON LOWER(TRIM(p1.nama)) = LOWER(TRIM(p2.nama))
        AND p1.harga = p2.harga
        AND LOWER(TRIM(p1.satuan)) = LOWER(TRIM(p2.satuan))
        AND LOWER(TRIM(p1.kategori)) = LOWER(TRIM(p2.kategori))
        AND p1.id > p2.id
");

// Tambahkan unique index agar item yang sama tidak bisa tersimpan dobel lagi.
// Jika index sudah ada, bagian ini akan dilewati.
$cekIndexPaketItem = $db->query("SHOW INDEX FROM paket_item WHERE Key_name = 'uniq_paket_item_clean'");
if($cekIndexPaketItem && $cekIndexPaketItem->num_rows == 0){
    $db->query("ALTER TABLE paket_item ADD UNIQUE KEY uniq_paket_item_clean (nama(191), harga, satuan, kategori)");
}

// ============================================================
// AJAX
// ============================================================
$act = $_POST['act'] ?? $_GET['act'] ?? '';

if($act === 'get_items_json'){
    header('Content-Type: application/json');
    $rows=[];
    $r=$db->query("
        SELECT p.*
        FROM paket_item p
        INNER JOIN (
            SELECT MIN(id) AS id
            FROM paket_item
            WHERE aktif = 1
            GROUP BY LOWER(TRIM(nama)), harga, LOWER(TRIM(satuan)), LOWER(TRIM(kategori))
        ) x ON x.id = p.id
        ORDER BY p.kategori, p.nama
    ");
    while($row=$r->fetch_assoc()) $rows[]=$row;
    echo json_encode($rows); exit;
}

if($act === 'save_item'){
    header('Content-Type: application/json');

    $id = intval($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'orang');
    $kat = trim($_POST['kategori'] ?? 'sea');

    if($nama === ''){
        echo json_encode(['ok'=>false,'msg'=>'Nama item wajib diisi.']);
        exit;
    }

    if($id){
        $s = $db->prepare("UPDATE paket_item SET nama=?, harga=?, satuan=?, kategori=? WHERE id=?");
        $s->bind_param("sissi", $nama, $harga, $satuan, $kat, $id);
        $s->execute();

        echo json_encode(['ok'=>true,'id'=>$id]);
        exit;
    }

    // Cek item yang sama sebelum insert agar tidak dobel.
    $cek = $db->prepare("
        SELECT id FROM paket_item
        WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?))
        AND harga = ?
        AND LOWER(TRIM(satuan)) = LOWER(TRIM(?))
        AND LOWER(TRIM(kategori)) = LOWER(TRIM(?))
        LIMIT 1
    ");
    $cek->bind_param("siss", $nama, $harga, $satuan, $kat);
    $cek->execute();
    $hasil = $cek->get_result();

    if($hasil && $hasil->num_rows > 0){
        $row = $hasil->fetch_assoc();
        $existingId = intval($row['id']);

        // Kalau item sudah ada, aktifkan kembali saja. Jangan insert baris baru.
        $up = $db->prepare("UPDATE paket_item SET aktif=1 WHERE id=?");
        $up->bind_param("i", $existingId);
        $up->execute();

        echo json_encode(['ok'=>true,'id'=>$existingId,'duplicate'=>true]);
        exit;
    }

    $s = $db->prepare("INSERT INTO paket_item (nama,harga,satuan,kategori) VALUES (?,?,?,?)");
    $s->bind_param("siss", $nama, $harga, $satuan, $kat);
    $s->execute();

    echo json_encode(['ok'=>true,'id'=>$db->insert_id]);
    exit;
}

if($act === 'toggle_item'){
    header('Content-Type: application/json');
    $id=intval($_POST['id']??0);
    $db->query("UPDATE paket_item SET aktif=IF(aktif=1,0,1) WHERE id=$id");
    echo json_encode(['ok'=>true]); exit;
}

if($act === 'delete_item'){
    header('Content-Type: application/json');
    $id=intval($_POST['id']??0);$db->query("DELETE FROM paket_item WHERE id=$id");
    echo json_encode(['ok'=>true]); exit;
}

if($act === 'delete_booking'){
    header('Content-Type: application/json');

    $id = intval($_POST['id'] ?? 0);
    if($id <= 0){
        echo json_encode(['ok'=>false,'msg'=>'ID booking tidak valid.']);
        exit;
    }

    $cek = $db->prepare("SELECT id FROM booking WHERE id=? LIMIT 1");
    $cek->bind_param("i", $id);
    $cek->execute();
    $ada = $cek->get_result();

    if(!$ada || $ada->num_rows == 0){
        echo json_encode(['ok'=>false,'msg'=>'Data booking tidak ditemukan.']);
        exit;
    }

    $db->begin_transaction();

    $s1 = $db->prepare("DELETE FROM booking_items WHERE booking_id=?");
    $s1->bind_param("i", $id);
    $ok1 = $s1->execute();

    $s2 = $db->prepare("DELETE FROM booking WHERE id=?");
    $s2->bind_param("i", $id);
    $ok2 = $s2->execute();

    if($ok1 && $ok2 && $s2->affected_rows > 0){
        $db->commit();
        echo json_encode(['ok'=>true]);
    } else {
        $db->rollback();
        echo json_encode(['ok'=>false,'msg'=>'Gagal menghapus data booking.']);
    }
    exit;
}

if($act === 'save_booking'){
    header('Content-Type: application/json');
    $bid=intval($_POST['bid']??0);$nama=trim($_POST['nama']??'');$email=trim($_POST['email']??'');
    $wa=trim($_POST['whatsapp']??'');$medsos=trim($_POST['medsos']??'');$tgl=$_POST['tanggal']??date('Y-m-d');
    $catatan=trim($_POST['catatan']??'');$waktu=trim($_POST['waktu']??'');
    $discount=intval($_POST['discount']??0);$dp=intval($_POST['dp']??0);
    $items=json_decode($_POST['items']??'[]',true)?:[];
    $total=0;
    foreach($items as &$it){$it['harga']=intval($it['harga']??0);$it['qty']=max(1,intval($it['qty']??1));$it['subtotal']=$it['harga']*$it['qty'];$total+=$it['subtotal'];}
    $tinv=max(0,$total-$discount);$sisa=max(0,$tinv-$dp);$status=$_POST['status']??'Pending';
    if($bid){
        $s=$db->prepare("UPDATE booking SET nama=?,email=?,whatsapp=?,medsos=?,tanggal=?,waktu_kegiatan=?,catatan=?,total_harga=?,discount=?,total_invoice=?,dp=?,sisa_bayar=?,status_bayar=?,updated_at=NOW() WHERE id=?");
        $s->bind_param("sssssssiiiiisi",$nama,$email,$wa,$medsos,$tgl,$waktu,$catatan,$total,$discount,$tinv,$dp,$sisa,$status,$bid);
        $s->execute();
        $db->query("DELETE FROM booking_items WHERE booking_id=$bid");
    } else {
        $no='PNT'.date('ymd').str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $src='admin';
        $s=$db->prepare("INSERT INTO booking (no_invoice,nama,email,whatsapp,medsos,tanggal,waktu_kegiatan,catatan,sumber,total_harga,discount,total_invoice,dp,sisa_bayar,status_bayar) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssssssssiiiiis",$no,$nama,$email,$wa,$medsos,$tgl,$waktu,$catatan,$src,$total,$discount,$tinv,$dp,$sisa,$status);
        $s->execute();$bid=$db->insert_id;
    }
    $si=$db->prepare("INSERT INTO booking_items (booking_id,produk,harga,qty,subtotal) VALUES (?,?,?,?,?)");
    foreach($items as $it){$si->bind_param("isiii",$bid,$it['produk'],$it['harga'],$it['qty'],$it['subtotal']);$si->execute();}
    echo json_encode(['ok'=>true]); exit;
}

if($act === 'update_status'){
    header('Content-Type: application/json');

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';

    // Bersihkan angka dari format titik
    // Contoh: 100.000 jadi 100000
    $dp_input = intval(preg_replace('/[^0-9]/', '', $_POST['dp'] ?? '0'));
    $pelunasan_cash = intval(preg_replace('/[^0-9]/', '', $_POST['pelunasan_cash'] ?? '0'));

    $tanggal_pelunasan = trim($_POST['tanggal_pelunasan'] ?? '');

    $q = $db->query("SELECT total_invoice, dp, sisa_bayar FROM booking WHERE id=$id");

    if(!$q || $q->num_rows == 0){
        echo json_encode([
            'ok' => false,
            'msg' => 'Data booking tidak ditemukan.'
        ]);
        exit;
    }

    $bk = $q->fetch_assoc();

    $total_invoice = intval($bk['total_invoice'] ?? 0);

    // Total terbayar = DP saat ini + pelunasan cash
    $total_terbayar = $dp_input + $pelunasan_cash;

    // Jika admin pilih Lunas, langsung lunaskan
    if($status === 'Lunas'){
        $dp_baru = $total_invoice;
        $sisa = 0;
        $status = 'Lunas';

        if($tanggal_pelunasan === ''){
            $tanggal_pelunasan = date('Y-m-d');
        }
    }

    // Jika status DP, cek apakah total terbayar sudah lunas
    elseif($status === 'DP'){
        if($total_terbayar >= $total_invoice){
            $dp_baru = $total_invoice;
            $sisa = 0;
            $status = 'Lunas';

            if($tanggal_pelunasan === ''){
                $tanggal_pelunasan = date('Y-m-d');
            }
        } else {
            $dp_baru = $total_terbayar;
            $sisa = max(0, $total_invoice - $dp_baru);
            $status = 'DP';
            $tanggal_pelunasan = null;
        }
    }

    // Jika Pending
    elseif($status === 'Pending'){
        $dp_baru = $dp_input;

        if($dp_baru > $total_invoice){
            $dp_baru = $total_invoice;
        }

        $sisa = max(0, $total_invoice - $dp_baru);
        $tanggal_pelunasan = null;
    }

    // Jika Batal / Refund
    else {
        $dp_baru = $dp_input;

        if($dp_baru > $total_invoice){
            $dp_baru = $total_invoice;
        }

        $sisa = max(0, $total_invoice - $dp_baru);
        $tanggal_pelunasan = null;
    }

    $status_safe = $db->real_escape_string($status);

    if($tanggal_pelunasan){
        $tgl_safe = $db->real_escape_string($tanggal_pelunasan);
        $tgl_sql = "'$tgl_safe'";
    } else {
        $tgl_sql = "NULL";
    }

    $sqlUpdate = "
        UPDATE booking 
        SET dp = $dp_baru,
            sisa_bayar = $sisa,
            status_bayar = '$status_safe',
            tanggal_pelunasan = $tgl_sql,
            updated_at = NOW()
        WHERE id = $id
    ";

    $update = $db->query($sqlUpdate);

    if(!$update){
        echo json_encode([
            'ok' => false,
            'msg' => 'Gagal update database: ' . $db->error
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'status_bayar' => $status,
        'tanggal_pelunasan' => $tanggal_pelunasan
    ]);
    exit;
}

if($act === 'get_booking'){
    header('Content-Type: application/json');
    $id=intval($_GET['id']??0);
    $bk=$db->query("SELECT * FROM booking WHERE id=$id")->fetch_assoc();
    if(!$bk){echo json_encode(['ok'=>false]);exit;}
    $items=[];$ir=$db->query("SELECT * FROM booking_items WHERE booking_id=$id");
    while($row=$ir->fetch_assoc()) $items[]=$row;
    $bk['items']=$items;
    echo json_encode(['ok'=>true,'data'=>$bk]); exit;
}

if($act === 'export_excel'){
    while(ob_get_level()) ob_end_clean();
    $fs=$_GET['fs']??'';$from=$_GET['from']??'';$to=$_GET['to']??'';$q=$_GET['q']??'';

    // filter: $wA pakai alias b. (untuk query join), $wN tanpa alias
    // Export Excel selalu membaca database TERBARU, jadi setelah data booking dihapus
    // jumlah transaksi dan omzet di Excel akan mengikuti halaman Laporan.
    $wA='WHERE 1=1'; $wN='WHERE 1=1';
    if($fs){   $fsSafe=$db->real_escape_string($fs); $wA.=" AND b.status_bayar='$fsSafe'"; $wN.=" AND status_bayar='$fsSafe'"; }
    if($from){ $fromSafe=$db->real_escape_string($from); $wA.=" AND b.tanggal>='$fromSafe'";   $wN.=" AND tanggal>='$fromSafe'"; }
    if($to){   $toSafe=$db->real_escape_string($to); $wA.=" AND b.tanggal<='$toSafe'";     $wN.=" AND tanggal<='$toSafe'"; }
    if($q){
        $qSafe=$db->real_escape_string($q);
        $wA.=" AND (b.nama LIKE '%$qSafe%' OR b.no_invoice LIKE '%$qSafe%' OR b.whatsapp LIKE '%$qSafe%' OR b.medsos LIKE '%$qSafe%')";
        $wN.=" AND (nama LIKE '%$qSafe%' OR no_invoice LIKE '%$qSafe%' OR whatsapp LIKE '%$qSafe%' OR medsos LIKE '%$qSafe%')";
    }

    // ===== REKAP (sama dengan halaman Laporan Omzet) =====
    $sum   = $db->query("SELECT SUM(total_invoice) t,SUM(dp) d,SUM(sisa_bayar) s,COUNT(*) c FROM booking $wN");
    $sum   = $sum ? $sum->fetch_assoc() : ['t'=>0,'d'=>0,'s'=>0,'c'=>0];
    $perst = $db->query("SELECT status_bayar,COUNT(*) cnt,SUM(total_invoice) total,SUM(dp) tdp FROM booking $wN GROUP BY status_bayar");
    $top   = $db->query("SELECT bi.produk,COUNT(*) cnt,SUM(bi.subtotal) total FROM booking_items bi LEFT JOIN booking b ON b.id=bi.booking_id $wA GROUP BY bi.produk ORDER BY total DESC LIMIT 10");
    $det   = $db->query("SELECT b.*,GROUP_CONCAT(CONCAT(bi.produk,' x',bi.qty) SEPARATOR ', ') ilist FROM booking b LEFT JOIN booking_items bi ON bi.booking_id=b.id $wA GROUP BY b.id ORDER BY b.created_at DESC");

    $periode = ($from||$to) ? (($from?:'awal').' s/d '.($to?:'sekarang')) : 'Semua Periode';
    $statusLabel = $fs ?: 'Semua Status';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Laporan_Omzet_Pangandaran_'.date('Ymd_His').'.xls"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";
    ?>
    <html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><style>
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
    </style></head><body>

    <table><tr><td colspan="4" class="title">LAPORAN OMZET &amp; DATA BOOKING — Pangandaran.in</td></tr>
    <tr><td colspan="4" class="sub">CV Pangandaran in Group&nbsp;|&nbsp;Periode: <?=$periode?>&nbsp;|&nbsp;Status: <?=$statusLabel?>&nbsp;|&nbsp;Dicetak: <?=date('d/m/Y H:i')?></td></tr></table>

    <!-- RINGKASAN -->
    <table>
      <tr><td colspan="2" class="sec">RINGKASAN OMZET</td></tr>
      <tr><td class="lbl">Total Transaksi</td><td class="num"><?=(int)$sum['c']?></td></tr>
      <tr><td class="lbl">Total Invoice</td><td class="num"><?=(int)$sum['t']?></td></tr>
      <tr><td class="lbl">Total Terbayar</td><td class="num"><?=(int)$sum['d']?></td></tr>
      <tr><td class="lbl">Sisa Tagihan</td><td class="num"><?=(int)$sum['s']?></td></tr>
    </table>

    <!-- PER STATUS -->
    <table>
      <tr><td colspan="4" class="sec">PER STATUS PEMBAYARAN</td></tr>
      <tr><th>Status</th><th>Jumlah</th><th>Total Invoice</th><th>Terbayar</th></tr>
      <?php if($perst) while($s=$perst->fetch_assoc()){ ?>
      <tr><td class="txt"><?=htmlspecialchars($s['status_bayar']?:'-')?></td>
          <td class="num"><?=(int)$s['cnt']?></td>
          <td class="num"><?=(int)$s['total']?></td>
          <td class="num"><?=(int)$s['tdp']?></td></tr>
      <?php } ?>
    </table>

    <!-- TOP LAYANAN -->
    <table>
      <tr><td colspan="3" class="sec">TOP LAYANAN YANG DIPESAN</td></tr>
      <tr><th>Layanan</th><th>Jumlah Dipesan</th><th>Total Pendapatan</th></tr>
      <?php if($top) while($t=$top->fetch_assoc()){ ?>
      <tr><td><?=htmlspecialchars($t['produk']?:'-')?></td>
          <td class="num"><?=(int)$t['cnt']?></td>
          <td class="num"><?=(int)$t['total']?></td></tr>
      <?php } ?>
    </table>

    <!-- DETAIL BOOKING -->
    <table>
      <tr><td colspan="13" class="sec">DETAIL DATA BOOKING</td></tr>
      <tr><th>No</th><th>No Invoice</th><th>Nama & Kontak</th><th>WhatsApp</th><th>Tanggal Trip</th>
          <th>Item Paket</th><th>Total</th><th>Diskon</th><th>Total Invoice</th>
          <th>DP/Terbayar</th><th>Sisa</th><th>Status</th><th>Tgl Booking</th></tr>
      <?php $n=0; if($det) while($r=$det->fetch_assoc()){ $n++; ?>
      <tr>
        <td class="num"><?=$n?></td>
        <td class="txt"><?=htmlspecialchars($r['no_invoice']??'')?></td>
        <td><?=htmlspecialchars($r['nama']??'')?></td>
        <td class="txt"><?=htmlspecialchars($r['whatsapp']??'')?></td>
        <td class="txt"><?=htmlspecialchars($r['tanggal']??'')?></td>
        <td><?=htmlspecialchars($r['ilist']??'')?></td>
        <td class="num"><?=(int)($r['total_harga']??0)?></td>
        <td class="num"><?=(int)($r['discount']??0)?></td>
        <td class="num"><?=(int)($r['total_invoice']??0)?></td>
        <td class="num"><?=(int)($r['dp']??0)?></td>
        <td class="num"><?=(int)($r['sisa_bayar']??0)?></td>
        <td class="txt"><?=htmlspecialchars($r['status_bayar']??'')?></td>
        <td class="txt"><?=htmlspecialchars($r['created_at']??'')?></td>
      </tr>
      <?php } ?>
      <tr class="tot"><td colspan="8" style="text-align:right">TOTAL (<?=$n?> transaksi)</td>
        <td class="num"><?=(int)$sum['t']?></td><td class="num"><?=(int)$sum['d']?></td>
        <td class="num"><?=(int)$sum['s']?></td><td colspan="2"></td></tr>
    </table>

    </body></html>
    <?php
    exit;
}

// ============================================================
// LOAD DATA
// ============================================================
$stats_res=$db->query("SELECT COUNT(*) as tb,SUM(total_invoice) as omzet,SUM(CASE WHEN status_bayar='Lunas' THEN total_invoice ELSE 0 END) as lunas_amt,SUM(CASE WHEN status_bayar='Pending' THEN 1 ELSE 0 END) as pending,SUM(CASE WHEN status_bayar='DP' THEN 1 ELSE 0 END) as dp_c,SUM(CASE WHEN status_bayar='Lunas' THEN 1 ELSE 0 END) as lunas_c FROM booking");
$stats=$stats_res ? $stats_res->fetch_assoc() : ['tb'=>0,'omzet'=>0,'lunas_amt'=>0,'pending'=>0,'dp_c'=>0,'lunas_c'=>0];
if(!$stats) $stats=['tb'=>0,'omzet'=>0,'lunas_amt'=>0,'pending'=>0,'dp_c'=>0,'lunas_c'=>0];
$omzet6=[];
$r6=$db->query("SELECT DATE_FORMAT(tanggal,'%b %Y') as bln,SUM(total_invoice) as total,COUNT(*) as cnt FROM booking WHERE tanggal>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(tanggal,'%Y-%m') ORDER BY tanggal");
if($r6) while($r=$r6->fetch_assoc()) $omzet6[]=$r;

$view=$_GET['v']??'dashboard';
$fs=$_GET['fs']??'';$fq=$_GET['q']??'';
$fromFilter=$_GET['from']??'';$toFilter=$_GET['to']??'';
$page=max(1,intval($_GET['page']??1));$pp=15;$offset=($page-1)*$pp;
$where="WHERE 1=1";
if($fs) $where.=" AND status_bayar='".addslashes($fs)."'";
if($fq) $where.=" AND (nama LIKE '%".addslashes($fq)."%' OR no_invoice LIKE '%".addslashes($fq)."%' OR whatsapp LIKE '%".addslashes($fq)."%' OR medsos LIKE '%".addslashes($fq)."%')";
// Hanya load data booking & paket saat view yang membutuhkan
$total_rows=0;$total_pages=1;$bookings=[];$paket_items=[];
if(in_array($view,['booking','invoice','dashboard'])){
    $tr_res=$db->query("SELECT COUNT(*) as c FROM booking $where");
    $total_rows=$tr_res ? ($tr_res->fetch_assoc()['c']??0) : 0;
    $total_pages=max(1,ceil($total_rows/$pp));
    $res=$db->query("
  SELECT 
    b.*,
    COALESCE(
      NULLIF(GROUP_CONCAT(CONCAT(bi.produk,' x',bi.qty) SEPARATOR ' | '), ''),
      NULLIF(b.paket, ''),
      '—'
    ) as ilist
  FROM booking b 
  LEFT JOIN booking_items bi ON bi.booking_id=b.id 
  $where 
  GROUP BY b.id 
  ORDER BY b.created_at DESC 
  LIMIT $pp OFFSET $offset
");
    if($res) while($r=$res->fetch_assoc()) $bookings[]=$r;
}
if(in_array($view,['items','booking'])){
    $rpi=$db->query("
        SELECT p.*
        FROM paket_item p
        INNER JOIN (
            SELECT MIN(id) AS id
            FROM paket_item
            GROUP BY LOWER(TRIM(nama)), harga, LOWER(TRIM(satuan)), LOWER(TRIM(kategori))
        ) x ON x.id = p.id
        ORDER BY p.aktif DESC, p.kategori, p.nama
    ");
    if($rpi) while($r=$rpi->fetch_assoc()) $paket_items[]=$r;
}

function rp($n){return number_format(intval($n),0,',','.');}
function badge($s){
    $map=['Lunas'=>['#22c55e','rgba(34,197,94,.12)'],'DP'=>['#f59e0b','rgba(245,158,11,.12)'],'Pending'=>['#94a3b8','rgba(148,163,184,.12)'],'Batal'=>['#ef4444','rgba(239,68,68,.12)'],'Refund'=>['#a78bfa','rgba(167,139,250,.12)']];
    [$c,$bg]=$map[$s]??['#94a3b8','rgba(148,163,184,.12)'];
    return "<span style='background:$bg;color:$c;border:1px solid {$c}40;font-size:9.5px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.3px;text-transform:uppercase'>$s</span>";
}
function kontak_info($r){
    $medsos = trim($r['medsos'] ?? '');
    $wa = trim($r['whatsapp'] ?? '');
    $email = trim($r['email'] ?? '');
    $lines = [];
    if($medsos !== ''){
        $lines[] = $medsos;
    } elseif($email !== '' && strtolower($email) !== 'noemail@pangandaran.in'){
        $lines[] = $email;
    }
    if($wa !== '') $lines[] = $wa;
    if(empty($lines)) return '—';
    return implode('<br>', array_map('htmlspecialchars', $lines));
}

$LOGO = '';
$lp = __DIR__.'/assets/logo.png';
if(file_exists($lp)) $LOGO = 'data:image/png;base64,'.base64_encode(file_get_contents($lp));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel — Pangandaran.in</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#00132f;--low:#051b39;--c:#0a1f3d;--high:#172a48;--border:rgba(162,231,255,.1);
  --cyan:#a2e7ff;--blue:#aac7ff;--text:#d6e3ff;--muted:rgba(214,227,255,.45)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-thumb{background:rgba(162,231,255,.12);border-radius:10px}
.sb{width:216px;background:rgba(0,14,37,.97);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:100}
.sb-logo{padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.sb-logo img{width:36px;height:36px;border-radius:50%;object-fit:cover}
.sb-name{font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:700;color:#fff}
.sb-sub{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:1.5px;display:block;margin-top:1px}
.sb-nav{padding:10px 8px;flex:1;overflow-y:auto}
.sb-section{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:rgba(162,231,255,.2);padding:10px 8px 4px}
.sb-link{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:10px;text-decoration:none;
  color:var(--muted);font-size:13px;font-weight:500;transition:.15s;cursor:pointer;border:none;background:none;width:100%;text-align:left}
.sb-link:hover{background:rgba(162,231,255,.06);color:var(--text)}
.sb-link.on{background:rgba(162,231,255,.1);color:var(--cyan)}
.sb-foot{padding:12px;border-top:1px solid var(--border)}
.sb-foot a{font-size:12px;color:rgba(255,100,100,.45);text-decoration:none;padding:7px 12px;border-radius:8px;display:block;transition:.15s}
.sb-foot a:hover{background:rgba(255,80,80,.08);color:#ff8080}
.main{margin-left:216px;flex:1;display:flex;flex-direction:column}
.topbar{height:52px;background:rgba(0,14,37,.95);border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;padding:0 22px;
  position:sticky;top:0;z-index:50;backdrop-filter:blur(20px)}
.topbar-t{font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:#fff}
.btn-p{padding:8px 18px;border-radius:9px;background:linear-gradient(135deg,#aac7ff,#0059b3);
  color:#001b3e;font-weight:700;font-size:12px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif;transition:.2s}
.btn-p:hover{opacity:.9;transform:translateY(-1px)}
.btn-s{padding:7px 14px;border-radius:9px;background:rgba(162,231,255,.07);
  border:1px solid var(--border);color:var(--muted);font-size:12px;cursor:pointer;transition:.15s;text-decoration:none}
.btn-s:hover{background:rgba(162,231,255,.12);color:var(--text)}
.btn-d{padding:5px 12px;border-radius:8px;background:rgba(255,80,80,.07);
  border:1px solid rgba(255,80,80,.18);color:#ff8080;font-size:11px;cursor:pointer;transition:.15s}
.btn-d:hover{background:rgba(255,80,80,.14)}
.wrap{padding:22px;flex:1}
.ph{margin-bottom:20px}
.pt{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:var(--muted);margin-bottom:4px}
.ptitle{font-family:'Space Grotesk',sans-serif;font-size:21px;font-weight:700;letter-spacing:-.3px}
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px}
.sc{background:var(--low);border:1px solid var(--border);border-radius:13px;padding:16px 18px}
.sv{font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:800;color:#fff;line-height:1;margin-bottom:4px}
.sl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px}
.tw{background:var(--low);border:1px solid var(--border);border-radius:13px;overflow:hidden}
.th{padding:13px 18px;display:flex;align-items:center;justify-content:space-between;
  border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px}
.th-title{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:14px}
.si{padding:8px 13px;border-radius:9px;border:1px solid var(--border);background:var(--c);
  color:var(--text);font-size:12px;outline:none;min-width:180px;transition:.15s}
.si:focus{border-color:rgba(162,231,255,.3)}
.sf{padding:8px 12px;border-radius:9px;border:1px solid var(--border);background:var(--c);
  color:var(--text);font-size:12px;outline:none;cursor:pointer}
table{width:100%;border-collapse:collapse}
thead th{padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;
  color:var(--muted);text-align:left;background:rgba(10,31,61,.5);border-bottom:1px solid var(--border)}
tbody tr{border-bottom:1px solid rgba(162,231,255,.04);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(162,231,255,.02)}
td{padding:10px 14px;font-size:12.5px;vertical-align:middle}
.es{text-align:center;padding:50px;color:var(--muted);font-size:14px}
.pg{display:flex;align-items:center;justify-content:space-between;padding:11px 18px;
  border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.pb{padding:5px 11px;border-radius:7px;border:1px solid var(--border);background:var(--c);
  color:var(--muted);font-size:11px;cursor:pointer;text-decoration:none;transition:.15s}
.pb:hover,.pb.on{background:rgba(162,231,255,.1);color:var(--cyan);border-color:rgba(162,231,255,.2)}
.mo{position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:500;display:none;
  align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px)}
.mo.show{display:flex}
.md{background:#0a1f3d;border:1px solid var(--border);border-radius:18px;
  width:100%;max-width:700px;max-height:90vh;overflow-y:auto;position:relative}
.mh{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.mt{font-family:'Space Grotesk',sans-serif;font-size:16px;font-weight:700;color:#fff}
.mb{padding:20px}
.mf{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.mx{background:none;border:none;color:var(--muted);font-size:22px;cursor:pointer;line-height:1;padding:2px 6px}
.mx:hover{color:#fff}
.fg{display:flex;flex-direction:column;gap:6px}
.fg label{font-size:11px;font-weight:500;color:var(--muted)}
.fg input,.fg select,.fg textarea{padding:10px 13px;border-radius:10px;border:1px solid var(--border);
  background:rgba(0,14,37,.8);color:var(--text);font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:.15s}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:rgba(162,231,255,.3)}
.fg select option{background:#0a1f3d}
.fg textarea{resize:vertical;min-height:65px;line-height:1.5}
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.full{grid-column:1/-1}
#toast{position:fixed;top:68px;left:50%;transform:translateX(-50%) translateY(-12px);
  background:rgba(5,27,57,.97);border:1px solid rgba(162,231,255,.25);color:var(--text);
  padding:11px 22px;border-radius:11px;font-size:12.5px;font-weight:500;z-index:9999;
  opacity:0;transition:all .3s;pointer-events:none;white-space:nowrap}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>

<div id="toast"></div>

<!-- SIDEBAR -->
<nav class="sb">
  <div class="sb-logo">
    <img src="assets/logo.png" onerror="this.style.display='none'">
    <div><div class="sb-name">Pangandaran.in</div><span class="sb-sub">Admin Panel</span></div>
  </div>
  <div class="sb-nav">
    <div class="sb-section">Utama</div>
    <a href="admin.php?v=dashboard" class="sb-link <?= $view==='dashboard'?'on':'' ?>"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Dashboard</a>
    <div class="sb-section">Transaksi</div>
    <a href="admin.php?v=booking" class="sb-link <?= $view==='booking'?'on':'' ?>"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg> Data Booking</a>
    <div class="sb-section">Master Data</div>
    <a href="admin.php?v=items" class="sb-link <?= $view==='items'?'on':'' ?>"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> Kelola Item Booking</a>
    <div class="sb-section">Laporan</div>
    <a href="admin.php?v=laporan" class="sb-link <?= $view==='laporan'?'on':'' ?>"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Laporan Transaksi</a>
    <div class="sb-section">Website</div>
    <a href="admin_paket.php" class="sb-link"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Kelola Paket Website</a>
    <a href="index.html" target="_blank" class="sb-link"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Lihat Website</a>
    <a href="booking.html" target="_blank" class="sb-link"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg> Form Booking</a>
  </div>
  <div class="sb-foot"><a href="?logout=1" style="display:flex;align-items:center;gap:8px;color:rgba(255,100,100,.45);padding:8px 12px;border-radius:9px;text-decoration:none;font-size:13px;transition:.15s"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Keluar</a></div>
</nav>

<!-- MAIN -->
<div class="main">
<div class="topbar">
  <div class="topbar-t"><?= ['dashboard'=>'Dashboard','booking'=>'Data Booking','invoice'=>'Invoice','items'=>'Kelola Item Booking','laporan'=>'Laporan Transaksi'][$view]??'Admin' ?></div>
  <div style="display:flex;gap:8px;align-items:center">
    <?php if(in_array($view,['booking','invoice','laporan'])): ?>
    <a href="?act=export_excel&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>&from=<?=urlencode($fromFilter)?>&to=<?=urlencode($toFilter)?>&_t=<?=time()?>" class="btn-s">Export Excel</a>
    <?php endif; ?>
    <?php if($view==='booking'): ?>
    <button class="btn-p" onclick="openNewBooking()">+ Buat Booking</button>
    <?php endif; ?>
    <?php if($view==='items'): ?>
    <button class="btn-p" onclick="openItemForm()">+ Tambah Item</button>
    <?php endif; ?>
  </div>
</div>

<div class="wrap">

<?php if($view==='dashboard'): ?>
<!-- ===== DASHBOARD ===== -->
<div class="ph"><div class="pt">Overview</div><div class="ptitle">Dashboard Operasional</div></div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">

  <div style="background:var(--low);border:1px solid var(--border);border-radius:14px;padding:20px 22px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted)">Total Booking</span>
      <div style="width:34px;height:34px;border-radius:9px;background:rgba(162,231,255,.07);border:1px solid rgba(162,231,255,.1);display:flex;align-items:center;justify-content:center">
        <svg width="15" height="15" fill="none" stroke="var(--cyan)" stroke-width="1.6" viewBox="0 0 24 24"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg>
      </div>
    </div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:34px;font-weight:800;color:#fff;line-height:1;letter-spacing:-.5px"><?= intval($stats['tb']) ?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:8px">transaksi tercatat</div>
  </div>

  <div style="background:var(--low);border:1px solid var(--border);border-radius:14px;padding:20px 22px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted)">Total Omzet</span>
      <div style="width:34px;height:34px;border-radius:9px;background:rgba(162,231,255,.07);border:1px solid rgba(162,231,255,.1);display:flex;align-items:center;justify-content:center">
        <svg width="15" height="15" fill="none" stroke="var(--cyan)" stroke-width="1.6" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </div>
    </div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:800;color:#fff;line-height:1;letter-spacing:-.3px">Rp <?= rp($stats['omzet']) ?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:8px">total nilai invoice</div>
  </div>

  <div style="background:var(--low);border:1px solid rgba(34,197,94,.15);border-radius:14px;padding:20px 22px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted)">Transaksi Lunas</span>
      <div style="width:34px;height:34px;border-radius:9px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.18);display:flex;align-items:center;justify-content:center">
        <svg width="15" height="15" fill="none" stroke="#22c55e" stroke-width="1.6" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
    </div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:34px;font-weight:800;color:#22c55e;line-height:1;letter-spacing:-.5px"><?= intval($stats['lunas_c']) ?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:8px">pembayaran lunas</div>
  </div>

  <div style="background:var(--low);border:1px solid rgba(245,158,11,.15);border-radius:14px;padding:20px 22px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted)">Belum Lunas</span>
      <div style="width:34px;height:34px;border-radius:9px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.18);display:flex;align-items:center;justify-content:center">
        <svg width="15" height="15" fill="none" stroke="#f59e0b" stroke-width="1.6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
    </div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:34px;font-weight:800;color:#f59e0b;line-height:1;letter-spacing:-.5px"><?= intval($stats['pending'])+intval($stats['dp_c']) ?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:8px">menunggu pembayaran</div>
  </div>

</div>

<?php if(count($omzet6)>0): $maxO=max(array_column($omzet6,'total'))?:1; ?>
<div style="background:var(--low);border:1px solid var(--border);border-radius:14px;padding:22px 24px;margin-bottom:22px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
      <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:15px;color:#fff">Omzet 6 Bulan Terakhir</div>
      <div style="font-size:11px;color:var(--muted);margin-top:3px">Total pemasukan dari transaksi booking</div>
    </div>
    <a href="admin.php?v=laporan" style="font-size:12px;color:var(--cyan);text-decoration:none;display:flex;align-items:center;gap:4px">Lihat laporan <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
  </div>
  <div style="height:140px;display:flex;align-items:flex-end;gap:10px;padding:0 4px">
    <?php foreach($omzet6 as $o): $pct = $maxO>0 ? round(($o['total']/$maxO)*100) : 0; ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:5px">
      <div style="font-size:9px;color:var(--cyan);font-weight:600;white-space:nowrap;min-height:14px"><?= $pct>8 ? 'Rp '.rp($o['total']) : '' ?></div>
      <div style="width:100%;border-radius:4px 4px 0 0;background:linear-gradient(to top,rgba(0,89,179,.8),rgba(162,231,255,.2));min-height:4px;transition:.3s;height:<?= max(4,$pct) ?>%"></div>
      <div style="font-size:9px;color:var(--muted);text-align:center"><?= $o['bln'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div style="background:var(--low);border:1px solid var(--border);border-radius:14px;overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:15px;color:#fff">Booking Terbaru</div>
      <div style="font-size:11px;color:var(--muted);margin-top:2px">8 booking masuk terakhir</div>
    </div>
    <a href="admin.php?v=booking" style="display:flex;align-items:center;gap:6px;color:var(--cyan);font-size:12px;text-decoration:none;background:rgba(162,231,255,.07);border:1px solid rgba(162,231,255,.15);padding:7px 14px;border-radius:9px;transition:.15s">Lihat semua <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
  </div>
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="background:rgba(10,31,61,.5);border-bottom:1px solid var(--border)">
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">No. Invoice</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">Nama & Kontak</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">Paket</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">Tanggal & Waktu</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:right">Total</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">Status</th>
        <th style="padding:10px 14px;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $rec=$db->query("
  SELECT 
    b.*,
    COALESCE(
      NULLIF(GROUP_CONCAT(bi.produk SEPARATOR ', '), ''),
      NULLIF(b.paket, ''),
      '—'
    ) pl
  FROM booking b 
  LEFT JOIN booking_items bi ON bi.booking_id=b.id 
  GROUP BY b.id 
  ORDER BY b.created_at DESC 
  LIMIT 8
");
    if($rec && $rec->num_rows > 0):
      while($r=$rec->fetch_assoc()):?>
    <tr style="border-bottom:1px solid rgba(162,231,255,.04);transition:background .15s" onmouseover="this.style.background='rgba(162,231,255,.02)'" onmouseout="this.style.background=''">
      <td style="padding:12px 14px"><div style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:11px;color:var(--cyan)"><?= htmlspecialchars($r['no_invoice']??'—') ?></div><div style="font-size:10px;color:var(--muted);margin-top:2px"><?= isset($r['created_at']) ? date('d M Y',strtotime($r['created_at'])) : '' ?></div></td>
      <td style="padding:12px 14px"><div style="font-weight:500;color:#fff;font-size:13px"><?= htmlspecialchars($r['nama']) ?></div><div style="font-size:11px;color:var(--muted);margin-top:1px"><?= kontak_info($r) ?></div></td>
      <td style="padding:12px 14px;font-size:11px;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['pl']??'—') ?></td>
      <td style="padding:12px 14px;font-size:12px;color:var(--text)"><div><?= htmlspecialchars($r['tanggal'] ?? '—') ?></div><div style="font-size:10px;color:var(--muted);margin-top:2px"><?= !empty($r['waktu_kegiatan']) ? htmlspecialchars($r['waktu_kegiatan']) : '—' ?></div></td>
      <td style="padding:12px 14px;text-align:right;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:12px;color:var(--cyan)">Rp <?= rp($r['total_invoice']) ?></td>
      <td style="padding:12px 14px"><?= badge($r['status_bayar']) ?></td>
      <td style="padding:12px 14px"><div style="display:flex;gap:4px;flex-wrap:wrap"><button class="btn-s" style="padding:5px 9px;font-size:11px" onclick="openDetail(<?= $r['id'] ?>)">Detail</button><button class="btn-s" style="padding:5px 9px;font-size:11px" onclick="openInvoice(<?= $r['id'] ?>)">Invoice</button><button class="btn-d" style="padding:5px 9px;font-size:11px" onclick="deleteBooking(<?= $r['id'] ?>)">Hapus</button></div></td>
    </tr>
    <?php endwhile;
    else: ?>
    <tr><td colspan="7" style="text-align:center;padding:48px;color:var(--muted);font-size:14px">Belum ada booking masuk.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php elseif(in_array($view,['booking','invoice'])): ?>
<!-- ===== BOOKING / INVOICE ===== -->
<div class="ph"><div class="pt">Transaksi</div><div class="ptitle"><?= $view==='invoice'?'Daftar Invoice':'Data Booking' ?></div></div>
<div class="tw">
  <div class="th">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="v" value="booking">
      <input class="si" name="q" placeholder="Cari nama / invoice / WA / IG..." value="<?= htmlspecialchars($fq) ?>">
      <select class="sf" name="fs" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <?php foreach(['Pending','DP','Lunas','Batal','Refund'] as $st): ?>
        <option value="<?= $st ?>" <?= $fs===$st?'selected':'' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-s" style="padding:8px 14px">Filter</button>
    </form>
    <span style="font-size:12px;color:var(--muted)"><?= $total_rows ?> data</span>
  </div>
  <table>
    <thead><tr>
      <th>No. Invoice</th>
      <th>Nama & Kontak</th>
      <th>Item</th>
      <th>Tgl & Waktu Trip</th>
      <th style="text-align:right">Total</th>
      <th style="text-align:right">Sisa</th>
      <th>Status</th>
      <th>Tgl Lunas</th>
      <th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(empty($bookings)): ?><tr><td colspan="9" class="es">Belum ada data.</td></td></tr>
    <?php else: foreach($bookings as $b): ?>
    <tr>
      <td style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:11px;color:var(--cyan)"><?= htmlspecialchars($b['no_invoice']) ?></td>
      <td><div style="font-weight:500;color:#fff"><?= htmlspecialchars($b['nama']) ?></div><div style="font-size:11px;color:var(--muted)"><?= kontak_info($b) ?></div></td>
      <td style="font-size:11px;color:var(--muted);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($b['ilist']??'—') ?></td>
      <td style="font-size:11px;color:var(--muted)"><div><?= htmlspecialchars($b['tanggal'] ?? '—') ?></div><div style="font-size:10px;color:var(--muted);margin-top:2px"><?= !empty($b['waktu_kegiatan']) ? htmlspecialchars($b['waktu_kegiatan']) : '—' ?></div></td>
      <td style="text-align:right;font-family:'Space Grotesk',sans-serif;font-weight:700;color:var(--cyan)">Rp <?= rp($b['total_invoice']) ?></td>
      <td style="text-align:right;font-weight:700;color:<?= $b['sisa_bayar']>0?'#f59e0b':'#22c55e' ?>">Rp <?= rp($b['sisa_bayar']) ?></td>
      <td><?= badge($b['status_bayar']) ?></td>

      <td style="font-size:11px;color:var(--muted)">
      <?= !empty($b['tanggal_pelunasan']) ? date('Y-m-d', strtotime($b['tanggal_pelunasan'])) : '—' ?>
      </td>

      <td><div style="display:flex;gap:4px;flex-wrap:wrap">
        <button class="btn-s" style="padding:5px 9px;font-size:11px" onclick="openDetail(<?= $b['id'] ?>)">Detail</button>
        <button class="btn-s" style="padding:5px 9px;font-size:11px" onclick="openInvoice(<?= $b['id'] ?>)">Invoice</button>
        <button class="btn-d" style="padding:5px 9px;font-size:11px" onclick="deleteBooking(<?= $b['id'] ?>)">Hapus</button>
      </div></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php if($total_pages>1): ?>
  <div class="pg">
    <span><?= $total_rows ?> data — hal. <?= $page ?>/<?= $total_pages ?></span>
    <div style="display:flex;gap:4px">
      <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
      <a href="?v=<?=$view?>&page=<?=$i?>&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>" class="pb <?=$i===$page?'on':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php elseif($view==='items'): ?>
<!-- ===== KELOLA ITEM ===== -->
<div class="ph"><div class="pt">Master Data</div><div class="ptitle">Kelola Item Booking</div></div>
<div class="tw">
  <table>
    <thead><tr><th>Nama Item</th><th>Harga</th><th>Satuan</th><th>Kategori</th><th style="text-align:center">Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($paket_items as $pi): ?>
    <tr style="<?= !$pi['aktif']?'opacity:.35':'' ?>">
      <td style="font-weight:500;color:#fff"><?= htmlspecialchars($pi['nama']) ?></td>
      <td style="color:var(--cyan);font-family:'Space Grotesk',sans-serif;font-weight:700"><?= $pi['harga']>0?'Rp '.rp($pi['harga']):'Nego' ?></td>
      <td style="font-size:11px;color:var(--muted)">/<?= $pi['satuan'] ?></td>
      <td><span style="background:rgba(162,231,255,.07);color:var(--muted);font-size:9px;padding:2px 8px;border-radius:8px;text-transform:uppercase"><?= $pi['kategori'] ?></span></td>
      <td style="text-align:center">
        <button onclick="toggleItem(<?= $pi['id'] ?>,this)" style="padding:3px 12px;border-radius:20px;border:1px solid;cursor:pointer;font-size:10px;font-weight:700;background:none;transition:.15s;<?= $pi['aktif']?'color:#22c55e;border-color:rgba(34,197,94,.3)':'color:#94a3b8;border-color:rgba(148,163,184,.3)' ?>"><?= $pi['aktif']?'Aktif':'Nonaktif' ?></button>
      </td>
      <td><div style="display:flex;gap:5px">
        <button class="btn-s" style="padding:5px 9px;font-size:11px" onclick='openItemForm(<?= htmlspecialchars(json_encode($pi)) ?>)'>Edit</button>
        <button class="btn-d" onclick="deleteItem(<?= $pi['id'] ?>)">Hapus</button>
      </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($view==='laporan'): ?>
<!-- ===== LAPORAN ===== -->
<div class="ph"><div class="pt">Analitik</div><div class="ptitle">Laporan Transaksi</div></div>
<div class="tw" style="padding:16px 18px;margin-bottom:18px">
  <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="v" value="laporan">
    <div class="fg"><label>Dari Tanggal</label><input type="date" name="from" value="<?= $_GET['from']??'' ?>" style="min-width:140px"></div>
    <div class="fg"><label>Sampai Tanggal</label><input type="date" name="to" value="<?= $_GET['to']??'' ?>" style="min-width:140px"></div>
    <div class="fg"><label>Status</label><select class="sf" name="fs"><option value="">Semua</option><?php foreach(['Lunas','DP','Pending','Batal','Refund'] as $st): ?><option value="<?=$st?>" <?=$fs===$st?'selected':''?>><?=$st?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn-p">Tampilkan</button>
    <a href="?act=export_excel&fs=<?=urlencode($fs)?>&q=<?=urlencode($fq)?>&from=<?=urlencode($_GET['from']??'')?>&to=<?=urlencode($_GET['to']??'')?>&_t=<?=time()?>" class="btn-s" style="padding:8px 14px">Export Excel</a>
  </form>
</div>
<?php
$lw="WHERE 1=1";if($fs) $lw.=" AND status_bayar='".addslashes($fs)."'";
if(isset($_GET['from'])&&$_GET['from']) $lw.=" AND tanggal>='".addslashes($_GET['from'])."'";
if(isset($_GET['to'])&&$_GET['to']) $lw.=" AND tanggal<='".addslashes($_GET['to'])."'";
$lt_r=$db->query("SELECT SUM(total_invoice) t,SUM(dp) d,SUM(sisa_bayar) s,COUNT(*) c FROM booking $lw");
$lt=$lt_r?$lt_r->fetch_assoc():['t'=>0,'d'=>0,'s'=>0,'c'=>0];
if(!$lt)$lt=['t'=>0,'d'=>0,'s'=>0,'c'=>0];
$ls_r=$db->query("SELECT status_bayar,COUNT(*) cnt,SUM(total_invoice) total,SUM(dp) tdp FROM booking $lw GROUP BY status_bayar");
$ls=$ls_r?$ls_r->fetch_all(MYSQLI_ASSOC):[];
// Gunakan prefix tabel b. agar tidak ambiguous pada query JOIN
$lw_join="WHERE 1=1";
if($fs) $lw_join.=" AND b.status_bayar='".addslashes($fs)."'";
if(isset($_GET['from'])&&$_GET['from']) $lw_join.=" AND b.tanggal>='".addslashes($_GET['from'])."'";
if(isset($_GET['to'])&&$_GET['to']) $lw_join.=" AND b.tanggal<='".addslashes($_GET['to'])."'";
$ti_r=$db->query("SELECT bi.produk,COUNT(*) cnt,SUM(bi.subtotal) total FROM booking_items bi LEFT JOIN booking b ON b.id=bi.booking_id $lw_join GROUP BY bi.produk ORDER BY total DESC LIMIT 10");
$ti=$ti_r?$ti_r->fetch_all(MYSQLI_ASSOC):[];
?>
<div class="sg" style="margin-bottom:18px">
  <div class="sc"><div class="sv"><?= $lt['c'] ?></div><div class="sl">Total Transaksi</div></div>
  <div class="sc"><div class="sv" style="font-size:14px">Rp <?= rp($lt['t']) ?></div><div class="sl">Total Invoice</div></div>
  <div class="sc"><div class="sv" style="font-size:14px;color:#22c55e">Rp <?= rp($lt['d']) ?></div><div class="sl">Terbayar</div></div>
  <div class="sc"><div class="sv" style="font-size:14px;color:#f59e0b">Rp <?= rp($lt['s']) ?></div><div class="sl">Sisa Tagihan</div></div>
</div>
<div class="tw" style="margin-bottom:18px">
  <div class="th"><span class="th-title">Per Status</span></div>
  <table><thead><tr><th>Status</th><th style="text-align:right">Jumlah</th><th style="text-align:right">Total Invoice</th><th style="text-align:right">Terbayar</th></tr></thead>
  <tbody><?php foreach($ls as $l): ?><tr>
    <td><?= badge($l['status_bayar']) ?></td>
    <td style="text-align:right;font-weight:600"><?= $l['cnt'] ?>x</td>
    <td style="text-align:right;color:var(--cyan);font-weight:600">Rp <?= rp($l['total']) ?></td>
    <td style="text-align:right;color:#22c55e">Rp <?= rp($l['tdp']) ?></td>
  </tr><?php endforeach; ?></tbody></table>
</div>
<div class="tw">
  <div class="th"><span class="th-title">Top Layanan yang Dipesan</span></div>
  <table><thead><tr><th>Layanan</th><th style="text-align:right">Dipesan</th><th style="text-align:right">Total Pendapatan</th></tr></thead>
  <tbody><?php foreach($ti as $t): ?><tr>
    <td style="font-weight:500;color:#fff"><?= htmlspecialchars($t['produk']) ?></td>
    <td style="text-align:right;color:var(--muted)"><?= $t['cnt'] ?>x</td>
    <td style="text-align:right;color:var(--cyan);font-weight:600">Rp <?= rp($t['total']) ?></td>
  </tr><?php endforeach; ?></tbody></table>
</div>
<?php endif; ?>
</div><!-- /wrap -->
</div><!-- /main -->

<!-- ===== MODALS ===== -->

<!-- MODAL BOOKING FORM -->
<div class="mo" id="moBk"><div class="md" style="max-width:760px">
  <div class="mh"><div class="mt" id="moBkTitle">Buat Booking Baru</div><button class="mx" onclick="closeM('moBk')">×</button></div>
  <div class="mb">
    <input type="hidden" id="bkId">
    <div class="fgrid" style="margin-bottom:14px">
      <div class="fg"><label>Nama Lengkap *</label><input id="bkNama" placeholder="Nama client" autocomplete="off"></div>
      <div class="fg"><label>Email <span style="font-weight:400;color:var(--muted)">(opsional)</span></label>
        <input id="bkEmail" type="text" placeholder="contoh@gmail.com" oninput="admValidateEmail(this)" autocomplete="off">
        <div id="adm_email_hint" style="font-size:11px;margin-top:5px;display:none"></div>
      </div>
      <div class="fg"><label>WhatsApp</label>
        <input id="bkWA" type="tel" placeholder="+62 8xx-xxxx-xxxx" oninput="admFormatWA(this)" autocomplete="off">
        <div id="adm_wa_hint" style="font-size:11px;margin-top:5px;display:none"></div>
      </div>
      <div class="fg"><label>Media Sosial / Instagram</label><input id="bkMedsos" placeholder="@username" autocomplete="off"></div>
      <div class="fg"><label>Tanggal Kegiatan</label><input id="bkTgl" type="date"></div>
      <div class="fg"><label>Waktu Kegiatan</label><input id="bkWaktu" placeholder="09.00 — 11.00 WIB"></div>
      <div class="fg"><label>Diskon (Rp)</label><input id="bkDisc" type="number" min="0" value="0" oninput="recalcBk()"></div>
      <div class="fg"><label>DP Terbayar (Rp)</label><input id="bkDP" type="number" min="0" value="0" oninput="recalcBk()"></div>
      <div class="fg"><label>Status Pembayaran</label><select id="bkSts"><option value="Pending">Pending</option><option value="DP">DP</option><option value="Lunas">Lunas</option><option value="Batal">Batal</option><option value="Refund">Refund</option></select></div>
      <div class="fg full"><label>Catatan</label><textarea id="bkCat" placeholder="Catatan khusus..."></textarea></div>
    </div>
    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Item / Paket Dipesan</div>
    <div id="bkItems"></div>
    <button onclick="addBkRow()" class="btn-s" style="width:100%;padding:9px;margin-top:6px;font-size:12px">+ Tambah Item</button>
    <div style="background:rgba(0,14,37,.6);border:1px solid var(--border);border-radius:11px;padding:13px 15px;margin-top:14px">
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Subtotal</span><span id="bkSub">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Diskon</span><span id="bkDiscD">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#fff;border-top:1px solid var(--border);padding-top:8px;margin-bottom:4px"><span>Total Invoice</span><span id="bkTI" style="color:var(--cyan)">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:3px"><span>DP Terbayar</span><span id="bkDPD">Rp 0</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#f59e0b"><span>Sisa Tagihan</span><span id="bkSisa">Rp 0</span></div>
    </div>
  </div>
  <div class="mf"><button class="btn-s" onclick="closeM('moBk')">Batal</button><button class="btn-p" onclick="saveBk()">Simpan</button></div>
</div></div>

<!-- MODAL DETAIL -->
<div class="mo" id="moDet"><div class="md" style="max-width:660px">
  <div class="mh"><div class="mt" id="moDetTitle">Detail Booking</div><button class="mx" onclick="closeM('moDet')">×</button></div>
  <div class="mb" id="moDetBody"></div>
  <div class="mf" id="moDetFoot"></div>
</div></div>

<!-- MODAL INVOICE (white bg) -->
<div class="mo" id="moInv"><div class="md" style="max-width:700px;background:#fff;border:none">
  <button class="mx" style="position:absolute;top:10px;right:14px;z-index:1;color:#aaa" onclick="closeM('moInv')">×</button>
  <div id="moInvBody"></div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr">
    <button id="invPDF" style="padding:14px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:13px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif;border-radius:0 0 0 18px">Download PDF</button>
    <button id="invWA"  style="padding:14px;background:#25D366;color:#fff;font-weight:700;font-size:13px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif">Kirim WA</button>
    <button onclick="closeM('moInv')" style="padding:14px;background:#f5f5f5;color:#555;font-weight:700;font-size:13px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif;border-radius:0 0 18px 0">Tutup</button>
  </div>
</div></div>

<!-- MODAL ITEM FORM -->
<div class="mo" id="moItem"><div class="md" style="max-width:430px">
  <div class="mh"><div class="mt" id="moItemTitle">Tambah Item</div><button class="mx" onclick="closeM('moItem')">×</button></div>
  <div class="mb">
    <input type="hidden" id="itId">
    <div class="fg" style="margin-bottom:12px"><label>Nama Item *</label><input id="itNama" placeholder="Nama layanan/paket"></div>
    <div class="fgrid">
      <div class="fg"><label>Harga (Rp)</label><input id="itH" type="number" min="0" placeholder="0" oninput="updItH()"><div id="itHD" style="font-size:14px;font-weight:700;color:var(--cyan);margin-top:4px">Nego</div></div>
      <div class="fg"><label>Satuan</label><select id="itSat"><option value="orang">/ orang</option><option value="unit">/ unit</option><option value="sesi">/ sesi</option><option value="paket">/ paket</option><option value="ride">/ ride</option><option value="nego">/ nego</option></select></div>
      <div class="fg full"><label>Kategori</label><select id="itKat"><option value="river">River Adventure</option><option value="sea">Sea Adventure</option><option value="multi">Multi Day Trip</option><option value="outbound">Outbound</option></select></div>
    </div>
  </div>
  <div class="mf"><button class="btn-s" onclick="closeM('moItem')">Batal</button><button class="btn-p" onclick="saveItem()">Simpan</button></div>
</div></div>

<!-- MODAL STATUS UPDATE -->
<div class="mo" id="moSts"><div class="md" style="max-width:380px">
  <div class="mh">
    <div class="mt">Update Status Pembayaran</div>
    <button class="mx" onclick="closeM('moSts')">×</button>
  </div>

  <div class="mb">
    <input type="hidden" id="stsId">

    <div class="fg" style="margin-bottom:12px">
      <label>Status Baru</label>
      <select id="stsVal">
        <option value="Pending">Pending</option>
        <option value="DP">DP — Sebagian Terbayar</option>
        <option value="Lunas">Lunas</option>
        <option value="Batal">Batal</option>
        <option value="Refund">Refund</option>
      </select>
    </div>

    <div class="fg" style="margin-bottom:12px">
      <label>Total DP / Terbayar Saat Ini (Rp)</label>
      <input id="stsDP" type="text" inputmode="numeric" placeholder="0" oninput="formatAdminRupiah(this)">
    </div>

    <div class="fg" style="margin-bottom:12px">
      <label>Nominal Pelunasan Cash (Rp)</label>
      <input id="stsPelunasanCash" type="text" inputmode="numeric" placeholder="Contoh: 140000" oninput="formatAdminRupiah(this)">
    </div>

    <div class="fg">
      <label>Tanggal Pelunasan</label>
      <input id="stsTanggalPelunasan" type="date">
    </div>
  </div>

  <div class="mf">
    <button class="btn-s" onclick="closeM('moSts')">Batal</button>
    <button class="btn-p" onclick="saveSts()">Update</button>
  </div>
</div></div>

<script>
const LOGO = '<?= $LOGO ?>';

function toast(msg,type,dur){
  const t=document.getElementById('toast');t.textContent=msg;
  t.style.borderColor=type==='ok'?'rgba(34,197,94,.3)':type==='err'?'rgba(255,80,80,.3)':'rgba(162,231,255,.25)';
  t.classList.add('show');setTimeout(()=>t.classList.remove('show'),dur||2800);
}
function openM(id){document.getElementById(id).classList.add('show');}
function closeM(id){document.getElementById(id).classList.remove('show');}
document.querySelectorAll('.mo').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('show');}));
function fRp(n){return (parseInt(n)||0).toLocaleString('id-ID');}
function angkaAdminOnly(value){
  return (value || '').toString().replace(/[^0-9]/g, '');
}

function formatAdminRupiah(el){
  const angka = angkaAdminOnly(el.value);
  el.value = angka ? fRp(angka) : '';
}

function getAdminAngka(id){
  const el = document.getElementById(id);
  return parseInt(angkaAdminOnly(el ? el.value : '')) || 0;
}
function fNow(){return new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});}

let piList=[];
const FALLBACK_ITEMS=[
  {id:0,nama:'Green Canyon Body Rafting',harga:250000,satuan:'orang',kategori:'river',aktif:1},
  {id:0,nama:'Ciwayang Body Rafting',harga:150000,satuan:'orang',kategori:'river',aktif:1},
  {id:0,nama:'Citumang Body Rafting',harga:150000,satuan:'orang',kategori:'river',aktif:1},
  {id:0,nama:'Santirah River Tubing',harga:150000,satuan:'orang',kategori:'river',aktif:1},
  {id:0,nama:'Jetski',harga:350000,satuan:'ride',kategori:'sea',aktif:1},
  {id:0,nama:'Water Sport',harga:150000,satuan:'paket',kategori:'sea',aktif:1},
  {id:0,nama:'Stand Up Paddle',harga:350000,satuan:'orang',kategori:'sea',aktif:1},
  {id:0,nama:'Snorkeling',harga:150000,satuan:'orang',kategori:'sea',aktif:1},
  {id:0,nama:'2 Day 1 Night Package',harga:700000,satuan:'orang',kategori:'multi',aktif:1},
  {id:0,nama:'3 Day 2 Night Package',harga:700000,satuan:'orang',kategori:'multi',aktif:1},
  {id:0,nama:'Fun Game',harga:150000,satuan:'orang',kategori:'outbound',aktif:1},
  {id:0,nama:'Team Building',harga:200000,satuan:'orang',kategori:'outbound',aktif:1},
  {id:0,nama:'Photo Session',harga:200000,satuan:'sesi',kategori:'outbound',aktif:1},
];
async function loadPI(){
  try{const r=await fetch('admin.php?act=get_items_json');const data=await r.json();piList=(Array.isArray(data)&&data.length>0)?data:FALLBACK_ITEMS;}
  catch(e){piList=FALLBACK_ITEMS;}
}
loadPI();
function mkSelect(val=''){
  const g={};piList.forEach(it=>{(g[it.kategori]=g[it.kategori]||[]).push(it);});
  const kl={river:'River Adventure',sea:'Sea Adventure',multi:'Multi Day Trip',outbound:'Outbound',other:'Other'};
  let h='<option value="" data-h="0">Pilih item...</option>';
  Object.keys(g).forEach(k=>{
    h+=`<optgroup label="${kl[k]||k}">`;
    g[k].forEach(it=>{const lbl=it.harga>0?'Rp '+fRp(it.harga)+'/'+it.satuan:'Nego';const s=it.nama===val?'selected':'';h+=`<option value="${it.nama}" data-h="${it.harga}" ${s}>${it.nama} — ${lbl}</option>`;});
    h+='</optgroup>';
  });
  return h;
}

let bkCnt=0;
function openNewBooking(){
  document.getElementById('bkId').value='';
  document.getElementById('moBkTitle').textContent='Buat Booking Baru';
  ['bkNama','bkEmail','bkWA','bkMedsos','bkCat','bkWaktu'].forEach(i=>{const el=document.getElementById(i);if(el){el.value='';el.style.borderColor='';}});
  document.getElementById('bkTgl').value='';
  document.getElementById('bkDisc').value=0;
  document.getElementById('bkDP').value=0;
  document.getElementById('bkSts').value='Pending';
  document.getElementById('bkItems').innerHTML='';bkCnt=0;
  addBkRow();recalcBk();openM('moBk');
}
async function openEditBk(id){
  const r=await(await fetch('admin.php?act=get_booking&id='+id)).json();
  if(!r.ok) return;const d=r.data;
  document.getElementById('bkId').value=d.id;
  document.getElementById('moBkTitle').textContent='Edit Booking — '+d.no_invoice;
  document.getElementById('bkNama').value=d.nama;document.getElementById('bkEmail').value=d.email;
  document.getElementById('bkWA').value=d.whatsapp;document.getElementById('bkMedsos').value=d.medsos||'';document.getElementById('bkTgl').value=d.tanggal;
  document.getElementById('bkWaktu').value=d.waktu_kegiatan||'';document.getElementById('bkCat').value=d.catatan||'';
  document.getElementById('bkDisc').value=d.discount||0;document.getElementById('bkDP').value=d.dp||0;
  document.getElementById('bkSts').value=d.status_bayar;
  document.getElementById('bkItems').innerHTML='';bkCnt=0;
  (d.items||[]).forEach(it=>addBkRow(it));if(!d.items||!d.items.length) addBkRow();
  recalcBk();openM('moBk');
}
function addBkRow(data=null){
  bkCnt++;const id='br'+bkCnt;const div=document.createElement('div');
  div.id=id;div.style.cssText='display:grid;grid-template-columns:1fr 80px 50px auto;gap:7px;margin-bottom:7px;align-items:end';
  div.innerHTML=`
    <div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Produk</label>
      <select onchange="onBkSel(this,'${id}')" style="width:100%;padding:9px 11px;border-radius:9px;border:1px solid var(--border);background:rgba(0,14,37,.8);color:var(--text);font-size:12px;outline:none">${mkSelect(data?.produk||'')}</select></div>
    <div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Harga</label>
      <input type="number" id="${id}h" value="${data?.harga||0}" min="0" oninput="recalcBk()" style="width:100%;padding:9px 10px;border-radius:9px;border:1px solid var(--border);background:rgba(0,14,37,.8);color:var(--cyan);font-size:11px;font-weight:700;outline:none"></div>
    <div><label style="font-size:10px;color:var(--muted);display:block;margin-bottom:4px">Qty</label>
      <input type="number" id="${id}q" value="${data?.qty||1}" min="1" oninput="recalcBk()" style="width:100%;padding:9px 8px;border-radius:9px;border:1px solid var(--border);background:rgba(0,14,37,.8);color:var(--text);font-size:12px;text-align:center;outline:none"></div>
    <div style="padding-bottom:1px"><button onclick="document.getElementById('${id}').remove();recalcBk()" style="width:28px;height:34px;border-radius:7px;background:rgba(255,80,80,.07);border:1px solid rgba(255,80,80,.18);color:#ff8080;cursor:pointer;font-size:14px;font-weight:700">×</button></div>`;
  document.getElementById('bkItems').appendChild(div);
  if(data){document.querySelector('#'+id+' select').value=data.produk;document.getElementById(id+'h').value=data.harga;document.getElementById(id+'q').value=data.qty;}
  recalcBk();
}
function onBkSel(sel,id){const h=parseInt(sel.options[sel.selectedIndex].getAttribute('data-h')||0);document.getElementById(id+'h').value=h;recalcBk();}
function recalcBk(){
  let tot=0;document.querySelectorAll('#bkItems>div').forEach(row=>{const h=parseInt(row.querySelector('input[id$="h"]')?.value||0);const q=parseInt(row.querySelector('input[id$="q"]')?.value||1);tot+=h*q;});
  const disc=parseInt(document.getElementById('bkDisc')?.value||0);const dp=parseInt(document.getElementById('bkDP')?.value||0);
  const tinv=Math.max(0,tot-disc);const sisa=Math.max(0,tinv-dp);
  document.getElementById('bkSub').textContent='Rp '+fRp(tot);
  document.getElementById('bkDiscD').textContent='Rp '+fRp(disc);
  document.getElementById('bkTI').textContent='Rp '+fRp(tinv);
  document.getElementById('bkDPD').textContent='Rp '+fRp(dp);
  document.getElementById('bkSisa').textContent='Rp '+fRp(sisa);
}

function admValidateEmail(input){
  const val=input.value.trim();const hint=document.getElementById("adm_email_hint");
  if(!hint) return;
  if(!val){hint.style.display="none";input.style.borderColor="";return;}
  const valid=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
  hint.style.display="block";
  if(!valid){
    if(!val.includes("@")) hint.innerHTML='<span style="color:#f59e0b">&#9888; Belum ada @ — contoh: nama@gmail.com</span>';
    else if(val.split("@")[1]&&!val.split("@")[1].includes(".")) hint.innerHTML='<span style="color:#f59e0b">&#9888; Domain belum lengkap — contoh: nama@gmail.com</span>';
    else hint.innerHTML='<span style="color:#f59e0b">&#9888; Format email tidak valid</span>';
    input.style.borderColor="rgba(245,158,11,.35)";
  } else {
    hint.innerHTML='<span style="color:#22c55e">&#10003; <strong>'+val+'</strong></span>';
    input.style.borderColor="rgba(34,197,94,.35)";
  }
}
function admFormatWA(input){
  let val=input.value.replace(/\s/g,"");const hint=document.getElementById("adm_wa_hint");
  if(!hint) return;
  if(val.startsWith("08")){input.value="+628"+val.slice(2);hint.textContent="Diformat: "+input.value;hint.style.cssText="font-size:11px;margin-top:5px;display:block;color:rgba(34,197,94,.7)";}
  else if(val.startsWith("8")&&val.length>=9){input.value="+62"+val;hint.textContent="Diformat: "+input.value;hint.style.cssText="font-size:11px;margin-top:5px;display:block;color:rgba(34,197,94,.7)";}
  else{hint.style.display="none";}
}

function safeAdminEmail(v){
  v = (v || '').trim();
  return (v && v.toLowerCase() !== 'noemail@pangandaran.in') ? v : '';
}
function adminContactHTML(d){
  const lines = [];
  if(d.medsos) lines.push(d.medsos);
  else if(safeAdminEmail(d.email)) lines.push(safeAdminEmail(d.email));
  if(d.whatsapp) lines.push(d.whatsapp);
  return lines.length ? lines.join('<br>') : '—';
}

async function saveBk(){
  const nama=document.getElementById('bkNama').value.trim();const email=document.getElementById('bkEmail').value.trim();
  if(!nama){toast('Nama wajib diisi.','err');document.getElementById('bkNama').focus();return;}
  if(email&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){toast('Format email tidak valid.','err');document.getElementById('bkEmail').focus();return;}
  const items=[];
  document.querySelectorAll('#bkItems>div').forEach(row=>{
    const produk=row.querySelector('select')?.value||'';if(!produk) return;
    const h=parseInt(row.querySelector('input[id$="h"]')?.value||0);const q=parseInt(row.querySelector('input[id$="q"]')?.value||1);
    items.push({produk,harga:h,qty:q,subtotal:h*q});
  });
  if(!items.length){toast('Minimal 1 item.','err');return;}
  const body=new URLSearchParams({act:'save_booking',bid:document.getElementById('bkId').value||0,
    nama,email,whatsapp:document.getElementById('bkWA').value,medsos:document.getElementById('bkMedsos').value,tanggal:document.getElementById('bkTgl').value,
    waktu:document.getElementById('bkWaktu').value,catatan:document.getElementById('bkCat').value,
    discount:document.getElementById('bkDisc').value||0,dp:document.getElementById('bkDP').value||0,
    status:document.getElementById('bkSts').value,items:JSON.stringify(items)});
  const r=await(await fetch('admin.php',{method:'POST',body})).json();
  if(r.ok){toast('Booking tersimpan!','ok');closeM('moBk');setTimeout(()=>location.reload(),1200);}
  else toast('Gagal.','err');
}

async function openDetail(id){
  const r=await(await fetch('admin.php?act=get_booking&id='+id)).json();if(!r.ok) return;
  const d=r.data;const items=d.items||[];
  const rows=items.map(it=>`<tr><td style="padding:7px 0;border-bottom:1px solid rgba(162,231,255,.05);font-size:12px">${it.produk}</td><td style="padding:7px 0;border-bottom:1px solid rgba(162,231,255,.05);text-align:right;font-size:12px">${fRp(it.harga)}</td><td style="padding:7px 0;border-bottom:1px solid rgba(162,231,255,.05);text-align:center;font-size:12px">${it.qty}</td><td style="padding:7px 0;border-bottom:1px solid rgba(162,231,255,.05);text-align:right;color:var(--cyan);font-weight:600;font-size:12px">${fRp(it.subtotal)}</td></tr>`).join('');
  document.getElementById('moDetTitle').textContent='Detail — '+d.no_invoice;
  document.getElementById('moDetBody').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
      <div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Client</div>
        <div style="font-size:12px;line-height:2"><strong style="color:#fff">${d.nama}</strong><br>${adminContactHTML(d)}</div></div>
      <div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:8px">Booking</div>
        <div style="font-size:12px;line-height:2">Tanggal: <strong style="color:#fff">${d.tanggal}</strong><br>
        Waktu: ${d.waktu_kegiatan||'—'}<br>
        Tanggal Pelunasan: <strong style="color:#22c55e">${d.tanggal_pelunasan || '—'}</strong><br>
        Sumber: ${d.sumber}</div></div>
    </div>
    <table style="width:100%;margin-bottom:12px"><thead><tr>
      <th style="font-size:10px;color:var(--muted);text-align:left;padding-bottom:6px">Produk</th>
      <th style="font-size:10px;color:var(--muted);text-align:right;padding-bottom:6px">Harga</th>
      <th style="font-size:10px;color:var(--muted);text-align:center;padding-bottom:6px">QTY</th>
      <th style="font-size:10px;color:var(--muted);text-align:right;padding-bottom:6px">Subtotal</th>
    </tr></thead><tbody>${rows}</tbody></table>
    <div style="background:rgba(0,14,37,.6);border:1px solid var(--border);border-radius:10px;padding:12px 14px">
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Total</span><span>Rp ${fRp(d.total_harga)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:4px"><span>Diskon</span><span>Rp ${fRp(d.discount)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#fff;border-top:1px solid var(--border);padding-top:8px;margin-bottom:4px"><span>Total Invoice</span><span style="color:var(--cyan)">Rp ${fRp(d.total_invoice)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:3px"><span>DP Terbayar</span><span style="color:#22c55e">Rp ${fRp(d.dp)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:#f59e0b"><span>Sisa Tagihan</span><span>Rp ${fRp(d.sisa_bayar)}</span></div>
    </div>
    ${d.catatan?`<div style="margin-top:10px;background:rgba(0,14,37,.5);border:1px solid var(--border);border-radius:9px;padding:10px 13px;font-size:12px;color:var(--muted)"><strong style="color:var(--text)">Catatan:</strong> ${d.catatan}</div>`:''}`;
  document.getElementById('moDetFoot').innerHTML=`
    <button class="btn-s" onclick="closeM('moDet')">Tutup</button>
    <button class="btn-s" onclick="closeM('moDet');openSts(${d.id}, ${parseInt(d.dp || 0)}, '${d.status_bayar || 'Pending'}')">Update Status</button>
    <button class="btn-s" onclick="closeM('moDet');openEditBk(${d.id})">Edit</button>
    <button class="btn-d" onclick="closeM('moDet');deleteBooking(${d.id})">Hapus</button>
    <button class="btn-p" onclick="closeM('moDet');openInvoice(${d.id})">Invoice</button>`;
  openM('moDet');
}

function openSts(id, dp, status){
  document.getElementById('stsId').value = id;
  document.getElementById('stsDP').value = dp ? fRp(dp) : '0';
  document.getElementById('stsVal').value = status || 'Pending';

  document.getElementById('stsPelunasanCash').value = '';

  const today = new Date().toISOString().slice(0, 10);
  document.getElementById('stsTanggalPelunasan').value = today;

  openM('moSts');
}

async function saveSts(){
  const id = document.getElementById('stsId').value;

  const body = new URLSearchParams({
    act: 'update_status',
    id: id,
    status: document.getElementById('stsVal').value,
    dp: getAdminAngka('stsDP'),
    pelunasan_cash: getAdminAngka('stsPelunasanCash'),
    tanggal_pelunasan: document.getElementById('stsTanggalPelunasan').value || ''
  });

  const r = await (await fetch('admin.php', {
    method: 'POST',
    body: body
  })).json();

  if(r.ok){
    toast('Status pembayaran diperbarui!', 'ok');
    closeM('moSts');
    setTimeout(()=>location.reload(), 1200);
  } else {
    toast(r.msg || 'Gagal update status.', 'err');
  }
}

function buildInvoiceHTML(d, items) {
  const tot=parseInt(d.total_harga??0);const disc=parseInt(d.discount??0);
  const tinv=parseInt(d.total_invoice??0)||Math.max(0,tot-disc);
  const dp=parseInt(d.ammount_paid??d.dp??0);
  const sisa=parseInt(d.outstanding??d.sisa_bayar??0)||Math.max(0,tinv-dp);
  const tgl=new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
  const sc=(d.status_bayar==='Lunas'||d.status_bayar==='Paid Off')?'#16a34a':'#d97706';
  const fRpDot=n=>(parseInt(n)||0).toLocaleString('id-ID');
  const rows=items.map(it=>`<tr><td style="padding:14px 0;font-size:13px;color:#333;border-bottom:1px solid #e0e0e0;font-family:Georgia,'Times New Roman',serif;line-height:1.45;vertical-align:top">${it.produk}</td><td style="padding:14px 16px;font-size:13px;text-align:right;color:#333;border-bottom:1px solid #e0e0e0;font-family:Georgia,serif;white-space:nowrap;vertical-align:top">${fRpDot(it.harga)}</td><td style="padding:14px 16px;font-size:13px;text-align:center;color:#333;border-bottom:1px solid #e0e0e0;font-family:Georgia,serif;vertical-align:top">${it.qty}</td><td style="padding:14px 0 14px 16px;font-size:13px;text-align:right;color:#333;border-bottom:1px solid #e0e0e0;font-family:Georgia,serif;white-space:nowrap;vertical-align:top">${fRpDot(it.subtotal)}</td></tr>`).join('');
  return `<div id="inv-print-area" style="padding:52px 56px 60px;font-family:Georgia,'Times New Roman',serif;background:#fff;color:#333;font-size:13px;min-width:600px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div style="display:flex;align-items:center;gap:20px">
        <img src="${LOGO||''}" width="88" height="88" style="border-radius:50%;object-fit:cover;flex-shrink:0;display:block" onerror="this.style.display='none'">
        <div><div style="font-family:Arial,Helvetica,sans-serif;font-size:27px;font-weight:900;color:#000;letter-spacing:-.3px;line-height:1">Pangandaran.in</div><div style="font-family:Arial,Helvetica,sans-serif;font-size:12.5px;color:#888;margin-top:5px;font-weight:400">CV. Pangandaran in Group</div></div>
      </div>
      <div style="text-align:right">
        <table style="border-collapse:collapse;margin-left:auto">
          <tr><td style="font-size:12.5px;color:#555;padding:3.5px 0;text-align:right;white-space:nowrap">https://pangandaran.in</td><td style="padding:3.5px 0 3.5px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #999;border-radius:50%;color:#999;font-size:9px"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span></td></tr>
          <tr><td style="font-size:12.5px;color:#555;padding:3.5px 0;text-align:right;white-space:nowrap">pangandaraningroup@gmail.com</td><td style="padding:3.5px 0 3.5px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #999;border-radius:3px;color:#999"><svg width="11" height="9" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 18"><rect x="2" y="2" width="20" height="14" rx="2"/><polyline points="2,2 12,11 22,2"/></svg></span></td></tr>
          <tr><td style="font-size:12.5px;color:#555;padding:3.5px 0;text-align:right;white-space:nowrap">@pangandaran.in</td><td style="padding:3.5px 0 3.5px 10px;vertical-align:middle"><span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:1.5px solid #999;border-radius:5px;color:#999"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></span></td></tr>
        </table>
        <div style="font-family:Georgia,'Times New Roman',serif;font-size:34px;font-weight:700;color:#000;margin-top:12px;letter-spacing:0">Receipt</div>
      </div>
    </div>
    <div style="border-top:1px solid #ccc;margin:18px 0 30px"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;margin-bottom:34px">
      <div>
        <div style="font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:700;color:#000;margin-bottom:16px">Bill to</div>
        <table style="border-collapse:collapse">
          <tr><td style="color:#666;padding:5px 0;width:80px;font-family:Georgia,serif;font-size:13px;vertical-align:top">Name</td><td style="color:#666;padding:5px 8px;font-family:Georgia,serif;font-size:13px;vertical-align:top">:</td><td style="color:#333;padding:5px 0;font-family:Georgia,serif;font-size:13px">${d.nama}</td></tr>
          <tr><td style="color:#666;padding:5px 0;font-family:Georgia,serif;font-size:13px;vertical-align:top">Media Sosial</td><td style="color:#666;padding:5px 8px;font-family:Georgia,serif;font-size:13px;vertical-align:top">:</td><td style="color:#333;padding:5px 0;font-family:Georgia,serif;font-size:13px">${d.medsos||'&mdash;'}</td></tr>
          <tr><td style="color:#666;padding:5px 0;font-family:Georgia,serif;font-size:13px;vertical-align:top">WhatsApp</td><td style="color:#666;padding:5px 8px;font-family:Georgia,serif;font-size:13px;vertical-align:top">:</td><td style="color:#333;padding:5px 0;font-family:Georgia,serif;font-size:13px">${d.whatsapp||'&mdash;'}</td></tr>
        </table>
      </div>
      <div>
        <div style="font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:700;color:#000;margin-bottom:16px">Payment Details</div>
        <table style="border-collapse:collapse">
          <tr><td style="color:#666;padding:4px 0;width:114px;font-family:Georgia,serif;font-size:13px">No. Order</td><td style="color:#666;padding:4px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="color:#333;padding:4px 0;font-family:Georgia,serif;font-size:13px">${d.no_invoice}</td></tr>
          <tr><td style="color:#666;padding:4px 0;font-family:Georgia,serif;font-size:13px">Status</td><td style="color:#666;padding:4px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="padding:4px 0;font-family:Georgia,serif;font-size:13px"><span style="font-weight:600;color:${sc}">${d.status_bayar}</span></td></tr>
          <tr><td style="color:#666;padding:4px 0;font-family:Georgia,serif;font-size:13px">Booking Date</td><td style="color:#666;padding:4px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="color:#333;padding:4px 0;font-family:Georgia,serif;font-size:13px">${tgl}</td></tr>
          <tr><td style="color:#666;padding:4px 0;font-family:Georgia,serif;font-size:13px">Trip Date</td><td style="color:#666;padding:4px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="color:#333;padding:4px 0;font-family:Georgia,serif;font-size:13px">${d.tanggal||'&mdash;'}</td></tr>
          <tr><td style="color:#666;padding:4px 0;font-family:Georgia,serif;font-size:13px">Trip Time</td><td style="color:#666;padding:4px 8px;font-family:Georgia,serif;font-size:13px">:</td><td style="color:#333;padding:4px 0;font-family:Georgia,serif;font-size:13px">${d.waktu_kegiatan||'&mdash;'}</td></tr>
        </table>
      </div>
    </div>
    <div style="font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:700;color:#000;margin-bottom:14px">Order Details</div>
    <table style="width:100%;border-collapse:collapse">
      <thead><tr style="border-top:1.5px solid #333;border-bottom:1.5px solid #333">
        <th style="padding:12px 0;font-size:13px;font-weight:700;text-align:left;color:#000;font-family:Georgia,serif">Product</th>
        <th style="padding:12px 16px;font-size:13px;font-weight:700;text-align:right;color:#000;font-family:Georgia,serif">Price<br>(Rp)</th>
        <th style="padding:12px 16px;font-size:13px;font-weight:700;text-align:center;color:#000;font-family:Georgia,serif">QTY</th>
        <th style="padding:12px 0 12px 16px;font-size:13px;font-weight:700;text-align:right;color:#000;font-family:Georgia,serif">Total<br>(Rp)</th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table>
    <table style="width:100%;border-collapse:collapse">
      <tr style="border-top:1.5px solid #333"><td style="width:54%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Total</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">${fRpDot(tot)}</td></tr>
      <tr><td style="width:54%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Discount (Rp.)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">${fRpDot(disc)}</td></tr>
      <tr><td style="width:54%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Total Invoice Amount</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">${fRpDot(tinv)}</td></tr>
      <tr><td style="width:54%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Ammount Paid (DP)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">${(d.status_bayar==='DP'&&dp>0)?fRpDot(dp):'&mdash;'}</td></tr>
      <tr><td style="width:54%;padding:0"></td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Amount Paid (Final)</td><td style="padding:10px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif;text-align:right">${(d.status_bayar==='Lunas'||d.status_bayar==='Paid Off')?fRpDot(tinv):'&mdash;'}</td></tr>
      <tr style="border-top:1.5px solid #333;border-bottom:1.5px solid #333"><td style="width:54%;padding:0"></td><td style="padding:11px 0;font-weight:700;font-size:13px;color:#000;font-family:Georgia,serif">Outstanding Balance</td><td style="padding:11px 0;font-weight:900;font-size:16px;color:#000;font-family:Georgia,serif;text-align:right;white-space:nowrap">${fRpDot(sisa)}</td></tr>
    </table>
    <div style="height:100px"></div>
    <div style="text-align:right"><div style="font-weight:700;font-size:14px;color:#000;font-family:Georgia,'Times New Roman',serif;margin-bottom:10px">Best Regards</div><div style="font-weight:900;font-size:17px;color:#000;font-family:Arial,Helvetica,sans-serif">Pangandaran.in</div></div>
  </div>`;
}

async function openInvoice(id){
  const r=await(await fetch('admin.php?act=get_booking&id='+id)).json();
  if(!r.ok) return;
  const d=r.data;const items=d.items||[];
  document.getElementById('moInvBody').innerHTML=buildInvoiceHTML(d,items);
  document.getElementById('invPDF').onclick=()=>genPDF(d,items);
  document.getElementById('invWA').onclick=()=>{
    const fRpDot=n=>(parseInt(n)||0).toLocaleString('id-ID');
    const tinv=parseInt(d.total_invoice??0),dp=parseInt(d.dp??0),sisa=parseInt(d.sisa_bayar??0)||Math.max(0,tinv-dp);
    const li=items.map(it=>`- ${it.produk} x${it.qty} = Rp ${fRpDot(it.subtotal)}`).join('\n');
    const msg=`Invoice Pangandaran.in\n\nNo. Invoice: ${d.no_invoice}\nNama: ${d.nama}\nWhatsApp: ${d.whatsapp||'-'}\nMedia Sosial: ${d.medsos||'-'}\nTanggal Trip: ${d.tanggal||'-'}\nWaktu Trip: ${d.waktu_kegiatan||'-'}\nStatus: ${d.status_bayar}\n\nItem Dipesan:\n${li}\n\nTotal Invoice : Rp ${fRpDot(tinv)}\nDP Terbayar  : Rp ${fRpDot(dp)}\nSisa Tagihan : Rp ${fRpDot(sisa)}`;
    const num='6285930478524';
    alert('Nomor WA yang dipakai: '+num);
    window.open(`https://web.whatsapp.com/send?phone=${num}&text=${encodeURIComponent(msg)}`,'_blank');
  };
  openM('moInv');
}

function genPDF(d,items){
  const {jsPDF}=window.jspdf;const doc=new jsPDF({unit:'mm',format:'a4'});
  if(!items) items=d.items||[];
  const tot=parseInt(d.total_harga)||0;const disc=parseInt(d.discount)||0;
  const tinv=parseInt(d.total_invoice)||tot-disc;const dp=parseInt(d.dp)||0;
  const sisa=parseInt(d.sisa_bayar)||Math.max(0,tinv-dp);const tgl=fNow();
  if(LOGO){try{doc.addImage(LOGO,'PNG',14,12,22,22);}catch(e){}}
  doc.setTextColor(0,89,179);doc.setFontSize(17);doc.setFont('helvetica','bold');doc.text('Pangandaran.in',40,19);
  doc.setTextColor(140);doc.setFontSize(8);doc.setFont('helvetica','normal');
  doc.text('CV. Pangandaran in Group',40,25);doc.text('https://pangandaran.in',40,30);
  doc.text('pangandaraningroup@gmail.com  |  @pangandaran.in',40,35);
  doc.setTextColor(140);doc.setFontSize(8);
  doc.text('https://pangandaran.in',196,13,{align:'right'});
  doc.text('pangandaraningroup@gmail.com',196,18,{align:'right'});
  doc.text('@pangandaran.in',196,23,{align:'right'});
  doc.setTextColor(30);doc.setFontSize(22);doc.setFont('helvetica','bold');doc.text('Receipt',196,35,{align:'right'});
  doc.setDrawColor(180);doc.setLineWidth(0.5);doc.line(14,42,196,42);
  doc.setFontSize(11);doc.setFont('helvetica','bold');doc.setTextColor(30);doc.text('Bill to',14,52);
  doc.setFont('helvetica','normal');doc.setFontSize(9);
  [['Name',d.nama],['Media Sosial',d.medsos||'—'],['WhatsApp',d.whatsapp||'—']].forEach(([k,v],i)=>{
    doc.setTextColor(130);doc.text(k+' :',14,61+i*8);
    doc.setTextColor(50);doc.setFont('helvetica','bold');doc.text(v,42,61+i*8);doc.setFont('helvetica','normal');
  });
  doc.setFontSize(11);doc.setFont('helvetica','bold');doc.setTextColor(30);doc.text('Payment Details',110,52);
  doc.setFont('helvetica','normal');doc.setFontSize(9);
  [['No. Order',d.no_invoice],['Status',d.status_bayar],['Booking Date',tgl],['Trip Date',d.tanggal||'—'],['Trip Time',d.waktu_kegiatan||'—']].forEach(([k,v],i)=>{
    doc.setTextColor(130);doc.text(k,110,61+i*8);
    doc.setTextColor(50);doc.setFont('helvetica','bold');doc.text(': '+v,148,61+i*8);doc.setFont('helvetica','normal');
  });
  doc.setFontSize(11);doc.setFont('helvetica','bold');doc.setTextColor(30);doc.text('Order Details',14,112);
  doc.autoTable({startY:117,head:[['Product','Price\n(Rp)','QTY','Total(Rp)']],
    body:items.map(it=>[it.produk,fRp(it.harga),String(it.qty),fRp(it.subtotal)]),
    theme:'plain',headStyles:{fillColor:[255,255,255],textColor:[80],fontSize:9,fontStyle:'bold',lineWidth:{bottom:0.5},lineColor:[180]},
    bodyStyles:{fontSize:9,textColor:[60],lineWidth:{bottom:0.3},lineColor:[220]},
    columnStyles:{1:{halign:'right'},2:{halign:'right'},3:{halign:'right'}},margin:{left:14,right:14}});
  let y=doc.lastAutoTable.finalY+8;
  [['Total',fRp(tot),false,false],['Discount (Rp.)',fRp(disc),false,false],
   ['Total Invoice Amount',fRp(tinv),true,false],['Ammount Paid (DP)',fRp(dp),false,false],
   ['Amount Paid (Final)',dp>=tinv?fRp(dp):'—',false,false],['Outstanding Balance',fRp(sisa),false,true]
  ].forEach(([k,v,bold,last])=>{
    if(bold){doc.setDrawColor(30);doc.setLineWidth(0.4);doc.line(116,y-4,196,y-4);}
    doc.setFont('helvetica',bold?'bold':'normal');doc.setFontSize(bold?9.5:9);
    if(last) doc.setTextColor(sisa===0?34:210,sisa===0?197:150,sisa===0?94:0);
    else doc.setTextColor(bold?20:90);
    doc.text(k,116,y);doc.text(v,196,y,{align:'right'});y+=8;
    if(last){doc.setDrawColor(180);doc.setLineWidth(0.3);doc.line(116,y-3,196,y-3);}
  });
  y+=8;doc.setDrawColor(180);doc.line(14,y,196,y);
  doc.setFontSize(9);doc.setFont('helvetica','normal');doc.setTextColor(150);doc.text('Best Regards',196,y+10,{align:'right'});
  doc.setFont('helvetica','bold');doc.setFontSize(12);doc.setTextColor(0,89,179);doc.text('Pangandaran.in',196,y+19,{align:'right'});
  doc.save('Receipt_'+d.no_invoice+'.pdf');
}

function openItemForm(data=null){
  document.getElementById('itId').value=data?.id||'';document.getElementById('itNama').value=data?.nama||'';
  document.getElementById('itH').value=data?.harga||0;document.getElementById('itSat').value=data?.satuan||'orang';
  document.getElementById('itKat').value=data?.kategori||'sea';
  document.getElementById('moItemTitle').textContent=data?'Edit Item':'Tambah Item Baru';
  updItH();openM('moItem');
}
function updItH(){const v=parseInt(document.getElementById('itH')?.value||0);document.getElementById('itHD').textContent=v>0?'Rp '+fRp(v)+' / '+document.getElementById('itSat').value:'Nego';}
async function saveItem(){
  const nama=document.getElementById('itNama').value.trim();if(!nama){toast('Nama wajib.','err');return;}
  const body=new URLSearchParams({act:'save_item',id:document.getElementById('itId').value||0,
    nama,harga:document.getElementById('itH').value||0,satuan:document.getElementById('itSat').value,kategori:document.getElementById('itKat').value});
  const r=await(await fetch('admin.php',{method:'POST',body})).json();
  if(r.ok){toast('Item disimpan!','ok');closeM('moItem');setTimeout(()=>location.reload(),1100);}
}
async function toggleItem(id,btn){
  await fetch('admin.php',{method:'POST',body:new URLSearchParams({act:'toggle_item',id})});
  const on=btn.textContent.trim()==='Aktif';
  btn.textContent=on?'Nonaktif':'Aktif';btn.style.color=on?'rgba(148,163,184,1)':'#22c55e';
  btn.closest('tr').style.opacity=on?'0.35':'1';toast('Status diperbarui','ok',2000);
}
async function deleteBooking(id){
  if(!id) return;
  if(!confirm('Hapus data booking ini? Data invoice dan item yang dipesan akan ikut terhapus.')) return;

  const r=await(await fetch('admin.php',{
    method:'POST',
    body:new URLSearchParams({act:'delete_booking',id})
  })).json();

  if(r.ok){
    toast('Data booking dihapus','ok',2000);
    setTimeout(()=>location.reload(),900);
  } else {
    toast(r.msg || 'Gagal menghapus data booking.','err');
  }
}

async function deleteItem(id){
  if(!confirm('Hapus item ini?')) return;
  const r=await(await fetch('admin.php',{method:'POST',body:new URLSearchParams({act:'delete_item',id})})).json();
  if(r.ok){toast('Item dihapus','ok',2000);setTimeout(()=>location.reload(),900);}
}
</script>
</body>
</html>