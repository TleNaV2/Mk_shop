<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

// สำหรับ CORS ถ้า Vue รันคนละ port
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    $conn->beginTransaction();

    // 1️⃣ ดึง order_id ทั้งหมดที่มี order_items status = 'ยกเลิก'
    $stmt = $conn->prepare("SELECT DISTINCT order_id FROM order_items WHERE status = 'ยกเลิก'");
    $stmt->execute();
    $ordersToCheck = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2️⃣ ลบรายการสินค้าที่ status = 'ยกเลิก'
    $stmt = $conn->prepare("DELETE FROM order_items WHERE status = 'ยกเลิก'");
    $stmt->execute();

    // 3️⃣ ตรวจสอบแต่ละ order หลังลบ
    $updateOrderStatus = $conn->prepare("UPDATE orders SET status = 'เสร็จแล้ว' WHERE id = ?");
    $checkItems = $conn->prepare("SELECT COUNT(*) as unfinished_items FROM order_items WHERE order_id = ? AND status != 'เสร็จแล้ว'");

    foreach ($ordersToCheck as $order_id) {
        // เช็คว่ามีรายการสินค้าที่ยังไม่เสร็จหรือไม่
        $checkItems->execute([$order_id]);
        $row = $checkItems->fetch(PDO::FETCH_ASSOC);
        if ($row['unfinished_items'] == 0) {
            // ถ้าไม่มีรายการค้างอยู่ → อัปเดต order เป็น เสร็จแล้ว
            $updateOrderStatus->execute([$order_id]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'ลบรายการยกเลิกทั้งหมดเรียบร้อยแล้ว']);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
