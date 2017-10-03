function SurveyManager(baseUrl, accessKey) {
    var self = this;
    var url = new URL(document.URL);
    var surveyId = url.searchParams.get("id");        
    self.results = ko.observableArray();

    self.loadResults = function () {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', baseUrl + '/results?postId=' + surveyId);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var result = xhr.response ? JSON.parse(xhr.response) : {};
            self.results(result);
        };
        xhr.send();
    }

    self.loadResults();
}

ko.applyBindings(new SurveyManager("http://localhost:8000"), document.getElementById("results"));