// Demo only: everything around the examples on a step page. Mounting the form or Creator, the
// requests log, the "What the server stored" panel and the demo-user switch. None of this is part
// of an integration, and the step code doesn't depend on it beyond mountSurvey() and mountCreator().
//
// It finds page elements by id, data- attributes and <template> slots and never uses CSS classes,
// so each platform styles its own templates. Step modules import this file first.
import { setLicenseKey } from "survey-core";
import { renderSurvey } from "survey-js-ui";

/** The page config the server renders into window.SURVEYJS_PAGE. */
export const page = window.SURVEYJS_PAGE ?? {};

if (page.licenseKey) setLicenseKey(page.licenseKey);

// ---------------------------------------------------------------------------------------------
// Mounting

export function mountSurvey(survey, id = "form") {
  const element = document.getElementById(id);
  element.replaceChildren();
  renderSurvey(survey, element);
  return survey;
}

export function mountCreator(creator, id = "creator") {
  creator.render(document.getElementById(id));
  return creator;
}

// ---------------------------------------------------------------------------------------------
// Requests log: wraps fetch, XMLHttpRequest (choicesByUrl uses XHR) and WebSocket

const REQUEST_HEADERS = ["authorization"];
const RESPONSE_HEADERS = ["x-demo-cache", "cache-control", "content-type"];
const storedListeners = [];
let storedQuery = {};
let refreshTimer;

/** The demo's own requests (stored panel, shared files, other origins) are hidden by default. */
function isDemoRequest(url) {
  const u = new URL(url, location.href);
  return u.origin !== location.origin || u.pathname.startsWith("/demo/") || u.pathname.startsWith("/shared/");
}

function fill(template, values) {
  const tpl = document.getElementById(template);
  if (!tpl) return null;
  const node = tpl.content.firstElementChild.cloneNode(true);
  for (const [key, value] of Object.entries(values)) {
    const slots = node.matches(`[data-field="${key}"]`) ? [node] : node.querySelectorAll(`[data-field="${key}"]`);
    for (const slot of slots) slot.textContent = value ?? "";
  }
  return node;
}

function startEntry(method, url, requestHeaders) {
  const started = performance.now();
  const u = new URL(url, location.href);
  const demo = isDemoRequest(url);
  const shown = u.origin === location.origin ? u.pathname + u.search : u.href;
  const headers = REQUEST_HEADERS.filter((h) => requestHeaders?.[h]).map((h) => `${h.replace(/\b\w/g, (c) => c.toUpperCase())}: ${requestHeaders[h]}`);
  const row = fill("request-row", { method: method.toUpperCase(), url: shown, status: "…", time: "", headers: headers.join(" · ") });
  const list = document.getElementById("requests");
  if (row && list) {
    row.dataset.demo = String(demo);
    row.dataset.state = "pending";
    row.hidden = demo && !document.getElementById("show-demo-requests")?.checked;
    list.prepend(row);
    document.getElementById("requests-empty")?.setAttribute("hidden", "");
  }
  return {
    finish(status, getHeader) {
      const extra = RESPONSE_HEADERS
        .map((h) => [h, getHeader?.(h)])
        .filter(([h, v]) => v && !(h === "content-type" && /json|html/.test(v)) && !(h === "cache-control" && !/max-age=[1-9]/.test(v)))
        .map(([h, v]) => `${h.replace(/(^|-)\w/g, (c) => c.toUpperCase())}: ${v}`);
      if (row) {
        row.querySelector('[data-field="status"]').textContent = status === 0 ? "failed" : String(status);
        row.querySelector('[data-field="time"]').textContent = `${Math.round(performance.now() - started)} ms`;
        row.querySelector('[data-field="headers"]').textContent = [...headers, ...extra].join(" · ");
        row.dataset.state = status >= 200 && status < 400 ? "ok" : "error";
      }
      if (!demo) scheduleStoredRefresh();
    },
  };
}

/** Add a line to the requests log for something that isn't an HTTP request (a WebSocket, a download). */
export function logEvent(method, url, status, getHeader) {
  startEntry(method, url, {}).finish(status, getHeader);
}

const nativeFetch = window.fetch.bind(window);
window.fetch = async (input, init = {}) => {
  const url = typeof input === "string" || input instanceof URL ? String(input) : input.url;
  const method = init.method ?? (typeof input === "object" && "method" in input ? input.method : "GET");
  const headers = Object.fromEntries(new Headers(init.headers ?? (input instanceof Request ? input.headers : undefined)));
  const entry = startEntry(method, url, headers);
  try {
    const response = await nativeFetch(input, init);
    entry.finish(response.status, (h) => response.headers.get(h));
    return response;
  } catch (error) {
    entry.finish(0);
    throw error;
  }
};

