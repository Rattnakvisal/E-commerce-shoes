<?php
require_once __DIR__ . '/../../../config/conn.php';

/* =====================================================
   SESSION & AUTH
===================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =====================================================
   FLASH MESSAGE
===================================================== */
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

/* =====================================================
   INIT VARS
===================================================== */
$errors   = [];
$editMode = false;
$editData = [];

/* =====================================================
   UPLOAD PATH SETUP
===================================================== */
$projectRoot = realpath(__DIR__ . '/../../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');

$webBase = '';
if ($projectRoot && strpos($projectRoot, $docRoot) === 0) {
    $webBase = '/' . ltrim(str_replace('\\', '/', substr($projectRoot, strlen($docRoot))), '/');
}

$uploadDirWeb = $webBase . '/assets/Images/featured/';
$uploadDirFs  = $projectRoot . '/assets/Images/featured/';

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

/* =====================================================
   DELETE FEATURED ITEM
===================================================== */
if (!empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT image_url FROM featured_items WHERE featured_id = ?");
    $stmt->execute([$id]);

    if ($image = $stmt->fetchColumn()) {
        $file = $uploadDirFs . basename($image);
        if (is_file($file)) unlink($file);
    }

    $pdo->prepare("DELETE FROM featured_items WHERE featured_id = ?")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Featured item deleted'];
    header('Location: featured.php');
    exit;
}

/* =====================================================
   TOGGLE ACTIVE STATUS
===================================================== */
if (!empty($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $pdo->prepare("
        UPDATE featured_items 
        SET is_active = IF(is_active = 1, 0, 1)
        WHERE featured_id = ?
    ")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Featured item status updated'];
    header('Location: featured.php');
    exit;
}

/* =====================================================
   LOAD EDIT DATA
===================================================== */
if (!empty($_GET['edit'])) {
    $editMode = true;
    $id = (int)$_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM featured_items WHERE featured_id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editData) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Featured item not found'];
        header('Location: featured.php');
        exit;
    }

    // Normalize column name
    $editData['position'] = $editData['POSITION'] ?? null;
}

/* =====================================================
   SAVE FEATURED ITEM (ADD / UPDATE)
===================================================== */
if (!empty($_POST['save_featured'])) {

    $id         = (int)($_POST['featured_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $position   = (int)($_POST['position'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl   = $_POST['old_image'] ?? null;

    if ($product_id <= 0) {
        $errors[] = 'Product is required';
    }

    /* ---------- IMAGE UPLOAD ---------- */
    if (!empty($_FILES['image']['name'])) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Invalid image type';
        } else {
            $fileName = uniqid('featured_', true) . '.' . $ext;
            $target   = $uploadDirFs . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                if ($imageUrl) {
                    $old = $uploadDirFs . basename($imageUrl);
                    if (is_file($old)) unlink($old);
                }
                $imageUrl = $uploadDirWeb . $fileName;
            } else {
                $errors[] = 'Image upload failed';
            }
        }
    }

    if ($id === 0 && !$imageUrl) {
        $errors[] = 'Image is required';
    }

    /* ---------- DB SAVE ---------- */
    if (!$errors) {

        if ($position <= 0) {
            $position = (int)$pdo->query("
                SELECT IFNULL(MAX(position), 0) + 1 FROM featured_items
            ")->fetchColumn();
        }

        if ($id === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO featured_items
                (product_id, title, image_url, position, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$product_id, $title, $imageUrl, $position, $is_active]);
            $msg = 'Featured item added';
        } else {
            $stmt = $pdo->prepare("
                UPDATE featured_items
                SET product_id = ?, title = ?, image_url = ?, position = ?, is_active = ?
                WHERE featured_id = ?
            ");
            $stmt->execute([$product_id, $title, $imageUrl, $position, $is_active, $id]);
            $msg = 'Featured item updated';
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $msg];
        header('Location: featured.php');
        exit;
    }
}

/* =====================================================
   GLOBAL STATS
===================================================== */
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(is_active = 1) AS active,
        SUM(is_active = 0) AS inactive
    FROM featured_items
")->fetch(PDO::FETCH_ASSOC);

$statusCounts = [
    'all'      => (int)$stats['total'],
    'active'   => (int)$stats['active'],
    'inactive' => (int)$stats['inactive'],
];

/* =====================================================
   FILTERS & SEARCH
===================================================== */
$where  = [];
$params = [];

$search = trim($_GET['search'] ?? $_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

if ($search !== '') {
    $where[]  = '(f.title LIKE ? OR p.name LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status !== '') {
    $where[] = $status === 'active' ? 'f.is_active = 1' : 'f.is_active = 0';
}

/* =====================================================
   PAGINATION
===================================================== */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 10));
$offset = ($page - 1) * $limit;

/* =====================================================
   FETCH FEATURED LIST
===================================================== */
$baseSql = "
    FROM featured_items f
    LEFT JOIN products p ON f.product_id = p.product_id
";

$countSql = "SELECT COUNT(*) {$baseSql}" . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalFeatured = (int)$countStmt->fetchColumn();

$listSql = "
    SELECT
        f.featured_id,
        f.product_id,
        f.title,
        f.image_url,
        f.position,
        f.is_active,
        f.created_at,
        p.name AS product_name
    {$baseSql}
" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY f.position ASC, f.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($listSql);
$stmt->execute($params);
$featured = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = (int)ceil($totalFeatured / $limit);

/* =====================================================
   PRODUCTS FOR DROPDOWN
===================================================== */
$products = $pdo->query("
    SELECT product_id, name, image_url
    FROM products
    WHERE status = 'active'
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   PRESERVE QUERY PARAMS
===================================================== */
$queryBase = $_GET;
unset($queryBase['status']);
