<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCare Pharmacy - Edit Medicine</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .flip-vertical-right {
	-webkit-animation: flip-vertical-right 0.4s cubic-bezier(0.455, 0.030, 0.515, 0.955) both;
	        animation: flip-vertical-right 0.4s cubic-bezier(0.455, 0.030, 0.515, 0.955) both;
}
        /* Custom styles for global search animation readiness if needed */
        .slide-out-left {
            -webkit-animation: slide-out-left 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
            animation: slide-out-left 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
        }
        /* 1. The Keyframes Definition */
@-webkit-keyframes flip-vertical-right {
  0% {
    -webkit-transform: rotateY(0);
            transform: rotateY(0);
  }
  100% {
    -webkit-transform: rotateY(180deg);
            transform: rotateY(180deg);
  }
}

@keyframes flip-vertical-right {
  0% {
    -webkit-transform: rotateY(0);
            transform: rotateY(0);
  }
  100% {
    -webkit-transform: rotateY(180deg);
            transform: rotateY(180deg);
  }
}
.heartbeat {
	-webkit-animation: heartbeat 1.5s ease-in-out infinite both;
	        animation: heartbeat 1.5s ease-in-out infinite both;
}
/* WebKit prefix for older browser compatibility */
@-webkit-keyframes heartbeat {
  0% {
    -webkit-transform: scale(1);
            transform: scale(1);
    -webkit-transform-origin: center center;
            transform-origin: center center;
    -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
  }
  10% {
    -webkit-transform: scale(0.91);
            transform: scale(0.91);
    -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
  }
  17% {
    -webkit-transform: scale(0.98);
            transform: scale(0.98);
    -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
  }
  33% {
    -webkit-transform: scale(0.87);
            transform: scale(0.87);
    -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
  }
  45% {
    -webkit-transform: scale(1);
            transform: scale(1);
    -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
  }
}

/* Standard Syntax */
@keyframes heartbeat {
  0% {
    transform: scale(1);
    transform-origin: center center;
    animation-timing-function: ease-out;
  }
  10% {
    transform: scale(0.91);
    animation-timing-function: ease-in;
  }
  17% {
    transform: scale(1.4);

    animation-timing-function: ease-out;
  }
  33% {
    transform: scale(0.87);
    animation-timing-function: ease-in;
  }
  45% {
    transform: scale(1);
    animation-timing-function: ease-out;
  }
}
.slide-in-fwd-top {
	-webkit-animation: slide-in-fwd-top 0.4s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
	animation: slide-in-fwd-top 0.4s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
} 

@-webkit-keyframes slide-in-fwd-top {
  0% {
    -webkit-transform: translateY(-1000px) translateZ(-1200px);
            transform: translateY(-1000px) translateZ(-1200px);
    opacity: 0;
  }
  100% {
    -webkit-transform: translateY(0) translateZ(0);
            transform: translateY(0) translateZ(0);
    opacity: 1;
  }
}

/* Standard Syntax */
@keyframes slide-in-fwd-top {
  0% {
    transform: translateY(-1000px) translateZ(-1200px);
    opacity: 0;
  }
  100% {
    transform: translateY(0) translateZ(0);
    opacity: 1;
  }
}
/* 2. Your Original Class (Optimized Tip) */
.flip-vertical-right {
	-webkit-animation: flip-vertical-right 0.4s cubic-bezier(0.455, 0.030, 0.515, 0.955) both;
	        animation: flip-vertical-right 0.4s cubic-bezier(0.455, 0.030, 0.515, 0.955) both;
  
  /* PRO-TIP: Adding perspective here gives it true 3D depth during the flip */
  perspective: 1000px; 
  backface-visibility: hidden; /* Stops the flip side from showing upside down if it's a card */
}

        @-webkit-keyframes slide-out-left {
            0% { -webkit-transform: translateX(0); transform: translateX(0); opacity: 1; }
            100% { -webkit-transform: translateX(-1000px); transform: translateX(-1000px); opacity: 0; }
        }
        @keyframes slide-out-left {
            0% { -webkit-transform: translateX(0); transform: translateX(0); opacity: 1; }
            100% { -webkit-transform: translateX(-1000px); transform: translateX(-1000px); opacity: 0; }
        }
    </style>
