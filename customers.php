<?php
require '../config/db.php';

$message = '';
$messageType = ''; 

// 2. Handle POST requests BEFORE rendering any HTML headers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect inputs from the form
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '') ?: null;
    $action    = $_POST['action'] ?? 'save';

    // Server-side validation
    if (empty($full_name) || empty($phone)) {
        $status = ['status' => 'error', 'message' => 'Full Name and Phone Number are required.'];
    } else {
        try {
            // Prepared SQL Statement matching table columns
            $sql = "INSERT INTO customers (full_name, phone, address) VALUES (:full_name, :phone, :address)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone'     => $phone, 
                ':address'   => $address
            ]);

            $newId = $pdo->lastInsertId();
            $joinedDate = date('d M Y');

            $status = [
                'status' => 'success',
                'action' => $action,
                'message' => 'Customer saved successfully!',
                'customer' => [
                    'id' => $newId,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'address' => $address ?? 'N/A',
                    'joined' => $joinedDate
                ]
            ];

        } catch (\PDOException $e) {
            $status = ['status' => 'error', 'message' => "Database error: " . $e->getMessage()];
        }
    }

    // 3. If it is an AJAX request, immediately return clean JSON and EXIT
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($status);
        exit; // This stops the script from continuing to load header.php
    }

    // Fallback for standard forms (without Javascript enabled)
    if ($status['status'] === 'success') {
        $redirectUrl = ($action === 'save_another') ? "add-customer.php?status=success_another" : "add-customer.php?status=success";
        header("Location: " . $redirectUrl);
        exit;
    } else {
        $message = $status['message'];
        $messageType = 'error';
    }
}

// 4. Handle redirect messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = "Customer saved successfully!";
        $messageType = "success";
    } elseif ($_GET['status'] === 'success_another') {
        $message = "Customer saved! Form ready for the next customer.";
        $messageType = "success";
    }
}

// 5. Fetch customers safely
try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $customers = [];
}

// 6. NOW it is safe to include HTML layouts
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCare Pharmacy - Add New Customer</title>
    <!-- Tailwind CSS Engine CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Plus Jakarta Sans Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* From Uiverse.io by abrahamcalsin */ 
.fd {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #00BFA5;
  font-family: "Montserrat", sans-serif;
  box-shadow: 0px 6px 24px 0px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  cursor: pointer;
  border: none;
  width: fit-content !important;
}

.fd:after {
  content: " ";
  width: 0%;
  height: 100%;
  background: black;
  position: absolute;
  transition: all 0.4s ease-in-out;
  right: 0;
}

.fd:hover::after {
  right: auto;
  left: 0;
  width: 100%;
}

.fd span {
  text-align: center;
  text-decoration: none;
  width: 100%;
  color: #fff;
  font-size: 1.125em;
  font-weight: 700;
  letter-spacing: 1px;
  z-index: 20;
  transition: all 0.3s ease-in-out;
}

.fd:hover span {
  color: white;
  animation: scaleUp 0.3s ease-in-out;
}

@keyframes scaleUp {
  0% {
    transform: scale(1);
  }

  50% {
    transform: scale(0.95);
  }

  100% {
    transform: scale(1);
  }
}
@media (max-width:768px){
    .customer-name{
        max-width:120px !important;   /* adjust as needed */
        overflow:hidden !important;
        text-overflow:ellipsis !important;
        white-space:nowrap !important;
    }

}
        input:focus{
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.5); 
            border-color: transparent !important;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F7F7F7] text-slate-800 antialiased md:h-screen flex flex-col md:flex-row md:overflow-hidden">

    <!-- ==================== MAIN CONTENT CONTAINER AREA ==================== -->
    <div class="flex-1 flex flex-col overflow-hidden w-full">
        
        <!-- HEADER MODULE -->
        <header class="min-h-20 py-4 md:py-0 bg-white border-b border-slate-100 px-4 md:px-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <a href="http://life-care.lovestoblog.com/index.php" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition-colors shadow-sm" title="Back to Dashboard">
                    <img style="width: 20px;" src="../assets/css/img/back.png">
                </a>
                <div class="w-10 h-10 rounded-full bg-[#ECFDF5] text-[#059669] flex items-center justify-center text-sm font-semibold">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">Add New Customer</h1>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">
                        Customers <span class="text-slate-300 mx-1"><i class="fa-solid fa-chevron-right text-[9px]"></i></span> <span class="text-[#0D9488]">Add New Customer</span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center justify-center pointer-events-none">
                        <img style="width: 18px; height: 18px; display: block;" src="../assets/css/img/search (2).png" alt="Search">
                    </span>
                    <input 
    type="text" 
    id="tableSearch"
    placeholder="Search customers..." 
    class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs focus:outline-none focus:border-slate-300"
