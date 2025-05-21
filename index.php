<?php
session_start();

// Koneksi ke database MySQL menggunakan PDO
$dsn = 'mysql:host=localhost;dbname=rpl_crud;charset=utf8';
$username = 'root';  // default username biasanya root
$password = '';      // default password biasanya kosong

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kesalahan database: " . $e->getMessage());
}

// Handle form submission untuk add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode']);
    $nama_barang = trim($_POST['nama_barang']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga_satuan = (int)$_POST['harga_satuan'];
    $jumlah = (int)$_POST['jumlah'];

    $foto_filename = null;

    if (!empty($_FILES['foto']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_filename = uniqid('foto_') . '.' . $ext;
        $targetFile = $uploadDir . $foto_filename;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
            $_SESSION['error'] = "Gagal mengupload foto.";
            header("Location: index.php");
            exit;
        }
    }

    if (isset($_POST['edit'])) {
        // Update
        $sql = "UPDATE barang SET nama_barang = :nama_barang, deskripsi = :deskripsi, harga_satuan = :harga_satuan, jumlah = :jumlah";

        if ($foto_filename !== null) {
            $sql .= ", foto = :foto";
        }

        $sql .= " WHERE kode = :kode";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':nama_barang', $nama_barang, PDO::PARAM_STR);
        $stmt->bindValue(':deskripsi', $deskripsi, PDO::PARAM_STR);
        $stmt->bindValue(':harga_satuan', $harga_satuan, PDO::PARAM_INT);
        $stmt->bindValue(':jumlah', $jumlah, PDO::PARAM_INT);
        if ($foto_filename !== null) {
            $stmt->bindValue(':foto', $foto_filename, PDO::PARAM_STR);
        }
        $stmt->bindValue(':kode', $kode, PDO::PARAM_STR);
        $stmt->execute();
        $_SESSION['success'] = "Data barang berhasil diupdate.";
        header("Location: index.php");
        exit;
    } else {
        // Insert
        $sql = "SELECT COUNT(*) FROM barang WHERE kode = :kode";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':kode' => $kode]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Kode barang sudah ada.";
            header("Location: index.php");
            exit;
        }

        $sql = "INSERT INTO barang (kode, nama_barang, deskripsi, harga_satuan, jumlah, foto) VALUES (:kode, :nama_barang, :deskripsi, :harga_satuan, :jumlah, :foto)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kode' => $kode,
            ':nama_barang' => $nama_barang,
            ':deskripsi' => $deskripsi,
            ':harga_satuan' => $harga_satuan,
            ':jumlah' => $jumlah,
            ':foto' => $foto_filename,
        ]);
        $_SESSION['success'] = "Data barang berhasil ditambahkan.";
        header("Location: index.php");
        exit;
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $kode = $_GET['delete'];

    // Hapus file foto jika ada
    $stmt = $pdo->prepare("SELECT foto FROM barang WHERE kode = :kode");
    $stmt->execute([':kode' => $kode]);
    $foto = $stmt->fetchColumn();
    if ($foto && file_exists(__DIR__ . '/uploads/' . $foto)) {
        unlink(__DIR__ . '/uploads/' . $foto);
    }

    // Delete row
    $stmt = $pdo->prepare("DELETE FROM barang WHERE kode = :kode");
    $stmt->execute([':kode' => $kode]);

    $_SESSION['success'] = "Data barang berhasil dihapus.";
    header("Location: index.php");
    exit;
}

