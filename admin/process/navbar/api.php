<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/connection.php';

// Ensure connection available
if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['ok' => false, 'msg' => 'Database connection not available']);
    exit;
}

/* ============================================================
   PDO RUN HELPER
============================================================ */
function run($pdo, $sql, $bind = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
        return ['ok' => true];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

$action = $_REQUEST['action'] ?? 'fetch_all';

/* ============================================================
   FETCH ALL
============================================================ */
if ($action === 'fetch_all') {

    // ----- FETCH PARENTS -----
    $parents = $pdo->query("
        SELECT id, title, position
        FROM navbar_parents
        ORDER BY position, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ----- FETCH GROUPS -----
    $groups = $pdo->query("
        SELECT id, parent_id, group_title, position, link_url
        FROM navbar_groups
        ORDER BY position, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ----- FETCH ITEMS -----
    $items = $pdo->query("
        SELECT id, group_id, item_title, position, link_url
        FROM navbar_items
        ORDER BY position, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'parents' => $parents,
        'groups' => $groups,
        'items' => $items
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ============================================================
   GET INPUT JSON
============================================================ */
$inp = json_decode(file_get_contents('php://input'), true) ?? $_POST;

/* ============================================================
   PARENT CRUD
============================================================ */

// ADD PARENT
if ($action === 'add_parent') {
    $title = trim($inp['title'] ?? '');
    $position = (int)($inp['position'] ?? 1);

    if ($title === '') {
        echo json_encode(['ok' => false, 'msg' => 'Title required']);
        exit;
    }

    echo json_encode(run($pdo, "
        INSERT INTO navbar_parents (title, position)
        VALUES (:title, :position)
    ", [
        ':title' => $title,
        ':position' => $position
    ]));
    exit;
}

// EDIT PARENT
if ($action === 'edit_parent') {
    $id = (int)($inp['id'] ?? 0);
    $title = trim($inp['title'] ?? '');
    $position = (int)($inp['position'] ?? 1);

    if ($id <= 0 || $title === '') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
        exit;
    }

    echo json_encode(run($pdo, "
        UPDATE navbar_parents
        SET title = :title,
            position = :position
        WHERE id = :id
    ", [
        ':title' => $title,
        ':position' => $position,
        ':id' => $id
    ]));
    exit;
}

// DELETE PARENT
if ($action === 'delete_parent') {
    $id = (int)($inp['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid id']);
        exit;
    }

    echo json_encode(run(
        $pdo,
        "DELETE FROM navbar_parents WHERE id = :id",
        [':id' => $id]
    ));
    exit;
}

/* ============================================================
   GROUP CRUD
============================================================ */

// ADD GROUP
if ($action === 'add_group') {
    $parent_raw = $inp['parent_id'] ?? null;
    $parent_id = is_numeric($parent_raw) ? (int)$parent_raw : 0;
    $title = trim($inp['group_title'] ?? '');
    $position = (int)($inp['position'] ?? 1);
    $link_url = trim($inp['link_url'] ?? '');

    if ($title === '') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
        exit;
    }

    // allow NULL parent (no parent)
    $pid = $parent_id > 0 ? $parent_id : null;

    echo json_encode(run(
        $pdo,
        "INSERT INTO navbar_groups (parent_id, group_title, position, link_url) VALUES (:pid, :title, :position, :url)",
        [
            ':pid' => $pid,
            ':title' => $title,
            ':position' => $position,
            ':url' => $link_url
        ]
    ));
    exit;
}

// EDIT GROUP
if ($action === 'edit_group') {
    $id = (int)($inp['id'] ?? 0);
    $parent_raw = $inp['parent_id'] ?? null;
    $parent_id = is_numeric($parent_raw) ? (int)$parent_raw : 0;
    $title = trim($inp['group_title'] ?? '');
    $position = (int)($inp['position'] ?? 1);
    $link_url = trim($inp['link_url'] ?? '');

    if ($id <= 0 || $title === '') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
        exit;
    }

    $pid = $parent_id > 0 ? $parent_id : null;

    echo json_encode(run(
        $pdo,
        "UPDATE navbar_groups SET parent_id = :pid, group_title = :title, position = :position, link_url = :url WHERE id = :id",
        [
            ':pid' => $pid,
            ':title' => $title,
            ':position' => $position,
            ':url' => $link_url,
            ':id' => $id
        ]
    ));
    exit;
}

// DELETE GROUP
if ($action === 'delete_group') {
    $id = (int)($inp['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid id']);
        exit;
    }

    echo json_encode(run(
        $pdo,
        "DELETE FROM navbar_groups WHERE id = :id",
        [':id' => $id]
    ));
    exit;
}

/* ============================================================
   ITEM CRUD
============================================================ */

// ADD ITEM
if ($action === 'add_item') {
    $group_id = (int)($inp['group_id'] ?? 0);
    $title = trim($inp['item_title'] ?? '');
    $url = trim($inp['link_url'] ?? '');
    $position = (int)($inp['position'] ?? 1);

    if ($group_id <= 0 || $title === '' || $url === '') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
        exit;
    }

    echo json_encode(run(
        $pdo,
        "INSERT INTO navbar_items (group_id, item_title, link_url, position) VALUES (:gid, :title, :url, :position)",
        [
            ':gid' => $group_id,
            ':title' => $title,
            ':url' => $url,
            ':position' => $position
        ]
    ));
    exit;
}

// EDIT ITEM
if ($action === 'edit_item') {
    $id = (int)($inp['id'] ?? 0);
    $title = trim($inp['item_title'] ?? '');
    $url = trim($inp['link_url'] ?? '');
    $position = (int)($inp['position'] ?? 1);

    if ($id <= 0 || $title === '' || $url === '') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
        exit;
    }

    echo json_encode(run(
        $pdo,
        "UPDATE navbar_items SET item_title = :title, link_url = :url, position = :position WHERE id = :id",
        [
            ':title' => $title,
            ':url' => $url,
            ':position' => $position,
            ':id' => $id
        ]
    ));
    exit;
}

// DELETE ITEM
if ($action === 'delete_item') {
    $id = (int)($inp['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid id']);
        exit;
    }

    echo json_encode(run(
        $pdo,
        "DELETE FROM navbar_items WHERE id = :id",
        [':id' => $id]
    ));
    exit;
}

/* ============================================================
   UNKNOWN ACTION
============================================================ */
echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
