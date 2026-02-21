<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-6xl mx-auto bg-white p-8 rounded shadow">
        
        <div class="flex justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Gallery</h1>
            <a href="/admin/galleries" class="text-gray-500">Back</a>
        </div>

        <div class="flex gap-4 mb-6">
            <input type="text" id="title" value="<?= htmlspecialchars($gallery['title']) ?>" class="border p-2 rounded w-1/2" placeholder="Gallery Title">
            <select id="type" class="border p-2 rounded w-1/4">
                <option value="grid" <?= $gallery['type']=='grid'?'selected':'' ?>>Grid Layout</option>
                <option value="slider" <?= $gallery['type']=='slider'?'selected':'' ?>>Slider/Swipe</option>
            </select>
            <button onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-4 py-2 rounded font-bold w-1/4">+ Add Images</button>
            <input type="file" id="fileInput" multiple accept="image/*" class="hidden">
        </div>

        <div id="image-grid" class="grid grid-cols-4 gap-4">
            </div>

        <button onclick="saveGallery()" class="mt-8 bg-green-600 text-white px-6 py-3 rounded font-bold w-full">Save Gallery</button>
    </div>

    <script>
        const galleryId = <?= $gallery['id'] ?>;
        const savedImages = <?= $gallery['images_json'] ?: '[]' ?>;
        const grid = document.getElementById('image-grid');

        // Init
        Sortable.create(grid, { animation: 150 });
        savedImages.forEach(url => renderImage(url));

        // Upload Logic
        document.getElementById('fileInput').addEventListener('change', function() {
            const formData = new FormData();
            for (let i = 0; i < this.files.length; i++) {
                formData.append('file', this.files[i]); // We reuse the single-file endpoint, ideally update controller to handle array
                // For simplicity, we loop fetch requests here (simple solution)
                uploadOne(this.files[i]);
            }
        });

        function uploadOne(file) {
            const formData = new FormData();
            formData.append('file', file);
            
            fetch('/admin/media/upload', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.url) renderImage(data.url);
            });
        }

        function renderImage(url) {
            const div = document.createElement('div');
            div.className = "relative group aspect-square bg-gray-100 rounded overflow-hidden cursor-move";
            div.innerHTML = `
                <img src="${url}" class="w-full h-full object-cover">
                <button onclick="this.closest('div').remove()" class="absolute top-1 right-1 bg-red-600 text-white w-6 h-6 rounded-full opacity-0 group-hover:opacity-100 transition">✕</button>
                <input type="hidden" class="img-url" value="${url}">
            `;
            grid.appendChild(div);
        }

        function saveGallery() {
            const images = [];
            grid.querySelectorAll('.img-url').forEach(input => images.push(input.value));

            fetch('/admin/galleries/save', {
                method: 'POST',
                body: JSON.stringify({
                    id: galleryId,
                    title: document.getElementById('title').value,
                    type: document.getElementById('type').value,
                    images: images
                })
            }).then(() => alert('Gallery Saved!'));
        }
    </script>
</body>
</html>