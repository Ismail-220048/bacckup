<?php
/**
 * CivicTrack API — Update Complaint Status (Admin + Officer)
 * 
 * Accepts JSON body:
 *   complaint_id       — ID of the complaint
 *   status             — New status (Pending, In Progress, Resolved)
 *   admin_reply        — Optional admin reply text
 *   officer_notes      — Optional officer progress notes
 *   assigned_officer_id — Officer ID to assign (admin only)
 *   action             — "update" or "delete"
 */
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Auth check — admin or officer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'officer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized — admin or officer access required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
    exit;
}

$complaintId = $input['complaint_id'] ?? '';
$action      = $input['action'] ?? 'update';

if (empty($complaintId)) {
    echo json_encode(['success' => false, 'message' => 'Complaint ID is required.']);
    exit;
}

$db = Database::getInstance();
$complaints = $db->getCollection('complaints');

try {
    $objectId = new MongoDB\BSON\ObjectId($complaintId);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.']);
    exit;
}

// Delete — admin only
if ($action === 'delete') {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only admins can delete complaints.']);
        exit;
    }
    $result = $complaints->deleteOne(['_id' => $objectId]);
    if ($result->getDeletedCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Complaint deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    }
    exit;
}

// Officers can only update their assigned complaints
if ($_SESSION['role'] === 'officer') {
    $complaint = $complaints->findOne(['_id' => $objectId]);
    if (!$complaint || ($complaint['assigned_officer_id'] ?? '') !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You can only update complaints assigned to you.']);
        exit;
    }
}

// Build update fields
$validStatuses = ['Pending', 'In Progress', 'Resolved'];
$updateFields = [];

if (!empty($input['status'])) {
    $status = htmlspecialchars(trim($input['status']), ENT_QUOTES, 'UTF-8');
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        exit;
    }
    $updateFields['status'] = $status;
}

if (isset($input['admin_reply'])) {
    $updateFields['admin_reply'] = htmlspecialchars(trim($input['admin_reply']), ENT_QUOTES, 'UTF-8');
}

if (isset($input['officer_notes'])) {
    $updateFields['officer_notes'] = htmlspecialchars(trim($input['officer_notes']), ENT_QUOTES, 'UTF-8');
}

// Assign officer — admin only
if (isset($input['assigned_officer_id']) && $_SESSION['role'] === 'admin') {
    $officerId = htmlspecialchars(trim($input['assigned_officer_id']), ENT_QUOTES, 'UTF-8');
    $updateFields['assigned_officer_id'] = $officerId;

    // Look up officer name
    if (!empty($officerId)) {
        $officersCol = $db->getCollection('officers');
        try {
            $officer = $officersCol->findOne(['_id' => new MongoDB\BSON\ObjectId($officerId)]);
            $updateFields['assigned_officer_name'] = $officer ? $officer['name'] : 'Unknown';
        } catch (Exception $e) {
            $updateFields['assigned_officer_name'] = '';
        }
    } else {
        $updateFields['assigned_officer_name'] = '';
    }
}

if (empty($updateFields)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update.']);
    exit;
}

$result = $complaints->updateOne(
    ['_id' => $objectId],
    ['$set' => $updateFields]
);

if ($result->getModifiedCount() > 0 || $result->getMatchedCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Complaint updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Complaint not found or no changes made.']);
}
exit;
