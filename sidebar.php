<?php
// sidebar.php - Rediseño con Menús Desplegables (Collapsible)
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/User.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
// normalize for configuration sections if needed, but handled by href matching usually

if (!isset($userAuth)) {
    $userAuth = new \Vsys\Lib\User();
}

$userName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Usuario');
$userRole = $_SESSION['role'] ?? 'Invitado';

// New Menu Structure
$menuStructure = [
    [
        'label' => 'VENTAS',
        'id' => 'ventas',
        'icon' => 'attach_money',
        'items' => [
            ['label' => 'Nueva Cotización', 'href' => 'cotizador.php', 'icon' => 'add_shopping_cart'],
            ['label' => 'Historial', 'href' => 'presupuestos.php', 'icon' => 'history'],
            ['label' => 'Perdidos', 'href' => 'presupuestos.php?view=perdidos', 'icon' => 'cancel'],
            ['label' => 'Listas de Precios', 'href' => 'listas_precios.php', 'icon' => 'price_check'],
            ['label' => 'CRM', 'href' => 'crm.php', 'icon' => 'group'],
            ['label' => 'Análisis Rentabilidad', 'href' => 'analisis.php', 'icon' => 'analytics'],
            ['label' => 'Análisis Competencia', 'href' => 'analisis_competencia.php', 'icon' => 'compare_arrows'],
            ['label' => 'Calendario', 'href' => 'https://calendar.google.com/calendar/u/0/r?cid=dmVjaW5vc2VndXJvMEBnbWFpbC5jb20', 'icon' => 'calendar_month', 'target' => '_blank'],
        ]
    ],
    [
        'label' => 'LOGÍSTICA',
        'id' => 'logistica_new',
        'icon' => 'local_shipping',
        'items' => [
            ['label' => 'En Armado', 'href' => 'logistica.php?view=armado', 'icon' => 'engineering'],
            ['label' => 'Pendientes', 'href' => 'logistica.php?view=pendientes', 'icon' => 'schedule'],
            ['label' => 'Archivados', 'href' => 'logistica.php?view=archivados', 'icon' => 'inventory_2'],
        ]
    ],
    [
        'label' => 'CONTABILIDAD',
        'id' => 'contabilidad',
        'icon' => 'account_balance',
        'items' => [
            ['label' => 'Compras', 'href' => 'compras.php', 'icon' => 'shopping_cart_checkout'],
            ['label' => 'Facturación', 'href' => 'facturacion.php', 'icon' => 'receipt'],
            ['label' => 'Ctas. Corrientes', 'href' => 'cuentas_corrientes.php', 'icon' => 'account_balance_wallet'],
            ['label' => 'Ctas. Corrientes Prov.', 'href' => 'cuentas_corrientes_proveedores.php', 'icon' => 'payments'],
            ['label' => 'Tesorería', 'href' => 'tesoreria.php', 'icon' => 'savings'],
        ]
    ],
    [
        'label' => 'INFORMES',
        'id' => 'informes_folder',
        'icon' => 'bar_chart',
        'items' => [
            ['label' => 'Mapa Clientes/Proveedores', 'href' => 'informes.php', 'icon' => 'map'],
            ['label' => 'Reporte Rentabilidad', 'href' => 'reporte_rentabilidad.php', 'icon' => 'trending_up'],
        ]
    ],
    [
        'label' => 'BASES DE DATOS',
        'id' => 'bdd',
        'icon' => 'database',
        'items' => [
            ['label' => 'Clientes', 'href' => 'clientes.php', 'icon' => 'groups'],
            ['label' => 'Proveedores', 'href' => 'proveedores.php', 'icon' => 'factory'],
            ['label' => 'Catálogo Gremio', 'href' => 'catalogo.php', 'icon' => 'engineering', 'target' => '_blank'],
        ]
    ],
];

?>

<!-- Theme Handler -->
<script src="js/theme_handler.js"></script>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
    rel="stylesheet" />
<style>
    body {
        text-transform: uppercase !important;
    }

    input,
    textarea,
    select,
    .normal-case {
        text-transform: none !important;
    }
