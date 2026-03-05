<?php
namespace CMS\Blocks;

class PostsGridBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $limit = (int)($data['limit'] ?? 6);
        $categoryId = $data['category'] ?? '';

        $sql = "SELECT p.*, c.name as cat_name FROM pa_posts p LEFT JOIN pa_post_categories c ON p.category_id = c.id WHERE p.status = 'published'";
        $params = [];
        if (!empty($categoryId)) {
            $sql .= " AND p.category_id = :cid";
            $params['cid'] = $categoryId;
        }
        $sql .= " ORDER BY p.created_at DESC LIMIT " . $limit;
        
        $posts = $db->query($sql, $params)->fetchAll();

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-10">';
        foreach ($posts as $post) {
            $postUrl = '/post?slug=' . htmlspecialchars($post['slug']);
            $html .= '<article class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col h-full border border-gray-100 group">';
            
            if (!empty($post['thumbnail'])) {
                $html .= '<a href="'.$postUrl.'" class="block h-48 overflow-hidden relative">';
                if (!empty($post['cat_name'])) {
                    $html .= '<span class="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full z-10">'.htmlspecialchars($post['cat_name']).'</span>';
                }
                $html .= '<img src="'.htmlspecialchars($post['thumbnail']).'" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Okładka">';
                $html .= '</a>';
            }
            
            $html .= '<div class="p-6 flex-1 flex flex-col">';
            $html .= '<span class="text-xs text-gray-400 mb-2 font-medium">'.date('d.m.Y', strtotime($post['created_at'])).'</span>';
            $html .= '<h3 class="text-xl font-bold text-gray-800 mb-3 leading-tight group-hover:text-blue-600 transition-colors"><a href="'.$postUrl.'">'.htmlspecialchars($post['title']).'</a></h3>';
            $html .= '<p class="text-gray-600 text-sm mb-6 flex-1 line-clamp-3">'.htmlspecialchars($post['excerpt']).'</p>';
            
            if (!empty($post['tags'])) {
                $tags = explode(',', $post['tags']);
                $html .= '<div class="flex flex-wrap gap-2 mb-4">';
                foreach ($tags as $tag) {
                    $html .= '<span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">#'.htmlspecialchars(trim($tag)).'</span>';
                }
                $html .= '</div>';
            }
            $html .= '<a href="'.$postUrl.'" class="text-blue-600 font-bold text-sm uppercase tracking-wide hover:underline mt-auto flex items-center gap-1">Czytaj dalej <span class="text-lg leading-none">→</span></a>';
            $html .= '</div></article>';
        }
        $html .= '</div>';

        if (count($posts) >= $limit) {
            $html .= '<div class="text-center mt-4"><a href="/blog" class="inline-block border-2 border-gray-300 text-gray-600 font-bold py-3 px-8 rounded-full hover:border-blue-600 hover:text-blue-600 transition">Zobacz wszystkie wpisy</a></div>';
        }
        return $html;
    }
}