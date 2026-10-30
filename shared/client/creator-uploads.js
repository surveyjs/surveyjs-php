// Upload Files
import { mountCreator, note } from "./host.js";
import { SurveyCreator } from "survey-creator-js";

const formId = "support";

// Load and save as in creator-load-save; saving automatically, so the stored panel shows each change
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

// #region sjs:creator-uploads.client
// Images and logos added in Creator: store them, keep only the URL in the definition
creator.onUploadFile.add(async (_, options) => {
  const body = new FormData();
  options.files.forEach(file => body.append("files[]", file));   // POST /api/files; "files[]" for PHP
  const [id] = await fetch("/api/files", { method: "POST", body }).then(r => r.json());
  options.callback("success", `/api/files/${id}`);                // the endpoint returns ids: the definition keeps a URL
});
// #endregion

mountCreator(creator);

// Demo only
creator.onUploadFile.add(() => setTimeout(() => note("The logo is stored by POST /api/files; the JSON schema keeps its URL, not Base64. See the Data tab.", "ok"), 1500));
