// IV.3 Export forms to PDF on the server
import { note } from "./host.js";

// #region sjs:IV.3.client
// Ask your server for the filled form as a PDF
async function openPdf(id) {
  const tab = window.open("", "_blank");             // open the tab in the click itself: after an await, browsers block pop-ups
  const res = await fetch(`/api/claims/${id}/pdf`);
  if (!res.ok) {
    tab.close();
    throw new Error((await res.json()).error);
  }
  tab.location = URL.createObjectURL(await res.blob());
}
// #endregion

// Demo only
for (const button of document.querySelectorAll("[data-claim]")) {
  button.addEventListener("click", () => openPdf(button.dataset.claim)
    .then(() => note(`Claim #${button.dataset.claim}: the PDF opened in a new tab.`, "ok"))
    .catch((e) => note(`Claim #${button.dataset.claim}: ${e.message}`, "error")));
}
