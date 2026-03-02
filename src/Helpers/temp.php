<?php
namespace CMS\Helpers;

class BlockRenderer {
    private static $galleryAssetsLoaded = false;
    private static $turnstileLoaded = false;

    public static function render($blocks, $db) {
        if (empty($blocks)) return;

        foreach ($blocks as $block) {
            $set = $block['settings'] ?? [];
            $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
            $clsAttr = !empty($set['css']) ? ' ' . htmlspecialchars($set['css']) : '';
            $styleAttr = !empty($set['style']) ? ' style="'.htmlspecialchars($set['style']).'"' : '';

            echo "<div{$idAttr} class=\"block-wrapper mb-0{$clsAttr}\"{$styleAttr}>";

            // ... (standardowe bloki pominięte dla zwięzłości) ...

            if ($block['type'] === 'form') {
                $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
                if ($form) {
                    $formSettings = json_decode($form['settings'] ?? '{}', true);
                    $userId = \CMS\Core\Session::get('user_id');

                    echo '<div class="bg-gray-50 border border-gray-200 p-6 md:p-8 rounded-xl mb-8 shadow-sm" id="form-container-'.$form['id'].'">';
                    echo '<h3 class="text-2xl font-bold mb-6 text-gray-800">' . htmlspecialchars($form['title']) . '</h3>';

                    if (!empty($formSettings['reqLogin']) && !$userId) {
                        echo '<div class="bg-yellow-100 p-5 rounded-lg text-yellow-800 border border-yellow-300">Zaloguj się, aby wyświetlić i wypełnić ten formularz. <br><a href="/login" class="font-bold underline text-yellow-900 mt-2 inline-block">Przejdź do logowania</a></div></div>';
                        continue;
                    }

                    $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $form['id'];
                    $isEditing = isset($_GET['edit']) && $_GET['edit'] == $form['id'];
                    $existingSubmission = null;
                    if ($userId) {
                        $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
                    }

                    $flash = \CMS\Core\Session::getFlash();
                    if ($flash && isset($_GET['err_form']) && $_GET['err_form'] == $form['id']) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-5 py-4 rounded-lg mb-6 text-sm font-bold shadow-sm">⚠️ ' . htmlspecialchars($flash['msg']) . '</div>';
                    }

                    $showThankYou = false;
                    if ($userId) {
                        if ($existingSubmission && ($isSubmittedNow || (!empty($formSettings['fillOnce']) && !$isEditing))) $showThankYou = true;
                    } else {
                        if ($isSubmittedNow) $showThankYou = true;
                    }

                    if ($showThankYou) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-800 px-5 py-5 rounded-lg mb-4 shadow-sm"><span class="text-3xl mb-3 block">🎉</span> <strong class="text-lg">Dziękujemy!</strong><br> Twój formularz został poprawnie zapisany na serwerze.</div>';
                        echo '<div class="flex flex-wrap gap-4 mt-6">';
                        if (!empty($formSettings['editable']) && $existingSubmission) 
                            echo '<a href="?edit='.$form['id'].'#form-container-'.$form['id'].'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">Kliknij, by edytować</a>';
                        if (empty($formSettings['fillOnce'])) 
                            echo '<a href="?#form-container-'.$form['id'].'" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded shadow transition">Wyślij ponownie</a>';
                        echo '</div>';
                    } else {
                        // Budowa formularza
                        $prefill = []; $existingFiles = [];
                        $vault = new \CMS\Core\Vault();

                        if ($isEditing && $existingSubmission) {
                            $prefill = json_decode($vault->decrypt($existingSubmission['data_json']), true) ?? [];
                            $existingFiles = json_decode($existingSubmission['files_json'] ?? '{}', true) ?? [];
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

                        echo '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">';
                        echo '<input type="hidden" name="form_id" value="'.$form['id'].'">';
                        echo '<input type="hidden" name="return_url" value="'.htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/').'">';
                        if ($isEditing && $existingSubmission) echo '<input type="hidden" name="submission_id" value="'.$existingSubmission['id'].'">';

                        $fields = json_decode($form['form_json'], true) ?? [];
                        foreach ($fields as $field) {
                            $type = $field['type'] ?? 'text';
                            $widthClass = ($field['width']??'full') === 'full' ? 'md:col-span-3' : (($field['width']??'full')==='half'?'md:col-span-2':'md:col-span-1');

                            if ($type === 'html') {
                                echo "<div class='{$widthClass} prose max-w-none text-sm'>" . ($field['html'] ?? '') . "</div>";
                                continue;
                            }

                            // A. HONEYPOT
                            if ($type === 'honeypot') {
                                $fieldId = $field['custom_id'] ?? $field['id'] ?? md5('hp' . rand());
                                echo "<div style='position:absolute; left:-9999px; top:-9999px; opacity:0;' aria-hidden='true'>";
                                echo "<label for='hp_{$fieldId}'>Nie wypełniaj tego pola, jeśli jesteś człowiekiem</label>";
                                echo "<input type='text' id='hp_{$fieldId}' name='hp_data_{$fieldId}' value='' tabindex='-1' autocomplete='off'>";
                                echo "</div>";
                                continue;
                            }
                            
                            // B. CAPTCHA OBRAZKOWA (Gregwar)
                            if ($type === 'captcha_image') {
                                if (!class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
                                    echo "<div class='{$widthClass} p-3 bg-red-100 text-red-700 text-xs font-bold rounded'>Błąd systemu: Biblioteka obrazków nie została zainstalowana. Uruchom <code>composer require gregwar/captcha</code> w konsoli.</div>";
                                    continue;
                                }
                                $builder = new \Gregwar\Captcha\CaptchaBuilder;
                                $builder->build();
                                \CMS\Core\Session::set('captcha_img_' . $form['id'], $builder->getPhrase());
                                
                                echo "<div class='{$widthClass} bg-blue-50/50 p-4 rounded-xl border border-blue-100'>";
                                echo "<label class='block text-sm font-bold text-gray-700 mb-3'>Zabezpieczenie przed robotami <span class='text-red-500'>*</span></label>";
                                echo "<div class='flex flex-wrap sm:flex-nowrap gap-3 items-center'>";
                                echo "<img src='{$builder->inline()}' class='rounded-lg border border-blue-200 shadow-sm h-[50px] pointer-events-none select-none'>";
                                echo "<input type='text' name='captcha_answer' required class='flex-1 min-w-[150px] border border-blue-200 p-3 rounded-lg shadow-inner focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white' placeholder='Przepisz kod...'>";
                                echo "</div></div>";
                                continue;
                            }

                            // C. TURNSTILE (Cloudflare)
                            if ($type === 'captcha_turnstile') {
                                $siteKey = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'turnstile_site_key'")->fetch()['setting_value'] ?? '';
                                echo "<div class='{$widthClass}'>";
                                if (empty($siteKey)) {
                                    echo "<div class='p-3 bg-red-100 text-red-700 text-xs font-bold rounded'>Błąd systemu: Brak klucza <b>Site Key</b>. Dodaj go w zakładce Ustawienia.</div>";
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

                            // Standardowe Pola (Select, Radio, Text, File)
                            $fieldId = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
                            $val = $prefill[$fieldId] ?? '';
                            $req = !empty($field['required']);
                            $reqAttr = $req ? 'required' : '';
                            $reqStar = $req ? '<span class="text-red-500 ml-1" title="Pole wymagane">*</span>' : '';

                            echo "<div class='$widthClass'><label class='block text-sm font-bold text-gray-700 mb-2' for='{$fieldId}'>".htmlspecialchars($field['label']).$reqStar."</label>";
                            // ... Tu ładuje się Twój standardowy kod inputów (ukryty dla oszczędności znaków, po prostu go zostawiasz tak jak w poprzednim pliku)
                            if ($type === 'textarea') {
                                echo "<textarea id='{$fieldId}' name='data[{$fieldId}]' class='w-full border p-2.5 rounded shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none' rows='4' {$reqAttr}>".htmlspecialchars(is_array($val)?'':$val)."</textarea>";
                            } else {
                                echo "<input type='{$type}' id='{$fieldId}' name='data[{$fieldId}]' value='".htmlspecialchars(is_array($val)?'':$val)."' class='w-full border p-2.5 rounded shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none' {$reqAttr}>";
                            }
                            echo "</div>";
                        }

                        $btnText = $isEditing ? 'Zaktualizuj dane' : 'Wyślij';
                        echo '<div class="md:col-span-3 mt-4"><button class="w-full bg-primary hover:opacity-90 text-white font-bold py-3.5 rounded-lg transition shadow-md">'.$btnText.'</button>';
                        if ($isEditing) echo '<div class="text-center mt-3"><a href="?" class="text-sm font-bold text-gray-500 hover:text-gray-800">Anuluj edycję</a></div>';
                        echo '</div></form>';
                    }
                    echo '</div>';
                }
            }
            echo "</div>";
        }
    }
}