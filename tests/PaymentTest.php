<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../app/PaymentHelper.php";

class PaymentTest extends TestCase
{
    public function testFullPayment()
    {
        $hasil = hitungFullPayment(500000);

        $this->assertEquals(500000, $hasil);
    }

    public function testDownPayment()
    {
        $hasil = hitungDownPayment(500000, 30);

        $this->assertEquals(150000, $hasil["dp"]);
        $this->assertEquals(350000, $hasil["sisa"]);
    }

    public function testStatusFullPayment()
    {
        $status = tentukanStatusPembayaran("full");

        $this->assertEquals("Lunas", $status);
    }

    public function testStatusDownPayment()
    {
        $status = tentukanStatusPembayaran("dp");

        $this->assertEquals("DP", $status);
    }
}