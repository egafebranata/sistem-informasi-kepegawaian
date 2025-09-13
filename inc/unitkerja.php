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
            $id = intval($_GET['id']);
            $stmt = $koneksi->prepare("SELECT * FROM unit_kerja WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            echo json_encode($data ? $data : ["error" => "Data tidak ditemukan"], JSON_PRETTY_PRINT);
        } else {
            $result = $koneksi->query("SELECT * FROM unit_kerja");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($data, JSON_PRETTY_PRINT);
        }
        break;

    case "POST":
        //$input = json_decode(file_get_contents("php://input"), true);
        if (!isset($_POST['unit_kerja']) || !isset($_POST['skpd_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Field 'unit_kerja' wajib diisi"]);
            exit;
        }
        $unit_kerja = $_POST['unit_kerja'];
        $skpd_id = $_POST['skpd_id'];
        $stmt = $koneksi->prepare("INSERT INTO unit_kerja (unit_kerja,skpd_id) VALUES (?,?)");
        //$stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ss", $unit_kerja, $skpd_id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Data berhasil ditambahkan", "id" => $koneksi->insert_id, "unit_kerja" => $unit_kerja, "skpd_id" => $skpd_id]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Gagal menambahkan data"]);
        }
        break;

    // -------------------- UPDATE (PUT) --------------------
case "PUT":
    // Ambil data dari params (query string)
    if (!isset($_GET['id']) || !isset($_GET['unit_kerja'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field 'id' dan 'unit_kerja' wajib diisi"]);
        exit;
    }

    $id = $_GET['id'];
    $unit_kerja = $_GET['unit_kerja'];

    $stmt = $koneksi->prepare("UPDATE unit_kerja SET unit_kerja = ? WHERE id = ?");
    $stmt->bind_param("si", $unit_kerja, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Data berhasil diupdate",
            "id" => $id,
            "unit_kerja" => $unit_kerja
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

    $stmt = $koneksi->prepare("DELETE FROM unit_kerja WHERE id = ?");
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