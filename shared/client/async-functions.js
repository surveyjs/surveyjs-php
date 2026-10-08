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

// Calculation and validation: the shipping price for a postcode, null where you don't deliver
const prices = new Map();   // one request per postcode serves both the calculated value and the validator
registerFunction({
  name: "shippingCost",
  isAsync: true,
  func: async ([postcode]) => {
    if (!postcode) return 0;
    if (!prices.has(postcode)) prices.set(postcode, fetch(`/api/shipping?postcode=${encodeURIComponent(postcode)}`).then(r => r.json()));
    return (await prices.get(postcode)).price;
  }
});
// #endregion

// In the definition (shared/definitions/async-functions.json):
//   "validators": [{ "type": "expression", "expression": "emailExists({email}) = false", "text": "This email is already registered" }]
//   "validators": [{ "type": "expression", "expression": "shippingCost({postcode}) > 0", "text": "We don't deliver to this postcode yet" }]
//   "calculatedValues": [{ "name": "shipping", "expression": "shippingCost({postcode})" }]
mountSurvey(new Model(page.definition));
