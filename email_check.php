
<?php

use kivweb\Models\DatabaseConnection;

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once ('app/Models/DatabaseConnection.class.php');
require_once ('settings.php');

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    try {
        $db = new DatabaseConnection(HOST, DB_NAME, USERNAME, PASSWORD);
        $isEmailUsed = $db->isEmailUsed($email);
        echo json_encode(['isEmailUsed' => $isEmailUsed]);
    }
    catch (Exception $e) {
        echo json_encode(['isEmailUsed' => true]);
    }
}
else {
    echo json_encode(['isEmailUsed' => true]);
}