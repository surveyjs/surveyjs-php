#!/usr/bin/env node
// Contract test for server-integration-v4: calls every endpoint of the Server Integration page
// and checks the documented shape. Zero dependencies, Node 22+. The same file in every platform repo.
//
//   BASE_URL=http://127.0.0.1:8000 node shared/contract-tests/run.mjs [--steps I.1,I.5]
//
// Options (environment):
//   BASE_URL             the app (default http://127.0.0.1:8000)
//   SERVICE=off|on       Section IV service: off expects 503 "not running", on expects real answers
//   AI=off|stub          III.3 and IV.4: off expects 501 / 503, stub expects ai-stub.mjs answers
//   VALIDATE_RESPONSES   true when the app runs IV.1 in place of I.1
//   LINT_DEFINITIONS     true when the app runs IV.2 in place of the III.1 PUT
//   DEMO_MODE            true when the app and relays run visitor sandboxes
//   RELAY_URL            ws://… of the I.8 fill relay (enables its checks)
//   EDIT_RELAY_URL       ws://… of the III.5 edit relay (enables its checks)
//
// Step checks read back through GET /demo/stored/:slug. Cross-step checks run only when every
// step they involve is selected. One line per check; the exit code is non-zero on any failure.
import { readFileSync } from "node:fs";
import { randomBytes } from "node:crypto";
import { isDeepStrictEqual } from "node:util";

const BASE_URL = (process.env.BASE_URL ?? "http://127.0.0.1:8000").replace(/\/$/, "");
const SERVICE = process.env.SERVICE === "on" ? "on" : "off";
const AI = process.env.AI === "stub" ? "stub" : "off";
const flag = (name) => /^(1|true|on|yes)$/i.test(process.env[name] ?? "");
const VALIDATE = flag("VALIDATE_RESPONSES");
const LINT = flag("LINT_DEFINITIONS");
const DEMO = flag("DEMO_MODE");
const RELAY_URL = process.env.RELAY_URL?.replace(/\/$/, "");
const EDIT_RELAY_URL = process.env.EDIT_RELAY_URL?.replace(/\/$/, "");

const ALL_STEPS = ["I.1", "I.2", "I.3", "I.4", "I.5", "I.6", "I.7", "I.8", "II", "III.1", "III.2", "III.3", "III.4", "III.5", "IV.1", "IV.2", "IV.3", "IV.4"];
const SLUGS = {
  "I.1": "save-response", "I.2": "load-definition-variables-data", "I.3": "resume-progress", "I.4": "store-files",
  "I.5": "choices-from-web", "I.6": "async-functions", "I.7": "relational-storage", "I.8": "fill-together",
  "II": "dashboard", "III.1": "creator-load-save", "III.3": "ai-translation", "III.4": "variable-presets",
  "III.5": "edit-together", "IV.1": "validate-response", "IV.2": "lint-definition", "IV.3": "server-pdf", "IV.4": "extract-from-paper",
};

const stepsArg = process.argv.slice(2).join(" ").match(/--steps[= ]([^\s]+)/)?.[1];
const SELECTED = new Set(stepsArg ? stepsArg.split(",").map((s) => s.trim()).filter(Boolean) : ALL_STEPS);
for (const s of SELECTED) if (!ALL_STEPS.includes(s)) { console.error(`Unknown step ${s}`); process.exit(2); }

const shared = new URL("../", import.meta.url);
const readShared = (path) => readFileSync(new URL(path, shared));
const ROUNDTRIP = { a: "  padded  ", b: "", c: null, d: {}, e: [{}], f: [], g: 1.5, h: "naïve — 東京", i: { j: { k: [1, "x", true] } } };
const unique = (prefix) => `${prefix}-${randomBytes(4).toString("hex")}`;

// ------------------------------------------------------------------------------------------------
// HTTP with a cookie jar: demo_sid comes from the server (never invented), demo_user is set here

class Jar {
  sid = null;
  absorb(response) {
    for (const line of response.headers.getSetCookie?.() ?? []) {
      const m = /^demo_sid=([^;]*)/.exec(line);
      if (m) this.sid = m[1] || null;
    }
  }
  header(user) {
    const parts = [];
    if (this.sid) parts.push(`demo_sid=${this.sid}`);
    if (user) parts.push(`demo_user=${user}`);
    return parts.join("; ");
  }
}
const jar = new Jar();

