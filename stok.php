<?php
class Stok {
    private $idStok;
    private $kodePart;
    private $jumlahTersedia;
    private $lokasiRak;
    private $batasMinimum;

    public function __construct($idStok, $kodePart, $jumlahTersedia, $lokasiRak, $batasMinimum = 5) {
        $this->idStok = $idStok;
        $this->kodePart = $kodePart;
        $this->setJumlahTersedia($jumlahTersedia);
        $this->setLokasiRak($lokasiRak);
        $this->batasMinimum = max(1, (int)$batasMinimum);
    }

    // GETTER
    public function getIdStok() { return $this->idStok; }
    public function getKodePart() { return $this->kodePart; }
    public function getJumlahTersedia() { return $this->jumlahTersedia; }
    public function getLokasiRak() { return $this->lokasiRak; }
    public function getBatasMinimum() { return $this->batasMinimum; }

    // SETTER
    public function setJumlahTersedia($jumlah) {
        $this->jumlahTersedia = max(0, (int)$jumlah);
    }

    public function setLokasiRak($rak) {
        $this->lokasiRak = strtoupper(trim($rak));
    }

    public function tambahStok($qty) {
        if ($qty > 0) {
            $this->jumlahTersedia += (int)$qty;
        }
    }

    public function kurangiStok($qty) {
        if ($qty > 0 && $this->jumlahTersedia >= $qty) {
            $this->jumlahTersedia -= (int)$qty;
            return true;
        }
        return false;
    }

    public function cekStatusStok() {
        return ($this->jumlahTersedia <= $this->batasMinimum) ? "Kritis (Segera Restock)" : "Aman";
    }
}
?>