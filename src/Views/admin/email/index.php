<div class="max-w-6xl mx-auto relative">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Panel Email & SMS</h1>
        <div class="flex gap-2">
            <button onclick="openCompose('new')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition">
                + Nowa Wiadomość
            </button>
            <a href="/admin/email/templates" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded shadow transition">📋 Szablony</a>
            <a href="/admin/settings" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded shadow transition"> ⚙️ Ustawienia IMAP/SMTP </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-green-500">
            <div class="bg-green-100 p-4 rounded-full text-green-600 text-2xl">📥</div>
            <div>
                <p class="text-gray-500 text-sm font-bold uppercase">Odebrane (IMAP)</p>
                <p class="text-3xl font-extrabold text-gray-800" id="imap-count">-</p>
            </div>
        </div>
        <a href="/admin/email/queue" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-orange-500 hover:bg-orange-50 transition cursor-pointer">
            <div class="bg-orange-100 p-4 rounded-full text-orange-600 text-2xl">⏳</div>
            <div>
                <p class="text-gray-500 text-sm font-bold uppercase">Kolejka (Wysyłka)</p>
                <p class="text-sm font-bold text-orange-600 mt-1">Sprawdź harmonogram ↗</p>
            </div>
        </a>
        <a href="/admin/email/lists" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-blue-500 hover:bg-blue-50 transition cursor-pointer">
            <div class="bg-blue-100 p-4 rounded-full text-blue-600 text-2xl">👥</div>
            <div>
                <p class="text-gray-500 text-sm font-bold uppercase">Listy Odbiorców</p>
                <p class="text-sm font-bold text-blue-600 mt-1">Zarządzaj subskrybentami ↗</p>
            </div>
        </a>
    </div>

    <div class="flex border-b border-gray-200 bg-gray-50 rounded-t-xl overflow-hidden shadow-sm">
        <button onclick="switchTab('inbox')" id="tab-btn-inbox" class="flex-1 py-4 font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition">
            📥 Odebrane (IMAP)
        </button>
        <button onclick="switchTab('sent')" id="tab-btn-sent" class="flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent">
            🚀 Wysłane z panelu
        </button>
    </div>

    <div class="bg-white shadow-sm border-x border-b border-gray-200 rounded-b-xl overflow-hidden mb-8 relative">
        <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h2 id="list-title" class="font-bold text-gray-700 text-lg">Ostatnie wiadomości (Inbox)</h2>
                <span id="cache-badge" class="hidden bg-gray-200 text-gray-600 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Wersja z Cache</span>
            </div>
            <button onclick="refreshCurrentTab()" id="btn-refresh" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm">
                <span id="icon-refresh">🔄</span> Odśwież skrzynkę
            </button>
        </div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs w-40">Data</th>
                    <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs" id="col-sender">Nadawca</th>
                    <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Temat</th>
                </tr>
            </thead>
            <tbody id="imap-tbody" class="divide-y divide-gray-100">
            </tbody>
        </table>
    </div>

    <div class="mt-8 bg-gray-900 rounded-xl overflow-hidden shadow-lg border border-gray-800">
        <div class="bg-gray-800 px-5 py-3 flex justify-between items-center cursor-pointer hover:bg-gray-700 transition" onclick="document.getElementById('imap-debug').classList.toggle('hidden')">
            <span class="text-gray-200 font-bold text-sm uppercase flex items-center gap-2">
                <span>🛠️</span> Logi serwera IMAP (Kliknij by rozwinąć)
            </span>
            <span class="text-gray-400 font-bold">▼</span>
        </div>
        <div id="imap-debug" class="hidden p-5">
            <pre id="imap-debug-content" class="text-green-400 text-[11px] font-mono overflow-x-auto whitespace-pre-wrap leading-relaxed">Oczekiwanie na logi...</pre>
        </div>
    </div>
</div>

