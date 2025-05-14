<?php
require_once "../config/database.php";
include_once "../includes/header.php";

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view.php");
    exit;
}

$id = $_GET['id'];

// Delete employee
$query = "DELETE FROM employees WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Employee deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting employee";
}

header("Location: view.php");
exit;
?>
