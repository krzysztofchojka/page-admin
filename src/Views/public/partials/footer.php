<?php if (($settings['hide_footer'] ?? 0) != 1): ?>
    <?php if (!empty($footerBlocks)): ?>
        <footer class="mt-auto border-gray-200">
            <div class="w-full mx-auto pb-4 pt-10">
                <?php 
                // Używamy Helpera, jeśli wdrożyłeś Zadanie 1
                \CMS\Helpers\BlockRenderer::render($footerBlocks, $db); 
                ?>
            </div>
        </footer>
    <?php else: ?>
        <footer class="bg-gray-100 text-center p-6 text-gray-500 text-sm mt-auto border-t">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_title'] ?? 'Footer Placeholder') ?>.
        </footer>
    <?php endif; ?>
<?php endif; ?>

<script>
    // Karuzela JS - jawnie przypięta do globalnego obiektu window
    window.showCarouselTab = function(cid, index) {
        document.querySelectorAll('.c-btn-' + cid).forEach(btn => {
            if (parseInt(btn.dataset.index) === index) {
                btn.classList.remove('bg-blue-700', 'hover:bg-blue-800');
                btn.classList.add('bg-blue-900', 'scale-105');
            } else {
                btn.classList.add('bg-blue-700', 'hover:bg-blue-800');
                btn.classList.remove('bg-blue-900', 'scale-105');
            }
        });
        document.querySelectorAll('.c-content-' + cid).forEach(content => {
            if (parseInt(content.dataset.index) === index) {
                content.classList.remove('hidden');
                content.classList.add('block');
            } else {
                content.classList.remove('block');
                content.classList.add('hidden');
            }
        });
        // Zapamiętywanie wyboru w przeglądarce
        localStorage.setItem('active_tab_' + cid, index);
    };

    window.moveCarousel = function(cid, direction) {
        const contents = document.querySelectorAll('.c-content-' + cid);
        let currentIndex = 0;
        contents.forEach(el => {
            if (!el.classList.contains('hidden')) {
                currentIndex = parseInt(el.dataset.index);
            }
        });
        let newIndex = currentIndex + direction;
        if (newIndex < 0) newIndex = contents.length - 1;
        if (newIndex >= contents.length) newIndex = 0;
        window.showCarouselTab(cid, newIndex);
    };

    // Przywracanie stanu karuzeli po przeładowaniu strony
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.carousel-wrapper').forEach(wrapper => {
            const cid = wrapper.id;
            const savedIndex = localStorage.getItem('active_tab_' + cid);
            if (savedIndex !== null) {
                window.showCarouselTab(cid, parseInt(savedIndex));
            }
        });
    });

    // Mobilne Menu Burger
    document.getElementById('burger-btn').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
        menu.classList.toggle('flex');
    });
</script>
</body>
</html>