<div id="email-modal" class="hidden fixed inset-0 bg-black/60 z-[100] flex justify-center items-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden">
        <div class="bg-gray-50 border-b p-4 sm:p-6 flex flex-col gap-2 relative">
            <button onclick="document.getElementById('email-modal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl font-bold transition">&times;</button>
            <h2 id="modal-subject" class="text-xl sm:text-2xl font-bold text-gray-800 pr-8 leading-tight">Ładowanie...</h2>
            <div class="flex flex-wrap justify-between items-center text-sm mt-2">
                <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-full"><span class="font-bold text-gray-800" id="modal-from-label">Od:</span> <span id="modal-from">...</span></span>
                <span id="modal-date" class="text-gray-500 font-bold mt-2 sm:mt-0">...</span>
            </div>
        </div>
        <div class="flex-1 bg-white relative overflow-y-auto p-6" id="modal-body-container">
            <div id="modal-loader" class="absolute inset-0 flex flex-col items-center justify-center bg-white/90 z-10">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
                <span class="font-bold text-gray-500">Pobieranie i dekodowanie treści...</span>
            </div>
            <div id="modal-body" class="prose max-w-none w-full"></div>
        </div>
        <div class="bg-gray-50 border-t p-4 flex flex-wrap justify-between items-center gap-3">
            <button onclick="document.getElementById('email-modal').classList.add('hidden')" class="px-6 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-300 transition">Zamknij</button>
            <div class="flex gap-2 w-full sm:w-auto" id="modal-actions">
                <button onclick="openCompose('forward')" class="flex-1 sm:flex-none text-center px-6 py-2 border border-blue-200 text-blue-700 bg-blue-50 font-bold rounded-lg hover:bg-blue-100 transition shadow-sm">➡️ Przekaż</button>
                <button onclick="openCompose('reply')" id="btn-reply" class="flex-1 sm:flex-none text-center px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-md">↩️ Odpowiedz</button>
            </div>
        </div>
    </div>
</div>

<div id="compose-modal" class="hidden fixed inset-0 bg-black/60 z-[110] flex justify-center items-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden">
        <div class="bg-gray-800 text-white border-b border-gray-700 p-4 flex justify-between items-center shrink-0">
            <h3 id="compose-title" class="font-bold text-lg">Nowa Wiadomość</h3>
            <button onclick="document.getElementById('compose-modal').classList.add('hidden')" class="text-gray-400 hover:text-white text-2xl font-bold transition">&times;</button>
        </div>
        
        <div class="flex-1 flex flex-col min-h-0 bg-white relative">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col gap-3 shrink-0">
                <div class="flex items-center gap-3">
                    <label class="w-16 text-right font-bold text-sm text-gray-500">Do:</label>
                    <input type="text" id="compose-to" class="flex-1 border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="adres@odbiorcy.pl">
                </div>
                <div class="flex items-center gap-3">
                    <label class="w-16 text-right font-bold text-sm text-gray-500">Temat:</label>
                    <input type="text" id="compose-subject" class="flex-1 border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Wpisz temat...">
                </div>
            </div>
            
            <div class="flex-1 flex flex-col min-h-0 quill-wrapper">
                <div id="quill-composer" class="flex-1 min-h-0 bg-white"></div>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t p-4 flex justify-between items-center gap-3 shrink-0">
            <button onclick="document.getElementById('compose-modal').classList.add('hidden')" class="px-6 py-2 text-gray-600 font-bold rounded-lg hover:bg-gray-200 transition">Anuluj</button>
            <button onclick="sendDirectEmail()" id="btn-send-direct" class="px-8 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-md flex items-center gap-2">
                <span>🚀</span> Wyślij
            </button>
        </div>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<style>
#quill-composer .ql-editor { font-size: 14px; font-family: sans-serif; }
#quill-composer .ql-editor blockquote { border-left: 4px solid #cbd5e1; padding-left: 16px; color: #475569; font-style: normal; margin-top:10px; margin-bottom:10px; }
</style>

<script>
// --- ZMIENNE GLOBALNE ---
let composeQuill = null;
let currentEmailData = { from: '', subject: '', date: '', rawBody: '', pureEmail: '' };
let currentTab = 'inbox';
window.sentEmailsData = []; // Cache na historię

const escapeHTML = (str) => {
    return (str || '').toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
};

