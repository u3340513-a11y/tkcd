<?php
/**
 * Türkiye il GeoJSON endpoint'i.
 * Leaflet choropleth haritası için Natural Earth kaynaklı WGS84 GeoJSON döner.
 * Kimlik doğrulama gerektirmez — sadece statik coğrafi veri.
 */
declare(strict_types=1);

$geojson_path = dirname(__DIR__, 2) . '/public/assets/data/turkey-provinces.geojson';

if (!file_exists($geojson_path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'GeoJSON dosyası bulunamadı.']);
    exit;
}

// Cache headers — coğrafi veri nadiren değişir
$mtime   = filemtime($geojson_path);
$etag    = '"' . dechex($mtime) . '-' . dechex(filesize($geojson_path)) . '"';
$if_none = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');
header('ETag: ' . $etag);
header('Access-Control-Allow-Origin: *');

if ($if_none === $etag) {
    http_response_code(304);
    exit;
}

readfile($geojson_path);
