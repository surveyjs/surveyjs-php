<?php

    namespace App\Data;
    
    require 'demo-surveys.php';

    class InMemoryDBAdapter { 

        public function __construct($config = null) {
        }

        public function getObjectFromStorage($storageId, $defaultValue) {
            if(!isset($_SESSION[$storageId])) {
                // $keys = array_keys($defaultValue);
                // $values = array_values($defaultValue);
                // $stringKeys = array_map('strval', $keys);
                // $defaultValue = array_combine($stringKeys, $values);                   
                $_SESSION[$storageId] = serialize($defaultValue);
            }
            return unserialize($_SESSION[$storageId]);
        }

        public function findById($array, $id) {
            foreach($array as $element) {
                if($id == $element->id) {
                    return $element;
                }
            }
            return null;
        }
    
        public function getSurveys() {
            global $survey1Name, $survey1Json;
            global $survey2Name, $survey2Json;
        
            $surveys = array();
            $surveys['1'] = (object) [
                'id' => '1',
                'name' => $survey1Name,
                'json' => $survey1Json
            ];
            $surveys['2'] = (object) [
                'id' => '2',
                'name' => $survey2Name,
                'json' => $survey2Json
            ];
            return $this->getObjectFromStorage('SurveyStorage', $surveys);
        }
    
        public function getResultsObject() {
            global $survey1Results, $survey2Results;
            $allResults = array();
            $allResults['1'] = (object) [
                'id' => '1',
                'data' => $survey1Results
            ];
            $allResults['2'] = (object) [
                'id' => '2',
                'data' => $survey2Results
            ];
            return $this->getObjectFromStorage('ResultsStorage', $allResults);
        }
    
        public function getSurvey($id) {
            $storage = $this->getSurveys();
            return $this->findById($storage, $id);
        }
    
        public function createSurvey() {
            global $currentId;
            $survey = (object) [
                'id' => ''.$currentId,
                'name' => 'New Survey '.$currentId++,
                'json' => '{}'
            ];
            $storage = $this->getSurveys();
            $storage[$survey->id] = $survey;
            $_SESSION['SurveyStorage'] = serialize($storage);
            return $survey;
        }

        public function changeName($id, $name) {
            $storage = $this->getSurveys();
            $json = $storage[$id];
            $storage[$name] = $json;
            unset($storage[$id]);
            $_SESSION['SurveyStorage'] = serialize($storage);
        }

        public function storeSurvey($id, $json) {
            $storage = $this->getSurveys();
            $survey = $this->findById($storage, $id);
            $survey->json = $json;
            $_SESSION['SurveyStorage'] = serialize($storage);
            return $survey;
        }

        public function deleteSurvey($id) {
            $storage = $this->getSurveys();
            foreach($storage as $key => $survey){
                if($survey->id == $id) {
                    unset($storage[$key]);
                    break;
                }
            }
            $_SESSION['SurveyStorage'] = serialize($storage);
            return (object) [ 'id' => $id ];
        }

        public function postResults($postId, $resultsJson) {
            $storage = $this->getResultsObject();
            $postResults = $this->findById($storage, $postId);
            if($postResults == null) {
                $postResults = (object) [
                    'id' => $postId,
                    'data' => array()
                ];
                $storage[$postId] = $postResults;
            }
            array_push($postResults->data, (object) $resultsJson);
            $_SESSION["ResultsStorage"] = serialize($storage);
            return $postResults;
        }

        public function getResults($postId) {
            $storage = $this->getResultsObject();
            $postResults = $this->findById($storage, $postId);
            return $postResults;
        }
    }
?>