// --- OBSŁUGA ZAKŁADEK ---
function switchTab(tab) {
    currentTab = tab;
    const btnInbox = document.getElementById('tab-btn-inbox');
    const btnSent = document.getElementById('tab-btn-sent');
    const title = document.getElementById('list-title');
    const cacheBadge = document.getElementById('cache-badge');
    const colSender = document.getElementById('col-sender');

    if(tab === 'inbox') {
        btnInbox.className = 'flex-1 py-4 font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition';
        btnSent.className = 'flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent bg-gray-50';
        title.innerText = 'Ostatnie wiadomości (Inbox)';
        colSender.innerText = 'Nadawca';
        loadEmails(false);
    } else {
        btnSent.className = 'flex-1 py-4 font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition';
        btnInbox.className = 'flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent bg-gray-50';
        title.innerText = 'Wysłane bezpośrednio z panelu CMS';
        colSender.innerText = 'Odbiorca';
        cacheBadge.classList.add('hidden');
        loadSentEmails();
    }
}

function refreshCurrentTab() {
    if(currentTab === 'inbox') loadEmails(true);
    else loadSentEmails();
}

// Inicjalizacja Edytora WYSIWYG
function initComposer() {
    if(!composeQuill) {
        composeQuill = new Quill('#quill-composer', {
            theme: 'snow',
            modules: { toolbar: [ ['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['link'], ['clean'] ] }
        });
    }
}

// --- OTWIERANIE OKNA KOMPONOWANIA (Nowy/Odpowiedz/Przekaż) ---
function openCompose(mode) {
    initComposer(); 
    const modal = document.getElementById('compose-modal');
    const inputTo = document.getElementById('compose-to');
    const inputSubject = document.getElementById('compose-subject');
    const title = document.getElementById('compose-title');
    
    composeQuill.root.innerHTML = '';
    
    if(mode === 'new') {
        title.innerText = 'Nowa Wiadomość';
        inputTo.value = '';
        inputSubject.value = '';
    } 
    else if (mode === 'reply') {
        title.innerText = 'Odpowiedz';
        inputTo.value = currentEmailData.pureEmail;
        inputSubject.value = currentEmailData.subject.startsWith('Re:') ? currentEmailData.subject : 'Re: ' + currentEmailData.subject;
        
        const historyBlock = `<br><br><br><blockquote><p><strong>W dniu ${currentEmailData.date}, ${escapeHTML(currentEmailData.from)} napisał(a):</strong></p>${currentEmailData.rawBody}</blockquote>`;
        composeQuill.clipboard.dangerouslyPasteHTML(0, "<p><br></p>" + historyBlock);
        
    } 
    else if (mode === 'forward') {
        title.innerText = 'Przekaż Wiadomość';
        inputTo.value = '';
        inputSubject.value = currentEmailData.subject.startsWith('Fwd:') ? currentEmailData.subject : 'Fwd: ' + currentEmailData.subject;
        
        const historyBlock = `<br><br><br><blockquote><p><strong>Przekazana wiadomość:</strong><br>Od: ${escapeHTML(currentEmailData.from)}<br>Data: ${currentEmailData.date}<br>Temat: ${escapeHTML(currentEmailData.subject)}</p><hr>${currentEmailData.rawBody}</blockquote>`;
        composeQuill.clipboard.dangerouslyPasteHTML(0, "<p><br></p>" + historyBlock);
    }
    
    document.getElementById('email-modal').classList.add('hidden');
    modal.classList.remove('hidden');
    
    setTimeout(() => { 
        composeQuill.setSelection(0, 0); // Ustaw kursor na start
        document.querySelector('.quill-wrapper .ql-editor').scrollTop = 0; // Przewiń widok na początek
    }, 100);
}

// --- WYSYŁKA MAILA Z ZAPISEM W BAZIE ---
function sendDirectEmail() {
    const btn = document.getElementById('btn-send-direct');
    const to = document.getElementById('compose-to').value.trim();
    const subject = document.getElementById('compose-subject').value.trim();
    const body = composeQuill.root.innerHTML;

    if(!to || !subject) {
        alert("Podaj odbiorcę i temat wiadomości!");
        return;
    }

    btn.innerHTML = '⏳ Wysyłanie...';
    btn.disabled = true;

    fetch('/admin/email/send-direct', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ to, subject, body })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            btn.innerHTML = '✅ Wysłano!';
            btn.classList.replace('bg-blue-600', 'bg-green-600');
            setTimeout(() => {
                document.getElementById('compose-modal').classList.add('hidden');
                btn.innerHTML = '<span>🚀</span> Wyślij';
                btn.classList.replace('bg-green-600', 'bg-blue-600');
                btn.disabled = false;
                
                // Odświeżamy historię jeśli jesteśmy na karcie Wysłane
                if(currentTab === 'sent') loadSentEmails();
            }, 1500);
        } else {
            alert("Błąd: " + data.message);
            btn.innerHTML = '<span>🚀</span> Wyślij';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert("Błąd połączenia. Spróbuj ponownie.");
        btn.innerHTML = '<span>🚀</span> Wyślij';
        btn.disabled = false;
    });
}