// Fetch data for edit if kode passed
$edit_data = null;
if (isset($_GET['edit'])) {
    $kode = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE kode = :kode");
    $stmt->execute([':kode' => $kode]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all barang data
$stmt = $pdo->query("SELECT * FROM barang ORDER BY kode ASC");
$barang = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>CRUD DATA BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #e3f2fd; /* biru muda */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
        }
        header {
            background-color: #0d47a1; /* biru tua */
            color: white;
            padding: 1.5rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 1.5px;
            border-radius: 12px;
            user-select: none;
            margin-bottom: 2rem;
        }
        .main-row {
            display: flex;
            flex-direction: column; /* Mengubah menjadi kolom */
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .form-section {
            background: #bbdefb; /* biru pastel */
            padding: 1.8rem 1.4rem;
            border-radius: 12px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-section h2 {
            color: #0d47a1;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #0d47a1;
        }
        .btn-primary {
            background-color: #0d47a1;
            border-color: #0d47a1;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0a3e7a;
            border-color: #0a3e7a;
        }
        .btn-warning {
            background-color: #ffb74d;
            border-color: #ffb74d;
            color: #0d47a1;
            font-weight: 600;
        }
        .btn-warning:hover {
            background-color: #ffa726;
            border-color: #ffa726;
            color: white;
        }
        .btn-secondary {
            background-color: #ccc;
            color: #0d47a1;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background-color: #bbb;
            color: #0a3e7a;
        }
        .btn-outline-secondary {
            color: #0d47a1;
            border-color: #0d47a1;
            font-weight: 600;
        }
        .btn-outline-secondary:hover {
            background-color: #0d47a1;
            color: white;
        }
        .table-section {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }
        h2.table-title {
            color: #0d47a1;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        table.table {
            border-radius: 10px;
            overflow: hidden;
        }
        table.table thead {
            background-color: #bbdefb;
            color: #0d47a1;
            font-weight: 700;
            font-size: 0.95rem;
        }
        table.table-striped tbody tr:nth-of-type(odd) {
            background-color: #e1f5fe;
        }
        .img-thumb {
            max-width: 60px;
            max-height: 50px;
            border-radius: 6px;
            object-fit: cover;
            border: 2px solid #0d47a1;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<header>CRUD DATA BARANG</header>

<div class="main-row">

    <section class="form-section">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?=htmlspecialchars($_SESSION['success'])?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?=htmlspecialchars($_SESSION['error'])?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

        <h2><?= $edit_data ? "Edit Barang" : "Tambah Barang" ?></h2>
        <form method="post" enctype="multipart/form-data" class="g-3">
            <div>
                <label for="kode" class="form-label">Kode</label>
                <input type="text" class="form-control" id="kode" name="kode" required <?= $edit_data ? "readonly" : "" ?> value="<?= $edit_data ? htmlspecialchars($edit_data['kode']) : '' ?>">
            </div>
            <div>
                <label for="nama_barang" class="form-label">Nama Barang</label>
                <input type="text" class="form-control" id="nama_barang" name="nama_barang" required value="<?= $edit_data ? htmlspecialchars($edit_data['nama_barang']) : '' ?>">
            </div>
            <div>
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= $edit_data ? htmlspecialchars($edit_data['deskripsi']) : '' ?></textarea>
            </div>
            <div>
                <label for="harga_satuan" class="form-label">Harga Satuan</label>
                <input type="number" min="0" class="form-control" id="harga_satuan" name="harga_satuan" required value="<?= $edit_data ? (int)$edit_data['harga_satuan'] : '' ?>">
            </div>
            <div>
                <label for="jumlah" class="form-label">Jumlah</label>
                <input type="number" min="0" class="form-control" id="jumlah" name="jumlah" required value="<?= $edit_data ? (int)$edit_data['jumlah'] : '' ?>">
            </div>
            <div>
                <label for="foto" class="form-label">Foto <?= $edit_data ? "(biarkan kosong jika tidak ganti)" : "" ?></label>
                <input class="form-control" type="file" id="foto" name="foto" accept="image/*">
                <?php if ($edit_data && $edit_data['foto'] && file_exists(__DIR__ . '/uploads/' . $edit_data['foto'])): ?>
                    <img src="uploads/<?=htmlspecialchars($edit_data['foto'])?>" alt="Foto" class="img-thumb mt-2">
                <?php endif; ?>
            </div>

            <div class="mt-3 d-flex gap-2">
                <?php if ($edit_data): ?>
                    <button type="submit" name="edit" class="btn btn-warning flex-fill">Update</button>
                    <a href="index.php" class="btn btn-secondary flex-fill">Batal</a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary flex-fill">Tambah</button>
                    <button type="reset" class="btn btn-outline-secondary flex-fill">Reset</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="table-section">
        <h2 class="table-title">Daftar Barang</h2>
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Deskripsi</th>
                    <th>Harga Satuan</th>
                    <th>Jumlah</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($barang) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Belum ada data barang.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($barang as $b): ?>
                    <tr>
                        <td><?=htmlspecialchars($b['kode'])?></td>
                        <td><?=htmlspecialchars($b['nama_barang'])?></td>
                        <td><?= nl2br(htmlspecialchars($b['deskripsi'])) ?></td>
                        <td>Rp <?=number_format($b['harga_satuan'], 0, ',', '.')?></td>
                        <td><?=intval($b['jumlah'])?></td>
                        <td>
                            <?php if ($b['foto'] && file_exists(__DIR__ . '/uploads/' . $b['foto'])): ?>
                                <img src="uploads/<?=htmlspecialchars($b['foto'])?>" alt="Foto" class="img-thumb">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?edit=<?=urlencode($b['kode'])?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete=<?=urlencode($b['kode'])?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin hapus barang ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
