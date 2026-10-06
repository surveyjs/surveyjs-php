// I.6 Async functions: calculations and validation
import { mountSurvey, page } from "./host.js";
import { Model } from "survey-core";

// #region sjs:I.6.client
import { registerFunction } from "survey-core";

// Validation: does a customer with this email already exist?
registerFunction({
  name: "emailExists",
  isAsync: true,
  func: async ([email]) => {
    if (!email) return false;
    const res = await fetch(`/api/customers/exists?email=${encodeURIComponent(email)}`);
    return (await res.json()).exists;
  }
});

// Calculation: shipping price for a postcode
registerFunction({
  name: "shippingCost",
  isAsync: true,
  func: async ([postcode]) => {
    if (!postcode) return 0;
    const res = await fetch(`/api/shipping?postcode=${encodeURIComponent(postcode)}`);
    return (await res.json()).price;
  }
});
// #endregion

// In the definition (shared/definitions/async-functions.json):
//   "validators": [{ "type": "expression", "expression": "emailExists({email}) = false", "text": "This email is already registered" }]
//   "calculatedValues": [{ "name": "shipping", "expression": "shippingCost({postcode})" }]
mountSurvey(new Model(page.definition));
