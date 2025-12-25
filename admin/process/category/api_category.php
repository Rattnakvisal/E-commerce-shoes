<?php
require_once __DIR__ . '/../../../config/conn.php'; // PDO connection

// --------------------------------------------------
// Helper: JSON response for AJAX
// --------------------------------------------------
function jsonResponse(array $data)
{
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// --------------------------------------------------
// Default messages
// --------------------------------------------------
$message = '';
$error   = '';

// --------------------------------------------------
// Handle POST (Add / Update / Delete)
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? null;

        switch ($action) {

            // ---------- ADD CATEGORY ----------
            case 'add_category':
                $name = trim($_POST['category_name'] ?? '');

                if ($name === '') {
                    throw new Exception('Category name is required');
                }

                $stmt = $conn->prepare(
                    "INSERT INTO categories (category_name) VALUES (?)"
                );
                $stmt->execute([$name]);

                $message = 'Category added successfully!';
                jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'category_id' => $conn->lastInsertId()
                ]);
                break;

            // ---------- UPDATE CATEGORY ----------
            case 'update_category':
                $id   = (int)($_POST['category_id'] ?? 0);
                $name = trim($_POST['category_name'] ?? '');

                if ($id <= 0 || $name === '') {
                    throw new Exception('Invalid category data');
                }

                $stmt = $conn->prepare(
                    "UPDATE categories SET category_name = ? WHERE category_id = ?"
                );
                $stmt->execute([$name, $id]);

                $message = 'Category updated successfully!';
                jsonResponse(['success' => true, 'message' => $message]);
                break;

            // ---------- DELETE CATEGORY ----------
            case 'delete_category':
                $id = (int)($_POST['category_id'] ?? 0);

                if ($id <= 0) {
                    throw new Exception('Invalid category ID');
                }

                // Check linked products
                $check = $conn->prepare(
                    "SELECT COUNT(*) FROM products WHERE category_id = ?"
                );
                $check->execute([$id]);

                if ($check->fetchColumn() > 0) {
                    throw new Exception(
                        'Cannot delete category with products. Update products first.'
                    );
                }

                $stmt = $conn->prepare(
                    "DELETE FROM categories WHERE category_id = ?"
                );
                $stmt->execute([$id]);

                $message = 'Category deleted successfully!';
                jsonResponse(['success' => true, 'message' => $message]);
                break;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        jsonResponse(['success' => false, 'message' => $error]);
    }
}

// --------------------------------------------------
// Fetch Categories
// --------------------------------------------------
try {
    // Categories
    $categories = $conn->query(
        "SELECT * FROM categories ORDER BY created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Product counts per category
    $productCounts = [];
    $counts = $conn->query(
        "SELECT category_id, COUNT(*) total
         FROM products
         WHERE category_id IS NOT NULL
         GROUP BY category_id"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($counts as $row) {
        $productCounts[$row['category_id']] = $row['total'];
    }

    // Statistics
    $totalCategories = count($categories);
    $totalProducts   = (int)$conn->query(
        "SELECT COUNT(*) FROM products"
    )->fetchColumn();

    $uncategorizedCount = (int)$conn->query(
        "SELECT COUNT(*) FROM products WHERE category_id IS NULL"
    )->fetchColumn();
} catch (PDOException $e) {
    $error = 'Failed to load categories: ' . $e->getMessage();
    $categories = [];
    $productCounts = [];
    $totalCategories = $totalProducts = $uncategorizedCount = 0;
}
