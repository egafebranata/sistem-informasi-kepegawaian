<?php
header('Content-Type: application/json');

// --- Koneksi database ---
$host = 'localhost';
$db   = 'belajardb'; // ganti sesuai database
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error"=>"Database connection failed: ".$e->getMessage()]);
    exit;
}

// --- Ambil method ---
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$allowedMethods = ['GET','POST','PUT','DELETE'];
if(!in_array($method, $allowedMethods)){
    http_response_code(405);
    echo json_encode([
        "error"=>"Method not allowed",
        "method_received"=>$method,
        "instruction"=>"Gunakan GET, POST, PUT, DELETE"
    ]);
    exit;
}

// --- Ambil Content-Type ---
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';

// --- Fungsi ambil data request ---
function getRequestData($contentType){
    if(stripos($contentType,'application/json')!==false){
        $raw = file_get_contents("php://input");
        $data = json_decode($raw,true);
        if($data===null && json_last_error()!==JSON_ERROR_NONE){
            http_response_code(400);
            echo json_encode([
                "error"=>"JSON tidak valid",
                "raw"=>$raw,
                "json_error"=>json_last_error_msg()
            ]);
            exit;
        }
        return $data;
    } elseif(stripos($contentType,'application/x-www-form-urlencoded')!==false){
        parse_str(file_get_contents("php://input"), $data);
        return $data;
    } else {
        return [];
    }
}

// --- Field wajib ---
$requiredFields = ['nip','nama_lengkap','jenis_kelamin','jabatan','skpd','unit_kerja','nama_golongan','nama_pangkat','alamat_lengkap'];

// --- Ambil data untuk POST/PUT/DELETE ---
$data = in_array($method,['POST','PUT','DELETE']) ? getRequestData($contentType) : [];

// --- Validasi field wajib POST & PUT ---
if(in_array($method,['POST','PUT'])){
    foreach($requiredFields as $field){
        if(!isset($data[$field]) || trim($data[$field])===''){
            http_response_code(400);
            echo json_encode(["error"=>"Field '$field' wajib diisi"]);
            exit;
        }
    }
}

// --- CRUD ---
try{
    switch($method){
        case 'GET':
            $id = $_GET['id'] ?? null;
            if($id){
                $stmt = $pdo->prepare("
                    SELECT pegawai.id, pegawai.nip, pegawai.nama_lengkap, pegawai.jenis_kelamin, jabatan.jabatan,
                           skpd.skpd, unit_kerja.unit_kerja,
                           pegawai.nama_golongan, pegawai.nama_pangkat, pegawai.alamat_lengkap
                    FROM pegawai
                    LEFT JOIN jabatan ON pegawai.jabatan = jabatan.id
                    LEFT JOIN skpd ON pegawai.skpd = skpd.id
                    LEFT JOIN unit_kerja ON pegawai.unit_kerja = unit_kerja.id
                    WHERE pegawai.nip = ?
                ");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if($row){
                    echo json_encode(["success"=>true,"data"=>$row]);
                } else {
                    http_response_code(404);
                    echo json_encode(["error"=>"Data dengan ID $id tidak ditemukan"]);
                }
            } else {
                $stmt = $pdo->query("
                    SELECT pegawai.id, pegawai.nip, pegawai.nama_lengkap, pegawai.jenis_kelamin, jabatan.jabatan,
                           skpd.skpd, unit_kerja.unit_kerja,
                           pegawai.nama_golongan, pegawai.nama_pangkat, pegawai.alamat_lengkap
                    FROM pegawai
                    LEFT JOIN jabatan ON pegawai.jabatan = jabatan.id
                    LEFT JOIN skpd ON pegawai.skpd = skpd.id
                    LEFT JOIN unit_kerja ON pegawai.unit_kerja = unit_kerja.id
                ");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["success"=>true,"data"=>$rows]);
            }
            break;

        case 'POST':
            $fields = implode(",", $requiredFields);
            $placeholders = implode(",", array_fill(0,count($requiredFields),"?"));
            $stmt = $pdo->prepare("INSERT INTO pegawai ($fields) VALUES ($placeholders)");
            $stmt->execute(array_map(fn($f)=>$data[$f], $requiredFields));

            // Ambil data dengan FK
            $stmt2 = $pdo->prepare("
            SELECT p.id, p.nip, p.nama_lengkap, p.jenis_kelamin,
            j.jabatan, s.skpd, u.unit_kerja,
            p.nama_golongan, p.nama_pangkat, p.alamat_lengkap
            FROM pegawai p
            LEFT JOIN jabatan j ON p.jabatan = j.id
            LEFT JOIN skpd s ON p.skpd = s.id
            LEFT JOIN unit_kerja u ON p.unit_kerja = u.id
            WHERE p.nip = ?
            ");
            $stmt2->execute([$data['nip']]);
            $row = $stmt2->fetch();

            echo json_encode(["success"=>true,"added"=>$row]);
            break;

        case 'PUT':
            $setStr = implode(",", array_map(fn($f)=>"$f=?", $requiredFields));
            $stmt = $pdo->prepare("UPDATE pegawai SET $setStr WHERE id=?");
            $stmt->execute([...array_map(fn($f)=>$data[$f], $requiredFields), $data['id']]);

            if($stmt->rowCount()>0){
            $stmt2 = $pdo->prepare("
            SELECT p.id, p.nip, p.nama_lengkap, p.jenis_kelamin,
               j.jabatan, s.skpd, u.unit_kerja,
               p.nama_golongan, p.nama_pangkat, p.alamat_lengkap
            FROM pegawai p
            LEFT JOIN jabatan j ON p.jabatan = j.id
            LEFT JOIN skpd s ON p.skpd = s.id
            LEFT JOIN unit_kerja u ON p.unit_kerja = u.id
            WHERE p.id = ?
            ");
            $stmt2->execute([$data['id']]);
            $row = $stmt2->fetch();
            echo json_encode(["success"=>true,"updated"=>$row]);
            }else{
            http_response_code(404);
            echo json_encode(["error"=>"Data ID {$data['id']} tidak ditemukan"]);
            }
            break;

        case 'DELETE':
            if(!isset($data['id']) || trim($data['id'])===''){
                http_response_code(400);
                echo json_encode(["error"=>"Field 'id' wajib diisi untuk DELETE"]);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM pegawai WHERE id=?");
            $stmt->execute([$data['id']]);
            if($stmt->rowCount()>0){
                echo json_encode(["success"=>true,"deleted"=>$data['id']]);
            } else {
                http_response_code(404);
                echo json_encode(["error"=>"Data dengan ID '{$data['id']}' tidak ditemukan"]);
            }
            break;
    }
} catch(PDOException $e){
    http_response_code(500);
    echo json_encode(["error"=>$e->getMessage()]);
}
?>