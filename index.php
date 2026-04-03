<?php
/**
 * Vecino Seguro - Corporate Gateway
 * ERP + CRM + Catalogo Nativo
 */
require_once __DIR__ . '/src/config/config.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vecino Seguro Sistemas | ERP + CRM + Catálogo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #020617;
            color: white;
            overflow-x: hidden;
        }

        .hero-gradient {
            background: radial-gradient(circle at 50% -20%, #1e293b 0%, #020617 100%);
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .module-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .module-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(59, 130, 246, 0.05);
            box-shadow: 0 10px 40px -10px rgba(59, 130, 246, 0.3);
        }

        .text-gradient {
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-premium {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>

<body class="antialiased">

    <!-- Hero Section -->
    <section class="min-h-screen flex flex-col items-center justify-center relative px-6 hero-gradient">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-purple-500/10 rounded-full blur-[100px]">
            </div>
        </div>

        <div class="max-w-6xl w-full text-center z-10 space-y-12 py-20">
            <div class="flex flex-col items-center gap-6 animate-in fade-in zoom-in duration-700">
                <img src="src/img/VSLogo_v2.jpg" alt="VS Logo"
                    class="h-24 md:h-28 drop-shadow-2xl hover:scale-105 transition-transform">
                <h1 class="text-4xl md:text-7xl font-extrabold tracking-tight text-gradient leading-tight py-2">
                    Vecino Seguro Sistemas
                </h1>
                <p class="text-xl md:text-2xl font-medium text-blue-400 uppercase tracking-[0.3em] opacity-80">
                    ERP + CRM + Catálogo Nativo
                </p>
            </div>

            <!-- Module Grid -->
            <div
                class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 text-center animate-in fade-in slide-in-from-bottom-10 duration-1000 delay-150">
                <?php
                $modules = [
                    ['name' => 'Ventas & CRM', 'status' => 'Actual', 'icon' => 'query_stats'],
                    ['name' => 'Catálogo', 'status' => 'Actual', 'icon' => 'inventory_2'],
                    ['name' => 'Compras', 'status' => 'Actual', 'icon' => 'shopping_cart'],
                    ['name' => 'Logística', 'status' => 'Actual', 'icon' => 'local_shipping'],
                    ['name' => 'Facturación', 'status' => 'Actual', 'icon' => 'receipt_long'],
                    ['name' => 'Tesorería', 'status' => 'Actual', 'icon' => 'account_balance_wallet'],
                    ['name' => 'RRHH', 'status' => 'Próximamente', 'icon' => 'groups'],
                    ['name' => 'Marketing', 'status' => 'Próximamente', 'icon' => 'campaign'],
                    ['name' => 'Gestión Calidad', 'status' => 'Próximamente', 'icon' => 'verified'],
                    ['name' => 'BuroSE', 'status' => 'Actual', 'icon' => 'credit_score', 'url' => 'https://www.burose.com.ar'],
                    ['name' => 'RMA & Garantías', 'status' => 'Próximamente', 'icon' => 'build'],
                    ['name' => 'Proyectos', 'status' => 'Próximamente', 'icon' => 'account_tree'],
                    ['name' => 'Análisis Pliegos', 'status' => 'Próximamente', 'icon' => 'description'],
                    ['name' => 'Competencia', 'status' => 'Actual', 'icon' => 'monitoring'],
                    ['name' => 'Informes Glob.', 'status' => 'Actual', 'icon' => 'public'],
                ];

                foreach ($modules as $m):
                    $isFuture = $m['status'] === 'Próximamente';
                    $hasUrl = isset($m['url']) && !empty($m['url']);
                    ?>
                    <<?php echo $hasUrl ? 'a href="' . $m['url'] . '" target="_blank"' : 'div'; ?>
                        class="glass p-6 rounded-2xl module-card
                        <?php echo $isFuture ? 'opacity-40 grayscale' : 'border-blue-500/30'; ?>">
                        <div class="flex flex-col items-center gap-3">
                            <span
                                class="material-symbols-outlined text-3xl <?php echo $isFuture ? 'text-slate-500' : 'text-blue-500'; ?> transition-colors"><?php echo $m['icon']; ?></span>
                            <h3 class="text-[11px] font-bold uppercase tracking-wider"><?php echo $m['name']; ?></h3>
                            <span
                                class="text-[9px] font-black px-2 py-0.5 rounded-full <?php echo $isFuture ? 'bg-slate-800 text-slate-500' : 'bg-blue-500/10 text-blue-400'; ?>">
                                <?php echo $m['status']; ?>
                            </span>
                        </div>
                    </<?php echo $hasUrl ? 'a' : 'div'; ?>>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div
                class="flex flex-col md:flex-row gap-6 justify-center pt-8 animate-in fade-in zoom-in duration-300 delay-500">
                <a href="catalogo_publico.php" target="_blank"
                    class="bg-white text-black px-12 py-5 rounded-xl font-black text-lg btn-premium flex items-center justify-center gap-3 shadow-2xl">
                    <span class="material-symbols-outlined">auto_stories</span> ABRIR CATÁLOGO PÚBLICO
                </a>
                <a href="dashboard.php"
                    class="bg-blue-600 text-white px-12 py-5 rounded-xl font-black text-lg btn-premium flex items-center justify-center gap-3 shadow-2xl">
                    <span class="material-symbols-outlined">login</span> ACCESO SISTEMA VS
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-white/5 text-center space-y-4 bg-[#020617]">
        <p class="text-slate-500 font-bold tracking-widest text-xs uppercase">VS Sistemas by Javier Gozzi</p>
        <div class="flex flex-col md:flex-row items-center justify-center gap-6">
            <a href="https://wa.me/5491138891414" target="_blank"
                class="flex items-center gap-2 text-slate-400 hover:text-green-500 transition-colors">
                <span class="material-symbols-outlined text-sm">chat</span> +54 9 11 3889 1414
            </a>
            <a href="mailto:vecinoseguro0@gmail.com"
                class="flex items-center gap-2 text-slate-400 hover:text-blue-500 transition-colors">
                <span class="material-symbols-outlined text-sm">mail</span> vecinoseguro0@gmail.com
            </a>
        </div>
        <p class="text-[10px] text-slate-600 font-medium">Soft exclusivo para Gremio de la Seguridad Electronica y
            afines.</p>
    </footer>

    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
</body>

</html>