<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$table = $data['table_no'] ?? null;
$items = $data['items'] ?? [];
$total = $data['total'] ?? 0;

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO orders (table_no, total_price, order_date) VALUES (?, ?, NOW())");
    $stmt->execute([$table, $total]);
    $order_id = $conn->lastInsertId();

   foreach ($items as $item) {
    if (!isset($item['product_id'], $item['price'], $item['quantity'])) {
        echo json_encode(["success" => false, "message" => "ข้อมูลสินค้าไม่ครบ"]);
        exit;
    }

    $quantity = $item['quantity'];
    $price = $item['price'];
    $subtotal = $quantity * $price;

    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmtItem->execute([$order_id, $item['product_id'], $quantity, $price, $subtotal]);
}

    $conn->commit();
    echo json_encode(["success" => true, "message" => "บันทึกคำสั่งซื้อเรียบร้อย"]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