</style>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[40] md:hidden hidden"
    onclick="toggleVsysSidebar()"></div>

<aside id="mainSidebar"
    class="fixed md:relative inset-y-0 left-0 w-64 h-full bg-white dark:bg-[#101822] border-r border-slate-200 dark:border-[#233348] flex-shrink-0 overflow-hidden transition-all duration-300 z-[50] -translate-x-full md:translate-x-0 flex flex-col">

    <!-- Header Logo -->
    <div class="p-6 flex items-center gap-3 flex-shrink-0">
        <div class="bg-[#136dec]/20 p-2 rounded-lg text-[#136dec] flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">shield</span>
        </div>
        <div>
            <h1 class="text-slate-800 dark:text-white text-lg font-bold leading-tight">VS System</h1>
            <p class="text-slate-400 text-[10px] font-normal uppercase tracking-wider">ERP & Seguridad</p>
        </div>
    </div>

    <!-- Scrollable Menu -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-2 space-y-2 custom-scrollbar">
        <!-- Dashboard Link (Always visible) -->
        <a href="dashboard.php"
            class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 <?php echo $currentPage === 'dashboard' ? 'bg-[#136dec] text-white shadow-lg shadow-[#136dec]/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-[#16202e] hover:text-[#136dec]'; ?>">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="font-bold text-sm">Inicio</span>
        </a>

        <?php foreach ($menuStructure as $section): ?>
            <div class="border border-slate-100 dark:border-[#233348] rounded-2xl overflow-hidden mb-2">
                <!-- Toggle Button -->
                <button onclick="toggleMenu('<?php echo $section['id']; ?>')"
                    class="w-full flex items-center justify-between px-4 py-3 bg-slate-50/50 dark:bg-[#16202e]/50 hover:bg-slate-100 dark:hover:bg-[#1c2a3b] transition-colors group">
                    <div class="flex items-center gap-3">
                        <span
                            class="material-symbols-outlined text-slate-400 group-hover:text-[#136dec] transition-colors"><?php echo $section['icon']; ?></span>
                        <span
                            class="font-bold text-xs text-slate-600 dark:text-slate-300 uppercase tracking-wider group-hover:text-[#136dec] transition-colors"><?php echo $section['label']; ?></span>
                    </div>
                    <!-- Toggle Icon (X / + / Chevron) -->
                    <span id="icon-<?php echo $section['id']; ?>"
                        class="material-symbols-outlined text-slate-400 text-lg transition-transform duration-300">expand_more</span>
                </button>

                <!-- Submenu Items -->
                <div id="menu-<?php echo $section['id']; ?>"
                    class="hidden bg-white dark:bg-[#101822] border-t border-slate-100 dark:border-[#233348]">
                    <?php foreach ($section['items'] as $item): ?>
                        <?php if (isset($item['role']) && !$userAuth->hasRole($item['role']))
                            continue; ?>

                        <?php
                        $isActive = ($currentPage === basename($item['href'], '.php'));
                        // Logic to keep menu open if active
                        if ($isActive) {
                            echo "<script>document.addEventListener('DOMContentLoaded', () => toggleMenu('{$section['id']}', true));</script>";
                        }
                        ?>

                        <a href="<?php echo $item['href']; ?>" <?php echo isset($item['target']) ? 'target="' . $item['target'] . '"' : ''; ?>
                            class="flex items-center gap-3 px-4 py-2.5 pl-11 text-sm font-medium transition-colors border-l-2 <?php echo $isActive ? 'border-[#136dec] text-[#136dec] bg-[#136dec]/5' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-[#136dec] hover:bg-slate-50 dark:hover:bg-[#16202e]'; ?>">
                            <!-- Optional: small dot or icon for subitems -->
                            <?php if ($isActive): ?>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#136dec] absolute left-6"></span>
                            <?php endif; ?>
                            <?php echo $item['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- User Profile Footer -->
    <div class="p-4 border-t border-slate-200 dark:border-[#233348] bg-slate-50/50 dark:bg-[#16202e]/50">
        <div class="flex items-center gap-3">
            <div
                class="size-10 rounded-full bg-[#136dec] flex items-center justify-center text-white shadow-lg shadow-[#136dec]/20">
                <span class="font-bold"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 dark:text-white truncate"><?php echo $userName; ?></p>
                <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $userRole; ?></p>
            </div>
            <div class="flex flex-col gap-1 items-center">
                <a href="configuration.php" class="text-slate-400 hover:text-[#136dec] mb-1" title="Configuración">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                </a>
                <div class="flex gap-2">
                    <button onclick="toggleVsysTheme()" class="text-slate-400 hover:text-[#136dec]" title="Tema">
                        <span class="material-symbols-outlined text-[18px] dark:hidden">dark_mode</span>
                        <span class="material-symbols-outlined text-[18px] hidden dark:block">light_mode</span>
                    </button>
                    <a href="logout.php" class="text-slate-400 hover:text-red-500" title="Salir">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleMenu(id, forceOpen = false) {
        const menu = document.getElementById('menu-' + id);
        const icon = document.getElementById('icon-' + id);

        if (!menu) return;

        if (forceOpen) {
            menu.classList.remove('hidden');
            icon.classList.add('rotate-180'); // Use rotate for chevron, or switch icon
            // User asked for "X". Let's change icon logic if requested, but rotate is cleaner.
            // If strictly "X":
            // icon.innerText = 'close';
            return;
        }

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    function toggleVsysTheme() {
        const current = localStorage.getItem('vsys_theme') || 'auto';
        let next = (current === 'dark' || (current === 'auto' && document.documentElement.classList.contains('dark'))) ? 'light' : 'dark';
        window.setVsysTheme(next);
    }

    function toggleVsysSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Auto-Logout Logic
    let inactivityTimeout;
    let countdownInterval;
    const INACTIVITY_TIME = 10 * 60 * 1000; // 10 minutes
    const COUNTDOWN_TIME = 60; // 60 seconds

    function resetInactivityTimer() {
        clearTimeout(inactivityTimeout);
        clearInterval(countdownInterval);
        hideLogoutModal();
        inactivityTimeout = setTimeout(startLogoutCountdown, INACTIVITY_TIME - (COUNTDOWN_TIME * 1000));
    }

    function startLogoutCountdown() {
        let timeLeft = COUNTDOWN_TIME;
        showLogoutModal(timeLeft);
        countdownInterval = setInterval(() => {
            timeLeft--;
            updateLogoutModal(timeLeft);
            if (timeLeft <= 0) {
                window.location.href = 'logout.php';
            }
        }, 1000);
    }

    function showLogoutModal(seconds) {
        let modal = document.getElementById('vsys-logout-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'vsys-logout-modal';
            modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4';
            modal.innerHTML = `
                <div class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl">
                    <div class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                        <span class="material-symbols-outlined text-4xl">timer</span>
                    </div>
                    <h2 class="text-xl font-bold dark:text-white text-slate-800 mb-2">¡Sesión por Caducar!</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Su sesión se cerrará automáticamente en <span id="vsys-logout-timer" class="font-bold text-amber-500">60</span> segundos debido a inactividad.</p>
                    <button onclick="resetInactivityTimer()" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:scale-105 transition-transform shadow-lg shadow-primary/20">SEGUIR TRABAJANDO</button>
                    <button onclick="window.location.href='logout.php'" class="w-full mt-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 font-bold text-xs uppercase tracking-widest">CERRAR SESIÓN AHORA</button>
                </div>
            `;
            document.body.appendChild(modal);
        }
        modal.classList.remove('hidden');
    }

    function updateLogoutModal(seconds) {
        const timerSpan = document.getElementById('vsys-logout-timer');
        if (timerSpan) timerSpan.innerText = seconds;
    }

    function hideLogoutModal() {
        const modal = document.getElementById('vsys-logout-modal');
        if (modal) modal.classList.add('hidden');
    }

    // Event listeners for activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, resetInactivityTimer, true);
    });

    // Initial start
    resetInactivityTimer();
</script>

<style>
    /* Custom Scrollbar for Sidebar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #334155;
    }
</style>