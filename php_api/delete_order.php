<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? null;
$product_id = $data['product_id'] ?? null;

if (!$order_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1️⃣ ลบรายการสินค้าที่ยกเลิก
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->execute([$order_id, $product_id]);

    // 2️⃣ ตรวจสอบรายการสินค้าที่เหลือใน order
    $stmt = $conn->prepare("SELECT COUNT(*) as remaining_items FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $remaining = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($remaining['remaining_items'] == 0) {
        // ถ้าไม่มีรายการสินค้าเหลือ ให้ปรับสถานะ order เป็น 'เสร็จแล้ว'
        $stmt = $conn->prepare("UPDATE orders SET status = 'เสร็จแล้ว' WHERE id = ?");
        $stmt->execute([$order_id]);
    } else {
        // ถ้ามีรายการเหลือ ตรวจสอบว่าสถานะสินค้าทั้งหมดเป็น 'เสร็จแล้ว' หรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) as unfinished_items FROM order_items WHERE order_id = ? AND status != 'เสร็จแล้ว'");
        $stmt->execute([$order_id]);
        $unfinished = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($unfinished['unfinished_items'] == 0) {
            // ถ้าสินค้าทั้งหมดเสร็จแล้ว อัปเดต status ของ order เป็น 'เสร็จแล้ว'
            $stmt = $conn->prepare("UPDATE orders SET status = 'เสร็จแล้ว' WHERE id = ?");
            $stmt->execute([$order_id]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
