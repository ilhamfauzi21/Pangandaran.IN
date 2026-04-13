<?php
$conn = new mysqli("localhost","root","","pangandaran_db");

$result = $conn->query("SELECT * FROM booking ORDER BY id DESC");
?>

<h2>Data Booking</h2>

<table border="1" cellpadding="10">
<tr>
<th>ID</th>
<th>Nama</th>
<th>Paket</th>
<th>Tanggal</th>
<th>Peserta</th>
</tr>

<?php
while($row = $result->fetch_assoc()){
?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['nama']; ?></td>
<td><?php echo $row['paket']; ?></td>
<td><?php echo $row['tanggal']; ?></td>
<td><?php echo $row['peserta']; ?></td>
</tr>
<?php } ?>

</table>