# surveyjs-php
PHP backend sample for SurveyJS: Survey Library and Survey Creator


### Disclaimer
This demo illustrates how to integrate SurveyJS libraries with PHP backend. This demo doesn't cover all of real survey service application aspects, such as authentication, authorization, user management, access levels and different security issues. These aspects are covered by backend-specific articles, forums and documentation. This demo demos is just intergration one and can't be used as a real service.

## [SurveyJS Home Page](https://surveyjs.io/Examples/Service/)

## [Live Online Survey / Form Builder Demo](https://surveyjs-php.herokuapp.com/)


### Prerequisites
- Install [docker](https://www.docker.com/) on your computer
- Clone this repository in the `surveyjs-php` folder
- Go to `surveyjs-php\docker\docker-compose.yml` and replace `- <ABSOLUTE PATH TO THE SRC FOLDER>:/var/www/site/` with your path.
  For example: `- E:\projects\surveyjs-php\:/var/www/site/`
- Go to `surveyjs-php\docker\` folder and run `docker-compose up`

At this point demo surveyjs-php service will be available at the `http://localhost:8000` address.

If everything is ok, you should see project home page with list of available surveys and links to `Survey` and `Editor` pages.
- You can continue with survey via `Run` page, go through the survey and post results to the custom service.
- You can continue with Survey Creator via `Edit` page, change the survey and store survey JSON to the custom service.
- Saved survey results are available via `Results` link.


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
These changes are demonstrated in the [/src/survey.js](https://github.com/surveyjs/surveyjs-php/blob/master/src/survey.js) file of this repo.

### To learn more about SurveyJS

You may learn more, about how to add these widgets on your page and customize them for your needs.

- [Read about Survey Creator](https://surveyjs.io/Documentation/Builder/?id=Survey-Builder-Overview) 
- [Read about Survey Library](https://surveyjs.io/Documentation/Library/?id=LibraryOverview)
