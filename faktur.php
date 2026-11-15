<?php
class Faktur {
    private $noFaktur;
    private $noSuratJalan;
    private $tanggalFaktur;
    private $totalTagihan;
    private $statusPembayaran;

    public function __construct($noFaktur, $noSuratJalan, $tanggalFaktur, $totalTagihan, $statusPembayaran = "Belum Lunas") {
        $this->noFaktur = $noFaktur;
        $this->noSuratJalan = $noSuratJalan;
        $this->tanggalFaktur = $tanggalFaktur;
        $this->totalTagihan = max(0, (int)$totalTagihan);
        $this->statusPembayaran = $statusPembayaran;
    }

    public function getNoFaktur() { return $this->noFaktur; }
    public function getNoSuratJalan() { return $this->noSuratJalan; }
    public function getTanggalFaktur() { return $this->tanggalFaktur; }
    public function getTotalTagihan() { return $this->totalTagihan; }
    public function getStatusPembayaran() { return $this->statusPembayaran; }

    public function hitungPajakPPN($persen = 11) {
        return $this->totalTagihan * ($persen / 100);
    }

    public function getTotalSetelahPajak() {
        return $this->totalTagihan + $this->hitungPajakPPN();
    }

    public function lunasiFaktur() {
        $this->statusPembayaran = "Lunas";
    }

    public function formatTagihan() {
        return "Rp " . number_format($this->getTotalSetelahPajak(), 0, ',', '.');
    }
}
?>