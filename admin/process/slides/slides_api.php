<?php
require_once __DIR__ . "/../../../config/connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/* ================= FLASH ================= */
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$errors   = [];
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

$uploadDirWeb = $webBase . '/assets/Images/slides/';
$uploadDirFs  = $projectRoot . '/assets/Images/slides/';

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT image_url FROM slides WHERE slides_id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();

    if ($image) {
        $fileFs = $uploadDirFs . basename($image);
        if (file_exists($fileFs)) unlink($fileFs);
    }

    $pdo->prepare("DELETE FROM slides WHERE slides_id = ?")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Slide deleted'];
    header("Location: slides.php");
    exit;
}

/* ================= TOGGLE ACTIVE ================= */
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];

    $pdo->prepare("
        UPDATE slides
        SET is_active = IF(is_active = 1, 0, 1)
        WHERE slides_id = ?
    ")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Slide status updated'];
    header("Location: slides.php");
    exit;
}

/* ================= EDIT LOAD ================= */
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = (int)$_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM slides WHERE slides_id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editData) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Slide not found'];
        header("Location: slides.php");
        exit;
    }
}

/* ================= SAVE (ADD / UPDATE) ================= */
if (isset($_POST['save_slide'])) {
    $id            = (int)($_POST['slide_id'] ?? 0);
    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $link_url      = trim($_POST['link_url']);
    $button_text   = trim($_POST['button_text']);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active     = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl      = $_POST['old_image'] ?? null;

    if ($title === '') {
        $errors[] = "Title is required";
    }

    /* ---------- FILE UPLOAD ---------- */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'mp4'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type";
        } else {
            $newName = uniqid('slide_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirFs . $newName)) {
                if ($imageUrl) {
                    $old = $uploadDirFs . basename($imageUrl);
                    if (file_exists($old)) unlink($old);
                }
                $imageUrl = $uploadDirWeb . $newName;
            } else {
                $errors[] = "Upload failed";
            }
        }
    }

    if ($id === 0 && !$imageUrl) {
        $errors[] = "Image is required";
    }

    if (!$errors) {
        if ($display_order <= 0) {
            $display_order = (int)$pdo
                ->query("SELECT IFNULL(MAX(display_order),0) + 1 FROM slides")
                ->fetchColumn();
        }

        if ($id === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO slides
                (title, description, image_url, link_url, button_text, display_order, is_active, created_at)
                VALUES (?,?,?,?,?,?,?,NOW())
            ");
            $stmt->execute([
                $title,
                $description,
                $imageUrl,
                $link_url,
                $button_text,
                $display_order,
                $is_active
            ]);
            $msg = "Slide added";
        } else {
            $stmt = $pdo->prepare("
                UPDATE slides SET
                    title=?, description=?, image_url=?,
                    link_url=?, button_text=?,
                    display_order=?, is_active=?
                WHERE slides_id=?
            ");
            $stmt->execute([
                $title,
                $description,
                $imageUrl,
                $link_url,
                $button_text,
                $display_order,
                $is_active,
                $id
            ]);
            $msg = "Slide updated";
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $msg];
        header("Location: slides.php");
        exit;
    }
}

/* ================= GLOBAL SLIDE STATS (FIXED) ================= */
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1) AS active,
        SUM(is_active = 0) AS inactive
    FROM slides
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/* ================= STATUS COUNTS (FOR TABS) ================= */
$statusCounts = [
    'all'      => (int)$stats['total'],
    'active'   => (int)$stats['active'],
    'inactive' => (int)$stats['inactive'],
];

/* ================= FILTERED SLIDES ================= */
$where  = [];
$params = [];

if (!empty($_GET['q'])) {
    $q = '%' . $_GET['q'] . '%';
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = $q;
    $params[] = $q;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = $_GET['status'] === 'active' ? 'is_active = 1' : 'is_active = 0';
}

$orderSql = 'ORDER BY display_order ASC, created_at DESC';
if (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) {
    $orderSql = 'ORDER BY display_order ' . strtoupper($_GET['order']);
}

$sql = "SELECT * FROM slides";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " $orderSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSlides = count($slides);

/* ================= PRESERVE FILTERS ================= */
$queryBase = $_GET;
unset($queryBase['status']);
