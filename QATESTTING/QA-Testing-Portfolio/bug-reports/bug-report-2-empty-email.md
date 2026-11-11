# 🐛 Bug Report — BR-002

## Title
Registration form accepts empty email

---

## Environment

| Field | Details |
|---|---|
| Device | Laptop |
| OS | Windows 11 |
| Browser | Chrome 124 |
| URL | `/register` |

---

## Steps to Reproduce

1. Open the registration page
2. Enter a valid username
3. Enter a valid password
4. Leave the **email field empty**
5. Click the **Register** button

---

## Expected Result

System displays a validation error: *"Email is required"* and does not create the account.

---

## Actual Result

Account is created successfully without an email address.

---

## Severity

🟡 **Medium** — Data integrity issue. Accounts without email break password recovery and notification features.

---

*Reported by: Pruonh | Date: [Insert Date]*
