<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Modules\CRM\CRM;

$crm = new CRM();
$today = date('Y-m-d');
$stats = $crm->getStats($today);

// Stages for Kanban (Material Symbols)
$stages = [
    'Nuevo' => ['icon' => 'star', 'color' => '#3b82f6', 'label' => 'Nuevos Leads'],
    'Contactado' => ['icon' => 'forum', 'color' => '#8b5cf6', 'label' => 'En Contacto'],
    'Presupuestado' => ['icon' => 'query_stats', 'color' => '#f59e0b', 'label' => 'Cotizados'],
    'Ganado' => ['icon' => 'check_circle', 'color' => '#10b981', 'label' => 'Ganados'],
    'Perdido' => ['icon' => 'cancel', 'color' => '#f43f5e', 'label' => 'Perdidos']
];
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Moderno - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "surface-dark": "#16202e",
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        .dark ::-webkit-scrollbar-track {
            background: #101822;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #233348;
        }

        .kanban-container {
            display: flex;
            gap: 1.25rem;
            padding-bottom: 1rem;
            min-height: calc(100vh - 350px);
        }

        .kanban-col {
            flex: 0 0 320px;
            @apply bg-slate-50 dark:bg-white/5 rounded-2xl p-4 border border-slate-200 dark:border-[#233348] flex flex-col gap-4;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-10 sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800 p-1 mr-2">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">dynamic_feed</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">CRM
                        Intelligent</h2>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="document.getElementById('newLeadModal').style.display='flex'"
                        class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-lg shadow-primary/20 active:scale-95">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        NUEVO LEAD
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-[1600px] mx-auto space-y-8">

                    <!-- Top Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <span class="material-symbols-outlined text-6xl">request_quote</span>
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Presupuestos
                                Activos</h4>
                            <div class="text-3xl font-bold dark:text-white text-slate-800">
                                <?php echo $stats['active_quotes']; ?>
                            </div>
                            <div class="mt-2 flex items-center gap-1 text-[10px] font-bold text-green-500 uppercase">
                                <span class="material-symbols-outlined text-sm">trending_up</span> Pipeline Saludable
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <div
                                class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity text-primary">
                                <span class="material-symbols-outlined text-6xl">shopping_cart_checkout</span>
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Cierres
                                Técnicos Hoy</h4>
                            <div class="text-3xl font-bold text-primary"><?php echo $stats['orders_today']; ?></div>
                            <div class="mt-2 text-[10px] font-bold text-slate-400 uppercase">Conversión diaria en tiempo
                                real</div>
                        </div>

                        <div
                            class="bg-white dark:bg-[#16202e] border border-slate-200 dark:border-[#233348] p-6 rounded-2xl shadow-sm dark:shadow-none transition-all group hover:border-primary/50 relative overflow-hidden">
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Eficiencia
                                de Ventas</h4>
                            <div class="text-3xl font-bold dark:text-white text-slate-800">
                                <?php echo $stats['efficiency']; ?>%
                            </div>
                            <div class="mt-4 w-full h-1.5 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all duration-1000"
                                    style="width: <?php echo $stats['efficiency']; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Follow-up Alerts -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- More dynamic alerts would be rendered here -->
                    </div>

                    <!-- Kanban Board -->
                    <div class="overflow-x-auto pb-4 custom-scrollbar">
                        <div class="kanban-container">
                            <?php foreach ($stages as $stage => $meta):
                                $leads = $crm->getLeadsByStatus($stage);
                                ?>
                                <div class="kanban-col">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-lg"
                                                style="color: <?php echo $meta['color']; ?>;"><?php echo $meta['icon']; ?></span>
                                            <h3
                                                class="text-xs font-bold dark:text-slate-400 text-slate-500 uppercase tracking-widest">
                                                <?php echo $meta['label']; ?>
                                            </h3>
                                        </div>
                                        <span
                                            class="bg-slate-200 dark:bg-white/10 px-2 py-0.5 rounded-full text-[10px] font-bold text-slate-500 dark:text-slate-400"><?php echo count($leads); ?></span>
                                    </div>

                                    <div class="flex flex-col gap-3 min-h-[50px]">
                                        <?php foreach ($leads as $lead): ?>
                                            <div onclick="openLead(<?php echo $lead['id']; ?>)"
                                                class="group bg-white dark:bg-[#101822] border border-slate-200 dark:border-white/5 p-4 rounded-xl hover:border-primary/50 dark:hover:border-primary/50 transition-all cursor-pointer shadow-sm hover:shadow-lg hover:shadow-primary/5 flex flex-col gap-2">
                                                <div class="flex justify-between items-start">
                                                    <h4
                                                        class="text-sm font-bold dark:text-white text-slate-800 group-hover:text-primary transition-colors leading-tight">
                                                        <?php echo $lead['name']; ?>
                                                    </h4>
                                                    <span
                                                        class="text-[9px] font-bold dark:text-slate-500 text-slate-400 uppercase"><?php echo date('d M', strtotime($lead['updated_at'])); ?></span>
                                                </div>

                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                                        <span
                                                            class="text-[10px] leading-none"><?php echo $lead['contact_person'] ?: 'Sin contacto'; ?></span>
                                                    </div>
                                                    <?php if ($lead['phone']): ?>
                                                        <div
                                                            class="flex items-center gap-2 text-green-500 font-medium leading-none">
                                                            <span class="material-symbols-outlined text-[14px]">smartphone</span>
                                                            <span
                                                                class="text-[10px] leading-none"><?php echo $lead['phone']; ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div
                                                    class="flex justify-between items-center mt-2 pt-2 border-t border-slate-50 dark:border-white/5 opacity-0 group-hover:opacity-100 transition-opacity">

                                                    <!-- Delete -->
                                                    <button
                                                        onclick="event.stopPropagation(); deleteLead(<?php echo $lead['id']; ?>, '<?php echo addslashes($lead['name']); ?>')"
                                                        class="p-1 hover:bg-red-500/10 rounded-lg text-slate-400 hover:text-red-500 transition-all"
                                                        title="Eliminar">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>

                                                    <div class="flex items-center gap-1">
                                                        <button
                                                            onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'prev')"
                                                            class="p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg text-slate-400 hover:text-primary transition-all">
                                                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                                                        </button>

                                                        <!-- Link to Quote if status is Presupuestado/Ganado -->
                                                        <?php if (in_array($lead['status'], ['Presupuestado', 'Ganado'])): ?>
                                                            <a href="presupuestos.php?search=<?php echo urlencode($lead['name']); ?>"
                                                                onclick="event.stopPropagation()"
                                                                class="p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg text-slate-400 hover:text-amber-500 transition-all"
                                                                title="Ver Presupuestos">
                                                                <span class="material-symbols-outlined text-lg">request_quote</span>
                                                            </a>
                                                        <?php endif; ?>

                                                        <button
                                                            onclick="event.stopPropagation(); moveLead(<?php echo $lead['id']; ?>, 'next')"
                                                            class="p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg text-slate-400 hover:text-primary transition-all">
                                                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if (empty($leads)): ?>
                                            <div
                                                class="py-12 flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-white/5 rounded-xl text-slate-300 dark:text-slate-800">
                                                <span class="material-symbols-outlined text-3xl">inbox</span>
                                                <span class="text-[10px] font-bold uppercase tracking-widest mt-2">Vacío</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Legajo Modal (Operation Folder) -->
    <div id="legajoModal"
        class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div
            class="bg-white dark:bg-[#16202e] w-full max-w-4xl max-h-[90vh] rounded-3xl overflow-hidden flex flex-col shadow-2xl border border-slate-200 dark:border-[#233348]">
            <div
                class="p-6 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-[#101822]/50">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined">folder_open</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl dark:text-white" id="legajoName">Legajo de Operación</h3>
                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest" id="legajoStatus">
                            Estado: Nuevo</p>
                    </div>
                </div>
                <button onclick="document.getElementById('legajoModal').style.display='none'"
                    class="p-2 hover:bg-slate-200 dark:hover:bg-white/10 rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Info & History -->
                    <div class="lg:col-span-2 space-y-8">
                        <div>
                            <h4
                                class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">history</span> Historial de Conversación
                            </h4>
                            <div class="space-y-4" id="interactionHistory">
                                <!-- Interacciones via JS -->
                            </div>
                        </div>

                        <div>
                            <h4
                                class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">compare_arrows</span> Estudios de
                                Competencia
                            </h4>
                            <div id="legajoCompetitor" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Análisis via JS -->
                            </div>
                        </div>

                        <!-- Add Interaction form -->
                        <div
                            class="bg-slate-50 dark:bg-[#101822] p-4 rounded-2xl border border-slate-200 dark:border-white/5">
                            <h4 class="text-[10px] font-bold text-primary uppercase tracking-widest mb-3">Registrar
                                Nueva Interacción</h4>
                            <div class="flex gap-2">
                                <input type="text" id="newInteractionDesc"
                                    placeholder="Escriba un resumen de lo conversado..."
                                    class="flex-1 bg-white dark:bg-[#16202e] border-slate-200 dark:border-[#233348] rounded-xl text-sm p-3">
                                <button onclick="saveInteraction()"
                                    class="bg-primary text-white p-3 rounded-xl hover:scale-105 transition-transform">
                                    <span class="material-symbols-outlined">send</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quotes & Links -->
                    <div class="space-y-6">
                        <div
                            class="bg-slate-50 dark:bg-[#101822] p-6 rounded-2xl border border-slate-200 dark:border-white/5">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Presupuestos
                                Vinculados</h4>
                            <div class="space-y-3" id="legajoQuotes">
                                <!-- Cotizaciones via JS -->
                            </div>
                        </div>

                        <div class="bg-primary/5 p-6 rounded-2xl border border-primary/10">
                            <h4 class="text-xs font-bold text-primary uppercase tracking-widest mb-4">Datos de Contacto
                            </h4>
                            <div class="space-y-4" id="legajoContact">
                                <!-- Datos via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="currentLeadId">

    <script>
        async function openLead(id) {
            document.getElementById('currentLeadId').value = id;
            const modal = document.getElementById('legajoModal');
            modal.style.display = 'flex';

            const resp = await fetch(`ajax_crm_details.php?id=${id}`);
            const data = await resp.json();

            if (!data.success) {
                alert(data.error);
                return;
            }

            document.getElementById('legajoName').innerText = data.lead.name;
            document.getElementById('legajoStatus').innerText = `Estado: ${data.lead.status}`;

            // Render Interactions
            const historyDiv = document.getElementById('interactionHistory');
            historyDiv.innerHTML = '';
            data.interactions.forEach(i => {
                const div = document.createElement('div');
                div.className = "flex gap-4 items-start";
                div.innerHTML = `
                    <div class="size-8 rounded-full bg-slate-200 dark:bg-white/10 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">${i.user_name.charAt(0)}</div>
                    <div class="flex-1 bg-slate-50 dark:bg-white/[0.03] p-3 rounded-2xl border border-slate-100 dark:border-white/5">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] font-bold text-primary uppercase">${i.type}</span>
                            <span class="text-[9px] text-slate-500">${i.interaction_date}</span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">${i.description}</p>
                    </div>
                `;
                historyDiv.appendChild(div);
            });
            if (data.interactions.length === 0) historyDiv.innerHTML = '<p class="text-xs text-slate-500 italic">No hay interacciones registradas.</p>';

            // Render Quotes
            const quotesDiv = document.getElementById('legajoQuotes');
            quotesDiv.innerHTML = '';
            data.quotations.forEach(q => {
                const div = document.createElement('div');
                div.className = "p-3 bg-white dark:bg-[#16202e] rounded-xl border border-slate-100 dark:border-white/5 flex justify-between items-center";
                div.innerHTML = `
                    <div>
                        <span class="text-xs font-bold dark:text-white">${q.quote_number}</span>
                        <p class="text-[10px] text-slate-500">$${q.total_usd} USD</p>
                    </div>
                    <a href="imprimir_cotizacion.php?id=${q.id}" target="_blank" class="p-2 text-primary hover:bg-primary/10 rounded-lg">
                        <span class="material-symbols-outlined text-lg">open_in_new</span>
                    </a>
                `;
                quotesDiv.appendChild(div);
            });
            if (data.quotations.length === 0) quotesDiv.innerHTML = '<p class="text-xs text-slate-500 italic">No hay presupuestos asociados.</p>';

            // Render Competitor Analyses
            const compDiv = document.getElementById('legajoCompetitor');
            compDiv.innerHTML = '';
            data.competitor_analyses.forEach(c => {
                const div = document.createElement('div');
                div.className = "p-4 bg-slate-50 dark:bg-white/5 rounded-2xl border border-slate-100 dark:border-white/5 flex justify-between items-center";
                div.innerHTML = `
                    <div>
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest block mb-1">Análisis</span>
                        <p class="text-xs font-bold dark:text-white">${c.analysis_number}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="analisis_competencia.php?id=${c.id}" class="p-2 text-slate-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg">edit_note</span>
                        </a>
                        <a href="imprimir_analisis_competencia.php?id=${c.id}" target="_blank" class="p-2 text-slate-400 hover:text-emerald-500 transition-colors">
                            <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        </a>
                    </div>
                `;
                compDiv.appendChild(div);
            });
            if (data.competitor_analyses.length === 0) compDiv.innerHTML = '<p class="text-xs text-slate-500 italic col-span-2">No se han realizado análisis de competencia.</p>';

            // Render Contact
            const contactDiv = document.getElementById('legajoContact');
            contactDiv.innerHTML = `
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Persona</p>
                    <p class="text-sm dark:text-white font-medium">${data.lead.contact_person || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Teléfono</p>
                    <p class="text-sm dark:text-white font-medium">${data.lead.phone || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Email</p>
                    <p class="text-sm dark:text-white font-medium truncate">${data.lead.email || 'N/A'}</p>
                </div>
            `;
        }

        async function saveInteraction() {
            const id = document.getElementById('currentLeadId').value;
            const desc = document.getElementById('newInteractionDesc').value;
            if (!desc) return;

            const formData = new FormData();
            formData.append('action', 'log_interaction');
            formData.append('entity_id', id);
            formData.append('entity_type', 'lead');
            formData.append('type', 'Nota Manual');
            formData.append('description', desc);
            formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);

            const resp = await fetch('ajax_crm_actions.php', { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                document.getElementById('newInteractionDesc').value = '';
                openLead(id);
            } else {
                alert(data.error);
            }
        }

        async function moveLead(id, direction) {
            const formData = new FormData();
            formData.append('action', 'move_lead');
            formData.append('id', id);
            formData.append('direction', direction);

            try {
                const resp = await fetch('ajax_crm_actions.php', { method: 'POST', body: formData });
                const res = await resp.json();
                if (res.success) location.reload();
                else alert(res.error);
            } catch (e) {
                console.error(e);
                alert('Error al mover lead');
            }
        }

        async function deleteLead(id, name) {
            if (!confirm(`¿Estás seguro de eliminar el lead "${name}"? Esta acción no se puede deshacer.`)) return;

            const formData = new FormData();
            formData.append('action', 'delete_lead');
            formData.append('id', id);

            try {
                const resp = await fetch('ajax_crm_actions.php', { method: 'POST', body: formData });
                const res = await resp.json();
                if (res.success) location.reload();
                else alert(res.error || 'Error al eliminar');
            } catch (e) {
                console.error(e);
                alert('Error de conexión');
            }
        }
    </script>
</body>

</html>