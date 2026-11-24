<?php
$currentPath = $_GET['path'] ?? '';
// Bezpieczne ścieżki do obsługi w JS
$jsCurrentPath = htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8');

// Limity PHP – JS dzieli wgrywanie folderów na partie, żeby ich nie przekroczyć
$toBytes = function ($v) {
    $v = trim((string)$v); $n = (float)$v; $u = strtolower(substr($v, -1));
    return (int)($u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n)));
};
$uploadLimits = [
    'maxFiles' => max(1, (int)ini_get('max_file_uploads') ?: 20),
    'maxPost' => $toBytes(ini_get('post_max_size')) ?: 8388608,
    'maxFile' => $toBytes(ini_get('upload_max_filesize')) ?: 2097152,
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menedżer Mediów</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="<?= \CMS\Helpers\Asset::url('/assets/css/icons.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
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
<body class="bg-gray-50 text-gray-800 font-sans h-screen flex flex-col overflow-hidden" id="drop-zone"
      data-upload-limits='<?= htmlspecialchars(json_encode($uploadLimits), ENT_QUOTES) ?>'
      data-csrf="<?= htmlspecialchars(\CMS\Core\Session::generateCsrfToken(), ENT_QUOTES) ?>">

    <div class="flex flex-col h-full p-6">
        <!-- HEADER (Nawigacja i Akcje) -->
        <div class="flex flex-wrap justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 gap-4">
            
            <!-- Okruszki (Breadcrumbs) -->
            <div class="flex items-center gap-1 text-sm font-medium text-gray-600">
                <?php if (!isset($_GET['picker'])): ?>
                    <a href="/admin" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md transition mr-2"><i class=i-arrow-left></i> Wróć</a>
                <?php endif; ?>
                
                <a href="/admin/media<?=(isset($_GET['picker']) ? '?picker='.$_GET['picker'] : '')?>" class="text-blue-600 hover:underline flex items-center gap-1 px-2 py-1 rounded transition border border-transparent" ondragover="dragOverBreadcrumb(event)" ondragleave="dragLeaveBreadcrumb(event)" ondrop="dropToFolder(event, '')">
                    <i class=i-house></i> Media
                </a>
                <?php
                $parts = explode('/', $currentPath);
                $build = '';
                foreach ($parts as $part) {
                    if (!$part) continue;
                    $build .= $part . '/';
                    $targetPath = trim($build, '/');
                    // Każdy podfolder w ścieżce jako strefa zrzutu
                    echo "<span class='text-gray-400'>/</span> <a href='?path=" . urlencode($targetPath) . (isset($_GET['picker']) ? '&picker=1' : '') . "' class='text-blue-600 hover:underline px-2 py-1 rounded transition border border-transparent' ondragover='dragOverBreadcrumb(event)' ondragleave='dragLeaveBreadcrumb(event)' ondrop='dropToFolder(event, \"".htmlspecialchars($targetPath, ENT_QUOTES)."\")'> $part </a>";
                }
                ?>
            </div>

            <!-- Akcje ogólne -->
            <div class="flex flex-wrap items-center gap-2 md:gap-3">
                <!-- Przyciski masowe -->
                <div id="bulk-actions" class="hidden items-center gap-2 mr-4 border-r pr-4 border-gray-200">
                    <span class="text-xs font-bold text-gray-500"><span id="sel-count">0</span> zaznaczonych</span>
                    <button onclick="downloadSelected()" class="bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 px-3 py-1.5 rounded-md text-sm font-bold transition" title="Pobierz"><i class=i-download></i> Pobierz</button>
                    <?php if (isset($_GET['picker'])): ?>
                        <button onclick="sendMultipleToPicker()" class="bg-green-600 text-white hover:bg-green-700 px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm ml-2">
                            <i class=i-circle-check></i> Zatwierdź wybrane
                        </button>
                    <?php endif; ?>
                </div>

                <form method="GET" class="relative">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($currentPath) ?>">
                    <?php if (isset($_GET['picker'])): ?><input type="hidden" name="picker" value="1"><?php endif; ?>
                    <input type="text" name="search" placeholder="Szukaj..." class="pl-3 pr-8 py-1.5 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500 text-sm w-48 transition" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </form>

                <button onclick="window.location.href='/admin/media/downloadZip?path=<?= urlencode($currentPath) ?>'" class="whitespace-nowrap inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-300 hover:bg-green-100 px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm" title="Pobierz ten folder jako ZIP"><i class=i-package></i> ZIP</button>
                <button onclick="openFolderModal()" class="whitespace-nowrap inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 px-4 py-1.5 rounded-md text-sm font-bold transition shadow-sm"><i class=i-folder-plus></i> Nowy Folder</button>
                <?php if (!isset($_GET['picker'])): ?>
                <button onclick="createTextFile()" class="whitespace-nowrap inline-flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 px-4 py-1.5 rounded-md text-sm font-bold transition shadow-sm" title="Utwórz pusty plik tekstowy (np. style.css, notatka.txt)"><i class=i-file-plus></i> Nowy plik</button>
                <?php endif; ?>
                <button onclick="document.getElementById('uploadFolderInput').click()" class="whitespace-nowrap inline-flex items-center gap-1.5 bg-white text-blue-700 border border-blue-300 hover:bg-blue-50 px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm" title="Wgraj cały folder razem z podfolderami (możesz też przeciągnąć folder na okno)"><i class=i-folder-up></i> Wgraj folder</button>
                <button onclick="document.getElementById('uploadInput').click()" class="whitespace-nowrap inline-flex items-center gap-1.5 bg-blue-600 text-white hover:bg-blue-700 px-4 py-1.5 rounded-md text-sm font-bold transition shadow-sm relative overflow-hidden group">
                    <i class=i-cloud-upload></i> Wgraj pliki
                    <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition"></div>
                </button>
            </div>
        </div>

        <!-- Wskaźnik Uploadu -->
        <div id="progress-container" class="hidden mb-4 bg-white p-3 rounded-lg shadow-sm border border-blue-100 fade-in">
            <div class="flex justify-between text-xs font-bold text-blue-800 mb-1">
                <span id="progress-label">Wgrywanie plików...</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <!-- Główna siatka (Grid) -->
        <div class="flex-1 overflow-y-auto bg-white p-6 rounded-xl shadow-inner border border-gray-200">
            <?php if (empty($files)): ?>
                <div class="h-full flex flex-col items-center justify-center text-gray-400">
                    <div class="text-6xl mb-4"><i class=i-folder-open-thin></i></div>
                    <p>Ten folder jest pusty lub nie znaleziono plików.</p>
                    <p class="text-sm mt-2">Przeciągnij i upuść pliki lub całe foldery tutaj, aby je wgrać.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    
                    <?php foreach ($files as $file): ?>
                        
                        <?php if ($file['type'] === 'folder'): ?>
                            <!-- Folder -->
                            <div class="group relative bg-gray-50 hover:bg-blue-50 border border-transparent hover:border-blue-400 rounded-xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center h-40 shadow-sm" ondblclick="location.href='?path=<?= urlencode($file['path']) ?><?= isset($_GET['picker']) ? '&picker=1' : '' ?>'" ondragover="dragOverFolder(event)" ondragleave="dragLeaveFolder(event)" ondrop="dropToFolder(event, '<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>')">
                                <div class="text-6xl mb-2 text-blue-400 group-hover:text-blue-500 transition group-hover:scale-105 pointer-events-none"><i class=i-folder-thin></i></div>
                                <div class="font-bold text-gray-700 text-sm truncate w-full px-2 pointer-events-none"><?= htmlspecialchars($file['name']) ?></div>
                                <!-- Akcje folderu (hover) - na dole po prawej stronie -->
                                <div class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition flex flex-wrap justify-end gap-1 z-10 p-1">
                                    <button onclick="downloadFolderZip('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Pobierz folder jako ZIP"><i class=i-package></i></button>
                                    <button onclick="openRenameModal('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-blue-600" title="Zmień nazwę"><i class=i-pencil></i></button>
                                    <button onclick="deleteItem('<?= htmlspecialchars($file['path'], ENT_QUOTES) ?>', event)" class="p-1 bg-white border border-gray-200 rounded shadow text-xs hover:text-red-600" title="Usuń"><i class=i-trash-2></i></button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Plik -->
                            <?php 
                                $isImg = str_starts_with($file['mime'], 'image/'); 
                                $isPdf = $file['mime'] === 'application/pdf';
                                // Ikony dobierane dynamicznie (lista dla generatora icons.css): i-file-code-thin i-file-json-thin i-file-text-thin
                                $isTxt = !empty($file['editable']);
                                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $codeIcon = in_array($ext, ['html', 'htm', 'xml', 'svg', 'php', 'phtml'], true) ? 'file-code' : (in_array($ext, ['json', 'webmanifest', 'map'], true) ? 'file-json' : (in_array($ext, ['css', 'scss', 'less', 'js', 'mjs', 'ts'], true) ? 'file-code' : 'file-text'));
                                $isZip = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'zip';
                            ?>
                            <div class="group relative bg-white border border-gray-200 hover:border-blue-400 rounded-xl p-3 text-center transition flex flex-col h-40 shadow-sm cursor-grab active:cursor-grabbing" draggable="true" ondragstart="dragStartFile(event, '<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>')">
                                
                                <!-- Checkbox na froncie, żeby móc zaznaczać do masowych akcji -->
                                <input type="checkbox" class="file-checkbox absolute top-2 left-2 z-10 w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer" value="<?= htmlspecialchars($file['relative']) ?>" data-url="<?= htmlspecialchars($file['url']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" onclick="updateBulkActions(event)">
                                
                                <div class="flex-1 flex items-center justify-center overflow-hidden w-full h-24 mb-2 rounded-lg bg-gray-50 pointer-events-none">
                                    <?php if ($isImg): ?>
                                        <img src="<?= htmlspecialchars($file['url']) ?>" class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition-transform" loading="lazy">
                                    <?php else: ?>
                                        <div class="text-5xl text-gray-400 transition-transform group-hover:scale-105">
                                            <?= $isPdf ? '<i class=i-file-text-thin></i>' : ($isTxt ? '<i class=i-' . $codeIcon . '-thin></i>' : ($isZip ? '<i class=i-file-archive-thin></i>' : '<i class=i-file-thin></i>')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="font-bold text-gray-700 text-xs truncate w-full pointer-events-none" title="<?= htmlspecialchars($file['name']) ?>"><?= htmlspecialchars($file['name']) ?></div>
                                <div class="text-gray-400 text-[10px] mt-1 pointer-events-none"><?= $file['size'] ?></div>

                                <!-- Nakładka na kliknięcie całego elementu (pobiera do edytora lub wyświetla PDF) -->
                                <div class="absolute inset-0 z-0" onclick="<?php if ($isTxt && !$isImg): ?>if (!isPickerMode) { openTextEditor('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event); return; } <?php endif; ?>handleFileClick('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', <?= $isImg ? 'true' : 'false' ?>, <?= $isPdf ? 'true' : 'false' ?>)"></div>
                                
                                <!-- Akcje pliku (hover) na dole po prawej stronie, aby nie nachodziły na checkbox w lewym górnym rogu -->
                                <div class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition flex flex-wrap justify-end gap-1 z-10 p-1">
                                    <button onclick="copyPath('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', false, event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Kopiuj ścieżkę (/uploads/...)"><i class=i-link></i></button>
                                    <button onclick="copyPath('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', true, event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Kopiuj pełen adres z domeną"><i class=i-globe></i></button>
                                    
                                    <?php if ($isImg): ?>
                                        <button onclick="openImageEditor('<?= htmlspecialchars($file['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-purple-600" title="Kadruj/Obróć"><i class=i-crop></i></button>
                                    <?php endif; ?>

                                    <?php if ($isZip): ?>
                                        <button onclick="unzipFile('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-amber-600" title="Rozpakuj do nowego folderu"><i class=i-folder-open></i></button>
                                    <?php endif; ?>

                                    <?php if ($isTxt): ?>
                                        <button onclick="openTextEditor('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-green-600" title="Edytuj plik"><i class=i-pencil></i></button>
                                    <?php endif; ?>
                                    
                                    <button onclick="openRenameModal('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-blue-600" title="Zmień nazwę"><i class=i-file-pen></i></button>
                                    <button onclick="deleteItem('<?= htmlspecialchars($file['relative'], ENT_QUOTES) ?>', event)" class="p-1.5 bg-white border border-gray-200 rounded shadow text-xs hover:text-red-600" title="Usuń"><i class=i-trash-2></i></button>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ukryty formularz uploadu -->
    <input type="file" id="uploadInput" multiple hidden>
    <input type="file" id="uploadFolderInput" webkitdirectory directory multiple hidden>

    <!-- Modal do powiększania/podglądu zdjęć i PDFów -->
    <div id="dynamic-modal" class="hidden fixed inset-0 bg-black/80 flex justify-center items-center z-50 fade-in p-4 backdrop-blur-sm" onclick="closeDynamicModal(event)">
        <!-- Generowane dynamicznie w JS -->
    </div>

    <!-- Modale Systemowe (Tworzenie folderu, Zmiana Nazwy) -->
    <!-- ... (Reszta modalów z kodu jest niezmieniona) ... -->
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

    <!-- Modal Edytora Tekstowego -->
    <div id="editor-modal" class="hidden fixed inset-0 bg-black/60 flex justify-center items-center z-[70] fade-in backdrop-blur-sm p-2 md:p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl h-[92vh] md:h-[85vh] flex flex-col overflow-hidden">
            <div class="bg-gray-50 border-b px-4 py-3 flex justify-between items-center gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <i class=i-file-code></i>
                    <h3 class="font-bold text-gray-800 truncate" id="editor-title">Edycja pliku</h3>
                    <span id="editor-dirty" class="hidden text-xs font-bold text-amber-600 whitespace-nowrap">● niezapisane</span>
                    <span id="editor-readonly" class="hidden text-xs font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded whitespace-nowrap"><i class=i-lock></i> tylko do odczytu</span>
                </div>
                <button onclick="closeTextEditor()" class="text-gray-400 hover:text-red-500 text-xl p-1" title="Zamknij (Esc)"><i class=i-x></i></button>
            </div>
            <div id="editor-readonly-info" class="hidden bg-red-50 text-red-800 text-xs px-4 py-2 border-b border-red-100">
                Pliki wykonywalne (np. .php) nie mogą być zapisywane w publicznym katalogu mediów – serwer mógłby je uruchomić. Podgląd jest możliwy, edycja nie.
            </div>
            <div class="flex-1 relative bg-gray-900">
                <div id="file-editor-ace" class="absolute inset-0"></div>
            </div>
            <div class="bg-gray-50 border-t px-4 py-3 flex flex-wrap justify-between items-center gap-3">
                <span id="editor-status" class="text-xs text-gray-500">Ctrl/⌘ + S – zapisz</span>
                <div class="flex gap-2">
                    <button onclick="closeTextEditor()" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded">Zamknij</button>
                    <button id="editor-save-btn" onclick="saveTextFile()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"><i class=i-save></i> Zapisz</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Croppera -->
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
                    <button onclick="cropper.rotate(-90)" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold"><i class=i-rotate-ccw></i> Obróć w lewo</button>
                    <button onclick="cropper.rotate(90)" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold"><i class=i-rotate-cw></i> Obróć w prawo</button>
                    <button onclick="cropper.reset()" class="px-3 py-1.5 bg-white border border-gray-300 rounded shadow-sm text-sm hover:bg-gray-50 font-bold">Reset</button>
                </div>
                <div class="flex gap-2">
                    <button onclick="closeCropper()" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded">Anuluj</button>
                    <button onclick="saveCroppedImage(true)" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 shadow-sm">Zapisz kopię</button>
                    <button onclick="saveCroppedImage(false)" id="btn-save-crop" class="px-4 py-2 bg-purple-600 text-white font-bold rounded hover:bg-purple-700 shadow-sm flex items-center gap-2"><i class=i-save></i> Nadpisz</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Skrypty usunięcia -->
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
        function copyPath(url, fullDomain, event) {
            event.stopPropagation();
            event.preventDefault();
            const textToCopy = fullDomain ? window.location.origin + url : url;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    alert('Skopiowano: ' + textToCopy);
                });
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = textToCopy;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                textArea.remove();
                alert('Skopiowano: ' + textToCopy);
            }
        }
    </script>
</body>
</html>