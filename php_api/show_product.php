<?php
include 'condb.php';

header("Content-Type: application/json; charset=utf-8");

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        // ✅ ตรวจสอบว่ามีการส่งค่า category_id มาหรือไม่
        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

        if ($category_id > 0) {
            // ✅ ดึงสินค้าตามประเภท
            $stmt = $conn->prepare("
                SELECT p.*, c.category_name 
                FROM products p 
                JOIN categorys c ON p.category_id = c.category_id 
                WHERE p.category_id = :category_id
                ORDER BY p.product_id DESC
            ");
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        } else {
            // ✅ ดึงสินค้าทั้งหมด
            $stmt = $conn->prepare("
                SELECT p.*, c.category_name 
                FROM products p 
                JOIN categorys c ON p.category_id = c.category_id 
                ORDER BY p.product_id DESC
            ");
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
