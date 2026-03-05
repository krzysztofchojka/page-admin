<?php
function getTemplatePreviewHtml($rawHtml) {
    $html = preg_replace('/\{\{(navigator|admin_navigator|menu)\}\}/', '<div style="background:#1e293b; color:#fff; padding:10px; text-align:center; font-family:sans-serif; margin:0; display:block; width:100%; font-size:12px; font-weight:bold;">[ Element Nawigacyjny ]</div>', $rawHtml);
    $html = preg_replace('/\{\{zone:([a-zA-Z0-9_]+)\}\}/', '<div style="border:2px dashed #cbd5e1; background:#f8fafc; padding:20px; text-align:center; color:#64748b; border-radius:8px; font-family:sans-serif; margin:10px 0; font-weight:bold;">Strefa: $1</div>', $html);
    $html = str_replace('{{global_footer}}', '<div style="background:#f1f5f9; padding:20px; text-align:center; color:#64748b; font-size:12px; margin:0; display:block; font-family:sans-serif;">[ Globalna Stopka ]</div>', $html);
    
    // ZWRACAMY CDN TAILWINDA SPECJALNIE DLA IFRAME
    return '<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"></script><style>body { margin: 0; padding: 0; }</style></head><body class="bg-white antialiased text-gray-800">' . $html . '</body></html>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zarządzaj Szablonami</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .preview-iframe {
            width: 400%; height: 400%; transform: scale(0.25); transform-origin: top left; pointer-events: none;
        }
        /* Loader spinner animation */
        .spinner {
            border: 3px solid rgba(203, 213, 225, 0.5); /* gray-300 */
            border-top: 3px solid #3b82f6; /* blue-500 */
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-gray-800">Szablony Stron</h1>
                <select id="filter-status" onchange="filterTemplates()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-bold text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <option value="all">Wszystkie</option>
                    <option value="1">Tylko Aktywne</option>
                    <option value="0">Tylko Wyłączone</option>
                </select>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="/admin" class="text-gray-600 hover:text-gray-900 font-medium">Wróć do Dashboardu</a>
                <div class="bg-white border border-gray-300 rounded-lg flex p-1 shadow-sm">
                    <button onclick="setViewMode('list')" id="btn-view-list" class="px-3 py-1.5 rounded text-sm font-bold text-gray-500 hover:text-gray-800 transition focus:outline-none">☰ Lista</button>
                    <button onclick="setViewMode('grid')" id="btn-view-grid" class="px-3 py-1.5 rounded text-sm font-bold text-gray-500 hover:text-gray-800 transition focus:outline-none">⊞ Siatka</button>
                </div>
                <a href="/admin/templates/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm transition">+ Utwórz Nowy</a>
            </div>
        </div>

        <?php if (empty($templates)): ?>
            <div class="bg-white p-10 text-center rounded-xl shadow-sm border border-gray-200 text-gray-500">
                Brak zapisanych szablonów.
            </div>
        <?php else: ?>

            <div id="view-list" class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6 hidden">
                <table class="min-w-full leading-normal text-left">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase">ID</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase">Nazwa Szablonu</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($templates as $template): ?>
                        <tr class="hover:bg-gray-50 transition template-item template-item-<?= $template['id'] ?> list-mode" data-active="<?= $template['is_active'] ?? 1 ?>">
                            <td class="px-5 py-4 text-sm text-gray-500 w-16"><?= $template['id'] ?></td>
                            <td class="px-5 py-4 text-sm badge-container">
                                <?php if($template['is_active'] ?? 1): ?>
                                    <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-1 rounded uppercase">Aktywny</span>
                                <?php else: ?>
                                    <span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase">Wyłączony</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-sm font-bold text-gray-800 title-text <?= !($template['is_active'] ?? 1) ? 'text-gray-400 line-through' : '' ?>"><?= htmlspecialchars($template['title']) ?></td>
                            <td class="px-5 py-4 text-sm text-right space-x-2">
                                <a href="/admin/templates/edit?id=<?= $template['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded transition">✏️ Edytuj HTML</a>
                                <button onclick="toggleTemplateStatus(<?= $template['id'] ?>)" class="toggle-btn px-3 py-1.5 rounded transition font-bold <?= ($template['is_active'] ?? 1) ? 'text-gray-600 bg-gray-100 hover:bg-gray-200' : 'text-green-700 bg-green-100 hover:bg-green-200' ?>">
                                    <?= ($template['is_active'] ?? 1) ? 'Wyłącz' : 'Aktywuj' ?>
                                </button>
                                <button onclick="deleteTemplate(<?= $template['id'] ?>)" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">🗑️ Usuń</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="view-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 hidden mb-6">
                <?php foreach ($templates as $template): ?>
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition border border-gray-200 overflow-hidden flex flex-col h-[330px] template-item template-item-<?= $template['id'] ?> grid-mode <?= !($template['is_active'] ?? 1) ? 'opacity-70 grayscale-[30%]' : '' ?>" data-active="<?= $template['is_active'] ?? 1 ?>">
                    
                    <div class="h-48 relative overflow-hidden bg-gray-100 border-b border-gray-200">
                        <div class="absolute inset-0 bg-gray-100 animate-pulse flex items-center justify-center z-10 iframe-loader">
                            <div class="spinner"></div>
                        </div>
                        <iframe onload="this.previousElementSibling.style.display='none'" srcdoc="<?= htmlspecialchars(getTemplatePreviewHtml($template['html_content']), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="preview-iframe absolute top-0 left-0 border-none z-0" scrolling="no"></iframe>
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between bg-white">
                        <div class="flex justify-between items-start mb-2">
                            <div class="overflow-hidden">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">ID: <?= $template['id'] ?></span>
                                <h3 class="font-bold text-base text-gray-800 leading-tight truncate title-text <?= !($template['is_active'] ?? 1) ? 'line-through' : '' ?>" title="<?= htmlspecialchars($template['title']) ?>">
                                    <?= htmlspecialchars($template['title']) ?>
                                </h3>
                            </div>
                            <div class="badge-container shrink-0 ml-2 mt-1">
                                <?php if(!($template['is_active'] ?? 1)): ?>
                                    <span class="bg-gray-200 text-gray-600 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Wyłączony</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-1.5 border-t border-gray-100 pt-3 mt-auto">
                            <a href="/admin/templates/edit?id=<?= $template['id'] ?>" class="w-full text-center text-blue-600 hover:text-blue-800 font-bold bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded transition text-xs">
                                ✏️ Edytuj kod HTML
                            </a>
                            <div class="flex justify-between items-stretch gap-1.5">
                                <button onclick="toggleTemplateStatus(<?= $template['id'] ?>)" class="toggle-btn flex-1 text-center font-bold px-2 py-1.5 rounded transition text-[11px] <?= ($template['is_active'] ?? 1) ? 'text-gray-600 bg-gray-100 hover:bg-gray-200' : 'text-green-700 bg-green-100 hover:bg-green-200' ?>">
                                    <?= ($template['is_active'] ?? 1) ? '✅ Aktywny (Wyłącz)' : '❌ Wyłączony (Aktywuj)' ?>
                                </button>
                                <button onclick="deleteTemplate(<?= $template['id'] ?>)" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition flex items-center justify-center text-xs" title="Usuń całkowicie">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <script>
    // --- NOWY SYSTEM POWIADOMIEŃ (TOAST) ---
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-red-600' : 'bg-green-600';
        const icon = type === 'error' ? '⚠️' : '✅';
        
        toast.className = `${bgColor} text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0 pointer-events-auto max-w-sm`;
        toast.innerHTML = `<span class="text-xl">${icon}</span><span class="font-bold text-sm leading-tight">${message}</span>`;
        
        container.appendChild(toast);

        // Animacja wjazdu
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-10', 'opacity-0');
        });

        // Animacja znikania i usunięcie elementu po 3.5s
        setTimeout(() => {
            toast.classList.add('opacity-0', 'scale-90');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // --- LOGIKA AJAX DLA STATUSU ---
    function toggleTemplateStatus(id) {
        const elements = document.querySelectorAll(`.template-item-${id}`);
        elements.forEach(el => el.classList.add('animate-pulse', 'pointer-events-none'));

        fetch('/admin/templates/toggleActive', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            elements.forEach(el => el.classList.remove('animate-pulse', 'pointer-events-none'));

            if (data.status === 'error') {
                showToast(data.message, 'error'); // ZMIANA Z ALERT
                return;
            }

            const isActive = data.is_active === 1;

            elements.forEach(item => {
                item.setAttribute('data-active', data.is_active);

                if (item.classList.contains('grid-mode')) {
                    const btn = item.querySelector('.toggle-btn');
                    if (isActive) {
                        item.classList.remove('opacity-70', 'grayscale-[30%]');
                        item.querySelector('.title-text').classList.remove('line-through');
                        item.querySelector('.badge-container').innerHTML = '';
                        btn.className = 'toggle-btn flex-1 text-center font-bold px-2 py-1.5 rounded transition text-[11px] text-gray-600 bg-gray-100 hover:bg-gray-200';
                        btn.innerHTML = '✅ Aktywny (Wyłącz)';
                    } else {
                        item.classList.add('opacity-70', 'grayscale-[30%]');
                        item.querySelector('.title-text').classList.add('line-through');
                        item.querySelector('.badge-container').innerHTML = '<span class="bg-gray-200 text-gray-600 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase mt-1 inline-block">Wyłączony</span>';
                        btn.className = 'toggle-btn flex-1 text-center font-bold px-2 py-1.5 rounded transition text-[11px] text-green-700 bg-green-100 hover:bg-green-200';
                        btn.innerHTML = '❌ Wyłączony (Aktywuj)';
                    }
                }

                if (item.classList.contains('list-mode')) {
                    const btn = item.querySelector('.toggle-btn');
                    if (isActive) {
                        item.querySelector('.title-text').classList.remove('text-gray-400', 'line-through');
                        item.querySelector('.badge-container').innerHTML = '<span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-1 rounded uppercase">Aktywny</span>';
                        btn.className = 'toggle-btn px-3 py-1.5 rounded transition font-bold text-gray-600 bg-gray-100 hover:bg-gray-200';
                        btn.innerHTML = 'Wyłącz';
                    } else {
                        item.querySelector('.title-text').classList.add('text-gray-400', 'line-through');
                        item.querySelector('.badge-container').innerHTML = '<span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase">Wyłączony</span>';
                        btn.className = 'toggle-btn px-3 py-1.5 rounded transition font-bold text-green-700 bg-green-100 hover:bg-green-200';
                        btn.innerHTML = 'Aktywuj';
                    }
                }
            });

            showToast(isActive ? 'Szablon aktywowany.' : 'Szablon wyłączony.', 'success');
            filterTemplates();
        })
        .catch(err => {
            console.error(err);
            showToast("Wystąpił błąd komunikacji z serwerem.", 'error'); // ZMIANA Z ALERT
            elements.forEach(el => el.classList.remove('animate-pulse', 'pointer-events-none'));
        });
    }

    // --- LOGIKA AJAX DLA USUWANIA ---
    function deleteTemplate(id) {
        if (!confirm('Czy na pewno chcesz trwale usunąć ten szablon? Tej operacji nie można cofnąć.')) {
            return;
        }

        const elements = document.querySelectorAll(`.template-item-${id}`);
        elements.forEach(el => el.classList.add('animate-pulse', 'pointer-events-none', 'opacity-50'));

        fetch('/admin/templates/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                elements.forEach(el => el.classList.remove('animate-pulse', 'pointer-events-none', 'opacity-50'));
                showToast(data.message, 'error'); // ZMIANA Z ALERT
            } else {
                showToast('Szablon usunięty pomyślnie.', 'success'); // ZMIANA Z ALERT
                elements.forEach(el => {
                    el.style.transition = 'all 0.3s ease';
                    el.style.transform = 'scale(0.9)';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                });
            }
        })
        .catch(err => {
            console.error(err);
            showToast("Wystąpił błąd komunikacji z serwerem.", 'error'); // ZMIANA Z ALERT
            elements.forEach(el => el.classList.remove('animate-pulse', 'pointer-events-none', 'opacity-50'));
        });
    }

    // --- LOGIKA WIDOKÓW I FILTRÓW ---
    function filterTemplates() {
        const status = document.getElementById('filter-status').value;
        document.querySelectorAll('.template-item').forEach(item => {
            const isActive = item.getAttribute('data-active');
            if (status === 'all' || status === isActive) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function setViewMode(mode) {
        const listEl = document.getElementById('view-list');
        const gridEl = document.getElementById('view-grid');
        const btnList = document.getElementById('btn-view-list');
        const btnGrid = document.getElementById('btn-view-grid');

        if (mode === 'grid') {
            listEl.classList.add('hidden'); gridEl.classList.remove('hidden');
            btnGrid.classList.replace('text-gray-500', 'bg-gray-200'); btnGrid.classList.add('text-gray-800');
            btnList.classList.replace('bg-gray-200', 'text-gray-500'); btnList.classList.remove('text-gray-800');
        } else {
            gridEl.classList.add('hidden'); listEl.classList.remove('hidden');
            btnList.classList.replace('text-gray-500', 'bg-gray-200'); btnList.classList.add('text-gray-800');
            btnGrid.classList.replace('bg-gray-200', 'text-gray-500'); btnGrid.classList.remove('text-gray-800');
        }
        localStorage.setItem('template_view_mode', mode);
    }

    document.addEventListener('DOMContentLoaded', () => {
        setViewMode(localStorage.getItem('template_view_mode') || 'grid');
        filterTemplates();
    });
</script>
</body>
</html>