<?php
// 1. Establish Database Connection & Setup State
include '../config/db.php';

$message = '';
$messageType = '';

// 2. Fetch active customers & active medicines to build selector dropdowns
try {
    $customersStmt = $pdo->query("SELECT id, full_name, phone FROM customers ORDER BY full_name ASC");
    $customers = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

    $medicinesStmt = $pdo->query("SELECT id, name, price FROM medicines ORDER BY name ASC");
    $medicines = $medicinesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $customers = [];
    $medicines = [];
    $message = "Initialization Fault: " . $e->getMessage();
    $messageType = "error";
}

// 3. Handle Sales Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $discount = floatval($_POST['discount'] ?? 0);
    
    // Arrays containing itemized structural properties
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $item_costs = $_POST['item_cost'] ?? [];
    $item_quantities = $_POST['item_qty'] ?? [];

    if (!empty($medicine_ids)) {
        try {
            // BACKEND SECURITY VALIDATION: Calculate total independently to prevent form manipulation
            $calculated_subtotal = 0;
            for ($i = 0; $i < count($medicine_ids); $i++) {
                if (empty($medicine_ids[$i])) continue;
                $calculated_subtotal += floatval($item_costs[$i]) * intval($item_quantities[$i]);
            }
            
            $base_taxed = max(0, $calculated_subtotal - $discount);
            $calculated_tax = $base_taxed * 0.14;
            $final_total_payable = $base_taxed + $calculated_tax;

            // Begin transactional integrity protection loop
            $pdo->beginTransaction();

            // Insert into main "sales" table
            $salesQuery = "INSERT INTO sales (customer_id, total, created_at) 
                           VALUES (:customer_id, :total, NOW())";
            
            $salesStmt = $pdo->prepare($salesQuery);
            $salesStmt->execute([
                ':customer_id' => $customer_id,
                ':total'       => $final_total_payable
            ]);

            // Retrieve the newly created auto-increment id from the sales table
            $saleId = $pdo->lastInsertId();

            // Target your itemized breakdown table "sale_items"
            $itemQuery = "INSERT INTO sale_items (sale_id, medicine_id, quantity, price) 
                          VALUES (:sale_id, :medicine_id, :quantity, :price)";
            $itemStmt = $pdo->prepare($itemQuery);

            for ($i = 0; $i < count($medicine_ids); $i++) {
                if (empty($medicine_ids[$i])) continue; 

                $med_id = intval($medicine_ids[$i]);
                $qty = intval($item_quantities[$i]);
                $price = floatval($item_costs[$i]);

                $itemStmt->execute([
                    ':sale_id'     => $saleId,
                    ':medicine_id' => $med_id,
                    ':quantity'    => $qty,
                    ':price'       => $price
                ]);
            }

            $pdo->commit();
            $message = "Sale transaction record generated successfully! Saved under Sale ID #$saleId";
            $messageType = "success";

        } catch (\PDOException $e) {
            $pdo->rollBack();
            $message = "Database Processing Error: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Cannot register empty ledger item rows.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCare Pharmacy - Create New Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 antialiased h-screen flex overflow-hidden relative">

    <!-- ==================== MOBILE BACKDROP OVERLAY ==================== -->
    <div id="sidebar_overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300"></div>

    <!-- ==================== LEFT SIDEBAR BRANDING ==================== -->
    <!-- Updated with responsiveness positioning classes: default hidden off-canvas on mobile, handles transitions -->
    <aside id="main_sidebar" class="w-[260px] bg-[#022329] flex flex-col justify-between p-6 flex-shrink-0 text-slate-300 fixed md:static inset-y-0 left-0 z-50 -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <div>
            <div class="flex items-center justify-between mb-8 px-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#00BFA5] text-white flex items-center justify-center text-base">
                        <i class="fa-solid fa-prescription-bottle-medical"></i>
                    </div>
                    <div>
                        <h2 class="text-white font-bold leading-none tracking-wide text-lg">LifeCare</h2>
                        <p class="text-[10px] tracking-widest text-[#00BFA5] font-bold uppercase mt-0.5">Pharmacy</p>
                    </div>
                </div>
                <!-- Close Button exclusively for mobile screen constraints -->
                <button type="button" id="close_sidebar_btn" class="md:hidden text-slate-400 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <nav class="space-y-1.5">
                <a href="#" class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium hover:bg-white/5 transition-colors">
                    <span class="w-4 text-center text-slate-400"><i class="fa-solid fa-chart-simple"></i></span> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium bg-[#0D9488] text-white shadow-md transition-all">
                    <span class="w-4 text-center"><i class="fa-solid fa-receipt"></i></span> POS Invoice
                </a>
                <a href="#" class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium hover:bg-white/5 transition-colors">
                    <span class="w-4 text-center text-slate-400"><i class="fa-solid fa-users"></i></span> Customers
                </a>
                <a href="#" class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium hover:bg-white/5 transition-colors">
                    <span class="w-4 text-center text-slate-400"><i class="fa-solid fa-pills"></i></span> Medicines
                </a>
            </nav>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-700 overflow-hidden flex items-center justify-center border border-white/10 text-slate-300">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white leading-none">Ahmed Awadien</h4>
                        <p class="text-[10px] text-slate-400 mt-1">Pharmacist</p>
                    </div>
                </div>
                <span class="text-[10px] text-slate-400 cursor-pointer"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
        </div>
    </aside>

    <!-- ==================== MAIN CONTENT CONTAINER AREA ==================== -->
    <form action="" method="POST" class="flex-1 flex flex-col overflow-hidden w-full">
        
        <header class="h-20 bg-white border-b border-slate-100 px-4 sm:px-8 flex items-center justify-between flex-shrink-0 gap-4">
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Hamburger Menu Trigger for Mobile -->
                <button type="button" id="open_sidebar_btn" class="md:hidden w-10 h-10 rounded-xl border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-50 active:scale-95 transition-all">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="w-10 h-10 rounded-full bg-[#ECFDF5] text-[#059669] hidden sm:flex items-center justify-center text-sm font-semibold">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight">New Retail Invoice</h1>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5 hidden sm:block">
                        Sales <span class="text-slate-300 mx-1"><i class="fa-solid fa-chevron-right text-[9px]"></i></span> <span class="text-[#0D9488]">Create Invoice</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:gap-6">
                <?php if(!empty($message)): ?>
                    <div class="px-3 py-1.5 text-[11px] sm:text-xs font-bold rounded-xl <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>
                <div class="text-right hidden xs:block">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Invoice Date</span>
                    <span class="text-xs font-bold text-slate-700"><?= date('d F, Y') ?></span>
                </div>
                <div class="text-right border-l border-slate-200 pl-3 sm:pl-6">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Invoice No.</span>
                    <span class="text-xs font-bold text-emerald-600">#LC-<?= rand(10000, 99999) ?></span>
                </div>
            </div>
        </header>

        <!-- Main Body Workspace: Becomes a vertical single-column layout on mobile, split columns on desktop -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
            
            <!-- LEFT PANEL: Dynamic Product Entry Stack -->
            <div class="flex-1 p-4 sm:p-8 overflow-y-auto space-y-6">
                
                <!-- ROW 1: Customer Dropdown -->
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-5 flex items-center justify-between gap-4">
                    <div class="w-full max-w-xl">
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Select Account Link</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"><i class="fa-solid fa-user-tag"></i></span>
                            <select name="customer_id" class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs font-medium focus:outline-none focus:border-teal-500 appearance-none transition-all">
                                <option value="">Walk-in Customer (Default Account)</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"><i class="fa-solid fa-chevron-down"></i></span>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Live Medicine Selector Sheet -->
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm p-4 sm:p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Item Batch Scanner</h3>
                    
                    <div class="flex flex-col sm:grid sm:grid-cols-12 gap-3 items-stretch sm:items-end">
                        <div class="sm:col-span-6">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Select Medicine from Stock</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"><i class="fa-solid fa-pills"></i></span>
                                <select id="scan_med_dropdown" class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs font-medium focus:outline-none focus:border-teal-500 appearance-none transition-all">
                                    <option value="" data-price="0.00">-- Choose Medicine Stock Profile --</option>
                                    <?php foreach($medicines as $m): ?>
                                        <option value="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-price="<?= $m['price'] ?>">
                                            <?= htmlspecialchars($m['name']) ?> (Stock Price: $<?= number_format($m['price'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:contents gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Price</label>
                                <input type="number" step="0.01" id="scan_price" value="0.00" class="w-full bg-slate-50 text-slate-500 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-center focus:outline-none">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Qty</label>
                                <input type="number" id="scan_qty" value="1" min="1" class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-center focus:outline-none focus:border-teal-500">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="button" id="inject_btn" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-2.5 sm:py-3 rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-plus text-[10px]"></i> Inject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Active Line Items Data Table Grid -->
                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto w-full no-scrollbar">
                        <table class="w-full min-w-[600px] text-left border-collapse">
                            <thead class="bg-slate-50 text-slate-400 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="p-4 w-12 text-center">#</th>
                                    <th class="p-4">Medicinal Component Descriptor</th>
                                    <th class="p-4 w-28 text-center">Unit Cost</th>
                                    <th class="p-4 w-24 text-center">Quantity</th>
                                    <th class="p-4 w-28 text-right">Total Payable</th>
                                    <th class="p-4 w-12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="invoice_items_body" class="divide-y divide-slate-50 text-xs font-medium text-slate-700">
                                <!-- Dynamic Row items inject here dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- RIGHT PANEL: Financial Calculation Stack -->
            <!-- Adjusted layout constraints to switch gracefully from a standard pane to a bottom block on smaller screens -->
            <div class="w-full lg:w-[380px] bg-white border-t lg:border-t-0 lg:border-l border-slate-100 flex-shrink-0 flex flex-col justify-between p-6 shadow-[-10px_0_30px_-15px_rgba(0,0,0,0.03)] overflow-y-auto lg:h-full">
                <div class="space-y-6">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800">Payment Breakdown</h3>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-xs font-medium text-slate-500">
                            <span>Cart Subtotal</span>
                            <span class="font-bold text-slate-800" id="subtotal_display">$0.00</span>
                            <input type="hidden" name="subtotal" id="subtotal_input" value="0.00">
                        </div>
                        
                        <div class="space-y-1">
                            <div class="flex justify-between items-center text-xs font-medium text-slate-500">
                                <span>Flat Discount ($)</span>
                                <input type="number" step="0.01" name="discount" id="discount_input" value="0.00" min="0" class="w-20 bg-[#F8FAFC] border border-slate-200 rounded-lg py-1 px-2 text-right font-bold text-slate-800 text-xs focus:outline-none focus:border-teal-500">
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-xs font-medium text-slate-500">
                            <span>Sales Tax (14%)</span>
                            <span class="font-bold text-slate-700" id="tax_display">$0.00</span>
                            <input type="hidden" name="tax" id="tax_input" value="0.00">
                        </div>

                        <hr class="border-slate-100 my-2">

                        <div class="bg-[#F0FDF4] border border-[#DCFCE7] rounded-xl p-3 flex justify-between items-center">
                            <span class="text-xs font-bold text-emerald-800">Total Payable Amount</span>
                            <span class="text-lg font-black text-emerald-600" id="total_payable_display">$0.00</span>
                            <input type="hidden" name="total_payable" id="total_payable_input" value="0.00">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Method of Settlement</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label id="label_cash" class="border-2 border-teal-500 bg-teal-50/20 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer transition-all payment-label">
                                <input type="radio" name="payment_method" value="cash" checked class="hidden payment-radio">
                                <span class="text-teal-600 text-sm"><i class="fa-solid fa-money-bill-wave"></i></span>
                                <span class="text-[11px] font-bold text-teal-900">Cash Settlement</span>
                            </label>
                            <label id="label_card" class="border border-slate-200 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer hover:bg-slate-50 transition-all payment-label">
                                <input type="radio" name="payment_method" value="card" class="hidden payment-radio">
                                <span class="text-slate-400 text-sm"><i class="fa-solid fa-credit-card"></i></span>
                                <span class="text-[11px] font-bold text-slate-600">Electronic Card</span>
                            </label>
                        </div>
                    </div>

                    <div id="cash_calculation_box" class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] font-bold text-slate-500">Received Cash</span>
                            <input type="number" step="0.01" id="received_cash" value="0.00" class="w-24 bg-white border border-slate-200 rounded-lg py-1 px-2.5 text-right font-bold text-slate-800 text-xs focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold text-slate-600 border-t border-slate-200/60 pt-2.5">
                            <span>Balance Return</span>
                            <span class="text-amber-600 font-extrabold" id="change_return_display">$0.00</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 pt-6 lg:pt-4 border-t border-slate-100 mt-6 lg:mt-0">
                    <button type="submit" id="submit_invoice_btn" class="w-full bg-[#00BFA5] hover:bg-teal-500 text-white font-bold text-xs py-3 rounded-xl shadow-md shadow-teal-500/10 transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-print"></i> Process & Save Sale
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- ==================== FRONTEND INTERACTION CALCULATOR ENGINE ==================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('scan_med_dropdown');
            const priceInput = document.getElementById('scan_price');
            const qtyInput = document.getElementById('scan_qty');
            const injectBtn = document.getElementById('inject_btn');
            const itemsBody = document.getElementById('invoice_items_body');
            const mainForm = document.querySelector('form');
            
            // Totals Fields
            const subtotalDisplay = document.getElementById('subtotal_display');
            const subtotalInput = document.getElementById('subtotal_input');
            const discountInput = document.getElementById('discount_input');
            const taxDisplay = document.getElementById('tax_display');
            const taxInput = document.getElementById('tax_input');
            const totalPayableDisplay = document.getElementById('total_payable_display');
            const totalPayableInput = document.getElementById('total_payable_input');
            const receivedCash = document.getElementById('received_cash');
            const changeReturnDisplay = document.getElementById('change_return_display');
            
            // Payment Method Toggles
            const paymentRadios = document.querySelectorAll('.payment-radio');
            const labelCash = document.getElementById('label_cash');
            const labelCard = document.getElementById('label_card');
            const cashCalculationBox = document.getElementById('cash_calculation_box');

            // Responsive Sidebar Selectors
            const openSidebarBtn = document.getElementById('open_sidebar_btn');
            const closeSidebarBtn = document.getElementById('close_sidebar_btn');
            const mainSidebar = document.getElementById('main_sidebar');
            const sidebarOverlay = document.getElementById('sidebar_overlay');

            // --- Responsive Sidebar Toggle Engine ---
            function openSidebar() {
                mainSidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
            }

            function closeSidebar() {
                mainSidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            }

            openSidebarBtn.addEventListener('click', openSidebar);
            closeSidebarBtn.addEventListener('click', closeSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);

            // Toggle Settlement Method Styles dynamically
            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'cash') {
                        labelCash.className = "border-2 border-teal-500 bg-teal-50/20 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer transition-all payment-label";
                        labelCard.className = "border border-slate-200 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer hover:bg-slate-50 transition-all payment-label";
                        cashCalculationBox.classList.remove('hidden');
                    } else {
                        labelCard.className = "border-2 border-teal-500 bg-teal-50/20 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer transition-all payment-label";
                        labelCash.className = "border border-slate-200 rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer hover:bg-slate-50 transition-all payment-label";
                        cashCalculationBox.classList.add('hidden');
                    }
                });
            });

            dropdown.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const selectedPrice = selectedOption.getAttribute('data-price') || '0.00';
                priceInput.value = selectedPrice;
            });

            function calculateTotals() {
                let subtotal = 0;
                document.querySelectorAll('.item-cost').forEach((costEl, i) => {
                    const qtyEl = document.querySelectorAll('.item-qty')[i];
                    subtotal += parseFloat(costEl.value) * parseInt(qtyEl.value);
                });

                const discount = parseFloat(discountInput.value) || 0;
                const baseTaxed = Math.max(0, subtotal - discount);
                const tax = baseTaxed * 0.14; 
                const totalPayable = baseTaxed + tax;
                const received = parseFloat(receivedCash.value) || 0;
                const change = Math.max(0, received - totalPayable);

                subtotalDisplay.textContent = `$${subtotal.toFixed(2)}`;
                subtotalInput.value = subtotal.toFixed(2);
                taxDisplay.textContent = `$${tax.toFixed(2)}`;
                taxInput.value = tax.toFixed(2);
                totalPayableDisplay.textContent = `$${totalPayable.toFixed(2)}`;
                totalPayableInput.value = totalPayable.toFixed(2);
                changeReturnDisplay.textContent = `$${change.toFixed(2)}`;
            }

            injectBtn.addEventListener('click', function() {
                const selectedOption = dropdown.options[dropdown.selectedIndex];
                const medicineId = dropdown.value;
                const medName = selectedOption.getAttribute('data-name');
                const price = parseFloat(priceInput.value).toFixed(2);
                // Fixed your bug here: replaced PHP intval with JS parseInt
                const qty = parseInt(qtyInput.value);

                if(!medicineId || price <= 0 || qty <= 0) return;

                const rowTotal = (price * qty).toFixed(2);
                const rowCount = itemsBody.querySelectorAll('tr').length + 1;

                const tr = document.createElement('tr');
                tr.className = "hover:bg-slate-50/60 transition-colors";
                tr.innerHTML = `
                    <td class="p-4 text-center text-slate-300 font-bold index-num">${rowCount}</td>
                    <td class="p-4">
                        <div class="font-bold text-slate-800">${medName}</div>
                        <input type="hidden" name="medicine_id[]" value="${medicineId}">
                        <div class="text-[10px] text-slate-400 mt-0.5">DB Reference Key ID: #${medicineId}</div>
                    </td>
                    <td class="p-4 text-center font-bold text-slate-600">
                        $${price} <input type="hidden" name="item_cost[]" class="item-cost" value="${price}">
                    </td>
                    <td class="p-4 text-center font-bold text-slate-800">
                        ${qty} <input type="hidden" name="item_qty[]" class="item-qty" value="${qty}">
                    </td>
                    <td class="p-4 text-right font-bold text-slate-900">$${rowTotal}</td>
                    <td class="p-4 text-center">
                        <button type="button" class="text-slate-300 hover:text-red-500 transition-colors remove-row-btn"><i class="fa-regular fa-trash-can"></i></button>
                    </td>
                `;

                itemsBody.appendChild(tr);
                calculateTotals();

                dropdown.value = '';
                priceInput.value = '0.00';
                qtyInput.value = '1';
            });

            itemsBody.addEventListener('click', function(e) {
                if(e.target.closest('.remove-row-btn')) {
                    e.target.closest('tr').remove();
                    document.querySelectorAll('.index-num').forEach((el, index) => el.textContent = index + 1);
                    calculateTotals();
                }
            });

            mainForm.addEventListener('submit', function(e) {
                const totalRows = itemsBody.querySelectorAll('tr').length;
                
                if (totalRows === 0) {
                    e.preventDefault();
                    const scannerCard = dropdown.closest('.bg-white');
                    scannerCard.classList.remove('border-slate-100');
                    scannerCard.classList.add('border-rose-400', 'bg-rose-50/20');
                    
                    alert("⚠️ Please select a medicine and click the 'Inject' button to add items to this invoice before saving.");
                    
                    setTimeout(() => {
                        scannerCard.classList.remove('border-rose-400', 'bg-rose-50/20');
                        scannerCard.classList.add('border-slate-100');
                    }, 3000);
                }
            });

            discountInput.addEventListener('input', calculateTotals);
            receivedCash.addEventListener('input', calculateTotals);
        });
    </script>
