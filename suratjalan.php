<?php
class SuratJalan {
    private $noSuratJalan;
    private $tanggalKirim;
    private $tujuanPenerima;
    private $namaSupir;
    private $statusPengiriman;

    public function __construct($noSuratJalan, $tanggalKirim, $tujuanPenerima, $namaSupir, $statusPengiriman = "Diproses") {
        $this->noSuratJalan = $noSuratJalan;
        $this->tanggalKirim = $tanggalKirim;
        $this->tujuanPenerima = $tujuanPenerima;
        $this->namaSupir = $namaSupir;
        $this->statusPengiriman = $statusPengiriman;
    }

    public function getNoSuratJalan() { return $this->noSuratJalan; }
    public function getTanggalKirim() { return $this->tanggalKirim; }
    public function getTujuanPenerima() { return $this->tujuanPenerima; }
    public function getNamaSupir() { return $this->namaSupir; }
    public function getStatusPengiriman() { return $this->statusPengiriman; }

    public function updateStatus($statusBaru) {
        $this->statusPengiriman = $statusBaru;
    }

    public function isTerkirim() {
        return $this->statusPengiriman === "Terkirim";
    }
}
?>