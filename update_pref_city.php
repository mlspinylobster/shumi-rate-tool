<?php
    define('RESAS_API_URL', 'https://opendata.resas-portal.go.jp/api/v1-rc.1');
    $api_key = json_decode(file_get_contents('./api_key.json'), true);
    $database = json_decode(file_get_contents('./database.json'), true);
    if (!$database) {
        exit;
    }
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => 'X-API-KEY:'.$api_key['RESAS_API_KEY']."\r\n",
        ),
    ));
    $prefectures = json_decode(file_get_contents(RESAS_API_URL.'/prefectures', false, $context), true);
    foreach ($prefectures['result'] as $index => $prefecture) {
        $pdo = null;
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=places', $database['user'], $database['pass']);
            $stmt = $pdo->prepare('INSERT IGNORE INTO prefecture(code, name) VALUES(?, ?)');
            $stmt->execute(array(
                                $prefecture['prefCode'],
                                $prefecture['prefName'],
                            ));
            $prefecture_id = $pdo->lastInsertId();
            echo $prefecture_id;
            usleep(200000);
            $cities = json_decode(file_get_contents(RESAS_API_URL.'/cities?prefCode='.$prefecture['prefCode'], false, $context), true);
            $stmt = $pdo->prepare('INSERT IGNORE INTO city(code, name, big_city_flag, prefecture_id) VALUES(?, ?, ?, ?)');
            foreach ($cities['result'] as $city_index => $city) {
                $stmt->execute(array(
                            $city['cityCode'],
                            $city['cityName'],
                            $city['bigCityFlag'],
                            $prefecture_id,
                        ));
            }
        } catch (Exception $e) {
        } finally {
            $pdo = null;
        }
    }
