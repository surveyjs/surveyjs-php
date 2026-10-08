// I.4 Store files outside the response
import { mountSurvey, note, onRequest, page, show } from "./host.js";
import { Model } from "survey-core";

// In the definition: { "type": "file", "name": "receipt", "storeDataAsText": false, "allowMultiple": true }
const survey = new Model(page.definition);

// #region sjs:I.4.client
// Upload: your server stores the files and returns an id (or a URL) for each
survey.onUploadFiles.add(async (_, options) => {
  const body = new FormData();
  options.files.forEach(file => body.append("files[]", file));   // "files[]": PHP keeps only the last of several "files"
  const ids = await fetch("/api/files", { method: "POST", body }).then(r => r.json());
  // The response stores these ids, not the file contents
  options.callback(options.files.map((file, i) => ({ file, content: ids[i] })));
});

// Preview: only when the stored value is not a public URL the browser can open
survey.onDownloadFile.add(async (_, options) => {
  const res = await fetch(`/api/files/${encodeURIComponent(options.content)}`);
  if (!res.ok) return options.callback("error");
  const reader = new FileReader();
  reader.onload = () => options.callback("success", reader.result);   // base64 data the form shows
  reader.readAsDataURL(await res.blob());
});
// #endregion

// Saving the finished response is I.1
survey.onComplete.add(async (sender) => {
  await fetch("/api/responses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ formId: "expenses", data: sender.data })
  });
});

mountSurvey(survey);

// Demo only: what the response would weigh with storeDataAsText: true (computed here, never posted)
const size = (bytes) => (bytes > 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(2)} MB` : `${(bytes / 1024).toFixed(1)} KB`);
const asDataUrl = (file) => new Promise((resolve) => { const r = new FileReader(); r.onload = () => resolve(r.result); r.readAsDataURL(file); });
survey.onUploadFiles.add(async (_, options) => {
  const embedded = await Promise.all(options.files.map(async (file) => ({ name: file.name, type: file.type, content: await asDataUrl(file) })));
  const withBase64 = JSON.stringify({ ...survey.data, receipt: embedded }).length;
  setTimeout(() => {
    const withIds = JSON.stringify(survey.data).length;
    show("sizes", `With storeDataAsText: true the response would be ${size(withBase64)}; with ids it is ${size(withIds)}.`);
    note(`The response keeps ${options.files.length} id(s), not the file contents: ${size(withIds)} instead of ${size(withBase64)}.`, "ok");
  }, 500);
});
onRequest(({ method, url, status }) => {
  if (method === "GET" && url.startsWith("/api/files/") && status === 404) {
    note("GET /api/files/:id answered 404, so the preview can't load.", "error");
  }
});
