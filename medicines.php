<?php
include '../config/db.php';
include '../includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$currentFilter = isset($_GET['filter']) ? trim($_GET['filter']) : 'All';

// Get status alert message if it exists
$statusAlert = isset($_GET['status']) ? trim($_GET['status']) : '';

// 2. Fetch all medicine records from the database using PDO
try {
    $sql = "SELECT * FROM medicines";
    $stmt = $pdo->query($sql);
    $dbMedicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// 3. FIRST PASS: Extract and normalize ALL global categories directly from raw data.
$categoriesList = ['All'];
foreach ($dbMedicines as $row) {
    if (!empty($row['category_id'])) {
        $catValue = strtoupper(trim($row['category_id']));
        
        // Force database ID numbers into strict text category names only
        if ($catValue === '1' || $catValue === 'ANTIBIOTICS') {
            $categoryName = 'Antibiotics';
        } elseif ($catValue === '2' || $catValue === 'CREAMS' || $catValue === 'CREAM') {
            $categoryName = 'Creams';
        } elseif ($catValue === '3' || $catValue === 'SUPPLEMENTS') {
            $categoryName = 'Supplements';
        } else {
            if (is_numeric($catValue)) {
                $categoryName = 'General';
            } else {
                $categoryName = ucwords(strtolower(trim($row['category_id'])));
            }
        }

        if (!in_array($categoryName, $categoriesList)) {
            $categoriesList[] = $categoryName;
        }
    }
}

// 4. SECOND PASS: Re-map database columns into the UI data arrays
$medicines = [];
foreach ($dbMedicines as $row) {
    $isLowStock = (intval($row['quantity']) <= intval($row['minimum_stock']));
    $isOutOfStock = (intval($row['quantity']) <= 0);
    
    $uiColor = '#0D9488'; // Teal default
    $tagText = null;
    
    if ($isOutOfStock) {
        $uiColor = '#EF4444'; // Red
        $tagText = 'Out of stock';
    } elseif ($isLowStock) {
        $uiColor = '#B45309'; // Amber
        $tagText = 'Low stock';
    }

    $categoryName = 'General';
    if (!empty($row['category_id'])) {
        $catValue = strtoupper(trim($row['category_id']));
        if ($catValue === '1' || $catValue === 'ANTIBIOTICS') {
            $categoryName = 'Antibiotics';
        } elseif ($catValue === '2' || $catValue === 'CREAMS' || $catValue === 'CREAM') {
            $categoryName = 'Creams';
        } elseif ($catValue === '3' || $catValue === 'SUPPLEMENTS') {
            $categoryName = 'Supplements';
        } else {
            if (is_numeric($catValue)) {
                $categoryName = 'General';
            } else {
                $categoryName = ucwords(strtolower(trim($row['category_id'])));
            }
        }
    }

    $id = $row['id'];
    $medicines[$id] = [
        'id'             => $id,
        'name'           => $row['medicine_name'],
        'genericName'    => $row['generic_name'],
        'category'       => $categoryName,
        'status'         => (intval($row['quantity']) <= 0) ? 'Out of Stock' : 'Active',
        'dose'           => $row['strength'],
        'frequency'      => !empty($row['dosage_form']) ? $row['dosage_form'] : 'Once Daily',
        'progress'       => '0/1 today', 
        'progressText'   => '0 of 1 taken', 
        'stock'          => intval($row['quantity']),
        'refillReminder' => intval($row['minimum_stock']),
        'prescribedBy'   => !empty($row['manufacturer']) ? $row['manufacturer'] : 'Generic Pharma',
        'startDate'      => !empty($row['manufacture_date']) ? date('M d, Y', strtotime($row['manufacture_date'])) : 'N/A',
        'duration'       => 'Expiry: ' . (!empty($row['expiry_date']) ? date('M d, Y', strtotime($row['expiry_date'])) : 'N/A'),
        'color'          => $uiColor,
        'tag'            => $tagText,
        'location'       => $row['shelf_location']
    ];
}

$filteredMedicines = [];
foreach ($medicines as $id => $med) {
    if ($currentFilter !== 'All' && $med['category'] !== $currentFilter) {
        continue;
    }

    if (!empty($search)) {
        if (
            stripos($med['name'], $search) === false &&
            stripos($med['genericName'], $search) === false &&
            stripos($med['category'], $search) === false &&
            stripos($med['location'], $search) === false
        ) {
            continue;
        }
    }
    $filteredMedicines[$id] = $med;
}

// 6. Handle Active Selection State Logic safely using filtered list outputs
$firstKey = !empty($filteredMedicines) ? array_key_first($filteredMedicines) : null;
$selectedId = isset($_GET['id']) && array_key_exists($_GET['id'], $filteredMedicines) ? $_GET['id'] : $firstKey;
$currentMed = $selectedId ? $filteredMedicines[$selectedId] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines Management System</title>
    <!-- Tailwind CSS Engine CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Family Layout Config -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        input:focus{
            border-color: transparent !important;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 antialiased h-screen flex flex-col md:flex-row overflow-hidden">

    <div class="md:hidden bg-white border-b border-slate-200 p-4 flex items-center justify-between z-50">
        <div class="flex items-center gap-3">
            <a href="http://life-care.lovestoblog.com" class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shadow-sm">
                <img style="width: 16px;" src="../assets/css/img/back.png">
            </a>
            <div>
                <h2 class="text-md font-bold text-slate-900">My Medicines</h2>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="toggleMobileList()" class="px-3 py-1.5 bg-teal-50 text-teal-700 font-semibold text-xs rounded-lg border border-teal-200">
                <i class="fa-solid fa-list-ul mr-1"></i> Browse
            </button>
            <img src="../assets/css/img/pills.png" class="w-[32px]">
        </div>
    </div>

    <!-- Main Workspace Container -->
    <div class="flex flex-1 overflow-hidden relative">
        
        <!-- Left Column Dashboard List View (Responsive Drawer on Mobile) -->
        <div id="mobileListDrawer" class="fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out z-40 w-[85vw] sm:w-[380px] md:w-[400px] border-r border-slate-200 bg-white flex flex-col h-full p-6 md:p-[45px] flex-shrink-0 shadow-xl md:shadow-none">
            
            <!-- Desktop Header Content (Hidden on Mobile Drawer) -->
            <div class="hidden md:flex items-center gap-3">
                <a href="http://life-care.lovestoblog.com/index.php" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors shadow-sm" title="Back to Dashboard">
                    <img style="width: 20px;" src="../assets/css/img/back.png">
                </a>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">My Medicines</h2>
                    <p class="text-sm text-slate-400 mt-0.5"><?php echo date('F d, Y'); ?></p>
                </div>
                <img src="../assets/css/img/pills.png" class="w-[45px] ml-auto">
            </div>

            <!-- Drawer Title for Mobile Only -->
            <div class="flex md:hidden items-center justify-between mb-4">
                <span class="text-sm font-bold tracking-wider text-slate-400 uppercase">Select Medicine</span>
                <button onclick="toggleMobileList()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Search Area -->
            <div class="relative w-full mt-2 md:mt-4 mb-4">
                <form method="GET" class="w-full">
                    <?php if ($selectedId): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($selectedId); ?>">
                    <?php endif; ?>

                    <span class="absolute inset-y-0 left-3.5 flex items-center justify-center pointer-events-none">
                        <img style="width: 20px; height: 20px; object-fit: contain;" src="../assets/css/img/search (1).png" alt="Search">
                    </span>

                    <input 
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search medicines..."
                        class="w-full bg-[#F1F5F9] border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                    >

                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($currentFilter); ?>">
                </form>
            </div>

            <!-- Dynamic Category Segment Tabs -->
            <div class="flex gap-2 mb-5 overflow-x-auto pb-1 scrollbar-none max-w-full">
                <?php foreach ($categoriesList as $tab): ?>
                    <a
                        href="?filter=<?php echo urlencode($tab); ?>&search=<?php echo urlencode($search); ?>"
                        class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap inline-block <?php echo $currentFilter === $tab ? 'bg-[#E0F2FE] text-[#0369A1] shadow-sm' : 'text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100'; ?>"
                    >
                        <?php echo htmlspecialchars($tab); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Scrollable Vertical Medical Stack Layout -->
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <?php if (empty($filteredMedicines)): ?>
                    <p class="text-xs text-slate-400 text-center mt-6">No medicine found in this category.</p>
                <?php else:
                    foreach ($filteredMedicines as $med):
                        $isSelected = ($med['id'] === $selectedId);
                ?>
                    <a
                        href="?id=<?php echo $med['id']; ?>&filter=<?php echo urlencode($currentFilter); ?>&search=<?php echo urlencode($search); ?>"
                        onclick="closeMobileList()"
                        class="block p-4 rounded-2xl transition-all border <?php echo $isSelected ? 'bg-[#F0FDFA] border-[#99F6E4] ring-1 ring-[#99F6E4]' : 'bg-white border-transparent hover:border-slate-200'; ?>"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full block flex-shrink-0" style="background-color: <?php echo $med['color']; ?>;"></span>
                                <div>
                                    <h4 class="font-semibold text-sm text-slate-800"><?php echo htmlspecialchars($med['name']); ?></h4>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        <?php echo htmlspecialchars($med['dose']); ?> · <?php echo htmlspecialchars($med['frequency']); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($med['tag']): ?>
                                <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded-md text-[10px] font-medium block whitespace-nowrap">
                                    <?php echo htmlspecialchars($med['tag']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 flex items-center gap-2 text-xs font-medium">
                            <span class="text-slate-400">Qty: <strong class="text-slate-600"><?php echo $med['stock']; ?></strong></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-400">Loc: <?php echo htmlspecialchars($med['location'] ?: 'A-1'); ?></span>
                        </div>
                    </a>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>

        <div id="drawerOverlay" onclick="toggleMobileList()" class="hidden fixed inset-0 bg-black/40 z-30 transition-opacity duration-300"></div>

        <!-- Right Side Panel Content Workspace -->
        <div class="flex-1 bg-[#F8FAFC] p-4 md:p-8 overflow-y-auto h-full relative">
            
            <!-- Dynamic Success Alert Banner Box Component -->
            <?php if ($statusAlert === 'deleted'): ?>
                <div id="successNotification" class="max-w-4xl mx-auto mb-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between shadow-sm transition-all duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                            <i class="fa-solid fa-circle-check text-md"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-emerald-950">تم حذف الدواء بنجاح</p>
                            <p class="text-xs text-emerald-600/80">The medical record has been securely cleared from inventory management.</p>
                        </div>
                    </div>
                    <button onclick="dismissNotification()" class="text-emerald-400 hover:text-emerald-600 transition-colors p-1">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($currentMed): ?>
                <div class="max-w-4xl mx-auto space-y-4 md:space-y-6">
                    
                    <!-- Main Header Cyan Hero Container -->
                    <div class="bg-[#14B8A6] text-white rounded-2xl md:rounded-3xl p-6 md:p-8 relative shadow-sm overflow-hidden">
                        <span class="text-[10px] md:text-xs font-bold tracking-wider opacity-90 uppercase">
                            <?php echo htmlspecialchars($currentMed['category']); ?>
                        </span>
                        
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mt-2">
                            <div>
                                <h1 class="text-2xl md:text-4xl font-bold tracking-tight break-words"><?php echo htmlspecialchars($currentMed['name']); ?></h1>
                                <p class="text-emerald-100 text-xs md:text-sm mt-1"><?php echo htmlspecialchars($currentMed['genericName']); ?></p>
                            </div>
                            <span class="bg-teal-400/60 px-3 py-1.5 md:py-2 rounded-full text-[11px] md:text-xs font-semibold backdrop-blur-sm self-start sm:self-auto">
                                <?php echo htmlspecialchars($currentMed['status']); ?>
                            </span>
                        </div>

                        <div class="flex gap-8 md:gap-12 mt-6 md:mt-8 border-t border-white/20 pt-4 md:pt-6">
                            <div>
                                <p class="text-xl md:text-2xl font-bold"><?php echo htmlspecialchars($currentMed['dose']); ?></p>
                                <p class="text-[11px] md:text-xs text-emerald-100 mt-0.5">Strength Metrics</p>
                            </div>
                            <div class="border-l border-white/20 pl-6 md:pl-8">
                                <p class="text-xl md:text-2xl font-bold"><?php echo htmlspecialchars($currentMed['frequency']); ?></p>
                                <p class="text-[11px] md:text-xs text-emerald-100 mt-0.5">Form / Frequency</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mid-Row Two Column Information Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                        
                        <!-- Actions & Operations Segment -->
                        <div class="bg-slate-100 w-full mx-auto text-black rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-[11px] md:text-xs font-bold tracking-wider uppercase mb-4 text-slate-500">
                                    Management Controls
                                </h3>
                                
                                <div class="bg-white rounded-xl p-4 border border-slate-200/60 text-xs md:text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Internal ID:</span>
                                        <span class="font-mono font-semibold text-slate-700">#<?php echo $currentMed['id']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Warehouse Loc:</span>
                                        <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($currentMed['location'] ?: 'Not Set'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-2 w-full">
                                <a href="edit.php?id=<?php echo $currentMed['id']; ?>" class="flex gap-2 flex-row flex-1 items-center justify-center bg-[#564AFC] text-white font-semibold text-xs py-2.5 rounded-xl text-center hover:bg-[#4A3DE0] transition-colors no-underline">
                                    <span>Edit Medicine</span>
                                    <img src="../assets/css/img/compose (1).png" class="w-[18px] md:w-[20px]">
                                </a>
                                <a href="delete.php?id=<?php echo (int)$currentMed['id']; ?>" 
                                   onclick="return confirm('هل أنت متأكد من حذف هذا الدواء؟');" 
                                   class="px-4 bg-[#FC1210] rounded-xl flex items-center justify-center hover:bg-[#E50F0D] transition-colors pointer-events-auto"
                                   title="Delete Medicine">
                                    <img src="../assets/css/img/delete.png" class="w-[22px] md:w-[25px] pointer-events-none" alt="Delete">
                                </a>
                            </div>
                        </div>

                        <!-- Volumetric Stock Tracking Dashboard Segment -->
                        <div class="bg-slate-100 text-black rounded-2xl p-4 flex flex-col justify-between border border-slate-200 shadow-sm">
                            <div>
                                <h3 class="text-[11px] md:text-xs font-bold tracking-wider uppercase mb-4 text-slate-500">
                                    Stock Level status
                                </h3>
                                
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl md:text-4xl font-bold text-slate-800"><?php echo $currentMed['stock']; ?></span>
                                    <span class="text-xs md:text-sm text-slate-500">units remaining</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden mb-2">
                                    <?php 
                                        $maxCapacityUnit = max(200, $currentMed['stock']);
                                        $percentage = min(($currentMed['stock'] / $maxCapacityUnit) * 100, 100);
                                        $barColor = ($currentMed['stock'] <= $currentMed['refillReminder']) ? 'bg-amber-500' : 'bg-[#14B8A6]';
                                        if ($currentMed['stock'] <= 0) $barColor = 'bg-red-500';
                                    ?>
                                    <div class="h-full rounded-full <?php echo $barColor; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <p class="text-[11px] md:text-xs font-medium text-slate-500">
                                    Minimum alert line tracking set at <?php echo $currentMed['refillReminder']; ?> units
                                </p>

                                <button class="w-full mt-4 bg-white border border-[#14B8A6] text-[#14B8A6] font-semibold text-xs md:text-sm py-2.5 rounded-xl hover:bg-emerald-50 transition-colors text-center shadow-sm">
                                    Logistics Restock Order
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm">
                            <p class="text-[11px] md:text-xs text-slate-400 font-medium">Manufacturer Partner</p>
                            <p class="text-xs md:text-sm font-semibold text-slate-700 mt-1 break-words"><?php echo htmlspecialchars($currentMed['prescribedBy']); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm">
                            <p class="text-[11px] md:text-xs text-slate-400 font-medium">Manufacture Date</p>
                            <p class="text-xs md:text-sm font-semibold text-slate-700 mt-1"><?php echo htmlspecialchars($currentMed['startDate']); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm">
                            <p class="text-[11px] md:text-xs text-slate-400 font-medium">Expiration Period</p>
                            <p class="text-xs md:text-sm font-semibold text-slate-700 mt-1 break-words"><?php echo htmlspecialchars($currentMed['duration']); ?></p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 md:p-6 border border-slate-200/60 shadow-sm">
                        <h3 class="text-[11px] md:text-xs font-bold tracking-wider text-slate-400 uppercase mb-2">
                            Global Storage Handling Instructions
                        </h3>
                        <p class="text-xs md:text-sm text-slate-600 leading-relaxed">
                            Maintain inventory securely arrayed inside storage shelves at position <strong><?php echo htmlspecialchars($currentMed['location'] ?: 'Unassigned Zone'); ?></strong>. Verify batch numbers, packaging seal safety parameters, and cold chain logs regularly prior to processing distribution outputs.
                        </p>
                    </div>

                </div>
            <?php else: ?>
                <div class="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                    <p class="text-sm">No medicine found matching this search or category scope.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- JavaScript Navigation Layer Drawer Controllers -->
    <script>
        function toggleMobileList() {
            const drawer = document.getElementById('mobileListDrawer');
            const overlay = document.getElementById('drawerOverlay');
            
            if (drawer.classList.contains('-translate-x-full')) {
                drawer.classList.remove('-translate-x-full');
                drawer.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
            } else {
                drawer.classList.add('-translate-x-full');
                drawer.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
            }
        }
        
        function closeMobileList() {
            if (window.innerWidth < 768) {
                toggleMobileList();
            }
        }

        function dismissNotification() {
            const el = document.getElementById('successNotification');
            if(el) {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
        }

        // Auto-hide the success notification banner after 4.5 seconds
        window.addEventListener('DOMContentLoaded', () => {
            const notification = document.getElementById('successNotification');
            if (notification) {
                setTimeout(() => {
                    dismissNotification();
                }, 4500);
            }
        });
    </script>
</body>
</html>