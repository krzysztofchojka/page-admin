<div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow mt-10 border-t-4 border-blue-600">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Kreator Nowej Strony</h1>
    
    <form action="/admin/pages/create" method="POST">
        <div class="mb-5">
            <label class="block font-bold text-gray-700 mb-2 text-sm">Tytuł strony</label>
            <input type="text" name="title" required class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none" placeholder="np. O nas">
        </div>
        
        <div class="mb-6 bg-blue-50 p-4 rounded border border-blue-100">
            <label class="block font-bold text-gray-800 mb-2 text-sm">Szablon układu (Layout)</label>
            <select name="template_id" class="w-full border p-2.5 rounded shadow-sm outline-none">
                <option value="">-- Pusta strona (Domyślny) --</option>
                <?php foreach($templates as $tpl): ?>
                    <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-red-500 mt-2 font-bold uppercase tracking-wide">Uwaga: Szablon można wybrać tylko raz przy tworzeniu!</p>
        </div>
        
        <div class="flex items-center gap-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded shadow transition">Utwórz i przejdź do edytora</button>
            <a href="/admin/pages" class="text-gray-500 hover:text-gray-800 font-bold text-sm">Anuluj</a>
        </div>
    </form>
</div>