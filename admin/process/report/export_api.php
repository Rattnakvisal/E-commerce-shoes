<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (!isset($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    exit('Database connection not available.');
}

/* =====================================================
   INPUT & VALIDATION
===================================================== */
$type   = $_GET['type']   ?? 'orders';
$format = strtolower($_GET['format'] ?? 'csv');

$allowedTypes   = ['orders', 'products', 'order_items'];
$allowedFormats = ['csv', 'pdf'];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit('Invalid export type.');
}

if (!in_array($format, $allowedFormats, true)) {
    http_response_code(400);
    exit('Invalid export format.');
}

$start = $_GET['start_date'] ?? null;
$end   = $_GET['end_date'] ?? null;

function valid_date(?string $date): ?string
{
    return ($date && strtotime($date)) ? $date : null;
}

$start = valid_date($start);
$end   = valid_date($end);

/* =====================================================
   HELPERS
===================================================== */
function stream_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, array_map(
            fn($h) => $row[$h] ?? '',
            $headers
        ));
    }

    fclose($out);
    exit;
}

function render_html_table(string $title, array $headers, array $rows): string
{
    ob_start(); ?>
    <h2 style="font-family:Arial"><?= htmlspecialchars($title) ?></h2>
    <table border="1" cellspacing="0" cellpadding="6"
        style="border-collapse:collapse;width:100%;font-family:Arial;font-size:12px">
        <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th style="background:#f4f4f4"><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <td><?= htmlspecialchars((string)($row[$h] ?? '')) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
    return ob_get_clean();
}

/* =====================================================
   DATASETS
===================================================== */
$params = [];
switch ($type) {

    case 'products':
        $sql = "
            SELECT
                p.product_id,
                p.NAME AS name,
                p.price,
                p.sku,
                COALESCE(c.category_name,'') AS category,
                p.created_at
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
        ";
        $headers = ['product_id', 'name', 'price', 'sku', 'category', 'created_at'];
        break;

    case 'order_items':
        $sql = "
            SELECT
                oi.order_item_id,
                oi.order_id,
                oi.product_id,
                p.NAME AS product_name,
                oi.quantity,
                oi.price
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
        ";
        $headers = ['order_item_id', 'order_id', 'product_id', 'product_name', 'quantity', 'price'];
        break;

    case 'orders':
    default:
        $sql = "
            SELECT
                o.order_id,
                o.user_id,
                COALESCE(u.NAME,'') AS customer_name,
                o.total,
                o.created_at
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
        ";
        $headers = ['order_id', 'user_id', 'customer_name', 'total', 'created_at'];
        break;
}

if ($start && $end) {
    $sql .= " WHERE DATE(created_at) BETWEEN :start AND :end";
    $params = compact('start', 'end');
}

if ($type === 'orders') {
    $sql .= " ORDER BY created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   EXPORT
===================================================== */
$filenameBase = $type . '_export_' . date('Ymd_His');

if ($format === 'csv') {
    stream_csv($filenameBase . '.csv', $headers, $rows);
}

if ($format === 'pdf') {

    $html = '<html><body>';
    $html .= render_html_table(ucfirst($type), $headers, $rows);
    $html .= '</body></html>';

    $autoload = __DIR__ . '/../../../../vendor/autoload.php';

    if (file_exists($autoload)) {
        require_once $autoload;

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
            echo $dompdf->output();
            exit;
        }
    }

    // HTML fallback
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.html"');
    echo $html;
    exit;
}

http_response_code(400);
exit('Unsupported format.');
