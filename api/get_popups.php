<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Load popups
$popups = loadJsonData(POPUPS_FILE);

// Filter active popups
$activePopups = array_filter($popups, function($popup) {
    return ($popup['active'] ?? true) && 
           strtotime($popup['start_date'] ?? date('Y-m-d')) <= time() &&
           strtotime($popup['end_date'] ?? date('Y-m-d', strtotime('+1 year'))) >= time();
});

echo json_encode(array_values($activePopups));
?>