function init() {
    //$.material.init();

    var json = {
        surveyPostId: '3ce10f8b-2d8a-4ca2-a110-2994b9e697a1',
        title: "Product Feedback Survey Example", showProgressBar: "top", pages: [
        {questions: [
            { type: "matrix", name: "Quality", title: "Please indicate if you agree or disagree with the following statements",
                columns: [{ value: 1, text: "Strongly Disagree" },
                    { value: 2, text: "Disagree" },
                    { value: 3, text: "Neutral" },
                    { value: 4, text: "Agree" },
                    { value: 5, text: "Strongly Agree" }],
                rows: [{ value: "affordable", text: "Product is affordable" },
                    { value: "does what it claims", text: "Product does what it claims" },
                    { value: "better then others", text: "Product is better than other products on the market" },
                    { value: "easy to use", text: "Product is easy to use" }]},
            { type: "rating", name: "satisfaction", title: "How satisfied are you with the Product?",
                mininumRateDescription: "Not Satisfied", maximumRateDescription: "Completely satisfied" },
            { type: "rating", name: "recommend friends", visibleIf: "{satisfaction} > 3",
                title: "How likely are you to recommend the Product to a friend or co-worker?",
                mininumRateDescription: "Will not recommend", maximumRateDescription: "I will recommend" },
            { type: "comment", name: "suggestions", title:"What would make you more satisfied with the Product?", }
        ]},
        {questions: [
            { type: "radiogroup", name: "price to competitors",
                title: "Compared to our competitors, do you feel the Product is",
                choices: ["Less expensive", "Priced about the same", "More expensive", "Not sure"]},
            { type: "radiogroup", name: "price", title: "Do you feel our current price is merited by our product?",
                choices: ["correct|Yes, the price is about right",
                    "low|No, the price is too low for your product",
                    "high|No, the price is too high for your product"]},
            { type: "multipletext", name: "pricelimit", title: "What is the... ",
                items: [{ name: "mostamount", title: "Most amount you would every pay for a product like ours" },
                    { name: "leastamount", title: "The least amount you would feel comfortable paying" }]}
        ]},
        { questions: [
            { type: "text", name: "email",
                title: "Thank you for taking our survey. Your survey is almost complete, please enter your email address in the box below if you wish to participate in our drawing, then press the 'Submit' button."}
        ]}
    ]};

    Survey.dxSurveyService.serviceUrl = "http://localhost:8000";
    Survey.defaultBootstrapCss.navigationButton = "btn btn-primary";
    //Survey.Survey.cssType = "bootstrapmaterial";
    Survey.Survey.cssType = "bootstrap";

    var model = new Survey.Model(json);
    window.survey = model;

    model.render("surveyElement");

    function SurveyManager(baseUrl, accessKey) {
        var self = this;
        self.selectedSurvey = ko.observable();
        self.availableSurveys = ko.observableArray();

        self.selectedSurvey.subscribe(function(newValue) {
            var surveyModel = new Survey.Model(JSON.parse(newValue.survey));
            window.survey = surveyModel;
            ko.cleanNode(document.getElementById("surveyElement"));
            document.getElementById("surveyElement").innerText = "";
            surveyModel.render("surveyElement");
        });

        var xhr = new XMLHttpRequest();
        xhr.open('GET', baseUrl + '/getActive?accessKey=' + accessKey);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var result = xhr.response ? JSON.parse(xhr.response) : {};
            self.availableSurveys(Object.keys(result).map(function(key) {
                return {
                    id: key,
                    survey: result[key]
                }
            }));
        };
        xhr.send();
    }

    ko.applyBindings(new SurveyManager("http://localhost:8000"), document.getElementById("select"));
}

init();
