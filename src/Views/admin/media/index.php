<?php
$currentPath = $_GET['path'] ?? '';
// Bezpieczne ścieżki do obsługi w JS
$jsCurrentPath = htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menedżer Mediów</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <style>
        /* Proste animacje i poprawki dla trybu modalnego */
        .fade-in { animation: fadeIn 0.2s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        /* Ustalona wysokość dla kontenera Croppera */
        .img-container { max-height: 60vh; width: 100%; background-color: #f3f4f6; }
        /* Drag and Drop wskaźnik */
        .drag-over { border: 2px dashed #3b82f6 !important; background-color: #eff6ff !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans h-screen flex flex-col overflow-hidden" id="drop-zone">

<div class="flex flex-col h-full p-6">
    
    <div class="flex flex-wrap justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 gap-4">
        
    <div class="flex items-center gap-1 text-sm font-medium text-gray-600">
            <?php if (!isset($_GET['picker'])): ?>
                <a href="/admin" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md transition mr-2">🔙 Wróć</a>
            <?php endif; ?>
            
            <a href="/admin/media<?=(isset($_GET['picker']) ? '?picker='.$_GET['picker'] : '')?>" class="text-blue-600 hover:underline flex items-center gap-1 px-2 py-1 rounded transition border border-transparent"
               ondragover="dragOverBreadcrumb(event)" 
               ondragleave="dragLeaveBreadcrumb(event)" 
               ondrop="dropToFolder(event, '')">
                🏠 Media
            </a>
            
            <?php
            $parts = explode('/', $currentPath);
            $build = '';
            foreach ($parts as $part) {
                if (!$part) continue;
                $build .= $part . '/';
                $targetPath = trim($build, '/');
                
                // Każdy podfolder w ścieżce jako strefa zrzutu
                echo "<span class='text-gray-400'>/</span> 
                      <a href='?path=" . urlencode($targetPath) . (isset($_GET['picker']) ? '&picker=1' : '') . "' 
                         class='text-blue-600 hover:underline px-2 py-1 rounded transition border border-transparent'
                         ondragover='dragOverBreadcrumb(event)' 
                         ondragleave='dragLeaveBreadcrumb(event)' 
                         ondrop='dropToFolder(event, \"".htmlspecialchars($targetPath, ENT_QUOTES)."\")'>
                         $part
                      </a>";
            }
            ?>
        </div>

        <div class="flex items-center gap-3">
        <div id="bulk-actions" class="hidden items-center gap-2 mr-4 border-r pr-4 border-gray-200">
            <span class="text-xs font-bold text-gray-500"><span id="sel-count">0</span> zaznaczonych</span>
            <button onclick="downloadSelected()" class="bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 px-3 py-1.5 rounded-md text-sm font-bold transition" title="Pobierz">⬇️ Pobierz</button>
            
            <?php if (isset($_GET['picker'])): ?>
            <button onclick="sendMultipleToPicker()" class="bg-green-600 text-white hover:bg-green-700 px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm ml-2">
                ✅ Zatwierdź wybrane
            </button>
            <?php endif; ?>
        </div>

            <form method="GET" class="relative">
                <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
                <?php if (isset($_GET['picker'])): ?><input type="hidden" name="picker" value="1"><?php endif; ?>
                <input type="text" name="search" placeholder="Szukaj..." class="pl-3 pr-8 py-1.5 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500 text-sm w-48 transition" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </form>
            
            <button onclick="window.location.href='/admin/media/downloadZip?path=<?= urlencode($currentPath) ?>'" class="bg-green-50 text-green-700 border border-green-300 hover:bg-green-100 px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm" title="Pobierz ten folder jako ZIP">📦 ZIP</button>
            <button onclick="openFolderModal()" class="bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 px-4 py-1.5 rounded-md text-sm font-bold transition shadow-sm">📁 Nowy Folder</button>
            <button onclick="document.getElementById('uploadInput').click()" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-1.5 rounded-md text-sm font-bold transition shadow-sm relative overflow-hidden group">
                ☁️ Wgraj pliki
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition"></div>
            </button>
        </div>
    </div>

    <div id="progress-container" class="hidden mb-4 bg-white p-3 rounded-lg shadow-sm border border-blue-100 fade-in">
        <div class="flex justify-between text-xs font-bold text-blue-800 mb-1">
            <span>Wgrywanie plików...</span>
            <span id="progress-text">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-white p-6 rounded-xl shadow-inner border border-gray-200">
        <?php if (empty($files)): ?>
            <div class="h-full flex flex-col items-center justify-center text-gray-400">
                <div class="text-6xl mb-4">📂</div>
                <p>Ten folder jest pusty lub nie znaleziono plików.</p>
                <p class="text-sm mt-2">Przeciągnij i upuść pliki tutaj, aby je wgrać.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            <?php foreach ($files as $file): ?>
                    
                    <?php if ($file['type'] === 'folder'): ?>
                        <div class="group relative bg-gray-50 hover:bg-blue-50 border border-transparent hover:border-blue-400 rounded-xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center h-40 shadow-sm" 
                             ondblclick="location.href='?path=<?= urlencode($file['path']) ?><?= isset($_GET['picker']) ? '&picker=1' : '' ?>'"
                             ondragover="dragOverFolder(event)" 
                             ondragleave="dragLeaveFolder(event)" 
                             ondrop="dropToFolder(event, '<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>')">
                            
                            <div class="text-5xl mb-3 drop-shadow-sm transition-transform group-hover:scale-110 pointer-events-none">📁</div>
                            <div class="font-bold text-gray-700 text-sm truncate w-full px-2 pointer-events-none"><?= htmlspecialchars($file['name']) ?></div>
                            
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition flex gap-1 z-10">
                                <button onclick="downloadFolderZip('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Pobierz folder jako ZIP">📦</button>
                                
                                <button onclick="openRenameModal('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-blue-600" title="Zmień nazwę">✏️</button>
                                <button onclick="deleteItem('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-red-600" title="Usuń">🗑</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php 
                            $isImg = str_starts_with($file['mime'], 'image/'); 
                            $isPdf = $file['mime'] === 'application/pdf';
                            $isTxt = in_array($file['mime'], ['text/plain', 'text/html', 'text/css', 'application/json', 'application/xml', 'text/javascript']);
                        ?>
                        <div class="group relative bg-white border border-gray-200 hover:border-blue-400 rounded-xl p-3 text-center transition flex flex-col h-40 shadow-sm cursor-grab active:cursor-grabbing"
                             draggable="true" 
                             ondragstart="dragStartFile(event, '<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>')">
                            
                            <input type="checkbox" class="file-checkbox absolute top-2 left-2 z-10 w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer" value="<?= htmlspecialchars($file['relative']) ?>" data-url="<?= htmlspecialchars($file['url']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" onclick="updateBulkActions(event)">

                            <div class="flex-1 flex items-center justify-center overflow-hidden w-full h-24 mb-2 rounded-lg bg-gray-50 pointer-events-none">
                                <?php if ($isImg): ?>
                                    <img src="<?= htmlspecialchars($file['url']) ?>" class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition-transform" loading="lazy">
                                <?php else: ?>
                                    <div class="text-4xl drop-shadow-sm transition-transform group-hover:scale-110">
                                        <?= $isPdf ? '📕' : ($isTxt ? '📝' : '📄') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="font-bold text-gray-700 text-xs truncate w-full pointer-events-none" title="<?= htmlspecialchars($file['name']) ?>"><?= htmlspecialchars($file['name']) ?></div>
                            <div class="text-gray-400 text-[10px] mt-1 pointer-events-none"><?= $file['size'] ?></div>

                            <div class="absolute inset-0 z-0" onclick="handleFileClick('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', <?= $isImg ? 'true' : 'false' ?>, <?= $isPdf ? 'true' : 'false' ?>)"></div>

                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition flex gap-1 z-10">
                                <?php if ($isImg): ?>
                                    <button onclick="openImageEditor('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-purple-600" title="Kadruj/Obróć">✂️</button>
                                <?php endif; ?>
                                <?php if ($isTxt): ?>
                                    <button onclick="openTextEditor('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Edytuj plik">✏️</button>
                                <?php endif; ?>
                                <button onclick="openRenameModal('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-blue-600" title="Zmień nazwę">📝</button>
                                <button onclick="deleteItem('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-red-600" title="Usuń">🗑</button>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<input type="file" id="uploadInput" multiple hidden>

<div id="dynamic-modal" class="hidden fixed inset-0 bg-black/80 flex justify-center items-center z-50 fade-in p-4 backdrop-blur-sm" onclick="closeDynamicModal(event)">
    </div>

<div id="folder-modal" class="hidden fixed inset-0 bg-black/50 flex justify-center items-center z-[60] fade-in backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Nowy Folder</h3>
            <button onclick="document.getElementById('folder-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        </div>
        <form onsubmit="submitFolderCreate(event)" class="p-5">
            <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
            <label class="block text-sm font-bold text-gray-600 mb-2">Nazwa folderu</label>
            <input type="text" name="name" required class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" placeholder="np. dokumenty">
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-md hover:bg-blue-700 transition">Utwórz</button>
        </form>
    </div>
</div>

<div id="rename-modal" class="hidden fixed inset-0 bg-black/50 flex justify-center items-center z-[60] fade-in backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Zmień nazwę</h3>
            <button onclick="document.getElementById('rename-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        </div>
        <form onsubmit="submitRename(event)" class="p-5">
            <input type="hidden" name="old" id="rename-old-path">
            <label class="block text-sm font-bold text-gray-600 mb-2">Nowa nazwa</label>
            <input type="text" name="new" id="rename-new-name" required class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-md hover:bg-blue-700 transition">Zapisz</button>
        </form>
    </div>
</div>

<div id="editor-modal" class="hidden fixed inset-0 bg-black/60 flex justify-center items-center z-[70] fade-in backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-[80vh] flex flex-col overflow-hidden">
        <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
            <h3 class="font-bold text-gray-800" id="editor-title">Edycja pliku</h3>
            <button onclick="document.getElementById('editor-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        </div>
        <div class="flex-1 p-4 bg-gray-100">
            <textarea id="file-editor-area" class="w-full h-full font-mono text-sm p-4 border border-gray-300 rounded-md shadow-inner focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="bg-gray-50 border-t p-4 flex justify-end gap-3">
            <button onclick="document.getElementById('editor-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded">Anuluj</button>
            <button onclick="saveTextFile()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow-sm">Zapisz zmiany na serwerze</button>
        </div>
    </div>
</div>

<div id="cropper-modal" class="hidden fixed inset-0 bg-black/90 flex justify-center items-center z-[80] fade-in p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden">
        <div class="bg-gray-900 border-b border-gray-700 p-4 flex justify-between items-center text-white">
            <h3 class="font-bold" id="cropper-title">Kadrowanie Zdjęcia</h3>
            <button onclick="closeCropper()" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
        </div>
        <div class="img-container">
            <img id="cropper-image" src="" alt="Obraz do edycji">
        </div>
        <div class="bg-gray-100 border-t p-4 flex justify-between items-center flex-wrap gap-4">
            <div class="flex gap-2">
                <button onclick="cropper.rotate(-90)" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold">↺ Obróć w lewo</button>
                <button onclick="cropper.rotate(90)" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold">↻ Obróć w prawo</button>
                <button onclick="cropper.reset()" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold">Reset</button>
            </div>
            <div class="flex gap-2">
                <button onclick="closeCropper()" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded">Anuluj</button>
                <button onclick="saveCroppedImage(true)" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 shadow-sm">Zapisz kopię</button>
                <button onclick="saveCroppedImage(false)" id="btn-save-crop" class="px-4 py-2 bg-purple-600 text-white font-bold rounded hover:bg-purple-700 shadow-sm flex items-center gap-2">💾 Nadpisz</button>
            </div>
        </div>
    </div>
</div>

<form id="delete-form" method="POST" action="/admin/media/delete" class="hidden">
<input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
    <input type="hidden" name="file" id="delete-file-input">
</form>

<script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/media-manager.js') ?>"></script>
<script>
function sendMultipleToPicker() {
    const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
    const urls = Array.from(checkedBoxes).map(cb => cb.dataset.url);
    
    if (urls.length > 0) {
        window.parent.postMessage({ type: 'media_selected_multiple', urls: urls }, '*');
    }
}
</script>
</body>
</html>