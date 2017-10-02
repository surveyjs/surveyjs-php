<?php

    class DBAdapter { 

        public function __construct($config = null) {
        }

        public function getSurveys() {
            if(!isset($_SESSION['SurveyStorage'])) {
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
                $keys = array_keys($surveys);
                $values = array_values($surveys);
                $stringKeys = array_map('strval', $keys);
                $surveys = array_combine($stringKeys, $values);                   
                $_SESSION['SurveyStorage'] = serialize($surveys);
            }
            return unserialize($_SESSION['SurveyStorage']);
        }
    
        public function getSurvey($id) {
            $storage = $this->getSurveys();
            return $storage[$id];
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
    }
?>