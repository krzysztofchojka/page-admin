<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Panel Email & SMS</h1>
        <div class="flex gap-2">
            <!--a href="/admin/email/templates/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition">
                + Utwórz Szablon
            </a-->
            <a href="/admin/email/templates" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded shadow transition">📋 Szablony</a>
            <a href="/admin/settings" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded shadow transition">
                ⚙️ Ustawienia IMAP/SMTP
            </a>
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

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden mb-8 relative">
        <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-gray-700 text-lg">Ostatnie wiadomości (Inbox)</h2>
                <span id="cache-badge" class="hidden bg-gray-200 text-gray-600 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Wersja z Cache</span>
            </div>
            <button onclick="loadEmails(true)" id="btn-refresh" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm">
                <span id="icon-refresh">🔄</span> Odśwież skrzynkę
            </button>
        </div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs w-32">Data</th>
                    <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Nadawca</th>
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
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            
            <div class="bg-gray-50 border-b p-4 sm:p-6 flex flex-col gap-2 relative">
                <button onclick="document.getElementById('email-modal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl font-bold transition">&times;</button>
                <h2 id="modal-subject" class="text-xl sm:text-2xl font-bold text-gray-800 pr-8 leading-tight">Ładowanie...</h2>
                <div class="flex flex-wrap justify-between items-center text-sm mt-2">
                    <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-full"><span class="font-bold text-gray-800">Od:</span> <span id="modal-from">...</span></span>
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
                <div class="flex gap-2 w-full sm:w-auto">
                    <a id="btn-forward" href="#" class="flex-1 sm:flex-none text-center px-6 py-2 border border-blue-200 text-blue-700 bg-blue-50 font-bold rounded-lg hover:bg-blue-100 transition shadow-sm">
                        ➡️ Przekaż
                    </a>
                    <a id="btn-reply" href="#" class="flex-1 sm:flex-none text-center px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-md">
                        ↩️ Odpowiedz
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
    // Bezpieczne wstawianie danych do HTML
    const escapeHTML = (str) => {
        return (str || '').toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    };

    function loadEmails(force = false) {
        const tbody = document.getElementById('imap-tbody');
        const icon = document.getElementById('icon-refresh');
        const badge = document.getElementById('cache-badge');
        
        icon.classList.add('animate-spin');
        
        if (force) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"></div><span class="font-bold">Pobieranie wiadomości z serwera...</span></div></td></tr>';
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center"><div class="animate-pulse rounded-full h-8 w-8 bg-gray-200 mb-3"></div><span class="font-bold">Wczytywanie...</span></div></td></tr>';
        }

        const url = '/admin/email/fetch-imap' + (force ? '?force=1' : '');

        fetch(url)
            .then(response => response.json())
            .then(data => {
                icon.classList.remove('animate-spin');
                
                // Obsługa błędu połączenia
                if (data.status === 'error') {
                    tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500 bg-red-50"><span class="font-bold">⚠️ ${escapeHTML(data.message)}</span></td></tr>`;
                    updateLogs(data.log);
                    return;
                }

                // Plakietka "Wersja z Cache"
                if (data.source === 'cache') {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                // Renderowanie tabeli
                // Wewnątrz funkcji loadEmails(force), w sekcji .then(data => { ... })
// Zamień fragment odpowiedzialny za renderowanie tabeli na ten:

if (data.emails.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 font-bold">Skrzynka jest pusta.</td></tr>';
} else {
    let html = '';
    data.emails.forEach((email, index) => {
        const bgClass = index === 0 ? 'bg-blue-50/30 font-medium' : '';
        html += `
            <tr class="hover:bg-gray-100 transition cursor-pointer email-row ${bgClass}" 
                data-id="${email.id}"
                data-subject="${escapeHTML(email.subject)}"
                data-from="${escapeHTML(email.from)}"
                data-date="${escapeHTML(email.date)}">
                <td class="px-6 py-4 text-gray-500 text-xs ">${escapeHTML(email.date)}</td>
                <td class="px-6 py-4 text-gray-800 ">${escapeHTML(email.from)}</td>
                <td class="px-6 py-4 text-gray-800 ">${escapeHTML(email.subject)}</td>
            </tr>
        `;
    });
    
    // 1. Wstawiamy HTML do tabeli
    tbody.innerHTML = html;

    // 2. Szukamy wszystkich nowo dodanych wierszy i nadajemy im onclick
    const rows = tbody.querySelectorAll('.email-row');
    console.log(rows)
    rows.forEach(row => {
        row.onclick = function() {
            const id = this.getAttribute('data-id');
            const subject = this.getAttribute('data-subject');
            const from = this.getAttribute('data-from');
            const date = this.getAttribute('data-date');
            
            openEmail(id, subject, from, date);
        };
    });
}
                
                document.getElementById('imap-count').innerText = data.emails.length;
                updateLogs(data.log);
            })
            .catch(err => {
                icon.classList.remove('animate-spin');
                tbody.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-red-500 font-bold">Wystąpił błąd komunikacji z serwerem.</td></tr>`;
            });
    }

    function updateLogs(logArray) {
        const logContainer = document.getElementById('imap-debug-content');
        if (!logContainer) return;
        if (logArray && logArray.length > 0) {
            logContainer.innerHTML = logArray.map(l => escapeHTML(l)).join('<br>');
        } else {
            logContainer.innerHTML = 'Brak zarejestrowanych logów.';
        }
    }

    // Obsługa otwierania i ładowania pełnej wiadomości
    function openEmail(id, subject, from, date) {
        // Pokaż modal i loader
        const modal = document.getElementById('email-modal');
        modal.classList.remove('hidden');
        document.getElementById('modal-loader').classList.remove('hidden');
        document.getElementById('modal-body').innerHTML = '';
        
        // Wypełnij nagłówki
        document.getElementById('modal-subject').innerText = subject;
        document.getElementById('modal-from').innerText = from;
        document.getElementById('modal-date').innerText = date;

        // Skonfiguruj przyciski Reply/Forward (otwierają domyślnego klienta poczty lub webmaila)
        // Ekstrakcja czystego adresu email (np. z "Jan Kowalski <jan@wp.pl>" -> "jan@wp.pl")
        const emailMatch = from.match(/<([^>]+)>/) || [null, from];
        const pureEmail = emailMatch[1];
        
        document.getElementById('btn-reply').href = `mailto:${pureEmail}?subject=Re: ${encodeURIComponent(subject)}`;
        document.getElementById('btn-forward').href = `mailto:?subject=Fwd: ${encodeURIComponent(subject)}`;

        // Pobierz treść
        fetch('/admin/email/read?id=' + id)
            .then(res => res.json())
            .then(data => {
                document.getElementById('modal-loader').classList.add('hidden');
                if(data.status === 'success') {
                    // UWAGA: Renderujemy HTML bez escapeHTML aby zachować formatowanie!
                    document.getElementById('modal-body').innerHTML = data.body;
                } else {
                    document.getElementById('modal-body').innerHTML = `<div class="p-4 bg-red-50 text-red-600 font-bold rounded border border-red-200">${escapeHTML(data.message)}</div>`;
                }
            })
            .catch(err => {
                document.getElementById('modal-loader').classList.add('hidden');
                document.getElementById('modal-body').innerHTML = '<div class="text-red-500 font-bold">Błąd komunikacji podczas pobierania treści.</div>';
            });
    }

    // Pobierz wiadomości natychmiast po załadowaniu szkieletu strony
    // DODAJ NA DOLE SKRYPTU
document.addEventListener('DOMContentLoaded', () => {
    // Uruchamiamy pierwsze pobranie maili
    loadEmails(false);
});
</script>