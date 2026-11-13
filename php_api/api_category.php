<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

function respond($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->query("
            SELECT c.category_id, c.category_name, COUNT(p.product_id) AS product_count
            FROM categorys c
            LEFT JOIN products p ON p.category_id = c.category_id
            GROUP BY c.category_id, c.category_name
            ORDER BY c.category_id ASC
        ");
        respond(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'ไม่รองรับ request method นี้'], 405);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $name = trim($_POST['category_name'] ?? '');
        $length = preg_match_all('/./us', $name);

        if ($name === '' || $length === false || $length > 100) {
            respond(['success' => false, 'message' => 'กรุณาระบุชื่อหมวดหมู่ไม่เกิน 100 ตัวอักษร'], 422);
        }

        $id = null;
        if ($action === 'update') {
            $id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                respond(['success' => false, 'message' => 'รหัสหมวดหมู่ไม่ถูกต้อง'], 422);
            }

            $check = $conn->prepare('SELECT category_id FROM categorys WHERE category_id = ?');
            $check->execute([$id]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                respond(['success' => false, 'message' => 'ไม่พบหมวดหมู่ที่ต้องการแก้ไข'], 404);
            }
        }

        $duplicateSql = 'SELECT category_id FROM categorys WHERE category_name = ?';
        $duplicateParams = [$name];
        if ($id !== null) {
            $duplicateSql .= ' AND category_id <> ?';
            $duplicateParams[] = $id;
        }
        $duplicate = $conn->prepare($duplicateSql);
        $duplicate->execute($duplicateParams);
        if ($duplicate->fetch(PDO::FETCH_ASSOC)) {
            respond(['success' => false, 'message' => 'มีชื่อหมวดหมู่นี้อยู่แล้ว'], 409);
        }

        if ($action === 'add') {
            $stmt = $conn->prepare('INSERT INTO categorys (category_name) VALUES (?)');
            $stmt->execute([$name]);
            respond(['success' => true, 'message' => 'เพิ่มหมวดหมู่เรียบร้อย']);
        }

        $stmt = $conn->prepare('UPDATE categorys SET category_name = ? WHERE category_id = ?');
        $stmt->execute([$name, $id]);
        respond(['success' => true, 'message' => 'แก้ไขหมวดหมู่เรียบร้อย']);
    }

    if ($action === 'delete') {
        $id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            respond(['success' => false, 'message' => 'รหัสหมวดหมู่ไม่ถูกต้อง'], 422);
        }

        $category = $conn->prepare('SELECT category_id FROM categorys WHERE category_id = ?');
        $category->execute([$id]);
        if (!$category->fetch(PDO::FETCH_ASSOC)) {
            respond(['success' => false, 'message' => 'ไม่พบหมวดหมู่ที่ต้องการลบ'], 404);
        }

        $products = $conn->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $products->execute([$id]);
        if ((int) $products->fetchColumn() > 0) {
            respond(['success' => false, 'message' => 'ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากยังมีสินค้าใช้งานอยู่'], 409);
        }

        $stmt = $conn->prepare('DELETE FROM categorys WHERE category_id = ?');
        $stmt->execute([$id]);
        respond(['success' => true, 'message' => 'ลบหมวดหมู่เรียบร้อย']);
    }

    respond(['success' => false, 'message' => 'ไม่พบ action ที่ต้องการ'], 400);
} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล'], 500);
}
?>
