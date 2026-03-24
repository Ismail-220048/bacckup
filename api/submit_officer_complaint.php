<?php
/**
 * CivicTrack — Submit Officer Complaint API
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();

$userId = $_SESSION['user_id'];
$complaintId = $_POST['complaint_id'] ?? '';
$description = $_POST['report_description'] ?? '';

if (!$complaintId || !$description) {
    echo json_encode(['success' => false, 'message' => 'Missing complaint ID or description']);
    exit;
}

try {
    $complaints = $db->getCollection('complaints');
    $complaint = $complaints->findOne(['_id' => new MongoDB\BSON\ObjectId($complaintId), 'user_id' => $userId]);

    if (!$complaint || !isset($complaint['assigned_officer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found or no officer assigned']);
        exit;
    }

    $officer_reports = $db->getCollection('officer_reports');
    $officer_reports->insertOne([
        'user_id'            => $userId,
        'user_name'          => $_SESSION['user_name'],
        'complaint_id'       => $complaintId,
        'original_title'     => $complaint['title'],
        'officer_id'         => $complaint['assigned_officer_id'],
        'report_description' => $description,
        'status'             => 'Pending Admin Review',
        'district'           => $complaint['district'] ?? '',
        'state'              => $complaint['state'] ?? '',
        'created_at'         => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
