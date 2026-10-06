// III.1 Load and save definitions
import { mountCreator, mountSurvey, note } from "./host.js";
import { Model, Serializer } from "survey-core";

const formId = "support";

// The I.7 custom property: it shows in the property grid's Data category
Serializer.addProperty("question", { name: "dbColumn", category: "data" });

// #region sjs:III.1.client
import { SurveyCreator } from "survey-creator-js";

const creator = new SurveyCreator({ showTranslationTab: true });
creator.JSON = (await fetch(`/api/forms/${formId}`).then(r => r.json())).definition;   // the I.2 endpoint's .definition

creator.saveSurveyFunc = async (saveNo, callback) => {
  const res = await fetch(`/api/forms/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(creator.JSON)
  });
  callback(saveNo, res.ok);   // tells Creator whether the save succeeded
};
// #endregion

// Demo only: "Open the form" (?form) renders the saved definition with the Form Library instead
if (new URLSearchParams(location.search).has("form")) {
  mountSurvey(new Model(creator.JSON), "creator");
  note("This is the saved definition, as the next page load renders it.", "ok");
} else {
  mountCreator(creator);
}
