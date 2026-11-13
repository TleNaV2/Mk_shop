<?php
include 'condb.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ดึงสินค้าพร้อมชื่อประเภท
        $stmt = $conn->prepare("
            SELECT p.*, c.category_name
            FROM products p
            LEFT JOIN categorys c ON p.category_id = c.category_id
            ORDER BY p.product_id DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึงรายการประเภทด้วย
        $catStmt = $conn->prepare("SELECT * FROM categorys ORDER BY category_id ASC");
        $catStmt->execute();
        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $products, "categories" => $categories]);
        exit;
    }

    // เพิ่มสินค้า
    if ($action === 'add') {
        $name = $_POST['product_name'];
        $desc = $_POST['description'] ?? '';
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];

        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$imageName");
        }

        $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, stock, image, category_id)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $price, $stock, $imageName, $category_id]);
        echo json_encode(["message" => "เพิ่มสินค้าเรียบร้อย"]);
        exit;
    }

    // แก้ไขสินค้า
    if ($action === 'update') {
        $id = $_POST['product_id'];
        $name = $_POST['product_name'];
        $desc = $_POST['description'] ?? '';
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];

        $sql = "UPDATE products SET product_name=?, description=?, price=?, stock=?, category_id=?";
        $params = [$name, $desc, $price, $stock, $category_id];

        if (!empty($_FILES['image']['name'])) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$imageName");
            $sql .= ", image=?";
            $params[] = $imageName;
        }

        $sql .= " WHERE product_id=?";
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["message" => "แก้ไขสินค้าสำเร็จ"]);
        exit;
    }

    // ลบสินค้า
    if ($action === 'delete') {
        $id = $_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "ลบสินค้าเรียบร้อย"]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
