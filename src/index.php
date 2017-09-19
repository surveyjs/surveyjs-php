<?php
    require 'lib/dispatch.php';

    class DBAdapter { 

        public function __construct($config=null){
        }

        public function getSurveys() {
            if(isset($_SESSION['SurveyStorage'])) {
                return unserialize($_SESSION['SurveyStorage']);
            }
            return array("MySurvey1" => '{
                "pages": [
                 {
                  "name": "page1",
                  "elements": [
                   {
                    "type": "radiogroup",
                    "choices": [
                     "item1",
                     "item2",
                     "item3"
                    ],
                    "name": "question from survey1"
                   }
                  ]
                 }
                ]
               }',
               "MySurvey2" => '{
                "pages": [
                 {
                  "name": "page1",
                  "elements": [
                   {
                    "type": "checkbox",
                    "choices": [
                     "item1",
                     "item2",
                     "item3"
                    ],
                    "name": "question from survey2"
                   }
                  ]
                 }
                ]
               }' );
        }
    
        public function getSurvey($id) {
            if(isset($_SESSION['SurveyStorage'])) {
                $storage = unserialize($_SESSION['SurveyStorage']);
                return $storage[$id];
            }
            return null;
        }
    
        public function storeSurvey($id, $json) {
            $storage = null;
            if(isset($_SESSION['SurveyStorage'])) {
                $storage = unserialize($_SESSION['SurveyStorage']);
            } else {
                session_start();
                $storage = array();
            }
            $storage[$id] = $json;
            $_SESSION['SurveyStorage'] = serialize($storage);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        }
        exit;
    }

    // route('GET', '/', function ($db, $config) {
    //     return response("<p>Hello?</p>");
    // });
      
    route('GET', '/hello/:name', function ($args, $db, $config) {
        $json = json_encode("Hello, " . $args['name'] . "!");
        return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });

    route('POST', '/post', function ($db, $config) {
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body); // $data is "{ "postId": "...", "surveyResult": "..." }"
        $resultJson = json_encode($data->surveyResult);
        return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });
    
    route('GET', '/survey', function ($db, $config) {
        $surveyId = $_GET['surveyId'];
        // Code to get survey json by id here
        $surveyJson = $db->getSurvey($surveyId);
        $resultJson = json_encode($surveyJson);
        return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });
    
    route('GET', '/getActive', function ($db, $config) {
        $accessKey = $_GET['accessKey'];
        // Code to get a list of all active surveys here
        $json = json_encode($db->getSurveys());
        return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });

    route('GET', '/api/MySurveys/create', function ($db, $config) {
        $name = $_GET['name'];
        $accessKey = $_GET['accessKey'];
        $id = $name; // It is needed to generate a valid id here
        // Code to create survey description json here
        $survey = array('Name' => $name, 'Id' => $id);
        $json = json_encode($survey);
        return response($json, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });

    route('POST', '/api/MySurveys/changeJson', function ($db, $config) {
        $accessKey = $_POST['accessKey'];
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body); // $data is "{ "Id": "...", "Json": "...", "Text": "..." }"
        // Code to store survey json here
        $db->storeSurvey($data->Id, $data->Json);
        $resultJson = json_encode($db->getSurvey($data->Id)); // For debug purposes - ensure store works
        return response($resultJson, 200, ['content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    });
    
    // $config = require __DIR__.'/config.php';
    // $db = createDBConnection($config['db']);
    $config = null;
    $db = new DBAdapter($config);
    
    dispatch($db, $config);
?>
