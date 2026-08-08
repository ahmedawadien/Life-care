<?php
session_start();

require 'config/db.php';
include "includes/header.php";

$totalMedicines =$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$totalCustomers =$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalInvoicesVal =$pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

$totalSales = $pdo->query("
    SELECT IFNULL(SUM(quantity * price), 0) AS total_sales
    FROM sale_items
")->fetch(PDO::FETCH_ASSOC);

$totalInvoices =$pdo->query("
    SELECT COUNT(*) AS total_invoices
    FROM sales
")->fetch(PDO::FETCH_ASSOC);

$totalMedicines =$pdo->query("
    SELECT COUNT(*) AS total_medicines
    FROM medicines
")->fetch(PDO::FETCH_ASSOC);

$lowStockCount =$pdo->query("
    SELECT COUNT(*) AS low_stock
    FROM medicines
    WHERE quantity <= minimum_stock
")->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        DATE(s.sale_date) AS sale_date,
        SUM(si.quantity * si.price) AS total_sales
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id
    WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(s.sale_date)
    ORDER BY sale_date ASC
");
$salesChart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartData[$date] = 0;
}

foreach ($salesChart as $row) {
    $chartData[$row['sale_date']] = $row['total_sales'];
}

$chartValues = array_values($chartData);
$chartDays = array_keys($chartData);

$stmt = $pdo->query("
    SELECT
        IFNULL(c.category_name, 'غير مصنف') AS category,
        SUM(si.quantity) AS total_quantity_sold
    FROM sale_items si
    JOIN medicines m ON si.medicine_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    GROUP BY c.id, c.category_name
    ORDER BY total_quantity_sold DESC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grandTotalQuantity = 0;
foreach ($categories as $row) {
    $grandTotalQuantity += $row['total_quantity_sold'];
}

$data = [];
foreach ($categories as $row) {
    $percentage = 0;
    if ($grandTotalQuantity > 0) {
        $percentage = round(($row['total_quantity_sold'] / $grandTotalQuantity) * 100);
    }
    $data[] = [
        'category' => $row['category'],
        'total' => $row['total_quantity_sold'],
        'percentage' => $percentage
    ];
}

$stmt = $pdo->query("
    SELECT 
        s.id,
        c.full_name AS customer_name,
        s.sale_date,
        IFNULL(
            (SELECT SUM(quantity * price) FROM sale_items WHERE sale_id = s.id), 
            0
        ) AS total_amount
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.sale_date DESC
    LIMIT 5
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        medicine_name,
        manufacturer,
        quantity,
        minimum_stock
    FROM medicines
    WHERE quantity <= minimum_stock
    ORDER BY quantity ASC
");
$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- زر القائمة للهواتف يوضع هنا ليكون عائماً فوق كل شيء -->
<button id="toggle-sidebar" class="btn btn-success d-lg-none position-fixed shadow" style="background-color: #065f46; border: none; padding: 12px 16px; left: 15px; top: 15px; z-index: 1060; color: white; border-radius: 8px;">
    <i class="fa-solid fa-bars fs-5"></i>
</button>

<style>
    /* التعديل: إخفاء زر الهمبرجر عند فتح القائمة على الموبايل */
    .sidebar-open #toggle-sidebar {
        display: none !important;
    }
    
    .fa, .fas, .far, .fab, .fa-solid, .fa-regular, .fa-brands, [class^="fa-"], [class*=" fa-"] {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "Font Awesome 5 Free", sans-serif !important;
        font-weight: 900 !important; 
        display: inline-block !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
        line-height: 1 !important;
        text-transform: none !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    .dashboard-container-fixed {
        display: flex !important;
        width: 100% !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        background: #f4f7fc !important;
    }

    .my-custom-sidebar {
        background-color: #065f46 !important;
        width: 260px !important;
        min-width: 260px !important;
        height: 100vh !important;
        position: sticky !important;
        top: 0 !important;
        left: 0 !important;
        z-index: 1000 !important;
        display: flex !important;
        flex-direction: column !important;
        padding: 1.5rem 1rem !important;
        box-shadow: 4px 0 10px rgba(0,0,0,0.05) !important;
        box-sizing: border-box !important;
        transition: left 0.3s ease-in-out !important;
    }

    .my-custom-sidebar a {
        text-decoration: none !important;
    }

    .main-content-area {
        flex-grow: 1 !important;
        width: calc(100% - 260px) !important;
        min-height: 100vh !important;
        background: #f4f7fc !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
    }

    .dashboard-wrapper, 
    .dashboard-wrapper *:not(.fa):not(.fas):not(.far):not(.fab):not(.fa-solid):not(.fa-regular):not([class^="fa-"]) {
        font-family: 'Cairo', sans-serif !important;
    }

    @media (max-width: 992px) {
        .my-custom-sidebar {
            position: fixed !important;
            top: 0 !important;
            left: -260px !important; 
            height: 100vh !important;
            z-index: 1050 !important;
            transition: left 0.3s ease-in-out !important;
        }
        .my-custom-sidebar.show-sidebar {
            left: 0 !important;
        }
        .main-content-area {
            width: 100% !important;
        }
    }

    .nav-link {
        transition: all 0.2s ease-in-out;
    }
    .green-sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }
    .logout-green-link:hover {
        background-color: rgba(239, 68, 68, 0.2) !important;
        color: #fecaca !important;
    }

    [data-animate] {
        opacity: 0;
        will-change: transform, opacity;
    }
    @keyframes slide-in-top {
        0% {
            opacity: 0;
            transform: translateY(-30px);  
        }
        100% {
            opacity: 1;
            transform: translateY(0); 
        }
    }
    @media (max-width: 768px){
        .nj{
            padding: 20px 20px 20px 10px !important;
        }
    }
</style>

<div class="dashboard-container-fixed">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content-area">
        
        <div class="dashboard-wrapper p-4 md:p-6 text-gray-800" dir="rtl">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <!-- Welcome Header -->
                <div class="flex gr flex-col sm:flex-row items-start sm:items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-gray-100 gap-2 mb-3 ">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <span class="wa">مرحباً بك في نظام إدارة الصيدلية</span>
                        <i class=" fa-solid fa-leaf text-emerald-500"></i>
                    </h1>
                    <span class="text-xs md:text-sm text-gray-500">لوحة البيانات الرئيسية</span>
                </div>

                <!-- Top Stat Cards -->
                 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Card 1 -->
                    <div data-animate="slide-in-top" data-duration="2s" class="bg-white nj hover:shadow-md w-[70%] sm:w-full mx-auto p-4 rounded-2xl  border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">إجمالي المبيعات</p>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mt-2">
                                <?= number_format($totalSales['total_sales'], 2) ?>    
                                <span class="text-xs font-normal text-gray-400">ج.م</span>
                            </h3>
                            <span class="text-xs text-emerald-500 font-semibold mt-2 block"> 
                                <i class="fa-solid fa-arrow-trend-up ml-1"></i> مبيعات فعلية دقيقة
                            </span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div data-animate="slide-in-top" data-duration="2s" data-delay="1s" class="bg-white nj p-4 rounded-2xl hover:shadow-md border border-gray-100 flex items-center justify-between w-[70%] sm:w-full mx-auto">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">عدد الفواتير</p>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mt-2">
                                <?= $totalInvoices['total_invoices'] ?>
                            </h3>
                            <span class="text-xs text-emerald-500 font-semibold mt-2 block">
                                <i class="fa-solid fa-arrow-trend-up ml-1"></i> الفواتير المسجلة بالنظام
                            </span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div data-animate="slide-in-top" data-duration="2s" data-delay="2s" class="bg-white nj hover:shadow-md p-4 w-[70%] sm:w-full mx-auto rounded-2xl border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">إجمالي الأصناف</p>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mt-2">
                                <?= $totalMedicines['total_medicines'] ?>
                            </h3>
                            <span class="text-xs text-gray-400 mt-2 block">إجمالي الأصناف في المخزن</span>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div data-animate="slide-in-top" data-duration="2s" data-delay="3s" class="bg-white nj hover:shadow-md w-[70%] sm:w-full mx-auto p-4 rounded-2xl border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">أصناف ناقصة</p>
                            <h3 class="text-xl md:text-2xl font-bold text-red-500 mt-2">
                                <?= $lowStockCount['low_stock']; ?>
                            </h3>
                            <span class="text-xs text-amber-600 font-semibold mt-2 block">تحتاج إلى إعادة طلب</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                </div>

                <!-- Middle Section: Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Line Chart -->
                    <div data-animate="slide-in-top" data-duration="2s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-gray-800 text-sm md:text-base">المبيعات خلال آخر 7 أيام</h3>
                            <select class="text-xs border border-gray-200 bg-gray-50 rounded-lg px-3 py-1.5 focus:outline-none text-gray-600">
                                <option>آخر 7 أيام</option>
                            </select>
                        </div>
                        <div class="overflow-x-auto">
                            <div class="h-64 min-w-[500px] flex flex-col justify-between relative">
                                <div class="w-full border-b border-gray-100 h-0 text-[10px] text-gray-400 font-sans">600</div>
                                <div class="w-full border-b border-gray-100 h-0 text-[10px] text-gray-400 font-sans">450</div>
                                <div class="w-full border-b border-gray-100 h-0 text-[10px] text-gray-400 font-sans">300</div>
                                <div class="w-full border-b border-gray-100 h-0 text-[10px] text-gray-400 font-sans">150</div>
                                <div class="w-full border-b border-gray-200 h-0 text-[10px] text-gray-400 font-sans">0</div>

                                <svg class="absolute inset-0 w-full h-full pt-4 px-8" viewBox="0 0 700 200" preserveAspectRatio="none">
                                    <?php
                                    $points = [];
                                    $max = count($chartValues) > 0 ? max($chartValues) : 1;
                                    if ($max <= 0) { $max = 1; }

                                    foreach ($chartValues as $index => $value) {
                                        $x = 40 + ($index * 100);
                                        $y = 180 - (($value / $max) * 150);
                                        $points[] = "$x,$y";
                                    }
                                    $path = count($points) > 0 ? "M " . implode(" L ", $points) : "M 0,180";
                                    ?>
                                    <path d="<?= $path ?>" fill="none" stroke="#10b981" stroke-width="3" />
                                    <?php foreach ($points as $point): 
                                        [$x, $y] = explode(",", $point);
                                    ?>
                                    <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="#10b981" />
                                    <?php endforeach; ?>
                                </svg>

                                <div class="flex justify-between text-xs text-gray-400 px-4 mt-2">
                                    <?php foreach ($chartDays as $day): ?>
                                        <span><?= date('D', strtotime($day)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Donut Chart -->
                    <div data-animate="slide-in-top" data-duration="2s" class="bg-white w-[80%] sm:w-full mx-auto p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <h3 class="font-bold text-gray-800 mb-4 text-sm md:text-base">توزيع المبيعات حسب الفئة</h3>
                        <?php $colors = ['#10b981', '#3b82f6', '#a855f7', '#fbbf24', '#22d3ee']; ?>
                        <div class="flex flex-col sm:flex-row lg:flex-col items-center justify-between gap-6 h-full">
                            <?php
                            $start = 0;
                            $gradient = "";
                            foreach ($data as $index => $item) {
                                $end = $start + $item['percentage'];
                                $gradient .= $colors[$index % count($colors)] . " {$start}% {$end}%, ";
                                $start = $end;
                            }
                            $gradient = rtrim($gradient, ", ");
                            ?>

                            <div class="relative w-36 h-36 md:w-40 md:h-40 rounded-full flex items-center justify-center shrink-0" 
                                 style="background: <?= !empty($gradient) ? 'conic-gradient(' . $gradient . ')' : '#e5e7eb' ?>;">
                                <div class="absolute w-28 h-28 md:w-30 md:h-30 bg-white rounded-full flex items-center justify-center">
                                    <div class="text-center">
                                        <span class="block text-xl md:text-2xl font-bold text-gray-800"><?= number_format($grandTotalQuantity) ?></span>
                                        <span class="text-[9px] md:text-[10px] text-gray-400">إجمالي كمية المبيعات</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 w-full text-xs">
                                <?php if (!empty($data)): ?>
                                    <?php foreach ($data as $index => $item): ?>
                                        <div class="flex justify-between items-center">
                                            <span class="flex items-center gap-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?= $colors[$index % count($colors)] ?>"></span>
                                                <span class="truncate max-w-[120px]"><?= htmlspecialchars($item['category']) ?></span>
                                            </span>
                                            <span class="font-semibold text-gray-600"><?= $item['percentage'] ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-gray-400 py-3">
                                        لا توجد كميات مبيعات لعرضها حالياً
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Table 1: Low Stock Items -->
                    <div data-animate="slide-in-top" data-duration="3s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2 text-sm md:text-base">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                    <span>أصناف منخفضة المخزون</span>
                                </h3>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-right text-xs min-w-[500px]">
                                    <thead>
                                        <tr class="text-gray-400 border-b border-gray-100">
                                            <th class="pb-3 font-medium">الدواء</th>
                                            <th class="pb-3 font-medium">الشركة</th>
                                            <th class="pb-3 font-medium text-center">كمية متبقية</th>
                                            <th class="pb-3 font-medium text-center">الحد الأدنى</th>
                                            <th class="pb-3 font-medium text-center">الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 text-gray-700 font-medium">
                                        <?php foreach ($lowStock as $medicine): ?>
                                            <tr>
                                                <td class="py-3"><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                                                <td class="py-3 text-gray-400"><?= htmlspecialchars($medicine['manufacturer']) ?></td>
                                                <td class="py-3 text-center text-red-500"><?= $medicine['quantity'] ?></td>
                                                <td class="py-3 text-center text-gray-400"><?= $medicine['minimum_stock'] ?></td>
                                                <td class="py-3 text-center">
                                                    <button class="er px-3 py-1.5 bg-red-50 text-red-500 rounded-xl text-[11px] font-semibold hover:bg-red-100 cursor-pointer transition">
                                                        طلب
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <?php if (count($lowStock) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-gray-400">لا توجد أصناف منخفضة المخزون</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-center mt-4">
                            <button class="w-full sm:w-1/2 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition cursor-pointer">
                                عرض كل الأصناف
                            </button>
                        </div>
                    </div>
                    
                    <!-- Table 2: Recent Invoices -->
                    <div data-animate="slide-in-top" data-duration="3s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-800 flex items-center gap-2 text-sm md:text-base">
                                    <i class="fa-solid fa-file-invoice text-emerald-500"></i>
                                    <span>آخر الفواتير</span>
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-right text-xs min-w-[500px]">
                                    <thead>
                                        <tr class="text-gray-400 border-b border-gray-100">
                                            <th class="pb-3 font-medium">رقم الفاتورة</th>
                                            <th class="pb-3 font-medium">العميل</th>
                                            <th class="pb-3 font-medium text-center">التاريخ</th>
                                            <th class="pb-3 font-medium text-center">الإجمالي الحقيقي</th>
                                            <th class="pb-3 font-medium text-center">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 text-gray-700 font-medium">
                                        <?php foreach ($invoices as $invoice): ?>
                                            <tr>
                                                <td class="py-3 text-gray-400">
                                                    #INV-<?= str_pad($invoice['id'], 5, '0', STR_PAD_LEFT) ?>
                                                </td>
                                                <td class="py-3">
                                                    <?= htmlspecialchars($invoice['customer_name'] ?? 'عميل افتراضي') ?>
                                                </td>
                                                <td class="py-3 text-center text-gray-400">
                                                    <?= date('Y-m-d', strtotime($invoice['sale_date'])) ?>
                                                </td>
                                                <td class="py-3 text-center font-bold text-emerald-600 whitespace-nowrap">
                                                    <!-- التعديل: إجبار النص على البقاء في سطر واحد دون التفاف عبر whitespace-nowrap -->
                                                    <?= number_format($invoice['total_amount'], 2) ?> ج.م
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[11px] whitespace-nowrap">
                                                        مدفوعة
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <?php if (count($invoices) == 0): ?>
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-gray-400">لا توجد فواتير</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="flex justify-center mt-4">
                            <button class="w-full sm:w-1/2 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition cursor-pointer">
                                عرض كل الفواتير
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div> 
</div> 

<div id="sidebar-backdrop" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-black opacity-50" style="backdrop-filter: blur(2px); z-index: 1040;"></div>

<script>
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

document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggle-sidebar");
    const closeBtn = document.getElementById("close-sidebar");
    const backdrop = document.getElementById("sidebar-backdrop");

    // التعديل: مزامنة كلاس الموبايل لربط زر الـ hamburger وعرض القائمة الجانبية وإخفائها بشكل موحد وصحيح
    function openSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.add("show-sidebar");
            backdrop.classList.remove("d-none");
            backdrop.style.setProperty("display", "block", "important");
            document.body.classList.add("sidebar-open");
        }
    }

    function closeSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.remove("show-sidebar");
            backdrop.classList.add("d-none");
            backdrop.style.setProperty("display", "none", "important");
            document.body.classList.remove("sidebar-open");
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            openSidebar();
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    if (backdrop) {
        backdrop.addEventListener("click", closeSidebar);
    }
});
</script>