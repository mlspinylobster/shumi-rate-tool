<?php
    $context_options = stream_context_create(array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
    ));

    $database = json_decode(file_get_contents('./database.json'), true);
        if (!$database) {
            exit(1);
        }

    function procGooglePlacesApi($query, $use_store_type)
    {
        global $context_options;
        $api_key_json = file_get_contents('./api_key.json');
        $api_key = json_decode($api_key_json, true);
        $params = array(
            'query' => $query,
            'key' => $api_key['GOOGLE_PLACES_API_KEY'],
        );
        if ($use_store_type) {
            $params['types'] = 'store';
        }

        return file_get_contents('https://maps.googleapis.com/maps/api/place/textsearch/json?'.http_build_query($params), false, $context_options);
    }

    $pdo = new PDO('mysql:host=localhost;dbname=places', $database['user'], $database['pass']);
    $stmt = $pdo->prepare('SELECT prefecture.id AS prefecture_id, city.id AS city_id, prefecture.name AS prefecture_name, city.name AS city_name FROM city LEFT JOIN prefecture ON city.prefecture_id = prefecture.id');
    $stmt->execute();
    $cities = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT * FROM keyword');
    $stmt->execute();
    $queries = $stmt->fetchAll();
    $pdo = null;
    foreach ($cities as $city_index => $city) {
        // if ($city['prefecture_id'] < 31 or $city['prefecture_id'] > 39) {
        //     continue;
        // }
        foreach ($queries as $query_index => $keyword) {
            try {
                $pdo = new PDO('mysql:host=localhost;dbname=places', $database['user'], $database['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // 検索済みかどうか
                $stmt = $pdo->prepare('SELECT * FROM search WHERE keyword_id=? AND prefecture_id=? AND city_id=?');
                $stmt->execute(array($keyword['id'], $city['prefecture_id'], $city['city_id']));
                $row = $stmt->fetch();
                if ($row) {
                    if ($row['complete']) {
                        // 検索済み
                        continue;
                    }
                    $search_id = intval($row['id']);
                } else {
                    // 検索レコード作成
                    $stmt = $pdo->prepare('INSERT IGNORE INTO search(keyword_id, prefecture_id, city_id) VALUES(?, ?, ?)');
                    $stmt->execute(array($keyword['id'], $city['prefecture_id'], $city['city_id']));
                    $search_id = $pdo->lastInsertId();
                }

                $query = $keyword['keyword'].' '.$city['prefecture_name'].' '.$city['city_name'];
                var_dump(mb_convert_encoding($query, 'SJIS', 'auto'));
                $response = procGooglePlacesApi($query, $keyword['use_store_type']);

                $res_json = json_decode($response, true);
                $results = $res_json['results'];
                foreach ($results as $index => $data) {
                    $stmt = $pdo->prepare('INSERT IGNORE INTO place(place_id, plain_json, name, rating, lat, lng, address) VALUES(?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute(array(
                                        $data['place_id'],
                                        json_encode($data),
                                        $data['name'],
                                        in_array('rating', $data) ? $data['rating'] : null,
                                        $data['geometry']['location']['lat'],
                                        $data['geometry']['location']['lng'],
                                        $data['formatted_address'],
                                    ));

                    $stmt = $pdo->prepare('SELECT * FROM place WHERE place_id=?');
                    $stmt->execute(array(
                              $data['place_id'],
                          ));
                    $place = $stmt->fetch();
                    $place_id = intval($place['id']);
                    $stmt = $pdo->prepare('INSERT IGNORE INTO placetype(place_id, type) VALUES(?, ?)');
                    foreach ($data['types'] as $type_index => $type) {
                        $stmt->execute(array(
                                        $place_id,
                                        $type,
                                    ));
                    }
                    $stmt = $pdo->prepare('INSERT IGNORE INTO placesearch(search_id, place_id) VALUES(?, ?)');
                    $stmt->execute(array(
                                            $search_id,
                                            $place_id,
                                        ));
                }
                $stmt = $pdo->prepare('UPDATE search SET complete=1 WHERE keyword_id=? AND prefecture_id=? AND city_id=?');
                $stmt->execute(array($keyword['id'], $city['prefecture_id'], $city['city_id']));
            } catch (Exception $e) {
                var_dump($e);
            } finally {
                $pdo = null;
            }
        }
    }
    // $file_name = $queries[0];
    // if (PHP_OS === 'WIN32' or PHP_OS === 'WINNT') {
    //     $file_name = mb_convert_encoding($file_name, 'SJIS', 'auto');
    // }
    // if (file_exists('./place/'.$file_name.'.json')) {
    //     echo $queries[0].'.jsonをキャッシュとして読み込みます';
    //     $response = file_get_contents('./place/'.$file_name.'.json');
    // } else {
    //     echo 'Google Places API で '.$queries[0].'について検索します';
    //     $response = procGooglePlacesApi($queries[0]);
    //     file_put_contents('./place/'.$file_name.'.json', $response);
    // }
