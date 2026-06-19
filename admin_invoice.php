<?php
session_start();
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'Pangandaran.in';
if (isset($_POST['login'])) {
    if ($_POST['username']===$ADMIN_USER && $_POST['password']===$ADMIN_PASS) $_SESSION['admin']=true;
    else $err=true;
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin_invoice.php'); exit; }
if (!isset($_SESSION['admin'])) { ?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Login</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',sans-serif;background:#00132f;color:#d6e3ff;min-height:100vh;display:flex;align-items:center;justify-content:center}.box{background:rgba(5,27,57,.85);border:1px solid rgba(162,231,255,.1);border-radius:20px;padding:40px;width:370px}h1{font-size:20px;margin-bottom:24px;color:#a2e7ff}label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:rgba(162,231,255,.5);display:block;margin-bottom:7px}input[type=text],input[type=password]{width:100%;padding:11px 14px;border-radius:10px;border:1px solid rgba(162,231,255,.12);background:rgba(10,31,61,.9);color:#d6e3ff;font-size:13px;margin-bottom:14px;outline:none}button{width:100%;padding:13px;border-radius:10px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:14px;border:none;cursor:pointer}</style>
</head><body><div class="box"><h1>Admin — Pangandaran.in</h1>
<?php if(isset($err)):?><p style="color:#ff8080;font-size:12px;margin-bottom:12px">Username atau password salah.</p><?php endif;?>
<form method="POST"><label>Username</label><input type="text" name="username" required><label>Password</label><input type="password" name="password" required><input type="hidden" name="login" value="1"><button>Masuk</button></form></div></body></html>
<?php exit; }

$conn = new mysqli("localhost","root","","pangandaran_db");
$rows = [];
$res = $conn->query("SELECT * FROM booking ORDER BY id DESC");
while ($r=$res->fetch_assoc()) $rows[]=$r;
$total = count($rows);
$pendapatan = array_sum(array_column($rows,'total_harga'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Invoice — Admin Pangandaran.in</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--ocean:#00132f;--b:rgba(162,231,255,.1);--cyan:#a2e7ff;--blue:#aac7ff;--text:#d6e3ff;--muted:rgba(214,227,255,.45)}
body{font-family:'Inter',sans-serif;background:var(--ocean);color:var(--text);min-height:100vh}
.nav{height:56px;background:rgba(0,14,37,.97);border-bottom:1px solid var(--b);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:50}
.brand{font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:#fff}
.brand span{color:var(--cyan)}
.badge-admin{font-size:9px;background:rgba(162,231,255,.1);color:var(--cyan);padding:2px 8px;border-radius:10px;letter-spacing:1px;text-transform:uppercase;border:1px solid rgba(162,231,255,.15);margin-left:6px;font-family:Inter}
.nav-links{display:flex;gap:6px;align-items:center}
.nav-links a{padding:7px 14px;border-radius:8px;font-size:13px;color:var(--muted);text-decoration:none;transition:.15s}
.nav-links a:hover{background:rgba(162,231,255,.06);color:var(--text)}
.nav-links a.active{background:rgba(162,231,255,.08);color:var(--text)}
.wrap{max-width:1100px;margin:0 auto;padding:28px 24px}
.page-tag{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:var(--muted);margin-bottom:6px}
.page-title{font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:700;margin-bottom:24px;letter-spacing:-.3px}
.stats{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap}
.stat{flex:1;min-width:140px;background:rgba(5,27,57,.6);border:1px solid var(--b);border-radius:14px;padding:16px 18px}
.stat-num{font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:800;color:#fff;line-height:1}
.stat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-top:5px}
.filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.filter-bar input,.filter-bar select{padding:10px 14px;border-radius:10px;border:1px solid var(--b);background:rgba(10,31,61,.8);color:var(--text);font-size:13px;font-family:'Inter',sans-serif;outline:none}
.filter-bar input{flex:1;min-width:200px}
.tbl-wrap{background:rgba(5,27,57,.5);border:1px solid var(--b);border-radius:16px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{padding:12px 16px;font-size:9.5px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);text-align:left;background:rgba(10,31,61,.5);border-bottom:1px solid var(--b)}
tbody tr{border-bottom:1px solid rgba(162,231,255,.04);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(162,231,255,.03)}
td{padding:12px 16px;font-size:12.5px;vertical-align:middle}
.td-inv{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:11px;color:var(--cyan)}
.td-nama{font-weight:500;color:#fff}
.td-muted{font-size:11px;color:var(--muted)}
.badge{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;padding:3px 8px;border-radius:7px}
.bd{background:rgba(162,231,255,.09);color:var(--cyan);border:1px solid rgba(162,231,255,.18)}
.bb{background:rgba(170,199,255,.09);color:var(--blue);border:1px solid rgba(170,199,255,.18)}
.btn-view{padding:5px 14px;border-radius:7px;background:rgba(162,231,255,.07);border:1px solid rgba(162,231,255,.14);color:var(--cyan);font-size:11px;font-weight:600;cursor:pointer;transition:.15s;font-family:'Inter',sans-serif}
.btn-view:hover{background:rgba(162,231,255,.14)}
.empty{text-align:center;padding:60px;color:var(--muted);font-size:14px}

/* MODAL */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:200;display:none;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(5px)}
.overlay.on{display:flex}
.modal-box{background:#fff;border-radius:14px;width:100%;max-width:660px;max-height:90vh;overflow-y:auto;position:relative}
.modal-close{position:absolute;top:10px;right:14px;background:none;border:none;font-size:24px;cursor:pointer;color:#aaa;line-height:1;z-index:1;padding:2px 6px}
.modal-close:hover{color:#555}
.btn-dl{display:block;width:100%;padding:15px;background:linear-gradient(135deg,#aac7ff,#0059b3);color:#001b3e;font-weight:700;font-size:13px;border:none;cursor:pointer;font-family:'Space Grotesk',sans-serif;letter-spacing:.3px;border-radius:0 0 14px 14px}
.btn-dl:hover{opacity:.9}

/* RECEIPT HTML */
.rcp{padding:36px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222}
.rcp-top{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:18px;border-bottom:2.5px solid #e0e0e0;margin-bottom:24px}
.rcp-brand{display:flex;align-items:center;gap:13px}
.rcp-logo{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#0059b3,#a2e7ff);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px;flex-shrink:0}
.rcp-brand-txt h2{font-size:19px;font-weight:800;color:#0059b3;margin:0;line-height:1.1}
.rcp-brand-txt p{font-size:9.5px;color:#999;margin:2px 0}
.rcp-right-top{text-align:right}
.rcp-right-top .meta{font-size:9.5px;color:#999;line-height:1.9}
.rcp-right-top .receipt-label{font-size:24px;font-weight:800;color:#111;margin-top:7px}
.rcp-cols{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:22px}
.rcp-section h3{font-size:13px;font-weight:700;color:#111;padding-bottom:6px;border-bottom:1.5px solid #eee;margin-bottom:10px}
.rcp-row{display:flex;gap:8px;margin-bottom:5px;font-size:11.5px}
.rcp-row .k{color:#888;min-width:80px;flex-shrink:0}
.rcp-row .v{font-weight:600;color:#111}
.rcp-tbl{width:100%;border-collapse:collapse;margin-bottom:16px}
.rcp-tbl thead th{background:#f5f6f8;padding:9px 11px;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#666;border-bottom:2px solid #ddd;text-align:left}
.rcp-tbl thead th.r{text-align:right}
.rcp-tbl tbody td{padding:10px 11px;font-size:11.5px;border-bottom:1px solid #f2f2f2;color:#333}
.rcp-tbl tbody td.r{text-align:right;font-weight:500}
.rcp-sum{margin-left:auto;width:260px}
.rcp-sum-row{display:flex;justify-content:space-between;padding:5px 0;font-size:11.5px;border-bottom:1px solid #f2f2f2;color:#555}
.rcp-sum-row.bold{font-weight:700;color:#111;font-size:12.5px;border-top:2px solid #222;border-bottom:none;padding-top:9px;margin-top:3px}
.rcp-sum-row.pending{color:#d97706;font-weight:600}
.rcp-footer{margin-top:28px;padding-top:16px;border-top:1.5px solid #eee;text-align:right}
.rcp-footer p{font-size:11px;color:#999}
.rcp-footer strong{font-size:14px;font-weight:800;color:#0059b3}
</style>
</head>
<body>
<div class="nav">
  <div><span class="brand">Pangandaran<span>.in</span></span><span class="badge-admin">Admin</span></div>
  <div class="nav-links">
    <a href="admin_paket.php">Kelola Paket</a>
    <a href="admin_booking.php">Data Booking</a>
    <a href="admin_invoice.php" class="active">Invoice</a>
    <a href="index.html" target="_blank">Website</a>
    <a href="?logout=1" style="color:rgba(255,150,150,.5)">Keluar</a>
  </div>
</div>

<div class="wrap">
  <div class="page-tag">Laporan</div>
  <div class="page-title">Daftar Invoice</div>

  <div class="stats">
    <div class="stat"><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Total Invoice</div></div>
    <div class="stat"><div class="stat-num">Rp <?= number_format($pendapatan,0,',','.') ?></div><div class="stat-lbl">Total Nilai Booking</div></div>
  </div>

  <div class="filter-bar">
    <input type="text" id="srch" placeholder="Cari nama, paket, atau no. invoice..." oninput="doFilter()">
    <select id="fSumber" onchange="doFilter()">
      <option value="">Semua Sumber</option>
      <option value="detail">Detail Paket</option>
      <option value="booking">Booking Cepat</option>
    </select>
  </div>

  <div class="tbl-wrap">
    <table id="tbl">
      <thead><tr>
        <th>No. Invoice</th><th>Nama</th><th>Paket</th>
        <th>Tanggal Trip</th><th style="text-align:center">Peserta</th>
        <th style="text-align:right">Total</th><th>Sumber</th><th>Aksi</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)):?>
        <tr><td colspan="8" class="empty">Belum ada data.</td></tr>
      <?php else: foreach($rows as $r):
        $s=$r['sumber']??'booking';
        $bc=$s==='detail'?'bd':'bb';
        $bt=$s==='detail'?'Detail Paket':'Booking Cepat';
        $inv=$r['no_invoice']?:'—';
        $tot=$r['total_harga']>0?'Rp '.number_format($r['total_harga'],0,',','.'):'—';
      ?>
        <tr>
          <td class="td-inv"><?=htmlspecialchars($inv)?></td>
          <td class="td-nama"><?=htmlspecialchars($r['nama'])?></td>
          <td><?=htmlspecialchars($r['paket'])?></td>
          <td class="td-muted"><?=$r['tanggal']?></td>
          <td style="text-align:center;font-weight:700;color:var(--cyan)"><?=$r['peserta']?></td>
          <td style="text-align:right;font-weight:600"><?=$tot?></td>
          <td><span class="badge <?=$bc?>"><?=$bt?></span></td>
          <td>
            <?php if($r['no_invoice']):?>
              <button class="btn-view" onclick='viewInvoice(<?=json_encode($r)?>)'>Lihat Invoice</button>
            <?php else:?><span style="font-size:11px;color:var(--muted)">—</span><?php endif;?>
          </td>
        </tr>
      <?php endforeach; endif;?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="overlay" id="overlay" onclick="closeOverlay(event)">
  <div class="modal-box">
    <button class="modal-close" onclick="document.getElementById('overlay').classList.remove('on')">×</button>
    <div id="rcpWrap"></div>
    <button class="btn-dl" id="dlBtn">Download Invoice PDF</button>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script>
let cur = null;

function fmtRp(n){ return (parseInt(n)||0).toLocaleString('id-ID'); }
function fmtDate(){ return new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}); }

function viewInvoice(d) {
  cur = d;
  const harga = parseInt(d.harga_satuan)||0;
  const total = parseInt(d.total_harga)||(harga*parseInt(d.peserta||1));
  const tgl = fmtDate();

  document.getElementById('rcpWrap').innerHTML = `
    <div class="rcp">
      <div class="rcp-top">
        <div class="rcp-brand">
          <div class="rcp-logo">P</div>
          <div class="rcp-brand-txt">
            <h2>Pangandaran.in</h2>
            <p>CV. Pangandaran in Group</p>
            <p>https://pangandaran.in</p>
            <p>pangandaraningroup@gmail.com</p>
            <p>@pangandaran.in</p>
          </div>
        </div>
        <div class="rcp-right-top">
          <div class="meta">https://pangandaran.in<br>pangandaraningroup@gmail.com<br>@pangandaran.in</div>
          <div class="receipt-label">Receipt</div>
        </div>
      </div>
      <div class="rcp-cols">
        <div class="rcp-section">
          <h3>Bill to</h3>
          <div class="rcp-row"><span class="k">Name</span><span class="v">${d.nama}</span></div>
          <div class="rcp-row"><span class="k">WhatsApp</span><span class="v">${d.whatsapp||'—'}</span></div>
        </div>
        <div class="rcp-section">
          <h3>Payment Details</h3>
          <div class="rcp-row"><span class="k">No. Order</span><span class="v" style="color:#0059b3">${d.no_invoice}</span></div>
          <div class="rcp-row"><span class="k">Status</span><span class="v" style="color:#d97706">${d.status_bayar||'Pending'}</span></div>
          <div class="rcp-row"><span class="k">Date</span><span class="v">${tgl}</span></div>
          <div class="rcp-row"><span class="k">Trip Date</span><span class="v">${d.tanggal}</span></div>
        </div>
      </div>
      <div class="rcp-section">
        <h3>Order Details</h3>
        <table class="rcp-tbl">
          <thead><tr>
            <th>Product</th>
            <th class="r">Price (Rp)</th>
            <th class="r">QTY</th>
            <th class="r">Total (Rp)</th>
          </tr></thead>
          <tbody><tr>
            <td>${d.paket}</td>
            <td class="r">${fmtRp(harga)}</td>
            <td class="r">${d.peserta}</td>
            <td class="r">${fmtRp(total)}</td>
          </tr></tbody>
        </table>
        <div class="rcp-sum">
          <div class="rcp-sum-row"><span>Total</span><span>${fmtRp(total)}</span></div>
          <div class="rcp-sum-row"><span>Discount (Rp.)</span><span>0</span></div>
          <div class="rcp-sum-row bold"><span>Total Invoice Amount</span><span>${fmtRp(total)}</span></div>
          <div class="rcp-sum-row"><span>Amount Paid (DP)</span><span>—</span></div>
          <div class="rcp-sum-row pending"><span>Outstanding Balance</span><span>Menunggu Konfirmasi</span></div>
        </div>
      </div>
      <div class="rcp-footer"><p>Best Regards</p><strong>Pangandaran.in</strong></div>
    </div>`;

  document.getElementById('dlBtn').onclick = () => generatePDF(cur);
  document.getElementById('overlay').classList.add('on');
}

function closeOverlay(e){ if(e.target===document.getElementById('overlay')) document.getElementById('overlay').classList.remove('on'); }

function generatePDF(d) {
  const {jsPDF} = window.jspdf;
  const doc = new jsPDF({unit:'mm',format:'a4'});
  const harga = parseInt(d.harga_satuan)||0;
  const total = parseInt(d.total_harga)||(harga*parseInt(d.peserta||1));
  const tgl = fmtDate();

  // Logo circle
  doc.setFillColor(0,89,179);
  doc.circle(22,22,10,'F');
  doc.setTextColor(255,255,255);
  doc.setFont('helvetica','bold');
  doc.setFontSize(12);
  doc.text('P',22,26,{align:'center'});

  // Brand name
  doc.setTextColor(0,89,179);
  doc.setFontSize(17);
  doc.text('Pangandaran.in',36,19);
  doc.setTextColor(140);
  doc.setFontSize(8);
  doc.setFont('helvetica','normal');
  doc.text('CV. Pangandaran in Group',36,25);
  doc.text('https://pangandaran.in',36,30);
  doc.text('pangandaraningroup@gmail.com  |  @pangandaran.in',36,35);

  // Right side meta
  doc.setTextColor(140);
  doc.setFontSize(8);
  doc.text('https://pangandaran.in',196,14,{align:'right'});
  doc.text('pangandaraningroup@gmail.com',196,19,{align:'right'});
  doc.text('@pangandaran.in',196,24,{align:'right'});

  // Receipt title
  doc.setTextColor(30);
  doc.setFontSize(20);
  doc.setFont('helvetica','bold');
  doc.text('Receipt',196,34,{align:'right'});

  // Divider
  doc.setDrawColor(180);
  doc.setLineWidth(0.5);
  doc.line(14,42,196,42);

  // Bill to
  doc.setFontSize(10);
  doc.setFont('helvetica','bold');
  doc.setTextColor(30);
  doc.text('Bill to',14,52);

  doc.setFont('helvetica','normal');
  doc.setFontSize(8.5);
  const billData = [['Name',d.nama],['WhatsApp',d.whatsapp||'—']];
  billData.forEach(([k,v],i) => {
    doc.setTextColor(130); doc.text(k+' :',14,60+i*7);
    doc.setTextColor(40); doc.setFont('helvetica','bold'); doc.text(v,40,60+i*7);
    doc.setFont('helvetica','normal');
  });

  // Payment details
  doc.setFontSize(10);
  doc.setFont('helvetica','bold');
  doc.setTextColor(30);
  doc.text('Payment Details',110,52);

  const payData = [['No. Order',d.no_invoice],['Status',d.status_bayar||'Pending'],['Date',tgl],['Trip Date',d.tanggal]];
  doc.setFont('helvetica','normal');
  doc.setFontSize(8.5);
  payData.forEach(([k,v],i) => {
    doc.setTextColor(130); doc.text(k,110,60+i*7);
    doc.setTextColor(40); doc.setFont('helvetica','bold'); doc.text(': '+v,140,60+i*7);
    doc.setFont('helvetica','normal');
  });

  // Order details title
  doc.setFontSize(10);
  doc.setFont('helvetica','bold');
  doc.setTextColor(30);
  doc.text('Order Details',14,92);

  // Table
  doc.autoTable({
    startY: 96,
    head: [['Product','Price (Rp)','QTY','Total (Rp)']],
    body: [[d.paket, fmtRp(harga), String(d.peserta), fmtRp(total)]],
    theme: 'grid',
    headStyles: {fillColor:[245,246,248],textColor:[80],fontSize:8.5,fontStyle:'bold',halign:'left'},
    bodyStyles: {fontSize:8.5,textColor:[55]},
    columnStyles: {0:{cellWidth:'auto'},1:{halign:'right'},2:{halign:'right'},3:{halign:'right'}},
    margin: {left:14,right:14},
  });

  let y = doc.lastAutoTable.finalY + 8;

  // Summary rows
  const sumRows = [
    ['Total', fmtRp(total), false],
    ['Discount (Rp.)', '0', false],
    ['Total Invoice Amount', fmtRp(total), true],
    ['Amount Paid (DP)', '—', false],
    ['Outstanding Balance', 'Menunggu Konfirmasi', false],
  ];

  sumRows.forEach(([k,v,bold],i) => {
    if (bold) {
      doc.setDrawColor(40);
      doc.setLineWidth(0.4);
      doc.line(120,y-4,196,y-4);
    }
    doc.setFont('helvetica', bold?'bold':'normal');
    doc.setFontSize(bold?9.5:8.5);
    doc.setTextColor(bold?20:90);
    doc.text(k,120,y);
    doc.text(v,196,y,{align:'right'});
    y += 8;
  });

  // Footer
  doc.setDrawColor(180);
  doc.line(14,y+4,196,y+4);
  doc.setFontSize(8.5);
  doc.setFont('helvetica','normal');
  doc.setTextColor(150);
  doc.text('Best Regards',196,y+12,{align:'right'});
  doc.setFont('helvetica','bold');
  doc.setFontSize(11);
  doc.setTextColor(0,89,179);
  doc.text('Pangandaran.in',196,y+20,{align:'right'});

  doc.save('Invoice_'+d.no_invoice+'.pdf');
}

function doFilter() {
  const s = document.getElementById('srch').value.toLowerCase();
  const sb = document.getElementById('fSumber').value.toLowerCase();
  document.querySelectorAll('#tbl tbody tr').forEach(row => {
    const t = row.textContent.toLowerCase();
    row.style.display = (!s||t.includes(s))&&(!sb||t.includes(sb)) ? '' : 'none';
  });
}
</script>
</body>
</html>
<?php $conn->close(); ?>