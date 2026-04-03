<?php
require_once 'auth_check.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

$db = \Vsys\Lib\Database::getInstance();
?>
<!DOCTYPE html>
<html class="dark" lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes Gráficos - VS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="js/theme_handler.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#136dec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101822",
                        "surface-dark": "#16202e",
                        "surface-border": "#233348",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 1);
            border: 1px solid #e2e8f0;
        }

        .dark .glass-card {
            background: rgba(22, 32, 46, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #233348;
        }

        #map {
            height: 600px;
            width: 100%;
            border-radius: 1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Heatmap Gradient Reference */
        .color-legend {
            height: 10px;
            width: 100%;
            background: linear-gradient(to right, blue, cyan, lime, yellow, red);
            border-radius: 5px;
        }
    </style>
</head>

<body
    class="bg-white dark:bg-[#101822] text-slate-800 dark:text-white antialiased overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Top Header -->
            <header
                class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-[#233348] bg-white dark:bg-[#101822]/95 backdrop-blur z-[1000] sticky top-0 transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <button onclick="toggleVsysSidebar()" class="lg:hidden dark:text-white text-slate-800">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="bg-primary/20 p-2 rounded-lg text-primary">
                        <span class="material-symbols-outlined text-2xl">insert_chart</span>
                    </div>
                    <h2 class="dark:text-white text-slate-800 font-bold text-lg uppercase tracking-tight">Informes
                        Gráficos</h2>
                </div>
            </header>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8">
                <div class="max-w-7xl mx-auto space-y-8">

                    <!-- Top Summary Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div class="glass-card p-5 rounded-xl">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">Clientes
                                Geolocalizados</h3>
                            <p class="text-3xl font-black text-primary" id="count-geo-clients">...</p>
                        </div>
                        <div class="glass-card p-5 rounded-xl">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">Proveedores
                                Geolocalizados</h3>
                            <p class="text-3xl font-black text-red-500" id="count-geo-suppliers">...</p>
                        </div>
                        <div class="glass-card p-5 rounded-xl">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">Transportes
                                Geolocalizados</h3>
                            <p class="text-3xl font-black text-emerald-800" id="count-geo-transports">...</p>
                        </div>
                        <div class="glass-card p-5 rounded-xl">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">Zonas de
                                Influencia</h3>
                            <p class="text-3xl font-black text-emerald-500" id="count-zones">...</p>
                        </div>
                        <div class="glass-card p-5 rounded-xl">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">Efectividad por
                                Zona</h3>
                            <p class="text-3xl font-black text-amber-500">Optimum</p>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6 rounded-2xl">
                            <h3 class="font-bold text-lg mb-4">Clientes por Localidad</h3>
                            <div class="chart-container">
                                <canvas id="clientsLocalityChart"></canvas>
                            </div>
                        </div>
                        <div class="glass-card p-6 rounded-2xl">
                            <h3 class="font-bold text-lg mb-4">Proveedores por Localidad</h3>
                            <div class="chart-container">
                                <canvas id="suppliersLocalityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6 rounded-2xl flex flex-col justify-center items-center text-center">
                            <span class="material-symbols-outlined text-5xl text-emerald-500 mb-4">analytics</span>
                            <h3 class="font-bold text-xl mb-2">Análisis de Rentabilidad Global</h3>
                            <p class="text-slate-400 text-sm mb-6 max-w-sm">Consulte el informe detallado de ventas vs
                                costos y márgenes operativos promedio.</p>
                            <a href="reporte_rentabilidad.php"
                                class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-primary/20">VER
                                INFORME DE RENTABILIDAD</a>
                        </div>
                    </div>

                    <!-- Map Section -->
                    <div class="glass-card p-6 rounded-2xl relative">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <h3 class="font-bold text-xl flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">map</span>
                                    Mapa de Distribución y Calor
                                </h3>
                                <p class="text-slate-400 text-sm">Visualización de clientes (Azul), proveedores (Rojo),
                                    transportes (Verde) y
                                    densidad de compras.</p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="setMapMode('markers')"
                                    class="bg-primary text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-tight hover:bg-blue-600 transition-all shadow-lg shadow-primary/20">Marcadores</button>
                                <button onclick="setMapMode('heatmap')"
                                    class="bg-emerald-500 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-tight hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">Mapa
                                    de Calor</button>
                            </div>
                        </div>

                        <div id="map"></div>

                        <!-- Heatmap Legend -->
                        <div id="heatmap-legend" class="mt-4 hidden p-4 bg-slate-100 dark:bg-slate-800 rounded-lg">
                            <div class="flex justify-between text-[10px] font-bold uppercase text-slate-500 mb-1">
                                <span>Menor Actividad (Frío)</span>
                                <span>Mayor Actividad (Caliente)</span>
                            </div>
                            <div class="color-legend"></div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

    <script>
        let map, markerLayer, heatLayer;
        let mapMode = 'markers';
        let chartInstances = {};

        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            loadStats();
            loadMapData();
        });

        function initMap() {
            // Center in Argentina
            map = L.map('map').setView([-38.4161, -63.6167], 4);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            markerLayer = L.layerGroup().addTo(map);
        }

        async function loadStats() {
            try {
                const response = await fetch('ajax_reports.php?action=locality_stats');
                const data = await response.json();

                renderChart('clientsLocalityChart', data.clients, '#136dec');
                renderChart('suppliersLocalityChart', data.suppliers, '#ef4444');

                document.getElementById('count-zones').innerText = data.clients.length;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function renderChart(canvasId, data, color) {
            const ctx = document.getElementById(canvasId).getContext('2d');

            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();

            // Detect dark/light mode for tick color
            const isDark = document.documentElement.classList.contains('dark');
            const tickColor = isDark ? '#cbd5e1' : '#475569';
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';

            // Sanitize labels: handle null/empty locality
            const labels = data.map(d => {
                const loc = d.locality || 'Sin Localidad';
                // Truncate long names to 14 chars
                return loc.length > 14 ? loc.substring(0, 13) + '…' : loc;
            });

            chartInstances[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data.map(d => d.count),
                        backgroundColor: color + '33',
                        borderColor: color,
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    // Show full name in tooltip
                                    const idx = items[0].dataIndex;
                                    return data[idx].locality || 'Sin Localidad';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: tickColor,
                                font: { size: 11, weight: '600' },
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: tickColor,
                                font: { size: 11, weight: '600' },
                                maxRotation: 35,
                                minRotation: 0,
                                autoSkip: false
                            }
                        }
                    }
                }
            });
        }

        async function loadMapData() {
            try {
                const response = await fetch('ajax_reports.php?action=map_entities');
                const entities = await response.json();

                let cCount = 0, sCount = 0, tCount = 0;
                markerLayer.clearLayers();
                entities.forEach(e => {
                    let color = 'blue';
                    if (e.is_transport == 1) {
                        tCount++;
                        color = '#10b981'; // Green for Transports
                    } else if (e.type === 'client') {
                        cCount++;
                        color = 'blue';
                    } else {
                        sCount++;
                        color = 'red';
                    }

                    // Robust parsing for coordinates (handle strings and malformed formats like "lat,lng,zoom")
                    let lat = 0, lng = 0;
                    if (typeof e.lat === 'string' && e.lat.includes(',')) {
                        const parts = e.lat.split(',');
                        lat = parseFloat(parts[0]);
                        lng = parseFloat(parts[1]);
                    } else {
                        lat = parseFloat(e.lat);
                        lng = parseFloat(e.lng);
                    }

                    if (isNaN(lat) || isNaN(lng)) return;

                    const marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: color,
                        color: "#fff",
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).bindPopup(`<b>${e.name}</b><br>${e.address}<br>${e.city}`);

                    markerLayer.addLayer(marker);
                });

                document.getElementById('count-geo-clients').innerText = cCount;
                document.getElementById('count-geo-suppliers').innerText = sCount;
                document.getElementById('count-geo-transports').innerText = tCount;

                // Load Heatmap data in background
                const heatResponse = await fetch('ajax_reports.php?action=heatmap_data');
                const heatData = await heatResponse.json();

                const points = heatData.map(p => [p.lat, p.lng, p.weight]);
                heatLayer = L.heatLayer(points, {
                    radius: 25,
                    blur: 15,
                    maxZoom: 10,
                    gradient: { 0.4: 'blue', 0.65: 'lime', 1: 'yellow', 1.2: 'red' }
                });

                if (mapMode === 'heatmap') {
                    markerLayer.remove();
                    heatLayer.addTo(map);
                }
            } catch (error) {
                console.error('Error loading map data:', error);
            }
        }

        function setMapMode(mode) {
            mapMode = mode;
            if (mode === 'heatmap') {
                markerLayer.remove();
                if (heatLayer) heatLayer.addTo(map);
                document.getElementById('heatmap-legend').classList.remove('hidden');
            } else {
                if (heatLayer) heatLayer.remove();
                markerLayer.addTo(map);
                document.getElementById('heatmap-legend').classList.add('hidden');
            }
        }
    </script>
</body>

</html>