<?php
header('Content-Type: application/json; charset=utf-8');

$FONNTE_TOKEN = 'TEMPEL_TOKEN_FONNTE_ANDA';   // <- token device Fonnte
$WA_ADMIN     = '6287793827592';              // nomor WA admin

$no_invoice = trim($_POST['no_invoice'] ?? '');
$img        = $_POST['image_base64'] ?? '';
if($no_invoice === '' || $img === ''){
  echo json_encode(['status'=>'error','message'=>'Data invoice kurang.']); exit;
}

// Simpan gambar invoice ke server
if(strpos($img, ',') !== false) $img = substr($img, strpos($img, ',')+1);
$bin = base64_decode($img);
$dir = __DIR__ . '/invoices';
if(!is_dir($dir)) @mkdir($dir, 0775, true);
$safe = preg_replace('/[^A-Za-z0-9_\-]/','', $no_invoice);
$file = 'Invoice_'.$safe.'.png';
file_put_contents($dir.'/'.$file, $bin);

// URL gambar (harus bisa diakses internet agar Fonnte bisa ambil)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https':'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$img_url = $scheme.'://'.$host.$base.'/invoices/'.$file;

// Kirim ke Fonnte
$ch = curl_init('https://api.fonnte.com/send');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => ['target'=>$WA_ADMIN, 'url'=>$img_url, 'filename'=>$file, 'message'=>'Invoice '.$no_invoice],
  CURLOPT_HTTPHEADER => ['Authorization: '.$FONNTE_TOKEN],
  CURLOPT_TIMEOUT => 30,
]);
$out = curl_exec($ch); $err = curl_error($ch); curl_close($ch);

if($err){ echo json_encode(['status'=>'error','message'=>$err]); exit; }
echo json_encode(['status'=>'ok','image_url'=>$img_url,'fonnte'=>json_decode($out,true)]);
exit;
?>