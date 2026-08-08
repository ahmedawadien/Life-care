
<!-- القائمة الجانبية (Sidebar) الموحدة والخالية من التعارضات -->
 <div id="sidebar" style="direction: ltr; width: 260px; background-color: #065f46;" class="my-custom-sidebar shadow py-4 px-3 text-white d-none d-lg-block">
    <!-- Logo / Brand Section & Close Button -->
    <div class="logo d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="d-flex align-items-center gap-3 px-1">
            <div class="d-flex align-items-center justify-content-center rounded-3 p-2" style="background-color: rgba(255, 255, 255, 0.15);">
                <img src="assets/css/img/pharmacy (1).png" style="width: 32px; height: 32px;" onerror="this.src='https://cdn-icons-png.flaticon.com/512/822/822143.png';">
            </div>
            <h3 class="m-0 fs-5 fw-bold text-white tracking-tight" style="font-family: 'Plus Jakarta Sans', sans-serif;">Pharmacy</h3>
        </div>
        <!-- زر إغلاق القائمة يظهر فقط على الهواتف -->
        <button id="close-sidebar" class="btn d-lg-none p-0 text-white border-0 opacity-75 hover-opacity-100">
            <i class="fa-solid fa-xmark fs-4"></i>
        </button>
    </div>

    <!-- Navigation Menu Layout -->
    <ul class="nav flex-column gap-1 flex-grow-1 ps-0 mb-0" style="list-style: none;">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link active d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all" style="color: #065f46; background-color: #ffffff; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-house fs-5" style="width: 24px; text-align: center;"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="data/medicines.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all green-sidebar-link" style="color: #a7f3d0; font-weight: 500;">
                <i class="fa-solid fa-pills fs-5" style="width: 24px; text-align: center;"></i>
                <span>Medicines</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="data/customers.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all green-sidebar-link" style="color: #a7f3d0; font-weight: 500;">
                <i class="fa-solid fa-users fs-5" style="width: 24px; text-align: center;"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="data/sales.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all green-sidebar-link" style="color: #a7f3d0; font-weight: 500;">
                <i class="fa-solid fa-cart-shopping fs-5" style="width: 24px; text-align: center;"></i>
                <span>Sales</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="reports/index.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all green-sidebar-link" style="color: #a7f3d0; font-weight: 500;">
                <i class="fa-solid fa-chart-line fs-5" style="width: 24px; text-align: center;"></i>
                <span>Reports</span>
            </a>
        </li>
    </ul>

    <!-- Bottom Separation Line for Logout Section -->
    <div class="pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <a href="logout.php" class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 transition-all logout-green-link" style="color: #fca5a5; font-weight: 500;">
            <i class="fa-solid fa-right-from-bracket fs-5" style="width: 24px; text-align: center;"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
