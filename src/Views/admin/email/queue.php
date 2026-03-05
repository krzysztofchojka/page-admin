<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Kolejka Wysyłkowa</h1>
    </div>
    
    <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 font-bold shadow-sm">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <h2 class="font-bold mb-4">Zaplanuj nową wysyłkę</h2>
        <form action="/admin/email/schedule" method="POST" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
        <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Szablon</label>
                <select name="template_id" class="w-full border p-2 rounded bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <?php 
                    $tpls = \CMS\Core\Database::getInstance()->query("SELECT id, title FROM pa_email_templates")->fetchAll();
                    foreach($tpls as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lista odbiorców</label>
                <select name="list_id" class="w-full border p-2 rounded bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Puste (Wpisz ręcznie obok) --</option>
                    <?php 
                    $lsts = \CMS\Core\Database::getInstance()->query("SELECT id, name FROM pa_mailing_lists")->fetchAll();
                    foreach($lsts as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Emaile wpisane z palca</label>
                <input type="text" name="custom_emails" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" placeholder="jan@wp.pl, anna@o2.pl">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded transition shadow w-full md:w-auto">
                Kolejkuj
            </button>
        </form>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs w-16">ID</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Szablon (Treść)</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Adresaci</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Status / Wynik</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs text-right">Akcja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($queue as $q): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-gray-500 font-bold">#<?= $q['id'] ?></td>
                        <td class="px-6 py-4 text-gray-800 font-bold">
                            <?= htmlspecialchars($q['template_title']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if($q['list_name']): ?>
                                <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md inline-block mb-1 shadow-sm">
                                    📂 <?= htmlspecialchars($q['list_name']) ?>
                                </span><br>
                            <?php endif; ?>
                            <?php if($q['custom_emails']): ?>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200 break-words max-w-[200px] inline-block">
                                    <?= htmlspecialchars($q['custom_emails']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if(!$q['list_name'] && !$q['custom_emails']): ?>
                                <span class="text-xs text-red-500 font-bold">Brak odbiorców!</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if($q['status'] == 'pending'): ?>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold uppercase border border-yellow-200 shadow-sm">Oczekuje</span>
                            <?php elseif($q['status'] == 'processing'): ?>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold uppercase">Przetwarzanie...</span>
                            <?php elseif($q['status'] == 'completed'): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold uppercase block mb-1 w-max border border-green-200 shadow-sm">Zakończono</span>
                                <div class="text-[11px] text-gray-600 font-medium">
                                    Dostarczone: <b class="text-green-600 text-xs"><?= $q['sent_count'] ?></b><br>
                                    Błędy: <b class="text-red-500 text-xs"><?= $q['failed_count'] ?></b>
                                </div>
                            <?php else: ?>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold uppercase">Błąd Wysyłki</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center gap-2 justify-end">
                                <?php if($q['status'] == 'pending'): ?>
                                    <a href="/admin/email/trigger?id=<?= $q['id'] ?>" class="text-white bg-green-500 hover:bg-green-600 font-bold px-4 py-2 rounded shadow transition text-xs flex items-center gap-1">
                                        <span>🚀</span> Wyślij
                                    </a>
                                <?php else: ?>
                                    <a href="/admin/email/trigger?id=<?= $q['id'] ?>&resend=1" onclick="return confirm('Kopia zostanie dodana do kolejki. Kontynuować?')" class="text-gray-700 bg-gray-100 border border-gray-300 hover:bg-gray-200 font-bold px-3 py-1.5 rounded shadow-sm transition text-xs flex items-center gap-1">
                                        <span>🔄</span> Ponów
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($queue)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Brak zaplanowanych wysyłek.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>