# 🐛 Bug Report — BR-001

## Title
Login button does not respond

---

## Environment

| Field | Details |
|---|---|
| Device | Laptop |
| OS | Windows 11 |
| Browser | Chrome 124 |
| URL | `/login` |

---

## Steps to Reproduce

1. Open the login page
2. Enter a valid email address
3. Enter a valid password
4. Click the **Login** button

---

## Expected Result

User is authenticated and redirected to the dashboard.

---

## Actual Result

Nothing happens after clicking the Login button. No error message, no redirect, no loading indicator.

---

## Severity

🔴 **High** — Core authentication is broken. Users cannot log in at all.

---

## Additional Notes

- Tested on Chrome, same result.
- No console errors observed (or attach screenshot of console here).
- Possibly a broken `onClick` handler or a failed API call.

---

*Reported by: Pruonh | Date: [Insert Date]*