// --- POBIERANIE MAILI Z IMAP ---
function loadEmails(force = false) {
    const tbody = document.getElementById('imap-tbody');
    const icon = document.getElementById('icon-refresh');
    const badge = document.getElementById('cache-badge');

    icon.classList.add('animate-spin');
    tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"></div><span class="font-bold">${force ? 'Pobieranie z serwera...' : 'Wczytywanie...'}</span></div></td></tr>`;

    fetch('/admin/email/fetch-imap' + (force ? '?force=1' : ''))
        .then(response => response.json())
        .then(data => {
            icon.classList.remove('animate-spin');
            if (data.status === 'error') {
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500 bg-red-50"><span class="font-bold">⚠️ ${escapeHTML(data.message)}</span></td></tr>`;
                updateLogs(data.log);
                return;
            }

            if (data.source === 'cache') badge.classList.remove('hidden');
            else badge.classList.add('hidden');

            if (data.emails.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 font-bold">Skrzynka jest pusta.</td></tr>';
            } else {
                let html = '';
                data.emails.forEach((email, index) => {
                    const bgClass = index === 0 ? 'bg-blue-50/30 font-medium' : '';
                    html += `
                    <tr class="hover:bg-gray-100 transition cursor-pointer email-row ${bgClass}" 
                        onclick="openEmail('${email.id}', '${escapeHTML(email.subject)}', '${escapeHTML(email.from)}', '${escapeHTML(email.date)}')">
                        <td class="px-6 py-4 text-gray-500 text-xs ">${escapeHTML(email.date)}</td>
                        <td class="px-6 py-4 text-gray-800 ">${escapeHTML(email.from)}</td>
                        <td class="px-6 py-4 text-gray-800 ">${escapeHTML(email.subject)}</td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            }
            document.getElementById('imap-count').innerText = data.emails.length;
            updateLogs(data.log);
        })
        .catch(() => {
            icon.classList.remove('animate-spin');
            tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500 font-bold">Wystąpił błąd komunikacji z serwerem.</td></tr>`;
        });
}

// --- POBIERANIE WYSŁANYCH Z BAZY DANYCH ---
function loadSentEmails() {
    const tbody = document.getElementById('imap-tbody');
    const icon = document.getElementById('icon-refresh');
    icon.classList.add('animate-spin');

    tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-400 mb-3"></div><span class="font-bold">Wczytywanie historii...</span></div></td></tr>`;

    fetch('/admin/email/fetch-sent')
        .then(response => response.json())
        .then(data => {
            icon.classList.remove('animate-spin');
            if (data.status === 'success') {
                if (data.emails.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 font-bold">Historia jest pusta. Wyślij pierwszą wiadomość.</td></tr>';
                    return;
                }
                
                window.sentEmailsData = data.emails; // Zapisujemy w Cache lokalnym, żeby nie pobierać body jeszcze raz
                
                let html = '';
                data.emails.forEach((email) => {
                    html += `
                    <tr class="hover:bg-gray-100 transition cursor-pointer email-row" 
                        onclick="openSentEmail(${email.id})">
                        <td class="px-6 py-4 text-gray-500 text-xs ">${escapeHTML(email.date)}</td>
                        <td class="px-6 py-4 text-gray-800 font-bold">${escapeHTML(email.from)}</td>
                        <td class="px-6 py-4 text-gray-800 ">${escapeHTML(email.subject)}</td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            }
        })
        .catch(() => {
            icon.classList.remove('animate-spin');
            tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500 font-bold">Błąd podczas wczytywania historii.</td></tr>`;
        });
}

// --- OTWIERANIE MAILA Z LOKALNEJ BAZY DANYCH ---
function openSentEmail(id) {
    const email = window.sentEmailsData.find(e => e.id == id);
    if(!email) return;

    const modal = document.getElementById('email-modal');
    modal.classList.remove('hidden');
    document.getElementById('modal-loader').classList.add('hidden'); // Od razu ukrywamy, bo dane już mamy

    document.getElementById('modal-subject').innerText = email.subject;
    document.getElementById('modal-from-label').innerText = "Do:";
    document.getElementById('modal-from').innerText = email.from;
    document.getElementById('modal-date').innerText = email.date;
    
    document.getElementById('btn-reply').classList.add('hidden'); // Ukrywamy guzik odpowiedz dla wysłanych

    currentEmailData = { id, subject: email.subject, from: email.from, date: email.date, pureEmail: email.from, rawBody: email.body };
    
    // Treść z edytora Quill jest już w formacie HTML
    document.getElementById('modal-body').innerHTML = email.body;
}

function updateLogs(logArray) {
    const logContainer = document.getElementById('imap-debug-content');
    if (!logContainer) return;
    if (logArray && logArray.length > 0) logContainer.innerHTML = logArray.map(l => escapeHTML(l)).join('<br>');
    else logContainer.innerHTML = 'Brak zarejestrowanych logów.';
}

// --- POBIERANIE TREŚCI Z IMAP I GRUPOWANIE ("Zwijanie Cytatów") ---
function openEmail(id, subject, from, date) {
    const modal = document.getElementById('email-modal');
    modal.classList.remove('hidden');
    document.getElementById('modal-loader').classList.remove('hidden');
    document.getElementById('modal-body').innerHTML = '';

    document.getElementById('modal-subject').innerText = subject;
    document.getElementById('modal-from-label').innerText = "Od:";
    document.getElementById('modal-from').innerText = from;
    document.getElementById('modal-date').innerText = date;
    
    document.getElementById('btn-reply').classList.remove('hidden'); // Pokazujemy guzik odpowiedz

    const emailMatch = from.match(/<([^>]+)>/) || [null, from];
    currentEmailData = { id, subject, from, date, pureEmail: emailMatch[1].trim(), rawBody: '' };

    fetch('/admin/email/read?id=' + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modal-loader').classList.add('hidden');
            if(data.status === 'success') {
                currentEmailData.rawBody = data.body;
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(data.body, 'text/html');
                const quoteNodes = doc.querySelectorAll('blockquote, .gmail_quote, [type="cite"]');
                
                quoteNodes.forEach(quote => {
                    if(!quote.dataset.processed) {
                        const details = document.createElement('details');
                        details.className = "mt-6";
                        details.innerHTML = `
                            <summary class="text-xs font-bold text-gray-500 cursor-pointer bg-gray-100 hover:bg-gray-200 inline-block px-4 py-1.5 rounded-full transition outline-none select-none border border-gray-200">
                                💬 Pokaż cytowaną historię (...)
                            </summary>
                            <div class="mt-4 opacity-80 border-l-4 border-gray-200 pl-4 overflow-x-auto text-sm quote-wrapper"></div>
                        `;
                        quote.parentNode.insertBefore(details, quote);
                        details.querySelector('.quote-wrapper').appendChild(quote);
                        quote.dataset.processed = "true";
                    }
                });

                document.getElementById('modal-body').innerHTML = doc.body.innerHTML;
            } else {
                document.getElementById('modal-body').innerHTML = `<div class="p-4 bg-red-50 text-red-600 font-bold rounded border border-red-200">${escapeHTML(data.message)}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('modal-loader').classList.add('hidden');
            document.getElementById('modal-body').innerHTML = '<div class="text-red-500 font-bold">Błąd komunikacji podczas pobierania treści.</div>';
        });
}

document.addEventListener('DOMContentLoaded', () => { loadEmails(false); });
</script>