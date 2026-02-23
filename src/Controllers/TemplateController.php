<?php
namespace CMS\Controllers;
use CMS\Core\Database;
use CMS\Core\Session;

class TemplateController {
    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn()) { header('Location: /login'); exit; }
    }

    public function index() {
        $db = Database::getInstance();
        $templates = $db->query("SELECT * FROM pa_templates ORDER BY id DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/templates/index.php'; // Utwórz prostą tabelkę podobną do listy stron
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        $defaultHtml = "<div class=\"container mx-auto py-10\">\n  \n  {{zone:main}}\n</div>\n\n\n{{global_footer}}";
        $db->query("INSERT INTO pa_templates (title, html_content) VALUES ('Nowy Szablon', :html)", ['html' => $defaultHtml]);
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/templates/edit?id=$id");
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        $db = Database::getInstance();
        $template = $db->query("SELECT * FROM pa_templates WHERE id = :id", ['id' => $id])->fetch();
        
        $safeHtml = htmlspecialchars($template['html_content'] ?? '');

        ob_start();
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>';
        echo '<div class="p-8 max-w-6xl mx-auto bg-white rounded shadow">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-2xl font-bold">Edytuj Szablon</h1>
                    <p class="text-sm text-gray-500 mt-1">Użyj znacznika <b class="bg-gray-100 px-1 rounded">{{zone:nazwa}}</b> oraz <b class="bg-gray-100 px-1 rounded">{{global_footer}}</b></p>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" id="btn-toggle-preview" class="bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow hover:bg-gray-300 transition flex items-center gap-2">
                        <span>👁️</span> Podgląd <span class="text-xs font-normal opacity-75">(Ctrl+P)</span>
                    </button>
                    <button type="button" id="btn-new-tab" class="bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow hover:bg-gray-300 transition" title="Otwórz podgląd w nowej karcie">
                        ↗️
                    </button>
                </div>
            </div>

            <form action="/admin/templates/save" method="POST" id="templateForm">
                <input type="hidden" name="id" value="'.$template['id'].'">
                <input type="text" name="title" value="'.htmlspecialchars($template['title']).'" class="w-full border p-2 mb-4 rounded font-bold" placeholder="Nazwa Szablonu">
                
                <div class="border rounded overflow-hidden shadow-sm mb-4 relative" id="editor-container">
                    <div id="ace-editor" style="height: 700px; width: 100%;"></div>
                    <iframe id="preview-frame" class="hidden w-full bg-white" style="height: 700px; border: none;"></iframe>
                </div>
                
                <textarea name="html_content" id="html_content" class="hidden">'.$safeHtml.'</textarea>
                
                <div class="flex items-center gap-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow transition">Zapisz szablon</button>
                    <span class="text-sm text-gray-400">Możesz też użyć skrótu <b>Ctrl + S</b> aby zapisać.</span>
                </div>
            </form>
        </div>

        <script>
            var editor = ace.edit("ace-editor");
            editor.setTheme("ace/theme/monokai"); 
            editor.session.setMode("ace/mode/html");
            editor.setOptions({
                fontSize: "14px",
                showPrintMargin: false,
                wrap: true
            });
            editor.session.setValue(document.getElementById("html_content").value);
            
            document.getElementById("templateForm").addEventListener("submit", function() {
                document.getElementById("html_content").value = editor.getValue();
            });

            let isPreview = false;
            const editorEl = document.getElementById("ace-editor");
            const previewEl = document.getElementById("preview-frame");
            const btnToggle = document.getElementById("btn-toggle-preview");
            const btnNewTab = document.getElementById("btn-new-tab");

            function getPreviewHtml() {
                let html = editor.getValue();
                
                // Używamy `backticks` (grawisów) w JS, co zapobiega błędom SyntaxError przy HTML
                // Dodatkowo escapeujemy slash w tagu script: <\/script> - to kluczowa poprawka!
                
                html = html.replace(/\{\{zone:([a-zA-Z0-9_]+)\}\}/g, `<div style="min-height:100px; border:2px dashed #3b82f6; background-color:#eff6ff; padding:20px; display:flex; align-items:center; justify-content:center; font-family:sans-serif; color:#3b82f6; border-radius:8px; font-weight:bold; margin: 10px 0;">🧩 STREFA: $1</div>`);
                
                html = html.replace(/\{\{global_footer\}\}/g, `<div style="padding:20px; background:#f3f4f6; text-align:center; border-top:1px dashed #d1d5db; font-family:sans-serif; color:#6b7280; font-size:12px; margin-top: 20px;">[ GLOBALNA STOPKA ]</div>`);
                
                if (!html.includes("<head") && !html.includes("tailwindcss")) {
                    // Tutaj był błąd - script tag musi mieć escapowany slash wewnątrz stringa JS
                    html = `<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"><\/script></head><body class="antialiased text-gray-800">` + html + `</body></html>`;
                }
                return html;
            }

            function togglePreview() {
                isPreview = !isPreview;
                if (isPreview) {
                    editorEl.classList.add("hidden");
                    previewEl.classList.remove("hidden");
                    
                    btnToggle.innerHTML = "<span>✏️</span> Wróć do Kodu <span class=\'text-xs font-normal opacity-75\'>(Ctrl+P)</span>";
                    btnToggle.className = "bg-blue-100 text-blue-800 font-bold py-2 px-4 rounded shadow hover:bg-blue-200 transition flex items-center gap-2";
                    
                    const doc = previewEl.contentWindow.document;
                    doc.open();
                    doc.write(getPreviewHtml());
                    doc.close();
                } else {
                    previewEl.classList.add("hidden");
                    editorEl.classList.remove("hidden");
                    
                    btnToggle.innerHTML = "<span>👁️</span> Podgląd <span class=\'text-xs font-normal opacity-75\'>(Ctrl+P)</span>";
                    btnToggle.className = "bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded shadow hover:bg-gray-300 transition flex items-center gap-2";
                    editor.focus();
                }
            }

            btnToggle.addEventListener("click", togglePreview);

            btnNewTab.addEventListener("click", function() {
                const newWin = window.open("", "_blank");
                newWin.document.write(getPreviewHtml());
                newWin.document.close();
            });

            document.addEventListener("keydown", function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === \'p\') {
                    e.preventDefault();
                    togglePreview();
                }
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === \'s\') {
                    e.preventDefault();
                    document.getElementById("html_content").value = editor.getValue();
                    document.getElementById("templateForm").submit();
                }
            });
            
            editor.commands.addCommand({
                name: \'saveTemplateCommand\',
                bindKey: {win: \'Ctrl-S\',  mac: \'Command-S\'},
                exec: function(editor) {
                    document.getElementById("html_content").value = editor.getValue();
                    document.getElementById("templateForm").submit();
                }
            });
            
            editor.commands.addCommand({
                name: \'togglePreviewCommand\',
                bindKey: {win: \'Ctrl-P\',  mac: \'Command-P\'},
                exec: function(editor) { togglePreview(); }
            });
        </script>';
        
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if (!$id) { header("Location: /admin/templates"); exit; }
        
        $db = Database::getInstance();
        
        // Zabezpieczenie przed usunięciem szablonu w użyciu
        $usage = $db->query("SELECT COUNT(*) as cnt FROM pa_data WHERE template_id = :id", ['id' => $id])->fetch();
        
        if ($usage['cnt'] > 0) {
            Session::setFlash("Nie można usunąć tego szablonu, ponieważ używa go <b>{$usage['cnt']}</b> strona/y.", 'error');
        } else {
            $db->query("DELETE FROM pa_templates WHERE id = :id", ['id' => $id]);
            Session::setFlash("Szablon usunięty poprawnie.", 'success');
        }
        header("Location: /admin/templates");
        exit;
    }

    public function save() {
        $db = Database::getInstance();
        $db->query("UPDATE pa_templates SET title = :title, html_content = :html WHERE id = :id", [
            'title' => $_POST['title'],
            'html' => $_POST['html_content'],
            'id' => $_POST['id']
        ]);
        header("Location: /admin/templates");
    }
}