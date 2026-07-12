<?php

function hitungFullPayment($totalHarga)
{
    return $totalHarga;
}

function hitungDownPayment($totalHarga, $persenDp = 30)
{
    $dp = $totalHarga * ($persenDp / 100);
    $sisa = $totalHarga - $dp;

    return [
        "dp" => $dp,
        "sisa" => $sisa
    ];
}

function tentukanStatusPembayaran($metodePembayaran)
{
    if ($metodePembayaran === "full") {
        return "Lunas";
    }

    if ($metodePembayaran === "dp") {
        return "DP";
    }

    return "Menunggu Pembayaran";
}