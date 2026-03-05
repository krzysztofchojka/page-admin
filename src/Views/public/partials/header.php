<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? ($page['title'] ?? 'CMS')) ?></title>
    
    <?php if (!empty($settings['meta_description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description']) ?>">
    <?php endif; ?>
    
    <?php if (!empty($settings['site_favicon'])): ?>
    <link rel="icon" href="<?= htmlspecialchars($settings['site_favicon']) ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?= htmlspecialchars($settings['color_primary'] ?? '#f97316') ?>',
                        secondary: '<?= htmlspecialchars($settings['color_secondary'] ?? '#1e3a8a') ?>',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        nav { position: sticky !important; top: 0 !important; z-index: 201 !important; }
        main { padding-top: 0 !important; }
        
        /* Widget Lotów - Awaryjne style na twardo */
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
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>

    <?= $settings['custom_head'] ?? '' ?>
</head>
<body class="bg-white text-gray-800 flex flex-col min-h-screen overflow-x-hidden">