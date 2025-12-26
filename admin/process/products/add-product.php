<?php
require_once __DIR__ . '/../../../config/conn.php';

header('Content-Type: application/json');

// Normalize connection
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

/* ============================
   ACTION ROUTER
============================ */
$action = $_REQUEST['action'] ?? 'create';

/* ============================
   ENSURE image_url COLUMN
============================ */
try {
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $col = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = :db
          AND TABLE_NAME = 'products'
          AND COLUMN_NAME = 'image_url'
    ");
    $col->execute([':db' => $dbName]);
    if (!$col->fetchColumn()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) NULL AFTER sku");
    }
} catch (Exception $e) {
}

/* ============================
   CREATE PRODUCT
============================ */
if ($action === 'create') {
    try {
        $NAME = trim($_POST['NAME'] ?? '');
        $DESCRIPTION = trim($_POST['DESCRIPTION'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $price = $_POST['price'] ?? null;
        $cost = $_POST['cost'] ?? null;
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $STATUS = $_POST['STATUS'] ?? 'active';

        if ($NAME === '' || $price === null || $price === '') {
            echo json_encode(['success' => false, 'message' => 'Name and price are required']);
            exit;
        }

        $image_url = uploadImage($_FILES['image'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO products
            (NAME, image_url, DESCRIPTION, price, cost, stock, category_id, STATUS)
            VALUES
            (:NAME, :image_url, :DESCRIPTION, :price, :cost, :stock, :category_id, :STATUS)
        ");

        bindProduct($stmt, compact(
            'NAME',
            'image_url',
            'DESCRIPTION',
            'price',
            'cost',
            'stock',
            'category_id',
            'STATUS'
        ));

        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ============================
   GET ONE PRODUCT (EDIT)
============================ */
if ($action === 'get_one') {
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");
    $stmt->execute([':id' => $id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => (bool)$product,
        'data' => $product
    ]);
    exit;
}

/* ============================
   UPDATE PRODUCT
============================ */
if ($action === 'update') {
    try {
        $id = (int)$_POST['product_id'];
        $NAME = trim($_POST['NAME']);
        $DESCRIPTION = trim($_POST['DESCRIPTION'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $price = $_POST['price'];
        $cost = $_POST['cost'] ?? null;
        $stock = (int)$_POST['stock'];
        $STATUS = $_POST['STATUS'];

        // Get old image
        $old = $pdo->prepare("SELECT image_url FROM products WHERE product_id = :id");
        $old->execute([':id' => $id]);
        $oldImage = $old->fetchColumn();

        $image_url = $oldImage;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_url = uploadImage($_FILES['image']);
        }

        $stmt = $pdo->prepare("
            UPDATE products SET
                NAME=:NAME,
                image_url=:image_url,
                DESCRIPTION=:DESCRIPTION,
                price=:price,
                cost=:cost,
                stock=:stock,
                category_id=:category_id,
                STATUS=:STATUS
            WHERE product_id=:id
        ");

        bindProduct($stmt, compact(
            'NAME',
            'image_url',
            'DESCRIPTION',
            'price',
            'cost',
            'stock',
            'category_id',
            'STATUS'
        ));
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ============================
   DELETE PRODUCT
============================ */
if ($action === 'delete') {
    $id = (int)$_POST['id'];

    // Remove image file
    $img = $pdo->prepare("SELECT image_url FROM products WHERE product_id = :id");
    $img->execute([':id' => $id]);
    $image = $img->fetchColumn();

    if ($image && file_exists($_SERVER['DOCUMENT_ROOT'] . $image)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $image);
    }

    $pdo->prepare("DELETE FROM products WHERE product_id = :id")
        ->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Product deleted']);
    exit;
}

/* ============================
   HELPERS
============================ */
function uploadImage($file)
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid image type');
    }

    $name = 'prod_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $dir = __DIR__ . '/../../../assets/Images/products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    move_uploaded_file($file['tmp_name'], $dir . $name);
    return '/E-commerce-shoes/assets/Images/products/' . $name;
}

function bindProduct(PDOStatement $stmt, array $data)
{
    $stmt->bindValue(':NAME', $data['NAME']);
    $stmt->bindValue(':image_url', $data['image_url']);
    $stmt->bindValue(':DESCRIPTION', $data['DESCRIPTION'] ?: null);
    $stmt->bindValue(':price', $data['price']);
    $stmt->bindValue(':cost', $data['cost'] ?: null);
    $stmt->bindValue(':stock', (int)$data['stock'], PDO::PARAM_INT);
    $stmt->bindValue(
        ':category_id',
        $data['category_id'] === '' || $data['category_id'] === null
            ? null
            : (int)$data['category_id'],
        $data['category_id'] === '' || $data['category_id'] === null
            ? PDO::PARAM_NULL
            : PDO::PARAM_INT
    );
    $stmt->bindValue(':STATUS', $data['STATUS']);
}
