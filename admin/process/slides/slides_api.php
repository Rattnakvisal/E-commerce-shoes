<?php
require_once __DIR__ . "/../../../config/connection.php";

session_start();

$errors = [];
$editMode = false;
$editData = [];

/* ------------------------------
   UPLOAD CONFIG
--------------------------------*/
$uploadDirWeb = "/uploads/slides/";
$uploadDirFs  = $_SERVER['DOCUMENT_ROOT'] . $uploadDirWeb;

if (!is_dir($uploadDirFs)) {
    mkdir($uploadDirFs, 0755, true);
}

/* ------------------------------
   DELETE WITH SweetAlert2
--------------------------------*/
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("SELECT image_url FROM slides WHERE slides_id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row && $row['image_url']) {
        $path = $_SERVER['DOCUMENT_ROOT'] . $row['image_url'];
        if (file_exists($path)) unlink($path);
    }

    $pdo->prepare("DELETE FROM slides WHERE slides_id=?")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Slide deleted successfully'];
    header("Location: slides.php");
    exit;
}

/* ------------------------------
   TOGGLE ACTIVE
--------------------------------*/
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("
        UPDATE slides 
        SET is_active = IF(is_active=1,0,1)
        WHERE slides_id=?
    ")->execute([$id]);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Slide status updated'];
    header("Location: slides.php");
    exit;
}

/* ------------------------------
   EDIT LOAD
--------------------------------*/
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = (int)$_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM slides WHERE slides_id=?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();

    if (!$editData) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Slide not found'];
        header("Location: slides.php");
        exit;
    }
}

/* ------------------------------
   SAVE (ADD / UPDATE)
--------------------------------*/
if (isset($_POST['save_slide'])) {
    $id            = (int)($_POST['slide_id'] ?? 0);
    $title         = trim($_POST['title']);
    $description   = trim($_POST['description']);
    $link_url      = trim($_POST['link_url']);
    $button_text   = trim($_POST['button_text']);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active     = (int)($_POST['is_active'] ?? 0);
    $imageUrl      = $_POST['old_image'] ?? null;
    $oldImagePath  = $imageUrl;

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }

    /* Image upload */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, and WebP images are allowed";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image size must be less than 5MB";
        } else {
            $newName = uniqid('slide_') . "." . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirFs . $newName)) {
                if (!empty($oldImagePath) && file_exists($_SERVER['DOCUMENT_ROOT'] . $oldImagePath)) {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $oldImagePath);
                }
                $imageUrl = $uploadDirWeb . $newName;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }

    if ($id === 0 && empty($imageUrl)) {
        $errors[] = "Image is required for new slide";
    }

    if (empty($errors)) {
        if ($display_order <= 0) {
            $display_order = (int)$pdo->query(
                "SELECT IFNULL(MAX(display_order),0)+1 FROM slides"
            )->fetchColumn();
        }

        if ($id === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO slides
                (title,description,image_url,link_url,button_text,display_order,is_active,created_at)
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
            $message = "Slide added successfully";
        } else {
            $stmt = $pdo->prepare("
                    UPDATE slides SET
                    title=?,description=?,image_url=?,
                    link_url=?,button_text=?,
                    display_order=?,is_active=?
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
            $message = "Slide updated successfully";
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $message];
        header("Location: slides.php");
        exit;
    }
}

/* ------------------------------
   FETCH SLIDES (with filters)
--------------------------------*/
// overall counts for stats
$totalSlidesAll = (int)$pdo->query("SELECT COUNT(*) FROM slides")->fetchColumn();
$activeSlidesAll = (int)$pdo->query("SELECT COUNT(*) FROM slides WHERE is_active = 1")->fetchColumn();
$inactiveSlidesAll = $totalSlidesAll - $activeSlidesAll;

// build WHERE and params from GET
$where = [];
$params = [];

if (!empty($_GET['q'])) {
    $q = '%' . $_GET['q'] . '%';
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = $q;
    $params[] = $q;
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    if ($_GET['status'] === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($_GET['status'] === 'inactive') {
        $where[] = 'is_active = 0';
    }
}

$orderSql = 'ORDER BY display_order';
if (isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) {
    $orderSql = 'ORDER BY display_order ' . strtoupper($_GET['order']);
} else {
    $orderSql .= ', created_at DESC';
}

$sql = 'SELECT * FROM slides';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ' . $orderSql;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$slides = $stmt->fetchAll();

// number of slides shown (filtered)
$totalSlides = count($slides);
