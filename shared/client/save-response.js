// I.1 Save the response
import { mountSurvey, note, onStored, show } from "./host.js";

// #region sjs:I.1.client
import { Model } from "survey-core";

// The definition can be a constant in the page — no database needed
const definition = {
  elements: [
    { type: "text", name: "email", title: "Your email", inputType: "email", isRequired: true },
    { type: "comment", name: "message", title: "How can we help?" }
  ]
};
const survey = new Model(definition);

// One endpoint: post the answers when the respondent completes the form
survey.onComplete.add(async (sender) => {
  await fetch("/api/responses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ formId: "contact", data: sender.data })
  });
});
// #endregion

mountSurvey(survey);

// Demo only: compare the posted body with what the server stored
let posted = null;
survey.onComplete.add((sender) => {
  posted = { formId: "contact", data: sender.data };
  show("posted", JSON.stringify(posted, null, 2));
});
onStored((stored) => {
  const row = stored.responses?.find((r) => r.form_id === "contact");
  if (!posted || !row) return;
  const identical = JSON.stringify(row.data) === JSON.stringify(posted.data);
  show("identical", identical ? "identical ✓" : "different ✗");
  note(identical
    ? `Response #${row.id}: the stored data is identical to the posted data.`
    : `Response #${row.id}: the stored data differs from the posted data.`, identical ? "ok" : "error");
  posted = null;
});
document.getElementById("start-over")?.addEventListener("click", () => survey.clear(true, true));
