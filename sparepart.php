<?php
class SparePart {
    private $kodePart;
    private $namaPart;
    private $kategori;
    private $merk;
    private $hargaSatuan;

    public function __construct($kodePart, $namaPart, $kategori, $merk, $hargaSatuan) {
        $this->kodePart = strtoupper(trim($kodePart));
        $this->setNamaPart($namaPart);
        $this->setKategori($kategori);
        $this->setMerk($merk);
        $this->setHargaSatuan($hargaSatuan);
    }

    // GETTER
    public function getKodePart() { return $this->kodePart; }
    public function getNamaPart() { return $this->namaPart; }
    public function getKategori() { return $this->kategori; }
    public function getMerk() { return $this->merk; }
    public function getHargaSatuan() { return $this->hargaSatuan; }

    // SETTER
    public function setNamaPart($nama) { $this->namaPart = trim($nama); }
    public function setKategori($kategori) { $this->kategori = trim($kategori); }
    public function setMerk($merk) { $this->merk = trim($merk); }
    public function setHargaSatuan($harga) {
        $this->hargaSatuan = max(0, (int)$harga); // Mencegah harga negatif
    }

    public function formatHarga() {
        return "Rp " . number_format($this->hargaSatuan, 0, ',', '.');
    }

    public function hitungSubtotal($qty) {
        return $this->hargaSatuan * max(0, (int)$qty);
    }

    public function getDetailPart() {
        return "[{$this->kodePart}] {$this->namaPart} ({$this->merk}) - " . $this->formatHarga();
    }
}
?>