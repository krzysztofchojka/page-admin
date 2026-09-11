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

    uploadInput.addEventListener('change', function() {
        if(this.files.length > 0) handleUpload(this.files);
    });

    // Drag & Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if(e.dataTransfer.files.length > 0) handleUpload(e.dataTransfer.files);
    });

    function handleUpload(files) {
        progressContainer.classList.remove('hidden');
        const formData = new FormData();
        for (let f of files) formData.append('files[]', f);
        formData.append('path', currentPath);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/media/upload', true);
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressText.innerText = percent + '%';
            }
        });

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        progressText.innerText = 'Zakończono! Odświeżanie...';
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('Błąd serwera: ' + (res.error || 'Plik może być za duży dla konfiguracji PHP.'));
                        progressContainer.classList.add('hidden');
                    }
                } catch(e) {
                    alert('Błąd serwera (np. plik przekroczył limit upload_max_filesize).');
                    progressContainer.classList.add('hidden');
                }
            } else {
                alert('Wystąpił błąd HTTP podczas wgrywania.');
                progressContainer.classList.add('hidden');
            }
        };
        xhr.onerror = () => alert('Błąd sieci podczas wgrywania.');
        xhr.send(formData);
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
                <div class="text-6xl mb-4">📄</div>
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

    // --- EDYTOR TEKSTU ---
    function openTextEditor(url, name, e) {
        e.stopPropagation();
        editingFileName = name;
        document.getElementById('editor-title').innerText = "Edycja: " + name;
        document.getElementById('file-editor-area').value = "Wczytywanie...";
        document.getElementById('editor-modal').classList.remove('hidden');

        // Pobranie aktualnej zawartości
        fetch(url)
            .then(res => res.text())
            .then(text => document.getElementById('file-editor-area').value = text)
            .catch(err => document.getElementById('file-editor-area').value = "Błąd odczytu pliku.");
    }

    function saveTextFile() {
        const content = document.getElementById('file-editor-area').value;
        const blob = new Blob([content], { type: "text/plain" }); // Wymuszamy Blob
        const fd = new FormData();
        fd.append('file', blob, editingFileName); // Symulujemy wrzucenie pliku z tą samą nazwą
        fd.append('path', currentPath);

        const btn = document.querySelector('#editor-modal button.bg-green-600');
        btn.innerText = "Zapisywanie..."; btn.disabled = true;

        fetch('/admin/media/upload', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    btn.innerText = "Zapisano!";
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Błąd zapisu pliku.');
                    btn.innerText = "Zapisz"; btn.disabled = false;
                }
            });
    }

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
        btn.innerHTML = "🔄 Przetwarzanie..."; btn.disabled = true;

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
                        btn.innerHTML = "✅ Zapisano!";
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