</body>
</html>


<?php
session_start();

include "includes/header.php";
include 'includes/sidebar.php';
require 'config/db.php';

// إجمالي الأدوية
$totalMedicines = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();

// إجمالي العملاء
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

// إجمالي المبيعات
$totalSalesVal = $pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM sales")->fetchColumn();

// إجمالي الفواتير
$totalInvoicesVal = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

// جلب إجمالي المبيعات كمصفوفة مترافقة
$totalSales = $pdo->query("
    SELECT IFNULL(SUM(total_amount),0) AS total_sales
    FROM sales
")->fetch(PDO::FETCH_ASSOC);

// جلب عدد الفواتير كمصفوفة مترافقة
$totalInvoices = $pdo->query("
    SELECT COUNT(*) AS total_invoices
    FROM sales
")->fetch(PDO::FETCH_ASSOC);

// جلب إجمالي الأدوية كمصفوفة مترافقة
$totalMedicines = $pdo->query("
    SELECT COUNT(*) AS total_medicines
    FROM medicines
")->fetch(PDO::FETCH_ASSOC);

// حساب الأدوية منخفضة المخزون
$lowStockCount = $pdo->query("
    SELECT COUNT(*) AS low_stock
    FROM medicines
    WHERE quantity <= minimum_stock
")->fetch(PDO::FETCH_ASSOC);

// جلب مبيعات آخر 7 أيام للرسم البياني
$stmt = $pdo->query("
    SELECT
        DATE(sale_date) AS sale_date,
        SUM(total_amount) AS total_sales
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
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

// جلب آخر 5 فواتير
$stmt = $pdo->query("
    SELECT 
        sales.id,
        customers.full_name AS customer_name,
        sales.sale_date,
        sales.total_amount
    FROM sales
    LEFT JOIN customers
        ON sales.customer_id = customers.id
    ORDER BY sales.sale_date DESC
    LIMIT 5
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب قائمة الأدوية منخفضة المخزون بالتفصيل
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

$stmt = $pdo->query("
SELECT
    c.category_name AS category,
    SUM(si.quantity * si.price) AS total_sales
FROM sale_items si
JOIN medicines m ON si.medicine_id = m.id
JOIN categories c ON m.category_id = c.id
GROUP BY c.id, c.category_name
ORDER BY total_sales DESC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grandTotal = 0;
foreach ($categories as $row) {
    $grandTotal += $row['total_sales'];
}

$data = [];
foreach ($categories as $row) {
    $percentage = 0;
    if ($grandTotal > 0) {
        $percentage = round(($row['total_sales'] / $grandTotal) * 100);
    }
    $data[] = [
        'category' => $row['category'] ?? 'غير مصنف',
        'total' => $row['total_sales'],
        'percentage' => $percentage
    ];
}
?>

<style>
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

    .dashboard-wrapper, .dashboard-wrapper * {
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
        #toggle-sidebar {
            display: block !important;
        }
    }

    @media (min-width: 992px) {
        #toggle-sidebar {
            display: none !important;
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
</style>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الصيدلية - لوحة التحكم</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f4f7f6;
        }
    </style>
</head>
<body class="p-6 text-gray-800">
    <!-- مساحة المحتوى الرئيسية المعزولة -->
    <div class="main-content-area">
        
        <div class="dashboard-wrapper p-4 md:p-6 text-gray-800" dir="rtl">
            <div class="max-w-[1400px] mx-auto space-y-6">
                
                <!-- Welcome Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-gray-100 gap-2">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <span>مرحباً بك في نظام إدارة الصيدلية</span>
                        <i class="fa-solid fa-leaf text-emerald-500"></i>
                    </h1>
                    <span class="text-xs md:text-sm text-gray-500">لوحة البيانات الرئيسية</span>
                </div>

                <!-- Top Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Card 1: Total Sales -->
                    <div data-animate="slide-in-top" data-duration="1.5s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">إجمالي المبيعات</p>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mt-2">
                                <?= number_format($totalSales['total_sales'], 2) ?>    
                                <span class="text-xs font-normal text-gray-400">ج.م</span>
                            </h3>
                            <span class="text-xs text-emerald-500 font-semibold mt-2 block"> 
                                <i class="fa-solid fa-arrow-trend-up ml-1"></i> +12% من الشهر الماضي
                            </span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                    </div>

                    <!-- Card 2: Total Invoices -->
                    <div data-animate="slide-in-top" data-delay="1s" data-duration="1.5s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">عدد الفواتير</p>
                            <h3 class="text-xl md:text-2xl font-bold text-gray-800 mt-2">
                                <?= $totalInvoices['total_invoices'] ?>
                            </h3>
                            <span class="text-xs text-emerald-500 font-semibold mt-2 block">
                                <i class="fa-solid fa-arrow-trend-up ml-1"></i> +8% من الشهر الماضي
                            </span>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                    </div>

                    <!-- Card 3: Total Items -->
                    <div data-animate="slide-in-top" data-delay="2s" data-duration="1.5s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
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

                    <!-- Card 4: Shortages -->
                    <div data-animate="slide-in-top" data-delay="3s" data-duration="1.5s" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
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
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-gray-800 text-sm md:text-base">المبيعات خلال آخر 7 أيام</h3>
                            <select class="text-xs border border-gray-200 bg-gray-50 rounded-lg px-3 py-1.5 focus:outline-none text-gray-600">
                                <option>آخر 7 أيام</option>
                                <option>آخر 30 يوم</option>
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
                                    $max = max($chartValues);
                                    if ($max <= 0) { $max = 1; }

                                    foreach ($chartValues as $index => $value) {
                                        $x = 40 + ($index * 100);
                                        $y = 180 - (($value / $max) * 150);
                                        $points[] = "$x,$y";
                                    }
                                    $path = "M " . implode(" L ", $points);
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
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
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

                            <div class="relative w-36 h-36 md:w-40 md:h-40 rounded-full flex items-center justify-center shrink-0" style="background: conic-gradient(<?= $gradient ?: '#e5e7eb 0% 100%' ?>);">
                                <div class="absolute w-28 h-28 md:w-30 md:h-30 bg-white rounded-full flex items-center justify-center">
                                    <div class="text-center">
                                        <span class="block text-sm md:text-md font-bold text-gray-800"><?= number_format($grandTotal) ?></span>
                                        <span class="text-[9px] md:text-[10px] text-gray-400">إجمالي المبيعات</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 w-full text-xs">
                                <?php foreach ($data as $index => $item): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?= $colors[$index % count($colors)] ?>"></span>
                                            <span class="truncate max-w-[120px]"><?= htmlspecialchars($item['category']) ?></span>
                                        </span>
                                        <span class="font-semibold text-gray-600"><?= $item['percentage'] ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Table 1: Low Stock Items -->
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
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
                                                    <button class="px-3 py-1.5 bg-red-50 text-red-500 rounded-xl text-[11px] font-semibold hover:bg-red-100 cursor-pointer transition">
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
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between">
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
                                            <th class="pb-3 font-medium text-center">الإجمالي</th>
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
                                                <td class="py-3 text-center">
                                                    <?= number_format($invoice['total_amount'], 2) ?> ج.م
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[11px]">
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

    </div> <!-- نهاية مساحة المحتوى -->
</div> <!-- نهاية الحاوي الأساسي للـ Flexbox -->

<!-- JavaScript -->
<script>
// تفعيل الحركات
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

// فتح وإغلاق السايدبار للهواتف
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.getElementById("toggle-sidebar");
    const closeBtn = document.getElementById("close-sidebar");
    const backdrop = document.getElementById("sidebar-backdrop");

    function openSidebar() {
        if(sidebar && backdrop) {
            sidebar.classList.add("show-sidebar");
            backdrop.classList.remove("d-none");
        }
    }

    function closeSidebar() {
        if(sidebar && backdrop) {
            sidebar.classList.remove("show-sidebar");
            backdrop.classList.add("d-none");
        }
    }

    if (toggleBtn) toggleBtn.addEventListener("click", openSidebar);
    if (closeBtn) closeBtn.addEventListener("click", closeSidebar);
    if (backdrop) backdrop.addEventListener("click", closeSidebar);
});
</script>

<?php
include "includes/footer.php";
?>