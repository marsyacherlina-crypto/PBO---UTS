<?php
require_once 'classes/Owner.php';
require_once 'classes/SparePart.php';
require_once 'classes/Stok.php';
require_once 'classes/SuratJalan.php';
require_once 'classes/Faktur.php';

session_start();

if (!isset($_SESSION['initialized'])) {
    $_SESSION['owner'] = serialize(new Owner(
        "OWNER-01",
        "Cepi Sunarya",
        "0812-8890-4412",
        "cepi@rizkimotor.id",
        "Rizki Motor Cireunghas"
    ));

    $_SESSION['parts'] = [
        "SP-001" => serialize(new SparePart("SP-001", "Kampas Rem Depan (Dispad)", "Pengereman", "Honda Genuine Parts", 65000)),
        "SP-002" => serialize(new SparePart("SP-002", "Oli Mesin Matic 10W-30 (0.8L)", "Pelumas & Cairan", "MPX2", 55000)),
        "SP-003" => serialize(new SparePart("SP-003", "Van Belt + Roller Set CVT", "Transmisi / CVT", "Bando", 165000)),
        "SP-004" => serialize(new SparePart("SP-004", "Busi Iridium CPR9EAIX-9", "Kelistrikan", "NGK", 95000))
    ];

    $_SESSION['stoks'] = [
        "SP-001" => serialize(new Stok("STK-01", "SP-001", 18, "RAK-B01", 5)),
        "SP-002" => serialize(new Stok("STK-02", "SP-002", 4, "RAK-A01", 5)),
        "SP-003" => serialize(new Stok("STK-03", "SP-003", 9, "RAK-C02", 5)),
        "SP-004" => serialize(new Stok("STK-04", "SP-004", 3, "RAK-B04", 5))
    ];

    $_SESSION['surat_jalan'] = [
        "SJ-2026-01" => serialize(new SuratJalan("SJ-2026-01", "2026-11-12", "Bengkel Cabang Sukabumi", "Agus", "Dikirim"))
    ];

    $_SESSION['faktur'] = [
        "INV-2026-01" => serialize(new Faktur("INV-2026-01", "SJ-2026-01", "2026-11-12", 195000, "Belum Lunas"))
    ];

    $_SESSION['initialized'] = true;
}

$owner = unserialize($_SESSION['owner']);
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 2. LOGIKA CREATE & UPDATE (MENGGUNAKAN GETTER & SETTER)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Profil Owner via Setter
    if (isset($_POST['update_owner'])) {
        $owner->setIdPerson($_POST['id_owner']);
        $owner->setNamaLengkap($_POST['nama_lengkap']);
        $owner->updateKontak($_POST['no_telepon'], $_POST['email']);
        $owner->setNamaBengkel($_POST['nama_bengkel']);
        $_SESSION['owner'] = serialize($owner);

        header("Location: index.php?page=owner");
        exit;
    }

    // Tambah / Edit Spare Part & Stok
    if (isset($_POST['simpan_part'])) {
        $kode = strtoupper(trim($_POST['kodePart']));
        $part = new SparePart($kode, $_POST['namaPart'], $_POST['kategori'], $_POST['merk'], (int)$_POST['hargaSatuan']);
        $_SESSION['parts'][$kode] = serialize($part);

        if (isset($_SESSION['stoks'][$kode])) {
            $stokLama = unserialize($_SESSION['stoks'][$kode]);
            $stokLama->setJumlahTersedia((int)$_POST['jumlahStok']);
            $stokLama->setLokasiRak($_POST['lokasiRak']);
            $_SESSION['stoks'][$kode] = serialize($stokLama);
        } else {
            $stok = new Stok("STK-" . rand(10, 99), $kode, (int)$_POST['jumlahStok'], $_POST['lokasiRak']);
            $_SESSION['stoks'][$kode] = serialize($stok);
        }

        header("Location: index.php?page=sparepart");
        exit;
    }

    // Restock Cepat di Menu Stok
    if (isset($_POST['restock_part'])) {
        $kode = $_POST['kodePartRestock'];
        $tambah = (int)$_POST['qtyTambah'];
        if (isset($_SESSION['stoks'][$kode])) {
            $stokObj = unserialize($_SESSION['stoks'][$kode]);
            $stokObj->tambahStok($tambah);
            $_SESSION['stoks'][$kode] = serialize($stokObj);
        }
        header("Location: index.php?page=stok");
        exit;
    }

    // Terbitkan Surat Jalan & Faktur Otomatis
    if (isset($_POST['buat_sj'])) {
        $noSj = strtoupper(trim($_POST['noSuratJalan']));
        $kodePart = $_POST['kodePartKirim'];
        $qty = (int)$_POST['qtyKirim'];

        if (isset($_SESSION['stoks'][$kodePart]) && isset($_SESSION['parts'][$kodePart])) {
            $stokObj = unserialize($_SESSION['stoks'][$kodePart]);
            $partObj = unserialize($_SESSION['parts'][$kodePart]);

            if ($stokObj->kurangiStok($qty)) {
                $_SESSION['stoks'][$kodePart] = serialize($stokObj);

                $sj = new SuratJalan($noSj, date('Y-m-d'), $_POST['tujuanPenerima'], $_POST['namaSupir']);
                $_SESSION['surat_jalan'][$noSj] = serialize($sj);

                $noInv = "INV-" . substr($noSj, 3);
                $total = $partObj->hitungSubtotal($qty);
                $faktur = new Faktur($noInv, $noSj, date('Y-m-d'), $total);
                $_SESSION['faktur'][$noInv] = serialize($faktur);
            }
        }
        header("Location: index.php?page=suratjalan");
        exit;
    }
}

