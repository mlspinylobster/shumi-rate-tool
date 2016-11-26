<?php
	function procGooglePlacesApi($query) {
		echo "Google Places API で " . $query . "について検索します";
		$api_key_json = file_get_contents("./api_key.json");
		$api_key = json_decode($api_key_json, TRUE);
		$params = array(
			"query" => $query,
			"key" => $api_key["GOOGLE_PLACES_API_KEY"]
		);
		echo http_build_query($params);
		return file_get_contents("https://maps.googleapis.com/maps/api/place/textsearch/json?" . http_build_query($params));
	}
	$queries = json_decode(file_get_contents("./queries.json"));

	$response = procGooglePlacesApi($queries[0]);
	file_put_contents("./place/".$queries[0].".json", $response);
