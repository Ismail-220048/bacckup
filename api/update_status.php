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
// Auth check — any admin or officer role
$allowedRoles = ['admin', 'national_admin', 'senior_officer', 'district_admin', 'state_admin', 'officer', 'local_officer'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    if (!empty($_POST)) {
        $input = $_POST;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
        exit;
    }
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
    $adminRoles = ['admin', 'national_admin', 'state_admin', 'district_admin'];
    if (!in_array($_SESSION['role'], $adminRoles)) {
        echo json_encode(['success' => false, 'message' => 'Only admins can delete complaints.']);
        exit;
    }

    // Fetch complaint BEFORE deleting to grab user IDs for notifications
    $toDelete = $complaints->findOne(['_id' => $objectId]);

    $result = $complaints->deleteOne(['_id' => $objectId]);

    if ($result->getDeletedCount() > 0) {
        // Notify all affected users about their complaint being removed
        if ($toDelete) {
            $notifications = $db->getCollection('notifications');
            $delMsg = "Your complaint '" . ($toDelete['title'] ?? 'Unknown') . "' has been deleted by an administrator.";

            // Notify primary user
            if (!empty($toDelete['user_id'])) {
                $notifications->insertOne([
                    'user_id'    => $toDelete['user_id'],
                    'role'       => 'user',
                    'message'    => $delMsg,
                    'is_read'    => false,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Notify any merged additional users
            if (!empty($toDelete['additional_user_ids']) && is_array($toDelete['additional_user_ids'])) {
                foreach ($toDelete['additional_user_ids'] as $uid) {
                    if ($uid) {
                        $notifications->insertOne([
                            'user_id'    => $uid,
                            'role'       => 'user',
                            'message'    => $delMsg,
                            'is_read'    => false,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Complaint deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
    }
    exit;
}

// Officers can only update their assigned complaints
// Officers can only update their assigned complaints (Standard and Local)
// Senior officers and Admins can update anything within their scope (handled by dashboard logic, but here we allow general update if they have the ID)
if (in_array($_SESSION['role'], ['officer', 'local_officer'])) {
    $complaint = $complaints->findOne(['_id' => $objectId]);
    if (!$complaint || ($complaint['assigned_officer_id'] ?? '') !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You can only update complaints assigned to you.']);
        exit;
    }
}

// Build update fields
$validStatuses = ['Pending', 'Submitted', 'Assigned', 'Under Review', 'In Progress', 'Escalated', 'Resolved', 'Officer Completed', 'Closed', 'Reopened'];
$updateFields = [];

if (!empty($input['status'])) {
    $status = htmlspecialchars(trim($input['status']), ENT_QUOTES, 'UTF-8');
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        exit;
    }
    $updateFields['status'] = $status;
    
    // Save Timestamp
    if ($status === 'In Progress' || $status === 'Officer Completed') {
        $updateFields['in_progress_timestamp'] = date('d M Y, h:i A');
    } elseif ($status === 'Resolved') {
        $updateFields['resolved_timestamp'] = date('d M Y, h:i A');
    }
}

if (isset($input['admin_reply'])) {
    $updateFields['admin_reply'] = htmlspecialchars(trim($input['admin_reply']), ENT_QUOTES, 'UTF-8');
}

if (isset($input['officer_notes'])) {
    $updateFields['officer_notes'] = htmlspecialchars(trim($input['officer_notes']), ENT_QUOTES, 'UTF-8');
}

// Assign officer — Admins and Senior Officers can assign
$canAssign = ['admin', 'national_admin', 'senior_officer', 'district_admin', 'state_admin'];
if (isset($input['assigned_officer_id']) && in_array($_SESSION['role'], $canAssign)) {
    $officerId = htmlspecialchars(trim($input['assigned_officer_id']), ENT_QUOTES, 'UTF-8');
    $updateFields['assigned_officer_id'] = $officerId;

    if (!empty($officerId)) {
        $updateFields['assigned_timestamp'] = date('d M Y, h:i A');
        $headOfficersCol = $db->getCollection('head_officers');
        $fieldOfficersCol = $db->getCollection('field_officers');
        try {
            $oid = new MongoDB\BSON\ObjectId($officerId);
            $officer = $headOfficersCol->findOne(['_id' => $oid]) ?? $fieldOfficersCol->findOne(['_id' => $oid]);
            $updateFields['assigned_officer_name'] = $officer ? $officer['name'] : 'Unknown';
        } catch (Exception $e) {
            $updateFields['assigned_officer_name'] = '';
        }
    } else {
        $updateFields['assigned_officer_name'] = '';
    }
}

// Handle officer proof image upload
if (isset($_FILES['officer_proof_image']) && $_FILES['officer_proof_image']['name'] !== '') {
    if ($_FILES['officer_proof_image']['error'] !== UPLOAD_ERR_OK) {
        $errMsg = 'Unknown upload error.';
        if ($_FILES['officer_proof_image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['officer_proof_image']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errMsg = 'Officer proof image size exceeds the allowed system limit (usually 2MB or 5MB). Please compress the image.';
        }
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileInfo = pathinfo($_FILES['officer_proof_image']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($ext, $allowedExt)) {
        $newName = uniqid('proof_') . '.' . $ext;
        $destPath = $uploadDir . $newName;
        
        if (move_uploaded_file($_FILES['officer_proof_image']['tmp_name'], $destPath)) {
            $updateFields['officer_proof_image'] = 'uploads/' . $newName;
        }
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
    // Notify Users and Admins
    $notifications = $db->getCollection('notifications');
    $complaint = $complaints->findOne(['_id' => $objectId]);
    
    if ($complaint) {
        $msg = "Complaint '" . ($complaint['title'] ?? 'Unknown') . "' was updated.";
        
        // Notify the user who originally created it
        if (!empty($complaint['user_id'])) {
            $notifications->insertOne([
                'user_id'    => $complaint['user_id'],
                'role'       => 'user',
                'message'    => $msg,
                'is_read'    => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Notify merged users (if any)
        if (!empty($complaint['additional_user_ids']) && is_array($complaint['additional_user_ids'])) {
            foreach ($complaint['additional_user_ids'] as $uid) {
                if ($uid) {
                    $notifications->insertOne([
                        'user_id'    => $uid,
                        'role'       => 'user',
                        'message'    => $msg,
                        'is_read'    => false,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        // If Officer updated it, notify Admins
        if (in_array($_SESSION['role'], ['officer', 'local_officer', 'senior_officer'])) {
            $notifications->insertOne([
                'role'       => 'admin',
                'message'    => "Officer updated complaint: " . ($complaint['title'] ?? 'Unknown'),
                'is_read'    => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // If Admin/Senior assigned an officer, notify that officer
        if (in_array($_SESSION['role'], $canAssign) && !empty($updateFields['assigned_officer_id'])) {
             $notifications->insertOne([
                'user_id'    => $updateFields['assigned_officer_id'],
                'role'       => 'officer',
                'message'    => "You have been assigned to complaint: " . ($complaint['title'] ?? 'Unknown'),
                'is_read'    => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Complaint updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Complaint not found or no changes made.']);
}
exit;