// 3. LOGIKA UPDATE STATUS & DELETE
if (isset($_GET['aksi'])) {
    if ($_GET['aksi'] === 'hapus_part' && isset($_GET['kode'])) {
        unset($_SESSION['parts'][$_GET['kode']]);
        unset($_SESSION['stoks'][$_GET['kode']]);
        header("Location: index.php?page=sparepart");
        exit;
    }
    if ($_GET['aksi'] === 'kirim_selesai' && isset($_GET['sj'])) {
        $sj = unserialize($_SESSION['surat_jalan'][$_GET['sj']]);
        $sj->updateStatus("Terkirim");
        $_SESSION['surat_jalan'][$_GET['sj']] = serialize($sj);
        header("Location: index.php?page=suratjalan");
        exit;
    }
    if ($_GET['aksi'] === 'hapus_sj' && isset($_GET['sj'])) {
        unset($_SESSION['surat_jalan'][$_GET['sj']]);
        header("Location: index.php?page=suratjalan");
        exit;
    }
    if ($_GET['aksi'] === 'lunasi' && isset($_GET['inv'])) {
        $inv = unserialize($_SESSION['faktur'][$_GET['inv']]);
        $inv->lunasiFaktur();
        $_SESSION['faktur'][$_GET['inv']] = serialize($inv);
        header("Location: index.php?page=faktur");
        exit;
    }
    if ($_GET['aksi'] === 'hapus_inv' && isset($_GET['inv'])) {
        unset($_SESSION['faktur'][$_GET['inv']]);
        header("Location: index.php?page=faktur");
        exit;
    }
}

// 4. PERSIAPAN DATA EDIT SPARE PART
$editPart = null;
$editStok = null;
if ($page === 'sparepart' && isset($_GET['edit']) && isset($_SESSION['parts'][$_GET['edit']])) {
    $editPart = unserialize($_SESSION['parts'][$_GET['edit']]);
    $editStok = isset($_SESSION['stoks'][$_GET['edit']]) ? unserialize($_SESSION['stoks'][$_GET['edit']]) : null;
}

// 5. KALKULASI METRIK INVENTARIS & KEUANGAN (VIA GETTER)
$totalJenisPart = count($_SESSION['parts']);
$totalUnitFisik = 0;
$nilaiAsetGudang = 0;
$stokKritisCount = 0;
$daftarKategori = [];

foreach ($_SESSION['parts'] as $kode => $pSer) {
    $pObj = unserialize($pSer);
    $daftarKategori[$pObj->getKategori()] = true;
    if (isset($_SESSION['stoks'][$kode])) {
        $sObj = unserialize($_SESSION['stoks'][$kode]);
        $totalUnitFisik += $sObj->getJumlahTersedia();
        $nilaiAsetGudang += ($pObj->getHargaSatuan() * $sObj->getJumlahTersedia());
        if ($sObj->getJumlahTersedia() <= $sObj->getBatasMinimum()) {
            $stokKritisCount++;
        }
    }
}

$sjAktifCount = 0;
foreach ($_SESSION['surat_jalan'] as $sjSer) {
    $sjObj = unserialize($sjSer);
    if (!$sjObj->isTerkirim()) $sjAktifCount++;
}

