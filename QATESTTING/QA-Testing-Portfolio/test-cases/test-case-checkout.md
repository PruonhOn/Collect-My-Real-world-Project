# 📋 Test Case — TC_003

## Test Case ID
`TC_003`

## Test Scenario
Verify that the checkout page displays the correct total price.

---

## Pre-conditions

- User is logged in
- At least 2 products are available in the store

---

## Test Steps

| Step | Action | Expected Result |
|---|---|---|
| 1 | Navigate to any product page | Product page loads correctly |
| 2 | Add **Product A** to cart | Cart shows 1 item |
| 3 | Navigate to another product page | Product page loads correctly |
| 4 | Add **Product B** to cart | Cart shows 2 items |
| 5 | Note the price of each product | Prices are recorded |
| 6 | Go to the **checkout page** | Checkout page loads |
| 7 | Review the **total price** | Total = Price A + Price B |

---

## Expected Result

Total price on checkout equals the **exact sum** of all cart items (excluding unexpected charges).

---

## Actual Result

> *(Fill in after testing)*

---

## Status

- [ ] Pass
- [ ] Fail
- [ ] Blocked

---

## Notes

> *(Include screenshots of cart vs checkout total if there is a mismatch)*

---

*Written by: Pruonh | Date: [Insert Date]*
