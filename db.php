<?php

$host = "sql104.infinityfree.com";
$dbname = "if0_41866188_pharma_care";
$username = "if0_41866188";
$password = "h7ARrEfuvw5G";
$port = 3306;

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8;port=$port",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

    die("Connection Failed : " . $e->getMessage());

}


// Get sales distribution by category
$stmt = $pdo->query("
    SELECT 
        medicines.dosage_form AS category,
        SUM(sale_items.quantity * sale_items.price) AS total
    FROM sale_items
    JOIN medicines 
        ON sale_items.medicine_id = medicines.id
    JOIN sales
        ON sale_items.sale_id = sales.id
    GROUP BY medicines.dosage_form
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calculate total sales
$grandTotal = 0;

foreach ($data as $item) {
    $grandTotal += $item['total'];
}


// Colors for dashboard
$colors = [
    '#10b981',
    '#3b82f6',
    '#a855f7',
    '#fbbf24',
    '#22d3ee'
];


// Add percentage and color
foreach ($data as $i => &$item) {

    $item['percentage'] = $grandTotal > 0
        ? round(($item['total'] / $grandTotal) * 100, 1)
        : 0;

    $item['color'] = $colors[$i % count($colors)];
}

unset($item);

?>





