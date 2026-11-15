<?php
class Person {
    protected $id_person;
    protected $nama_lengkap;
    protected $no_telepon;
    protected $email;
    protected $peran;

    public function __construct($id_person, $nama_lengkap, $no_telepon, $email, $peran = "Umum") {
        $this->setIdPerson($id_person);
        $this->setNamaLengkap($nama_lengkap);
        $this->setNoTelepon($no_telepon);
        $this->setEmail($email);
        $this->peran = $peran;
    }


    public function getIdPerson() {
        return $this->id_person;
    }

    public function getNamaLengkap() {
        return $this->nama_lengkap;
    }

    public function getNoTelepon() {
        return $this->no_telepon;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPeran() {
        return $this->peran;
    }

    // SETTER DENGAN VALIDASI
    public function setIdPerson($id) {
        $this->id_person = trim($id);
    }

    public function setNamaLengkap($nama) {
        if (!empty(trim($nama))) {
            $this->nama_lengkap = trim($nama);
        }
    }

    public function setNoTelepon($telp) {
        $this->no_telepon = trim($telp);
    }

    public function setEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = $email;
        } else {
            $this->email = "email@tidakvalid.com";
        }
    }

    public function getInfoDasar() {
        return "[{$this->id_person}] {$this->nama_lengkap} ({$this->peran})";
    }

    public function getKontakLengkap() {
        return "Telp: {$this->no_telepon} | Email: {$this->email}";
    }

    public function updateKontak($no_telepon, $email) {
        $this->setNoTelepon($no_telepon);
        $this->setEmail($email);
    }
}
?>