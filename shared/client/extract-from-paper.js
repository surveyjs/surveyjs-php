// IV.4 Turn paper, PDF and images into responses
import { mountSurvey, note, page, show } from "./host.js";
import { Model } from "survey-core";

const survey = mountSurvey(new Model(page.definition));   // the work order form

// #region sjs:IV.4.client
// Upload the scan; open the draft in the form for review
async function extract(file) {
  const body = new FormData();
  body.append("scan", file);
  const res = await fetch("/api/work-orders/extract", { method: "POST", body });
  const result = await res.json();
  if (!res.ok) throw new Error(result.error);
  survey.data = result.answers;   // a person checks and corrects, then saves as usual
  return result;                  // { answers, confidence, uniqueId }
}
// #endregion

// Demo only: highlight low-confidence answers and show the unique id
async function run(file) {
  try {
    for (const question of survey.getAllQuestions()) question.description = "";
    show("extract-status", `Extracting ${file.name}…`);
    const { confidence, uniqueId } = await extract(file);
    const flagged = confidence.filter((c) => c.flagged);
    for (const c of flagged) {
      const question = survey.getQuestionByName(c.fieldName);
      if (question) question.description = `⚠ Check this answer: read with ${Math.round(c.confidence * 100)}% confidence`;
    }
    show("extract-status", `Draft filled from ${file.name}. Unique id: ${uniqueId ?? "none found"}. ${flagged.length} answer(s) flagged for review. Nothing was saved.`);
    note(`Extracted ${file.name}: answers in the form's shape, ${flagged.length} flagged.`, "ok");
  } catch (e) {
    show("extract-status", e.message);
    note(e.message, "error");
  }
}
document.getElementById("sample-scan")?.addEventListener("click", async () => {
  const blob = await fetch("/shared/samples/work-order-scan.png").then((r) => r.blob());
  run(new File([blob], "work-order-scan.png", { type: "image/png" }));
});
document.getElementById("own-scan")?.addEventListener("change", (e) => e.target.files[0] && run(e.target.files[0]));
