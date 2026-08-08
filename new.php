<?php
session_start();
require '../config/db.php'; 

$current_page = basename(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $medicine_code    = !empty($_POST['medicine_code']) ? trim($_POST['medicine_code']) : 'MED' . time(); 
    $barcode          = trim($_POST['barcode'] ?? '');
    $medicine_name    = trim($_POST['medicine_name'] ?? '');
    $generic_name     = trim($_POST['generic_name'] ?? '');
    $category_id      = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $supplier_id      = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $manufacturer     = trim($_POST['manufacturer'] ?? '');
    $brand            = trim($_POST['brand'] ?? '');
    $dosage_form      = trim($_POST['dosage_form'] ?? '');
    $strength         = trim($_POST['strength'] ?? '');
    $pack_size        = trim($_POST['pack_size'] ?? '');
    $unit             = trim($_POST['unit'] ?? '');
    
    $purchase_price   = floatval($_POST['purchase_price'] ?? 0.00);
    $selling_price    = floatval($_POST['selling_price'] ?? 0.00);
    $tax              = floatval($_POST['tax'] ?? 0.00);
    $discount         = floatval($_POST['discount'] ?? 0.00);
    $quantity         = intval($_POST['quantity'] ?? 0);
    $minimum_stock    = intval($_POST['minimum_stock'] ?? 0);
    $reorder_level    = intval($_POST['reorder_level'] ?? 0);
    
    $batch_number     = trim($_POST['batch_number'] ?? '');
    $manufacture_date = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : null;
    $expiry_date      = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $shelf_location   = trim($_POST['shelf_location'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $status           = trim($_POST['status'] ?? 'Available'); 

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/medicines/';
        
        // إنشاء المجلد بصلاحيات مناسبة إذا لم يكن موجوداً
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); 
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $allowed_extensions)) {
            $new_file_name = 'IMG_' . uniqid() . '.' . $file_extension;
            $image_path = $upload_dir . $new_file_name;
            
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        } else {
            $_SESSION['error'] = "خطأ: امتداد الصورة غير مسموح به. يرجى رفع ملفات JPG، PNG أو WEBP فقط.";
            header("Location: " . $current_page);
            exit();
        }
    }

    if (empty($medicine_name) || empty($barcode)) {
        $_SESSION['error'] = "خطأ: اسم الدواء والباركود حقول إلزامية.";
        header("Location: " . $current_page);
        exit();
    }

    try {
        $sql = "INSERT INTO medicines (
                    medicine_code, barcode, medicine_name, generic_name, category_id, 
                    supplier_id, manufacturer, brand, dosage_form, strength, 
                    pack_size, unit, purchase_price, selling_price, tax, 
                    discount, quantity, minimum_stock, reorder_level, batch_number, 
                    manufacture_date, expiry_date, shelf_location, image, description, 
                    status, created_at, updated_at
                ) VALUES (
                    :medicine_code, :barcode, :medicine_name, :generic_name, :category_id, 
                    :supplier_id, :manufacturer, :brand, :dosage_form, :strength, 
                    :pack_size, :unit, :purchase_price, :selling_price, :tax, 
                    :discount, :quantity, :minimum_stock, :reorder_level, :batch_number, 
                    :manufacture_date, :expiry_date, :shelf_location, :image, :description, 
                    :status, NOW(), NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':medicine_code'    => $medicine_code,
            ':barcode'          => $barcode,
            ':medicine_name'    => $medicine_name,
            ':generic_name'     => $generic_name,
            ':category_id'      => $category_id,
            ':supplier_id'      => $supplier_id,
            ':manufacturer'     => $manufacturer,
            ':brand'            => $brand,
            ':dosage_form'      => $dosage_form,
            ':strength'         => $strength,
            ':pack_size'        => $pack_size,
            ':unit'             => $unit,
            ':purchase_price'   => $purchase_price,
            ':selling_price'    => $selling_price,
            ':tax'              => $tax,
            ':discount'         => $discount,
            ':quantity'         => $quantity,
            ':minimum_stock'    => $minimum_stock,
            ':reorder_level'    => $reorder_level,
            ':batch_number'     => $batch_number,
            ':manufacture_date' => $manufacture_date,
            ':expiry_date'      => $expiry_date,
            ':shelf_location'   => $shelf_location,
            ':image'            => $image_path,
            ':description'      => $description,
            ':status'           => $status
        ]);

        $_SESSION['success'] = "تم إضافة الصنف بنجاح وتحديث إحصائيات لوحة التحكم.";
        header("Location: dashboard.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "فشل في حفظ البيانات: " . $e->getMessage();
        header("Location: " . $current_page);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Medicine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#F8FAFC] text-gray-800 min-h-screen flex flex-col antialiased">

    <!-- HEADER WITH ACTIONS -->
    <header class="bg-white border-b border-gray-100 px-4 md:px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="http://life-care.lovestoblog.com/data/edit.php" class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-100 transition shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div class="flex items-center gap-2 text-xs md:text-sm font-medium">
                <span class="text-gray-400">Dashboard</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300"></i>
                <span class="text-[#10B981] font-semibold">Add New Medicine</span>
            </div>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <button type="button" onclick="window.location.href='dashboard.php';" class="flex-1 sm:flex-none text-center px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                Discard
            </button>
            <button type="submit" form="medicine-form" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-[#10B981] hover:bg-emerald-600 text-white text-sm font-semibold rounded-xl transition shadow-sm shadow-emerald-100">
                <i data-lucide="save" class="w-4 h-4"></i> Save Product
            </button>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-6 max-w-7xl mx-auto w-full">
        
        <!-- رسالة الخطأ المحدثة بالـ Session -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i>
                <span class="font-medium"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- رسالة النجاح المحدثة بالـ Session -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                <span class="font-medium"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- تم توجيه الأكشن إلى نفس الصفحة لضمان معالجة البيانات بسلاسة -->
        <form id="medicine-form" action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- LEFT & CENTER REGION (2 Columns wide) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- General Information Card -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 md:p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <img src="../assets/css/img/medicine (2).png" class="w-[25px]"> General Information
                    </h3>
                    <hr class="border-gray-100">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Medicine Name *</label>
                            <input type="text" name="medicine_name" placeholder="e.g. Panadol Extra" required class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Generic Name (Active Ingredient) *</label>
                            <input type="text" name="generic_name" placeholder="e.g. Paracetamol / Caffeine" required class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Barcode / UPC *</label>
                            <div class="relative flex items-center">
                                <input type="text" name="barcode" placeholder="Scan or enter code" required class="border border-gray-200 rounded-xl pl-3.5 pr-10 py-2.5 text-sm outline-none w-full focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition">
                                <i data-lucide="scan-barcode" class="w-4 h-4 text-gray-400 absolute right-3.5 cursor-pointer hover:text-gray-600"></i>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">SKU / ID (Medicine Code)</label>
                            <input type="text" name="medicine_code" placeholder="MED-100938" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Dosage Form *</label>
                            <select name="dosage_form" required class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none bg-white focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full cursor-pointer">
                                <option value="" disabled selected>Select Form</option>
                                <option value="Tablet">Tablet</option>
                                <option value="Capsule">Capsule</option>
                                <option value="Syrup">Syrup</option>
                                <option value="Injection">Injection</option>
                                <option value="Ointment">Ointment</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Strength (e.g. 500mg)</label>
                            <input type="text" name="strength" placeholder="e.g. 500mg" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Pack Size</label>
                            <input type="text" name="pack_size" placeholder="e.g. 24 Tablets" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Unit</label>
                            <input type="text" name="unit" placeholder="e.g. Box / Strip" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-500">Short Description / Indications</label>
                        <textarea name="description" rows="3" placeholder="Write usage, indications or warning notes..." class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full resize-none"></textarea>
                    </div>
                </div>

                <!-- Pricing & Stock Card -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 md:p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="banknote" class="w-5 h-5 text-[#10B981]"></i> Pricing & Stock Control
                    </h3>
                    <hr class="border-gray-100">

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Cost (EGP) *</label>
                            <input type="number" name="purchase_price" step="0.01" min="0" placeholder="0.00" required class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Price (EGP) *</label>
                            <input type="number" name="selling_price" step="0.01" min="0" placeholder="0.00" required class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Tax (%)</label>
                            <input type="number" name="tax" step="0.01" min="0" placeholder="14" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Discount (EGP)</label>
                            <input type="number" name="discount" step="0.01" min="0" placeholder="0.00" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Quantity *</label>
                            <input type="number" name="quantity" min="0" placeholder="150" required class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Min Stock</label>
                            <input type="number" name="minimum_stock" min="0" placeholder="15" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Reorder Lvl</label>
                            <input type="number" name="reorder_level" min="0" placeholder="30" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Shelf Loc.</label>
                            <input type="text" name="shelf_location" placeholder="e.g. A-12" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT REGION (Sidebar inputs) -->
            <div class="space-y-6">
                
                <!-- Category & Supplier Card -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 md:p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <img src="../assets/css/img/tag.png" class="w-[20px]"> Categorization
                    </h3>
                    <hr class="border-gray-100">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-500">Category *</label>
                        <select name="category_id" required class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none bg-white focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full cursor-pointer">
                            <option value="" disabled selected>Select Category</option>
                            <option value="1">Analgesics / Pain Killers</option>
                            <option value="2">Antibiotics</option>
                            <option value="3">Cardiovascular</option>
                            <option value="4">Vitamins & Supplements</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-500">Supplier *</label>
                        <select name="supplier_id" required class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none bg-white focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full cursor-pointer">
                            <option value="" disabled selected>Select Supplier</option>
                            <option value="1">Ibnsina Pharma</option>
                            <option value="2">United Company for Pharmacists</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Manufacturer</label>
                            <input type="text" name="manufacturer" placeholder="e.g. Pfizer" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] transition w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Brand</label>
                            <input type="text" name="brand" placeholder="e.g. Sanofi" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] transition w-full">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-500">Status</label>
                        <select name="status" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none bg-white focus:border-[#10B981] transition w-full cursor-pointer">
                            <option value="Available">Available</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                </div>

                <!-- Expiry & Dates -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 md:p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-[#10B981]"></i> Dates & Batches
                    </h3>
                    <hr class="border-gray-100">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-500">Batch Number</label>
                        <input type="text" name="batch_number" placeholder="B-9028A" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-[#10B981] focus:ring-2 focus:ring-emerald-500/10 transition w-full">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Mfg. Date</label>
                            <input type="date" name="manufacture_date" class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#10B981] cursor-pointer w-full">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-gray-500">Expiry Date *</label>
                            <input type="date" name="expiry_date" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#10B981] cursor-pointer w-full">
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 md:p-6 shadow-sm space-y-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="image" class="w-4 h-4 text-[#10B981]"></i> Product Image
                    </h3>
                    <hr class="border-gray-100">

                    <label class="border-2 border-dashed border-gray-200 hover:border-[#10B981] focus-within:border-[#10B981] transition rounded-2xl p-6 flex flex-col items-center text-center cursor-pointer group">
                        <input type="file" name="image" accept="image/*" class="sr-only" onchange="previewImage(this)">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-[#10B981] mb-3 group-hover:scale-105 transition">
                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                        </div>
                        <span id="upload-text" class="text-sm font-semibold text-gray-800">Upload product image</span>
                        <span class="text-xs text-gray-400 mt-1 max-w-[200px]">Drag and drop or click to browse. Max size 2MB (PNG, JPG, WEBP)</span>
                    </label>
                </div>

            </div>
        </form>
    </main>

    <script>
        // تشغيل مكتبة الأيقونات
        lucide.createIcons();

        // عرض اسم الملف فور اختياره
        function previewImage(input) {
            const textSpan = document.getElementById('upload-text');
            if (input.files && input.files[0]) {
                textSpan.innerText = "Selected: " + input.files[0].name;
                textSpan.classList.remove('text-gray-800');
                textSpan.classList.add('text-emerald-600');
            }
        }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    
    const alerts = document.querySelectorAll('.bg-emerald-50, .bg-red-50');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s ease, transform 0.5s ease";
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            
            setTimeout(function() {
                alert.remove();
            }, 500); 
        }, 4000);
    });
});
</script>
</body>
</html>