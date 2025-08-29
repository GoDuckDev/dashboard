<?php
require_once '../models/UserModel.php';

$userModel = new UserModel();
$result = $userModel->createUser(
    'admin',                    // username
    'admin@example.com',        // email
    'AdminPassword123!@#',      // password
    1                          // role_id (1 = admin)
);

if ($result['success']) {
    echo "Admin user created successfully!";
} else {
    echo "Error: " . $result['error'];
}
?>