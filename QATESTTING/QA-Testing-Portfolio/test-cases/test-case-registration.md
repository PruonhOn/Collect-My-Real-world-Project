# 📋 Test Case — TC_002

## Test Case ID
`TC_002`

## Test Scenario
Verify that a user cannot register with an empty email field.

---

## Pre-conditions

- Registration page is accessible
- No account exists for the test username

---

## Test Steps

| Step | Action | Expected Result |
|---|---|---|
| 1 | Open the registration page (`/register`) | Registration form is displayed |
| 2 | Enter a valid username | Field accepts input |
| 3 | Enter a valid password | Field accepts input |
| 4 | Leave the **email field empty** | Field remains blank |
| 5 | Click the **Register** button | Validation error appears |

---

## Expected Result

System displays error message: *"Email is required"* and does **not** create an account.

---

## Actual Result

> *(Fill in after testing)*

---

## Status

- [ ] Pass
- [ ] Fail
- [ ] Blocked

---

*Written by: Pruonh | Date: [Insert Date]*
