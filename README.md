# SurveyJS + PHP Demo Example

This demo shows how to integrate [SurveyJS](https://surveyjs.io/) components with a PHP backend.

[View Demo Online](https://surveyjs-php.herokuapp.com/)

## Disclaimer

This demo must not be used as a real service as it doesn't cover such real-world survey service aspects as authentication, authorization, user management, access levels, and different security issues. These aspects are covered by backend-specific articles, forums, and documentation.

## Run the Application

Install [Composer](https://getcomposer.org/) on your machine. After that, run the following commands:

```bash
git clone https://github.com/surveyjs/surveyjs-php.git
cd surveyjs-php
composer install
composer start
```

Open http://localhost:8000 in your web browser.

## Client-Side App

The client-side part is the `surveyjs-react-client` React application. The current project includes only the application's build artifacts. Refer to the [surveyjs-react-client](https://github.com/surveyjs/surveyjs-react-client) repo for full code and information about the application.