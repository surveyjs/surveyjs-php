<?php

    class PostgresDBAdapter { 
        private $dbh;

        public function __construct($config = null) {
            $this->dbh = new PDO("pgsql:host=dataserver;port=5432;dbname=surveyjs;user=postgres;password=123456");
        }

        public function getObjectFromStorage($storageId) {
            $sqlQuery = 'SELECT * FROM ' . $storageId;
            $data = array();
            $sql = $this->dbh->query($sqlQuery);
            while($result = $sql->fetch(PDO::FETCH_ASSOC)) {
                $data[$result['id']] = $result;
            }
            return $data;
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
            $result = $this->getObjectFromStorage('surveys');
            if(count($result) == 0) {
                $id1 = $this->addSurvey('MySurvey1');
                $this->storeSurvey($id1, $surveys['MySurvey1']);
                $id2 = $this->addSurvey('MySurvey2');
                $this->storeSurvey($id2, $surveys['MySurvey2']);
                $result = $surveys;
            }
            return $result;
        }
    
        public function getSurvey($id) {
            $storage = $this->getSurveys();
            return $storage[$id]['json'];
        }
    
        public function addSurvey($name) {
            $sqlQuery = 'insert into surveys (name, json) values (\'' . $name . '\', \'{}\') RETURNING *';
            $sql = $this->dbh->query($sqlQuery);
            $result = $sql->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }

        public function storeSurvey($id, $json) {
            $sqlQuery = 'update surveys set json=\'' . $json . '\' where id=\'' . $id . '\'';
            $sql = $this->dbh->query($sqlQuery);
            $result = $sql->fetch(PDO::FETCH_ASSOC);
            return $result;
        }

        public function deleteSurvey($id) {
            $this->dbh->exec('DELETE FROM surveys WHERE id=\'' . $id . '\'');
        }

        public function changeName($id, $name) {
            //TODO
        }

        public function postResults($postId, $resultsJson) {
            $sqlQuery = 'insert into results (postid, json) values (\'' . $postId . '\', \'' . $resultsJson . '\')';
            $sql = $this->dbh->query($sqlQuery);
            $result = $sql->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }

        public function getResults($postId) {
            $sqlQuery = 'SELECT * FROM results WHERE postid=\'' . $postId . '\'';
            $data = array();
            $sql = $this->dbh->query($sqlQuery);
            while($result = $sql->fetch(PDO::FETCH_ASSOC)) {
                array_push($data, $result['json']);
            }
            return $data;
        }
    }
?>