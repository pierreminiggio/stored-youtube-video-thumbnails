<?php

namespace App;

use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{
    public function run(string $path, ?string $queryParameters): void
    {
        if ($path === '/') {
            http_response_code(404);

            return;
        }

        $videoId = strtolower(substr($path, 1));

        $config = require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
        $dbConfig = $config['db'];
        $fetcher = new DatabaseFetcher(new DatabaseConnection(
            $dbConfig['host'],
            $dbConfig['database'],
            $dbConfig['username'],
            $dbConfig['password']
        ));

        $queriedIds = $fetcher->query(
            $fetcher
                ->createQuery('youtube_video')
                ->select('thumbnail')
                ->where('id = :id')
            ,
            ['id' => $videoId]
        );

        if (! $queriedIds) {
            http_response_code(404);

            return;
        }

        $cacheFolder = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

        if (! file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        $thumbnailPath = $cacheFolder . $videoId . '.png';

        if (! file_exists($thumbnailPath)) {
            $thumbnailYoutubeUrl = $queriedIds[0]['thumbnail'];
            $fp = fopen($thumbnailPath, 'w+');
            $ch = curl_init($thumbnailYoutubeUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        header('Content-Type: image/png');
        readfile($thumbnailPath);

        http_response_code(200);
    }
}
