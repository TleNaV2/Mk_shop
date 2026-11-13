<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

// สรุปยอดขายตามวันที่
$sql = "SELECT DATE(order_date) AS date, SUM(total_price) AS total
        FROM orders
        GROUP BY DATE(order_date)
        ORDER BY DATE(order_date) ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result);
