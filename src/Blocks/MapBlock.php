<?php
namespace CMS\Blocks;

class MapBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $lat = $data['lat'] ?? '52.2297';
        $lng = $data['lng'] ?? '21.0122';
        $zoom = $data['zoom'] ?? '13';
        $tooltip = htmlspecialchars($data['tooltip'] ?? '');
        $mapId = 'map_' . uniqid();
        
        $html = '<div class="mb-8 w-full h-[400px] rounded-xl shadow-lg border border-gray-200 z-10" id="'.$mapId.'"></div>';
        $html .= "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('{$mapId}').setView([{$lat}, {$lng}], {$zoom});
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([{$lat}, {$lng}]).addTo(map).bindPopup('{$tooltip}').openPopup();
        });
        </script>";
        return $html;
    }
}