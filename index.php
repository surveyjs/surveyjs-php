<?php
    require 'lib/dispatch.php';
    require 'inmemorydbadapter.php';
    require 'postgresdbadapter.php';
    
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        }
        exit;
    }

    route('GET', '/', function ($db, $config) {
        header('Location: /index.html');
    });
      
    // route('GET', '/hello/:name', function ($args, $db, $config) {
    //     $json = json_encode("Hello, " . $args['name'] . "!");
    //     return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    // });

    route('POST', '/post', function ($db, $config) {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body); // $data is "{ "postId": "...", "surveyResult": "..." }"
        $resultsFromStorage = $db->postResults($data->postId, $data->surveyResult);
        $resultsFromStorage = $db->getResults($data->postId);
        $resultJson = json_encode($resultsFromStorage);
        // return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
        return response($resultJson, 200);
    });
    
    // route('GET', '/survey', function ($db, $config) {
    //     $surveyId = $_GET['surveyId'];
    //     $surveyJson = $db->getSurvey($surveyId);
    //     //return response($surveyJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    //     return response($surveyJson, 200, ['content-type' => 'application/json']);
    // });
    
    route('GET', '/getSurvey', function ($db, $config) {
        $surveyId = $_GET['surveyId'];
        $surveyJson = $db->getSurvey($surveyId);
        //return response($surveyJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
        return response($surveyJson, 200, ['content-type' => 'application/json']);
    });
    
    route('GET', '/getActive', function ($db, $config) {
        $accessKey = $_GET['accessKey'];
        $json = json_encode($db->getSurveys());
        // return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
        return response($json, 200, ['content-type' => 'application/json']);
    });

    route('GET', '/create', function ($db, $config) {
        $name = $_GET['name'];
        $accessKey = $_GET['accessKey'];
        $id = $db->addSurvey($name);
        $survey = array('Name' => $name, 'Id' => $id);
        $json = json_encode($survey);
        return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });

    route('GET', '/changeName', function ($db, $config) {
        $name = $_GET['name'];
        $id = $_GET['id'];
        $db->changeName($id, $name);
        $data = array('Id' => $name);
        $json = json_encode($data);
        return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });

    route('POST', '/changeJson', function ($db, $config) {
        $accessKey = $_POST['accessKey'];
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body); // $data is "{ "Id": "...", "Json": "...", "Text": "..." }"
        // Code to store survey json here
        $db->storeSurvey($data->Id, $data->Json);
        $resultJson = json_encode($db->getSurvey($data->Id)); // For debug purposes - ensure store works
        return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });
    
    route('GET', '/delete', function ($db, $config) {
        $surveyId = $_GET['id'];
        $db->deleteSurvey($surveyId);
        //return response(null, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
        return response(null, 200, ['content-type' => 'application/json']);
    });
    
    route('GET', '/results', function ($db, $config) {
        $postId = $_GET['postId'];
        $resultsFromStorage = $db->getResults($postId);
        $resultJson = json_encode($resultsFromStorage);
        //return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
        return response($resultJson, 200, ['content-type' => 'application/json']);
    });
    
    // $config = require __DIR__.'/config.php';
    // $db = createDBConnection($config['db']);
    $config = null;
    $db = new InMemoryDBAdapter($config);
    //$db = new PostgresDBAdapter($config);
    
    dispatch($db, $config);
?>

