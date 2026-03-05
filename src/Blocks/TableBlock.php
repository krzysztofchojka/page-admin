<?php
namespace CMS\Blocks;

class TableBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $raw = $block['content'] ?? '';
        if (!$raw) return '';

        $rows = explode("\n", trim($raw));
        $html = '<div class="overflow-x-auto mb-8 bg-white rounded-xl shadow-sm border border-gray-200">';
        $html .= '<table class="min-w-full text-left text-sm">';
        
        foreach ($rows as $index => $row) {
            $cells = explode("\t", $row);
            if ($index === 0) {
                $html .= '<thead class="bg-gray-50 border-b"><tr>';
                foreach ($cells as $cell) $html .= '<th class="px-6 py-4 font-bold text-gray-700">'.htmlspecialchars($cell).'</th>';
                $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';
            } else {
                $html .= '<tr class="hover:bg-gray-50 transition">';
                foreach ($cells as $cell) $html .= '<td class="px-6 py-4 text-gray-600">'.htmlspecialchars($cell).'</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}