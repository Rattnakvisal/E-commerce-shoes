<?php
require_once __DIR__ . '/../../../config/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
// Allow both admin and staff roles to access this page
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: ../auth/Log/login.php');
    exit;
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$errors = [];
$editMode = false;
$editData = [];

/* ================= UPLOAD PATHS ================= */
$projectRoot = realpath(__DIR__ . '/../../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');

$webBase = '';
if ($projectRoot && strpos($projectRoot, $docRoot) === 0) {
    $webBase = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    $webBase = $webBase === '' ? '' : '/' . ltrim($webBase, '/');
}

$uploadDirWeb = $webBase . '/assets/Images/featured/';
$uploadDirFs  = $projectRoot . '/assets/Images/featured/';

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT image_url FROM featured_items WHERE featured_id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();

    if ($image) {
        $fileFs = $uploadDirFs . basename($image);
        if (file_exists($fileFs)) unlink($fileFs);
    }

    $pdo->prepare("DELETE FROM featured_items WHERE featured_id = ?")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Featured item deleted'];
    header('Location: featured.php');
    exit;
}

/* ================= TOGGLE ACTIVE ================= */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $pdo->prepare("UPDATE featured_items SET is_active = IF(is_active = 1, 0, 1) WHERE featured_id = ?")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Featured item status updated'];
    header('Location: featured.php');
    exit;
}

/* ================= EDIT LOAD ================= */
if (isset($_GET['edit'])) {
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
}

/* ================= SAVE (ADD / UPDATE) ================= */
if (isset($_POST['save_featured'])) {
    $id         = (int)($_POST['featured_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $position   = (int)($_POST['position'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl   = $_POST['old_image'] ?? null;

    if ($product_id <= 0) {
        $errors[] = 'Product is required';
    }

    /* ---------- FILE UPLOAD ---------- */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid file type';
        } else {
            $newName = uniqid('featured_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirFs . $newName)) {
                if ($imageUrl) {
                    $old = $uploadDirFs . basename($imageUrl);
                    if (file_exists($old)) unlink($old);
                }
                $imageUrl = $uploadDirWeb . $newName;
            } else {
                $errors[] = 'Upload failed';
            }
        }
    }

    if ($id === 0 && !$imageUrl) {
        $errors[] = 'Image is required';
    }

    if (!$errors) {
        if ($position <= 0) {
            $position = (int)$pdo->query("SELECT IFNULL(MAX(POSITION),0) + 1 FROM featured_items")->fetchColumn();
        }

        if ($id === 0) {
            $stmt = $pdo->prepare("INSERT INTO featured_items (product_id, title, image_url, POSITION, is_active, created_at) VALUES (?,?,?,?,?,NOW())");
            $stmt->execute([$product_id, $title, $imageUrl, $position, $is_active]);
            $msg = 'Featured item added';
        } else {
            $stmt = $pdo->prepare("UPDATE featured_items SET product_id=?, title=?, image_url=?, POSITION=?, is_active=? WHERE featured_id=?");
            $stmt->execute([$product_id, $title, $imageUrl, $position, $is_active, $id]);
            $msg = 'Featured item updated';
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $msg];
        header('Location: featured.php');
        exit;
    }
}

/* ================= GLOBAL STATS ================= */
$stmt = $pdo->query("SELECT COUNT(*) AS total, SUM(is_active = 1) AS active, SUM(is_active = 0) AS inactive FROM featured_items");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$statusCounts = [
    'all'      => (int)($stats['total'] ?? 0),
    'active'   => (int)($stats['active'] ?? 0),
    'inactive' => (int)($stats['inactive'] ?? 0),
];

/* ================= FILTERED LIST ================= */
$where = [];
$params = [];

if (!empty($_GET['q'])) {
    $q = '%' . $_GET['q'] . '%';
    $where[] = '(f.title LIKE ? OR p.name LIKE ?)';
    $params[] = $q;
    $params[] = $q;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = $_GET['status'] === 'active' ? 'f.is_active = 1' : 'f.is_active = 0';
}

$sql = "SELECT
    f.featured_id,
    f.product_id,
    f.title,
    f.image_url,
    f.POSITION AS position,
    f.is_active,
    f.created_at,
    p.name AS product_name
FROM featured_items f
LEFT JOIN products p ON f.product_id = p.product_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$featured = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalFeatured = count($featured);

/* ================= GET PRODUCTS FOR DROPDOWN ================= */
$products = $pdo->query("SELECT product_id, name, image_url FROM products WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ================= PRESERVE FILTERS ================= */
$queryBase = $_GET;
unset($queryBase['status']);

// normalize edit data keys (POSITION -> position)
if (!empty($editData) && isset($editData['POSITION'])) {
    $editData['position'] = $editData['POSITION'];
}
