
<?php
if (!defined('ABSPATH')) exit;

// Hole Crawl-Ziele aus WP-Optionen
function nova_ai_get_custom_targets() {
    $default = [
        'https://wiki.ubuntuusers.de/',
        'https://wiki.archlinux.org/',
        'https://ss64.com/osx/',
        'https://ss64.com/nt/',
        'https://wiki.termux.com/wiki/Main_Page',
        'https://www.freebsd.org/doc/',
        'https://man.openbsd.org/',
        'https://itsfoss.com/linux-commands/'
    ];
    $urls = get_option('nova_ai_crawl_urls', implode("\n", $default));
    return array_filter(array_map('trim', explode("\n", $urls)));
}

function nova_ai_run_crawler() {
    $targets = nova_ai_get_custom_targets();
    $results = [];

    foreach ($targets as $url) {
        $res = wp_remote_get($url);
        if (is_wp_error($res)) continue;
        $text = wp_strip_all_tags(wp_remote_retrieve_body($res));
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $results[] = [
            'url' => $url,
            'content' => mb_substr($text, 0, 5000)
        ];
    }

    $filename = plugin_dir_path(__FILE__) . '../data/knowledge/general/web-' . date('Y-m-d_H-i-s') . '.json';
    if (!file_exists(dirname($filename))) {
        mkdir(dirname($filename), 0777, true);
    }
    file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $filename;
}
?>
