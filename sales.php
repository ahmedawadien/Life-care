<?php
include '../config/db.php';

$css_2 = '../assets/css/sales.css';
// 1. Fetch Today's Total Sales and Total Transactions from the 'sales' table
$stmt = $pdo->query("
    SELECT 
        COUNT(id) AS transactions,
        SUM(total) AS total_sales
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
");
$today = $stmt->fetch(PDO::FETCH_ASSOC);

$totalSales = $today['total_sales'] ?? 0;
$totalTransactions = $today['transactions'] ?? 0;
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

$stmt_sales = $pdo->prepare("
    SELECT
        s.id,
        s.sale_date,
        COALESCE(c.full_name,'عميل نقدي') AS customer_name,
        s.total,
        s.payment_method
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.sale_date DESC
    LIMIT $limit OFFSET $offset
");

$stmt_sales->execute();
$recent_sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);
// 2. Fetch Total Items Sold Today from 'sale_items' referencing today's sales
$stmt = $pdo->query("
    SELECT SUM(quantity) AS items
    FROM sale_items
    WHERE sale_id IN (
        SELECT id FROM sales WHERE DATE(sale_date) = CURDATE()
    )
");
$items = $stmt->fetch(PDO::FETCH_ASSOC);
$totalItems = $items['items'] ?? 0;

// 3. Calculate the Average Sale Value
$averageSale = 0;
if ($totalTransactions > 0) {
    $averageSale = $totalSales / $totalTransactions;
}

// 4. Fetch recent sales (with Left Join to connect Customers and avoid NULL warnings)
$stmt_sales = $pdo->query("
    SELECT 
        s.id,
        s.id AS invoice_no, 
        s.sale_date, 
        COALESCE(c.full_name, 'عميل نقدي') AS customer_name, 
        s.total, 
        s.payment_method
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.sale_date DESC
    LIMIT 10
");
$recent_sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);

// Fetch chart data last 7 days
$stmt = $pdo->query("
    SELECT
        DATE(sale_date) AS day,
        SUM(total) AS total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY day ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$values = [];
foreach ($rows as $r) {
    $labels[] = $r['day'];
    $values[] = (float)$r['total'];
}

// Fetch payment methods statistics
$stmt = $pdo->query("
    SELECT
        payment_method,
        SUM(total) AS total
    FROM sales
    GROUP BY payment_method
");
$paymentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$paymentLabels = [];
$paymentValues = [];
foreach ($paymentData as $row) {
    $paymentLabels[] = $row['payment_method'] ?? 'Unspecified';
    $paymentValues[] = (float)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCare Pharmacy - Sales Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/magic.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
       
        
    </style>
    <link rel="stylesheet" href="<?php echo $css_2; ?>">
</head>
<body class="bg-[#F8FAFC] text-gray-800 min-h-screen flex flex-col lg:flex-row overflow-x-hidden">

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 min-h-screen flex flex-col w-full overflow-x-hidden">     
        
        <!-- HEADER -->
        <header class="bg-white border-b border-gray-100 px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <!-- Left -->
            <div class="flex items-center gap-4">
                <!-- Back Button -->
                <a  style="animation-duration: 3s;"  href="http://life-care.lovestoblog.com/index.php" class="foolishIn magictime w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors shadow-sm" title="Back to Dashboard">
                    <img style="width: 20px;" src="../assets/css/img/back.png" alt="Back">
                </a>
                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Sales Dashboard</h2>
                    <p class="text-xs text-gray-500">Monitor pharmacy sales and transactions</p>
                </div>
            </div>

            <!-- Right -->
            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto border-t sm:border-t-0 pt-3 sm:pt-0 border-gray-100">
                <!-- Search -->
                <div class="hidden md:flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 w-64 lg:w-72">
                    <img src="../assets/css/img/search (3).png" style="width: 20px;">
                    <input type="text" placeholder="Search..." class="ml-2 bg-transparent outline-none text-sm w-full">
                </div>

                <div class="flex items-center gap-3 ml-auto sm:ml-0">
                    <!-- Notification -->
                    <button class="relative p-2 rounded-xl border border-gray-200 hover:bg-gray-100">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
                    </button>

                    <!-- User -->
                    <div class="flex items-center gap-3">
                        <img src="https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&q=80&w=100" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-cover">
                        <div class="hidden sm:block">
                            <p class="font-semibold text-sm leading-tight">Pharmacist</p>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- DASHBOARD CONTAINER -->
        <div class="flex-1 p-4 sm:p-6 space-y-6 w-full max-w-[1400px] mx-auto">
            
            <!-- TITLE BAR -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Sales</h2>
                    <p class="text-xs text-gray-500 font-medium mt-1">Dashboard &nbsp;<span class="text-gray-300">/</span>&nbsp; <span class="text-[#10B981]">Sales</span></p>
                </div>
                <div class="flex items-center gap-3">
                    <button style="color: black !important;" id="main-export-btn" class="ya flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl bg-white text-xs sm:text-sm font-medium text-gray-600 hover:bg-gray-50 transition shadow-sm cursor-pointer">
                        <i data-lucide="download" class="w-4 h-4"></i> Export
                    </button>
                    <button  id="main-new-sale-btn" class=" ya flex items-center gap-2 px-4 py-2.5 bg-[#10B981] hover:bg-emerald-600 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-sm shadow-emerald-100 cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i> New Sale
                    </button>
                </div>
            </div>

            <!-- METRIC CARDS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Sales -->
                <div data-animate="slide-in-fwd-center" data-duration="3s" class="bg-white sm:w-full w-[70%] mx-auto p-5 border border-gray-100 rounded-2xl flex items-start justify-between transition-all hover:shadow-md">
                    <div class="space-y-1">
                        <p class="text-xs text-gray-400 font-medium">Total Sales (Today)</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight"> <?php echo number_format($totalSales,2); ?> <span class="text-xs font-normal text-gray-400">EGP</span></h3>
                        <p class="text-[11px] text-[#10B981] font-semibold flex items-center gap-1 pt-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i> 12.5% <span class="text-gray-400 font-normal">vs yesterday</span>
                        </p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl text-[#10B981] flex-shrink-0">
                        <i data-lucide="banknote" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                </div>
                <!-- Total Transactions -->
                <div data-animate="slide-in-fwd-center" data-duration="3s" data-delay="1s" class="bg-white w-[70%] sm:w-full mx-auto p-5 border border-gray-100 rounded-2xl flex items-start justify-between transition-all hover:shadow-md">
                    <div class="space-y-1">
                        <p class="text-xs text-gray-400 font-medium">Total Transactions</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight"><?= number_format($totalTransactions) ?></h3>
                        <p class="text-[11px] text-[#10B981] font-semibold flex items-center gap-1 pt-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i> 6.3% <span class="text-gray-400 font-normal">vs yesterday</span>
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl text-blue-500 flex-shrink-0">
                        <i data-lucide="shopping-bag" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                </div>
                <!-- Average Sale Value -->
                <div data-animate="slide-in-fwd-center" data-duration="3s" data-delay="2s" class="bg-white w-[70%] sm:w-full mx-auto p-5 border border-gray-100 rounded-2xl flex items-start justify-between transition-all hover:shadow-md">
                    <div class="space-y-1">
                        <p class="text-xs text-gray-400 font-medium">Average Sale Value</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight"><?php echo number_format($averageSale,2); ?> <span class="text-xs font-normal text-gray-400">EGP</span></h3>
                        <p class="text-[11px] text-[#10B981] font-semibold flex items-center gap-1 pt-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i> 8.1% <span class="text-gray-400 font-normal">vs yesterday</span>
                        </p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl text-purple-500 flex-shrink-0">
                        <i data-lucide="coins" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                </div>
                <!-- Total Items Sold -->
                <div data-animate="slide-in-fwd-center" data-duration="3s" data-delay="3s" class="bg-white mx-auto w-[70%] sm:w-full p-5 border border-gray-100 rounded-2xl flex items-start justify-between transition-all hover:shadow-md">
                    <div class="space-y-1">
                        <p class="text-xs text-gray-400 font-medium">Total Items Sold</p>
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight"><?php echo number_format($totalItems); ?></h3>
                        <p class="text-[11px] text-[#10B981] font-semibold flex items-center gap-1 pt-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i> 9.4% <span class="text-gray-400 font-normal">vs yesterday</span>
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-xl text-amber-500 flex-shrink-0">
                        <i data-lucide="tag" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                    </div>
                </div>
            </div>

            <!-- LAYOUT SPLIT -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- LEFT & CENTER REGION: Table & Filters -->
                <div class="lg:col-span-2 space-y-6 w-full">
                    
                    <!-- INVOICES TABLE CONTAINER -->
                    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                        <!-- Filters Header Row -->
                        <div class="p-4 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 items-center">
                            <!-- Date Input -->
                            <div class="relative border border-gray-200 rounded-xl px-3 py-2 bg-white">
                                <input type="date" id="date-filter" class="bg-transparent outline-none w-full text-xs cursor-pointer text-gray-600 font-medium">
                            </div>

                            <!-- Cashier Select -->
                            <div class="relative border border-gray-200 rounded-xl px-3 py-2 bg-white">
                                <select id="cashier-filter" class="text-xs font-medium text-gray-600 bg-transparent outline-none w-full appearance-none pr-4 cursor-pointer">
                                    <option value="">All Cashiers</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>

                            <!-- Payments Select -->
                            <div class="relative border border-gray-200 rounded-xl px-3 py-2 bg-white">
                                <select id="table-payment-filter" class="text-xs font-medium text-gray-600 bg-transparent outline-none w-full appearance-none pr-4 cursor-pointer">
                                    <option value="">All Payment Methods</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Instapay">Instapay</option>
                                    <option value="Vodafone Cash">Vodafone Cash</option>
                                </select>
                            </div>
                            
                            <!-- Search Input Block -->
                            <div class="flex items-center gap-2">
                                <div class="relative border border-gray-200 rounded-xl px-3 py-2.5 flex items-center gap-2 flex-1 bg-white">
                                    <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400"></i>
                                    <input type="text" placeholder="Search invoice..." class="border-none outline-none text-xs w-full text-gray-600 placeholder-gray-400">
                                </div>
                                <button class="p-2 border border-gray-200 rounded-xl bg-white hover:bg-gray-50 text-gray-500 cursor-pointer">
                                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Data Table Content Module -->
                        <div class="overflow-x-auto w-full">
                            <table class="w-full text-left border-collapse whitespace-nowrap min-w-[700px]">
                                <thead>
                                    <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                        <th class="py-3 px-4">Invoice No.</th>
                                        <th class="py-3 px-4">Date & Time</th>
                                        <th class="py-3 px-4">Customer</th>
                                        <th class="py-3 px-4 text-center">Items</th>
                                        <th class="py-3 px-4 text-right">Total Amount</th>
                                        <th class="py-3 px-4 text-center">Payment</th>
                                        <th class="py-3 px-4">Cashier</th>
                                        <th class="py-3 px-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs font-medium text-gray-600">
                                    <?php if (!empty($recent_sales)): ?>
                                        <?php foreach ($recent_sales as $sale): ?>
                                            <tr class="hover:bg-gray-50/50 transition sales-row" data-date="<?php echo date('Y-m-d', strtotime($sale['sale_date'])); ?>">
                                                <td class="py-3.5 px-4 text-[#10B981] font-semibold">
                                                    INV-2026-<?php echo $sale['invoice_no']; ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-gray-500">
                                                    <?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-gray-900 font-semibold">
                                                    <?php echo htmlspecialchars($sale['customer_name'] ?? 'عميل نقدي'); ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-center">-</td>
                                                <td class="py-3.5 px-4 text-right text-gray-900 font-semibold">
                                                    EGP <?php echo number_format($sale['total'] ?? 0, 2); ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-center">
                                                    <?php 
                                                    $badge_colors = [
                                                        'Cash' => 'bg-emerald-50 text-emerald-600',
                                                        'Card' => 'bg-blue-50 text-blue-600',
                                                        'Instapay' => 'bg-purple-50 text-purple-600',
                                                        'Vodafone Cash' => 'bg-red-50 text-red-600'
                                                    ];
                                                    $method = $sale['payment_method'] ?? 'Cash';
                                                    $color = $badge_colors[$method] ?? 'bg-gray-50 text-gray-600';
                                                    ?>
                                                    <span class="px-2.5 py-1 rounded-full font-bold text-[10px] <?php echo $color; ?>">
                                                        <?php echo htmlspecialchars($method); ?>
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4">Admin</td>
                                                <td class="py-3.5 px-4 text-center">
                                                    <button class="p-1.5 border border-gray-200 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-50 cursor-pointer">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5 mx-auto"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- DYNAMIC JAVASCRIPT FILTER EMPTY STATE -->
                                    <tr id="no-results-row" class="hidden">
                                        <td colspan="8" class="py-12 px-4">
                                            <div class="flex flex-col items-center justify-center text-center max-w-sm mx-auto">
                                                <div class="p-4 bg-gray-50 rounded-full text-gray-400 mb-4 ring-8 ring-gray-50/50">
                                                    <i data-lucide="search" class="w-6 h-6"></i>
                                                </div>
                                                <h4 class="text-sm font-bold text-gray-900">No matching transactions</h4>
                                                <p class="text-xs text-gray-500 mt-1 mb-4">We couldn't find any sales matching your current filter criteria.</p>
                                                <button onclick="resetFilters()" class="px-3.5 py-1.5 border border-gray-200 rounded-xl bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 shadow-sm transition cursor-pointer">
                                                    Clear Filters
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- DATABASE STATIC EMPTY STATE -->
                                    <?php if (empty($recent_sales)): ?>
                                        <tr id="empty-db-row">
                                            <td colspan="8" class="py-16 px-4">
                                                <div class="flex flex-col items-center justify-center text-center max-w-sm mx-auto">
                                                    <div class="p-4 bg-emerald-50 rounded-full text-[#10B981] mb-4 ring-8 ring-emerald-50/50">
                                                        <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                                                    </div>
                                                    <h4 class="text-sm font-bold text-gray-900">No sales recorded yet</h4>
                                                    <p class="text-xs text-gray-500 mt-1 mb-5">Start scanning items in your POS window to log your first transaction.</p>
                                                    <button onclick="window.location.href='new_sale.php'" class="flex items-center gap-2 px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition shadow-sm shadow-emerald-100 cursor-pointer">
                                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Create New Sale
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>  
                            </table>
                        </div>

                        <!-- Table Pagination Module -->
                        <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 font-medium">
                            <span>Showing 1 to 5 entries</span>
                            <div class="flex items-center gap-1.5">
                                <button class="p-1.5 border border-gray-200 rounded-lg bg-white text-gray-400 hover:bg-gray-50 cursor-pointer"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i></button>
                                <button class="w-7 h-7 rounded-lg bg-[#10B981] text-white font-bold text-center cursor-pointer"><a href="?page=1">1</a></button>
                                <button class="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-600 text-center hover:bg-gray-50 cursor-pointer">
<a href="?page=2">2</a></button>
                                <button class="p-1.5 border border-gray-200 rounded-lg bg-white text-gray-400 hover:bg-gray-50 cursor-pointer"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- SALES OVERVIEW CHART -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-900">Sales Overview</h3>
                            <div class="relative border border-gray-200 rounded-xl px-2.5 py-1.5 flex items-center gap-2 text-xs font-semibold text-gray-600 bg-white cursor-pointer">
                                <span>This Week</span>
                                <i data-lucide="chevron-down" class="w-3 h-3 text-gray-400"></i>
                            </div>
                        </div>
                        <div class="h-64 w-full relative">
                            <canvas id="salesOverviewChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- RIGHT REGION: Aggregated Panels & Categories -->
                <div class="space-y-6 w-full">
                    
                    <!-- SALES SUMMARY CARD -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-900">Sales Summary</h3>
                            <div class="relative border border-gray-200 rounded-xl px-2.5 py-1.5 flex items-center gap-2 text-xs font-semibold text-gray-600 bg-white cursor-pointer">
                                <span>This Week</span>
                                <i data-lucide="chevron-down" class="w-3 h-3 text-gray-400"></i>
                            </div>
                        </div>
                        <div class="space-y-3 text-xs font-medium text-gray-500">
                            <div class="flex justify-between"><span>Total Sales</span><span class="text-[#10B981] font-bold">EGP <?php echo number_format($totalSales, 2); ?></span></div>
                            <div class="flex justify-between"><span>Total Transactions</span><span class="text-gray-900 font-semibold"><?= number_format($totalTransactions) ?></span></div>
                            <div class="flex justify-between"><span>Total Items Sold</span><span class="text-gray-900 font-semibold"><?= number_format($totalItems) ?></span></div>
                            <div class="flex justify-between"><span>Discounts Given</span><span class="text-red-500 font-semibold">EGP 0.00</span></div>
                            <hr class="border-gray-100 my-2">
                            <div class="flex justify-between items-center text-sm pt-1">
                                <span class="text-gray-900 font-bold">Net Sales</span>
                                <span class="text-[#10B981] font-extrabold text-base">EGP <?php echo number_format($totalSales, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT METHODS DONUT -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Payment Methods</h3>
                        <div class="flex flex-col sm:flex-row lg:flex-col items-center justify-center gap-6">
                            <div class="relative h-28 w-28 flex-shrink-0">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                            <div class="space-y-2.5 w-full text-xs font-semibold text-gray-500">
                                <div class="relative border border-gray-200 rounded-xl px-2 py-1.5 bg-white">
                                    <select id="payment-filter" class="text-xs font-medium text-gray-600 bg-transparent outline-none w-full appearance-none pr-4 cursor-pointer">
                                        <option value="">All Payment Methods</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="Instapay">Instapay</option>
                                        <option value="Vodafone Cash">Vodafone Cash</option>
                                    </select>
                                </div>
                                <hr class="border-gray-100 my-1">
                                <div class="flex justify-between text-xs font-bold text-gray-900 p-1 bg-slate-50 rounded-xl border border-slate-100">
                                    <span>Total</span><span>EGP <?php echo number_format($totalSales, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SALES BY CATEGORY -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-900">Sales by Category</h3>
                            <div class="relative border border-gray-200 rounded-xl px-2.5 py-1.5 flex items-center gap-2 text-xs font-semibold text-gray-600 bg-white cursor-pointer">
                                <span>This Week</span>
                                <i data-lucide="chevron-down" class="w-3 h-3 text-gray-400"></i>
                            </div>
                        </div>
                        <div class="space-y-3 text-xs font-medium">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#10B981]"></span><span class="text-gray-700">Pain Relief</span></div>
                                <span class="font-bold text-gray-900">35%</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#3B82F6]"></span><span class="text-gray-700">Antibiotics</span></div>
                                <span class="font-bold text-gray-900">25%</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#A855F7]"></span><span class="text-gray-700">Vitamins</span></div>
                                <span class="font-bold text-gray-900">15%</span>
                            </div>
                        </div>
                    </div>

                    <!-- TOP SELLING MEDICINES -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-gray-900">Top Selling Medicines</h3>
                            <a href="#" class="text-xs font-semibold text-[#10B981] hover:underline">View All</a>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs font-medium">
                                <div class="flex items-center gap-3">
                                    <span class="w-5 h-5 flex items-center justify-center rounded bg-emerald-50 text-[#10B981] font-bold text-[10px]">1</span>
                                    <span class="text-gray-900 font-semibold">Paracetamol 650mg</span>
                                </div>
                                <span class="text-gray-500 font-bold">156</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-medium">
                                <div class="flex items-center gap-3">
                                    <span class="w-5 h-5 flex items-center justify-center rounded bg-gray-50 text-gray-400 font-bold text-[10px]">2</span>
                                    <span class="text-gray-900 font-semibold">Amoxicillin 500mg</span>
                                </div>
                                <span class="text-gray-500 font-bold">128</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </main>

    <!-- INLINE GRAPH RENDERING & LOGIC SCRIPTS -->
    <script>
        // 1. Initialize Lucide Vector Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Secure Data Bridges from PHP
        const paymentLabels = <?= json_encode($paymentLabels); ?>;
        const paymentValues = <?= json_encode($paymentValues); ?>;
        const salesOverviewLabels = <?= json_encode($labels); ?>;
        const salesOverviewValues = <?= json_encode($values); ?>;

        // 2. Sales Overview - Curve Line Chart
        const salesCtx = document.getElementById('salesOverviewChart').getContext('2d');
        const salesGradient = salesCtx.createLinearGradient(0, 0, 0, 240);
        salesGradient.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
        salesGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesOverviewLabels,
                datasets: [{
                    label: 'Sales',
                    data: salesOverviewValues,
                    borderColor: '#10B981',
                    borderWidth: 2,
                    pointBackgroundColor: '#10B981',
                    pointHoverBackgroundColor: '#ffffff',
                    pointHoverBorderColor: '#10B981',
                    pointHoverBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.35, 
                    fill: true,
                    backgroundColor: salesGradient
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94A3B8', font: { size: 10 } } },
                    y: { ticks: { color: '#94A3B8', font: { size: 10 } }, grid: { color: '#F1F5F9' } }
                }
            }
        });

        // 3. Payment Methods - Donut Chart
        const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentValues,
                    backgroundColor: ['#10B981', '#3B82F6', '#A855F7', '#F59E0B', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: { legend: { display: false } }
            }
        });

        // --- Dynamic Filtration Framework ---
        const tableRows = document.querySelectorAll('tbody tr.sales-row');
        const noResultsRow = document.getElementById('no-results-row');
        const tableSearch = document.querySelector('input[placeholder="Search invoice..."]');
        const tablePaymentFilter = document.getElementById('table-payment-filter');
        const paymentFilterRight = document.getElementById('payment-filter'); 
        const cashierFilter = document.getElementById('cashier-filter');
        const dateFilter = document.getElementById('date-filter');

        window.resetFilters = function() {
            if (tableSearch) tableSearch.value = '';
            if (tablePaymentFilter) tablePaymentFilter.value = '';
            if (paymentFilterRight) paymentFilterRight.value = '';
            if (cashierFilter) cashierFilter.value = '';
            if (dateFilter) dateFilter.value = '';
            filterTable();
        };

        function filterTable() {
            const searchQuery = tableSearch ? tableSearch.value.toLowerCase().trim() : '';
            const paymentValue = tablePaymentFilter ? tablePaymentFilter.value.toLowerCase() : '';
            const cashierValue = cashierFilter ? cashierFilter.value.toLowerCase() : '';
            const dateValue = dateFilter ? dateFilter.value : '';

            let visibleRowsCount = 0;

            tableRows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                const paymentBadge = row.cells[5]?.querySelector('span')?.textContent.trim().toLowerCase() || row.cells[5]?.textContent.trim().toLowerCase() || '';
                const cashierText = row.cells[6]?.textContent.trim().toLowerCase() || '';
                const rowDate = row.getAttribute('data-date') || '';

                const matchesSearch = textContent.includes(searchQuery);
                const matchesPayment = paymentValue === '' || paymentBadge === paymentValue;
                const matchesCashier = cashierValue === '' || cashierText.includes(cashierValue);
                const matchesDate = dateValue === '' || rowDate === dateValue;

                if (matchesSearch && matchesPayment && matchesCashier && matchesDate) {
                    row.style.display = ''; 
                    visibleRowsCount++;
                } else {
                    row.style.setProperty('display', 'none', 'important');
                }
            });

            if (noResultsRow) {
                if (visibleRowsCount === 0 && tableRows.length > 0) {
                    noResultsRow.classList.remove('hidden');
                } else {
                    noResultsRow.classList.add('hidden');
                }
            }
        }

        if (tableSearch) tableSearch.addEventListener('input', filterTable);
        if (tablePaymentFilter) tablePaymentFilter.addEventListener('change', filterTable);
        if (cashierFilter) cashierFilter.addEventListener('change', filterTable);
        if (dateFilter) dateFilter.addEventListener('change', filterTable);
        if (paymentFilterRight) {
            paymentFilterRight.addEventListener('change', function() {
                if (tablePaymentFilter) tablePaymentFilter.value = this.value;
                filterTable();
            });
        }

        // Action Panel Hooks
        document.getElementById('main-new-sale-btn')?.addEventListener('click', () => window.location.href = 'new_sale.php');
        document.getElementById('main-export-btn')?.addEventListener('click', () => window.location.href = 'export_sales.php');

        // Scale Animation Module Observer - تم الإصلاح هنا
    const observe = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const el = entry.target;
      const anim = el.getAttribute("data-animate");
      const duration = el.getAttribute("data-duration") || "0.5s";
      const delay = el.getAttribute("data-delay") || "0s";
      
      el.style.animation = `${anim} ${duration} cubic-bezier(0.16, 1, 0.3, 1) ${delay} forwards`;
      observe.unobserve(el); 
    }
  });
}, {
  threshold: 0.05
});

document.querySelectorAll('[data-animate]').forEach(el => {
  observe.observe(el);
});
el.classList.add("animated");
el.classList.add(el.dataset.animate);
</script>
</body>
</html>