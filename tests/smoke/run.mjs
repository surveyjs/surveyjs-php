#!/usr/bin/env node
// Smoke test: for every step of the Server Integration page, two quick checks: the step page loads,
// and the step's main endpoint answers with the expected status. Zero dependencies, Node 22+.
// Unlike shared/contract-tests/run.mjs it needs no fresh database, so it also runs against a deployed
// copy (the weekly and release pipelines: .github/workflows/smoke.yml). Platform-neutral: the steps,
// titles and pages come from surveyjs-integration.json; the request shapes and error texts from run.mjs.
//
//   BASE_URL=https://surveyjs-php.demos.surveyjs.io node tests/smoke/run.mjs [--steps 1.1,1.5]
//
// Options (environment), as in shared/contract-tests/run.mjs:
//   BASE_URL             the app (default http://127.0.0.1:8000)
//   SERVICE=off|on       Section 4 service: off expects 503 "not running"
//   AI=off|stub          3.3 and 4.4: off expects 501 / 503, stub expects answers
//   VALIDATE_RESPONSES   true when the app runs 4.1 in place of 1.1
//   LINT_DEFINITIONS     true when the app runs 4.2 in place of the 3.1 PUT
//   RELAY_URL            ws://… of the 1.8 fill relay (without it, 1.8's endpoint check is skipped)
//   EDIT_RELAY_URL       ws://… of the 3.5 edit relay (without it, 3.5's endpoint check is skipped)
//   MANIFEST             path of surveyjs-integration.json (default: the one in the current directory)
//
// It sends demo_user=alice and keeps the demo_sid cookie the server sets, so a run against the hosted
// demo works in one visitor sandbox, pruned after 24 hours. One line per check; exit code 1 on any failure.
import { existsSync, readFileSync } from "node:fs";
import { randomBytes } from "node:crypto";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const BASE_URL = (process.env.BASE_URL || "http://127.0.0.1:8000").replace(/\/$/, "");
const SERVICE = process.env.SERVICE === "on" ? "on" : "off";
const AI = process.env.AI === "stub" ? "stub" : "off";
const flag = (name) => /^(1|true|on|yes)$/i.test(process.env[name] ?? "");
const VALIDATE = flag("VALIDATE_RESPONSES");
const LINT = flag("LINT_DEFINITIONS");
const RELAY_URL = process.env.RELAY_URL?.replace(/\/$/, "");
const EDIT_RELAY_URL = process.env.EDIT_RELAY_URL?.replace(/\/$/, "");

const manifestFile = process.env.MANIFEST
  ? resolve(process.env.MANIFEST)
  : [resolve("surveyjs-integration.json"), fileURLToPath(new URL("../../surveyjs-integration.json", import.meta.url))].find(existsSync);
const manifest = JSON.parse(readFileSync(manifestFile, "utf8"));
const readRepo = (path) => readFileSync(resolve(dirname(manifestFile), path));   // shared/samples/… next to the manifest

const stepsArg = process.argv.slice(2).join(" ").match(/--steps[= ]([^\s]+)/)?.[1];
const SELECTED = stepsArg ? stepsArg.split(",").map((s) => s.trim()).filter(Boolean) : Object.keys(manifest.steps).sort((a, b) => a.localeCompare(b, "en", { numeric: true }));   // JS lists the integer key "2" first
for (const s of SELECTED) if (!manifest.steps[s]) { console.error(`Unknown step ${s}`); process.exit(2); }

const unique = (prefix) => `${prefix}-${randomBytes(4).toString("hex")}`;

// ------------------------------------------------------------------------------------------------
// HTTP with a cookie jar: demo_sid comes from the server, demo_user is always alice

let sid = null;
const cookie = () => [sid && `demo_sid=${sid}`, "demo_user=alice"].filter(Boolean).join("; ");

async function http(method, path, { json, body } = {}) {
  const init = { method, headers: { Cookie: cookie() }, redirect: "manual", signal: AbortSignal.timeout(30000) };
  if (json !== undefined) {
    init.headers["Content-Type"] = "application/json";
    init.body = JSON.stringify(json);
  } else if (body !== undefined) {
    init.body = body;
  }
  const response = await fetch(BASE_URL + path, init);
  for (const line of response.headers.getSetCookie?.() ?? []) {
    const m = /^demo_sid=([^;]*)/.exec(line);
    if (m) sid = m[1] || null;
  }
  const bytes = Buffer.from(await response.arrayBuffer());
  let data;
  if ((response.headers.get("content-type") ?? "").includes("json") && bytes.length) {
    try { data = JSON.parse(bytes.toString("utf8")); } catch { data = undefined; }
  }
  return { status: response.status, headers: response.headers, data, text: () => bytes.toString("utf8") };
}

class Failure extends Error {}
class Skip extends Error {}
function expect(condition, message) { if (!condition) throw new Failure(message); }
function expectStatus(r, status, what) {
  const statuses = [].concat(status);
  expect(statuses.includes(r.status), `${what}: expected ${statuses.join(" or ")}, got ${r.status} ${r.text().slice(0, 200)}`);
}
function expectError(r, status, what, message) {
  expectStatus(r, status, what);
  expect(typeof r.data?.error === "string", `${what}: expected { error }, got ${r.text().slice(0, 200)}`);
  if (message) expect(r.data.error.includes(message), `${what}: expected error "${message}", got "${r.data.error}"`);
}
const isObject = (v) => v !== null && typeof v === "object" && !Array.isArray(v);
const skip = (why) => { throw new Skip(why); };

