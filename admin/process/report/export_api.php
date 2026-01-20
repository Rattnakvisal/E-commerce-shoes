<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/conn.php';

if (empty($conn) || !$conn instanceof PDO) {
    http_response_code(500);
    exit('Database connection not available.');
}

/* =====================================================
   INPUT & VALIDATION
===================================================== */
$type   = $_GET['type']   ?? 'orders';
$format = strtolower($_GET['format'] ?? 'csv');

$allowedTypes   = ['orders', 'products', 'order_items', 'customers', 'revenue', 'analytics'];
$allowedFormats = ['csv', 'pdf', 'excel', 'json'];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    exit('Invalid export type.');
}

if (!in_array($format, $allowedFormats, true)) {
    http_response_code(400);
    exit('Invalid export format.');
}

function valid_date(?string $date): ?string
{
    return ($date && strtotime($date)) ? $date : null;
}

$start = valid_date($_GET['start_date'] ?? null);
$end   = valid_date($_GET['end_date'] ?? null);

/* =====================================================
   HELPERS
===================================================== */
function stream_csv(string $filename, array $headers, array $rows, bool $excel = false): void
{
    header(
        $excel
            ? 'Content-Type: application/vnd.ms-excel; charset=utf-8'
            : 'Content-Type: text/csv; charset=utf-8'
    );

    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');

    if ($excel) {
        // UTF-8 BOM for Excel
        fprintf($out, "%s", chr(0xEF) . chr(0xBB) . chr(0xBF));
    }

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
    <table border="1" cellpadding="6" cellspacing="0"
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
   DATASET DEFINITIONS
===================================================== */
$datasets = [

    'products' => [
        'sql' => "
            SELECT
                p.product_id,
                p.name,
                p.price,
                p.sku,
                COALESCE(c.category_name,'') AS category,
                p.created_at
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
        ",
        'headers' => ['product_id', 'name', 'price', 'sku', 'category', 'created_at'],
        'date_column' => 'p.created_at',
    ],

    'order_items' => [
        'sql' => "
            SELECT
                oi.order_item_id,
                oi.order_id,
                oi.product_id,
                p.name AS product_name,
                oi.quantity,
                oi.price
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
        ",
        'headers' => ['order_item_id', 'order_id', 'product_id', 'product_name', 'quantity', 'price'],
        'date_column' => null,
    ],

    'customers' => [
        'sql' => "
            SELECT
                u.user_id,
                u.name,
                u.email,
                u.created_at
            FROM users u
        ",
        'headers' => ['user_id', 'name', 'email', 'created_at'],
        'date_column' => 'u.created_at',
    ],

    'revenue' => [
        'sql' => "
            SELECT
                DATE(o.created_at) AS date,
                SUM(o.total) AS total_sales,
                COUNT(*) AS orders_count
            FROM orders o
        ",
        'headers' => ['date', 'total_sales', 'orders_count'],
        'date_column' => 'o.created_at',
        'group_by' => 'DATE(o.created_at)',
    ],

    'analytics' => [
        'sql' => "
            SELECT
                (SELECT COUNT(*) FROM users)  AS total_users,
                (SELECT COUNT(*) FROM orders) AS total_orders,
                (SELECT COALESCE(SUM(total),0) FROM orders) AS total_revenue
        ",
        'headers' => ['total_users', 'total_orders', 'total_revenue'],
        'date_column' => null,
    ],

    'orders' => [
        'sql' => "
            SELECT
                o.order_id,
                o.user_id,
                COALESCE(u.name,'') AS customer_name,
                o.total,
                o.created_at
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
        ",
        'headers' => ['order_id', 'user_id', 'customer_name', 'total', 'created_at'],
        'date_column' => 'o.created_at',
        'order_by' => 'o.created_at DESC',
    ],
];

/* =====================================================
   BUILD QUERY
===================================================== */
$config  = $datasets[$type];
$sql     = $config['sql'];
$headers = $config['headers'];
$params  = [];

if ($start && $end && $config['date_column']) {
    $sql .= " WHERE DATE({$config['date_column']}) BETWEEN :start AND :end";
    $params = compact('start', 'end');
}

if (!empty($config['group_by'])) {
    $sql .= " GROUP BY {$config['group_by']}";
}

if (!empty($config['order_by'])) {
    $sql .= " ORDER BY {$config['order_by']}";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================================================
   EXPORT
===================================================== */
$filenameBase = "{$type}_export_" . date('Ymd_His');

match ($format) {
    'csv'   => stream_csv("$filenameBase.csv", $headers, $rows),
    'excel' => stream_csv("$filenameBase.xls", $headers, $rows, true),

    'json' => (function () use ($rows) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    })(),

    'pdf' => (function () use ($type, $headers, $rows, $filenameBase) {
        $html = '<html><body>' .
            render_html_table(ucfirst($type), $headers, $rows) .
            '</body></html>';

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

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filenameBase . '.html"');
        echo $html;
        exit;
    })(),

    default => (function () {
        http_response_code(400);
        exit('Unsupported format.');
    })(),
};
