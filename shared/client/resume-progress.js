// I.3 Resume where the user left off
import { mountSurvey, onRequest, page, show, setStoredQuery } from "./host.js";
import { Model } from "survey-core";

const formId = "onboarding";   // real code keys progress by user and form
const survey = mountSurvey(new Model(page.definition));

// #region sjs:I.3.client
import debounce from "lodash/debounce";

// On return: restore both — the user continues on the same page, in the same input.
// Restore before listening, so restoring the page doesn't count as a change to save.
const saved = await fetch(`/api/progress/${formId}`).then(r => (r.ok ? r.json() : null));
if (saved) {
  survey.data = saved.data;
  survey.uiState = saved.uiState;
}

// Save answers + UI state as the user works (uiState: see the Form Library API reference)
const saveProgress = debounce(() => {
  fetch(`/api/progress/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ data: survey.data, uiState: survey.uiState }),
    keepalive: true                    // lets the last request finish while the tab closes
  });
}, 800);
survey.onValueChanged.add(saveProgress);
survey.onCurrentPageChanged.add(saveProgress);
document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "hidden") saveProgress.flush();
});
// #endregion

// Demo only: the "Saved" indicator, reload and clear buttons
setStoredQuery({ key: formId });
show("saved", saved ? "Restored the saved progress" : "Nothing saved yet");
onRequest(({ method, url, status }) => {
  if (method === "PUT" && url.startsWith("/api/progress/") && status === 204) show("saved", `Saved ${new Date().toLocaleTimeString()}`);
});
survey.onValueChanged.add(() => show("saved", "Typing… saves after a pause"));
document.getElementById("reload")?.addEventListener("click", () => {
  saveProgress.flush();
  setTimeout(() => location.reload(), 300);
});
document.getElementById("clear-progress")?.addEventListener("click", async () => {
  saveProgress.cancel();
  await fetch(`/api/progress/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ data: {}, uiState: {} })
  });
  location.reload();
});
