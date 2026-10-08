// I.2 Load the definition, variables and previous answers
import { mountSurvey, note, onStored } from "./host.js";

// #region sjs:I.2.client
import { Model } from "survey-core";

async function createSurvey(recordId) {
  if (window.FORM_DEFINITION) {
    // Option A — your server rendered the definition into the page
    return new Model(window.FORM_DEFINITION);
  }
  // Option B — one request returns the definition, variables and previous answers
  const { definition, variables, data } =
    await fetch(`/api/forms/claim-request?record=${recordId}`).then(r => r.json());
  const survey = new Model(definition);
  survey.setVariables(variables);   // all at once; read as {user.name}, {plan}… and never saved with the response
  survey.data = data;               // previous answers: edit instead of starting blank
  return survey;
}
// #endregion

const recordId = new URLSearchParams(location.search).get("record") ?? "";
const survey = mountSurvey(await createSurvey(recordId));

// Saving the finished response is I.1: post survey.data, which holds answers only
survey.onComplete.add(async (sender) => {
  await fetch("/api/responses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ formId: "claim", data: sender.data })
  });
});

// Demo only: show that the stored response carries no variables
let completed = false;
survey.onComplete.add(() => { completed = true; });
onStored((stored) => {
  const row = stored.responses?.[0];
  if (!completed || !row) return;
  completed = false;
  const keys = Object.keys(row.data ?? {});
  const leaked = keys.filter((key) => ["user", "plan", "variables"].includes(key));
  note(leaked.length
    ? `Response #${row.id} contains ${leaked.join(", ")}: variables were saved.`
    : `Response #${row.id} stores ${keys.join(", ")}: no variables, only answers.`, leaked.length ? "error" : "ok");
});
document.getElementById("start-over")?.addEventListener("click", () => survey.clear(false, true));
