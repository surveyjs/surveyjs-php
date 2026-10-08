// II See results in a dashboard
import { setStoredQuery } from "./host.js";

// #region sjs:II.client
import { Model } from "survey-core";
import { VisualizationPanel } from "survey-analytics";

async function showDashboard(from) {
  const { definition } = await fetch("/api/forms/feedback").then(r => r.json());   // .definition: the I.2 endpoint
  const responses = await fetch(`/api/responses?formId=feedback&from=${from}`).then(r => r.json());

  const survey = new Model(definition);
  const dashboard = new VisualizationPanel(survey.getAllQuestions(), responses);
  dashboard.render(document.getElementById("dashboard"));
  return dashboard;
}
// #endregion

// Demo only: the date field sets `from`; the stored panel counts the responses since then
const fromInput = document.getElementById("from");
let current;
async function refresh() {
  current?.destroy();
  document.getElementById("dashboard").replaceChildren();
  setStoredQuery({ from: fromInput.value });
  current = await showDashboard(fromInput.value);
}
fromInput.value = new Date(Date.now() - 30 * 864e5).toISOString().slice(0, 10);
fromInput.addEventListener("change", refresh);
refresh();
