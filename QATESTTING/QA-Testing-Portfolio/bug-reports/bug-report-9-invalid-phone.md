# 🐛 Bug Report — BR-009

## Title
Contact form accepts invalid phone number

---

## Steps to Reproduce

1. Open the **contact form** page
2. Enter letters (e.g., "abcdefg") into the phone number field
3. Fill in all other required fields
4. Click **Submit**

---

## Expected Result

System displays a validation error: *"Please enter a valid phone number"*.

---

## Actual Result

Form submits successfully with an invalid phone number.

---

## Severity

🟢 **Low** — Bad data is stored, but it does not break core functionality.

---

*Reported by: Pruonh | Date: [Insert Date]*
