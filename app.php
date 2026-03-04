<?php

$url = getenv('API_URL');

if (!$url) {
    exit;
}

$content = file_get_contents($url);
$lines = explode("\n", $content);

$hls_data = [];
$drm_data = [];
$cookie_data = [];
$current_channel = [];

$hls_counter = 1;
$drm_counter = 1;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if (strpos($line, '#EXTINF:') === 0) {
        preg_match('/tvg-logo="(.*?)"/', $line, $logo_match);
        $current_channel['logo'] = $logo_match[1] ?? '';
        $parts = explode(',', $line);
        $current_channel['name'] = end($parts);
    } elseif (strpos($line, '#EXTHTTP:') === 0) {
        $json_str = substr($line, 9);
        $http_headers = json_decode($json_str, true);
        if ($http_headers !== null) {
            $cookie_data = $http_headers; 
        }
    } elseif (strpos($line, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
        $current_channel['key'] = substr($line, 45);
    } elseif ($line[0] !== '#') {
        $current_channel['url'] = $line;

        if (strpos($line, '.mpd') !== false || isset($current_channel['key'])) {
            $drm_data[] = [
                'id' => sprintf('%03d', $drm_counter),
                'name' => $current_channel['name'],
                'logo' => $current_channel['logo'],
                'url' => $current_channel['url'],
                'key' => $current_channel['key'] ?? ''
            ];
            $drm_counter++;
        } else {
            $hls_data[] = [
                'id' => sprintf('%03d', $hls_counter),
                'name' => $current_channel['name'],
                'logo' => $current_channel['logo'],
                'url' => $current_channel['url']
            ];
            $hls_counter++;
        }
        $current_channel = [];
    }
}

file_put_contents('cookie.json', json_encode($cookie_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents('hls.json', json_encode($hls_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents('drm.json', json_encode($drm_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

?>
