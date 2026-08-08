<?php
include '../config/db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $sql = "DELETE FROM medicines WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        // Redirect directly back to the main inventory panel clear of old IDs
        header("Location: http://life-care.lovestoblog.com/index.php?status=deleted");
        exit;
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
} else {
    header("Location: http://life-care.lovestoblog.com/index.php");
    exit;
}