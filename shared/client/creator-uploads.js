// III.2 Upload images and files from Creator
import { mountCreator, note } from "./host.js";
import { SurveyCreator } from "survey-creator-js";

const formId = "support";

// Load and save as in III.1; saving automatically, so the stored panel shows each change
const creator = new SurveyCreator({ showTranslationTab: true });
creator.JSON = (await fetch(`/api/forms/${formId}`).then(r => r.json())).definition;
creator.autoSaveEnabled = true;
creator.saveSurveyFunc = async (saveNo, callback) => {
  const res = await fetch(`/api/forms/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(creator.JSON)
  });
  callback(saveNo, res.ok);
};

// #region sjs:III.2.client
// Images and logos added in Creator: store them, keep only the URL in the definition
creator.onUploadFile.add(async (_, options) => {
  const body = new FormData();
  options.files.forEach(file => body.append("files[]", file));   // the I.4 endpoint; "files[]" for PHP
  const [id] = await fetch("/api/files", { method: "POST", body }).then(r => r.json());
  options.callback("success", `/api/files/${id}`);                // I.4 returns ids: the definition keeps a URL
});
// #endregion

mountCreator(creator);

// Demo only
creator.onUploadFile.add(() => setTimeout(() => note("The logo is stored by I.4; the definition keeps its URL, not base64. See the stored panel.", "ok"), 1500));
