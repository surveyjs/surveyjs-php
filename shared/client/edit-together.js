// III.5 Edit one form together
import { inviteLink, mountCreator, note, page, participantName, relayUrl, setStoredQuery } from "./host.js";
import { SurveyCreator } from "survey-creator-js";
import { CollaborationPlugin } from "survey-creator-core/collaboration";

const formId = "support";
const userName = participantName();
const creator = new SurveyCreator({ showTranslationTab: true });

// Saving is unchanged: saveSurveyFunc stores creator.JSON through III.1
creator.saveSurveyFunc = async (saveNo, callback) => {
  const res = await fetch(`/api/forms/${formId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(creator.JSON)
  });
  callback(saveNo, res.ok);
};

// #region sjs:III.5.client
// Every edit becomes a small JSON record; the plugin also captures and draws presence
const collab = new CollaborationPlugin(creator, { roomId: formId, getInviteLink: inviteLink });
creator.addPlugin("collaboration", collab);
// The relay runs as its own process, so its URL comes from the page config (EDIT_RELAY_URL)
const ws = new WebSocket(relayUrl("edit", `/ws/forms/${formId}?name=${encodeURIComponent(userName)}`));
let ready = false;                                   // send nothing before init is applied
const sendPresence = () => ready && ws.send(JSON.stringify({ type: "presence", state: collab.getState() }));
ws.addEventListener("message", (e) => {
  const msg = JSON.parse(e.data);
  if (msg.type === "init") {
    creator.JSON = msg.seed;                         // the saved definition (III.1)…
    if (msg.log.length) collab.apply(msg.log);       // …plus every edit since, in order
    ready = true;
    sendPresence();                                  // announce yourself to the others
  }
  if (msg.type === "record") collab.apply(msg.payload);
  if (msg.type === "presence-sync") collab.setPeers(msg.peers);
  if (msg.type === "presence") collab.upsertPeer(msg.peer);
  if (msg.type === "presence-leave") collab.removePeer(msg.clientId);
});
const sendRecord = (_, { record }) => ready && ws.send(JSON.stringify({ type: "append", payload: record }));
collab.onRecordAdded.add(sendRecord);
collab.onRecordChanged.add(sendRecord);              // a coalesced record re-sent as it grows
collab.onStateChanged.add(sendPresence);
// #endregion

mountCreator(creator);

// Demo only: connection status in Creator's participant bar and on the page
setStoredQuery({ room: formId });
collab.setStatus("connecting");
ws.addEventListener("message", (e) => { if (JSON.parse(e.data).type === "init") collab.setStatus("connected"); });
ws.addEventListener("open", () => note(`Connected to the edit relay as ${userName}.`, "ok"));
ws.addEventListener("close", () => {
  collab.setStatus("closed");
  note(page.user?.isEditor
    ? "The relay closed the connection. Is it running? Start it with `composer edit-relay` (or `php artisan relay:edit`), then reload."
    : "Only editors may join: the relay refuses viewers and signed-out users. Switch to Alice and reload.", "error");
});
