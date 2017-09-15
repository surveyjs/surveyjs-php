#docker rm $(docker stop $(docker ps -a -q))
#docker build -t surveyjs-php .
#docker run -p 8000:80 -d -v <ABSOLUTE PATH TO THE SRC FOLDER>:/var/www/site surveyjs-php
