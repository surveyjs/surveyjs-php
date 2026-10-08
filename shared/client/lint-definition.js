// IV.2 Lint definitions before saving
import { mountCreator, note } from "./host.js";
import { SurveyCreator } from "survey-creator-js";

const formId = "support";
const creator = new SurveyCreator({ showTranslationTab: true });
creator.JSON = (await fetch(`/api/forms/${formId}`).then(r => r.json())).definition;

// #region sjs:IV.2.client
// Creator: surface the server's lint report instead of a silent failure
async function saveDefinition(definition) {
  const res = await fetch(`/api/forms/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(definition)
  });
  const report = res.status === 204 ? {} : await res.json();   // 422 { errors, warnings } · 200 { warnings } · { error }
  if (report.errors) creator.notify(report.errors.map(e => e.message).join("\n"), "error");
  else if (report.warnings) creator.notify(report.warnings.map(e => e.message).join("\n"), "info");
  else if (report.error) creator.notify(report.error, "error");
  return res.ok;
}
creator.saveSurveyFunc = async (saveNo, callback) => callback(saveNo, await saveDefinition(creator.JSON));
// #endregion

mountCreator(creator);

// Demo only: Creator never produces these definitions itself, so the buttons send them as they are
const broken = (change) => {
  const definition = structuredClone(creator.JSON);
  change(definition.pages?.[0]?.elements ?? definition.elements);
  return definition;
};
const cases = {
  "unknown-variable": () => broken((elements) => { elements[0].visibleIf = "{nosuch} = 1"; }),
  "no-name": () => broken((elements) => elements.push({ type: "text", title: "A question without a name" })),
  "unknown-property": () => broken((elements) => { elements[0].someproperty = 1; }),
  "clean": () => creator.JSON,
};
for (const button of document.querySelectorAll("[data-lint]")) {
  button.addEventListener("click", async () => {
    const saved = await saveDefinition(cases[button.dataset.lint]());
    note(`${button.textContent}: ${saved ? "saved" : "not saved"}. Creator shows the report as a notification.`, saved ? "ok" : "error");
  });
}
