    <?php
    require_once 'Person.php';

    class Owner  extends Person {
        private $nama_bengkel;
        private $level_akses;

        public function __construct($id_owner, $nama_lengkap, $no_telepon, $email, $nama_bengkel, $level_akses = "Full Access") {
            parent::__construct($id_owner, $nama_lengkap, $no_telepon, $email, "Pemilik Bengkel");
            $this->setNamaBengkel($nama_bengkel);
            $this->level_akses = $level_akses;
        }
        
        // GETTER
        public function getIdOwner() {
            return $this->id_person;
        }

        public function getNamaBengkel() {
            return $this->nama_bengkel;
        }

        public function getLevelAkses() {
            return $this->level_akses;
        }

        // SETTER
        public function setNamaBengkel($nama_bengkel) {
            if (!empty(trim($nama_bengkel))) {
                $this->nama_bengkel = trim($nama_bengkel);
            }
        }

        public function getProfilOwner() {
            return "[{$this->id_person}] {$this->nama_lengkap} — Pemilik {$this->nama_bengkel}";
        }

        public function getKartuKontak() {
            return "{$this->nama_bengkel} | Hubungi: {$this->no_telepon} / {$this->email}";
        }

        public function otorisasiDokumen($nomorDokumen) {
            return "Disetujui secara resmi oleh {$this->nama_lengkap} ({$this->nama_bengkel}) untuk Dokumen #{$nomorDokumen}";
        }

        public function ubahNamaBengkel($namaBaru) {
            $this->setNamaBengkel($namaBaru);
        }
    }
    ?>