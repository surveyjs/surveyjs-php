<?php

    class InMemoryDBAdapter { 

        public function __construct($config = null) {
        }

        public function getObjectFromStorage($storageId, $defaultValue) {
            if(!isset($_SESSION[$storageId])) {
                $keys = array_keys($defaultValue);
                $values = array_values($defaultValue);
                $stringKeys = array_map('strval', $keys);
                $defaultValue = array_combine($stringKeys, $values);                   
                $_SESSION[$storageId] = serialize($defaultValue);
            }
            return unserialize($_SESSION[$storageId]);
        }
    
        public function getSurveys() {
            $surveys = array("MySurvey1" => '{
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
            return $this->getObjectFromStorage('SurveyStorage', $surveys);
        }
    
        public function getResultsObject() {
            $allResults = array();
            return $this->getObjectFromStorage('ResultsStorage', $allResults);
        }
    
        public function getSurvey($id) {
            $storage = $this->getSurveys();
            return $storage[$id];
        }
    
        public function addSurvey($name) {
            $storage = $this->storeSurvey($name, '{}');
            return $name;
        }

        public function storeSurvey($id, $json) {
            $storage = $this->getSurveys();
            $storage[$id] = $json;
            $_SESSION['SurveyStorage'] = serialize($storage);
        }

        public function deleteSurvey($id) {
            $storage = $this->getSurveys();
            unset($storage[$id]);
            $_SESSION['SurveyStorage'] = serialize($storage);
        }

        public function postResults($postId, $resultsJson) {
            $storage = $this->getResultsObject();
            $postResults = $storage[$postId];
            if($postResults == null) {
                $storage[$postId] = array();
            }
            $postResults = $storage[$postId];
            array_push($postResults, $resultsJson);
            $storage[$postId] = $postResults;
            $_SESSION["ResultsStorage"] = serialize($storage);
            return $postResults;
        }

        public function getResults($postId) {
            $storage = $this->getResultsObject();
            $postResults = $storage[$postId];
            return $postResults;
        }
    }
?>