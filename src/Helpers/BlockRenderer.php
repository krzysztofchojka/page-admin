<?php
namespace CMS\Helpers;

class BlockRenderer {
    // Zapobiega wielokrotnemu pobieraniu skryptów Swiper i GLightbox na jednej stronie
    private static $galleryAssetsLoaded = false;
    private static $turnstileLoaded = false;

    public static function render($blocks, $db) {
        if (empty($blocks)) return;

        foreach ($blocks as $block) {
            $bType = $block['type'] ?? ''; // Bezpieczne pobranie typu (zapobiega PHP Warning)
            if (empty($bType)) continue;

            $set = $block['settings'] ?? [];
            $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
            $clsAttr = !empty($set['css']) ? ' ' . htmlspecialchars($set['css']) : '';
            $styleAttr = !empty($set['style']) ? ' style="'.htmlspecialchars($set['style']).'"' : '';

            echo "<div{$idAttr} class=\"block-wrapper mb-0{$clsAttr}\"{$styleAttr}>";

            // 1. COLUMNS 2
            if ($bType === 'columns_2') {
                echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">';
                echo '<div>'; if(!empty($block['children']['left'])) self::render($block['children']['left'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['right'])) self::render($block['children']['right'], $db); echo '</div>';
                echo '</div>';
            }
            // 2. COLUMNS 3
            elseif ($bType === 'columns_3') {
                echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">';
                echo '<div>'; if(!empty($block['children']['left'])) self::render($block['children']['left'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['center'])) self::render($block['children']['center'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['right'])) self::render($block['children']['right'], $db); echo '</div>';
                echo '</div>';
            }
            // 3. TEXT
            elseif ($bType === 'text') {
                echo '<div class="prose max-w-none mb-0">' . $block['content'] . '</div>';
            }
            // 4. IMAGE
            elseif ($bType === 'image') {
                if (!empty($block['content'])) {
                    echo '<div class="mb-6"><img src="' . htmlspecialchars($block['content']) . '" class="w-full rounded-xl shadow-lg"></div>';
                }
            }
            // 5. WIDEO
            elseif ($bType === 'video') {
                $url = $block['content'] ?? '';
                if ($url) {
                    echo '<div class="w-full aspect-video mb-8 overflow-hidden rounded-xl shadow-lg bg-black">';
                    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                        $embedUrl = preg_replace('/watch\?v=([a-zA-Z0-9_-]+)/', 'embed/$1', $url);
                        $embedUrl = str_replace('youtu.be/', 'youtube.com/embed/', $embedUrl);
                        echo '<iframe src="'.htmlspecialchars($embedUrl).'" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } elseif (strpos($url, 'vimeo.com') !== false) {
                        $vimeoId = substr(parse_url($url, PHP_URL_PATH), 1);
                        echo '<iframe src="https://player.vimeo.com/video/'.htmlspecialchars($vimeoId).'" class="w-full h-full border-0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                    } else {
                        echo '<video controls class="w-full h-full outline-none"><source src="'.htmlspecialchars($url).'" type="video/mp4">Twoja przeglądarka nie obsługuje tagu video.</video>';
                    }
                    echo '</div>';
                }
            }
            // 6. PRZYCISK
            elseif ($bType === 'button') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $label = $data['label'] ?? 'Kliknij tutaj';
                $url = $data['url'] ?? '#';
                $style = $data['style'] ?? 'primary';
                
                $btnClass = 'inline-block px-8 py-3 rounded-lg font-bold transition shadow hover:shadow-lg text-center ';
                if ($style === 'primary') {
                    $btnClass .= 'bg-primary text-white hover:opacity-90';
                } else {
                    $btnClass .= 'border-2 border-primary text-primary hover:bg-primary hover:text-white';
                }

                echo '<div class="mb-8">';
                echo '<a href="'.htmlspecialchars($url).'" class="'.$btnClass.'">'.htmlspecialchars($label).'</a>';
                echo '</div>';
            }
            // 7. SEPARATOR
            elseif ($bType === 'divider') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $height = $data['height'] ?? '8';
                echo '<hr class="border-t border-gray-200 my-'.htmlspecialchars($height).' w-full">';
            }
            // 8. CYTAT
            elseif ($bType === 'quote') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $text = $data['text'] ?? '';
                $author = $data['author'] ?? '';
                echo '<blockquote class="border-l-4 border-yellow-500 bg-yellow-50 p-6 rounded-r-xl mb-8 shadow-sm">';
                echo '<p class="text-xl md:text-2xl italic font-serif text-gray-800 mb-4 leading-relaxed">"'.nl2br(htmlspecialchars($text)).'"</p>';
                if (!empty($author)) {
                    echo '<footer class="text-gray-600 font-bold tracking-wide text-sm uppercase">— '.htmlspecialchars($author).'</footer>';
                }
                echo '</blockquote>';
            }
            // 9. AKORDEON
            elseif ($bType === 'accordion') {
                $title = $block['content']['title'] ?? 'Kliknij, aby rozwinąć';
                echo '<details class="group mb-4 bg-white border border-gray-200 rounded-xl shadow-sm cursor-pointer overflow-hidden transition-all">';
                echo '<summary class="p-5 font-bold text-lg text-purple-800 bg-purple-50 list-none flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-purple-300">';
                echo '<span>'.htmlspecialchars($title).'</span>';
                echo '<span class="transition-transform duration-300 group-open:rotate-180 text-xl font-normal">▼</span>';
                echo '</summary>';
                echo '<div class="p-6 border-t border-purple-100 bg-white">';
                if (!empty($block['children'])) {
                    self::render($block['children'], $db);
                }
                echo '</div>';
                echo '</details>';
            }
            // 10. FORMULARZ
            elseif ($bType === 'form') {
                
                // AUTOMATYCZNA MIGRACJA W TLE - ZAPOBIEGA BŁĘDOWI "Unknown column 'status'"
                try {
                    $db->query("ALTER TABLE pa_submissions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'submitted' AFTER user_ip");
                } catch (\Exception $e) { /* Zignoruj jeśli kolumna już istnieje */ }

                $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
                if ($form) {
                    $formSettings = json_decode($form['settings'] ?? '{}', true);
                    $userId = \CMS\Core\Session::get('user_id');

                    echo '<div class="bg-gray-50 border border-gray-200 p-6 md:p-10 rounded-2xl mb-8 shadow-sm" id="form-container-'.$form['id'].'">';
                    echo '<h3 class="text-2xl font-extrabold mb-8 text-gray-800">' . htmlspecialchars($form['title']) . '</h3>';

                    if (!empty($formSettings['reqLogin']) && !$userId) {
                        echo '<div class="bg-yellow-50 p-6 rounded-xl text-yellow-800 border border-yellow-200 flex items-center gap-4"><span class="text-3xl">🔐</span><div>Zaloguj się, aby wyświetlić i wypełnić ten formularz. <br><a href="/login" class="font-bold underline text-yellow-900 mt-1 inline-block hover:text-yellow-700 transition-colors">Przejdź do logowania</a></div></div></div>';
                        continue;
                    }

                    $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $form['id'];
                    $isEditing = isset($_GET['edit']) && $_GET['edit'] == $form['id'];
                    $existingSubmission = null;
                    $draftSubmission = null;

                    if ($userId) {
                        $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'submitted' ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
                        $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? AND status = 'draft' ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
                    } else {
                        $guestDraftId = \CMS\Core\Session::get('draft_' . $form['id']);
                        if ($guestDraftId) {
                            $draftSubmission = $db->query("SELECT * FROM pa_submissions WHERE id = ? AND status = 'draft'", [$guestDraftId])->fetch();
                        }
                    }

                    $flash = \CMS\Core\Session::getFlash();
                    if ($flash && isset($_GET['err_form']) && $_GET['err_form'] == $form['id']) {
                        echo '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-4 rounded-r-lg mb-8 text-sm font-bold shadow-sm">⚠️ ' . htmlspecialchars($flash['msg']) . '</div>';
                    }

                    $showThankYou = false;
                    if ($userId) {
                        if ($existingSubmission && ($isSubmittedNow || (!empty($formSettings['fillOnce']) && !$isEditing))) {
                            $showThankYou = true;
                        }
                    } else {
                        if ($isSubmittedNow) $showThankYou = true;
                    }

                    if ($showThankYou) {
                        echo '<div class="bg-green-50 border border-green-200 text-green-800 px-8 py-8 rounded-xl mb-4 shadow-sm text-center"><span class="text-5xl mb-4 block">🎉</span> <strong class="text-2xl block mb-2">Dziękujemy!</strong><p class="text-green-700">Twój formularz został poprawnie zapisany.</p></div>';
                        echo '<div class="flex flex-wrap justify-center gap-4 mt-6">';
                        if (!empty($formSettings['editable']) && $existingSubmission) {
                            echo '<a href="?edit='.$form['id'].'#form-container-'.$form['id'].'" class="bg-white border border-gray-300 hover:border-blue-500 hover:text-blue-600 text-gray-700 font-bold py-3 px-8 rounded-xl shadow-sm transition-all">✏️ Kliknij, by edytować</a>';
                        }
                        if (empty($formSettings['fillOnce'])) {
                            echo '<a href="?#form-container-'.$form['id'].'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-all">🔄 Wyślij ponownie</a>';
                        }
                        echo '</div>';
                    } else {
                        $prefill = [];
                        $existingFiles = [];
                        $vault = new \CMS\Core\Vault();
                        
                        $activeSubmissionToLoad = null;
                        if ($draftSubmission && !$isEditing) {
                            $activeSubmissionToLoad = $draftSubmission;
                        } elseif ($isEditing && $existingSubmission) {
                            $activeSubmissionToLoad = $existingSubmission;
                        }

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

                        echo '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-8">';
                        echo '<input type="hidden" name="form_id" value="'.$form['id'].'">';
                        echo '<input type="hidden" name="return_url" value="'.htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/').'">';
                        
                        if ($activeSubmissionToLoad) {
                            echo '<input type="hidden" name="submission_id" value="'.$activeSubmissionToLoad['id'].'">';
                        }

                        $fields = json_decode($form['form_json'], true) ?? [];
                        foreach ($fields as $field) {
                            $type = $field['type'] ?? 'text';
                            $widthClass = ($field['width']??'full') === 'full' ? 'md:col-span-3' : (($field['width']??'full')==='half'?'md:col-span-2':'md:col-span-1');

                            if ($type === 'html') {
                                echo "<div class='{$widthClass} prose max-w-none text-sm bg-white p-6 rounded-xl border border-gray-100'>" . ($field['html'] ?? '') . "</div>";
                                continue;
                            }

                            // HONEYPOT
                            if ($type === 'honeypot') {
                                $fieldId = $field['custom_id'] ?? $field['id'] ?? md5('hp' . rand());
                                echo "<div style='position:absolute; left:-9999px; top:-9999px; opacity:0;' aria-hidden='true'>";
                                echo "<label for='hp_{$fieldId}'>Nie wypełniaj tego pola, jeśli jesteś człowiekiem</label>";
                                echo "<input type='text' id='hp_{$fieldId}' name='hp_data_{$fieldId}' value='' tabindex='-1' autocomplete='off'>";
                                echo "</div>";
                                continue;
                            }

                            // CAPTCHA OBRAZKOWA
                            if ($type === 'captcha_image') {
                                if (!class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
                                    echo "<div class='{$widthClass} p-3 bg-red-100 text-red-700 text-xs font-bold rounded'>Błąd: Brak biblioteki Gregwar/Captcha.</div>";
                                    continue;
                                }
                                $builder = new \Gregwar\Captcha\CaptchaBuilder;
                                $builder->build();
                                \CMS\Core\Session::set('captcha_img_' . $form['id'], $builder->getPhrase());

                                echo "<div class='{$widthClass} bg-white p-5 rounded-2xl border border-gray-200 shadow-sm'>";
                                echo "<label class='block text-sm font-bold text-gray-700 mb-3'>Weryfikacja <span class='text-red-500'>*</span></label>";
                                echo "<div class='flex flex-col sm:flex-row gap-4 items-center'>";
                                echo "<img src='{$builder->inline()}' class='rounded-xl border border-gray-200 shadow-sm h-[56px] pointer-events-none select-none w-auto object-cover'>";
                                echo "<input type='text' name='captcha_answer' required class='{$inputClasses} flex-1 min-w-[150px]' placeholder='Przepisz kod z obrazka...'>";
                                echo "</div></div>";
                                continue;
                            }

                            // CLOUDFLARE TURNSTILE
                            if ($type === 'captcha_turnstile') {
                                $siteKey = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'turnstile_site_key'")->fetch()['setting_value'] ?? '';
                                echo "<div class='{$widthClass}'>";
                                if (empty($siteKey)) {
                                    echo "<div class='p-3 bg-red-100 text-red-700 text-xs font-bold rounded'>Błąd: Brak klucza Turnstile Site Key.</div>";
                                } else {
                                    if (!self::$turnstileLoaded) {
                                        echo "<script src='https://challenges.cloudflare.com/turnstile/v0/api.js' async defer></script>";
                                        self::$turnstileLoaded = true;
                                    }
                                    echo "<div class='cf-turnstile' data-sitekey='".htmlspecialchars($siteKey)."'></div>";
                                }
                                echo "</div>";
                                continue;
                            }

                            // Standardowe Pola
                            $fieldId = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
                            $val = $prefill[$fieldId] ?? '';
                            $req = !empty($field['required']);
                            $reqAttr = $req ? 'required' : '';
                            $reqStar = $req ? '<span class="text-red-500 ml-1" title="Pole wymagane">*</span>' : '';

                            // POLE PLIKÓW - ASYNCHRONICZNE
                            if ($type === 'file') {
                                $allowMultiple = !empty($formSettings['allowMultipleFiles']) ? 'multiple' : '';
                                echo "<div class='{$widthClass} async-file-upload' data-field-id='{$fieldId}'>";
                                echo "<label class='block text-sm font-bold text-gray-800 mb-2' for='{$fieldId}'>".htmlspecialchars($field['label']).$reqStar."</label>";
                                
                                $hasExisting = isset($existingFiles[$fieldId]) && !empty($existingFiles[$fieldId]);
                                $currentReqAttr = ($hasExisting) ? '' : $reqAttr;
                                
                                echo "<div class='relative border-2 border-dashed border-blue-300 bg-blue-50/50 hover:bg-blue-100/50 rounded-xl py-4 px-6 text-center transition-all group overflow-hidden focus-within:ring-4 focus-within:ring-blue-100 focus-within:border-blue-500 flex flex-col items-center justify-center'>";
                                echo "<input type='file' id='{$fieldId}' class='absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 file-input' {$currentReqAttr} {$allowMultiple}>";
                                if ($req) echo "<input type='hidden' class='original-required-flag' value='1'>";

                                echo "<div class='text-blue-500 mb-1 transition-transform group-hover:scale-110 flex justify-center'>
                                        <svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'></path></svg>
                                      </div>";
                                echo "<p class='text-sm font-bold text-blue-700 mb-0'>Przeciągnij lub kliknij</p>";
                                echo "</div>";

                                // Pasek postępu
                                echo "<div class='progress-container hidden mt-3'>";
                                echo "<div class='flex justify-between text-xs font-bold text-blue-800 mb-1'><span class='progress-text-name truncate max-w-[80%]'>Wgrywanie...</span><span class='progress-text-pct'>0%</span></div>";
                                echo "<div class='w-full bg-blue-100 rounded-full h-2.5 overflow-hidden shadow-inner'><div class='progress-bar bg-blue-600 h-2.5 rounded-full transition-all duration-300' style='width: 0%'></div></div>";
                                echo "</div>";
                                
                                // Lista wgranych plików
                                echo "<div class='uploaded-files-list mt-3 flex flex-col gap-2'>";
                                if ($hasExisting) {
                                    $eFiles = isset($existingFiles[$fieldId]['original_name']) ? [$existingFiles[$fieldId]] : $existingFiles[$fieldId];
                                    foreach ($eFiles as $eFile) {
                                        if (empty($eFile['original_name']) || empty($eFile['storage_name'])) continue;
                                        
                                        $jsonVal = htmlspecialchars(json_encode($eFile), ENT_QUOTES, 'UTF-8');
                                        $origName = urlencode($eFile['original_name']);
                                        $dlUrl = "/admin/forms/download?file=" . urlencode($eFile['storage_name']) . "&orig=" . $origName;

                                        echo "<div class='flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm group hover:border-blue-300 transition-colors existing-file-item animate-fade-in'>";
                                        echo "  <div class='flex items-center gap-3 overflow-hidden'>";
                                        echo "    <div class='bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0'><svg class='w-4 h-4' fill='currentColor' viewBox='0 0 20 20'><path fill-rule='evenodd' d='M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z' clip-rule='evenodd'></path></svg></div>";
                                        echo "    <a href='{$dlUrl}' target='_blank' class='truncate font-medium hover:text-blue-600 transition-colors'>".htmlspecialchars($eFile['original_name'])."</a>";
                                        echo "  </div>";
                                        echo "  <button type='button' class='text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors remove-existing-file' title='Usuń plik'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button>";
                                        echo "  <input type='hidden' name='async_files[{$fieldId}][]' value='{$jsonVal}'>";
                                        echo "</div>";
                                    }
                                }
                                echo "</div>";
                                echo "</div>";
                                continue;
                            }

                            echo "<div class='$widthClass'><label class='block text-sm font-bold text-gray-800 mb-2' for='{$fieldId}'>".htmlspecialchars($field['label']).$reqStar."</label>";
                            
                            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                                $optionsRaw = explode("\n", trim($field['options'] ?? ''));
                                $options = [];
                                foreach ($optionsRaw as $opt) {
                                    if (!$opt) continue;
                                    $parts = explode('|limit:', $opt);
                                    $options[] = ['label' => trim($parts[0]), 'limit' => isset($parts[1]) ? (int)trim($parts[1]) : 0];
                                }

                                if ($type === 'select') {
                                    echo "<div class='relative'>";
                                    echo "<select id='{$fieldId}' name='data[{$fieldId}]' class='appearance-none {$inputClasses} pr-10' {$reqAttr}>";
                                    echo "<option value=''>-- Wybierz opcję --</option>";
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
                                        echo "<option value='".htmlspecialchars($o['label'])."' {$disabled} {$selected}>".htmlspecialchars($o['label']) . $limitText."</option>";
                                    }
                                    echo "</select>";
                                    echo "<div class='pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500'><svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'></path></svg></div>";
                                    echo "</div>";
                                } elseif ($type === 'radio') {
                                    echo "<div class='flex flex-col gap-3 mt-1' id='{$fieldId}'>";
                                    foreach ($options as $idx => $o) {
                                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                                        $disabled = '';
                                        $limitText = '';
                                        if ($o['limit'] > 0) {
                                            $left = $o['limit'] - $currentCount;
                                            if ($left <= 0 && $val !== $o['label']) {
                                                $disabled = 'disabled';
                                                $limitText = " <span class='text-xs text-red-500 font-bold block'>(Brak miejsc)</span>";
                                            } else {
                                                $limitText = " <span class='text-xs text-gray-500 block'>(Zostało: {$left})</span>";
                                            }
                                        }
                                        $checked = ($val === $o['label']) ? 'checked' : '';
                                        $optId = $fieldId . '_' . $idx;
                                        
                                        echo "<label class='relative flex items-start p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all focus-within:ring-2 focus-within:ring-blue-500 bg-white shadow-sm ".(($disabled)?'opacity-50 cursor-not-allowed':'')."' for='{$optId}'>";
                                        echo "<input type='radio' id='{$optId}' name='data[{$fieldId}]' value='".htmlspecialchars($o['label'])."' class='peer w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 mt-0.5 transition-all' {$disabled} {$checked} {$reqAttr}>";
                                        echo "<div class='ml-3 flex flex-col'>";
                                        echo "<span class='text-sm font-medium text-gray-800 peer-checked:text-blue-700 transition-colors'>".htmlspecialchars($o['label'])."</span>";
                                        echo $limitText;
                                        echo "</div>";
                                        echo "</label>";
                                    }
                                    echo "</div>";
                                } elseif ($type === 'checkbox') {
                                    echo "<div class='flex flex-col gap-3 mt-1' id='{$fieldId}'>";
                                    $valArr = is_array($val) ? $val : (is_string($val) && strpos($val, ',') !== false ? explode(', ', $val) : [$val]);
                                    foreach ($options as $idx => $o) {
                                        $currentCount = $optionCounts[$fieldId][$o['label']] ?? 0;
                                        $disabled = '';
                                        $limitText = '';
                                        if ($o['limit'] > 0) {
                                            $left = $o['limit'] - $currentCount;
                                            if ($left <= 0 && !in_array($o['label'], $valArr)) {
                                                $disabled = 'disabled';
                                                $limitText = " <span class='text-xs text-red-500 font-bold block'>(Brak miejsc)</span>";
                                            } else {
                                                $limitText = " <span class='text-xs text-gray-500 block'>(Zostało: {$left})</span>";
                                            }
                                        }
                                        $checked = in_array($o['label'], $valArr) ? 'checked' : '';
                                        $optId = $fieldId . '_' . $idx;
                                        
                                        echo "<label class='relative flex items-start p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all focus-within:ring-2 focus-within:ring-blue-500 bg-white shadow-sm ".(($disabled)?'opacity-50 cursor-not-allowed':'')."' for='{$optId}'>";
                                        echo "<input type='checkbox' id='{$optId}' name='data[{$fieldId}][]' value='".htmlspecialchars($o['label'])."' class='peer w-5 h-5 rounded text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 mt-0.5 transition-all' {$disabled} {$checked}>";
                                        echo "<div class='ml-3 flex flex-col'>";
                                        echo "<span class='text-sm font-medium text-gray-800 peer-checked:text-blue-700 transition-colors'>".htmlspecialchars($o['label'])."</span>";
                                        echo $limitText;
                                        echo "</div>";
                                        echo "</label>";
                                    }
                                    echo "</div>";
                                }
                            } elseif ($type === 'textarea') {
                                echo "<textarea id='{$fieldId}' name='data[{$fieldId}]' class='{$inputClasses}' rows='4' {$reqAttr} placeholder='Wpisz tekst tutaj...'>".htmlspecialchars(is_array($val)?'':$val)."</textarea>";
                            } elseif ($type === 'date') {
                                echo "<input type='date' id='{$fieldId}' name='data[{$fieldId}]' value='".htmlspecialchars(is_array($val)?'':$val)."' class='{$inputClasses}' {$reqAttr}>";
                            } else {
                                echo "<input type='{$type}' id='{$fieldId}' name='data[{$fieldId}]' value='".htmlspecialchars(is_array($val)?'':$val)."' class='{$inputClasses}' {$reqAttr} placeholder='Wpisz wartość...'>";
                            }
                            echo "</div>";
                        }

                        $btnText = $isEditing ? 'Zaktualizuj formularz' : 'Wyślij formularz';
                        echo '<div class="md:col-span-3 mt-6 pt-6 border-t border-gray-200 relative">';
                        
                        if (!$isEditing) {
                            echo "<div id='autosave-status-{$form['id']}' class='text-sm font-bold text-gray-500 mb-3 hidden transition-all flex items-center gap-2'></div>";
                        }

                        echo '<button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all focus:outline-none focus:ring-4 focus:ring-blue-300" type="submit">'.$btnText.'</button>';
                        if ($isEditing) echo '<div class="text-center mt-4"><a href="?" class="text-sm font-medium text-gray-500 hover:text-gray-800 transition-colors">Anuluj edycję</a></div>';
                        echo '</div></form>';

                        echo "<script>
                        (function() {
                            const formContainer = document.getElementById('form-container-{$form['id']}');
                            if (!formContainer) return;
                            const form = formContainer.querySelector('form');
                            if (!form) return;
                            const submitBtn = form.querySelector('button[type=\"submit\"]');
                            let activeUploads = 0;

                            let autosaveTimeout;
                            const statusEl = document.getElementById('autosave-status-{$form['id']}');

                            function triggerAutosave() {
                                if (!statusEl) return;
                                statusEl.innerHTML = '⏳ Wersja robocza: Zapisywanie...';
                                statusEl.classList.remove('hidden');
                                
                                clearTimeout(autosaveTimeout);
                                autosaveTimeout = setTimeout(() => {
                                    const formData = new FormData(form);
                                    fetch('/form-autosave', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            const d = new Date();
                                            statusEl.innerHTML = '✅ Wersja robocza: Zapisano (' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0') + ')';
                                            
                                            if (data.submission_id) {
                                                let subInput = form.querySelector('input[name=\"submission_id\"]');
                                                if (!subInput) {
                                                    subInput = document.createElement('input');
                                                    subInput.type = 'hidden';
                                                    subInput.name = 'submission_id';
                                                    form.appendChild(subInput);
                                                }
                                                subInput.value = data.submission_id;
                                            }
                                        }
                                    }).catch(() => {
                                        statusEl.innerHTML = '❌ Błąd zapisu roboczego';
                                    });
                                }, 1500);
                            }

                            if (statusEl) {
                                form.addEventListener('input', function(e) {
                                    if (e.target.name === 'captcha_answer' || e.target.type === 'password' || e.target.type === 'file') return;
                                    triggerAutosave();
                                });
                            }

                            form.querySelectorAll('.async-file-upload').forEach(container => {
                                const fileInput = container.querySelector('.file-input');
                                const progressContainer = container.querySelector('.progress-container');
                                const progressBar = container.querySelector('.progress-bar');
                                const progressTextName = container.querySelector('.progress-text-name');
                                const progressTextPct = container.querySelector('.progress-text-pct');
                                const filesList = container.querySelector('.uploaded-files-list');
                                const fieldId = container.dataset.fieldId;
                                const isMultiple = fileInput.hasAttribute('multiple');

                                if (fileInput.hasAttribute('required')) {
                                    fileInput.setAttribute('data-required', 'true');
                                }

                                filesList.querySelectorAll('.remove-existing-file').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        this.closest('.existing-file-item').remove();
                                        if (filesList.children.length === 0 && container.querySelector('.original-required-flag')) {
                                            fileInput.setAttribute('required', 'required');
                                        }
                                        if (statusEl) triggerAutosave();
                                    });
                                });

                                fileInput.addEventListener('change', function() {
                                    const files = this.files;
                                    if (files.length === 0) return;

                                    if (!isMultiple) {
                                        filesList.innerHTML = '';
                                        form.querySelectorAll('input[name=\"async_files[' + fieldId + '][]\"]').forEach(el => el.remove());
                                    }

                                    Array.from(files).forEach(file => {
                                        uploadFile(file);
                                    });
                                    this.value = '';
                                });

                                function uploadFile(file) {
                                    activeUploads++;
                                    submitBtn.disabled = true;
                                    submitBtn.innerHTML = '⏳ Przesyłanie plików...';
                                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

                                    progressContainer.classList.remove('hidden');
                                    progressBar.style.width = '0%';
                                    progressTextName.innerText = file.name;
                                    progressTextPct.innerText = '0%';

                                    const formData = new FormData();
                                    formData.append('file', file);

                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', '/form-upload', true);

                                    xhr.upload.onprogress = function(e) {
                                        if (e.lengthComputable) {
                                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                                            progressBar.style.width = percentComplete + '%';
                                            progressTextPct.innerText = percentComplete + '%';
                                        }
                                    };

                                    xhr.onload = function() {
                                        activeUploads--;
                                        if (xhr.status === 200) {
                                            try {
                                                const res = JSON.parse(xhr.responseText);
                                                if (res.status === 'success') {
                                                    addUploadedFileUi(res.file, file.name);
                                                    if (statusEl) triggerAutosave();
                                                } else {
                                                    alert('Błąd przesyłania pliku: ' + file.name);
                                                }
                                            } catch(e) {
                                                alert('Błąd odpowiedzi serwera dla pliku: ' + file.name);
                                            }
                                        } else {
                                            alert('Błąd serwera podczas przesyłania pliku.');
                                        }
                                        checkUploadsFinished();
                                    };

                                    xhr.onerror = function() {
                                        activeUploads--;
                                        alert('Błąd sieci podczas przesyłania pliku.');
                                        checkUploadsFinished();
                                    };

                                    xhr.send(formData);
                                }

                                function checkUploadsFinished() {
                                    if (activeUploads === 0) {
                                        progressContainer.classList.add('hidden');
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = '{$btnText}';
                                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                    }
                                }

                                function addUploadedFileUi(fileData, displayName) {
                                    fileInput.removeAttribute('required');

                                    const dlUrl = '/admin/forms/download?file=' + encodeURIComponent(fileData.storage_name) + '&orig=' + encodeURIComponent(displayName);

                                    const item = document.createElement('div');
                                    item.className = 'flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm group hover:border-blue-300 transition-colors existing-file-item animate-fade-in';
                                    
                                    const safeJson = JSON.stringify(fileData).replace(/'/g, '&#39;');

                                    item.innerHTML = '<div class=\"flex items-center gap-3 overflow-hidden\">' + 
                                        '<div class=\"bg-blue-100 text-blue-600 p-2 rounded-lg shrink-0\"><svg class=\"w-4 h-4\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z\" clip-rule=\"evenodd\"></path></svg></div>' + 
                                        '<a href=\"' + dlUrl + '\" target=\"_blank\" class=\"truncate font-medium hover:text-blue-600 transition-colors\">' + displayName + '</a>' +
                                        '</div>' +
                                        '<button type=\"button\" class=\"text-gray-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-colors remove-existing-file\" title=\"Usuń plik\">' + 
                                        '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path></svg>' + 
                                        '</button>' +
                                        '<input type=\"hidden\" name=\"async_files[' + fieldId + '][]\" value=\'' + safeJson + '\'>';
                                    
                                    item.querySelector('.remove-existing-file').addEventListener('click', function() {
                                        item.remove();
                                        if (filesList.children.length === 0 && container.querySelector('.original-required-flag')) {
                                            fileInput.setAttribute('required', 'required');
                                        }
                                        if (statusEl) triggerAutosave();
                                    });

                                    filesList.appendChild(item);
                                }
                            });
                        })();
                        </script>";
                    }
                    echo '</div>';
                }
            }
            // 11. LINKED IMAGE
            elseif ($bType === 'linked_image') {
                $img = $block['content']['url'] ?? '';
                $link = $block['content']['link'] ?? '#';
                if ($img) {
                    echo '<div class="mb-8"><a href="'.htmlspecialchars($link).'"><img src="'.htmlspecialchars($img).'" class="w-full rounded-xl shadow-md hover:opacity-90 transition transform hover:scale-[1.01]"></a></div>';
                }
            }
            // 12. CAROUSEL (Przewijany efekt Slide)
            elseif ($bType === 'carousel') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $tabs = $data['tabs'] ?? [];
                $arrows = $data['arrows'] ?? false;
                $cid = 'c_' . (!empty($set['id']) ? htmlspecialchars($set['id']) : substr(md5(json_encode($tabs)), 0, 8));

                if (!empty($tabs)) {
                    echo '<div class="mb-10 carousel-wrapper mt-10" id="'.$cid.'">';
                    
                    // Przyciski do przełączania (Strzałki przeniesione pod klasę i event listenery)
                    echo '<div class="flex flex-wrap gap-3 justify-center mb-8 items-center">';
                    if ($arrows) {
                        echo '<button class="c-arrow-'.$cid.' bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow flex items-center justify-center" data-dir="-1"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>';
                    }

                    foreach ($tabs as $idx => $tab) {
                        $activeClass = $idx === 0 ? 'bg-blue-900 scale-105' : 'bg-blue-700 hover:bg-blue-800';
                        $tSet = $tab['settings'] ?? [];
                        $tId = !empty($tSet['id']) ? ' id="'.htmlspecialchars($tSet['id']).'"' : '';
                        $tCls = !empty($tSet['css']) ? ' ' . htmlspecialchars($tSet['css']) : '';
                        $tStyle = !empty($tSet['style']) ? ' style="'.htmlspecialchars($tSet['style']).'"' : '';

                        echo '<button'.$tId.' data-index="'.$idx.'" class="c-btn-'.$cid.' flex flex-col items-center justify-center p-4 rounded-xl w-32 md:w-40 text-white shadow-lg transition-all duration-300 transform '.$activeClass.$tCls.'"'.$tStyle.'>';
                        if (strpos($tab['icon'], 'http') === 0 || strpos($tab['icon'], '/') === 0) {
                            echo '<img src="'.htmlspecialchars($tab['icon']).'" class="h-8 w-8 mb-2 invert">';
                        } else {
                            echo '<span class="text-3xl mb-2 block">'.htmlspecialchars($tab['icon']).'</span>';
                        }
                        echo '<span class="text-xs md:text-sm font-bold text-center leading-tight uppercase">'.htmlspecialchars($tab['label']).'</span>';
                        echo '</button>';
                    }

                    if ($arrows) {
                        echo '<button class="c-arrow-'.$cid.' bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow flex items-center justify-center" data-dir="1"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>';
                    }
                    echo '</div>';

                    // GŁÓWNY KONTENER EKRANU
                    echo '<div class="bg-white rounded-xl shadow border-t-4 border-blue-900 relative overflow-hidden">';
                    
                    // TAŚMA ZE SLAJDAMI
                    echo '<div id="track-'.$cid.'" class="flex" style="transform: translateX(0%);">';
                    foreach ($tabs as $idx => $tab) {
                        echo '<div class="p-6 md:p-10 shrink-0" style="flex: 0 0 100%; max-width: 100%; width: 100%;">';
                        if (!empty($tab['children'])) {
                            self::render($tab['children'], $db);
                        } else if (!empty($tab['content'])) {
                            echo '<div class="prose max-w-none">' . $tab['content'] . '</div>';
                        }
                        echo '</div>';
                    }
                    echo '</div>'; // Zakończenie track
                    echo '</div>'; // Zakończenie okna bg-white

                    // ZAMKNIĘTY SKRYPT JS (Zero zanieczyszczania zasięgu globalnego "window.")
                    echo "<script>
                    (function() {
                        const cid = '{$cid}';
                        const track = document.getElementById('track-' + cid);
                        const buttons = document.querySelectorAll('.c-btn-' + cid);
                        const arrows = document.querySelectorAll('.c-arrow-' + cid);
                        const total = " . count($tabs) . ";
                        
                        function goToSlide(index) {
                            if (track) {
                                track.style.transform = 'translateX(-' + (index * 100) + '%)';
                            }
                            buttons.forEach(btn => {
                                if (parseInt(btn.dataset.index) === index) {
                                    btn.classList.remove('bg-blue-700', 'hover:bg-blue-800');
                                    btn.classList.add('bg-blue-900', 'scale-105');
                                } else {
                                    btn.classList.add('bg-blue-700', 'hover:bg-blue-800');
                                    btn.classList.remove('bg-blue-900', 'scale-105');
                                }
                            });
                            localStorage.setItem('active_tab_' + cid, index);
                        }

                        buttons.forEach(btn => {
                            btn.addEventListener('click', function() {
                                goToSlide(parseInt(this.dataset.index));
                            });
                        });

                        arrows.forEach(arrow => {
                            arrow.addEventListener('click', function() {
                                let currentIndex = 0;
                                buttons.forEach(btn => {
                                    if (btn.classList.contains('bg-blue-900')) {
                                        currentIndex = parseInt(btn.dataset.index);
                                    }
                                });
                                
                                let dir = parseInt(this.dataset.dir);
                                let newIndex = currentIndex + dir;
                                if (newIndex < 0) newIndex = total - 1;
                                if (newIndex >= total) newIndex = 0;
                                
                                goToSlide(newIndex);
                            });
                        });

                        const savedIndex = localStorage.getItem('active_tab_' + cid);
                        if (savedIndex !== null) {
                            goToSlide(parseInt(savedIndex));
                        }
                        
                        if (track) {
                            requestAnimationFrame(() => {
                                setTimeout(() => {
                                    track.classList.add('transition-transform', 'duration-500', 'ease-in-out');
                                }, 50);
                            });
                        }
                    })();
                    </script>";

                    echo '</div>'; // Zakończenie carousel-wrapper
                }
            }
            // 13. GALLERY
            elseif ($bType === 'gallery') {
                $gal = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $block['content']])->fetch();
                if ($gal) {
                    $imgs = json_decode($gal['images_json'], true);
                    $settings = json_decode($gal['settings'] ?? '{}', true);

                    if (empty($imgs)) continue;

                    echo '<div class="mb-10">';
                    echo '<h3 class="text-2xl font-bold mb-6 text-gray-800">'.htmlspecialchars($gal['title']).'</h3>';

                    if (!self::$galleryAssetsLoaded) {
                        echo '';
                        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>';
                        echo '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
                        echo '';
                        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"/>';
                        echo '<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>';
                        echo '<script>document.addEventListener("DOMContentLoaded", function() { if(typeof GLightbox !== "undefined") { GLightbox({ selector: ".glightbox" }); } });</script>';
                        self::$galleryAssetsLoaded = true;
                    }

                    if ($gal['type'] === 'grid') {
                        $maxVisible = 5;
                        $total = count($imgs);
                        echo '<div class="grid grid-cols-6 gap-2 md:gap-3 rounded-xl overflow-hidden shadow-sm">';
                        
                        foreach ($imgs as $index => $img) {
                            if ($index >= $maxVisible) {
                                echo "<a href='$img' class='glightbox hidden' data-gallery='gallery-{$gal['id']}'></a>";
                                continue;
                            }
                            
                            $classes = 'block relative overflow-hidden group bg-gray-100';
                            if ($total === 1) $classes .= ' col-span-6 aspect-video';
                            elseif ($total === 2) $classes .= ' col-span-3 aspect-[4/3] md:aspect-video';
                            elseif ($total === 3) $classes .= ($index === 0) ? ' col-span-6 aspect-video md:aspect-[21/9]' : ' col-span-3 aspect-square md:aspect-[4/3]';
                            elseif ($total === 4) $classes .= ($index === 0) ? ' col-span-6 aspect-video md:aspect-[21/9]' : ' col-span-2 aspect-square';
                            else $classes .= ($index < 2) ? ' col-span-3 aspect-square md:aspect-[4/3]' : ' col-span-2 aspect-square';

                            echo "<a href='$img' class='glightbox $classes' data-gallery='gallery-{$gal['id']}'>";
                            echo "<img src='$img' class='w-full h-full object-cover transition-transform duration-500 group-hover:scale-110'>";
                            
                            if ($index === 4 && $total > 5) {
                                $more = $total - 5;
                                echo "<div class='absolute inset-0 bg-black/60 flex items-center justify-center text-white font-bold text-3xl md:text-5xl backdrop-blur-sm transition-colors group-hover:bg-black/50'>+{$more}</div>";
                            }
                            echo "</a>";
                        }
                        echo '</div>';
                    } else {
                        $swiperId = 'gallery_swiper_' . $gal['id'] . '_' . md5(uniqid());
                        $isLoop = !empty($settings['loop']) ? 'true' : 'false';
                        $showNav = !empty($settings['nav']);
                        $showPag = !empty($settings['pag']);
                        $autoplay = (!empty($settings['autoplay']) && $settings['autoplay'] > 0) ? "{ delay: {$settings['autoplay']}, disableOnInteraction: false }" : 'false';
                        $effect = 'slide';
                        $extraConfig = '';
                        $containerClasses = 'rounded-xl shadow-sm relative';
                        $slideClasses = 'relative bg-gray-100 group rounded-xl overflow-hidden';

                        if ($gal['type'] === 'swiper_coverflow') {
                            $effect = 'coverflow';
                            $extraConfig = "coverflowEffect: { rotate: 50, stretch: 0, depth: 100, modifier: 1, slideShadows: true },";
                            $containerClasses .= ' !p-4 !-m-4 !overflow-visible';
                            $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
                        } elseif ($gal['type'] === 'swiper_fade') {
                            $effect = 'fade';
                            $extraConfig = "fadeEffect: { crossFade: true },";
                            $containerClasses .= ' overflow-hidden';
                            $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
                        } elseif ($gal['type'] === 'swiper_cards') {
                            $effect = 'cards';
                            $extraConfig = "cardsEffect: { slideShadows: true }, grabCursor: true,";
                            $containerClasses .= ' !overflow-visible max-w-sm mx-auto mt-8 mb-12';
                            $slideClasses .= ' aspect-[3/4] shadow-lg';
                        } else {
                            $containerClasses .= ' overflow-hidden';
                            $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
                        }

                        echo '<div class="swiper '.$swiperId.' '.$containerClasses.'">';
                        echo '<div class="swiper-wrapper">';
                        foreach ($imgs as $img) {
                            echo '<div class="swiper-slide w-full h-full">';
                            echo "<a href='$img' class='glightbox block {$slideClasses}' data-gallery='gallery-{$gal['id']}'>";
                            echo "<img src='$img' draggable='false' class='w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 select-none'>";
                            echo "</a>";
                            echo '</div>';
                        }
                        echo '</div>';

                        $navNext = 'next_' . $swiperId;
                        $navPrev = 'prev_' . $swiperId;
                        $pagEl = 'pag_' . $swiperId;

                        if ($showPag) echo '<div class="swiper-pagination '.$pagEl.' !bottom-0"></div>';
                        if ($showNav) {
                            echo '<button type="button" aria-label="Poprzedni slajd" class="'.$navPrev.' absolute top-1/2 left-4 z-50 -translate-y-1/2 cursor-pointer text-white w-10 h-10 bg-black/30 rounded-full shadow-lg border border-white/20 flex items-center justify-center hover:bg-black/60 transition backdrop-blur-sm focus:outline-none"><span class="text-xl font-bold leading-none">&lt;</span></button>';
                            echo '<button type="button" aria-label="Następny slajd" class="'.$navNext.' absolute top-1/2 right-4 z-50 -translate-y-1/2 cursor-pointer text-white w-10 h-10 bg-black/30 rounded-full shadow-lg border border-white/20 flex items-center justify-center hover:bg-black/60 transition backdrop-blur-sm focus:outline-none"><span class="text-xl font-bold leading-none">&gt;</span></button>';
                        }
                        echo '</div>';

                        $jsBreakpoints = "";
                        $jsSlidesPerView = "1";
                        $imgCount = count($imgs);

                        if ($effect === 'slide') {
                            $jsBreakpoints = "breakpoints: { 640: { slidesPerView: 1, spaceBetween: 16 }, 768: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 24 } }";
                        } elseif ($effect === 'coverflow') {
                            $jsSlidesPerView = "'auto'";
                            $jsBreakpoints = "breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }";
                        } else {
                            $jsSlidesPerView = "1";
                            $jsBreakpoints = "";
                        }

                        if ($imgCount <= 1) $isLoop = 'false';
                        if ($effect === 'cards' && $imgCount < 4) $isLoop = 'false';
                        if ($effect === 'fade' && $imgCount < 2) $isLoop = 'false';

                        $jsSpaceBetween = in_array($effect, ['fade', 'cards']) ? '0' : '12';

                        echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            new Swiper('.$swiperId', {
                                effect: '{$effect}',
                                {$extraConfig}
                                loop: {$isLoop},
                                autoplay: {$autoplay},
                                observer: true,
                                observeParents: true,
                                ".($showPag ? "pagination: { el: '.{$pagEl}', clickable: true, dynamicBullets: true }," : "")."
                                ".($showNav ? "navigation: { nextEl: '.{$navNext}', prevEl: '.{$navPrev}' }," : "")."
                                slidesPerView: {$jsSlidesPerView},
                                spaceBetween: {$jsSpaceBetween},
                                {$jsBreakpoints}
                                on: {
                                    init: function () {
                                        const swiperInstance = this;
                                        requestAnimationFrame(() => {
                                            swiperInstance.update();
                                        });
                                        setTimeout(() => {
                                            swiperInstance.update();
                                        }, 150);
                                    }
                                }
                            });
                        });
                        </script>";
                    }
                    echo '</div>';
                }
            }
            // 14. IMAGE CARDS
            elseif ($bType === 'image_cards') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $cards = $data['cards'] ?? [];

                if (!empty($cards)) {
                    echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">';
                    foreach ($cards as $card) {
                        $link = !empty($card['link']) ? htmlspecialchars($card['link']) : '#';
                        $cSet = $card['settings'] ?? [];
                        $cId = !empty($cSet['id']) ? ' id="'.htmlspecialchars($cSet['id']).'"' : '';
                        $cCls = !empty($cSet['css']) ? ' ' . htmlspecialchars($cSet['css']) : '';
                        $cStyle = !empty($cSet['style']) ? ' style="'.htmlspecialchars($cSet['style']).'"' : '';

                        echo '<a href="'.$link.'"'.$cId.' class="block bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group flex flex-col h-full'.$cCls.'"'.$cStyle.'>';
                        echo '<div class="h-48 w-full overflow-hidden bg-gray-100">';
                        if (!empty($card['img'])) {
                            echo '<img src="'.htmlspecialchars($card['img']).'" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">';
                        }
                        echo '</div>';
                        echo '<div class="p-6 flex-1 flex flex-col justify-center">';
                        echo '<h3 class="text-orange-500 font-bold text-xl mb-2 leading-tight group-hover:text-orange-600 transition">'.htmlspecialchars($card['title']).'</h3>';
                        if (!empty($card['subtitle'])) {
                            echo '<p class="text-gray-600 text-sm">'.htmlspecialchars($card['subtitle']).'</p>';
                        }
                        echo '</div>';
                        echo '</a>';
                    }
                    echo '</div>';
                }
            }
            // 15. BANNER
            elseif ($bType === 'banner') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $bg = $data['bg'] ?? '';
                $title = $data['title'] ?? '';
                $subtitle = $data['subtitle'] ?? '';

                echo '<div class="relative w-screen h-64 md:h-[500px] bg-cover bg-center mb-10" style="margin-left: calc(-50vw + 50%); background-image: url(\''.htmlspecialchars($bg).'\');">';
                if (!empty($title)) {
                    echo '  <div class="absolute bottom-8 left-0 w-11/12 md:w-2/3 bg-white/90 p-6 md:pl-16 backdrop-blur-sm shadow-xl rounded-r-2xl">';
                    echo '    <h1 class="text-3xl md:text-5xl font-extrabold text-orange-500 tracking-wide drop-shadow-sm">'.htmlspecialchars($title).'</h1>';
                    echo '  </div>';
                }
                echo '</div>';

                if (!empty($subtitle)) {
                    echo '<div class="text-gray-800 font-medium mb-10 text-lg md:text-xl max-w-4xl border-l-4 border-orange-500 pl-5 leading-relaxed">'.nl2br(htmlspecialchars($subtitle)).'</div>';
                }
            }
            // 16. RAW HTML
            elseif ($bType === 'raw_html') {
                echo $block['content'];
            }
            // 17. MAPA LEAFLET
            elseif ($bType === 'map') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $lat = $data['lat'] ?? '52.2297';
                $lng = $data['lng'] ?? '21.0122';
                $zoom = $data['zoom'] ?? '13';
                $tooltip = htmlspecialchars($data['tooltip'] ?? '');
                $mapId = 'map_' . uniqid();

                echo '<div class="mb-8 w-full h-[400px] rounded-xl shadow-lg border border-gray-200 z-10" id="'.$mapId.'"></div>';
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var map = L.map('{$mapId}').setView([{$lat}, {$lng}], {$zoom});
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([{$lat}, {$lng}]).addTo(map).bindPopup('{$tooltip}').openPopup();
                    });
                </script>";
            }
            // 18. ODLICZANIE
            elseif ($bType === 'countdown') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $targetDate = $data['date'] ?? '';
                $title = htmlspecialchars($data['title'] ?? '');
                $cdId = 'cd_' . uniqid();

                if ($targetDate) {
                    echo '<div class="mb-8 bg-gray-900 text-white p-8 rounded-2xl shadow-xl text-center">';
                    echo '<h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-300">'.$title.'</h3>';
                    echo '<div id="'.$cdId.'" class="flex justify-center gap-4 md:gap-8 text-3xl md:text-5xl font-extrabold text-orange-500">';
                    echo '<div><span class="days block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Dni</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="hours block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Godzin</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="minutes block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Minut</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="seconds block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Sekund</span></div>';
                    echo '</div></div>';

                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var target = new Date('{$targetDate}').getTime();
                            var el = document.getElementById('{$cdId}');
                            var interval = setInterval(function() {
                                var now = new Date().getTime();
                                var distance = target - now;
                                if (distance < 0) { clearInterval(interval); return; }
                                el.querySelector('.days').innerText = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                                el.querySelector('.hours').innerText = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
                                el.querySelector('.minutes').innerText = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
                                el.querySelector('.seconds').innerText = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');
                            }, 1000);
                        });
                    </script>";
                }
            }
            // 19. TABELA
            elseif ($bType === 'table') {
                $raw = $block['content'] ?? '';
                if ($raw) {
                    $rows = explode("\n", trim($raw));
                    echo '<div class="overflow-x-auto mb-8 bg-white rounded-xl shadow-sm border border-gray-200">';
                    echo '<table class="min-w-full text-left text-sm">';
                    foreach ($rows as $index => $row) {
                        $cells = explode("\t", $row);
                        if ($index === 0) {
                            echo '<thead class="bg-gray-50 border-b"><tr>';
                            foreach ($cells as $cell) {
                                echo '<th class="px-6 py-4 font-bold text-gray-700">'.htmlspecialchars($cell).'</th>';
                            }
                            echo '</tr></thead><tbody class="divide-y divide-gray-100">';
                        } else {
                            echo '<tr class="hover:bg-gray-50 transition">';
                            foreach ($cells as $cell) {
                                echo '<td class="px-6 py-4 text-gray-600">'.htmlspecialchars($cell).'</td>';
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</tbody></table></div>';
                }
            }
            // 20. PRZELOT
            elseif ($bType === 'flight') {
                echo '<div class="flight-container mb-8">';
                if (is_array($block['content']) && isset($block['content']['html'])) {
                    echo $block['content']['html'];
                } else {
                    echo $block['content'] ?? '';
                }
                echo '</div>';
            }
            // 21. SYSTEM: LOGOWANIE
            elseif ($bType === 'system_login') {
                $flash = \CMS\Core\Session::getFlash();
                $oldLogin = \CMS\Core\Session::get('old_login');
                \CMS\Core\Session::remove('old_login');
                
                $setRows = $db->query("SELECT setting_key, setting_value FROM pa_settings")->fetchAll();
                $s = []; foreach($setRows as $r) $s[$r['setting_key']] = $r['setting_value'];

                echo '<div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-100">';
                if ($flash) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
                }
                echo '<form action="/login" method="POST">';
                echo '  <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email lub Login</label><input class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" name="login" type="text" required value="'.htmlspecialchars($oldLogin ?? '').'"></div>';
                echo '  <div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Hasło</label><input class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" name="password" type="password" required></div>';
                echo '  <button class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded w-full transition shadow" type="submit">Zaloguj się</button>';
                echo '</form>';
                
                $regMode = $s['reg_mode'] ?? 'disabled';
                $isSecretUnlocked = \CMS\Core\Session::get('secret_reg_unlocked') === true;

                if ($regMode === 'open' || ($regMode === 'secret' && $isSecretUnlocked)) {
                    echo '<p class="text-center mt-5 text-sm text-gray-600 border-t pt-4">Nie masz konta? <br><a href="/register" class="text-blue-600 font-bold hover:underline">Zarejestruj się</a></p>';
                }
                echo '</div>';
            }
            // 22. SYSTEM: REJESTRACJA
            elseif ($bType === 'system_register') {
                $flash = \CMS\Core\Session::getFlash();
                $oldEmail = \CMS\Core\Session::get('old_email');
                \CMS\Core\Session::remove('old_email');

                echo '<div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-100">';
                if ($flash) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
                }
                echo '<form action="/register" method="POST">';
                echo '  <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="email" type="email" required value="'.htmlspecialchars($oldEmail ?? '').'"></div>';
                echo '  <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="password" type="password" required></div>';
                echo '  <div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Potwierdź hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="confirm_password" type="password" required></div>';
                echo '  <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded w-full transition shadow" type="submit">Utwórz konto</button>';
                echo '</form>';
                echo '<p class="text-center mt-4 text-sm text-gray-500">Masz już konto? <a href="/login" class="text-blue-600 font-bold hover:underline">Zaloguj</a></p>';
                echo '</div>';
            }
            // 23. SYSTEM: ZMIANA HASŁA
            elseif ($bType === 'system_change_password') {
                $flash = \CMS\Core\Session::getFlash();
                echo '<div class="max-w-sm w-full mx-auto bg-white p-8 rounded-lg shadow-lg border-t-4 border-yellow-500">';
                echo '<h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Zmiana Hasła</h2>';
                echo '<p class="text-sm text-gray-600 mb-6 text-center">Wymagana jest zmiana hasła w celach bezpieczeństwa.</p>';
                if ($flash) echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
                echo '<form action="/change-password" method="POST">';
                echo '<div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Nowe Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-blue-500" name="pass1" type="password" required></div>';
                echo '<div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Potwierdź Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-blue-500" name="pass2" type="password" required></div>';
                echo '<button class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full shadow transition" type="submit">Zaktualizuj Hasło</button>';
                echo '</form></div>';
            }
            // 24. SYSTEM: LOCKDOWN
            elseif ($bType === 'system_lockdown') {
                echo '<div class="max-w-md w-full mx-auto bg-gray-900 p-8 rounded-xl shadow-2xl border border-gray-700 text-center">';
                echo '<h1 class="text-3xl font-bold mb-4 text-white">Strona Zabezpieczona</h1>';
                echo '<p class="mb-6 text-gray-400">Podaj kod dostępu, aby kontynuować.</p>';
                echo '<form method="POST">';
                echo '<input type="password" name="site_pass" class="w-full p-3 rounded mb-4 text-black focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kod dostępu..." required>';
                echo '<button class="bg-blue-600 hover:bg-blue-700 text-white w-full p-3 rounded font-bold shadow transition">Wejdź na stronę</button>';
                echo '</form></div>';
            }
            // 25. POSTS GRID (Lista wpisów / Kafelki na stronie głównej)
            elseif ($bType === 'posts_grid') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $limit = (int)($data['limit'] ?? 6);
                $categoryId = $data['category'] ?? '';
                
                $sql = "SELECT p.*, c.name as cat_name FROM pa_posts p LEFT JOIN pa_post_categories c ON p.category_id = c.id WHERE p.status = 'published'";
                $params = [];
                if (!empty($categoryId)) {
                    $sql .= " AND p.category_id = :cid";
                    $params['cid'] = $categoryId;
                }
                $sql .= " ORDER BY p.created_at DESC LIMIT " . $limit;
                
                $posts = $db->query($sql, $params)->fetchAll();

                echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">';
                foreach ($posts as $post) {
                    $postUrl = '/post?slug=' . htmlspecialchars($post['slug']);
                    echo '<article class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col h-full border border-gray-100 group">';
                    
                    // Thumbnail
                    if (!empty($post['thumbnail'])) {
                        echo '<a href="'.$postUrl.'" class="block h-48 overflow-hidden relative">';
                        if (!empty($post['cat_name'])) {
                            echo '<span class="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full z-10">'.htmlspecialchars($post['cat_name']).'</span>';
                        }
                        echo '<img src="'.htmlspecialchars($post['thumbnail']).'" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Okładka">';
                        echo '</a>';
                    }

                    echo '<div class="p-6 flex-1 flex flex-col">';
                    echo '<span class="text-xs text-gray-400 mb-2 font-medium">'.date('d.m.Y', strtotime($post['created_at'])).'</span>';
                    echo '<h3 class="text-xl font-bold text-gray-800 mb-3 leading-tight group-hover:text-blue-600 transition-colors"><a href="'.$postUrl.'">'.htmlspecialchars($post['title']).'</a></h3>';
                    echo '<p class="text-gray-600 text-sm mb-6 flex-1 line-clamp-3">'.htmlspecialchars($post['excerpt']).'</p>';
                    
                    // Tagi
                    if (!empty($post['tags'])) {
                        $tags = explode(',', $post['tags']);
                        echo '<div class="flex flex-wrap gap-2 mb-4">';
                        foreach ($tags as $tag) {
                            echo '<span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">#'.htmlspecialchars(trim($tag)).'</span>';
                        }
                        echo '</div>';
                    }

                    echo '<a href="'.$postUrl.'" class="text-blue-600 font-bold text-sm uppercase tracking-wide hover:underline mt-auto flex items-center gap-1">Czytaj dalej <span class="text-lg leading-none">→</span></a>';
                    echo '</div></article>';
                }
                echo '</div>';

                // Przycisk "Załaduj więcej" (Prosty Infinite Scroll / AJAX by wymagał dopisania endpointu API, tutaj bezpieczny link do "/blog")
                if (count($posts) >= $limit) {
                    echo '<div class="text-center mt-4"><a href="/blog" class="inline-block border-2 border-gray-300 text-gray-600 font-bold py-3 px-8 rounded-full hover:border-blue-600 hover:text-blue-600 transition">Zobacz wszystkie wpisy</a></div>';
                }
            }

            // Zamknięcie uniwersalnego wrappera
            echo "</div>";
        }
    }
}