>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 relative">
                        <i class="fa-regular fa-bell text-sm"></i> <span class="w-2 h-2 rounded-full bg-red-500 absolute top-2.5 right-2.5"></span>
                    </button>
                    <button type="button" class="w-10 h-10 rounded-full bg-[#ECFDF5] text-[#059669] flex items-center justify-center border border-emerald-100">
                        <i class="fa-solid fa-store"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- SCROLLABLE CORE BODY CONTENT LAYOUT -->
        <form id="customerForm" action="add-customer.php" method="POST" class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Dynamic SQL Operation Alert Container -->
            <div id="alertContainer" class="mx-4 md:mx-8 mt-4 hidden">
                <div id="alertBox" class="p-4 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <i id="alertIcon" class="fa-solid text-sm"></i>
                    <span id="alertText"></span>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mx-4 md:mx-8 mt-4 p-4 rounded-xl text-xs font-semibold flex items-center gap-2 <?php echo $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
                    <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> text-sm"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="flex-1 p-4 md:p-8 overflow-y-auto space-y-6">
                
                <!-- Main Grid: Form Inputs + Info Block Panel -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    
                    <!-- Primary Input Core Area (Takes 2 Columns on desktop) -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm px-3 py-4 md:p-6 space-y-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800">Customer Details</h3>
                                    <p class="text-[11px] text-slate-400">Populate record variables into the customer registry</p>
                                </div>
                            </div>

                            <!-- Responsive Input Matrix Wrapper -->
                            <div class="space-y-4">
                                <!-- Data Row: Name and Phone (1 col on mobile, 2 cols on desktop) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Full Name Input -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                                <i class="fa-regular fa-user text-sm"></i>
                                            </span>
                                            <input 
                                                type="text"
                                                id="full_name"
                                                name="full_name"
                                                placeholder="Enter full name"
                                                required
                                                class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-teal-500 transition-all"
                                            >
                                        </div>
                                    </div>

                                    <!-- Phone Number Input -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Phone Number <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-3  flex items-center text-slate-400 pointer-events-none">
                                                <i style="transform: rotate(100deg);" class="fa-solid fa-phone-flip text-sm"></i>
                                            </span>
                                            <input
                                                type="text"
                                                id="phone"
                                                name="phone"
                                                placeholder="Enter phone number"
                                                required
                                                class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-teal-500 transition-all"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Row: Address -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Address</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                                            <i class="fa-solid fa-location-dot text-sm"></i>
                                        </span>
                                        <input 
                                            type="text" 
                                            id="address" 
                                            name="address" 
                                            placeholder="Enter residential address" 
                                            class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-teal-500 transition-all"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Side Informative Panel Block -->
                    <div class="w-full space-y-6">
                        <div class="bg-gradient-to-br from-[#F0FDF4] to-[#DCFCE7] border border-[#DCFCE7] rounded-[24px] p-5 space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-[#166534]"><i class="fa-solid fa-circle-question"></i></span>
                                <h4 class="text-xs font-bold text-[#14532D]">Database Synchronization</h4>
                            </div>
                            <p class="text-[11px] font-medium text-[#166534] leading-relaxed">
                                Submitting this information directly streams standard data lines into your live <strong>customers</strong> table structure.
                            </p>
                            <ul class="space-y-2.5 text-[11px] font-medium text-[#166534] border-t border-emerald-200/50 pt-3">
                                <li class="flex items-center gap-2"><span class="text-emerald-600 text-[10px]"><i class="fa-solid fa-circle-check"></i></span> auto-increment sequence</li>
                                <li class="flex items-center gap-2"><span class="text-emerald-600 text-[10px]"><i class="fa-solid fa-circle-check"></i></span> created_at standard timestamp</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h3 class="text-sm font-bold text-slate-800">Customer Directory</h3>
                        <p class="text-[11px] text-slate-400">Reviewing existing registered records in the pharmacy ecosystem</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left min-w-[700px]">
                            <thead class="bg-slate-50 border-b border-slate-100 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="p-4 w-16 text-center">#</th>
                                    <th class="p-4 text-center">Customer</th>
                                    <th class="p-4 text-center">Phone</th>
                                    <th class="p-4 text-center">Address</th>
                                    <th class="p-4 text-center">Joined</th>
                                    <th class="p-4 text-center">Status</th>
                                    <th class="p-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody" class="text-xs divide-y divide-slate-100">
                                <?php if(empty($customers)): ?>
                                <tr id="noCustomersRow">
                                    <td colspan="7" class="p-8 text-center text-slate-400 font-medium">No customers registered yet.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($customers as $customer): ?>
                                    <tr class="hover:bg-teal-50/40 transition-colors">
                                        <td class="p-4 font-semibold text-center text-slate-400">
                                            <?= htmlspecialchars($customer['id']) ?>
                                        </td>
                                        <td class="p-4 ">
                                            <div class="flex items-center justify-center gap-3">
                                                <div style="white-space: nowrap;" class="customer-name w-9 h-9 rounded-full bg-gradient-to-r  from-teal-500 to-cyan-500  text-white flex items-center justify-center font-bold text-sm">
                                                    <?= strtoupper(substr($customer['full_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-slate-800 truncate block md:inline md:whitespace-normal md:overflow-visible">

                                                        <?= htmlspecialchars($customer['full_name'] ?? '') ?>
                                                    </div>
                                                    <div class="text-[10px] text-slate-400">
                                                        Customer ID #<?= htmlspecialchars($customer['id']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 font-medium text-slate-600 text-center">
                                            <?= htmlspecialchars($customer['phone'] ?? '') ?>
                                        </td>
                                        <td class="p-4 text-slate-500 max-w-[200px] truncate text-center">
                                            <?= htmlspecialchars($customer['address'] ?? 'N/A') ?>
                                        </td>
                                        <td class="p-4 text-slate-500 text-center">
                                            <?= isset($customer['created_at']) ? date('d M Y', strtotime($customer['created_at'])) : 'N/A' ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wide">
                                                Active
                                            </span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex justify-center gap-1.5">
                                                <a href="view-customer.php?id=<?= urlencode($customer['id']) ?>" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors" title="View Detail">
                                                    <i class="fa fa-eye text-xs"></i>
                                                </a>
                                                <a href="edit-customer.php?id=<?= urlencode($customer['id']) ?>" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-100 transition-colors" title="Edit Customer">
                                                    <i class="fa fa-edit text-xs"></i>
                                                </a>
                                                <a href="customers.php?id=<?= urlencode($customer['id']) ?>" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-colors" title="Delete Customer" onclick="return confirm('Are you sure you want to delete this customer?');">
                                                    <i class="fa fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

                             
<footer class="min-h-20 py-4 md:py-0 bg-white border-t border-slate-100 px-4 md:px-8 flex flex-col sm:flex-row items-center justify-end flex-shrink-0 z-10">
    <div class="flex flex-wrap items-center justify-end gap-2.5 w-full sm:w-auto">                  
        <button type="submit" name="action" value="save" id="submitBtn" class="fd px-5 py-2.5 bg-[#00BFA5] text-white font-semibold text-xs rounded-xl hover:bg-teal-500 shadow-sm transition-colors flex-1 sm:flex-none text-center">
            <span><i class="fa-regular fa-floppy-disk mr-1"></i> Save Customer</span>
        </button>
    </div>
</footer>
                

        </form>
    </div>
  
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerForm = document.getElementById('customerForm');
    const alertContainer = document.getElementById('alertContainer');
    const alertBox = document.getElementById('alertBox');
    const alertIcon = document.getElementById('alertIcon');
    const alertText = document.getElementById('alertText');
    const customerTableBody = document.getElementById('customerTableBody');
    const noCustomersRow = document.getElementById('noCustomersRow');
    const tableSearch = document.getElementById('tableSearch');
    
    // Timer variable to track the auto-hide state
    let alertTimeout = null;

    // 1. LIVE HEADER SEARCH MECHANISM
  // 1. LIVE HEADER SEARCH MECHANISM
if (tableSearch) {
    tableSearch.addEventListener('input', function() {
        const filterValue = this.value.toLowerCase().trim();
        const rows = customerTableBody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            if (rows[i].id === 'noCustomersRow') continue;

            // Target the specific container holding the name text
            const nameElement = rows[i].querySelector('.font-semibold.text-slate-800');
            
            if (nameElement) {
                const nameText = nameElement.textContent || nameElement.innerText;
                
                if (nameText.toLowerCase().includes(filterValue)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    });
}

    // 2. ASYNCHRONOUS FORM SUBMISSION
    if (customerForm) {
        customerForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const formData = new FormData(this);
            
            // Safe fallback value capture matching custom styled buttons
            let actionValue = 'save';
            const clickedButton = document.activeElement;
            if (clickedButton && clickedButton.name === 'action') {
                actionValue = clickedButton.value;
            } else {
                const fallbackBtn = document.getElementById('submitBtn');
                if (fallbackBtn) actionValue = fallbackBtn.value;
            }
            formData.append('action', actionValue);

            // Hide old alert instantly during execution transitions
            alertContainer.classList.add('hidden');
            if (alertTimeout) clearTimeout(alertTimeout);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);

                    if (data.customer) {
                        if (noCustomersRow) noCustomersRow.remove();

                        const newRow = document.createElement('tr');
                        newRow.className = "hover:bg-teal-50/40 transition-colors border-b border-slate-100 text-xs";
                        newRow.innerHTML = `
                            <td class="p-4 font-semibold text-center text-slate-400">${data.customer.id}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 text-white flex items-center justify-center font-bold text-sm">
                                        ${data.customer.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <div class="customer-name font-semibold text-slate-800">${escapeHtml(data.customer.full_name)}</div>
                                        <div class="text-[10px] text-slate-400">Customer ID #${data.customer.id}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 font-medium text-slate-600">${escapeHtml(data.customer.phone)}</td>
                            <td class="p-4 text-slate-500 max-w-[200px] truncate">${escapeHtml(data.customer.address)}</td>
                            <td class="p-4 text-slate-500">${data.customer.joined}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wide">Active</span>
                            </td>
                            <td class="p-4">
                              // Locate this section inside your newRow.innerHTML backticks and update the icons:
<div class="flex justify-center gap-1.5">
    <a href="view-customer.php?id=${data.customer.id}" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors">
        <i class="fa-solid fa-eye text-xs"></i>
    </a>
    <a href="edit-customer.php?id=${data.customer.id}" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center hover:bg-amber-100 transition-colors">
        <i class="fa-solid fa-pen-to-square text-xs"></i>
    </a>
    <a href="customers.php?id=${data.customer.id}" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-100 transition-colors" onclick="return confirm('Are you sure you want to delete this customer?');">
        <i class="fa-solid fa-trash-can text-xs"></i>
    </a>
</div>
                            </td>
                        `;
                        customerTableBody.insertBefore(newRow, customerTableBody.firstChild);
                    }

                    customerForm.reset();
                } else {
                    showAlert('error', data.message || 'Unknown error occurred.');
                }
            })
            .catch(error => {
                console.error('Submission error:', error);
                showAlert('error', 'Something went wrong with the connection.');
            });
        });
    }

    // 3. DYNAMIC ALERT DISPLAY WITH AUTO-HIDE TIMEOUT
    function showAlert(type, text) {
        alertText.textContent = text;
        alertContainer.classList.remove('hidden');

        if (type === 'success') {
            alertBox.className = "p-4 rounded-xl text-xs font-semibold flex items-center gap-2 bg-emerald-50 text-emerald-800 border border-emerald-200";
            alertIcon.className = "fa-solid fa-circle-check text-sm";
            
            // Clear old timer if user hits submit multiple times quickly
            if (alertTimeout) clearTimeout(alertTimeout);
            
            // Automatically hide the message after 3000ms (3 seconds)
            alertTimeout = setTimeout(() => {
                alertContainer.classList.add('hidden');
            }, 3000);

        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-semibold flex items-center gap-2 bg-rose-50 text-rose-800 border border-rose-200";
            alertIcon.className = "fa-solid fa-circle-exclamation text-sm";
            
            // Keep error messages open longer (5 seconds) so users can read what went wrong
            if (alertTimeout) clearTimeout(alertTimeout);
            alertTimeout = setTimeout(() => {
                alertContainer.classList.add('hidden');
            }, 5000);
        }
    }

    function escapeHtml(string) {
        if (!string) return '';
        return String(string).replace(/[&<>"']/g, function (s) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s];
        });
    }
});
</script>

<!-- Ensure your header search input field matches this exactly (add id="tableSearch") -->
<script>
    // Just a quick check to make sure your header input element has id="tableSearch" assigned to it
    const inputElement = document.querySelector('input[placeholder*="Search medicines"]');
    if (inputElement && !inputElement.id) {
        inputElement.id = 'tableSearch';
    }
</script>
</body>
</html>