</head>
<body class="h-full text-slate-700 bg-slate-50 min-h-screen flex flex-col">

    <!-- MAIN BODY CONTAINER -->
    <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
        
        <!-- HEADER -->
        <header class="h-20 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shrink-0 gap-4">
            <div class="flex items-center flex-1 max-w-xl gap-3">
                <div class="border-r border-slate-200 pr-3 flex items-center">
                    <a href="http://life-care.lovestoblog.com/index.php" class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shadow-sm hover:bg-slate-200 transition shrink-0">
                        <img class="w-4 h-4" src="../assets/css/img/back.png" alt="Back">
                    </a>
                </div>
               
                <div class="relative w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" placeholder="Search medicines..." class="w-full pl-9 pr-16 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-mono font-medium text-slate-400 bg-white px-1.5 py-0.5 border border-slate-200 rounded hidden sm:inline-block">Ctrl + K</span>
                </div>
            </div>

            <div class="flex items-center space-x-3 sm:space-x-4">
                <button class="relative p-2 text-slate-500 hover:bg-slate-50 rounded-full hidden sm:block">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span data-animate="heartbeat" data-infinite="true" data-duration="4s" class="absolute top-1 right-1 w-4 h-4 bg-emerald-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">8</span>
                </button>
                <button class="relative p-2 text-slate-500 hover:bg-slate-50 rounded-full hidden sm:block">
                    <i class="fa-regular fa-comment-dots text-lg"></i>
                    <span data-animate="heartbeat" data-infinite="true" data-duration="4s" class="absolute top-1 right-1 w-4 h-4 bg-emerald-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">5</span>
                </button>
                <a href="new.php" data-animate="slide-in-fwd-top" data-duration="3s">
                <button data-animate="slide-in-fwd-top" data-duration="3s" class="bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-medium px-3 sm:px-4 py-2 rounded-lg flex items-center space-x-2 transition shrink-0">
                <img data-animate="flip-vertical-right" data-infinite="true" src="../assets/css/img/add.png" class="w-[15px]">
                    <span class="hidden sm:inline">Add New</span>
                </button>
            </a>
                <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                <div class="flex items-center space-x-2 cursor-pointer shrink-0">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100" class="w-8 h-8 rounded-full object-cover" alt="User">
                    <div class="hidden md:block text-left">
                        <p class="text-xs font-semibold text-slate-800 leading-none">Ahmed Mostafa</p>
                        <span class="text-[10px] text-slate-400">Pharmacist</span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden sm:inline-block"></i>
                </div>
            </div>
        </header>

        <!-- CONTENT SCROLL WRAPPER -->
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto max-w-7xl w-full mx-auto">
            
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 text-xs font-medium text-slate-400 mb-2">
                <a href="#" class="text-emerald-600 hover:underline">Medicines</a>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                <span>Edit Medicine</span>
            </nav>

            <!-- Title Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Edit Medicine</h2>
                <p class="text-sm text-slate-500">Update medicine information and quantity</p>
            </div>

            <!-- TWO COLUMN LAYOUT -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- LEFT FORM COLUMN (2/3 width) -->
                <form class="lg:col-span-2 space-y-6" onsubmit="event.preventDefault();">
                    
                    <!-- SECTION 1: BASIC INFORMATION -->
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center justify-start space-x-2 text-emerald-700 font-semibold text-sm mb-5 pb-3 border-b border-slate-100">
                            <img src="../assets/css/img/to-do-list.png" class="w-[24px] h-[24px] object-contain" alt="Icon">
                            <span>Basic Information</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Medicine Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Medicine Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" value="Augmentin" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-capsules absolute right-3 top-1/2 -translate-y-1/2 text-emerald-600/70 text-sm"></i>
                                </div>
                            </div>
                            <!-- Generic Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Generic Name</label>
                                <input type="text" value="Amoxicillin + Clavulanic Acid" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                            </div>
                            <!-- Category Dropdown -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select class="w-full pl-3 pr-10 py-2 text-sm border border-slate-200 rounded-lg bg-white appearance-none focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                        <option>Antibiotics</option>
                                        <option>Analgesics</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                            <!-- Manufacturer Dropdown -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Manufacturer <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select class="w-full pl-3 pr-10 py-2 text-sm border border-slate-200 rounded-lg bg-white appearance-none focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                        <option>GlaxoSmithKline (GSK)</option>
                                        <option>Pfizer</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                            <!-- Strength -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Strength <span class="text-red-500">*</span></label>
                                <input type="text" value="625 mg" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                            </div>
                            <!-- Unit Type Dropdown -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Type <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select class="w-full pl-3 pr-10 py-2 text-sm border border-slate-200 rounded-lg bg-white appearance-none focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                        <option>Tablet</option>
                                        <option>Syrup</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                                <textarea rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">Augmentin is an antibiotic used to treat bacterial infections. It works by stopping the growth of bacteria.</textarea>
                            </div>
                            <!-- Barcode -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Barcode</label>
                                <div class="relative">
                                    <input type="text" value="6221234567890" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: PRICING & STOCK INFORMATION -->
                    <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center space-x-2 text-emerald-700 font-semibold text-sm mb-5 pb-3 border-b border-slate-100">
                            <i class="fa-solid fa-wallet text-base"></i>
                            <span>Pricing & Stock Information</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <!-- Buying Price -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Buying Price (EGP) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" value="120.00" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-wallet absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            <!-- Selling Price -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price (EGP) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" value="160.00" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-tag absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            <!-- Profit Margin -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Profit Margin</label>
                                <div class="w-full px-3 py-2 text-sm border border-emerald-100 bg-emerald-50/50 rounded-lg text-emerald-700 font-semibold flex justify-between items-center h-[38px]">
                                    <span>33.33%</span>
                                    <i class="fa-solid fa-arrow-trend-up text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Current Stock -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Current Stock <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" value="45" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-box-archive absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            <!-- Min Stock Level -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Min. Stock Level</label>
                                <div class="relative">
                                    <input type="text" value="10" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-regular fa-bell absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>
                            <!-- Max Stock Level -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Max. Stock Level</label>
                                <div class="relative">
                                    <input type="text" value="100" class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <i class="fa-solid fa-layer-group absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2">
                        <button type="button" class="px-8 py-2.5 border border-slate-200 bg-white text-slate-700 font-medium text-sm rounded-lg hover:bg-slate-50 transition ordered-2 sm:order-1">Cancel</button>
                        <button type="submit" class="px-8 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg flex items-center justify-center space-x-2 transition min-w-[200px] order-1 sm:order-2">
                            <i class="fa-regular fa-floppy-disk"></i>
                            <span>Update Medicine</span>
                        </button>
                    </div>

                </form>

                <!-- RIGHT SIDEBAR COLUMN (1/3 width) -->
                <div class="space-y-6">
                    
                    <!-- MEDICINE PREVIEW CARD -->
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-bold text-slate-900 tracking-wide uppercase">Medicine Preview</h3>
                            <button class="text-slate-400 hover:text-slate-600"><i class="fa-regular fa-eye fa-lg"></i></button>
                        </div>
                        
                        <!-- Product Render Area -->
                        <div class="bg-slate-50 border border-slate-100 rounded-lg p-6 flex justify-center items-center mb-4 relative aspect-[4/3]">
                            <!-- Mock Medicine Box Graphic -->
                            <div class="w-44 bg-white border-2 border-slate-200 rounded shadow-md transform -rotate-2 p-3 flex flex-col justify-between relative overflow-hidden h-28">
                                <div class="absolute top-0 left-0 right-0 h-1.5 bg-emerald-600"></div>
                                <div>
                                    <div class="text-[11px] font-bold text-emerald-800 leading-tight">Augmentin</div>
                                    <div class="text-[9px] font-semibold text-emerald-600 flex items-center gap-1">625 mg <span class="text-[7px] bg-emerald-100 px-1 rounded-sm text-emerald-800">tabs</span></div>
                                    <div class="text-[6px] text-slate-400 mt-0.5 leading-none truncate">Amoxicillin + Clavulanic Acid</div>
                                </div>
                                <div class="flex justify-between items-end">
                                    <span class="text-[6px] text-slate-400 font-mono">14 Tablets</span>
                                    <span class="text-[8px] font-bold italic text-slate-500">gsk</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-baseline space-x-2 flex-wrap gap-y-1">
                            <h4 class="text-lg font-bold text-slate-900">Augmentin 625 mg</h4>
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 font-semibold rounded-full border border-emerald-100">Antibiotics</span>
                        </div>
                        <div class="flex items-center space-x-1.5 text-xs text-slate-400 mt-1">
                            <i class="fa-regular fa-building text-[10px]"></i>
                            <span>GlaxoSmithKline (GSK)</span>
                        </div>
                    </div>

                    <!-- STOCK OVERVIEW -->
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-slate-900 tracking-wide uppercase">Stock Overview</h3>
                            <a href="#" class="text-[11px] font-medium text-emerald-600 hover:underline">View History</a>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="bg-slate-50/50 border border-slate-100 p-3 rounded-lg flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0"><i class="fa-solid fa-box-archive text-xs"></i></div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-slate-900 leading-none">45</div>
                                    <span class="text-[10px] text-slate-400 block truncate">Current</span>
                                </div>
                            </div>
                            <div class="bg-slate-50/50 border border-slate-100 p-3 rounded-lg flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-500 shrink-0"><i class="fa-regular fa-bell text-xs"></i></div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-slate-900 leading-none">10</div>
                                    <span class="text-[10px] text-slate-400 block truncate">Min Level</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50/50 border border-slate-100 p-3 rounded-lg flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0"><i class="fa-solid fa-layer-group text-xs"></i></div>
                            <div>
                                <div class="text-base font-bold text-slate-900 leading-none">100</div>
                                <span class="text-[10px] text-slate-400">Max. Level</span>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT ACTIVITY -->
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-slate-900 tracking-wide uppercase">Recent Activity</h3>
                            <a href="#" class="text-[11px] font-medium text-emerald-600 hover:underline">View All</a>
                        </div>

                        <!-- Timeline -->
                        <div class="space-y-4 relative before:absolute before:bottom-2 before:top-2 before:left-4 before:w-0.5 before:bg-slate-100">
                            <!-- Act 1 -->
                            <div class="flex items-start space-x-3 relative">
                                <div class="w-8 h-8 rounded-full bg-emerald-50 border-2 border-white shadow-sm flex items-center justify-center text-emerald-600 shrink-0 z-10"><i class="fa-solid fa-pen text-[10px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-800 truncate">Medicine updated</p>
                                        <span class="text-[9px] text-slate-400 shrink-0">2m ago</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Price changed to 160 EGP</p>
                                </div>
                            </div>
                            <!-- Act 2 -->
                            <div class="flex items-start space-x-3 relative">
                                <div class="w-8 h-8 rounded-full bg-amber-50 border-2 border-white shadow-sm flex items-center justify-center text-amber-600 shrink-0 z-10"><i class="fa-solid fa-boxes-stacked text-[10px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-800 truncate">Stock updated</p>
                                        <span class="text-[9px] text-slate-400 shrink-0">15m ago</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Stock changed from 40 to 45</p>
                                </div>
                            </div>
                            <!-- Act 3 -->
                            <div class="flex items-start space-x-3 relative">
                                <div class="w-8 h-8 rounded-full bg-blue-50 border-2 border-white shadow-sm flex items-center justify-center text-blue-600 shrink-0 z-10"><i class="fa-solid fa-circle-info text-[10px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-semibold text-slate-800 truncate">Info updated</p>
                                        <span class="text-[9px] text-slate-400 shrink-0">1h ago</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">Manufacturer details altered</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LAST UPDATED BOX -->
                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4 flex items-start space-x-3">
                        <div class="text-emerald-600 text-base pt-0.5 shrink-0"><i class="fa-regular fa-calendar-check"></i></div>
                        <div>
                            <h4 class="text-xs font-bold text-emerald-800 mb-0.5">Last Updated</h4>
                            <p class="text-xs text-slate-700 font-medium">Today, 20 May 2025 at 10:30 AM</p>
                            <span class="text-[10px] text-slate-400">By Ahmed Mostafa</span>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- Intersection Observer Script -->
    <script>
        const observe = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const anim = el.getAttribute("data-animate");
                    const duration = el.getAttribute("data-duration") || "2s";
                    const delay = el.getAttribute("data-delay") || "0s";
                    const loopMode = el.getAttribute("data-infinite") === "true" ? "infinite" : "forwards";
                    el.style.animation = `${anim} ${duration} cubic-bezier(0.16, 1, 0.3, 1) ${delay} ${loopMode} forwards`;
                    observe.unobserve(el); 
                }
            });
        }, {
            threshold: 0.05
        });

        document.querySelectorAll('[data-animate]').forEach(el => {
            observe.observe(el);
        });
    </script>
</body>
</html>