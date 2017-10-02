#docker build -t surveyjs-php .
#docker build -t postgresql-db .
#docker rm $(docker stop $(docker ps -a -q))
#docker run --name dbsrv -p 5432:5432 -d postgresql-db
#docker run --name appsrv --link dbsrv:dataserver -p 8000:80 -d -v <ABSOLUTE PATH TO THE SRC FOLDER>:/var/www/site/ surveyjs-php
