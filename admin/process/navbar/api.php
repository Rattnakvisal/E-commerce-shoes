<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/connection.php';

/* ============================================================
   BOOTSTRAP
============================================================ */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    respond(false, 'Database connection not available');
}

/* ============================================================
   HELPERS
============================================================ */
function respond(bool $ok, string $msg = '', array $data = []): void
{
    echo json_encode(array_merge([
        'ok'  => $ok,
        'msg' => $msg
    ], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function input(): array
{
    return json_decode(file_get_contents('php://input'), true)
        ?? $_POST
        ?? [];
}

function run(PDO $pdo, string $sql, array $params = []): void
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(true);
    } catch (PDOException $e) {
        respond(false, $e->getMessage());
    }
}

/* ============================================================
   ROUTER
============================================================ */
$action = $_REQUEST['action'] ?? 'fetch_all';
$inp    = input();

/* ============================================================
   FETCH ALL
============================================================ */
if ($action === 'fetch_all') {
    respond(true, '', [
        'parents' => $pdo->query(
            "SELECT id, title, position
             FROM navbar_parents
             ORDER BY position, id"
        )->fetchAll(PDO::FETCH_ASSOC),

        'groups' => $pdo->query(
            "SELECT id, parent_id, group_title, position, link_url
             FROM navbar_groups
             ORDER BY position, id"
        )->fetchAll(PDO::FETCH_ASSOC),

        'items' => $pdo->query(
            "SELECT id, group_id, item_title, position, link_url
             FROM navbar_items
             ORDER BY position, id"
        )->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

/* ============================================================
   PARENT CRUD
============================================================ */
if ($action === 'add_parent') {
    $title = trim($inp['title'] ?? '');
    $pos   = (int)($inp['position'] ?? 1);

    if ($title === '') respond(false, 'Title required');

    run(
        $pdo,
        "INSERT INTO navbar_parents (title, position)
         VALUES (:t, :p)",
        [':t' => $title, ':p' => $pos]
    );
}

if ($action === 'edit_parent') {
    $id    = (int)($inp['id'] ?? 0);
    $title = trim($inp['title'] ?? '');
    $pos   = (int)($inp['position'] ?? 1);

    if ($id <= 0 || $title === '') respond(false, 'Invalid data');

    run(
        $pdo,
        "UPDATE navbar_parents
         SET title = :t, position = :p
         WHERE id = :id",
        [':t' => $title, ':p' => $pos, ':id' => $id]
    );
}

if ($action === 'delete_parent') {
    $id = (int)($inp['id'] ?? 0);
    if ($id <= 0) respond(false, 'Invalid ID');

    run(
        $pdo,
        "DELETE FROM navbar_parents WHERE id = :id",
        [':id' => $id]
    );
}

/* ============================================================
   GROUP CRUD
============================================================ */
if (in_array($action, ['add_group', 'edit_group'], true)) {

    $id     = (int)($inp['id'] ?? 0);
    $parent = is_numeric($inp['parent_id'] ?? null)
        ? (int)$inp['parent_id']
        : null;

    $title = trim($inp['group_title'] ?? '');
    $pos   = (int)($inp['position'] ?? 1);
    $url   = trim($inp['link_url'] ?? '');

    if ($title === '') respond(false, 'Group title required');

    if ($action === 'add_group') {
        run(
            $pdo,
            "INSERT INTO navbar_groups
             (parent_id, group_title, position, link_url)
             VALUES (:pid, :t, :p, :u)",
            [
                ':pid' => $parent ?: null,
                ':t'   => $title,
                ':p'   => $pos,
                ':u'   => $url
            ]
        );
    }

    if ($action === 'edit_group') {
        if ($id <= 0) respond(false, 'Invalid ID');

        run(
            $pdo,
            "UPDATE navbar_groups
             SET parent_id = :pid,
                 group_title = :t,
                 position = :p,
                 link_url = :u
             WHERE id = :id",
            [
                ':pid' => $parent ?: null,
                ':t'   => $title,
                ':p'   => $pos,
                ':u'   => $url,
                ':id'  => $id
            ]
        );
    }
}

if ($action === 'delete_group') {
    $id = (int)($inp['id'] ?? 0);
    if ($id <= 0) respond(false, 'Invalid ID');

    run(
        $pdo,
        "DELETE FROM navbar_groups WHERE id = :id",
        [':id' => $id]
    );
}

/* ============================================================
   ITEM CRUD
============================================================ */
if (in_array($action, ['add_item', 'edit_item'], true)) {

    $id    = (int)($inp['id'] ?? 0);
    $gid   = (int)($inp['group_id'] ?? 0);
    $title = trim($inp['item_title'] ?? '');
    $url   = trim($inp['link_url'] ?? '');
    $pos   = (int)($inp['position'] ?? 1);

    if ($title === '' || $url === '') respond(false, 'Invalid data');

    if ($action === 'add_item') {
        if ($gid <= 0) respond(false, 'Invalid group');

        run(
            $pdo,
            "INSERT INTO navbar_items
             (group_id, item_title, link_url, position)
             VALUES (:g, :t, :u, :p)",
            [
                ':g' => $gid,
                ':t' => $title,
                ':u' => $url,
                ':p' => $pos
            ]
        );
    }

    if ($action === 'edit_item') {
        if ($id <= 0) respond(false, 'Invalid ID');

        run(
            $pdo,
            "UPDATE navbar_items
             SET item_title = :t,
                 link_url = :u,
                 position = :p
             WHERE id = :id",
            [
                ':t'  => $title,
                ':u'  => $url,
                ':p'  => $pos,
                ':id' => $id
            ]
        );
    }
}

if ($action === 'delete_item') {
    $id = (int)($inp['id'] ?? 0);
    if ($id <= 0) respond(false, 'Invalid ID');

    run(
        $pdo,
        "DELETE FROM navbar_items WHERE id = :id",
        [':id' => $id]
    );
}

/* ============================================================
   FALLBACK
============================================================ */
respond(false, 'Unknown action');