// The same WebSocket handshake as run.mjs: the cookie header carries demo_sid and demo_user
async function opens(url) {
  await http("GET", "/demo/stored/fill-together");   // takes demo_sid from the server in demo mode
  const ws = new WebSocket(url, { headers: { Cookie: cookie() } });
  await new Promise((resolveOpen, reject) => {
    const timer = setTimeout(() => reject(new Failure(`timed out connecting to ${url}`)), 5000);
    ws.addEventListener("open", () => { clearTimeout(timer); resolveOpen(); }, { once: true });
    ws.addEventListener("error", () => { clearTimeout(timer); reject(new Failure(`could not connect to ${url}`)); }, { once: true });
  });
  ws.close();
}

const definition = { elements: [{ type: "text", name: "q1" }] };
const claimData = { customer_email: "smoke@example.com", amount: 120, incident: "theft", details: "Smoke test" };
const files = () => {
  const form = new FormData();
  form.append("files[]", new Blob([Buffer.from("smoke\n")], { type: "text/plain" }), "smoke.txt");
  return form;
};

// ------------------------------------------------------------------------------------------------
// The main endpoint of each step: [what it calls, the check]

const ENDPOINTS = {
  "1.1": ["POST /api/responses", async () => {
    if (VALIDATE) skip("VALIDATE_RESPONSES replaces 1.1 with 4.1");
    const r = await http("POST", "/api/responses", { json: { formId: "contact", data: { email: "smoke@example.com" } } });
    expectStatus(r, 201, "POST /api/responses");
  }],
  "1.2": ["GET /claim, GET /api/forms/claim-request", async () => {
    expectStatus(await http("GET", "/claim"), 200, "GET /claim");
    const r = await http("GET", "/api/forms/claim-request");
    expectStatus(r, 200, "GET /api/forms/claim-request");
    expect(isObject(r.data?.definition), `expected a definition object, got ${r.text().slice(0, 200)}`);
  }],
  "1.3": ["PUT /api/progress/:formId", async () => {
    expectStatus(await http("PUT", `/api/progress/${unique("smoke")}`, { json: {} }), 204, "PUT /api/progress");
  }],
  "1.4": ["POST /api/files", async () => {
    const r = await http("POST", "/api/files", { body: files() });
    expectStatus(r, 200, "POST /api/files");
    expect(Array.isArray(r.data) && r.data.length === 1, `expected an array of 1 id, got ${r.text().slice(0, 200)}`);
  }],
  "1.5": ["GET /api/offices, GET /api/countries", async () => {
    for (const path of ["/api/offices?region=europe", "/api/countries"]) {
      const r = await http("GET", path);
      expectStatus(r, 200, `GET ${path}`);
      expect(Array.isArray(r.data) && r.data.length > 0, `GET ${path}: expected a non-empty array, got ${r.text().slice(0, 200)}`);
    }
  }],
  "1.6": ["GET /api/customers/exists", async () => {
    const r = await http("GET", "/api/customers/exists?email=taken%40example.com");
    expectStatus(r, 200, "GET /api/customers/exists");
    expect(r.data?.exists === true, `expected { "exists": true }, got ${r.text()}`);
  }],
  "1.7": ["POST /api/claims", async () => {
    const r = await http("POST", "/api/claims", { json: { data: { customer_email: "smoke@example.com", amount: 1 }, definitionVersion: "v1" } });
    expectStatus(r, 201, "POST /api/claims");
    expect(Number.isInteger(r.data?.id), `expected { id }, got ${r.text()}`);
  }],
  "1.8": ["WS /ws/rooms/:id", async () => {
    if (!RELAY_URL) skip("RELAY_URL is not set");
    await opens(`${RELAY_URL}/ws/rooms/${unique("smoke")}?name=Smoke`);
  }],
  "2": ["GET /api/responses", async () => {
    const r = await http("GET", "/api/responses?formId=feedback&limit=1");
    expectStatus(r, 200, "GET /api/responses");
    expect(Array.isArray(r.data), `expected an array, got ${r.text().slice(0, 200)}`);
  }],
  "3.1": ["PUT /api/forms/:id", async () => {
    const r = await http("PUT", `/api/forms/${unique("smoke")}`, { json: definition });
    if (!LINT) return expectStatus(r, 204, "PUT /api/forms");
    if (SERVICE === "off") return expectError(r, 503, "PUT /api/forms (LINT_DEFINITIONS)", "not running");
    expectStatus(r, [200, 204], "PUT /api/forms (LINT_DEFINITIONS)");
  }],
  "3.2": ["POST /api/files", async () => {
    expectStatus(await http("POST", "/api/files", { body: files() }), 200, "POST /api/files");
  }],
  "3.3": ["POST /api/translate", async () => {
    const r = await http("POST", "/api/translate", { json: { strings: ["Hello"], from: "en", to: "de" } });
    if (AI === "off") return expectError(r, 501, "POST /api/translate without a key", "Set AI_API_KEY to enable translation");
    expectStatus(r, 200, "POST /api/translate");
    expect(Array.isArray(r.data) && r.data.length === 1, `expected an array of 1, got ${r.text().slice(0, 200)}`);
  }],
  "3.4": ["PUT /api/variable-presets/:formId", async () => {
    expectStatus(await http("PUT", `/api/variable-presets/${unique("smoke")}`, { json: {} }), 204, "PUT /api/variable-presets");
  }],
  "3.5": ["WS /ws/forms/:id", async () => {
    if (!EDIT_RELAY_URL) skip("EDIT_RELAY_URL is not set");
    await opens(`${EDIT_RELAY_URL}/ws/forms/${unique("smoke")}?name=Smoke`);
  }],
  "4.1": ["POST /api/responses (validated)", async () => {
    if (!VALIDATE) skip("VALIDATE_RESPONSES is off");
    const r = await http("POST", "/api/responses", { json: { formId: "claim", data: claimData } });
    if (SERVICE === "off") return expectError(r, 503, "POST /api/responses", "not running");
    expectStatus(r, 201, "POST /api/responses");
  }],
  "4.2": ["PUT /api/forms/:id (linted)", async () => {
    if (!LINT) skip("LINT_DEFINITIONS is off");
    const r = await http("PUT", `/api/forms/${unique("smoke")}`, { json: definition });
    if (SERVICE === "off") return expectError(r, 503, "PUT /api/forms", "not running");
    expectStatus(r, 204, "PUT /api/forms");
  }],
  "4.3": ["GET /api/claims/:id/pdf", async () => {
    const claim = await http("POST", "/api/claims", { json: { data: claimData, definitionVersion: "v1" } });
    expectStatus(claim, 201, "POST /api/claims (the fixture)");
    const r = await http("GET", `/api/claims/${claim.data.id}/pdf`);
    if (SERVICE === "off") return expectError(r, 503, "GET /api/claims/:id/pdf", "not running");
    expectStatus(r, 200, "GET /api/claims/:id/pdf");
    expect((r.headers.get("content-type") ?? "").startsWith("application/pdf"), `Content-Type ${r.headers.get("content-type")}`);
  }],
  "4.4": ["POST /api/work-orders/extract", async () => {
    const form = new FormData();
    form.append("scan", new Blob([readRepo("shared/samples/work-order-scan.png")], { type: "image/png" }), "work-order-scan.png");
    const r = await http("POST", "/api/work-orders/extract", { body: form });
    if (SERVICE === "off") return expectError(r, 503, "POST /api/work-orders/extract", "not running");
    if (AI === "off") return expectError(r, 503, "POST /api/work-orders/extract", "The SurveyJS service has no AI provider configured");
    expectStatus(r, 200, "POST /api/work-orders/extract");
  }],
};

