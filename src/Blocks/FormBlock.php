<?php
namespace CMS\Blocks;

class FormBlock implements BlockInterface {
    private static $turnstileLoaded = false;

    public function render(array $block, $db): string {
        try {
            $db->query("ALTER TABLE pa_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted' AFTER user_ip");
        } catch (\Exception $e) {}

        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
        if (!$form) return '';

        $formSettings = json_decode($form['settings'] ?? '{}', true);
        $userId = \CMS\Core\Session::get('user_id');
        $html = '<div class="bg-gray-50 border border-gray-200 p-6 md:p-10 rounded-2xl mb-8 shadow-sm" id="form-container-'.$form['id'].'">';
        $html .= '<h3 class="text-2xl font-extrabold mb-8 text-gray-800">' . htmlspecialchars($form['title']) . '</h3>';

        if (!empty($formSettings['reqLogin']) && !$userId) {
            $html .= '<div class="bg-yellow-50 p-6 rounded-xl text-yellow-800 border border-yellow-200 flex items-center gap-4"><span class="text-3xl">🔐</span><div>Zaloguj się, aby wypełnić formularz.<br><a href="/login" class="font-bold underline text-yellow-900 mt-1 inline-block">Zaloguj się</a></div></div></div>';
            return $html;
        }

        $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $form['id'];
        $isEditing = isset($_GET['edit']) && $_GET['edit'] == $form['id'];
        
        $existingSubmission = null; $draftSubmission = null;
        if ($userId) {
            $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'submitted' ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
            $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
        } else {
            $guestDraftId = \CMS\Core\Session::get('draft_' . $form['id']);
            if ($guestDraftId) $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE id = ? AND status = 'draft'", [$guestDraftId])->fetch();
        }

        $flash = \CMS\Core\Session::getFlash();
        if ($flash && isset($_GET['err_form']) && $_GET['err_form'] == $form['id']) {
            $html .= '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-r-lg mb-8 text-sm font-bold shadow-sm">⚠️ ' . htmlspecialchars($flash['msg']) . '</div>';
        }

        $showThankYou = false;
        if ($userId) {
            if ($existingSubmission && ($isSubmittedNow || (!empty($formSettings['fillOnce']) && !$isEditing))) $showThankYou = true;
        } else {
            if ($isSubmittedNow) $showThankYou = true;
        }

        if ($showThankYou) {
            $html .= '<div class="bg-green-50 border border-green-200 text-green-800 px-8 py-8 rounded-xl mb-4 shadow-sm text-center"><span class="text-5xl mb-4 block">🎉</span> <strong class="text-2xl block mb-2">Dziękujemy!</strong><p class="text-green-700">Twój formularz został zapisany.</p></div>';
            $html .= '<div class="flex flex-wrap justify-center gap-4 mt-6">';
            if (!empty($formSettings['editable']) && $existingSubmission) {
                $html .= '<a href="?edit='.$form['id'].'#form-container-'.$form['id'].'" class="bg-white border border-gray-300 hover:border-blue-500 hover:text-blue-600 text-gray-700 font-bold py-3 px-8 rounded-xl shadow-sm transition-all">✏️ Edytuj</a>';
            }
            if (empty($formSettings['fillOnce'])) {
                $html .= '<a href="?#form-container-'.$form['id'].'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-all">🔄 Wyślij ponownie</a>';
            }
            $html .= '</div></div>';
            return $html;
        }

        $prefill = []; $existingFiles = []; $vault = new \CMS\Core\Vault();
        $activeSubmissionToLoad = ($draftSubmission && !$isEditing) ? $draftSubmission : ($isEditing ? $existingSubmission : null);
        
        if ($activeSubmissionToLoad) {
            $prefill = json_decode($vault->decrypt($activeSubmissionToLoad['data_json']), true) ?? [];
            $existingFiles = json_decode($activeSubmissionToLoad['files_json'] ?? '{}', true) ?? [];
        }

        $subs = $db->query("SELECT data_json FROM pa_submissions WHERE form_id = :id", ['id' => $form['id']])->fetchAll();
        $optionCounts = [];
        foreach ($subs as $s) {
            $d = json_decode($vault->decrypt($s['data_json']), true) ?? [];
            foreach ($d as $fk => $fv) {
                if (is_array($fv)) foreach ($fv as $v) $optionCounts[$fk][$v] = ($optionCounts[$fk][$v] ?? 0) + 1;
                else $optionCounts[$fk][$fv] = ($optionCounts[$fk][$fv] ?? 0) + 1;
            }
        }

        $inputClasses = "w-full border border-gray-200 bg-gray-50/50 text-gray-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white block p-3.5 shadow-sm transition-all outline-none";
        $html .= '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">';
        // --- KLUCZOWE: OCHRONA CSRF W FORMULARZACH DYNAMICZNYCH ---
        $html .= '<input type="hidden" name="csrf_token" value="'.\CMS\Core\Session::generateCsrfToken().'">';
        $html .= '<input type="hidden" name="form_id" value="'.$form['id'].'">';
        $html .= '<input type="hidden" name="return_url" value="'.htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/').'">';
        if ($activeSubmissionToLoad) $html .= '<input type="hidden" name="submission_id" value="'.$activeSubmissionToLoad['id'].'">';

        $fields = json_decode($form['form_json'], true) ?? [];
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'text';
            $widthClass = ($field['width']??'full') === 'full' ? 'md:col-span-3' : (($field['width']??'full')==='half'?'md:col-span-2':'md:col-span-1');
            
            if ($type === 'html') {
                $html .= "<div class='{$widthClass} prose max-w-none text-sm bg-white p-6 rounded-xl border border-gray-100'>" . ($field['html'] ?? '') . "</div>"; continue;
            }
            if ($type === 'honeypot') {
                $fieldId = $field['custom_id'] ?? $field['id'] ?? md5('hp' . rand());
                $html .= "<div style='position:absolute; left:-9999px; top:-9999px; opacity:0;' aria-hidden='true'><input type='text' name='hp_data_{$fieldId}' value='' tabindex='-1' autocomplete='off'></div>"; continue;
            }
            if ($type === 'captcha_image') {
                if (class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
                    $builder = new \Gregwar\Captcha\CaptchaBuilder; $builder->build();
                    \CMS\Core\Session::set('captcha_img_' . $form['id'], $builder->getPhrase());
                    $html .= "<div class='{$widthClass} bg-white p-5 rounded-2xl border border-gray-200 shadow-sm'><label class='block text-sm font-bold text-gray-700 mb-3'>Weryfikacja *</label><div class='flex gap-4 items-center'><img src='{$builder->inline()}' class='rounded-xl border h-[56px]'><input type='text' name='captcha_answer' required class='{$inputClasses} flex-1'></div></div>";
                } continue;
            }
            if ($type === 'captcha_turnstile') {
                $siteKey = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'turnstile_site_key'")->fetch()['setting_value'] ?? '';
                if ($siteKey) {
                    if (!self::$turnstileLoaded) { $html .= "<script src='https://challenges.cloudflare.com/turnstile/v0/api.js' async defer></script>"; self::$turnstileLoaded = true; }
                    $html .= "<div class='{$widthClass}'><div class='cf-turnstile' data-sitekey='".htmlspecialchars($siteKey)."'></div></div>";
                } continue;
            }

            $fieldId = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
            $val = $prefill[$fieldId] ?? '';
            $req = !empty($field['required']);
            $reqAttr = $req ? 'required' : '';
            $reqStar = $req ? '<span class="text-red-500 ml-1">*</span>' : '';

            if ($type === 'file') {
                $allowMultiple = !empty($formSettings['allowMultipleFiles']) ? 'multiple' : '';
                $html .= "<div class='{$widthClass} async-file-upload' data-field-id='{$fieldId}'>";
                $html .= "<label class='block text-sm font-bold text-gray-800 mb-2'>".htmlspecialchars($field['label']).$reqStar."</label>";
                $hasExisting = isset($existingFiles[$fieldId]) && !empty($existingFiles[$fieldId]);
                $currentReqAttr = ($hasExisting) ? '' : $reqAttr;
                
                $html .= "<div class='relative border-2 border-dashed border-blue-300 bg-blue-50 hover:bg-blue-100 rounded-xl py-4 px-6 text-center transition-all group overflow-hidden flex flex-col items-center justify-center'>";
                $html .= "<input type='file' id='{$fieldId}' class='absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 file-input' {$currentReqAttr} {$allowMultiple}>";
                if ($req) $html .= "<input type='hidden' class='original-required-flag' value='1'>";
                $html .= "<p class='text-sm font-bold text-blue-700 mb-0'>Przeciągnij plik lub kliknij</p></div>";
                $html .= "<div class='progress-container hidden mt-3'><div class='w-full bg-blue-100 rounded-full h-2.5'><div class='progress-bar bg-blue-600 h-2.5 rounded-full' style='width: 0%'></div></div></div>";
                $html .= "<div class='uploaded-files-list mt-3 flex flex-col gap-2'>";
                
                if ($hasExisting) {
                    $eFiles = isset($existingFiles[$fieldId]['original_name']) ? [$existingFiles[$fieldId]] : $existingFiles[$fieldId];
                    foreach ($eFiles as $eFile) {
                        if (empty($eFile['original_name'])) continue;
                        $jsonVal = htmlspecialchars(json_encode($eFile), ENT_QUOTES, 'UTF-8');
                        
                        $html .= "<div class='flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm group hover:border-blue-300 transition-colors existing-file-item mb-2'>";
                        $html .= "  <div class='flex items-center gap-3 overflow-hidden'>";
                        $origName = urlencode($eFile['original_name']);
                        $dlUrl = "/admin/forms/download?file=" . urlencode($eFile['storage_name'] ?? '') . "&orig=" . $origName;
                        $html .= "      <div class='bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0'><svg class='w-4 h-4' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z' clip-rule='evenodd'></path></svg></div>";
                        $html .= "      <a href='{$dlUrl}' target='_blank' class='truncate font-medium hover:text-blue-600 hover:underline transition-colors'>".htmlspecialchars($eFile['original_name'])."</a>";
                        $html .= "  </div>";
                        $html .= "  <button type='button' class='text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors remove-existing-file' title='Usuń plik'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button>";
                        $html .= "  <input type='hidden' name='async_files[{$fieldId}][]' value='{$jsonVal}'>";
                        $html .= "</div>";
                    }
                }
                $html .= "</div></div>"; continue;
            }

            $html .= "<div class='$widthClass'><label class='block text-sm font-bold text-gray-800 mb-2'>".htmlspecialchars($field['label']).$reqStar."</label>";
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $optionsRaw = explode("\n", trim($field['options'] ?? ''));
                $options = []; foreach ($optionsRaw as $opt) { if ($opt) { $parts = explode('|limit:', $opt); $options[] = ['label' => trim($parts[0]), 'limit' => isset($parts[1]) ? (int)trim($parts[1]) : 0]; } }
                
                if ($type === 'select') {
                    $html .= "<select name='data[{$fieldId}]' class='{$inputClasses}' {$reqAttr}><option value=''>-- Wybierz --</option>";
                    foreach ($options as $o) {
                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                        $disabled = '';
                        $limitText = '';
                        if ($o['limit'] > 0) {
                            $left = $o['limit'] - $currentCount;
                            if ($left <= 0 && $val !== $o['label']) {
                                $disabled = 'disabled';
                                $limitText = " (Brak miejsc)";
                            } else {
                                $limitText = " (Zostało: {$left})";
                            }
                        }
                        $selected = ($val === $o['label']) ? 'selected' : '';
                        $html .= "<option value='".htmlspecialchars($o['label'])."' {$disabled} {$selected}>".htmlspecialchars($o['label']) . $limitText . "</option>";
                    }
                    $html .= "</select>";
                } elseif ($type === 'radio') {
                    $html .= "<div class='flex flex-col gap-2'>";
                    foreach ($options as $idx => $o) {
                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                        $disabled = '';
                        $limitText = '';
                        if ($o['limit'] > 0) {
                            $left = $o['limit'] - $currentCount;
                            if ($left <= 0 && $val !== $o['label']) {
                                $disabled = 'disabled';
                                $limitText = " <span class='text-xs text-red-500 font-bold ml-1'>(Brak miejsc)</span>";
                            } else {
                                $limitText = " <span class='text-xs text-gray-500 ml-1'>(Zostało: {$left})</span>";
                            }
                        }
                        $checked = ($val === $o['label']) ? 'checked' : '';
                        $html .= "<label class='flex items-center gap-2 text-sm p-3 border rounded-xl bg-white ".($disabled?'opacity-50 cursor-not-allowed':'')."'><input type='radio' name='data[{$fieldId}]' value='".htmlspecialchars($o['label'])."' {$disabled} {$checked} {$reqAttr}><span>".htmlspecialchars($o['label']) . $limitText . "</span></label>";
                    }
                    $html .= "</div>";
                } elseif ($type === 'checkbox') {
                    $valArr = is_array($val) ? $val : (is_string($val) && strpos($val, ',') !== false ? explode(', ', $val) : [$val]);
                    $html .= "<div class='flex flex-col gap-2'>";
                    foreach ($options as $idx => $o) {
                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                        $disabled = '';
                        $limitText = '';
                        if ($o['limit'] > 0) {
                            $left = $o['limit'] - $currentCount;
                            if ($left <= 0 && !in_array($o['label'], $valArr)) {
                                $disabled = 'disabled';
                                $limitText = " <span class='text-xs text-red-500 font-bold ml-1'>(Brak miejsc)</span>";
                            } else {
                                $limitText = " <span class='text-xs text-gray-500 ml-1'>(Zostało: {$left})</span>";
                            }
                        }
                        $checked = in_array($o['label'], $valArr) ? 'checked' : '';
                        $html .= "<label class='flex items-center gap-2 text-sm p-3 border rounded-xl bg-white ".($disabled?'opacity-50 cursor-not-allowed':'')."'><input type='checkbox' name='data[{$fieldId}][]' value='".htmlspecialchars($o['label'])."' {$disabled} {$checked}><span>".htmlspecialchars($o['label']) . $limitText . "</span></label>";
                    }
                    $html .= "</div>";
                }
            } elseif ($type === 'textarea') {
                $html .= "<textarea name='data[{$fieldId}]' class='{$inputClasses}' rows='4' {$reqAttr}>".htmlspecialchars(is_array($val)?'':$val)."</textarea>";
            } else {
                $html .= "<input type='{$type}' name='data[{$fieldId}]' value='".htmlspecialchars(is_array($val)?'':$val)."' class='{$inputClasses}' {$reqAttr}>";
            }
            $html .= "</div>";
        }

        $btnText = $isEditing ? 'Zaktualizuj formularz' : 'Wyślij formularz';
        $html .= '<div class="md:col-span-3 mt-6 pt-6 border-t border-gray-200 relative">';
        if (!$isEditing) $html .= "<div id='autosave-status-{$form['id']}' class='text-sm font-bold text-gray-500 mb-3 hidden transition-all flex items-center gap-2'></div>";
        $html .= '<button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition-all" type="submit">'.$btnText.'</button>';
        $html .= '</div></form>';

        $html .= "<script>
        (function() {
            const formContainer = document.getElementById('form-container-{$form['id']}');
            if (!formContainer) return;
            const form = formContainer.querySelector('form');
            if (!form) return;
            
            // Zmienne do kontroli przycisku Submit
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Wyślij';
            let activeUploads = 0;
        
            let autosaveTimeout;
            const statusEl = document.getElementById('autosave-status-{$form['id']}');
        
            function triggerAutosave() {
                if (!statusEl) return;
                statusEl.innerHTML = '⏳ Zapisywanie robocze...';
                statusEl.classList.remove('hidden');
                clearTimeout(autosaveTimeout);
                autosaveTimeout = setTimeout(() => {
                    const formData = new FormData(form);
                    fetch('/form-autosave', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
                        if (data.status === 'success') {
                            statusEl.innerHTML = '✅ Zapisano roboczo';
                            if (data.submission_id && !form.querySelector('input[name=\"submission_id\"]')) {
                                const subInput = document.createElement('input');
                                subInput.type = 'hidden';
                                subInput.name = 'submission_id';
                                subInput.value = data.submission_id;
                                form.appendChild(subInput);
                            }
                        }
                    });
                }, 1500);
            }
        
            if (statusEl) {
                form.addEventListener('input', e => {
                    if (e.target.name !== 'captcha_answer' && e.target.type !== 'password' && e.target.type !== 'file') triggerAutosave();
                });
            }
        
            form.querySelectorAll('.async-file-upload').forEach(container => {
                const fileInput = container.querySelector('.file-input');
                const filesList = container.querySelector('.uploaded-files-list');
                const progressContainer = container.querySelector('.progress-container');
                const progressBar = container.querySelector('.progress-bar');
                const fieldId = container.dataset.fieldId;
                const dropArea = fileInput.closest('.border-dashed'); // Pobieramy przerywany kontener
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                // Dodajemy ciemniejsze tło, inną ramkę i lekkie powiększenie
                dropArea.classList.add('bg-blue-200', 'border-blue-500', 'scale-105');
            }, false);
        });

        ['dragleave'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                // Usuwamy klasy po opuszczeniu strefy
                dropArea.classList.remove('bg-blue-200', 'border-blue-500', 'scale-105');
            }, false);
        });

        dropArea.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            // Usuwamy klasy po upuszczeniu pliku
            dropArea.classList.remove('bg-blue-200', 'border-blue-500', 'scale-105');

            // Przechwytujemy pliki i wymuszamy uruchomienie przesyłania
            if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        }, false);
        
                // Obsługa usuwania już istniejących plików
                filesList.querySelectorAll('.remove-existing-file').forEach(btn => {
                    btn.addEventListener('click', function() {
                        this.closest('.existing-file-item').remove();
                        if (statusEl) triggerAutosave();
                    });
                });
        
                // Obsługa wgrywania nowego pliku
                fileInput.addEventListener('change', function() {
                    const files = this.files;
                    if (files.length === 0) return;
        
                    Array.from(files).forEach(file => {
                        activeUploads++;
                        
                        // Blokada przycisku Submit
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '⏳ Przesyłanie plików...';
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
        
                        // Pokazanie paska postępu
                        if (progressContainer && progressBar) {
                            progressContainer.classList.remove('hidden');
                            progressBar.style.width = '0%';
                        }
        
                        const formData = new FormData();
                        formData.append('file', file);
                        
                        // Pobieranie dozwolonych rozszerzeń (jeśli dodałeś tę opcję w poprzednim kroku)
                        const allowedExts = fileInput.dataset.allowedExts || 'jpg, jpeg, png, pdf, doc, docx, zip';
                        formData.append('allowed_exts', allowedExts);
        
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '/form-upload', true);
        
                        // Aktualizacja paska postępu
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable && progressBar) {
                                const percentComplete = Math.round((e.loaded / e.total) * 100);
                                progressBar.style.width = percentComplete + '%';
                            }
                        });
        
                        xhr.onload = function() {
                            activeUploads--;
                            
                            // Odblokowanie przycisku, jeśli to był ostatni plik
                            if (activeUploads === 0) {
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnText;
                                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                }
                                if (progressContainer) progressContainer.classList.add('hidden');
                            }
        
                            if (xhr.status === 200) {
                                const res = JSON.parse(xhr.responseText);
                                if (res.status === 'success') {
                                    const safeJson = JSON.stringify(res.file).replace(/'/g, '&#39;');
const dlUrl = '/admin/forms/download?file=' + encodeURIComponent(res.file.storage_name) + '&orig=' + encodeURIComponent(file.name);

const item = document.createElement('div');
item.className = 'flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm group hover:border-blue-300 transition-colors existing-file-item mt-2';
item.innerHTML = '<div class=\"flex items-center gap-3 overflow-hidden\">' +
                 '<div class=\"bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0\"><svg class=\"w-4 h-4\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z\" clip-rule=\"evenodd\"></path></svg></div>' +
                 '<a href=\"' + dlUrl + '\" target=\"_blank\" class=\"truncate font-medium hover:text-blue-600 hover:underline transition-colors\">' + file.name + '</a>' +
                 '</div>' +
                 '<button type=\"button\" class=\"text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors remove-existing-file\" title=\"Usuń plik\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path></svg></button>' +
                 '<input type=\"hidden\" name=\"async_files[' + fieldId + '][]\" value=\'' + safeJson + '\'>';
                                    
                                    item.querySelector('.remove-existing-file').addEventListener('click', function() {
                                        item.remove();
                                        if (statusEl) triggerAutosave();
                                    });
                                    filesList.appendChild(item);
                                    if (statusEl) triggerAutosave();
                                } else {
                                    alert(res.msg || 'Wystąpił błąd podczas wgrywania pliku.');
                                }
                            }
                        };
        
                        xhr.onerror = function() {
                            activeUploads--;
                            if (activeUploads === 0) {
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnText;
                                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                }
                                if (progressContainer) progressContainer.classList.add('hidden');
                            }
                            alert('Błąd sieci podczas wgrywania pliku.');
                        };
        
                        xhr.send(formData);
                    });
                    this.value = '';
                });
            });
        })();
        </script></div>";
        return $html;
    }
}