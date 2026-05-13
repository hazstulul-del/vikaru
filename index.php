<?php
/**
 * VIKARU RAT API - Entry Point
 * Deploy: Railway
 */

// Set header default
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token');

// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Response root
echo json_encode([
    'status'  => 'online',
    'service' => 'VIKARU RAT API v3.0',
    'server'  => 'Railway',
    'time'    => date('Y-m-d H:i:s'),
    'php'     => PHP_VERSION
]);
