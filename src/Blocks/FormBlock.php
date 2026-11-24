<?php
namespace CMS\Blocks;

class FormBlock implements BlockInterface {
    private static $turnstileLoaded = false;

    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }

    private function getServerMaxUploadBytes() {
        $maxUpload = $this->parseSize(ini_get('upload_max_filesize'));
        $maxPost = $this->parseSize(ini_get('post_max_size'));
        if ($maxPost <= 0) return $maxUpload; // 0 w post_max_size oznacza brak limitu
        return min($maxUpload, $maxPost);
    }

    /** Klasa szerokości pola w siatce 6-kolumnowej (patrz .pa-form-grid w input.css). */
    private function widthClass(array $field): string {
        $w = $field['width'] ?? 'full';
        return 'pa-fw pa-fw-' . (in_array($w, ['sixth', 'third', 'semi', 'half', 'full'], true) ? $w : 'full');
    }

    public function render(array $block, $db): string {
        try {
            $db->query("ALTER TABLE pa_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted' AFTER user_ip");
        } catch (\Exception $e) {}

        $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
        if (!$form) return '';

        $formSettings = json_decode($form['settings'] ?? '{}', true) ?: [];
        $userId = \CMS\Core\Session::get('user_id');
        $compact = ($formSettings['style'] ?? 'default') === 'compact';
        $lockFilled = !empty($formSettings['lockFilled']);
        $fid = (int)$form['id'];

        // Zestaw klas zależny od wyglądu formularza
        $ui = $compact ? [
            'box'   => 'pa-form pa-form-compact',
            'title' => 'pa-form-title',
            'label' => 'pa-label',
            'input' => 'pa-input',
            'grid'  => 'pa-form-grid',
            'html'  => 'pa-form-html',
            'opt'   => 'pa-opt',
            'card'  => 'pa-form-card',
        ] : [
            'box'   => 'pa-form bg-gray-50 border border-gray-200 p-6 md:p-10 rounded-2xl mb-8 shadow-sm',
            'title' => 'text-2xl font-extrabold mb-8 text-gray-800',
            'label' => 'block text-sm font-bold text-gray-800 mb-2',
            'input' => 'pa-input w-full border border-gray-200 bg-gray-50/50 text-gray-800 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white block p-3.5 shadow-sm transition-all outline-none',
            'grid'  => 'pa-form-grid pa-form-grid-roomy',
            'html'  => 'prose max-w-none text-sm bg-white p-6 whitespace-pre-line rounded-xl border border-gray-100',
            'opt'   => 'flex items-center gap-2 text-sm p-3 border rounded-xl bg-white',
            'card'  => 'bg-white p-5 rounded-2xl border border-gray-200 shadow-sm',
        ];

        $html = '<div class="' . $ui['box'] . '" id="form-container-' . $fid . '">';
        $html .= '<h3 class="' . $ui['title'] . '">' . htmlspecialchars($form['title']) . '</h3>';

        if (!empty($formSettings['reqLogin']) && !$userId) {
            $html .= '<div class="bg-yellow-50 p-6 rounded-xl text-yellow-800 border border-yellow-200 flex items-center gap-4"><span class="text-3xl"><i class=i-lock-keyhole-thin></i></span><div>Zaloguj się, aby wypełnić formularz.<br><a href="/login" class="font-bold underline text-yellow-900 mt-1 inline-block">Zaloguj się</a></div></div></div>';
            return $html;
        }

        $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $fid;
        // Edycja tylko gdy formularz na to pozwala (wcześniej wystarczył parametr ?edit=)
        $canEdit = !empty($formSettings['editable']) || $lockFilled;
        $isEditing = $canEdit && isset($_GET['edit']) && $_GET['edit'] == $fid;

        $existingSubmission = null; $draftSubmission = null;
        if ($userId) {
            $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'submitted' ORDER BY id DESC LIMIT 1", [$fid, $userId])->fetch();
            $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1", [$fid, $userId])->fetch();
        } else {
            $guestDraftId = \CMS\Core\Session::get('draft_' . $fid);
            if ($guestDraftId) $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE id = ? AND status = 'draft'", [$guestDraftId])->fetch();
        }

        // Tryb uzupełniania: zalogowany użytkownik z wysłanym zgłoszeniem od razu widzi swój formularz
        if ($lockFilled && $existingSubmission && !$isSubmittedNow) $isEditing = true;

        $flash = \CMS\Core\Session::getFlash();
        if ($flash && isset($_GET['err_form']) && $_GET['err_form'] == $fid) {
            $html .= '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-r-lg mb-6 text-sm font-bold shadow-sm"><i class=i-triangle-alert></i> ' . htmlspecialchars($flash['msg']) . '</div>';
        }

        $vault = new \CMS\Core\Vault();
        $fields = json_decode($form['form_json'], true) ?? [];

        $showThankYou = false;
        if ($userId) {
            if ($existingSubmission && ($isSubmittedNow || (!empty($formSettings['fillOnce']) && !$isEditing))) $showThankYou = true;
        } else {
            if ($isSubmittedNow) $showThankYou = true;
        }

        if ($showThankYou) {
            // Ile pól jeszcze czeka na uzupełnienie?
            $missing = 0;
            if ($lockFilled && $existingSubmission) {
                $d = json_decode($vault->decrypt($existingSubmission['data_json']) ?: '[]', true) ?? [];
                $f = json_decode($existingSubmission['files_json'] ?? '{}', true) ?? [];
                foreach ($fields as $fd) {
                    if (!\CMS\Services\FormSubmissionService::isLockable($fd)) continue;
                    $k = $fd['custom_id'] ?? $fd['id'] ?? '';
                    $empty = ($fd['type'] ?? '') === 'file' ? empty($f[$k]) : \CMS\Services\FormSubmissionService::isEmptyValue($d[$k] ?? null);
                    if ($empty) $missing++;
                }
            }
            $html .= '<div class="pa-form-thanks bg-green-50 border border-green-200 text-green-800 px-6 py-6 rounded-xl mb-4 text-center"><span class="text-4xl mb-3 block"><i class=i-party-popper-thin></i></span><strong class="text-xl block mb-1">Dziękujemy!</strong><p class="text-green-700">Twój formularz został zapisany.</p>';
            if ($missing > 0) $html .= '<p class="text-green-900 font-bold mt-2">Do uzupełnienia zostało pól: ' . $missing . '</p>';
            $html .= '</div><div class="flex flex-wrap justify-center gap-3 mt-4">';
            if ($canEdit && $existingSubmission) {
                $label = $lockFilled ? ($missing > 0 ? '<i class=i-pencil></i> Uzupełnij brakujące dane' : '<i class=i-eye></i> Zobacz przesłane dane') : '<i class=i-pencil></i> Edytuj';
                $html .= '<a href="?edit=' . $fid . '#form-container-' . $fid . '" class="bg-white border border-gray-300 hover:border-blue-500 hover:text-blue-600 text-gray-700 font-bold py-3 px-6 rounded-xl shadow-sm transition-all">' . $label . '</a>';
            }
            if (empty($formSettings['fillOnce']) && !$lockFilled) {
                $html .= '<a href="?#form-container-' . $fid . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition-all"><i class=i-refresh-cw></i> Wyślij ponownie</a>';
            }
            $html .= '</div></div>';
            return $html;
        }

        $prefill = []; $existingFiles = [];
        $activeSubmissionToLoad = ($draftSubmission && !$isEditing) ? $draftSubmission : ($isEditing ? $existingSubmission : null);

        if ($activeSubmissionToLoad) {
            $prefill = json_decode($vault->decrypt($activeSubmissionToLoad['data_json']), true) ?? [];
            $existingFiles = json_decode($activeSubmissionToLoad['files_json'] ?? '{}', true) ?? [];
        }
        // Blokujemy tylko dane z WYSŁANEGO zgłoszenia (szkic można dowolnie poprawiać)
        $lockActive = $lockFilled && $activeSubmissionToLoad && ($activeSubmissionToLoad['status'] ?? '') === 'submitted';

        $subs = $db->query("SELECT data_json FROM pa_submissions WHERE form_id = :id", ['id' => $fid])->fetchAll();
        $optionCounts = [];
        foreach ($subs as $s) {
            $d = json_decode($vault->decrypt($s['data_json']), true) ?? [];
            foreach ($d as $fk => $fv) {
                if (is_array($fv)) foreach ($fv as $v) $optionCounts[$fk][$v] = ($optionCounts[$fk][$v] ?? 0) + 1;
                else $optionCounts[$fk][$fv] = ($optionCounts[$fk][$fv] ?? 0) + 1;
            }
        }

        $inputClasses = $ui['input'];

        if ($lockActive) {
            $html .= '<div class="pa-form-lockinfo"><i class=i-lock></i> Pola, które już przesłałeś, są zablokowane. Możesz uzupełnić tylko brakujące dane. Aby zmienić przesłane informacje, skontaktuj się z organizatorem.</div>';
        }

        $html .= '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="' . $ui['grid'] . '">';
        $html .= '<input type="hidden" name="csrf_token" value="'.\CMS\Core\Session::generateCsrfToken().'">';
        $html .= '<input type="hidden" name="form_id" value="'.$fid.'">';
        $html .= '<input type="hidden" name="return_url" value="'.htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/').'">';
        if ($activeSubmissionToLoad) $html .= '<input type="hidden" name="submission_id" value="'.$activeSubmissionToLoad['id'].'">';

        $lockedCount = 0;
        $editableCount = 0;
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'text';
            $widthClass = $this->widthClass($field);

            if ($type === 'section') {
                $html .= '<div class="pa-fw pa-fw-full pa-form-section"><h4>' . htmlspecialchars($field['label'] ?? '') . '</h4>';
                if (!empty($field['desc'])) $html .= '<p>' . nl2br(htmlspecialchars($field['desc'])) . '</p>';
                $html .= '</div>';
                continue;
            }
            if ($type === 'html') {
                $html .= "<div class='{$widthClass} {$ui['html']}'>" . ($field['html'] ?? '') . "</div>"; continue;
            }
            if ($type === 'account') {
                // Pole "Konto (e-mail)" – tylko do odczytu, pokazuje adres zalogowanego użytkownika (nie jest wysyłane)
                if (!$userId) continue;
                $email = $db->query("SELECT email FROM pa_users WHERE id = ?", [$userId])->fetchColumn();
                if (!$email) continue;
                $lbl = trim((string)($field['label'] ?? ''));
                $html .= "<div class='{$widthClass} pa-form-account'>" . ($lbl !== '' ? "<label class='{$ui['label']}'>" . htmlspecialchars($lbl) . "</label>" : '')
                       . "<input type='text' class='{$inputClasses}' value='" . htmlspecialchars($email, ENT_QUOTES) . "' disabled aria-label='Konto'></div>";
                continue;
            }
            if ($type === 'honeypot') {
                $fieldId = $field['custom_id'] ?? $field['id'] ?? md5('hp' . rand());
                $html .= "<div style='position:absolute; left:-9999px; top:-9999px; opacity:0;' aria-hidden='true'><input type='text' name='hp_data_{$fieldId}' value='' tabindex='-1' autocomplete='off'></div>"; continue;
            }
            if ($type === 'captcha_image') {
                if (class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
                    $builder = new \Gregwar\Captcha\CaptchaBuilder; $builder->build();
                    \CMS\Core\Session::set('captcha_img_' . $fid, $builder->getPhrase());
                    $html .= "<div class='{$widthClass} {$ui['card']}'><label class='{$ui['label']}'>Weryfikacja *</label><div class='flex gap-4 items-center'><img src='{$builder->inline()}' class='rounded-xl border h-[56px]'><input type='text' name='captcha_answer' required class='{$inputClasses} flex-1'></div></div>";
                } continue;
            }
            if ($type === 'captcha_turnstile') {
                $siteKey = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'turnstile_site_key'")->fetch()['setting_value'] ?? '';
                if ($siteKey) {
                    if (!self::$turnstileLoaded) { $html .= "<script src='https://challenges.cloudflare.com/turnstile/v0/api.js' async defer></script>"; self::$turnstileLoaded = true; }
                    $html .= "<div class='{$widthClass}'><div class='cf-turnstile' data-sitekey='".htmlspecialchars($siteKey)."'></div></div>";
                } continue;
            }

            $fieldId = $field['custom_id'] ?? $field['id'] ?? md5($field['label'] ?? '');
            $val = $prefill[$fieldId] ?? '';
            $req = !empty($field['required']);
            $placeholder = trim((string)($field['placeholder'] ?? ''));
            $phAttr = $placeholder !== '' ? " placeholder='" . htmlspecialchars($placeholder, ENT_QUOTES) . "'" : '';

            // Czy to pole jest zablokowane (tryb uzupełniania)?
            $hasValue = $type === 'file' ? !empty($existingFiles[$fieldId]) : !\CMS\Services\FormSubmissionService::isEmptyValue($val);
            $locked = $lockActive && $hasValue && \CMS\Services\FormSubmissionService::isLockable($field);
            if ($locked) { $lockedCount++; $req = false; } else { $editableCount++; }
            $dis = $locked ? ' disabled' : '';
            $lockCls = $locked ? ' pa-locked' : '';
            $lockIcon = $locked ? " <span class='pa-lock' title='Dane przesłane – pole zablokowane'><i class=i-lock></i></span>" : '';

            $reqAttr = $req ? 'required' : '';
            $reqStar = $req ? '<span class="text-red-500 ml-1">*</span>' : '';
            $label = trim((string)($field['label'] ?? ''));
            $labelHtml = $label !== '' ? htmlspecialchars($label) . $reqStar . $lockIcon : '&nbsp;';

            // POLE PLIKÓW - ASYNCHRONICZNE
            if ($type === 'file') {
                if ($locked) {
                    $html .= "<div class='{$widthClass}{$lockCls}'><label class='{$ui['label']}'>{$labelHtml}</label><div class='uploaded-files-list flex flex-col gap-2'>";
                    $eFiles = isset($existingFiles[$fieldId]['original_name']) ? [$existingFiles[$fieldId]] : $existingFiles[$fieldId];
                    foreach ($eFiles as $eFile) {
                        if (empty($eFile['original_name']) || empty($eFile['storage_name'])) continue;
                        $dlUrl = "/form-download?file=" . urlencode($eFile['storage_name']) . "&orig=" . urlencode($eFile['original_name']);
                        $html .= "<a href='{$dlUrl}' target='_blank' class='pa-file-chip'><i class=i-paperclip></i> " . htmlspecialchars($eFile['original_name']) . "</a>";
                    }
                    $html .= "</div></div>";
                    continue;
                }
                $allowMultiple = !empty($formSettings['allowMultipleFiles']) ? 'multiple' : '';

                // --- OBLICZANIE MAX ROZMIARU PLIKU ---
                $serverMaxBytes = $this->getServerMaxUploadBytes();
                $serverMaxMB = floor($serverMaxBytes / (1024 * 1024));
                $adminMaxMB = !empty($field['maxSize']) ? (int)$field['maxSize'] : $serverMaxMB;
                $effectiveMaxMB = min($serverMaxMB, $adminMaxMB);
                $effectiveMaxBytes = $effectiveMaxMB * 1024 * 1024;

                $html .= "<div class='{$widthClass} async-file-upload' data-field-id='{$fieldId}'>";
                $html .= "<label class='{$ui['label']}' for='{$fieldId}'>{$labelHtml}</label>";

                $hasExisting = isset($existingFiles[$fieldId]) && !empty($existingFiles[$fieldId]);
                $currentReqAttr = ($hasExisting) ? '' : $reqAttr;

                $allowedExtsRaw = $field['allowedExts'] ?? 'jpg, png, pdf, zip, doc, docx';
                $allowedExtsRawEscaped = htmlspecialchars($allowedExtsRaw, ENT_QUOTES);
                $exts = array_filter(array_map('trim', explode(',', $allowedExtsRaw)));
                $accepts = array_map(function($ext) { return '.' . ltrim($ext, '.'); }, $exts);
                $acceptAttr = !empty($accepts) ? 'accept="' . htmlspecialchars(implode(',', $accepts), ENT_QUOTES) . '"' : '';

                $html .= "<div class='pa-drop relative border-2 border-dashed border-blue-300 bg-blue-50/50 hover:bg-blue-100/50 rounded-xl py-4 px-6 text-center transition-all group overflow-hidden focus-within:ring-4 focus-within:ring-blue-100 focus-within:border-blue-500 flex flex-col items-center justify-center'>";
                $html .= "<input type='file' id='{$fieldId}' class='absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 file-input' {$currentReqAttr} {$allowMultiple} data-allowed-exts='{$allowedExtsRawEscaped}' data-max-size='{$effectiveMaxBytes}' {$acceptAttr}>";
                if ($req) $html .= "<input type='hidden' class='original-required-flag' value='1'>";
                $html .= "<div class='text-blue-500 mb-1 transition-transform group-hover:scale-110 flex justify-center'>
                            <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'></path></svg>
                          </div>";
                $html .= "<p class='text-sm font-bold text-blue-700 mb-0'>Przeciągnij plik lub kliknij</p>";
                $html .= "<p class='text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider'>MAX: {$effectiveMaxMB} MB</p>";
                $html .= "</div>";

                $html .= "<div class='progress-container hidden mt-3'>";
                $html .= "<div class='flex justify-between text-xs font-bold text-blue-800 mb-1'><span class='progress-text-name truncate max-w-[80%]'>Wgrywanie...</span><span class='progress-text-pct'>0%</span></div>";
                $html .= "<div class='w-full bg-blue-100 rounded-full h-2.5 overflow-hidden shadow-inner'><div class='progress-bar bg-blue-600 h-2.5 rounded-full transition-all duration-300' style='width: 0%'></div></div>";
                $html .= "</div>";

                $html .= "<div class='uploaded-files-list mt-3 flex flex-col gap-2'>";
                if ($hasExisting) {
                    $eFiles = isset($existingFiles[$fieldId]['original_name']) ? [$existingFiles[$fieldId]] : $existingFiles[$fieldId];
                    foreach ($eFiles as $eFile) {
                        if (empty($eFile['original_name']) || empty($eFile['storage_name'])) continue;
                        $jsonVal = htmlspecialchars(json_encode($eFile), ENT_QUOTES, 'UTF-8');
                        $origName = urlencode($eFile['original_name']);
                        $dlUrl = "/form-download?file=" . urlencode($eFile['storage_name']) . "&orig=" . $origName;
                        $html .= "<div class='flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm group hover:border-blue-300 transition-colors existing-file-item animate-fade-in'>";
                        $html .= "  <div class='flex items-center gap-3 overflow-hidden'>";
                        $html .= "    <div class='bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0'><svg class='w-4 h-4' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z' clip-rule='evenodd'></path></svg></div>";
                        $html .= "    <a href='{$dlUrl}' target='_blank' class='truncate font-medium hover:text-blue-600 transition-colors'>".htmlspecialchars($eFile['original_name'])."</a>";
                        $html .= "  </div>";
                        $html .= "  <button type='button' class='text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors remove-existing-file' title='Usuń plik'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button>";
                        $html .= "  <input type='hidden' name='async_files[{$fieldId}][]' value='{$jsonVal}'>";
                        $html .= "</div>";
                    }
                }
                $html .= "</div>";
                $html .= "</div>";
                continue;
            }

            // Pusta etykieta: przy polach obok siebie zostawiamy odstęp (wyrównanie), przy pełnej szerokości – nic
            $labelTag = ($label === '' && ($field['width'] ?? 'full') === 'full') ? '' : "<label class='{$ui['label']}'>{$labelHtml}</label>";
            $html .= "<div class='{$widthClass}{$lockCls}'>{$labelTag}";
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                $optionsRaw = explode("\n", trim($field['options'] ?? ''));
                $options = []; foreach ($optionsRaw as $opt) { if ($opt) { $parts = explode('|limit:', $opt); $options[] = ['label' => trim($parts[0]), 'limit' => isset($parts[1]) ? (int)trim($parts[1]) : 0]; } }

                if ($type === 'select') {
                    $html .= "<select name='data[{$fieldId}]' class='{$inputClasses}' {$reqAttr}{$dis}><option value=''>" . htmlspecialchars($placeholder !== '' ? $placeholder : '-- Wybierz --') . "</option>";
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
                } else {
                    $inline = !empty($field['inline']);
                    $html .= "<div class='pa-opts " . ($inline ? 'pa-opts-inline flex flex-wrap gap-x-4 gap-y-2' : 'flex flex-col gap-2') . "'>";
                    $valArr = $type === 'checkbox'
                        ? (is_array($val) ? $val : (is_string($val) && strpos($val, ',') !== false ? explode(', ', $val) : [$val]))
                        : [];
                    foreach ($options as $o) {
                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                        $isChosen = $type === 'radio' ? ($val === $o['label']) : in_array($o['label'], $valArr);
                        $disabled = '';
                        $limitText = '';
                        if ($o['limit'] > 0) {
                            $left = $o['limit'] - $currentCount;
                            if ($left <= 0 && !$isChosen) {
                                $disabled = 'disabled';
                                $limitText = " <span class='text-xs text-red-500 font-bold ml-1'>(Brak miejsc)</span>";
                            } else {
                                $limitText = " <span class='text-xs text-gray-500 ml-1'>(Zostało: {$left})</span>";
                            }
                        }
                        if ($locked) $disabled = 'disabled';
                        $checked = $isChosen ? 'checked' : '';
                        $name = $type === 'radio' ? "data[{$fieldId}]" : "data[{$fieldId}][]";
                        $rq = $type === 'radio' ? $reqAttr : '';
                        $html .= "<label class='{$ui['opt']} ".($disabled && !$locked ? 'opacity-50 cursor-not-allowed' : '')."'><input type='{$type}' name='{$name}' value='".htmlspecialchars($o['label'])."' {$disabled} {$checked} {$rq}><span>".htmlspecialchars($o['label']) . $limitText . "</span></label>";
                    }
                    $html .= "</div>";
                }
            } elseif ($type === 'textarea') {
                $html .= "<textarea name='data[{$fieldId}]' class='{$inputClasses}' rows='4' {$reqAttr}{$phAttr}{$dis}>".htmlspecialchars(is_array($val)?'':$val)."</textarea>";
            } else {
                $inputType = $type === 'phone' ? 'tel' : $type;
                $html .= "<input type='{$inputType}' name='data[{$fieldId}]' value='".htmlspecialchars(is_array($val)?'':$val)."' class='{$inputClasses}' {$reqAttr}{$phAttr}{$dis}>";
            }
            $html .= "</div>";
        }

        $submitLabel = trim((string)($formSettings['submitLabel'] ?? '')) ?: ($isEditing ? 'Zaktualizuj formularz' : 'Wyślij formularz');
        if ($lockActive && $editableCount === 0) {
            // Wszystko przesłane i zablokowane – nie ma czego wysyłać
            $html .= '<div class="pa-fw pa-fw-full pa-form-actions"><div class="pa-form-lockinfo"><i class=i-circle-check></i> Wszystkie dane zostały przesłane.</div></div></form>';
        } else {
            $html .= '<div class="pa-fw pa-fw-full pa-form-actions">';
            if (!$isEditing) $html .= "<div id='autosave-status-{$fid}' class='text-sm font-bold text-gray-500 mb-3 hidden transition-all flex items-center gap-2'></div>";
            $html .= '<div class="pa-form-buttons">';
            if ($lockFilled && $userId) {
                // Zapis częściowy – pomija walidację wymaganych pól, resztę można uzupełnić później
                $html .= '<button type="submit" name="partial" value="1" formnovalidate class="pa-btn-partial"><i class=i-save></i> Zapisz – uzupełnię resztę później</button>';
            }
            $submitCls = $compact ? 'pa-btn-submit' : 'w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition-all';
            $html .= '<button class="' . $submitCls . '" type="submit">' . htmlspecialchars($submitLabel) . '</button>';
            $html .= '</div>';
            if ($lockFilled && $userId) {
                $html .= '<p class="pa-form-note">Uwaga: po zapisaniu wypełnione pola zostaną zablokowane do edycji.</p>';
            }
            $html .= '</div></form>';
        }

        $html .= "<script>
        (function() {
            const formContainer = document.getElementById('form-container-{$fid}');
            if (!formContainer) return;
            const form = formContainer.querySelector('form');
            if (!form) return;
            
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Wyślij';
            let activeUploads = 0;
            let autosaveTimeout;
            const statusEl = document.getElementById('autosave-status-{$fid}');
        
            function triggerAutosave() {
                if (!statusEl) return;
                statusEl.innerHTML = '<i class=i-loader-circle></i> Zapisywanie robocze...';
                statusEl.classList.remove('hidden');
                clearTimeout(autosaveTimeout);
                autosaveTimeout = setTimeout(() => {
                    const formData = new FormData(form);
                    fetch('/form-autosave', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
                        if (data.status === 'success') {
                            statusEl.innerHTML = '<i class=i-circle-check></i> Zapisano roboczo';
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
                const dropArea = fileInput.closest('.border-dashed');
                
                // SPRAWDZAMY CZY UŻYTKOWNIK MOŻE DODAWAĆ WIELE PLIKÓW
                const isMultiple = fileInput.hasAttribute('multiple');
        
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, e => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropArea.classList.add('bg-blue-200', 'border-blue-500', 'scale-105');
                    }, false);
                });

                ['dragleave'].forEach(eventName => {
                    dropArea.addEventListener(eventName, e => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropArea.classList.remove('bg-blue-200', 'border-blue-500', 'scale-105');
                    }, false);
                });

                dropArea.addEventListener('drop', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropArea.classList.remove('bg-blue-200', 'border-blue-500', 'scale-105');

                    if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                        // FIX: Jeśli to pole pojedynczego pliku a ktoś upuścił kilka, weź tylko pierwszy!
                        if (!isMultiple && e.dataTransfer.files.length > 1) {
                            const dt = new DataTransfer();
                            dt.items.add(e.dataTransfer.files[0]);
                            fileInput.files = dt.files;
                        } else {
                            fileInput.files = e.dataTransfer.files;
                        }
                        fileInput.dispatchEvent(new Event('change'));
                    }
                }, false);
        
                filesList.querySelectorAll('.remove-existing-file').forEach(btn => {
                    btn.addEventListener('click', function() {
                        this.closest('.existing-file-item').remove();
                        if (statusEl) triggerAutosave();
                    });
                });
        
                fileInput.addEventListener('change', function() {
                    const files = this.files;
                    if (files.length === 0) return;
                    
                    // FIX: Czyścimy widok i ukryte inputy z bazy, jeśli pole przyjmuje tylko 1 plik
                    if (!isMultiple) {
                        filesList.innerHTML = '';
                        form.querySelectorAll('input[name=\"async_files[' + fieldId + '][]\"]').forEach(el => el.remove());
                    }
        
                    Array.from(files).forEach(file => {
                        const allowedExts = fileInput.dataset.allowedExts || \"jpg, png, pdf, zip, doc, docx\";
                        const maxSize = parseInt(fileInput.dataset.maxSize) || 0;

                        if (maxSize > 0 && file.size > maxSize) {
                            alert(\"Plik \" + file.name + \" jest za duży! Maksymalny dozwolony rozmiar to \" + (maxSize / (1024 * 1024)) + \" MB.\");
                            return;
                        }
                        
                        activeUploads++;
                        
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class=i-loader-circle></i> Przesyłanie plików...';
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
        
                        if (progressContainer && progressBar) {
                            progressContainer.classList.remove('hidden');
                            progressBar.style.width = '0%';
                        }
        
                        const formData = new FormData();
                        formData.append('file', file);
                        formData.append('allowed_exts', allowedExts);
                        formData.append('max_size', maxSize);
        
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '/form-upload', true);
        
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable && progressBar) {
                                const percentComplete = Math.round((e.loaded / e.total) * 100);
                                progressBar.style.width = percentComplete + '%';
                            }
                        });
        
                        xhr.onload = function() {
                            activeUploads--;
                            
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
                                    fileInput.required = false;
                                    fileInput.removeAttribute('required');
                                    const safeJson = JSON.stringify(res.file).replace(/\'/g, '&#39;');
                                    const dlUrl = '/form-download?file=' + encodeURIComponent(res.file.storage_name) + '&orig=' + encodeURIComponent(file.name);

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
                                        if (filesList.children.length === 0 && container.querySelector('.original-required-flag')) {
                                            fileInput.required = true;
                                            fileInput.setAttribute('required', 'required');
                                        }
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