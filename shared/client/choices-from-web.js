// I.5 Choices from the web
import { mountSurvey, page } from "./host.js";
import { Model } from "survey-core";

// Demo only: a stand-in for the access token your app already has
const token = "demo-token";

// #region sjs:I.5.client
// In the definition — no code (shared/definitions/choices-from-web.json):
//   "choicesByUrl": { "url": "/api/offices?region={region}", "valueName": "id", "titleName": "name" }
//   "choicesByUrl": { "url": "/api/countries", "valueName": "code", "titleName": "name" }
// {region} is replaced with the answer to "region" (or a variable), and the list reloads when it changes.

// Optional: add your auth header to every choices request
import { settings } from "survey-core";
settings.web.onBeforeRequestChoices = (_, options) => {
  options.request?.setRequestHeader("Authorization", `Bearer ${token}`);
};
// #endregion

mountSurvey(new Model(page.definition));
