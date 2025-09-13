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
            $stmt = $koneksi->prepare("SELECT * FROM skpd WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            echo json_encode($data ? $data : ["error" => "Data tidak ditemukan"], JSON_PRETTY_PRINT);
        } else {
            $result = $koneksi->query("SELECT * FROM skpd");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($data, JSON_PRETTY_PRINT);
        }
        break;

    case "POST":
        //$input = json_decode(file_get_contents("php://input"), true);
        if (!isset($_POST['skpd'])) {
            http_response_code(400);
            echo json_encode(["error" => "Field 'skpd' wajib diisi"]);
            exit;
        }
        $skpd = $_POST['skpd'];
        $stmt = $koneksi->prepare("INSERT INTO skpd (skpd) VALUES (?)");
        $stmt->bind_param("s", $skpd);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Data berhasil ditambahkan", "id" => $koneksi->insert_id, "skpd" => $skpd]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Gagal menambahkan data"]);
        }
        break;

    // -------------------- UPDATE (PUT) --------------------
case "PUT":
    // Ambil data dari params (query string)
    if (!isset($_GET['id']) || !isset($_GET['skpd'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field 'id' dan 'skpd' wajib diisi"]);
        exit;
    }

    $id = $_GET['id'];
    $skpd = $_GET['skpd'];

    $stmt = $koneksi->prepare("UPDATE skpd SET skpd = ? WHERE id = ?");
    $stmt->bind_param("si", $skpd, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Data berhasil diupdate",
            "id" => $id,
            "skpd" => $skpd
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

    $stmt = $koneksi->prepare("DELETE FROM skpd WHERE id = ?");
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