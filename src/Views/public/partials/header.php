<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? $page['title']) ?></title>
    
    <?php if (!empty($settings['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars($settings['meta_description']) ?>">
    <?php endif; ?>
    
    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['site_favicon']) ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --color-primary: <?= htmlspecialchars($settings['color_primary'] ?? '#f97316') ?>;
            --color-secondary: <?= htmlspecialchars($settings['color_secondary'] ?? '#1e3a8a') ?>;
        }
        nav{ position: sticky !important; top: 0 !important; z-index: 201 !important; }
        main{ padding-top: 0 !important; }
        /* Dynamiczne klasy w oparciu o zmienne CSS */
        .text-primary { color: var(--color-primary); }
        .bg-primary { background-color: var(--color-primary); }
        .bg-secondary { background-color: var(--color-secondary); }
        .border-primary { border-color: var(--color-primary); }
        .hover\:bg-primary:hover { background-color: var(--color-primary); }
        .hover\:text-white:hover { color: #ffffff; }
        
        /* Typografia dla edytora tekstu */
       /* .prose h1 { font-size: 2.25em; font-weight: 800; margin-bottom: 0.5em; }
        .prose h2 { font-size: 1.5em; font-weight: 700; margin-bottom: 0.5em; }
        .prose p { margin-bottom: 1em; line-height: 1.6; }
        .prose ul { list-style: disc; margin-left: 1.5em; }*/
        
        /* Przeloty CSS (Zoptymalizowane) */
        .przelot-widget { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 24px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .przelot-widget .header { display: flex; align-items: center; gap: 12px; font-weight: bold; margin-bottom: 16px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px; }
        .przelot-widget .header img.logo { height: 24px; object-fit: contain; }
        .przelot-widget .body { display: flex; align-items: stretch; gap: 16px; margin-bottom: 16px; }
        .przelot-widget .duration { font-size: 0.875rem; color: #4b5563; min-width: 60px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .przelot-widget .en-translation { display: block; font-size: 0.75rem; color: #9ca3af; font-weight: normal; margin-top: 2px; }
        .przelot-widget .graphic { display: flex; flex-direction: column; align-items: center; padding-top: 6px; }
        .przelot-widget .circle { width: 10px; height: 10px; border-radius: 50%; border: 2px solid #3b82f6; background: white; }
        .przelot-widget .line { width: 2px; background: #bfdbfe; flex-grow: 1; margin: 4px 0; }
        .przelot-widget .stops { font-weight: bold; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .przelot-widget .infobubble { background: #eff6ff; color: #1e40af; padding: 8px 12px; border-radius: 6px; text-align: center; font-size: 0.875rem; font-weight: bold; margin: 16px 0; border: 1px dashed #bfdbfe; }
        .przelot-widget .footer { border-top: 1px solid #f3f4f6; padding-top: 12px; font-size: 0.875rem; color: #374151; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>
    
    <?= $settings['custom_head'] ?? '' ?>
</head>
<body class="bg-white text-gray-800 flex flex-col min-h-screen overflow-x-hidden">