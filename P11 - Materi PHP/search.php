<?php
    include "koneksi.php";

   $keyword = isset($_GET['q']) ? $_GET['q'] : '';
    $searchTerm = "%" . $keyword . "%";

    $sql = "SELECT * FROM users WHERE name LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($data);
        
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Gagal menyiapkan query database.']);
    }

    $conn->close();
