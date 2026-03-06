<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
        <h1 class="text-3xl font-bold text-gray-800">Statystyki Odwiedzin (Cookieless)</h1>
        
        <div class="flex gap-2">
            <button onclick="loadStats(7)" class="filter-btn bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-bold shadow-sm transition" data-days="7">7 Dni</button>
            <button onclick="loadStats(30)" class="filter-btn bg-blue-600 border border-blue-600 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition" data-days="30">30 Dni</button>
            <button onclick="loadStats(90)" class="filter-btn bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-bold shadow-sm transition" data-days="90">90 Dni</button>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <h2 class="text-lg font-bold text-gray-700 mb-4">Unikalne wyświetlenia stron (wg dnia)</h2>
        <div class="w-full h-[300px]">
            <canvas id="visitsChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-lg font-bold text-gray-700 mb-4">Kraje pochodzenia (Top 10)</h2>
            <div class="w-full h-[250px] flex justify-center">
                <canvas id="countriesChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h2 class="text-lg font-bold text-gray-700 mb-4">Najpopularniejsze podstrony</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 font-bold text-gray-600">Ścieżka (URL)</th>
                            <th class="px-4 py-3 font-bold text-gray-600 text-right">Odwiedziny</th>
                        </tr>
                    </thead>
                    <tbody id="top-pages-tbody" class="divide-y divide-gray-100">
                        <tr><td colspan="2" class="p-4 text-center text-gray-500">Ładowanie danych...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let visitsChartInstance = null;
let countriesChartInstance = null;

function loadStats(days) {
    // Aktualizacja guzików
    document.querySelectorAll('.filter-btn').forEach(btn => {
        if(parseInt(btn.dataset.days) === days) {
            btn.className = 'filter-btn bg-blue-600 border border-blue-600 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition';
        } else {
            btn.className = 'filter-btn bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-bold shadow-sm transition';
        }
    });

    fetch('/admin/stats/data?days=' + days)
        .then(res => res.json())
        .then(data => {
            renderVisitsChart(data.visits);
            renderCountriesChart(data.countries);
            renderTopPages(data.pages);
        });
}

function renderVisitsChart(data) {
    const ctx = document.getElementById('visitsChart').getContext('2d');
    const labels = data.map(item => item.date);
    const values = data.map(item => item.count);

    if (visitsChartInstance) visitsChartInstance.destroy();

    visitsChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Odwiedziny',
                data: values,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderCountriesChart(data) {
    const ctx = document.getElementById('countriesChart').getContext('2d');
    const labels = data.map(item => item.country_code);
    const values = data.map(item => item.count);

    if (countriesChartInstance) countriesChartInstance.destroy();

    countriesChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#64748b', '#ec4899', '#84cc16'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
}

function renderTopPages(data) {
    const tbody = document.getElementById('top-pages-tbody');
    tbody.innerHTML = '';
    if(data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="p-4 text-center text-gray-500">Brak danych</td></tr>';
        return;
    }
    data.forEach(item => {
        tbody.innerHTML += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-800 font-medium truncate max-w-[200px]" title="${item.page_url}">${item.page_url}</td>
                <td class="px-4 py-3 text-right font-bold text-blue-600">${item.count}</td>
            </tr>
        `;
    });
}

// Inicjalizacja przy załadowaniu (domyślnie 30 dni)
document.addEventListener('DOMContentLoaded', () => loadStats(30));
</script>