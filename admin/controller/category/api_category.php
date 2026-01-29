<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

$pdo = $pdo ?? ($conn ?? null);
if (!$pdo instanceof PDO) {
    die('Database connection missing.');
}

/* =====================================================
   JSON helper
===================================================== */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(bool $ok, string $message, array $extra = []): void
{
    if (!isAjax()) return;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/* =====================================================
   Defaults
===================================================== */
$message = '';
$error   = '';

/* =====================================================
   POST: Add / Update / Delete
===================================================== */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {

            /* ---------- ADD ---------- */
            case 'add_category': {
                    $name = trim((string)($_POST['category_name'] ?? ''));

                    if ($name === '') {
                        throw new RuntimeException('Category name is required.');
                    }

                    // Optional: prevent duplicate names
                    $dup = $pdo->prepare("SELECT 1 FROM categories WHERE category_name = ? LIMIT 1");
                    $dup->execute([$name]);
                    if ($dup->fetchColumn()) {
                        throw new RuntimeException('Category name already exists.');
                    }

                    $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                    $stmt->execute([$name]);

                    $message = 'Category added successfully!';
                    jsonResponse(true, $message, ['category_id' => (int)$pdo->lastInsertId()]);
                    break;
                }

                /* ---------- UPDATE ---------- */
            case 'update_category': {
                    $id   = (int)($_POST['category_id'] ?? 0);
                    $name = trim((string)($_POST['category_name'] ?? ''));

                    if ($id <= 0 || $name === '') {
                        throw new RuntimeException('Invalid category data.');
                    }

                    // Optional: prevent duplicate names (excluding current)
                    $dup = $pdo->prepare("SELECT 1 FROM categories WHERE category_name = ? AND category_id <> ? LIMIT 1");
                    $dup->execute([$name, $id]);
                    if ($dup->fetchColumn()) {
                        throw new RuntimeException('Category name already exists.');
                    }

                    $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
                    $stmt->execute([$name, $id]);

                    $message = 'Category updated successfully!';
                    jsonResponse(true, $message);
                    break;
                }

                /* ---------- DELETE ---------- */
            case 'delete_category': {
                    $id = (int)($_POST['category_id'] ?? 0);

                    if ($id <= 0) {
                        throw new RuntimeException('Invalid category ID.');
                    }

                    // Check linked products
                    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                    $check->execute([$id]);
                    if ((int)$check->fetchColumn() > 0) {
                        throw new RuntimeException('Cannot delete category with products. Update products first.');
                    }

                    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->execute([$id]);

                    $message = 'Category deleted successfully!';
                    jsonResponse(true, $message);
                    break;
                }

            default:
                // If someone posts without action
                throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        jsonResponse(false, $error);
    }
}

/* =====================================================
   FETCH DATA (Categories + Stats)
===================================================== */
$categories = [];
$productCounts = [];
$totalCategories = 0;
$totalProducts = 0;
$uncategorizedCount = 0;

try {
    // Categories
    $categories = $pdo->query("
        SELECT category_id, category_name, created_at
        FROM categories
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Product counts per category (fast map)
    $counts = $pdo->query("
        SELECT category_id, COUNT(*) AS total
        FROM products
        WHERE category_id IS NOT NULL
        GROUP BY category_id
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($counts as $row) {
        $productCounts[(int)$row['category_id']] = (int)$row['total'];
    }

    // Stats
    $totalCategories = count($categories);

    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    $uncategorizedCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE category_id IS NULL
    ")->fetchColumn();
} catch (Throwable $e) {
    $error = 'Failed to load categories.';
    error_log('[api_category] ' . $e->getMessage());
}
