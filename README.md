# surveyjs-php
Sample PHP backend for SurveyJS library


### Prerequisites
- Install [docker](https://www.docker.com/) on your computer
- Clone this repository in the `surveyjs-php` folder
- Build surveyjs-php docker container via `docker build -t surveyjs-php .` command in the `docker/surveyjs-php` folder
- Build postgresql-db docker container via `docker build -t postgresql-db .` command in the `docker/postgresql-db` folder
- Start dockers container via the following commands:
`docker run --name dbsrv -p 5432:5432 -d postgresql-db`
`docker run --name appsrv --link dbsrv:dataserver -p 8000:80 -d -v <ABSOLUTE PATH TO THE SRC FOLDER>:/var/www/site/ surveyjs-php`

Note: if you are familliar with docker and docker-compose, just run `docker-compose up` from the `docker` folder.
Note: `<ABSOLUTE PATH TO THE SRC FOLDER>` is the path to the `surveyjs-php/src` folder on your computer.

At this point demo surveyjs-php service will be available at the `http://localhost:8000` address.
If everything is ok, you should see project home page with list of available surveys and links to `Survey` and `Editor` pages.

You can continue with survey via `Run` page, go through the survey and post results to the custom service.
You can continue with editor via `Edit` page, change the survey and store survey JSON to the custom service.
Saved survey results are available via `Results` link.


### In order to post survey results it is needed to:
- initialize survey json with a post id
```
    var surveyJson = {
        surveyPostId: '3ce10f8b-2d8a-4ca2-a110-2994b9e697a1',
```
- to set up custom service URL
```
    Survey.dxSurveyService.serviceUrl = "http://localhost:8000";
```
These changed are demoinstrated in the [/src/survey.js](https://github.com/surveyjs/surveyjs-php/blob/master/src/survey.js) file of this repo.
