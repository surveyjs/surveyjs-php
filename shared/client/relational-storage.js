// I.7 Store responses in a relational database
import { mountSurvey, note, onStored, page } from "./host.js";

// Demo only: the v1 / v2 switch. v2 renames q_email and q_amount in Creator but keeps their valueName
const definitionVersion = new URLSearchParams(location.search).get("version") === "v2" ? "v2" : "v1";
const definitions = { v1: page.definition, v2: page.definitionV2 };

// #region sjs:I.7.client
import { Model, Serializer } from "survey-core";

// Optional: a custom property for richer mapping, editable in Creator's property grid
Serializer.addProperty("question", { name: "dbColumn", category: "data" });

// valueName fixes the key your columns read → response: { "customer_email": "...", "amount": 1840.5 }
const survey = new Model(definitions[definitionVersion]);

// Post the original response with the version of the definition it was filled against
survey.onComplete.add(async (sender) => {
  await fetch("/api/claims", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ data: sender.data, definitionVersion })
  });
});
// #endregion

mountSurvey(survey);

// Demo only: confirm the columns were filled from the valueName keys
let completed = false;
survey.onComplete.add(() => { completed = true; });
onStored((stored) => {
  const claim = stored.claims?.[0];
  const response = stored.responses?.[0];
  if (!completed || !claim || !response) return;
  completed = false;
  note(`Response #${response.id} (definition ${response.definition_version}) → claims row: customer_email = ${claim.customer_email}, amount = ${claim.amount}.`, "ok");
});
document.getElementById("start-over")?.addEventListener("click", () => survey.clear(true, true));
