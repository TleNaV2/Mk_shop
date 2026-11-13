<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

// รับข้อมูลจาก request
$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? null;
$product_id = $data['product_id'] ?? null;
$status = $data['status'] ?? null;

if ($order_id && $product_id && $status) {
    try {
        $conn->beginTransaction(); // เริ่ม transaction

        // ตรวจสอบว่ามีสินค้านี้ในคำสั่งซื้อหรือไม่
        $check = $conn->prepare("
            SELECT COUNT(*) 
            FROM order_items 
            WHERE order_id = ? AND product_id = ?
        ");
        $check->execute([$order_id, $product_id]);
        $exists = $check->fetchColumn();

        if ($exists == 0) {
            throw new Exception("ไม่พบสินค้านี้ในคำสั่งซื้อ");
        }

        // อัปเดตสถานะสินค้ารายการนี้
        $stmt = $conn->prepare("
            UPDATE order_items 
            SET status = ? 
            WHERE order_id = ? AND product_id = ?
        ");
        $stmt->execute([$status, $order_id, $product_id]);

        // ตรวจสอบว่าสินค้าทั้งหมดมีสถานะเหมือนกันหรือไม่
        $stmtCheck = $conn->prepare("
            SELECT COUNT(*) AS total, SUM(status = ?) AS matched 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmtCheck->execute([$status, $order_id]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['total'] == $row['matched']) {
            // ถ้าสินค้าทั้งหมดสถานะเหมือนกัน → อัปเดต orders
            $stmtOrder = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmtOrder->execute([$status, $order_id]);
        }

        // คำนวณยอดรวมทั้งหมดของสินค้าที่ status='completed'
        $stmtTotal = $conn->prepare("
            SELECT COALESCE(SUM(price * quantity), 0) 
            FROM order_items 
            WHERE order_id = ? AND status = 'completed'
        ");
        $stmtTotal->execute([$order_id]);
        $total = $stmtTotal->fetchColumn();

        // อัปเดตยอดเงินรวมใน orders
        $stmtUpdateTotal = $conn->prepare("
            UPDATE orders 
            SET total_price = ? 
            WHERE id = ?
        ");
        $stmtUpdateTotal->execute([$total, $order_id]);

        $conn->commit(); // commit transaction
        echo json_encode([
            "success" => true, 
            "message" => "อัปเดตสถานะและยอดเงินเรียบร้อย",
            "total_price" => $total
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            "success" => false, 
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false, 
        "message" => "ข้อมูลไม่ครบถ้วน (ต้องมี order_id, product_id, status)"
    ]);
}
?>
