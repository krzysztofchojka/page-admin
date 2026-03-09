<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class TemplateController {
    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    private function ensureActiveColumnExists($db)
    {
        try {
            $db->query("ALTER TABLE pa_templates ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER html_content");
        } catch (\Exception $e) {
            // Kolumna już istnieje
        }
    }

    public function index()
    {
        $db = Database::getInstance();
        $this->ensureActiveColumnExists($db);
        
        $templates = $db->query("SELECT * FROM pa_templates ORDER BY id DESC")->fetchAll();
        
        ob_start();
        require_once __DIR__ . '/../Views/admin/templates/index.php';
        $content = ob_get_clean();
        
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        $defaultHtml = "{{admin_navigator}}\n{{navigator}}\n\n<div class=\"container mx-auto py-10\">\n \n {{zone:main}}\n</div>\n\n\n{{global_footer}}";
        $db->query("INSERT INTO pa_templates (title, html_content) VALUES ('Nowy Szablon', :html)", ['html' => $defaultHtml]);
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/templates/edit?id=$id");
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        $db = \CMS\Core\Database::getInstance();
        $template = $db->query("SELECT * FROM pa_templates WHERE id = :id", ['id' => $id])->fetch();
        $safeHtml = htmlspecialchars($template['html_content'] ?? '');

        ob_start();
        ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
        
        <div class="h-[calc(100vh-80px)] flex flex-col bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 border-b p-4 flex justify-between items-center z-10">
                <div class="flex items-center gap-4 flex-1">
                    <a href="/admin/templates" class="text-gray-500 hover:text-black font-bold">← Wróć</a>
                    <input type="hidden" name="id" id="template_id" value="<?= $template['id'] ?>">
                    <input type="text" name="title" id="template_title" value="<?= htmlspecialchars($template['title']) ?>" class="border border-transparent hover:border-gray-300 focus:border-blue-500 p-2 rounded font-bold text-xl outline-none transition w-1/3" placeholder="Nazwa Szablonu">
                </div>
                
                <div class="flex gap-2 items-center shrink-0">
                    <button type="button" id="btn-toggle-preview" class="bg-blue-100 text-blue-800 font-bold py-2 px-4 rounded shadow-sm hover:bg-blue-200 transition flex items-center gap-2">
                        <span>👁️</span> Podgląd <span class="text-xs font-normal opacity-75">(Ctrl+P)</span>
                    </button>
                    <button type="button" id="btn-save" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-8 rounded shadow-sm transition">
                        💾 Zapisz
                    </button>
                    <button type="button" id="btn-toggle-sidebar" class="bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-300 transition" title="Pokaż/Ukryj legendę">
                        Znaczniki ☰
                    </button>
                </div>
            </div>

            <div class="flex-1 flex overflow-hidden relative">
                
                <div class="flex-1 flex flex-col relative" id="editor-wrapper">
                    <div id="ace-editor" class="absolute inset-0"></div>
                    <iframe id="preview-frame" class="hidden absolute inset-0 w-full h-full bg-white border-none"></iframe>
                    <textarea id="html_content" class="hidden"><?= $safeHtml ?></textarea>
                </div>

                <div id="sidebar-legend" class="w-80 bg-gray-50 border-l border-gray-200 flex flex-col transition-all duration-300 shrink-0 overflow-hidden" style="width: 20rem;">
                    <div class="p-5 flex-1 overflow-y-auto">
                        <p class="text-xs text-gray-500 mb-4 font-medium uppercase tracking-wider text-center border-b pb-2">Kliknij, aby skopiować 📋</p>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="font-bold text-gray-700 text-sm mb-2 flex items-center gap-2">🧩 Struktura</h3>
                                <div class="flex flex-col gap-1.5">
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-blue-600 shadow-sm text-xs cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition" data-tag="{{zone:main}}">{{zone:main}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-gray-600 shadow-sm text-xs cursor-pointer hover:border-gray-400 hover:bg-gray-100 transition" data-tag="{{global_footer}}">{{global_footer}}</code>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-700 text-sm mb-2 flex items-center gap-2">🗺️ Nawigacja</h3>
                                <div class="flex flex-col gap-1.5">
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-indigo-600 shadow-sm text-xs cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition" data-tag="{{navigator}}">{{navigator}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-indigo-600 shadow-sm text-xs cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition" data-tag="{{admin_navigator}}">{{admin_navigator}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-indigo-600 shadow-sm text-xs cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition" data-tag="{{menu}}">{{menu}}</code>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-700 text-sm mb-2 flex items-center gap-2">📝 Dane Strony</h3>
                                <div class="flex flex-col gap-1.5">
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-purple-600 shadow-sm text-xs cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" data-tag="{{site_title}}">{{site_title}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-purple-600 shadow-sm text-xs cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" data-tag="{{page_title}}">{{page_title}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-purple-600 shadow-sm text-xs cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" data-tag="{{site_logo}}">{{site_logo}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-purple-600 shadow-sm text-xs cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" data-tag="{{user_name}}">{{user_name}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-purple-600 shadow-sm text-xs cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition" data-tag="{{current_year}}">{{current_year}}</code>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-700 text-sm mb-2 flex items-center gap-2">🎨 Kolory (Ustawienia)</h3>
                                <div class="flex flex-col gap-1.5">
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-green-600 shadow-sm text-xs cursor-pointer hover:border-green-400 hover:bg-green-50 transition" data-tag="{{color_primary}}">{{color_primary}}</code>
                                    <code class="tag-copy block bg-white px-2 py-1.5 rounded border border-gray-200 text-green-600 shadow-sm text-xs cursor-pointer hover:border-green-400 hover:bg-green-50 transition" data-tag="{{color_secondary}}">{{color_secondary}}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-5 border-t bg-gradient-to-b from-gray-50 to-gray-100">
                        <h3 class="font-bold text-gray-800 text-sm mb-2 flex items-center gap-2">🤖 Asystent AI</h3>
                        <p class="text-[11px] text-gray-500 mb-3 leading-tight">Skopiuj gotowy prompt dla modelu AI (Gemini/ChatGPT), wklej go w czacie i łatwo generuj wspaniałe szablony.</p>
                        <button type="button" id="btn-copy-prompt" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md transition transform hover:-translate-y-0.5 text-xs flex items-center justify-center gap-2">
                            ✨ Kopiuj Prompt dla AI
                        </button>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>

        <script>
            // --- INICJALIZACJA EDYTORA ---
            var editor = ace.edit("ace-editor");
            editor.setTheme("ace/theme/monokai");
            editor.session.setMode("ace/mode/html");
            editor.setOptions({
                fontSize: "15px",
                showPrintMargin: false,
                wrap: true
            });
            editor.session.setValue(document.getElementById("html_content").value);

            // --- ZAPISYWANIE AJAX ---
            function saveTemplate() {
                const btn = document.getElementById('btn-save');
                const originalText = "💾 Zapisz";
                
                // Zmiana stanu przycisku
                btn.innerHTML = '⏳ Zapisywanie...';
                btn.disabled = true;
                btn.classList.add('opacity-75');

                const payload = {
                    id: document.getElementById('template_id').value,
                    title: document.getElementById('template_title').value,
                    html_content: editor.getValue()
                };

                fetch('/admin/templates/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        // Efekt sukcesu
                        btn.innerHTML = '✅ Zapisano!';
                        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'opacity-75');
                        btn.classList.add('bg-green-600', 'hover:bg-green-700');
                        
                        // Powrót po 2 sekundach
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                            btn.disabled = false;
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Błąd zapisu:', error);
                    btn.innerHTML = '❌ Błąd';
                    setTimeout(() => { btn.innerHTML = originalText; btn.disabled = false; btn.classList.remove('opacity-75'); }, 2000);
                });
            }

            document.getElementById('btn-save').addEventListener('click', saveTemplate);

            // Klawisz Ctrl+S
            document.addEventListener("keydown", function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    saveTemplate();
                }
            });
            editor.commands.addCommand({
                name: 'saveTemplateCommand',
                bindKey: {win: 'Ctrl-S', mac: 'Command-S'},
                exec: function(editor) { saveTemplate(); }
            });

            // --- KOPIOWANIE DO SCHOWKA (Apple, Linux, Windows) ---
            document.querySelectorAll('.tag-copy').forEach(el => {
                el.addEventListener('click', async function() {
                    const textToCopy = this.dataset.tag;
                    const originalHTML = this.innerHTML;
                    
                    try {
                        // Nowoczesne API dla bezpiecznych kontekstów (HTTPS / localhost)
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(textToCopy);
                        } else {
                            // Awaryjne API (dla starszych iOS / HTTP)
                            const textArea = document.createElement("textarea");
                            textArea.value = textToCopy;
                            // Ukryj element poza ekranem
                            textArea.style.position = "fixed";
                            textArea.style.left = "-999999px";
                            textArea.style.top = "-999999px";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            
                            // Dla iOS
                            textArea.setSelectionRange(0, 99999);
                            document.execCommand('copy');
                            textArea.remove();
                        }
                        
                        // Wizualne potwierdzenie
                        this.innerHTML = '✅ Skopiowano';
                        this.classList.add('bg-green-100', 'border-green-400', 'text-green-800');
                        this.classList.remove('text-blue-600', 'text-gray-600', 'text-indigo-600', 'text-purple-600', 'text-green-600');
                        
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.className = this.getAttribute('class').replace(/(bg-green-100|border-green-400|text-green-800)/g, '');
                        }, 1000);
                        
                    } catch (err) {
                        console.error('Błąd kopiowania:', err);
                        this.innerHTML = '❌ Błąd';
                        setTimeout(() => this.innerHTML = originalHTML, 1000);
                    }
                });
            });

            // --- POKAZYWANIE / UKRYWANIE PASKA BOCZNEGO ---
            let sidebarVisible = true;
            document.getElementById('btn-toggle-sidebar').addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar-legend');
                sidebarVisible = !sidebarVisible;
                
                if(sidebarVisible) {
                    sidebar.style.width = '20rem'; // 320px (odpowiednik w-80)
                    sidebar.classList.add('border-l');
                } else {
                    sidebar.style.width = '0px';
                    sidebar.classList.remove('border-l');
                }
                
                // Dajemy edytorowi znać, że zmienił się rozmiar okna, żeby dopasował kod
                setTimeout(() => editor.resize(), 300);
            });

            // --- GENERATOR PROMPTU DLA AI ---
            const aiPrompt = `Jesteś ekspertem UI/UX i zaawansowanym Frontend Developerem. Twoim zadaniem jest generowanie nowoczesnego, responsywnego kodu HTML dla autorskiego systemu CMS.

Rygorystyczne zasady generowania kodu:
1. Zwracaj WYŁĄCZNIE czysty kod HTML (to, co weszłoby w <body>). Absolutnie NIE dołączaj tagów <html>, <head>, <body>, ani <!DOCTYPE>.
2. Używaj wyłącznie klas Tailwind CSS. Żadnego zewnętrznego CSS czy skryptów JS.
3. CMS używa dynamicznych tagów w klamrach. Musisz umieścić dokładnie te tagi w skrajnych miejscach kodu, BEZ ŻADNEGO OWIJANIA ich w tagi <div>, <header> czy inne kontenery:
   - {{admin_navigator}} - Pierwsza linijka kodu (samotna).
   - {{navigator}} - Druga linijka kodu (samotna).
   - {{global_footer}} - Ostatnia linijka kodu (samotna).
4. Buduj layout w oparciu o tagi <section>. Używaj nowoczesnych rozwiązań Tailwind: parallax (bg-fixed), glassmorphism (backdrop-blur, bg-white/10), mix-blend-mode, filtry i zaawansowane siatki (Grid/Flex). Obrazki tła zaciągaj bezpośrednio z Unsplash via style inline.
5. Zostawiaj "puste strefy" (Drop-Zones) na klocki CMS za pomocą tagu {{zone:NAZWA_STREFY}} (np. {{zone:hero_content}}, {{zone:services}}). Obudowuj te tagi ładnymi kontenerami w Tailwind.
6. Jeśli używasz zmiennych {{color_primary}} / {{color_secondary}}, podpinaj je przez style inline (np. style="color: {{color_primary}};"). Jeśli nie jest to narzucone, korzystaj z wbudowanej palety Tailwind dla szybkiego i estetycznego efektu.

OPIS SZABLONU DO WYGENEROWANIA:
[TUTAJ WPISZ SWOJE POLECENIE - np. "Potrzebuję nowoczesnego landing page dla warsztatu samochodowego. Zrób duży obszar Hero na górze {{zone:hero}}, poniżej strefę na usługi w układzie siatki {{zone:services}} oraz sekcję kontaktową na dole {{zone:contact}}."]`;

            document.getElementById('btn-copy-prompt').addEventListener('click', async function() {
                const btn = this;
                const originalText = btn.innerHTML;
                
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(aiPrompt);
                    } else {
                        const textArea = document.createElement("textarea");
                        textArea.value = aiPrompt;
                        textArea.style.position = "fixed";
                        textArea.style.left = "-999999px";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        textArea.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        textArea.remove();
                    }
                    
                    btn.innerHTML = '✅ Skopiowano do czatu!';
                    btn.classList.remove('from-purple-600', 'to-blue-600');
                    btn.classList.add('from-emerald-500', 'to-green-600');
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('from-emerald-500', 'to-green-600');
                        btn.classList.add('from-purple-600', 'to-blue-600');
                    }, 3000);
                    
                } catch (err) {
                    console.error('Błąd kopiowania promptu:', err);
                    btn.innerHTML = '❌ Błąd kopiowania';
                    setTimeout(() => btn.innerHTML = originalText, 2000);
                }
            });

            // --- PODGLĄD NA ŻYWO ---
            let isPreview = false;
            const editorEl = document.getElementById("ace-editor");
            const previewEl = document.getElementById("preview-frame");
            const btnToggle = document.getElementById("btn-toggle-preview");

            function getPreviewHtml() {
                let html = editor.getValue();
                html = html.replace(/\{\{zone:([a-zA-Z0-9_]+)\}\}/g, `<div style="min-height:100px; border:2px dashed #3b82f6; background-color:#eff6ff; padding:20px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; color:#3b82f6; border-radius:8px; font-weight:bold; margin: 10px 0;">🧩 STREFA: $1</div>`);
                html = html.replace(/\{\{global_footer\}\}/g, `<div style="padding:20px; background:#f3f4f6; text-align:center; border-top:1px dashed #d1d5db; font-family:sans-serif; color:#6b7280; font-size:12px; margin-top: 20px;">[ GLOBALNA STOPKA ]</div>`);
                html = html.replace(/\{\{admin_navigator\}\}/g, `<div style="padding:10px; background:#111827; color:#60a5fa; text-align:center; border-bottom:2px solid #3b82f6; font-family:sans-serif; font-size:13px; font-weight:bold;">[ PASEK ADMINISTRATORA CMS ]</div>`);
                html = html.replace(/\{\{navigator\}\}/g, `<div style="padding:20px; background:#1f2937; color:#fff; text-align:center; font-family:sans-serif; font-weight:bold;">[ STANDARDOWY NAVIGATOR ]</div>`);
                html = html.replace(/\{\{menu\}\}/g, `<div style="padding:10px; background:#e5e7eb; color:#374151; text-align:center; border:1px dashed #9ca3af; font-family:sans-serif;">[ POZYCJE MENU ]</div>`);
                html = html.replace(/\{\{site_title\}\}/g, `<strong>[Tytuł Strony]</strong>`);
                html = html.replace(/\{\{page_title\}\}/g, `<strong>[Tytuł Podstrony]</strong>`);
                html = html.replace(/\{\{site_logo\}\}/g, `[URL_LOGOTYPU]`);
                html = html.replace(/\{\{user_name\}\}/g, `[login@test.pl]`);
                html = html.replace(/\{\{current_year\}\}/g, new Date().getFullYear());
                html = html.replace(/\{\{color_primary\}\}/g, `#f97316`);
                html = html.replace(/\{\{color_secondary\}\}/g, `#1e3a8a`);
                
                // ZWRACAMY CDN TAILWINDA SPECJALNIE DLA PODGLĄDU NA ŻYWO
                if (!html.includes("<head") && !html.includes("tailwindcss")) {
                    html = `<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"><\/script></head><body class="antialiased text-gray-800">` + html + `</body></html>`;
                }
                return html;
            }

            function togglePreview() {
                isPreview = !isPreview;
                if (isPreview) {
                    editorEl.classList.add("hidden");
                    previewEl.classList.remove("hidden");
                    btnToggle.innerHTML = "<span>✏️</span> Wróć do Kodu <span class='text-xs font-normal opacity-75'>(Ctrl+P)</span>";
                    btnToggle.className = "bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow-sm hover:bg-gray-300 transition flex items-center gap-2";
                    
                    const doc = previewEl.contentWindow.document;
                    doc.open();
                    doc.write(getPreviewHtml());
                    doc.close();
                } else {
                    previewEl.classList.add("hidden");
                    editorEl.classList.remove("hidden");
                    btnToggle.innerHTML = "<span>👁️</span> Podgląd <span class='text-xs font-normal opacity-75'>(Ctrl+P)</span>";
                    btnToggle.className = "bg-blue-100 text-blue-800 font-bold py-2 px-4 rounded shadow-sm hover:bg-blue-200 transition flex items-center gap-2";
                    editor.focus();
                }
            }

            btnToggle.addEventListener("click", togglePreview);

            document.addEventListener("keydown", function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
                    e.preventDefault();
                    togglePreview();
                }
            });
            editor.commands.addCommand({
                name: 'togglePreviewCommand',
                bindKey: {win: 'Ctrl-P', mac: 'Command-P'},
                exec: function(editor) { togglePreview(); }
            });
        </script>
        <?php
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function toggleActive()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Brak ID szablonu.']);
            exit;
        }

        $db = Database::getInstance();
        $this->ensureActiveColumnExists($db);
        
        $template = $db->query("SELECT * FROM pa_templates WHERE id = :id", ['id' => $id])->fetch();
        
        if ($template) {
            if ($template['is_active']) {
                $usage = $db->query("SELECT COUNT(*) as cnt FROM pa_data WHERE template_id = :id", ['id' => $id])->fetch();
                if ($usage['cnt'] > 0) {
                    echo json_encode(['status' => 'error', 'message' => "Nie można wyłączyć tego szablonu, używa go {$usage['cnt']} strona/y."]);
                    exit;
                } else {
                    $db->query("UPDATE pa_templates SET is_active = 0 WHERE id = :id", ['id' => $id]);
                    echo json_encode(['status' => 'success', 'is_active' => 0]);
                    exit;
                }
            } else {
                $db->query("UPDATE pa_templates SET is_active = 1 WHERE id = :id", ['id' => $id]);
                echo json_encode(['status' => 'success', 'is_active' => 1]);
                exit;
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Szablon nie istnieje.']);
        exit;
    }

    public function delete()
    {
        // Odbieramy dane JSON z frontendu
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Brak ID szablonu.']);
            exit;
        }

        $db = Database::getInstance();
        $usage = $db->query("SELECT COUNT(*) as cnt FROM pa_data WHERE template_id = :id", ['id' => $id])->fetch();
        
        if ($usage['cnt'] > 0) {
            // Szablon jest w użyciu - zwracamy błąd w JSON
            echo json_encode([
                'status' => 'error', 
                'message' => "Nie można usunąć tego szablonu, ponieważ używa go {$usage['cnt']} strona/y."
            ]);
        } else {
            // Sukces - usuwamy
            $db->query("DELETE FROM pa_templates WHERE id = :id", ['id' => $id]);
            echo json_encode(['status' => 'success']);
        }
        exit;
    }

    public function save() {
        $db = \CMS\Core\Database::getInstance();

        // Odbieramy dane JSON z żądania (fetch API)
        $data = json_decode(file_get_contents('php://input'), true);

        // CZYSZCZENIE CACHE
        $clearCache = function() {
            $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
            if (is_array($cacheFiles)) {
                foreach ($cacheFiles as $file) {
                    if(is_file($file)) unlink($file);
                }
            }
        };

        if ($data) {
            $db->query("UPDATE pa_templates SET title = :title, html_content = :html WHERE id = :id", [
                'title' => $data['title'],
                'html' => $data['html_content'],
                'id' => $data['id']
            ]);

            $clearCache();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        }

        // Fallback dla standardowego formularza (gdyby skrypty zawiodły)
        $db->query("UPDATE pa_templates SET title = :title, html_content = :html WHERE id = :id", [
            'title' => $_POST['title'],
            'html' => $_POST['html_content'],
            'id' => $_POST['id']
        ]);

        $clearCache();
        header("Location: /admin/templates");
    }
}