// I.8 Fill one form together
import { inviteLink, mountSurvey, note, page, participantName, relayUrl, setStoredQuery, show } from "./host.js";

const recordId = new URLSearchParams(location.search).get("record") ?? String(page.record ?? "new");
const userName = participantName();

// Saving the finished response is still I.1: post survey.data on complete
const postResponse = async (sender) => {
  await fetch("/api/responses", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ formId: "job-sheet", data: sender.data })
  });
};

// #region sjs:I.8.client
import { Model } from "survey-core";
import { CollaborationPlugin } from "survey-core/collaboration";   // v3.1.1+

// One plugin on the model: it emits messages and applies incoming ones, and draws the
// participant bar, focus rings and cursors itself. The page only moves JSON frames.
// The relay runs as its own process, so its URL comes from the page config (RELAY_URL).
const ws = new WebSocket(relayUrl("fill", `/ws/rooms/${recordId}?name=${encodeURIComponent(userName)}`));
let collab;

ws.addEventListener("message", (e) => {
  const msg = JSON.parse(e.data);
  if (msg.type === "init" && !collab) {
    const survey = new Model(msg.seed);             // the server hands out the definition
    collab = new CollaborationPlugin(survey, { getInviteLink: inviteLink });
    collab.onEvent.add((_, { message }) => ws.send(JSON.stringify(message)));   // out: value, presence
    survey.onComplete.add(postResponse);
    mountSurvey(survey);
  }
  collab?.apply(msg);                                // in: init, value, peer, peer-left, as they arrive
});
// #endregion

// Demo only: connection status, and the stored panel follows this room
setStoredQuery({ room: recordId });
show("room", `Room ${recordId} · you are ${userName}`);
ws.addEventListener("open", () => note(`Connected to the relay as ${userName}.`, "ok"));
ws.addEventListener("close", () => {
  collab?.apply({ type: "status", status: "closed" });
  note(page.user
    ? "The relay closed the connection. Is it running? Start it with `composer relay` (or `php artisan relay:fill`), then reload."
    : "The relay refuses signed-out visitors. Pick a demo user and reload.", "error");
});