$totalPendapatanLunas = 0;
$tagihanBelumLunas = 0;
foreach ($_SESSION['faktur'] as $invSer) {
    $invObj = unserialize($invSer);
    if ($invObj->getStatusPembayaran() === 'Lunas') {
        $totalPendapatanLunas += $invObj->getTotalSetelahPajak();
    } else {
        $tagihanBelumLunas += $invObj->getTotalSetelahPajak();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $owner->getNamaBengkel(); ?> — Inventory & Distribution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-app: #09090b;
            --bg-surface: #121216;
            --bg-elevated: #18181d;
            --bg-input: #0e0e11;
            --bg-hover: #1f1f26;
            --border: rgba(255, 255, 255, 0.08);
            --border-strong: rgba(255, 255, 255, 0.15);
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-blue-soft: rgba(59, 130, 246, 0.14);
            --accent-red: #ef4444;
            --accent-red-hover: #dc2626;
            --accent-red-soft: rgba(239, 68, 68, 0.14);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.5);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.45);
        }

        body.light-mode {
            --bg-app: #f4f4f6;
            --bg-surface: #ffffff;
            --bg-elevated: #fafafa;
            --bg-input: #ffffff;
            --bg-hover: #f1f5f9;
            --border: #e4e4e7;
            --border-strong: #d4d4d8;
            --text-primary: #18181b;
            --text-secondary: #52525b;
            --text-muted: #71717a;
            --accent-blue: #2563eb;
            --accent-blue-hover: #1d4ed8;
            --accent-blue-soft: #eff6ff;
            --accent-red: #dc2626;
            --accent-red-hover: #b91c1c;
            --accent-red-soft: #fef2f2;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-app);
            color: var(--text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88em;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background-color: var(--bg-surface);
            border-right: 1px solid var(--border);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 22px 10px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
            flex-shrink: 0;
        }

        .brand-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .brand-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .nav-section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            padding: 0 12px;
            margin-bottom: 8px;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .nav-list a svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 1.9;
            fill: none;
        }

        .nav-list a:hover {
            background-color: var(--bg-hover);
            color: var(--text-primary);
        }

        .nav-list a.active {
            background-color: var(--accent-blue-soft);
            color: var(--accent-blue);
            font-weight: 600;
        }

        .nav-badge {
            background-color: var(--accent-red);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 999px;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 8px;
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--accent-blue);
        }

        .main-content {
            padding: 28px 36px;
            max-width: 1280px;
            width: 100%;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-heading h1 {
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .page-heading p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .topbar-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-theme {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 8px;
            background-color: var(--bg-surface);
            border: 1px solid var(--border-strong);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.83rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-theme:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .kpi-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .kpi-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background-color: var(--accent-blue-soft);
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .kpi-icon.danger {
            background-color: var(--accent-red-soft);
            color: var(--accent-red);
        }

        .kpi-value {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .kpi-sub {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .panel {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            box-shadow: var(--shadow-sm);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }

        .field-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        input, select {
            width: 100%;
            padding: 10px 13px;
            background-color: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.88rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--accent-blue-soft);
        }

        input[readonly] {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            background-color: var(--accent-blue);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s;
        }

        .btn-primary:hover {
            background-color: var(--accent-blue-hover);
        }

        .btn-sm {
            padding: 6px 11px;
            font-size: 0.78rem;
            border-radius: 6px;
        }

        .btn-danger-soft {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 11px;
            background-color: var(--accent-red);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .btn-danger-soft:hover {
            background-color: var(--accent-red-hover);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            color: var(--text-secondary);
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .toolbar input {
            flex: 1;
            min-width: 220px;
        }

        .toolbar select {
            width: 210px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.87rem;
        }

        th {
            background-color: var(--bg-elevated);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: var(--bg-hover);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .pill-blue {
            background-color: var(--accent-blue-soft);
            color: var(--accent-blue);
        }

        .pill-red {
            background-color: var(--accent-red-soft);
            color: var(--accent-red);
        }

        .pill-neutral {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            color: var(--text-secondary);
        }

        .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .stock-meter {
            width: 110px;
            height: 6px;
            background-color: var(--bg-elevated);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 5px;
        }

        .stock-fill {
            height: 100%;
            border-radius: 999px;
            background-color: var(--accent-blue);
        }

        .stock-fill.low {
            background-color: var(--accent-red);
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .invoice-paper {
            background: #ffffff;
            color: #111827;
            padding: 32px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);
        }

        .invoice-paper * {
            color: #111827;
        }

        @media print {
            body * { visibility: hidden; }
            #areaCetak, #areaCetak * { visibility: visible; }
            #areaCetak {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                border: none;
            }
            .no-print { display: none !important; }
        }

        @media (max-width: 960px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="app-shell">
    <!-- SIDEBAR NAVIGASI -->
    <aside class="sidebar">
        <div>
            <div class="brand-box">
                <div class="brand-logo">RM</div>
                <div>
                    <div class="brand-title"><?= $owner->getNamaBengkel(); ?></div>
                    <div class="brand-sub">Inventory & Distribution</div>
                </div>
            </div>

            <div class="nav-section-label">Menu Utama</div>
            <ul class="nav-list">
                <li>
                    <a href="?page=dashboard" class="<?= ($page === 'dashboard') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                            Dashboard
                        </span>
                    </a>
                </li>
                <li>
                    <a href="?page=sparepart" class="<?= ($page === 'sparepart') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            Katalog Spare Part
                        </span>
                    </a>
                </li>
                <li>
                    <a href="?page=stok" class="<?= ($page === 'stok') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                            Stok & Gudang
                        </span>
                        <?php if ($stokKritisCount > 0): ?>
                            <span class="nav-badge"><?= $stokKritisCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="?page=suratjalan" class="<?= ($page === 'suratjalan') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            Surat Jalan
                        </span>
                    </a>
                </li>
                <li>
                    <a href="?page=faktur" class="<?= ($page === 'faktur') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Faktur Tagihan
                        </span>
                    </a>
                </li>
                <li>
                    <a href="?page=owner" class="<?= ($page === 'owner') ? 'active' : ''; ?>">
                        <span class="nav-left">
                            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Profil Owner
                        </span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="avatar-circle"><?= strtoupper(substr($owner->getNamaLengkap(), 0, 2)); ?></div>
            <div>
                <div style="font-size:0.83rem; font-weight:600; color:var(--text-primary);"><?= $owner->getNamaLengkap(); ?></div>
                <div class="mono" style="font-size:0.72rem; color:var(--text-muted);"><?= $owner->getIdOwner(); ?></div>
            </div>
        </div>
    </aside>

    <!-- KONTEN KANAN -->
    <main class="main-content">
        <header class="topbar">
            <div class="page-heading">
                <h1>
                    <?php
                        if ($page === 'dashboard') echo "Ringkasan Operasional & Inventaris";
                        elseif ($page === 'sparepart') echo "Katalog Master Spare Part";
                        elseif ($page === 'stok') echo "Kontrol Stok & Lokasi Rak";
                        elseif ($page === 'suratjalan') echo "Distribusi & Surat Jalan";
                        elseif ($page === 'faktur') echo "Faktur Penagihan (PPN 11%)";
                        elseif ($page === 'owner') echo "Pengaturan Akun Owner";
                    ?>
                </h1>
                <p>Sistem terintegrasi manajemen gudang dan distribusi suku cadang bengkel.</p>
            </div>
            <div class="topbar-controls">
                <button type="button" id="themeToggle" class="btn-theme">☀️ Mode Terang</button>
            </div>
        </header>

        <!-- HALAMAN 0: DASHBOARD -->
        <?php if ($page === 'dashboard'): ?>
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-top">
                        <span class="kpi-title">Total Aset Inventaris</span>
                        <div class="kpi-icon">Rp</div>
                    </div>
                    <div class="kpi-value mono">Rp <?= number_format($nilaiAsetGudang, 0, ',', '.'); ?></div>
                    <div class="kpi-sub">Dari <?= $totalUnitFisik; ?> unit fisik (<?= $totalJenisPart; ?> SKU)</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <span class="kpi-title">Peringatan Stok Minimum</span>
                        <div class="kpi-icon <?= ($stokKritisCount > 0) ? 'danger' : ''; ?>">!</div>
                    </div>
                    <div class="kpi-value" style="<?= ($stokKritisCount > 0) ? 'color:var(--accent-red);' : ''; ?>">
                        <?= $stokKritisCount; ?> Item Kritis
                    </div>
                    <div class="kpi-sub">Batas aman minimum &le; 5 unit</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <span class="kpi-title">Pengiriman Berjalan</span>
                        <div class="kpi-icon">SJ</div>
                    </div>
                    <div class="kpi-value"><?= $sjAktifCount; ?> Surat Jalan</div>
                    <div class="kpi-sub">Status dalam proses / dikirim</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <span class="kpi-title">Pendapatan Lunas (+PPN)</span>
                        <div class="kpi-icon">✓</div>
                    </div>
                    <div class="kpi-value mono">Rp <?= number_format($totalPendapatanLunas, 0, ',', '.'); ?></div>
                    <div class="kpi-sub">Piutang berjalan: Rp <?= number_format($tagihanBelumLunas, 0, ',', '.'); ?></div>
                </div>
            </div>

            <?php if ($stokKritisCount > 0): ?>
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Daftar Barang Perlu Segera Restock</span>
                    <a href="?page=stok" class="btn-primary btn-sm">Kelola Gudang &rarr;</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode SKU</th>
                                <th>Nama Spare Part</th>
                                <th>Lokasi Rak</th>
                                <th>Sisa Unit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['stoks'] as $kode => $sSer):
                                $s = unserialize($sSer);
                                if ($s->getJumlahTersedia() <= $s->getBatasMinimum()):
                                    $p = unserialize($_SESSION['parts'][$kode]);
                            ?>
                            <tr>
                                <td class="mono"><?= $p->getKodePart(); ?></td>
                                <td><strong><?= $p->getNamaPart(); ?></strong><br><span style="font-size:0.78rem; color:var(--text-muted);"><?= $p->getMerk(); ?></span></td>
                                <td><span class="pill pill-neutral mono"><?= $s->getLokasiRak(); ?></span></td>
                                <td class="mono" style="font-weight:700; color:var(--accent-red);"><?= $s->getJumlahTersedia(); ?> unit</td>
                                <td><span class="pill pill-red"><span class="dot"></span><?= $s->cekStatusStok(); ?></span></td>
                            </tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Otorisasi Sistem & Penanggung Jawab</span>
                </div>
                <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:6px;"><?= $owner->getProfilOwner(); ?></p>
                <p style="font-size:0.85rem; color:var(--text-muted);"><?= $owner->otorisasiDokumen("SISTEM-INVENTARIS-AKTIF"); ?></p>
            </div>

        <!-- HALAMAN 1: SPARE PART -->
        <?php elseif ($page === 'sparepart'): ?>
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= $editPart ? "Perbarui Data SKU: {$editPart->getKodePart()}" : "Registrasi Spare Part Baru"; ?></span>
                </div>
                <form method="POST">
                    <div class="form-grid-3">
                        <div class="field-group">
                            <label class="field-label">Kode SKU Part</label>
                            <input type="text" name="kodePart" class="mono" value="<?= $editPart ? $editPart->getKodePart() : ''; ?>" placeholder="SP-005" <?= $editPart ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Nama Spare Part</label>
                            <input type="text" name="namaPart" value="<?= $editPart ? $editPart->getNamaPart() : ''; ?>" placeholder="Contoh: Filter Udara Beat" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Kategori</label>
                            <input type="text" name="kategori" value="<?= $editPart ? $editPart->getKategori() : ''; ?>" placeholder="Pengereman / Mesin / CVT" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Merk / Pabrikan</label>
                            <input type="text" name="merk" value="<?= $editPart ? $editPart->getMerk() : ''; ?>" placeholder="Honda / NGK / Yamalube" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Harga Satuan (Rp)</label>
                            <input type="number" name="hargaSatuan" class="mono" value="<?= $editPart ? $editPart->getHargaSatuan() : ''; ?>" placeholder="65000" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Stok Awal & Lokasi Rak</label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                <input type="number" name="jumlahStok" class="mono" value="<?= $editStok ? $editStok->getJumlahTersedia() : ''; ?>" placeholder="Qty" required>
                                <input type="text" name="lokasiRak" class="mono" value="<?= $editStok ? $editStok->getLokasiRak() : ''; ?>" placeholder="RAK-A1" required>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:6px;">
                        <button type="submit" name="simpan_part" class="btn-primary">
                            <?= $editPart ? "Simpan Perubahan" : "Tambahkan ke Katalog"; ?>
                        </button>
                        <?php if ($editPart): ?>
                            <a href="?page=sparepart" class="btn-ghost">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="toolbar">
                    <input type="text" id="searchPart" onkeyup="filterCatalog()" placeholder="Cari kode SKU, nama suku cadang, atau merk...">
                    <select id="filterKategori" onchange="filterCatalog()">
                        <option value="">Semua Kategori</option>
                        <?php foreach (array_keys($daftarKategori) as $kat): ?>
                            <option value="<?= strtolower($kat); ?>"><?= $kat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-wrap">
                    <table id="tablePart">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nama Spare Part</th>
                                <th>Kategori</th>
                                <th>Merk</th>
                                <th>Harga Satuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['parts'] as $pSerialized): $p = unserialize($pSerialized); ?>
                            <tr data-kategori="<?= strtolower($p->getKategori()); ?>">
                                <td class="mono" style="font-weight:600;"><?= $p->getKodePart(); ?></td>
                                <td><strong><?= $p->getNamaPart(); ?></strong></td>
                                <td><span class="pill pill-neutral"><?= $p->getKategori(); ?></span></td>
                                <td><?= $p->getMerk(); ?></td>
                                <td class="mono"><?= $p->formatHarga(); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <a class="btn-primary btn-sm" href="?page=sparepart&edit=<?= $p->getKodePart(); ?>">Edit</a>
                                        <a class="btn-danger-soft" href="?aksi=hapus_part&kode=<?= $p->getKodePart(); ?>" onclick="return confirm('Hapus spare part ini dari katalog?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- HALAMAN 2: STOK & GUDANG -->
        <?php elseif ($page === 'stok'): ?>
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Input Barang Masuk (Restock Gudang)</span>
                </div>
                <form method="POST" class="form-grid-3" style="align-items:end;">
                    <div class="field-group" style="margin-bottom:0;">
                        <label class="field-label">Pilih Spare Part</label>
                        <select name="kodePartRestock" required>
                            <option value="">-- Pilih Item SKU --</option>
                            <?php foreach ($_SESSION['parts'] as $pSerialized): $p = unserialize($pSerialized); ?>
                                <option value="<?= $p->getKodePart(); ?>">[<?= $p->getKodePart(); ?>] <?= $p->getNamaPart(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label class="field-label">Jumlah Unit Masuk</label>
                        <input type="number" name="qtyTambah" class="mono" placeholder="Contoh: 10" min="1" required>
                    </div>
                    <button type="submit" name="restock_part" class="btn-primary" style="height:41px;">+ Tambah Stok</button>
                </form>
            </div>

            <div class="panel">
                <div class="toolbar">
                    <input type="text" id="searchStok" onkeyup="filterSimpleTable('searchStok', 'tableStok')" placeholder="Cari kode SKU, nama barang, atau kode rak (misal: RAK-A01)...">
                </div>

                <div class="table-wrap">
                    <table id="tableStok">
                        <thead>
                            <tr>
                                <th>ID Stok</th>
                                <th>Spare Part</th>
                                <th>Lokasi Rak</th>
                                <th>Kapasitas Fisik</th>
                                <th>Nilai Stok</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['stoks'] as $kode => $sSerialized): 
                                $s = unserialize($sSerialized);
                                $p = unserialize($_SESSION['parts'][$kode]);
                                $isKritis = ($s->getJumlahTersedia() <= $s->getBatasMinimum());
                                $persenBar = min(100, ($s->getJumlahTersedia() / 20) * 100);
                                $nilaiItem = $p->getHargaSatuan() * $s->getJumlahTersedia();
                            ?>
                            <tr>
                                <td class="mono"><?= $s->getIdStok(); ?></td>
                                <td>
                                    <span class="mono" style="color:var(--accent-blue); font-weight:600;"><?= $p->getKodePart(); ?></span> &mdash; <strong><?= $p->getNamaPart(); ?></strong>
                                </td>
                                <td><span class="pill pill-neutral mono"><?= $s->getLokasiRak(); ?></span></td>
                                <td>
                                    <span class="mono" style="font-weight:700;"><?= $s->getJumlahTersedia(); ?> unit</span>
                                    <div class="stock-meter">
                                        <div class="stock-fill <?= $isKritis ? 'low' : ''; ?>" style="width: <?= $persenBar; ?>%;"></div>
                                    </div>
                                </td>
                                <td class="mono">Rp <?= number_format($nilaiItem, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($isKritis): ?>
                                        <span class="pill pill-red"><span class="dot"></span><?= $s->cekStatusStok(); ?></span>
                                    <?php else: ?>
                                        <span class="pill pill-blue"><span class="dot"></span><?= $s->cekStatusStok(); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- HALAMAN 3: SURAT JALAN -->
        <?php elseif ($page === 'suratjalan'): ?>
            <?php if (isset($_GET['cetak']) && isset($_SESSION['surat_jalan'][$_GET['cetak']])): 
                $sjCetak = unserialize($_SESSION['surat_jalan'][$_GET['cetak']]);
            ?>
            <div id="areaCetak" class="invoice-paper">
                <div style="display:flex; justify-content:space-between; border-bottom:2px solid #111827; padding-bottom:14px; margin-bottom:18px;">
                    <div>
                        <h3 style="font-size:1.2rem;"><?= strtoupper($owner->getNamaBengkel()); ?></h3>
                        <p style="font-size:0.85rem; color:#4b5563;"><?= $owner->getKartuKontak(); ?></p>
                    </div>
                    <div style="text-align:right;" class="mono">
                        <strong>SURAT JALAN PENGIRIMAN</strong><br>
                        No: <?= $sjCetak->getNoSuratJalan(); ?><br>
                        Tgl: <?= $sjCetak->getTanggalKirim(); ?>
                    </div>
                </div>
                <p style="margin-bottom:6px;"><strong>Tujuan Penerima:</strong> <?= $sjCetak->getTujuanPenerima(); ?></p>
                <p style="margin-bottom:6px;"><strong>Supir Pengantar:</strong> <?= $sjCetak->getNamaSupir(); ?></p>
                <p style="margin-bottom:16px;"><strong>Status Pengiriman:</strong> <?= $sjCetak->getStatusPengiriman(); ?></p>
                <hr style="border:none; border-top:1px dashed #9ca3af; margin:16px 0;">
                <p style="font-size:0.82rem; font-style:italic;"><?= $owner->otorisasiDokumen($sjCetak->getNoSuratJalan()); ?></p>
                <div class="no-print" style="margin-top:18px; display:flex; gap:10px;">
                    <button type="button" class="btn-primary btn-sm" onclick="window.print()">Cetak Dokumen</button>
                    <a href="?page=suratjalan" class="btn-danger-soft">Tutup Pratinjau</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Terbitkan Surat Jalan & Faktur Otomatis</span>
                </div>
                <form method="POST">
                    <div class="form-grid-3">
                        <div class="field-group">
                            <label class="field-label">No. Surat Jalan</label>
                            <input type="text" name="noSuratJalan" class="mono" placeholder="SJ-2026-02" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Pilih Spare Part</label>
                            <select name="kodePartKirim" required>
                                <option value="">-- Pilih Item --</option>
                                <?php foreach ($_SESSION['parts'] as $pSerialized): $p = unserialize($pSerialized); ?>
                                    <option value="<?= $p->getKodePart(); ?>"><?= $p->getDetailPart(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Jumlah Unit Dikirim</label>
                            <input type="number" name="qtyKirim" class="mono" placeholder="1" min="1" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="field-group">
                            <label class="field-label">Tujuan Penerima</label>
                            <input type="text" name="tujuanPenerima" placeholder="Nama Bengkel / Pelanggan" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Nama Supir</label>
                            <input type="text" name="namaSupir" placeholder="Nama Pengantar" required>
                        </div>
                    </div>
                    <button type="submit" name="buat_sj" class="btn-primary">Terbitkan Surat Jalan & Faktur</button>
                </form>
            </div>

            <div class="panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Surat Jalan</th>
                                <th>Tanggal</th>
                                <th>Tujuan</th>
                                <th>Supir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['surat_jalan'] as $sjSerialized): $sj = unserialize($sjSerialized); ?>
                            <tr>
                                <td class="mono" style="font-weight:600;"><?= $sj->getNoSuratJalan(); ?></td>
                                <td class="mono"><?= $sj->getTanggalKirim(); ?></td>
                                <td><?= $sj->getTujuanPenerima(); ?></td>
                                <td><?= $sj->getNamaSupir(); ?></td>
                                <td>
                                    <?php if ($sj->isTerkirim()): ?>
                                        <span class="pill pill-blue"><span class="dot"></span>Terkirim</span>
                                    <?php else: ?>
                                        <span class="pill pill-neutral"><span class="dot"></span><?= $sj->getStatusPengiriman(); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if (!$sj->isTerkirim()): ?>
                                            <a class="btn-primary btn-sm" href="?aksi=kirim_selesai&sj=<?= $sj->getNoSuratJalan(); ?>">Tandai Terkirim</a>
                                        <?php endif; ?>
                                        <a class="btn-primary btn-sm" href="?page=suratjalan&cetak=<?= $sj->getNoSuratJalan(); ?>">Cetak</a>
                                        <a class="btn-danger-soft" href="?aksi=hapus_sj&sj=<?= $sj->getNoSuratJalan(); ?>" onclick="return confirm('Hapus surat jalan ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- HALAMAN 4: FAKTUR -->
        <?php elseif ($page === 'faktur'): ?>
            <?php if (isset($_GET['cetak']) && isset($_SESSION['faktur'][$_GET['cetak']])): 
                $invCetak = unserialize($_SESSION['faktur'][$_GET['cetak']]);
            ?>
            <div id="areaCetak" class="invoice-paper">
                <div style="display:flex; justify-content:space-between; border-bottom:2px solid #111827; padding-bottom:14px; margin-bottom:18px;">
                    <div>
                        <h3 style="font-size:1.2rem;"><?= strtoupper($owner->getNamaBengkel()); ?></h3>
                        <p style="font-size:0.85rem; color:#4b5563;"><?= $owner->getKartuKontak(); ?></p>
                    </div>
                    <div style="text-align:right;" class="mono">
                        <strong>FAKTUR PENAGIHAN (INVOICE)</strong><br>
                        No: <?= $invCetak->getNoFaktur(); ?><br>
                        Tgl: <?= $invCetak->getTanggalFaktur(); ?>
                    </div>
                </div>
                <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
                    <thead>
                        <tr style="background:#f3f4f6;">
                            <th style="border:1px solid #d1d5db; padding:8px;">Ref. Surat Jalan</th>
                            <th style="border:1px solid #d1d5db; padding:8px;">Subtotal</th>
                            <th style="border:1px solid #d1d5db; padding:8px;">PPN (11%)</th>
                            <th style="border:1px solid #d1d5db; padding:8px;">Total Tagihan</th>
                            <th style="border:1px solid #d1d5db; padding:8px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="mono">
                            <td style="border:1px solid #d1d5db; padding:8px;"><?= $invCetak->getNoSuratJalan(); ?></td>
                            <td style="border:1px solid #d1d5db; padding:8px;">Rp <?= number_format($invCetak->getTotalTagihan(), 0, ',', '.'); ?></td>
                            <td style="border:1px solid #d1d5db; padding:8px;">Rp <?= number_format($invCetak->hitungPajakPPN(), 0, ',', '.'); ?></td>
                            <td style="border:1px solid #d1d5db; padding:8px;"><strong><?= $invCetak->formatTagihan(); ?></strong></td>
                            <td style="border:1px solid #d1d5db; padding:8px;"><strong><?= $invCetak->getStatusPembayaran(); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-size:0.82rem; font-style:italic;"><?= $owner->otorisasiDokumen($invCetak->getNoFaktur()); ?></p>
                <div class="no-print" style="margin-top:18px; display:flex; gap:10px;">
                    <button type="button" class="btn-primary btn-sm" onclick="window.print()">Cetak Faktur</button>
                    <a href="?page=faktur" class="btn-danger-soft">Tutup Pratinjau</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Faktur</th>
                                <th>Ref. Surat Jalan</th>
                                <th>Tanggal</th>
                                <th>Total (+PPN 11%)</th>
                                <th>Status Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['faktur'] as $invSerialized): 
                                $inv = unserialize($invSerialized);
                                $isLunas = ($inv->getStatusPembayaran() === 'Lunas');
                            ?>
                            <tr>
                                <td class="mono" style="font-weight:600;"><?= $inv->getNoFaktur(); ?></td>
                                <td class="mono"><?= $inv->getNoSuratJalan(); ?></td>
                                <td class="mono"><?= $inv->getTanggalFaktur(); ?></td>
                                <td class="mono" style="font-weight:700;"><?= $inv->formatTagihan(); ?></td>
                                <td>
                                    <?php if ($isLunas): ?>
                                        <span class="pill pill-blue"><span class="dot"></span>Lunas</span>
                                    <?php else: ?>
                                        <span class="pill pill-red"><span class="dot"></span>Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if (!$isLunas): ?>
                                            <a class="btn-primary btn-sm" href="?aksi=lunasi&inv=<?= $inv->getNoFaktur(); ?>">Lunasi</a>
                                        <?php endif; ?>
                                        <a class="btn-primary btn-sm" href="?page=faktur&cetak=<?= $inv->getNoFaktur(); ?>">Cetak</a>
                                        <a class="btn-danger-soft" href="?aksi=hapus_inv&inv=<?= $inv->getNoFaktur(); ?>" onclick="return confirm('Hapus faktur ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- HALAMAN 5: PROFIL OWNER -->
        <?php elseif ($page === 'owner'): ?>
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Identitas Pemilik & Bengkel</span>
                    <span class="pill pill-blue mono"><?= $owner->getIdOwner(); ?></span>
                </div>
                <div class="form-grid-2" style="font-size:0.9rem; color:var(--text-secondary);">
                    <div><strong>Nama Lengkap:</strong> <span style="color:var(--text-primary);"><?= $owner->getNamaLengkap(); ?></span></div>
                    <div><strong>Nama Bengkel:</strong> <span style="color:var(--text-primary);"><?= $owner->getNamaBengkel(); ?></span></div>
                    <div><strong>No. Telepon:</strong> <span class="mono" style="color:var(--text-primary);"><?= $owner->getNoTelepon(); ?></span></div>
                    <div><strong>Email Bisnis:</strong> <span class="mono" style="color:var(--text-primary);"><?= $owner->getEmail(); ?></span></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Perbarui Informasi Owner</span>
                </div>
                <form method="POST">
                    <div class="form-grid-2">
                        <div class="field-group">
                            <label class="field-label">ID Owner</label>
                            <input type="text" name="id_owner" class="mono" value="<?= $owner->getIdOwner(); ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?= $owner->getNamaLengkap(); ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">No. Telepon</label>
                            <input type="text" name="no_telepon" class="mono" value="<?= $owner->getNoTelepon(); ?>" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Email</label>
                            <input type="email" name="email" class="mono" value="<?= $owner->getEmail(); ?>" required>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Nama Bengkel</label>
                        <input type="text" name="nama_bengkel" value="<?= $owner->getNamaBengkel(); ?>" required>
                    </div>
                    <button type="submit" name="update_owner" class="btn-primary">Simpan Perubahan Owner</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    // 1. Switch Mode Terang / Gelap
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    if (localStorage.getItem('temaBengkel') === 'light') {
        body.classList.add('light-mode');
        themeToggle.textContent = '🌙 Mode Gelap';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        if (body.classList.contains('light-mode')) {
            localStorage.setItem('temaBengkel', 'light');
            themeToggle.textContent = '🌙 Mode Gelap';
        } else {
            localStorage.setItem('temaBengkel', 'dark');
            themeToggle.textContent = '☀️ Mode Terang';
        }
    });

    // 2. Filter Gabungan Pencarian & Kategori di Katalog Spare Part
    function filterCatalog() {
        const keyword = document.getElementById('searchPart').value.toLowerCase();
        const kategori = document.getElementById('filterKategori').value.toLowerCase();
        const rows = document.getElementById('tablePart').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const text = rows[i].textContent.toLowerCase();
            const rowKat = rows[i].getAttribute('data-kategori');
            const matchText = text.includes(keyword);
            const matchKat = (kategori === '' || rowKat === kategori);
            rows[i].style.display = (matchText && matchKat) ? '' : 'none';
        }
    }

    // 3. Pencarian Cepat di Tabel Stok
    function filterSimpleTable(inputId, tableId) {
        const keyword = document.getElementById(inputId).value.toLowerCase();
        const rows = document.getElementById(tableId).getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const text = rows[i].textContent.toLowerCase();
            rows[i].style.display = text.includes(keyword) ? '' : 'none';
        }
    }
</script>

</body>
</html>