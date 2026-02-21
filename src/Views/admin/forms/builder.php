<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen">

    <div class="w-64 bg-white border-r p-4 flex flex-col gap-2">
        <h2 class="font-bold mb-4">Form Fields</h2>
        <div class="draggable-item bg-gray-50 border p-2 rounded cursor-move hover:bg-blue-50" data-type="text">
            Text Input
        </div>
        <div class="draggable-item bg-gray-50 border p-2 rounded cursor-move hover:bg-blue-50" data-type="email">
            Email Address
        </div>
        <div class="draggable-item bg-gray-50 border p-2 rounded cursor-move hover:bg-blue-50" data-type="file">
            File Upload (Encrypted)
        </div>
        <div class="mt-8 border-t pt-4">
    <h3 class="font-bold mb-3 text-sm text-gray-700">Form Settings</h3>
    <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
        <input type="checkbox" id="reqLogin" class="rounded text-blue-600"> Require Login
    </label>
    <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
        <input type="checkbox" id="fillOnce" class="rounded text-blue-600"> Fill Only Once
    </label>
    <label class="flex items-center gap-2 text-sm mb-4 cursor-pointer">
        <input type="checkbox" id="editable" class="rounded text-blue-600"> Editable after submit
    </label>
</div>
        <div class="mt-auto">
            <button onclick="saveForm()" class="w-full bg-green-600 text-white py-2 rounded font-bold">Save Form</button>
            <a href="/admin/forms" class="block text-center mt-2 text-gray-500">Cancel</a>
        </div>
    </div>
    

    <div class="flex-1 p-8 overflow-auto">
        <input type="text" id="formTitle" value="<?= htmlspecialchars($form['title']) ?>" class="text-3xl font-bold bg-transparent border-none mb-6 w-full focus:outline-none" placeholder="Form Title">
        
        <div id="form-canvas" class="grid grid-cols-1 md:grid-cols-3 gap-4 min-h-[500px] border-2 border-dashed border-gray-300 p-8 bg-white rounded-lg">
            </div>
    </div>

    <script>
        const formId = <?= $form['id'] ?>;
        // Load Layout from DB
        const savedFields = <?= $form['form_json'] ?: '[]' ?>;
        const canvas = document.getElementById('form-canvas');
        const savedSettings = <?= $form['settings'] ?: '{}' ?>;
        document.getElementById('reqLogin').checked = savedSettings.reqLogin || false;
        document.getElementById('fillOnce').checked = savedSettings.fillOnce || false;
        document.getElementById('editable').checked = savedSettings.editable || false;

        // Initialize Sortable
        Sortable.create(canvas, {
            animation: 150,
            ghostClass: 'bg-blue-100'
        });

        // Initial Load
        savedFields.forEach(field => addFieldToCanvas(field));

        // Sidebar logic to add fields
        document.querySelectorAll('.draggable-item').forEach(item => {
            item.addEventListener('click', () => {
                addFieldToCanvas({ type: item.dataset.type, label: 'New Field', width: 'full' });
            });
        });

        function addFieldToCanvas(data) {
    const div = document.createElement('div');
    div.className = "field-card relative border p-4 rounded bg-gray-50 hover:shadow-md transition-all group col-span-1";
    updateWidthClasses(div, data.width);
    
    // Generowanie lub przypisanie unikalnego ID pola
    const fieldId = data.id || 'f_' + Math.random().toString(36).substr(2, 9);
    
    div.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-bold uppercase text-gray-500">${data.type}</span>
            <div class="flex gap-1">
                <button onclick="setWidth(this, 'third')" class="p-1 text-xs bg-gray-200 hover:bg-gray-300 rounded" title="1/3 Width">1/3</button>
                <button onclick="setWidth(this, 'full')" class="p-1 text-xs bg-gray-200 hover:bg-gray-300 rounded" title="Full Width">Full</button>
                <button onclick="this.closest('.field-card').remove()" class="p-1 text-xs text-red-500 hover:bg-red-100 rounded">✕</button>
            </div>
        </div>
        <input type="text" class="field-label w-full font-bold bg-transparent border-b border-gray-300 focus:border-blue-500 mb-2" value="${data.label}">
        <div class="h-8 bg-white border rounded"></div>
    `;
    div.dataset.type = data.type;
    div.dataset.width = data.width || 'full';
    div.dataset.id = fieldId; // Zapisujemy unikalne ID
    canvas.appendChild(div);
}

        function setWidth(btn, width) {
            const card = btn.closest('.field-card');
            card.dataset.width = width;
            updateWidthClasses(card, width);
        }

        function updateWidthClasses(el, width) {
            // Remove existing width classes
            el.classList.remove('md:col-span-1', 'md:col-span-3');
            
            // Grid Logic: Mobile is always col-span-1 (handled by parent grid-cols-1)
            // Desktop (md) logic:
            if (width === 'third') el.classList.add('md:col-span-1');
            if (width === 'full') el.classList.add('md:col-span-3');
        }

        function saveForm() {
    const fields = [];
    canvas.querySelectorAll('.field-card').forEach(card => {
        fields.push({
            id: card.dataset.id,
            type: card.dataset.type,
            label: card.querySelector('.field-label').value,
            width: card.dataset.width
        });
    });

    const settings = {
        reqLogin: document.getElementById('reqLogin').checked,
        fillOnce: document.getElementById('fillOnce').checked,
        editable: document.getElementById('editable').checked
    };

    fetch('/admin/forms/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id: formId, 
            title: document.getElementById('formTitle').value, 
            fields: fields,
            settings: settings
        })
    })
    .then(res => res.json())
    .then(data => { if(data.status === 'success') alert('Form Saved!'); });
}
    </script>
</body>
</html>