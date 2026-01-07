<?php
require_once __DIR__ . "/../../../config/connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   AUTH CHECK
===================================================== */
if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../auth/login.php");
    exit;
}

/* =====================================================
   FLASH MESSAGE
===================================================== */
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$errors   = [];
$editMode = false;
$editData = [];

/* =====================================================
   UPLOAD PATH CONFIG
===================================================== */
$projectRoot = realpath(__DIR__ . '/../../../');
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/\\');

$webBase = '';
if ($projectRoot && strpos($projectRoot, $docRoot) === 0) {
    $webBase = '/' . ltrim(
        str_replace('\\', '/', substr($projectRoot, strlen($docRoot))),
        '/'
    );
}

$uploadDirWeb = $webBase . '/assets/Images/slides/';
$uploadDirFs  = $projectRoot . '/assets/Images/slides/';

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

/* =====================================================
   DELETE SLIDE
===================================================== */
if (isset($_GET['delete'])) {
    $slideId = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT image_url FROM slides WHERE slides_id = ?");
    $stmt->execute([$slideId]);
    $imageUrl = $stmt->fetchColumn();

    if ($imageUrl) {
        $filePath = $uploadDirFs . basename($imageUrl);
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    $pdo->prepare("DELETE FROM slides WHERE slides_id = ?")
        ->execute([$slideId]);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => 'Slide deleted successfully'
    ];

    header("Location: slides.php");
    exit;
}

/* =====================================================
   TOGGLE ACTIVE STATUS
===================================================== */
if (isset($_GET['toggle'])) {
    $slideId = (int)$_GET['toggle'];

    $pdo->prepare("
        UPDATE slides
        SET is_active = IF(is_active = 1, 0, 1)
        WHERE slides_id = ?
    ")->execute([$slideId]);

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => 'Slide status updated'
    ];

    header("Location: slides.php");
    exit;
}

/* =====================================================
   LOAD EDIT DATA
===================================================== */
if (isset($_GET['edit'])) {
    $editMode = true;
    $slideId  = (int)$_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM slides WHERE slides_id = ?");
    $stmt->execute([$slideId]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editData) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'text' => 'Slide not found'
        ];
        header("Location: slides.php");
        exit;
    }
}

/* =====================================================
   SAVE SLIDE (ADD / UPDATE)
===================================================== */
if (isset($_POST['save_slide'])) {

    $slideId       = (int)($_POST['slide_id'] ?? 0);
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $linkUrl       = trim($_POST['link_url'] ?? '');
    $buttonText    = trim($_POST['button_text'] ?? '');
    $displayOrder  = (int)($_POST['display_order'] ?? 0);
    $isActive      = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl      = $_POST['old_image'] ?? null;

    if ($title === '') {
        $errors[] = "Title is required";
    }

    /* ---------- IMAGE / VIDEO UPLOAD ---------- */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'mp4'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = "Invalid file type";
        } else {
            $fileName = uniqid('slide_', true) . '.' . $ext;
            $filePath = $uploadDirFs . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                if ($imageUrl) {
                    $oldFile = $uploadDirFs . basename($imageUrl);
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $imageUrl = $uploadDirWeb . $fileName;
            } else {
                $errors[] = "File upload failed";
            }
        }
    }

    if ($slideId === 0 && !$imageUrl) {
        $errors[] = "Image is required";
    }

    if (!$errors) {

        if ($displayOrder <= 0) {
            $displayOrder = (int)$pdo
                ->query("SELECT IFNULL(MAX(display_order), 0) + 1 FROM slides")
                ->fetchColumn();
        }

        if ($slideId === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO slides
                (title, description, image_url, link_url, button_text, display_order, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $title,
                $description,
                $imageUrl,
                $linkUrl,
                $buttonText,
                $displayOrder,
                $isActive
            ]);
            $message = "Slide added successfully";
        } else {
            $stmt = $pdo->prepare("
                UPDATE slides SET
                    title = ?, description = ?, image_url = ?,
                    link_url = ?, button_text = ?,
                    display_order = ?, is_active = ?
                WHERE slides_id = ?
            ");
            $stmt->execute([
                $title,
                $description,
                $imageUrl,
                $linkUrl,
                $buttonText,
                $displayOrder,
                $isActive,
                $slideId
            ]);
            $message = "Slide updated successfully";
        }

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'text' => $message
        ];

        header("Location: slides.php");
        exit;
    }
}

/* =====================================================
   SLIDE STATISTICS
===================================================== */
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(is_active = 1) AS active,
        SUM(is_active = 0) AS inactive
    FROM slides
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$statusCounts = [
    'all'      => (int)$stats['total'],
    'active'   => (int)$stats['active'],
    'inactive' => (int)$stats['inactive'],
];

/* =====================================================
   FILTERED SLIDE LIST
===================================================== */
$where  = [];
$params = [];

if (!empty($_GET['q'])) {
    $search = '%' . $_GET['q'] . '%';
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = $_GET['status'] === 'active'
        ? 'is_active = 1'
        : 'is_active = 0';
}

$orderSql = 'ORDER BY display_order ASC, created_at DESC';
if (!empty($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'], true)) {
    $orderSql = 'ORDER BY display_order ' . strtoupper($_GET['order']);
}

$sql = "SELECT * FROM slides";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " $orderSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSlides = count($slides);

/* =====================================================
   PRESERVE FILTER QUERY
===================================================== */
$queryBase = $_GET;
unset($queryBase['status']);