async function http(method, path, { body, json, user = "alice", headers = {}, jar: j = jar } = {}) {
  const init = { method, headers: { ...headers }, redirect: "manual" };
  const cookie = j.header(user);
  if (cookie) init.headers.Cookie = cookie;
  if (json !== undefined) {
    init.headers["Content-Type"] = "application/json";
    init.body = typeof json === "string" ? json : JSON.stringify(json);
  } else if (body !== undefined) {
    init.body = body;
  }
  const response = await fetch(BASE_URL + path, init);
  j.absorb(response);
  const bytes = Buffer.from(await response.arrayBuffer());
  const type = response.headers.get("content-type") ?? "";
  let data;
  if (type.includes("json") && bytes.length) {
    try { data = JSON.parse(bytes.toString("utf8")); } catch { data = undefined; }
  }
  return { status: response.status, headers: response.headers, bytes, data, text: () => bytes.toString("utf8") };
}

const stored = async (slug, query = {}) => {
  const r = await http("GET", `/demo/stored/${slug}?${new URLSearchParams(query)}`);
  expect(r.status === 200, `GET /demo/stored/${slug} answered ${r.status}`);
  return r.data;
};

// ------------------------------------------------------------------------------------------------
// Checks

class Failure extends Error {}
function expect(condition, message) { if (!condition) throw new Failure(message); }
function expectStatus(r, status, what) {
  const statuses = [].concat(status);
  expect(statuses.includes(r.status), `${what}: expected ${statuses.join(" or ")}, got ${r.status} ${r.text().slice(0, 300)}`);
}
function expectError(r, status, what, message) {
  expectStatus(r, status, what);
  expect(r.data && typeof r.data.error === "string", `${what}: expected { error }, got ${r.text().slice(0, 200)}`);
  if (message) expect(r.data.error.includes(message), `${what}: expected error "${message}", got "${r.data.error}"`);
}
function expectEqual(actual, expected, what) {
  expect(isDeepStrictEqual(actual, expected), `${what}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
}
const isObject = (v) => v !== null && typeof v === "object" && !Array.isArray(v);

const checks = [];
const check = (name, steps, fn, { skip } = {}) => checks.push({ name, steps: [].concat(steps), fn, skip });

// ---------- I.1
check("I.1 POST /api/responses stores the JSON exactly as it arrives", "I.1", async () => {
  const r = await http("POST", "/api/responses", { json: { formId: "contract", data: ROUNDTRIP } });
  expectStatus(r, 201, "POST /api/responses");
  expect(Number.isInteger(r.data?.id), `expected { id: number }, got ${r.text()}`);
  const s = await stored("save-response", { id: r.data.id });
  expectEqual(s.responses?.[0]?.data, ROUNDTRIP, "stored data");
}, { skip: VALIDATE && "VALIDATE_RESPONSES replaces I.1 with IV.1" });

check("I.1 bad JSON answers 400 { error }", "I.1", async () => {
  expectError(await http("POST", "/api/responses", { json: "{not json" }), 400, "POST /api/responses with bad JSON");
});

// ---------- I.2
check("I.2 GET /api/forms/:id returns { definition, variables, data }", "I.2", async () => {
  const r = await http("GET", "/api/forms/claim");
  expectStatus(r, 200, "GET /api/forms/claim");
  expect(isObject(r.data?.definition) && Array.isArray(r.data.definition.elements ?? r.data.definition.pages), "definition is a survey JSON object");
  expect(typeof r.data.variables?.user?.name === "string", "variables.user.name is a string");
  expectEqual(r.data.variables.user.plan, "premium", "Alice's variables.user.plan");
  expect(isObject(r.data.data) && Object.keys(r.data.data).length === 0, `data is {} without a record, got ${JSON.stringify(r.data.data)}`);
  const bob = await http("GET", "/api/forms/claim", { user: "bob" });
  expectEqual(bob.data?.variables?.user?.plan, "basic", "Bob's variables.user.plan");
});

check("I.2 ?record= returns the stored response as data", "I.2", async () => {
  const s = await stored("load-definition-variables-data");
  const row = s.responses?.[0];
  expect(row && Number.isInteger(row.id), "the stored panel lists a claim response");
  const r = await http("GET", `/api/forms/claim?record=${row.id}`);
  expectStatus(r, 200, "GET /api/forms/claim?record=");
  expectEqual(r.data?.data, row.data, "data for the record");
});

check("I.2 an unknown form answers 404 { error }", "I.2", async () => {
  expectError(await http("GET", `/api/forms/${unique("missing")}`), 404, "GET /api/forms/<unknown>");
});

// ---------- I.3
check("I.3 progress: 404 before, 204 on PUT, stored as-is, the same document on GET", "I.3", async () => {
  const key = unique("contract");
  expectError(await http("GET", `/api/progress/${key}`), 404, "GET /api/progress before any PUT");
  const doc = { data: ROUNDTRIP, uiState: { currentPageName: "page2", activeElementName: "q1" } };
  expectStatus(await http("PUT", `/api/progress/${key}`, { json: doc }), 204, "PUT /api/progress");
  const s = await stored("resume-progress", { key });
  expectEqual(s.progress?.json, doc, "stored progress");
  const r = await http("GET", `/api/progress/${key}`);
  expectStatus(r, 200, "GET /api/progress");
  expectEqual(r.data, doc, "GET /api/progress after PUT");
});

// ---------- I.4
check("I.4 upload two files, download the same bytes and Content-Type", "I.4", async () => {
  const one = Buffer.from("first file\n");
  const two = readShared("samples/work-order-scan.png");
  const form = new FormData();
  form.append("files[]", new Blob([one], { type: "text/plain" }), "one.txt");
  form.append("files[]", new Blob([two], { type: "image/png" }), "two.png");
  const r = await http("POST", "/api/files", { body: form });
  expectStatus(r, 200, "POST /api/files");
  expect(Array.isArray(r.data) && r.data.length === 2 && r.data.every((id) => typeof id === "string" && id), `expected two ids, got ${r.text()}`);
  for (const [id, bytes, type] of [[r.data[0], one, "text/plain"], [r.data[1], two, "image/png"]]) {
    const d = await http("GET", `/api/files/${encodeURIComponent(id)}`);
    expectStatus(d, 200, "GET /api/files/:id");
    expect(d.bytes.equals(bytes), "downloaded bytes equal the upload");
    expect((d.headers.get("content-type") ?? "").startsWith(type), `Content-Type ${d.headers.get("content-type")} for ${type}`);
  }
  const s = await stored("store-files");
  expect(JSON.stringify(s).includes(r.data[0]), "the stored panel lists the uploaded file");
  expectError(await http("GET", `/api/files/${encodeURIComponent(r.data[0])}`, { user: null }), 404, "GET /api/files/:id signed out");
  expectError(await http("GET", `/api/files/${unique("missing")}`), 404, "GET /api/files/<unknown>");
});

// ---------- I.5
check("I.5 GET /api/offices?region= returns [{ id, name }] ordered by name", "I.5", async () => {
  const r = await http("GET", "/api/offices?region=europe");
  expectStatus(r, 200, "GET /api/offices");
  expect(Array.isArray(r.data) && r.data.length > 0 && r.data.every((o) => "id" in o && typeof o.name === "string"), `expected [{ id, name }], got ${r.text()}`);
  const names = r.data.map((o) => o.name);
  expectEqual(names, [...names].sort((a, b) => a.localeCompare(b)), "office names in order");
  const none = await http("GET", "/api/offices?region=nowhere");
  expectEqual(none.data, [], "offices of an unknown region");
});

check("I.5 GET /api/countries returns at least 100 { code, name }, cached for a day", "I.5", async () => {
  const r = await http("GET", "/api/countries");
  expectStatus(r, 200, "GET /api/countries");
  expect(Array.isArray(r.data) && r.data.length >= 100, `expected at least 100 countries, got ${r.data?.length}`);
  expect(r.data.every((c) => typeof c.code === "string" && typeof c.name === "string"), "every country has code and name");
  expect(/max-age=86400/.test(r.headers.get("cache-control") ?? ""), `Cache-Control: ${r.headers.get("cache-control")}`);
});

// ---------- I.6
check("I.6 GET /api/customers/exists trims and lower-cases the email", "I.6", async () => {
  expectEqual((await http("GET", "/api/customers/exists?email=taken%40example.com")).data, { exists: true }, "seeded email");
  expectEqual((await http("GET", `/api/customers/exists?email=${encodeURIComponent("  Taken@Example.COM ")}`)).data, { exists: true }, "seeded email, padded and upper-case");
  expectEqual((await http("GET", `/api/customers/exists?email=${unique("new")}%40example.com`)).data, { exists: false }, "unknown email");
});

check("I.6 GET /api/shipping uses the longest matching postcode prefix, null when none matches", "I.6", async () => {
  for (const [postcode, price] of [["10115", 4.9], ["10999", 3.5], ["80331", 5.9], ["SW1A 1AA", 9.5], ["99999", null]]) {
    const r = await http("GET", `/api/shipping?postcode=${encodeURIComponent(postcode)}`);
    expectStatus(r, 200, "GET /api/shipping");
    expect(price === null ? r.data?.price === null : Math.abs(Number(r.data?.price) - price) < 1e-9, `price for ${postcode}: expected ${price}, got ${r.text()}`);
  }
});

// ---------- I.7
check("I.7 POST /api/claims keeps the JSON and the version, and fills the columns", "I.7", async () => {
  const data = { ...ROUNDTRIP, customer_email: "contract@example.com", amount: 1840.5, incident: "theft" };
  const r = await http("POST", "/api/claims", { json: { data, definitionVersion: "v2" } });
  expectStatus(r, 201, "POST /api/claims");
  expect(Number.isInteger(r.data?.id), `expected { id }, got ${r.text()}`);
  const s = await stored("relational-storage", { id: r.data.id });
  expectEqual(s.responses?.[0]?.data, data, "stored claim response");
  expectEqual(s.responses?.[0]?.definition_version, "v2", "definition_version");
  expectEqual(s.responses?.[0]?.form_id, "claim", "form_id");
  expectEqual(s.claims?.[0]?.customer_email, "contract@example.com", "claims.customer_email");
  expect(Number(s.claims?.[0]?.amount) === 1840.5, `claims.amount: ${s.claims?.[0]?.amount}`);
});

// ---------- II
check("II GET /api/responses returns the stored documents, filtered and paged", "II", async () => {
  const all = await http("GET", "/api/responses?formId=feedback");
  expectStatus(all, 200, "GET /api/responses");
  expect(Array.isArray(all.data) && all.data.length >= 150 && all.data.every(isObject), `expected the seeded feedback responses, got ${all.data?.length}`);
  expectEqual((await http("GET", "/api/responses?formId=feedback&from=2999-01-01")).data, [], "from in the future");
  const page = await http("GET", "/api/responses?formId=feedback&limit=5&offset=2");
  expectEqual(page.data, all.data.slice(2, 7), "limit=5&offset=2");
  const since = new Date(Date.now() - 30 * 864e5).toISOString().slice(0, 10);
  const recent = await http("GET", `/api/responses?formId=feedback&from=${since}`);
  expect(recent.data.length > 0 && recent.data.length < all.data.length, `from=${since} filters (${recent.data.length} of ${all.data.length})`);
});

check("I.1 → II: a posted response comes back as stored", ["I.1", "II"], async () => {
  const formId = unique("contract");
  expectStatus(await http("POST", "/api/responses", { json: { formId, data: ROUNDTRIP } }), 201, "POST /api/responses");
  expectEqual((await http("GET", `/api/responses?formId=${formId}`)).data, [ROUNDTRIP], "GET /api/responses");
}, { skip: VALIDATE && "VALIDATE_RESPONSES replaces I.1 with IV.1" });

// ---------- III.1
check("III.1 PUT /api/forms/:id: 403 for Bob and signed out, saved as-is for Alice", "III.1", async () => {
  const key = unique("contract");
  expectError(await http("PUT", `/api/forms/${key}`, { json: ROUNDTRIP, user: "bob" }), 403, "PUT as Bob");
  expectError(await http("PUT", `/api/forms/${key}`, { json: ROUNDTRIP, user: null }), 403, "PUT signed out");
  const definition = { title: "Contract", elements: [{ type: "text", name: "q1", title: "  padded  " }], pages: undefined };
  delete definition.pages;
  expectStatus(await http("PUT", `/api/forms/${key}`, { json: definition }), 204, "PUT as Alice");
  const s = await stored("creator-load-save", { key });
  expectEqual(s.forms?.json, definition, "stored definition");
  const support = await http("GET", "/api/forms/support");
  expect(isObject(support.data?.definition), "GET /api/forms/support has .definition");
});

check("III.1 → I.2: a saved definition comes back as .definition", ["III.1", "I.2"], async () => {
  const key = unique("contract");
  expectStatus(await http("PUT", `/api/forms/${key}`, { json: ROUNDTRIP }), LINT ? [200, 204] : 204, "PUT /api/forms");
  expectEqual((await http("GET", `/api/forms/${key}`)).data?.definition, ROUNDTRIP, "GET /api/forms .definition");
});

// ---------- III.2
check("III.2 → I.4: a Creator upload is served from the URL kept in the definition", ["III.2", "I.4", "III.1"], async () => {
  const form = new FormData();
  form.append("files[]", new Blob([readShared("samples/work-order-scan.png")], { type: "image/png" }), "logo.png");
  const [id] = (await http("POST", "/api/files", { body: form })).data ?? [];
  expect(typeof id === "string", "POST /api/files returned an id");
  const key = unique("contract");
  const definition = { logo: `/api/files/${id}`, elements: [{ type: "text", name: "q1" }] };
  expectStatus(await http("PUT", `/api/forms/${key}`, { json: definition }), LINT ? [200, 204] : 204, "PUT /api/forms with a logo");
  const logo = (await http("GET", `/api/forms/${key}`)).data?.definition?.logo;
  expectEqual(logo, `/api/files/${id}`, "definition.logo");
  const d = await http("GET", logo);
  expectStatus(d, 200, "GET the logo URL");
  expect(d.bytes.equals(readShared("samples/work-order-scan.png")), "logo bytes");
});

// ---------- III.3
check(`III.3 POST /api/translate (AI=${AI})`, "III.3", async () => {
  const r = await http("POST", "/api/translate", { json: { strings: ["Hello", "Thank you, naïve user"], from: "en", to: "de" } });
  if (AI === "off") return expectError(r, 501, "POST /api/translate without a key", "Set AI_API_KEY to enable translation");
  expectStatus(r, 200, "POST /api/translate");
  expectEqual(r.data, ["[de] Hello", "[de] Thank you, naïve user"], "translated strings in order");
});

// ---------- III.4
check("III.4 variable presets: GET, 404, 403 for Bob, PUT stored as-is", "III.4", async () => {
  const seeded = await http("GET", "/api/variable-presets/support");
  expectStatus(seeded, 200, "GET /api/variable-presets/support");
  expect(Array.isArray(seeded.data) && seeded.data.length >= 2, "basic and premium presets are seeded");
  const key = unique("contract");
  expectError(await http("GET", `/api/variable-presets/${key}`), 404, "GET unknown presets");
  const presets = [{ name: "basic", variables: { customerTier: "basic" } }, { name: "premium", description: "", variables: { customerTier: "premium", extra: {} } }];
  expectError(await http("PUT", `/api/variable-presets/${key}`, { json: presets, user: "bob" }), 403, "PUT as Bob");
  expectStatus(await http("PUT", `/api/variable-presets/${key}`, { json: presets }), 204, "PUT as Alice");
  expectEqual((await stored("variable-presets", { key })).variable_presets?.json, presets, "stored presets");
  expectEqual((await http("GET", `/api/variable-presets/${key}`)).data, presets, "GET after PUT");
});

// ---------- IV.1
const claimData = { customer_email: "valid@example.com", amount: 120, incident: "theft", details: "Contract test" };
check(`IV.1 POST /api/responses is validated by the service (SERVICE=${SERVICE})`, "IV.1", async () => {
  const before = (await stored("validate-response")).responses?.[0]?.id ?? 0;
  const valid = await http("POST", "/api/responses", { json: { formId: "claim", data: claimData } });
  if (SERVICE === "off") return expectError(valid, 503, "POST /api/responses", "not running");
  expectStatus(valid, 201, "a valid response");
  for (const [what, data] of [
    ["an unknown choice", { ...claimData, incident: "meteor" }],
    ["a wrong type", { ...claimData, amount: { much: true } }],
    ["a missing required answer", { amount: 10, incident: "theft" }],
  ]) {
    const r = await http("POST", "/api/responses", { json: { formId: "claim", data } });
    expectStatus(r, 400, what);
    expect(Array.isArray(r.data?.errors) && r.data.errors.length > 0, `${what}: expected { errors: [...] }, got ${r.text().slice(0, 200)}`);
  }
  const after = (await stored("validate-response")).responses ?? [];
  expectEqual(after.filter((row) => row.id > before).length, 1, "rows stored (only the valid response)");
}, { skip: !VALIDATE && "VALIDATE_RESPONSES is off" });

// ---------- IV.2
check(`IV.2 PUT /api/forms/:id is linted by the service (SERVICE=${SERVICE})`, "IV.2", async () => {
  const put = (key, json) => http("PUT", `/api/forms/${key}`, { json });
  const clean = unique("contract");
  const r0 = await put(clean, { elements: [{ type: "text", name: "q1" }] });
  if (SERVICE === "off") return expectError(r0, 503, "PUT /api/forms", "not running");
  expectStatus(r0, 204, "a clean definition");
  for (const [what, definition] of [
    ["an unknown variable", { elements: [{ type: "text", name: "q1", visibleIf: "{nosuch} = 1" }] }],
    ["a question without a name", { elements: [{ type: "text", title: "No name" }] }],
  ]) {
    const key = unique("contract");
    const r = await put(key, definition);
    expectStatus(r, 422, what);
    expect(Array.isArray(r.data?.errors) && r.data.errors.length > 0 && Array.isArray(r.data.warnings), `${what}: expected { errors, warnings }, got ${r.text().slice(0, 200)}`);
    expectError(await http("GET", `/api/forms/${key}`), 404, `${what} was not saved`);
  }
  const key = unique("contract");
  const warned = { elements: [{ type: "text", name: "q1", someproperty: 1 }] };
  const r = await put(key, warned);
  expectStatus(r, 200, "an unknown property");
  expect(Array.isArray(r.data?.warnings) && r.data.warnings.length > 0 && !("errors" in r.data), `expected { warnings }, got ${r.text().slice(0, 200)}`);
  expectEqual((await http("GET", `/api/forms/${key}`)).data?.definition, warned, "a definition with warnings is saved");
}, { skip: !LINT && "LINT_DEFINITIONS is off" });

// ---------- IV.3
const pdfCheck = async (id) => {
  const r = await http("GET", `/api/claims/${id}/pdf`);
  if (SERVICE === "off") return expectError(r, 503, "GET /api/claims/:id/pdf", "not running");
  expectStatus(r, 200, "GET /api/claims/:id/pdf");
  expect((r.headers.get("content-type") ?? "").startsWith("application/pdf"), `Content-Type ${r.headers.get("content-type")}`);
  expect(r.bytes.subarray(0, 4).toString("latin1") === "%PDF", "the body starts with %PDF");
};
check(`IV.3 GET /api/claims/:id/pdf (SERVICE=${SERVICE})`, "IV.3", async () => {
  const s = await stored("server-pdf");
  const id = s.responses?.[0]?.id;
  expect(Number.isInteger(id), "the stored panel lists a claim");
  await pdfCheck(id);
  expectError(await http("GET", "/api/claims/999999999/pdf"), 404, "GET the PDF of an unknown claim");
});

check("I.7 → IV.3: a new claim prints", ["I.7", "IV.3"], async () => {
  const r = await http("POST", "/api/claims", { json: { data: claimData, definitionVersion: "v1" } });
  expectStatus(r, 201, "POST /api/claims");
  await pdfCheck(r.data.id);
});

// ---------- IV.4
check(`IV.4 POST /api/work-orders/extract (SERVICE=${SERVICE}, AI=${AI})`, "IV.4", async () => {
  const before = (await stored("extract-from-paper"))["row counts"];
  const form = new FormData();
  form.append("scan", new Blob([readShared("samples/work-order-scan.png")], { type: "image/png" }), "work-order-scan.png");
  const r = await http("POST", "/api/work-orders/extract", { body: form });
  if (SERVICE === "off") expectError(r, 503, "POST /api/work-orders/extract", "not running");
  else if (AI === "off") expectError(r, 503, "POST /api/work-orders/extract", "The SurveyJS service has no AI provider configured");
  else {
    expectStatus(r, 200, "POST /api/work-orders/extract");
    expectEqual(r.data?.answers, JSON.parse(readShared("samples/work-order-scan.answers.json")), "answers");
    expect(Array.isArray(r.data.confidence) && r.data.confidence.every((c) => typeof c.fieldName === "string" && typeof c.flagged === "boolean"), "confidence: [{ fieldName, value, confidence, flagged }]");
    expect(r.data.uniqueId === null || typeof r.data.uniqueId === "string", "uniqueId is a string or null");
  }
  expectEqual((await stored("extract-from-paper"))["row counts"], before, "nothing was saved");
  const big = new FormData();
  big.append("scan", new Blob([Buffer.alloc(5 * 1024 * 1024 + 1, 0x20)], { type: "image/png" }), "big.png");
  expectError(await http("POST", "/api/work-orders/extract", { body: big }), 413, "a scan over 5 MB");
});

// ---------- /demo/stored for every step with server code
check("/demo/stored/:slug answers for every step with server code", ALL_STEPS.filter((s) => SLUGS[s] && SELECTED.has(s)), async () => {
  for (const step of ALL_STEPS) if (SLUGS[step] && SELECTED.has(step)) await stored(SLUGS[step]);
});

// ---------- Relays

class Socket {
  static async open(url, { user = "alice", jar: j = jar } = {}) {
    const headers = {};
    const cookie = j.header(user);
    if (cookie) headers.Cookie = cookie;
    const s = new Socket();
    s.ws = new WebSocket(url, { headers });
    s.messages = [];
    s.waiters = [];
    s.closed = new Promise((resolve) => s.ws.addEventListener("close", resolve));
    s.ws.addEventListener("message", (e) => {
      const msg = JSON.parse(String(e.data));
      s.messages.push(msg);
      s.waiters = s.waiters.filter((w) => !(w.test(msg) && (w.resolve(msg), true)));
    });
    await new Promise((resolve, reject) => {
      s.ws.addEventListener("open", resolve, { once: true });
      s.ws.addEventListener("error", () => reject(new Failure(`could not connect to ${url}`)), { once: true });
    });
    return s;
  }
  next(test, what, ms = 3000) {
    const found = this.messages.find(test);
    if (found) { this.messages.splice(this.messages.indexOf(found), 1); return Promise.resolve(found); }
    return new Promise((resolve, reject) => {
      const w = { test, resolve: (m) => { clearTimeout(timer); this.messages.splice(this.messages.indexOf(m), 1); resolve(m); } };
      const timer = setTimeout(() => { this.waiters = this.waiters.filter((x) => x !== w); reject(new Failure(`timed out waiting for ${what}`)); }, ms);
      this.waiters.push(w);
    });
  }
  send(msg) { this.ws.send(JSON.stringify(msg)); }
  close() { this.ws.close(); return this.closed; }
}
const type = (t) => (m) => m.type === t;

check("I.8 fill relay: init first, values relayed, late joiners get them, peer-left on leave", "I.8", async () => {
  await http("GET", "/demo/stored/fill-together");   // takes demo_sid from the server in demo mode
  const room = `${RELAY_URL}/ws/rooms/${unique("contract")}`;
  const a = await Socket.open(`${room}?name=Ann`);
  const initA = await a.next(() => true, "the first message");
  expectEqual(initA.type, "init", "the first message type");
  expect(typeof initA.clientId === "string" && isObject(initA.values) && Array.isArray(initA.peers) && "seed" in initA && Number.isInteger(initA.colorIndex), `init { clientId, name, colorIndex, seed, values, peers }: ${JSON.stringify(initA)}`);
  const b = await Socket.open(`${room}?name=Ben`);
  const initB = await b.next(type("init"), "Ben's init");
  a.send({ type: "value", key: "q1", value: ROUNDTRIP });
  const v = await b.next(type("value"), "the value at Ben");
  expectEqual(v, { type: "value", from: initA.clientId, key: "q1", value: ROUNDTRIP }, "relayed value");
  a.send({ type: "presence", state: { page: "page1", focus: "q1" }, retain: true });
  const p = await b.next(type("peer"), "Ann's presence at Ben");
  expect(p.peer?.clientId === initA.clientId && p.retain === true && isObject(p.peer.state), `peer { peer, retain }: ${JSON.stringify(p)}`);
  a.send({ type: "nonsense" });
  const c = await Socket.open(`${room}?name=Cat`);
  const initC = await c.next(type("init"), "the late joiner's init");
  expectEqual(initC.values?.q1, ROUNDTRIP, "late joiner's init.values.q1");
  expect(initC.peers.some((peer) => peer.clientId === initA.clientId), "late joiner's init.peers lists Ann");
  await a.close();
  const left = await b.next(type("peer-left"), "peer-left at Ben");
  expectEqual(left.clientId, initA.clientId, "peer-left.clientId");
  await b.close(); await c.close();
  void initB;
}, { skip: !RELAY_URL && "RELAY_URL is not set" });

check("III.5 edit relay: appends reach a late joiner's init.log in order", "III.5", async () => {
  await http("GET", "/demo/stored/edit-together");
  const room = `${EDIT_RELAY_URL}/ws/forms/${unique("contract")}`;
  const a = await Socket.open(`${room}?name=Ann`);
  const initA = await a.next(() => true, "the first message");
  expect(initA.type === "init" && typeof initA.clientId === "string" && Array.isArray(initA.log) && "seed" in initA, `init { clientId, colorIndex, seed, log }: ${JSON.stringify(initA)}`);
  const b = await Socket.open(`${room}?name=Ben`);
  await b.next(type("init"), "Ben's init");
  const records = [{ v: 1, seq: 1, op: 0, payload: { name: "title", value: "One" } }, { v: 1, seq: 1, op: 0, payload: { name: "title", value: "One more" } }];
  for (const record of records) a.send({ type: "append", payload: record });
  for (const record of records) expectEqual(await b.next(type("record"), "a record at Ben"), { type: "record", from: initA.clientId, payload: record }, "relayed record");
  a.send({ type: "presence", state: { tab: "designer", sel: null } });
  const p = await b.next(type("presence"), "Ann's presence at Ben");
  expect(p.peer?.clientId === initA.clientId && isObject(p.peer.state), `presence { peer }: ${JSON.stringify(p)}`);
  const c = await Socket.open(`${room}?name=Cat`);
  const initC = await c.next(type("init"), "the late joiner's init");
  expectEqual(initC.log, records, "late joiner's init.log");
  await a.close();
  expectEqual((await b.next(type("presence-leave"), "presence-leave at Ben")).clientId, initA.clientId, "presence-leave.clientId");
  await b.close(); await c.close();
}, { skip: !EDIT_RELAY_URL && "EDIT_RELAY_URL is not set" });

check("I.8 and III.5 relays: sandboxes get separate rooms, one sandbox shares a room", ["I.8", "III.5"], async () => {
  const other = new Jar();
  await http("GET", "/demo/stored/fill-together", { jar: other });
  await http("GET", "/demo/stored/fill-together");
  expect(jar.sid && other.sid && jar.sid !== other.sid, "two visitors got two demo_sid cookies from the server");
  for (const [base, path, msg, seen] of [
    [RELAY_URL, "/ws/rooms/", { type: "value", key: "q1", value: "mine" }, (m) => m.values?.q1],
    [EDIT_RELAY_URL, "/ws/forms/", { type: "append", payload: { seq: 1 } }, (m) => m.log?.length],
  ]) {
    const room = `${base}${path}${unique("contract")}?name=X`;
    const mine = await Socket.open(room);
    await mine.next(type("init"), "init");
    mine.send(msg);
    await new Promise((r) => setTimeout(r, 300));
    const same = await Socket.open(room);
    expect(seen(await same.next(type("init"), "init in the same sandbox")), `${path}: the same sandbox shares the room`);
    const theirs = await Socket.open(room, { jar: other });
    expect(!seen(await theirs.next(type("init"), "init in another sandbox")), `${path}: another sandbox gets its own room`);
    await mine.close(); await same.close(); await theirs.close();
  }
}, { skip: (!DEMO && "DEMO_MODE is off") || ((!RELAY_URL || !EDIT_RELAY_URL) && "RELAY_URL and EDIT_RELAY_URL are not set") });

// ------------------------------------------------------------------------------------------------

console.log(`Contract server-integration-v4 against ${BASE_URL} (SERVICE=${SERVICE} AI=${AI}${VALIDATE ? " VALIDATE_RESPONSES" : ""}${LINT ? " LINT_DEFINITIONS" : ""}${DEMO ? " DEMO_MODE" : ""})`);
let failed = 0, passed = 0, skipped = 0;
for (const c of checks) {
  const missing = c.steps.filter((s) => !SELECTED.has(s));
  if (c.steps.length === 0 || (c.steps.length === 1 && missing.length)) continue;     // a step check for an unselected step
  if (missing.length) { console.log(`skip  ${c.name}: skipped (needs ${missing.join(", ")})`); skipped++; continue; }
  if (c.skip) { console.log(`skip  ${c.name}: ${c.skip}`); skipped++; continue; }
  try {
    await c.fn();
    console.log(`ok    ${c.name}`);
    passed++;
  } catch (e) {
    console.log(`FAIL  ${c.name}\n      ${e instanceof Failure ? e.message : e.stack}`);
    failed++;
  }
}
console.log(`\n${passed} passed, ${failed} failed, ${skipped} skipped`);
process.exit(failed ? 1 : 0);
