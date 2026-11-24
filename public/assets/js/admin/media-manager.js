const isPickerMode = new URLSearchParams(window.location.search).has('picker');
const get_path=(new URLSearchParams(document.location.search)).get("path");
    const currentPath = get_path?get_path:"";
    let cropper = null;
    let editingFileName = '';

    // --- UPLOAD LOGIC (XHR z Progress Barem) ---
    const uploadInput = document.getElementById('uploadInput');
    const dropZone = document.getElementById('drop-zone');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');

    const uploadFolderInput = document.getElementById('uploadFolderInput');
    const progressLabel = document.getElementById('progress-label');
    const LIMITS = (() => { try { return JSON.parse(document.body.dataset.uploadLimits || '{}'); } catch (e) { return {}; } })();
    const MAX_FILES = LIMITS.maxFiles || 20;
    const MAX_POST = (LIMITS.maxPost || 8388608) * 0.9;     // margines na pola formularza
    const MAX_FILE = LIMITS.maxFile || 2097152;

    uploadInput.addEventListener('change', function() {
        if (this.files.length > 0) handleUpload(Array.from(this.files).map(f => ({ file: f, path: '' })));
        this.value = '';
    });

    // Wybór całego folderu (input webkitdirectory) – webkitRelativePath zawiera "folder/pod/plik.jpg"
    uploadFolderInput.addEventListener('change', function() {
        if (this.files.length > 0) handleUpload(Array.from(this.files).map(f => ({ file: f, path: f.webkitRelativePath || '' })));
        this.value = '';
    });

    // Drag & Drop – pliki i całe foldery (rekurencyjnie)
    dropZone.addEventListener('dragover', (e) => {
        if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return;
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', async (e) => {
        if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return; // przenoszenie plików między folderami
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const items = Array.from(e.dataTransfer.items || []);
        const entries = items.map(it => it.webkitGetAsEntry ? it.webkitGetAsEntry() : null).filter(Boolean);
        if (entries.length) {
            showProgress('Odczytywanie folderów...', 0);
            const list = [];
            for (const entry of entries) await walkEntry(entry, '', list);
            handleUpload(list);
        } else if (e.dataTransfer.files.length > 0) {
            handleUpload(Array.from(e.dataTransfer.files).map(f => ({ file: f, path: '' })));
        }
    });

    function readAllEntries(reader) {
        // readEntries zwraca wyniki porcjami (Chrome ~100) – czytamy do skutku
        return new Promise((resolve, reject) => {
            const all = [];
            const next = () => reader.readEntries(batch => {
                if (!batch.length) return resolve(all);
                all.push(...batch); next();
            }, reject);
            next();
        });
    }

    async function walkEntry(entry, prefix, out) {
        if (entry.isFile) {
            const file = await new Promise((res, rej) => entry.file(res, rej));
            out.push({ file, path: prefix ? prefix + '/' + file.name : '' });
        } else if (entry.isDirectory) {
            const children = await readAllEntries(entry.createReader());
            const dirPath = prefix ? prefix + '/' + entry.name : entry.name;
            for (const child of children) await walkEntry(child, dirPath, out);
        }
    }

    function showProgress(label, percent) {
        progressContainer.classList.remove('hidden');
        if (progressLabel) progressLabel.innerText = label;
        progressBar.style.width = percent + '%';
        progressText.innerText = percent + '%';
    }

    // Dzieli pliki na partie mieszczące się w max_file_uploads i post_max_size
    function makeBatches(items) {
        const batches = []; let cur = []; let size = 0;
        for (const it of items) {
            if (cur.length && (cur.length >= MAX_FILES || size + it.file.size > MAX_POST)) {
                batches.push(cur); cur = []; size = 0;
            }
            cur.push(it); size += it.file.size;
        }
        if (cur.length) batches.push(cur);
        return batches;
    }

    function sendBatch(batch, onProgress) {
        return new Promise((resolve) => {
            const formData = new FormData();
            batch.forEach(it => {
                formData.append('files[]', it.file);
                formData.append('relpaths[]', it.path || '');
            });
            formData.append('path', currentPath);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/media/upload', true);
            xhr.upload.addEventListener('progress', (e) => { if (e.lengthComputable) onProgress(e.loaded); });
            xhr.onload = () => {
                let res = null;
                try { res = JSON.parse(xhr.responseText); } catch (e) {}
                resolve(xhr.status === 200 && res ? res : { success: false, saved: 0, skipped: batch.map(b => b.path || b.file.name), error: 'Błąd serwera (limit uploadu?)' });
            };
            xhr.onerror = () => resolve({ success: false, saved: 0, skipped: batch.map(b => b.path || b.file.name), error: 'Błąd sieci' });
            xhr.send(formData);
        });
    }

    async function handleUpload(items) {
        if (!items.length) { progressContainer.classList.add('hidden'); return; }

        // Pliki większe niż upload_max_filesize i ukryte pliki systemowe od razu pomijamy
        const tooBig = items.filter(it => it.file.size > MAX_FILE);
        const ok = items.filter(it => it.file.size <= MAX_FILE && !it.file.name.startsWith('.'));
        const batches = makeBatches(ok);
        const totalBytes = ok.reduce((a, it) => a + it.file.size, 0) || 1;
        let doneBytes = 0, saved = 0;
        const skipped = tooBig.map(it => (it.path || it.file.name) + ' (za duży)');

        for (let i = 0; i < batches.length; i++) {
            const batch = batches[i];
            const batchBytes = batch.reduce((a, it) => a + it.file.size, 0);
            const label = batches.length > 1 ? `Wgrywanie partii ${i + 1}/${batches.length} (${ok.length} plików)...` : `Wgrywanie ${ok.length} plików...`;
            const res = await sendBatch(batch, (loaded) => {
                showProgress(label, Math.min(100, Math.round(((doneBytes + Math.min(loaded, batchBytes)) / totalBytes) * 100)));
            });
            doneBytes += batchBytes;
            saved += res.saved || 0;
            if (res.skipped && res.skipped.length) skipped.push(...res.skipped);
            if (!res.success && res.error && !(res.skipped && res.skipped.length)) skipped.push(res.error);
        }

        showProgress(`Zakończono: wgrano ${saved} plików.`, 100);
        if (skipped.length) {
            alert(`Wgrano ${saved} plików.\nPominięto ${skipped.length}:\n- ` + skipped.slice(0, 15).join('\n- ') + (skipped.length > 15 ? '\n…' : '')
                + '\n\n(Pliki wykonywalne, np. .php, oraz pliki ukryte są blokowane ze względów bezpieczeństwa.)');
        }
        setTimeout(() => location.reload(), skipped.length ? 0 : 500);
    }

    // --- ROZPAKOWYWANIE ZIP ---
    function unzipFile(path, name, e) {
        e.stopPropagation();
        if (!confirm(`Rozpakować "${name}" do nowego folderu obok archiwum?`)) return;
        showProgress('Rozpakowywanie ' + name + '...', 50);
        const fd = new FormData();
        fd.append('file', path);
        fd.append('csrf_token', document.body.dataset.csrf || '');
        fetch('/admin/media/unzip', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success) { alert(d.error || 'Nie udało się rozpakować archiwum.'); progressContainer.classList.add('hidden'); return; }
                let msg = `Rozpakowano ${d.extracted} plików do folderu "${d.folder}".`;
                if (d.skippedCount) msg += `\nPominięto ${d.skippedCount} (niedozwolone/niebezpieczne):\n- ` + d.skipped.join('\n- ');
                alert(msg);
                const params = new URLSearchParams(location.search);
                params.set('path', d.folder);
                location.search = params.toString();
            })
            .catch(() => { alert('Błąd serwera podczas rozpakowywania.'); progressContainer.classList.add('hidden'); });
    }

    // --- SELEKCJA I POBIERANIE (ZBIORCZE) ---
    function updateBulkActions(e) {
        e.stopPropagation(); // Zapobiega otwarciu pliku przy klikaniu w checkbox
        const checked = document.querySelectorAll('.file-checkbox:checked');
        const bulkDiv = document.getElementById('bulk-actions');
        document.getElementById('sel-count').innerText = checked.length;
        if(checked.length > 0) {
            bulkDiv.classList.remove('hidden');
            bulkDiv.classList.add('flex');
        } else {
            bulkDiv.classList.add('hidden');
            bulkDiv.classList.remove('flex');
        }
    }

    async function downloadSelected() {
        const checked = document.querySelectorAll('.file-checkbox:checked');
        if(checked.length === 0) return;
        
        // Pętla pobierająca pliki z opóźnieniem (aby przeglądarka nie zablokowała)
        for(let i = 0; i < checked.length; i++) {
            const el = checked[i];
            const a = document.createElement('a');
            a.href = el.dataset.url;
            a.download = el.dataset.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            // Czekamy pół sekundy miedzy pobraniami
            await new Promise(r => setTimeout(r, 500));
        }
    }

    // --- KLIKNIĘCIE W PLIK (PICKER vs PREVIEW) ---
    function handleFileClick(url, isImg, isPdf) {
        if (isPickerMode) {
            window.parent.postMessage({ type: 'media_selected', url: url }, '*');
        } else {
            openPreviewModal(url, isImg, isPdf);
        }
    }

    // --- MODALE: PODGLĄD (Dynamiczny Modal) ---
    const dynModal = document.getElementById('dynamic-modal');
    function openPreviewModal(url, isImg, isPdf) {
        dynModal.innerHTML = ''; // Wyczyszczenie
        
        let content = '';
        if (isImg) {
            content = `<img src="${url}" class="max-w-full max-h-[90vh] rounded-xl shadow-2xl border-4 border-white/10" onclick="event.stopPropagation()">`;
        } else if (isPdf) {
            content = `<iframe src="${url}#toolbar=0" class="w-full h-[85vh] max-w-5xl bg-white rounded-xl shadow-2xl" onclick="event.stopPropagation()"></iframe>`;
        } else {
            content = `<div class="bg-white p-8 rounded-xl text-center shadow-xl" onclick="event.stopPropagation()">
                <div class="text-6xl mb-4 text-gray-300"><i class=i-file-text-thin></i></div>
                <h3 class="font-bold text-lg mb-4">Brak podglądu dla tego typu pliku</h3>
                <a href="${url}" download class="bg-blue-600 text-white px-4 py-2 rounded-md font-bold hover:bg-blue-700">Pobierz plik</a>
            </div>`;
        }

        dynModal.innerHTML = content + `<button onclick="closeDynamicModal(event)" class="absolute top-4 right-6 text-white text-4xl font-bold hover:text-gray-300 drop-shadow-md">&times;</button>`;
        dynModal.classList.remove('hidden');
    }

    function closeDynamicModal(e) {
        if(e.target === dynModal || e.target.tagName.toLowerCase() === 'button') {
            dynModal.classList.add('hidden');
            dynModal.innerHTML = '';
        }
    }

    // --- MODALE: FOLDER / RENAME ---
    function openFolderModal() { document.getElementById('folder-modal').classList.remove('hidden'); }
    
    function openRenameModal(oldPath, oldName, e) {
        e.stopPropagation(); // Blokuje kliknięcie w rodzica (otwarcie folderu/pliku)
        document.getElementById('rename-old-path').value = oldPath;
        document.getElementById('rename-new-name').value = oldName;
        document.getElementById('rename-modal').classList.remove('hidden');
    }

    // --- USUWANIE ---
    function deleteItem(path, e) {
        e.stopPropagation();
        if(confirm('Czy na pewno chcesz usunąć ten element? Tej operacji nie można cofnąć.')) {
            document.getElementById('delete-file-input').value = path;
            document.getElementById('delete-form').submit();
        }
    }

    // --- EDYTOR PLIKÓW TEKSTOWYCH (Ace) ---
    let aceEditor = null;
    let editingPath = '';
    let editingMtime = 0;
    let editingReadonly = false;
    let editorDirty = false;

    function setEditorDirty(on) {
        editorDirty = on;
        document.getElementById('editor-dirty').classList.toggle('hidden', !on);
    }

    function editorStatus(msg, cls = 'text-gray-500') {
        const el = document.getElementById('editor-status');
        el.className = 'text-xs ' + cls;
        el.innerText = msg;
    }

    function ensureAce() {
        if (aceEditor || typeof ace === 'undefined') return aceEditor;
        aceEditor = ace.edit('file-editor-ace');
        aceEditor.setTheme('ace/theme/tomorrow_night');
        aceEditor.setOptions({ fontSize: '14px', showPrintMargin: false, wrap: true, useSoftTabs: true, tabSize: 4 });
        aceEditor.session.on('change', () => { if (!aceEditor.__loading) setEditorDirty(true); });
        aceEditor.commands.addCommand({ name: 'save', bindKey: { win: 'Ctrl-S', mac: 'Command-S' }, exec: () => saveTextFile() });
        return aceEditor;
    }

    function openTextEditor(path, name, e) {
        if (e) e.stopPropagation();
        editingPath = path;
        document.getElementById('editor-title').innerText = name;
        document.getElementById('editor-modal').classList.remove('hidden');
        setEditorDirty(false);
        editorStatus('Wczytywanie…');
        const ed = ensureAce();
        if (!ed) { editorStatus('Nie udało się załadować edytora (brak połączenia z CDN?).', 'text-red-600'); return; }
        ed.__loading = true; ed.setValue('', -1); ed.__loading = false;

        fetch('/admin/media/readText?file=' + encodeURIComponent(path))
            .then(r => r.json())
            .then(d => {
                if (!d.success) { editorStatus(d.error || 'Błąd odczytu pliku.', 'text-red-600'); return; }
                editingMtime = d.mtime;
                editingReadonly = !!d.readonly;
                ed.session.setMode('ace/mode/' + (d.mode || 'text'));
                ed.__loading = true; ed.setValue(d.content, -1); ed.__loading = false;
                ed.session.getUndoManager().reset();
                ed.setReadOnly(editingReadonly);
                document.getElementById('editor-readonly').classList.toggle('hidden', !editingReadonly);
                document.getElementById('editor-readonly-info').classList.toggle('hidden', !editingReadonly);
                document.getElementById('editor-save-btn').disabled = editingReadonly;
                editorStatus(editingReadonly ? 'Tylko podgląd.' : 'Ctrl/⌘ + S – zapisz');
                ed.focus();
                setTimeout(() => ed.resize(), 50);
            })
            .catch(() => editorStatus('Błąd sieci podczas odczytu pliku.', 'text-red-600'));
    }

    function closeTextEditor() {
        if (editorDirty && !confirm('Masz niezapisane zmiany. Zamknąć bez zapisywania?')) return;
        document.getElementById('editor-modal').classList.add('hidden');
        if (editorDirty) setEditorDirty(false);
    }

    function saveTextFile(force = false) {
        if (!aceEditor || editingReadonly || !editingPath) return;
        const btn = document.getElementById('editor-save-btn');
        btn.disabled = true;
        editorStatus('Zapisywanie…');
        const fd = new FormData();
        fd.append('file', editingPath);
        fd.append('content', aceEditor.getValue());
        fd.append('mtime', editingMtime);
        fd.append('csrf_token', document.body.dataset.csrf || '');
        if (force) fd.append('force', '1');
        fetch('/admin/media/saveText', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                if (d.success) {
                    editingMtime = d.mtime;
                    setEditorDirty(false);
                    editorStatus('Zapisano ' + new Date().toLocaleTimeString(), 'text-green-700 font-bold');
                } else if (d.conflict) {
                    if (confirm(d.error)) saveTextFile(true); else editorStatus('Nie zapisano – plik zmieniony w międzyczasie.', 'text-amber-700');
                } else {
                    editorStatus(d.error || 'Błąd zapisu.', 'text-red-600');
                }
            })
            .catch(() => { btn.disabled = false; editorStatus('Błąd sieci podczas zapisu.', 'text-red-600'); });
    }

    function createTextFile() {
        const name = prompt('Nazwa nowego pliku (np. style.css, notatka.txt, dane.json):');
        if (!name) return;
        const fd = new FormData();
        fd.append('create', '1');
        fd.append('path', currentPath);
        fd.append('name', name.trim());
        fd.append('content', '');
        fd.append('csrf_token', document.body.dataset.csrf || '');
        fetch('/admin/media/saveText', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success) { alert(d.error || 'Nie udało się utworzyć pliku.'); return; }
                sessionStorage.setItem('pa_open_editor', JSON.stringify({ path: d.file, name: name.trim() }));
                location.reload();
            });
    }

    // Esc zamyka edytor; po utworzeniu pliku od razu go otwieramy
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('editor-modal').classList.contains('hidden')) closeTextEditor();
    });
    window.addEventListener('beforeunload', (e) => { if (editorDirty) { e.preventDefault(); e.returnValue = ''; } });
    try {
        const pending = JSON.parse(sessionStorage.getItem('pa_open_editor') || 'null');
        if (pending) { sessionStorage.removeItem('pa_open_editor'); openTextEditor(pending.path, pending.name); }
    } catch (e) {}

    // --- EDYTOR ZDJĘĆ (CROPPER.JS) ---
    function openImageEditor(url, name, e) {
        e.stopPropagation();
        editingFileName = name;
        document.getElementById('cropper-title').innerText = "Kadrowanie: " + name;
        const imgEl = document.getElementById('cropper-image');
        
        // Czasowy fix cache, żeby cropper zawsze ładował świeże
        imgEl.src = url + "?t=" + new Date().getTime(); 
        
        document.getElementById('cropper-modal').classList.remove('hidden');

        // Inicjalizacja biblioteki
        if(cropper) cropper.destroy();
        cropper = new Cropper(imgEl, {
            viewMode: 2, // Restrict crop box to canvas
            autoCropArea: 1,
            responsive: true,
            background: false
        });
    }

    function closeCropper() {
        if(cropper) cropper.destroy();
        document.getElementById('cropper-modal').classList.add('hidden');
    }

    function saveCroppedImage(asCopy = false) {
        if(!cropper) return;
        const btn = document.getElementById('btn-save-crop');
        const originalText = btn.innerHTML;
        btn.innerHTML = "<i class=i-refresh-cw></i> Przetwarzanie..."; btn.disabled = true;

        cropper.getCroppedCanvas().toBlob((blob) => {
            const fd = new FormData();
            
            let finalName = editingFileName;
            if (asCopy) {
                // Rozdziel nazwę i rozszerzenie, dopisz znacznik czasu
                const parts = editingFileName.split('.');
                const ext = parts.pop();
                finalName = parts.join('.') + '_kopia_' + new Date().getTime() + '.' + ext;
            }

            fd.append('file', blob, finalName);
            fd.append('path', currentPath);

            fetch('/admin/media/upload', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        btn.innerHTML = "<i class=i-circle-check></i> Zapisano!";
                        setTimeout(() => location.reload(), 600);
                    } else {
                        alert('Błąd zapisu pliku.');
                        btn.innerHTML = originalText; btn.disabled = false;
                    }
                }).catch(() => {
                    alert('Błąd sieci.');
                    btn.innerHTML = originalText; btn.disabled = false;
                });
        }, 'image/jpeg', 0.9);
    }
    // --- AJAX FORMULARZE (Folder / Rename) ---
    function submitFolderCreate(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fetch('/admin/media/createFolder', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => { if(d.success) location.reload(); else alert(d.error || 'Błąd tworzenia folderu'); });
    }

    function submitRename(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fetch('/admin/media/rename', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => { if(d.success) location.reload(); else alert('Błąd zmiany nazwy'); });
    }

    // --- DRAG & DROP DO FOLDERÓW ---
    let draggedFilePath = null;

    function dragStartFile(e, path) {
        draggedFilePath = path;
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(() => e.target.classList.add('opacity-50'), 0);
    }

    // Obsługa najeżdżania na ikony folderów w gridzie
    function dragOverFolder(e) {
        e.preventDefault();
        e.currentTarget.classList.add('bg-blue-100', 'border-blue-500');
    }
    function dragLeaveFolder(e) {
        e.currentTarget.classList.remove('bg-blue-100', 'border-blue-500');
    }

    // Obsługa najeżdżania na linki w pasku nawigacji (Breadcrumbs)
    function dragOverBreadcrumb(e) {
        e.preventDefault();
        e.currentTarget.classList.add('bg-blue-100', 'border-blue-400', 'shadow-sm');
        e.currentTarget.classList.remove('border-transparent');
    }
    function dragLeaveBreadcrumb(e) {
        e.currentTarget.classList.remove('bg-blue-100', 'border-blue-400', 'shadow-sm');
        e.currentTarget.classList.add('border-transparent');
    }

    // Wspólna funkcja upuszczania plików
    function dropToFolder(e, targetPath) {
        e.preventDefault();
        // Czyszczenie stylów z folderów i ze ścieżki
        e.currentTarget.classList.remove('bg-blue-100', 'border-blue-500', 'border-blue-400', 'shadow-sm');
        e.currentTarget.classList.add('border-transparent');

        let itemsToMove = [];
        
        const checkedBoxes = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
        if (checkedBoxes.includes(draggedFilePath)) {
            itemsToMove = checkedBoxes; 
        } else {
            itemsToMove = [draggedFilePath];
        }

        if(itemsToMove.length === 0) return;

        // Jeśli ktoś próbuje wrzucić plik do folderu w którym już jest
        if (targetPath === currentPath) return;

        const fd = new FormData();
        fd.append('items', JSON.stringify(itemsToMove));
        fd.append('target', targetPath);

        fetch('/admin/media/move', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Błąd przenoszenia plików.');
                }
            });
    }

    document.addEventListener('dragend', (e) => {
        if (e.target.classList && e.target.classList.contains('opacity-50')) {
            e.target.classList.remove('opacity-50');
        }
    });

    function downloadFolderZip(path, e) {
        e.stopPropagation();
        window.location.href = '/admin/media/downloadZip?path=' + encodeURIComponent(path);
    }