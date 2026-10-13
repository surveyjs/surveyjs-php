// Demo only: the three tabs of a step page (layouts/example.blade.php): Demo, Description and Under
// the hood, with its Code, Requests and Stored sections, the live links in the header, the badge
// and the highlights. It works from the DOM alone: shared/client/host.js fills #requests, #stored
// and #notes, and MutationObservers here watch them, so host.js doesn't know the tabs exist.
//
// - The URL hash holds the tab: none or #demo opens Demo, #description Description, and #code,
//   #requests or #stored Under the hood on that section. Choosing a tab replaces the hash (no new
//   history entry). Nothing else picks the tab: a page without a hash always opens on Demo.
// - The last section chosen is remembered in localStorage; the hash overrides it.
// - The badge on "Under the hood" counts what the reader hasn't seen: each non-demo request that
//   finishes, each Stored section whose text changes after its first render and each new note.
//   Showing the panel clears it. An unseen error (row or note) turns it red.
// - The live links show the latest matching non-demo request's status, or whether a Stored section
//   changed, and open their place in Under the hood. Row 2 never wraps: the links that don't fit
//   move, from the end, into "More (n)".

const tablist = document.getElementById("page-tabs");
if (tablist) setUpPageTabs();

function setUpPageTabs() {
  const TABS = ["demo", "description", "hood"];
  const SECTIONS = ["code", "requests", "stored"];
  const STORAGE_KEY = "surveyjs-hood-section";

  const header = document.getElementById("page-header");
  const demoPanel = document.getElementById("panel-demo");
  const component = document.getElementById("component");
  const requests = document.getElementById("requests");
  const stored = document.getElementById("stored");
  const notes = document.getElementById("notes");
  const readouts = document.getElementById("readouts");
  const badge = document.getElementById("hood-badge");
  const requestsCount = document.getElementById("requests-count");
  const tabs = [...tablist.querySelectorAll('[role="tab"]')];
  const sectionTabs = [...document.querySelectorAll('#hood-sections [role="tab"]')];
  const tabOf = (name) => tabs.find((tab) => tab.dataset.tab === name);
  const sectionTabOf = (name) => sectionTabs.find((tab) => tab.dataset.section === name);

  // ---------------------------------------------------------------------------------------------
  // Sizes: the header's height for Under the hood and Creator, and what sits above the component

  function measure() {
    const root = document.documentElement.style;
    root.setProperty("--header-h", `${header.offsetHeight}px`);
    if (!demoPanel.hidden) root.setProperty("--demo-above", `${component.offsetTop - demoPanel.offsetTop}px`);
  }
  const sizes = new ResizeObserver(measure);
  sizes.observe(header);
  for (const child of demoPanel.children) if (child !== component) sizes.observe(child);

  // ---------------------------------------------------------------------------------------------
  // Tabs, sections and the hash

  let active = "demo";
  let section = remembered() ?? "requests";
  const scrollOf = {};   // each tab keeps its own scroll position

  function remembered() {
    try {
      const value = localStorage.getItem(STORAGE_KEY);
      return SECTIONS.includes(value) ? value : null;
    } catch {
      return null;
    }
  }
  function remember() {
    try {
      localStorage.setItem(STORAGE_KEY, section);
    } catch {
      // private window or blocked storage: the page works, it just forgets
    }
  }

  const hashFor = () => (active === "demo" ? "" : active === "hood" ? `#${section}` : `#${active}`);
  function writeHash() {
    if (location.hash === hashFor() || (!location.hash && !hashFor())) return;
    history.replaceState(history.state, "", hashFor() || location.pathname + location.search);
  }

  function render() {
    for (const tab of tabs) {
      const selected = tab.dataset.tab === active;
      tab.setAttribute("aria-selected", String(selected));
      tab.tabIndex = selected ? 0 : -1;
      document.getElementById(tab.getAttribute("aria-controls")).hidden = !selected;
    }
    for (const tab of sectionTabs) {
      const selected = tab.dataset.section === section;
      tab.setAttribute("aria-selected", String(selected));
      tab.tabIndex = selected ? 0 : -1;
      document.getElementById(tab.getAttribute("aria-controls")).hidden = !selected;
    }
    if (active === "hood") clearBadge();
    measure();
  }

  function select(name, { sectionName, hash = true } = {}) {
    if (name !== active) {
      scrollOf[active] = scrollY;
      active = name;
      render();
      scrollTo(0, scrollOf[name] ?? 0);
    }
    if (sectionName && sectionName !== section) {
      section = sectionName;
      render();
    }
    if (hash) writeHash();
  }

  function fromHash() {
    const name = decodeURIComponent(location.hash.slice(1));
    if (SECTIONS.includes(name)) select("hood", { sectionName: name, hash: false });
    else select(name === "description" ? "description" : "demo", { hash: false });
  }

  function keyboard(list, onMove) {
    for (const tab of list) {
      tab.addEventListener("keydown", (e) => {
        const i = list.indexOf(tab);
        const next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: list.length - 1 }[e.key];
        if (next === undefined) return;
        e.preventDefault();
        const target = list[(next + list.length) % list.length];
        target.focus();
        onMove(target);
      });
    }
  }

  for (const tab of tabs) tab.addEventListener("click", () => select(tab.dataset.tab));
  keyboard(tabs, (tab) => select(tab.dataset.tab));

  function chooseSection(name) {
    select("hood", { sectionName: name });
    remember();
  }
  for (const tab of sectionTabs) tab.addEventListener("click", () => chooseSection(tab.dataset.section));
  keyboard(sectionTabs, (tab) => chooseSection(tab.dataset.section));

  document.addEventListener("click", (e) => {
    const opener = e.target.closest?.("[data-open-tab]");
    if (opener && TABS.includes(opener.dataset.openTab)) select(opener.dataset.openTab);
  });

  addEventListener("hashchange", fromHash);

  // ---------------------------------------------------------------------------------------------
  // The badge

  let unseen = 0;
  let unseenError = false;
  function bump(error) {
    if (active === "hood") return;
    unseen++;
    unseenError ||= error;
    paintBadge();
  }
  function clearBadge() {
    unseen = 0;
    unseenError = false;
    paintBadge();
  }
  function paintBadge() {
    badge.hidden = unseen === 0;
    badge.textContent = unseen > 99 ? "99+" : String(unseen);
    badge.dataset.kind = unseenError ? "error" : "ok";
    tabOf("hood").setAttribute("aria-label", unseen ? `Under the hood, ${unseen} new` : "Under the hood");
  }

  function flash(element) {
    element.removeAttribute("data-hood-flash");
    void element.offsetWidth;   // restart the animation
    element.setAttribute("data-hood-flash", "");
    element.addEventListener("animationend", () => element.removeAttribute("data-hood-flash"), { once: true });
  }

  // ---------------------------------------------------------------------------------------------
  // Live links: endpoints and stored sections, in the header and in "More (n)"

  const liveLinks = [...document.querySelectorAll("[data-live]")];
  const pattern = (label) => {
    const [method, path = ""] = label.split(/\s+/);
    const source = path.split(/\{[^}]+\}/).map((part) => part.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")).join("[^/]+");
    return { method: method.toUpperCase(), path: new RegExp(`^${source}$`) };
  };
  const endpointMatches = (label, info) => {
    const { method, path } = pattern(label);
    return method === info.method && path.test(info.path);
  };

  function setPill(kind, label, text, pillKind) {
    for (const link of liveLinks) {
      if (link.dataset.live !== kind || link.dataset.label !== label) continue;
      const pill = link.querySelector("[data-pill]");
      pill.textContent = text;
      pill.dataset.kind = pillKind;
    }
    fitSoon();
  }

  function latestRow(label) {
    return [...requests.children].find((row) => row.dataset.demo !== "true" && endpointMatches(label, rowInfo(row))) ?? null;
  }

  function paintEndpoint(label) {
    const row = latestRow(label);
    if (!row) return setPill("endpoint", label, "–", "none");
    const { status } = rowInfo(row);
    if (row.dataset.state === "pending") return setPill("endpoint", label, "…", "none");
    setPill("endpoint", label, status, row.dataset.state === "error" ? "error" : "ok");
  }
  const endpointLabels = [...new Set(liveLinks.filter((l) => l.dataset.live === "endpoint").map((l) => l.dataset.label))];
  const paintEndpoints = () => endpointLabels.forEach(paintEndpoint);

  for (const link of liveLinks) {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      closeMore(false);
      if (link.dataset.live === "endpoint") {
        select("hood", { sectionName: "requests" });
        const row = latestRow(link.dataset.label);
        if (row) {
          expand(row, true);
          row.scrollIntoView({ block: "nearest" });
        }
      } else {
        select("hood", { sectionName: "stored" });
        const target = [...stored.children].find((s) => titleOf(s) === link.dataset.label);
        target?.scrollIntoView({ block: "start" });
      }
    });
  }

  // Row 2 never wraps: move the links that don't fit, from the end, into "More (n)"
  const nav = document.getElementById("live-links");
  const more = document.getElementById("live-more");
  const moreButton = document.getElementById("live-more-button");
  const moreList = document.getElementById("live-more-list");
  const rowLinks = [...nav.querySelectorAll("[data-live]")];
  const moreItems = [...moreList.querySelectorAll("[data-more-item]")];

  function fit() {
    nav.dataset.fit = "";
    if (!nav.offsetParent && getComputedStyle(nav).display === "none") return;   // below 768px: no links
    rowLinks.forEach((link) => (link.hidden = false));
    moreItems.forEach((item) => (item.hidden = true));
    more.hidden = true;
    if (nav.scrollWidth <= nav.clientWidth) return closeMore(false);
    more.hidden = false;
    let hidden = 0;
    for (let i = rowLinks.length - 1; i >= 0 && nav.scrollWidth > nav.clientWidth; i--) {
      rowLinks[i].hidden = true;
      moreItems[i].hidden = false;
      hidden++;
      more.querySelector("[data-more-count]").textContent = String(hidden);
    }
  }
  let fitFrame = 0;
  function fitSoon() {
    cancelAnimationFrame(fitFrame);
    fitFrame = requestAnimationFrame(fit);
  }
  new ResizeObserver(fitSoon).observe(nav.parentElement);
  document.fonts?.ready.then(fitSoon);   // the labels' widths change when the web font arrives

  function openMore() {
    moreList.hidden = false;
    moreButton.setAttribute("aria-expanded", "true");
    moreList.querySelector("li:not([hidden]) a")?.focus();
  }
  function closeMore(returnFocus) {
    if (moreList.hidden) return;
    moreList.hidden = true;
    moreButton.setAttribute("aria-expanded", "false");
    if (returnFocus) moreButton.focus();
  }
  moreButton.addEventListener("click", () => (moreList.hidden ? openMore() : closeMore(false)));
  more.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !moreList.hidden) {
      e.preventDefault();
      closeMore(true);
    }
  });
  document.addEventListener("pointerdown", (e) => {
    if (!moreList.hidden && !more.contains(e.target)) closeMore(more.contains(document.activeElement));
  });

  // ---------------------------------------------------------------------------------------------
  // Requests: highlights, the badge, the count and the rows' bodies

  const finished = new WeakSet();
  let current = null;
  let rowIds = 0;

  function rowInfo(row) {
    const text = (name) => row.querySelector(`[data-field="${name}"]`)?.textContent.trim() ?? "";
    let path = text("url").split(/[?\s]/)[0];
    try {
      path = new URL(path, location.href).pathname;
    } catch {
      // keep it as shown
    }
    return { method: text("method").toUpperCase(), path, status: text("status") };
  }

  function expand(row, open) {
    const toggle = row.querySelector("[data-row-toggle]");
    const bodies = row.querySelector("[data-row-bodies]");
    if (!toggle || !bodies) return;
    toggle.setAttribute("aria-expanded", String(open));
    bodies.hidden = !open;
  }

  function onRow(row) {
    if (!(row instanceof HTMLElement) || row.parentElement !== requests) return;
    const bodies = row.querySelector("[data-row-bodies]");
    if (bodies && !bodies.id) {
      bodies.id = `request-bodies-${++rowIds}`;
      row.querySelector("[data-row-toggle]")?.setAttribute("aria-controls", bodies.id);
    }
    if (row.dataset.demo === "true") return;
    const info = rowInfo(row);
    for (const label of endpointLabels) if (endpointMatches(label, info)) paintEndpoint(label);
    if (finished.has(row) || (row.dataset.state !== "ok" && row.dataset.state !== "error")) return;
    finished.add(row);
    bump(row.dataset.state === "error");

    current?.removeAttribute("data-hood-current");
    current = row;
    row.dataset.hoodCurrent = "true";
    flash(row);
    if (!requests.closest("[hidden]")) row.scrollIntoView({ block: "nearest" });
  }

  function countRows() {
    const shown = [...requests.children].filter((row) => !row.hidden).length;
    requestsCount.textContent = shown ? String(shown) : "";
  }

  new MutationObserver((records) => {
    for (const record of records) {
      if (record.type === "attributes") {
        if (record.attributeName === "data-state") onRow(record.target);
      } else {
        record.addedNodes.forEach(onRow);
      }
    }
    if (records.some((r) => r.type === "childList")) paintEndpoints();
    countRows();
  }).observe(requests, { childList: true, attributes: true, subtree: true, attributeFilter: ["data-state", "hidden"] });

  requests.addEventListener("click", (e) => {
    const toggle = e.target.closest("[data-row-toggle]");
    if (toggle) expand(toggle.closest("li"), toggle.getAttribute("aria-expanded") !== "true");
  });

  // ---------------------------------------------------------------------------------------------
  // Stored: first render, changes, highlights and <mark> on new entries

  const first = new Map();      // section title → its text at the first render
  const previous = new Map();   // section title → its text at the last render
  let marks = new Map();        // section title → { text, fresh } to keep highlighted across re-renders
  const IDENTITY = ["id", "key", "claim_id"];
  const titleOf = (section) => section.querySelector('[data-field="title"]')?.textContent ?? "";

  // For a JSON array of objects with an id, key or claim_id: the identities that weren't there before
  function freshEntries(before, text) {
    let now;
    try {
      now = JSON.parse(text);
    } catch {
      return null;
    }
    const isEntry = (entry) => entry !== null && typeof entry === "object" && !Array.isArray(entry);
    const key = Array.isArray(now) && now.length && now.every(isEntry) ? IDENTITY.find((k) => now.every((entry) => k in entry)) : null;
    if (!key) return null;
    let old = [];
    try {
      old = JSON.parse(before ?? "[]");
    } catch {
      // not JSON before: every entry is new
    }
    const known = new Set(Array.isArray(old) ? old.filter(isEntry).map((entry) => JSON.stringify(entry[key])) : []);
    const fresh = new Set(now.map((entry) => JSON.stringify(entry[key])).filter((id) => !known.has(id)));
    return fresh.size ? { key, ids: fresh } : null;
  }

  // Rebuild the section's <pre> with a <mark> around the new entries' lines (host.js replaces it on its next refresh)
  function markEntries(pre, text, { key, ids }) {
    const entries = JSON.parse(text);
    const chunks = entries.map((entry) => JSON.stringify(entry, null, 2).replace(/^/gm, "  "));
    if (`[\n${chunks.join(",\n")}\n]` !== text) return false;   // not host.js's JSON.stringify(value, null, 2)
    const nodes = [document.createTextNode("[\n")];
    chunks.forEach((chunk, i) => {
      if (i) nodes.push(document.createTextNode(",\n"));
      if (ids.has(JSON.stringify(entries[i][key]))) {
        const mark = document.createElement("mark");
        mark.textContent = chunk;
        nodes.push(mark);
      } else {
        nodes.push(document.createTextNode(chunk));
      }
    });
    nodes.push(document.createTextNode("\n]"));
    pre.replaceChildren(...nodes);
    return true;
  }

  function highlight(section, pre, text, fresh, isNew) {
    if (!(fresh && markEntries(pre, text, fresh))) section.dataset.hoodChanged = "true";
    if (isNew) flash(section);
  }

  function onStoredRender() {
    const changed = [];
    for (const section of stored.children) {
      const title = titleOf(section);
      const pre = section.querySelector('[data-field="json"]');
      const text = pre?.textContent ?? "";
      if (!first.has(title)) first.set(title, text);
      setPill("stored", title, text === first.get(title) ? "unchanged" : "updated", text === first.get(title) ? "none" : "ok");
      const before = previous.get(title);
      previous.set(title, text);
      if (before === undefined) continue;   // the first render is the baseline
      if (before === text) {
        if (marks.get(title)?.text === text) highlight(section, pre, text, marks.get(title).fresh, false);
        continue;
      }
      changed.push({ section, pre, title, text, fresh: freshEntries(before, text) });
    }
    if (!changed.length) return;

    marks = new Map(changed.map(({ title, text, fresh }) => [title, { text, fresh }]));
    for (const { section, pre, text, fresh } of changed) {
      highlight(section, pre, text, fresh, true);
      bump(false);
    }
  }

  new MutationObserver(onStoredRender).observe(stored, { childList: true });

  // ---------------------------------------------------------------------------------------------
  // Notes: each new one counts; the read-outs block shows once there is one

  new MutationObserver((records) => {
    for (const record of records) {
      for (const node of record.addedNodes) {
        if (node instanceof HTMLElement) bump(node.dataset.kind === "error");
      }
    }
    readouts.hidden = false;
  }).observe(notes, { childList: true });

  // ---------------------------------------------------------------------------------------------

  requests.querySelectorAll(":scope > li").forEach(onRow);   // in case host.js logged before this ran
  if (notes.children.length) readouts.hidden = false;
  onStoredRender();
  paintEndpoints();
  countRows();
  fromHash();
  render();
  fit();
}
