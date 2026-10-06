// III.3 Translate strings with AI
import { mountCreator, note, onRequest } from "./host.js";
import { SurveyCreator } from "survey-creator-js";

const formId = "support";
const creator = new SurveyCreator({ showTranslationTab: true });
creator.JSON = (await fetch(`/api/forms/${formId}`).then(r => r.json())).definition;

// #region sjs:III.3.client
// Creator asks for machine translation; your server calls the AI provider
creator.onMachineTranslate.add(async (_, options) => {
  const res = await fetch("/api/translate", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ strings: options.strings, from: options.fromLocale, to: options.toLocale })
  });
  options.callback(res.ok ? await res.json() : []);   // same order as options.strings; [] when it failed
});
// #endregion

mountCreator(creator);
creator.makeNewViewActive("translation");

// Demo only: show the server's message when translation is off or fails
onRequest(async ({ url, status }) => {
  if (url === "/api/translate" && status !== 200) {
    note(status === 501 ? "501: Set AI_API_KEY to enable translation (see .env.example)." : `POST /api/translate answered ${status}.`, "error");
  }
});
