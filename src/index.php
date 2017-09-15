<?php
    require 'lib/dispatch.php';

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        }
        exit;
    }

    route('GET', '/', function ($db, $config) {
        return response("<p>Hello?</p>");
    });
      
    route('GET', '/hello/:name', function ($args, $db, $config) {
        $json = json_encode("Hello, " . $args['name'] . "!");
        return response($json, 200, ['content-type' => 'application/json']);
    });

    route('POST', '/post', function ($db, $config) {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body); // $data is "{ "postId": "...", "surveyResult": "..." }"
        $resultJson = json_encode($data->surveyResult);
        return response($resultJson, 200, ['content-type' => 'application/json']);
    });
    
    #$config = require __DIR__.'/config.php';
    #$db = createDBConnection($config['db']);
    $config = null;
    $db = null;
    
    dispatch($db, $config);
?>