// The step page: 200, the title, and window.SURVEYJS_PAGE with the slug (as JSON or escaped by the template)
const escapeHtml = (s) => s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
async function pageLoads(step) {
  const r = await http("GET", step.page);
  expectStatus(r, 200, `GET ${step.page}`);
  const html = r.text();
  expect(html.includes(step.title) || html.includes(escapeHtml(step.title)), `the page has no title "${step.title}"`);
  expect(html.includes("window.SURVEYJS_PAGE"), "the page sets no window.SURVEYJS_PAGE");
  const slug = [`"slug":"${step.slug}"`, `"slug": "${step.slug}"`, `\\u0022slug\\u0022:\\u0022${step.slug}\\u0022`];
  expect(slug.some((s) => html.includes(s)), `window.SURVEYJS_PAGE has no slug "${step.slug}"`);
}

// ------------------------------------------------------------------------------------------------

console.log(`Smoke ${manifest.contract} against ${BASE_URL} (SERVICE=${SERVICE} AI=${AI}${VALIDATE ? " VALIDATE_RESPONSES" : ""}${LINT ? " LINT_DEFINITIONS" : ""})`);
const started = performance.now();
let passed = 0, failed = 0, skipped = 0;
async function run(name, fn) {
  try {
    await fn();
    console.log(`ok    ${name}`);
    passed++;
  } catch (e) {
    if (e instanceof Skip) {
      console.log(`skip  ${name}: ${e.message}`);
      skipped++;
    } else {
      console.log(`FAIL  ${name}\n      ${e instanceof Failure ? e.message : e.stack ?? e}`);
      failed++;
    }
  }
}

for (const id of SELECTED) {
  const step = manifest.steps[id];
  await run(`${id} page ${step.page}`, () => pageLoads(step));
  const [what, check] = ENDPOINTS[id] ?? [step.endpoints?.join(", ") ?? "endpoint", () => skip("no smoke request for this step")];
  await run(`${id} ${what}`, check);
}

console.log(`\n${passed} passed, ${failed} failed, ${skipped} skipped in ${((performance.now() - started) / 1000).toFixed(1)} s`);
process.exit(failed ? 1 : 0);
