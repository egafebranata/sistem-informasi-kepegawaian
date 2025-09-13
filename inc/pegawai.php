<?php
header("Content-Type: application/json");

// Koneksi database
$koneksi = new mysqli("localhost", "root", "","belajardb");
if ($koneksi->connect_error) {
    die(json_encode(["error" => "Koneksi gagal: " . $koneksi->connect_error]));
}

// Ambil method HTTP
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case "GET":
    if (isset($_GET['id'])) {
        // Ambil satu data berdasarkan id
        $id = $_GET['id'];
        $stmt = $koneksi->prepare("SELECT id, nip, nama_lengkap, jenis_kelamin, jabatan_id, skpd_id, unit_kerja_id, nama_golongan, nama_pangkat, alamat_lengkap FROM pegawai WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Data tidak ditemukan"]);
        }
    } else {
        // Ambil semua data
        $result = $koneksi->query("SELECT id, nip, nama_lengkap, jenis_kelamin, jabatan_id, skpd_id, unit_kerja_id, nama_golongan, nama_pangkat, alamat_lengkap FROM pegawai");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
    }
    break;

    case "POST":
    // Cek semua field wajib
    $fields = ["nip", "nama_lengkap", "jenis_kelamin", "jabatan_id", "skpd_id", "unit_kerja_id", "nama_golongan", "nama_pangkat", "alamat_lengkap"];
    foreach ($fields as $f) {
        if (!isset($_POST[$f])) {
            http_response_code(400);
            echo json_encode(["error" => "Field '$f' wajib diisi"]);
            exit;
        }
    }

    // Ambil data
    $nip             = $_POST['nip'];
    $nama_lengkap    = $_POST['nama_lengkap'];
    $jenis_kelamin   = $_POST['jenis_kelamin'];
    $jabatan_id      = $_POST['jabatan_id'];
    $skpd_id         = $_POST['skpd_id'];
    $unit_kerja_id    = $_POST['unit_kerja_id'];
    $nama_golongan   = $_POST['nama_golongan'];
    $nama_pangkat    = $_POST['nama_pangkat'];
    $alamat_lengkap  = $_POST['alamat_lengkap'];

    // Query insert
    $stmt = $koneksi->prepare("INSERT INTO pegawai (nip, nama_lengkap, jenis_kelamin, jabatan_id, skpd_id, unit_kerja_id, nama_golongan, nama_pangkat, alamat_lengkap) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issiiisss", $nip, $nama_lengkap, $jenis_kelamin, $jabatan_id, $skpd_id, $unit_kerja_id, $nama_golongan, $nama_pangkat, $alamat_lengkap);

    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Data berhasil ditambahkan",
            "id"      => $koneksi->insert_id,
            "data"    => [
                "nip" => $nip,
                "nama_lengkap" => $nama_lengkap,
                "jenis_kelamin" => $jenis_kelamin,
                "jabatan_id" => $jabatan_id,
                "skpd_id" => $skpd_id,
                "unit_kerja_id" => $unit_kerja_id,
                "nama_golongan" => $nama_golongan,
                "nama_pangkat" => $nama_pangkat,
                "alamat_lengkap" => $alamat_lengkap
                ]]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Gagal menambahkan data", "detail" => $stmt->error]);
    }
    break;

    // -------------------- UPDATE (PUT) --------------------
case "PUT":
    // Ambil data dari params (query string)
    if (!isset($_GET['id']) || !isset($_GET['jabatan'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field 'id' dan 'jabatan' wajib diisi"]);
        exit;
    }

    $id = $_GET['id'];
    $jabatan = $_GET['jabatan'];

    $stmt = $koneksi->prepare("UPDATE jabatan SET jabatan = ? WHERE id = ?");
    $stmt->bind_param("si", $jabatan, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Data berhasil diupdate",
            "id" => $id,
            "jabatan" => $jabatan
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Gagal update data", "detail" => $stmt->error]);
    }
    break;

// -------------------- DELETE --------------------
case "DELETE":
    // Ambil data dari params
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field 'id' wajib diisi"]);
        exit;
    }

    $id = $_GET['id'];

    $stmt = $koneksi->prepare("DELETE FROM jabatan WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Data berhasil dihapus",
            "id" => $id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Gagal hapus data", "detail" => $stmt->error]);
    }
    break;
}
?>