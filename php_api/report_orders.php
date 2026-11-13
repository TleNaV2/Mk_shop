<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");
include 'condb.php';

$type = $_GET['type'] ?? 'table'; // default = table

try {
    if ($type === 'daily') {
        // ✅ รายงานยอดขายรายวัน (เฉพาะที่สถานะ = เสร็จแล้ว)
        $sql = "SELECT DATE(order_date) AS label, SUM(total_price) AS total 
                FROM orders 
                WHERE status = 'เสร็จแล้ว'
                GROUP BY DATE(order_date)
                ORDER BY DATE(order_date)";
    } elseif ($type === 'status') {
        // ✅ จำนวนออเดอร์ตามสถานะ (แสดงทุกสถานะ)
        $sql = "SELECT status AS label, COUNT(*) AS total 
                FROM orders 
                GROUP BY status";
    } else {
        // ✅ ยอดขายตามโต๊ะ (เฉพาะที่สถานะ = เสร็จแล้ว)
        $sql = "SELECT table_no AS label, SUM(total_price) AS total 
                FROM orders 
                WHERE status = 'เสร็จแล้ว'
                GROUP BY table_no
                ORDER BY table_no";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