const xhrOpen = XMLHttpRequest.prototype.open;
const xhrSetHeader = XMLHttpRequest.prototype.setRequestHeader;
const xhrSend = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.open = function (method, url, ...rest) {
  this.__demo = { method, url: String(url), headers: {} };
  return xhrOpen.call(this, method, url, ...rest);
};
XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
  if (this.__demo) this.__demo.headers[name.toLowerCase()] = value;
  return xhrSetHeader.call(this, name, value);
};
XMLHttpRequest.prototype.send = function (...args) {
  if (this.__demo) {
    const entry = startEntry(this.__demo.method, this.__demo.url, this.__demo.headers);
    this.addEventListener("loadend", () => entry.finish(this.status, (h) => this.getResponseHeader(h)), { once: true });
  }
  return xhrSend.apply(this, args);
};

const NativeWebSocket = window.WebSocket;
window.WebSocket = class extends NativeWebSocket {
  constructor(url, protocols) {
    super(url, protocols);
    const shown = String(url).replace(/^wss?:\/\/[^/]+/, "");
    this.addEventListener("open", () => logEvent("WS", shown, 101), { once: true });
    this.addEventListener("close", (e) => logEvent("WS", `${shown} (closed)`, e.code === 1000 || e.code === 1005 ? 200 : 0), { once: true });
  }
};

document.getElementById("show-demo-requests")?.addEventListener("change", (e) => {
  for (const row of document.querySelectorAll('#requests [data-demo="true"]')) row.hidden = !e.target.checked;
});
document.getElementById("clear-requests")?.addEventListener("click", () => {
  document.getElementById("requests")?.replaceChildren();
  document.getElementById("requests-empty")?.removeAttribute("hidden");
});

// ---------------------------------------------------------------------------------------------
// What the server stored: refreshed 300 ms after a non-demo request completes

function scheduleStoredRefresh() {
  clearTimeout(refreshTimer);
  refreshTimer = setTimeout(refreshStored, 300);
}

/** Extra query parameters for GET /demo/stored/:slug, e.g. { id: 12 } or { from: "2026-09-01" }. */
export function setStoredQuery(query) {
  storedQuery = query;
  scheduleStoredRefresh();
}

/** Called with the stored JSON after each refresh, for step-specific checks. */
export function onStored(callback) {
  storedListeners.push(callback);
}

export async function refreshStored() {
  const panel = document.getElementById("stored");
  if (!panel || !page.slug) return;
  const query = new URLSearchParams(Object.entries(storedQuery).filter(([, v]) => v !== undefined && v !== null && v !== ""));
  const response = await fetch(`/demo/stored/${page.slug}${query.size ? `?${query}` : ""}`);
  if (!response.ok) return;
  const stored = await response.json();
  panel.replaceChildren(...Object.entries(stored).map(([title, value]) =>
    fill("stored-section", { title, json: typeof value === "string" ? value : JSON.stringify(value, null, 2) })).filter(Boolean));
  const status = document.getElementById("stored-updated");
  if (status) status.textContent = `updated ${new Date().toLocaleTimeString()}`;
  for (const listener of storedListeners) listener(stored);
}

// ---------------------------------------------------------------------------------------------
// Notes, the demo-user switch, relay URLs and invite links

/** Show a short message next to the form ("identical: yes", "403 Forbidden"…). kind: info | ok | error */
export function note(text, kind = "info") {
  const list = document.getElementById("notes");
  const item = fill("note", { text });
  if (!list || !item) return;
  item.dataset.kind = kind;
  list.prepend(item);
  list.hidden = false;
}

/** Replace the contents of an element marked data-show="<name>" with text. */
export function show(name, text) {
  for (const el of document.querySelectorAll(`[data-show="${name}"]`)) el.textContent = text;
}

const userSwitch = document.getElementById("demo-user");
if (userSwitch) {
  userSwitch.value = page.user?.key ?? "";
  userSwitch.addEventListener("change", () => {
    document.cookie = userSwitch.value
      ? `demo_user=${userSwitch.value}; path=/; max-age=31536000; samesite=lax`
      : "demo_user=; path=/; max-age=0; samesite=lax";
    location.reload();
  });
}

/** Display name for collaboration: the demo user's name plus a per-window number, so two windows differ. */
const windowNumber = Math.floor(Math.random() * 90) + 10;
export function participantName() {
  return `${page.user?.name ?? "Guest"} · ${windowNumber}`;
}

/** The relays run as their own processes: RELAY_URL / EDIT_RELAY_URL, or this host on their ports. */
export function relayUrl(relay, path) {
  const config = relay === "edit" ? page.relay?.edit : page.relay?.fill;
  const base = config?.url || `${location.protocol === "https:" ? "wss" : "ws"}://${location.hostname}:${config?.port}`;
  return base.replace(/\/$/, "") + path;
}

/** The page's own URL; in demo mode with ?join=<sandbox id>, so a collaborator shares this sandbox. */
export function inviteLink() {
  const url = new URL(location.href);
  if (page.sandboxId) url.searchParams.set("join", page.sandboxId);
  else url.searchParams.delete("join");
  return url.href;
}

for (const link of document.querySelectorAll("[data-invite-link]")) {
  if ("value" in link && link.tagName === "INPUT") link.value = inviteLink();
  else link.href = inviteLink();
}
document.querySelector("[data-copy-invite]")?.addEventListener("click", async (e) => {
  await navigator.clipboard?.writeText(inviteLink());
  e.currentTarget.dataset.copied = "true";
});

refreshStored();
