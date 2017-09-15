# surveyjs-php
PHP backend for SurveyJS library


### Prerequisites
- Install [docker](https://www.docker.com/) on your computer
- Clone this repository in the `surveyjs-php` folder
- Build surveyjs-php docker container via `docker build -t surveyjs-php .` command in the `surveyjs-php` folder
- Start docker container via `docker run -p 8000:80 -d -v <ABSOLUTE PATH TO THE SRC FOLDER>:/var/www/site surveyjs-php` command

At this point demo surveyjs-php service will be available at the `http://localhost:8000` address
If everything is ok, you should see project home page with links to `Survey` and `Editor` pages

For now you can continue with `Survey` page, go through the survey and post results to the cusom service

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
