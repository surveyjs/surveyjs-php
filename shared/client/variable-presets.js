// III.4 Variable presets
import { mountCreator, page } from "./host.js";

const formId = "support";

// #region sjs:III.4.client
import { SurveyCreator } from "survey-creator-js";

const creator = new SurveyCreator({
  variablePresets: {
    definition: {               // describes the variables and how authors edit them
      elements: [{
        type: "dropdown", name: "customerTier", title: "Customer plan",
        choices: ["basic", "premium"]
      }]
    },
    presets: await fetch(`/api/variable-presets/${formId}`).then(r => (r.ok ? r.json() : []))
  }
});

// Save the presets when an author edits them
creator.onVariablePresetsChanged.add((_, options) => {
  if (options.reason === "edit") {
    fetch(`/api/variable-presets/${formId}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(options.variablePresets)
    });
  }
});
// #endregion

creator.JSON = page.definition;   // a form whose logic reads {customerTier}
mountCreator(creator);
creator.makeNewViewActive("preview");
