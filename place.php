<?php
    $context_options = stream_context_create(array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
    ));

    $database = json_decode(file_get_contents('./database.json'), true);

    function procGooglePlacesApi($query)
    {
        global $context_options;
        $api_key_json = file_get_contents('./api_key.json');
        $api_key = json_decode($api_key_json, true);
        $params = array(
            'query' => $query,
            'key' => $api_key['GOOGLE_PLACES_API_KEY'],
        );
        echo http_build_query($params);

        return file_get_contents('https://maps.googleapis.com/maps/api/place/textsearch/json?'.http_build_query($params), false, $context_options);
    }
    $queries = json_decode(file_get_contents('./queries.json'));

    $file_name = $queries[0];
    if (PHP_OS === 'WIN32' or PHP_OS === 'WINNT') {
        $file_name = mb_convert_encoding($file_name, 'SJIS', 'auto');
        echo $file_name;
    }

    $file_name = $queries[0];
    if (PHP_OS === 'WIN32' or PHP_OS === 'WINNT') {
        $file_name = mb_convert_encoding($file_name, 'SJIS', 'auto');
    }
    if (file_exists('./place/'.$file_name.'.json')) {
        echo $queries[0].'.jsonをキャッシュとして読み込みます';
        $response = file_get_contents('./place/'.$file_name.'.json');
    } else {
        echo 'Google Places API で '.$queries[0].'について検索します';
        $response = procGooglePlacesApi($queries[0]);
        file_put_contents('./place/'.$file_name.'.json', $response);
    }
    $res_json = json_decode($response, true);
    $results = $res_json['results'];
    foreach ($results as $index => $data) {
        $pdo = null;
        try {
            if ($database) {
                $pdo = new PDO('mysql:host=localhost;dbname=places', $database['user'], $database['pass']);
            }
            $stmt = $pdo->prepare('INSERT IGNORE INTO place VALUES(?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute(array(
                            $data['place_id'],
                            json_encode($data),
                            $data['name'],
                            in_array('rating', $data) ? $data['rating'] : null,
                            $data['geometry']['location']['lat'],
                            $data['geometry']['location']['lng'],
                            $data['formatted_address'],
                        ));
            $stmt = $pdo->prepare('INSERT IGNORE INTO placetype VALUES(?, ?)');
            foreach ($data['types'] as $type_index => $type) {
                $stmt->execute(array(
                            $data['place_id'],
                                                        $type,
                        ));
            }
        } catch (Exception $e) {
        } finally {
            $pdo = null;
        }
    }
