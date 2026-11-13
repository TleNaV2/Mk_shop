<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // ดึงเฉพาะคำสั่งซื้อที่เสร็จแล้ว
    $stmt = $conn->prepare("
        SELECT 
            o.id AS order_id,
            o.table_no,
            o.total_price,
            o.status,
            oi.product_id,
            p.product_name AS product_name,
            oi.quantity,
            oi.price,
            (oi.price * oi.quantity) AS subtotal,
            o.order_date
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN products p ON oi.product_id = p.product_id
        WHERE o.status = 'เสร็จแล้ว'
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $orders]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
