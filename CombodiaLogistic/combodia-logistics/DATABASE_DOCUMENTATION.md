

# DATABASE DOCUMENTATION

## Cambodia Logistics Management System (CLMS)

---

**Version:** 1.0.0  
**Date:** July 2026  
**Status:** Draft  
**Prepared by:** System Architecture Team  
**Last Updated:** July 21, 2026

---

# TABLE OF CONTENTS

1. [Complete Requirement Analysis](#1-complete-requirement-analysis)
2. [Functional Requirements](#2-functional-requirements)
3. [Non-Functional Requirements](#3-non-functional-requirements)
4. [Business Rules](#4-business-rules)
5. [User Roles](#5-user-roles)
6. [User Permissions](#6-user-permissions)
7. [System Workflow](#7-system-workflow)
8. [Business Workflow](#8-business-workflow)
9. [Data Flow](#9-data-flow)
10. [Use Case Diagram](#10-use-case-diagram)
11. [Entity Relationship Diagram](#11-entity-relationship-diagram)
12. [Database Dictionary](#12-database-dictionary)
13. [Database Relationship Explanation](#13-database-relationship-explanation)
14. [Activity Diagram](#14-activity-diagram)
15. [Sequence Diagram](#15-sequence-diagram)
16. [System Architecture](#16-system-architecture)
17. [Folder Structure](#17-folder-structure)
18. [API Structure](#18-api-structure)
19. [Navigation Flow](#19-navigation-flow)
20. [UI Wireframe Description](#20-ui-wireframe-description)

---

# 1. COMPLETE REQUIREMENT ANALYSIS

## 1.1 Overview of Logistics in Cambodia

Cambodia's logistics industry stands at a critical inflection point. As a developing economy in the heart of Southeast Asia, Cambodia's trade and commerce have expanded significantly over the past two decades, driven by garment manufacturing exports, agricultural commodities, tourism, and a rapidly growing domestic consumer market. The Kingdom's geographic position -- bordered by Thailand to the west and north, Laos to the north, Vietnam to the east, and possessing a coastline along the Gulf of Thailand to the south -- places it at a strategic crossroads for regional trade. The Port of Sihanoukville serves as the country's primary maritime gateway, while the Mekong River corridor provides inland waterway connectivity. Phnom Penh, the capital, functions as the central logistics hub where road, rail, river, and air cargo converge.

Despite this strategic positioning, Cambodia's logistics sector faces persistent challenges that create both pain points for existing operators and opportunities for technology-driven solutions. The World Bank's Logistics Performance Index has consistently ranked Cambodia below its ASEAN peers, highlighting deficiencies in infrastructure quality, customs efficiency, tracking capabilities, and logistics competence. Road infrastructure, while improving through significant investment by the Royal Government of Cambodia and international development partners, remains uneven -- major national highways connecting Phnom Penh to Siem Reap, Sihanoukville, and the Vietnamese border at Bavet have been upgraded, but secondary and rural road networks continue to present significant transportation challenges, particularly during the monsoon season when flooding can disrupt supply chains for days or even weeks.

## 1.2 Current Challenges in Cambodian Logistics

The Cambodian logistics industry operates in an environment characterized by several distinct challenges. First, **fragmented supply chains** are the norm. Most logistics operations in Cambodia rely on manual processes, paper-based documentation, and telephone-based coordination. Warehouses operate without integrated inventory management systems, leading to frequent stock discrepancies, inefficient space utilization, and delayed order fulfillment. Shipment tracking is largely nonexistent for domestic logistics, with customers relying on phone calls to couriers or drivers for status updates. This fragmentation results in high operational costs, estimated at 25-30% of GDP, which is significantly above the ASEAN average.

Second, **regulatory complexity** poses operational challenges. Cambodia's customs procedures, while modernizing through the National Single Window project, still require significant documentation for cross-border shipments. The coexistence of multiple regulatory frameworks for domestic versus international shipments, varying taxation requirements across provinces, and the need for compliance with ASEAN trade agreements create a complex compliance environment that demands systematic management.

Third, **limited technology adoption** pervades the industry. While large logistics companies and international freight forwarders operating in Cambodia have implemented enterprise resource planning (ERP) systems, the vast majority of small and medium-sized logistics operators -- who collectively handle the majority of domestic freight movement -- rely on spreadsheets, handwritten logs, and personal judgment. This technological gap creates information asymmetries, reduces operational transparency, and makes it difficult for businesses to scale their operations efficiently.

Fourth, **human resource constraints** limit operational capacity. The logistics industry in Cambodia faces a shortage of trained professionals, particularly in areas such as supply chain management, fleet management, warehouse optimization, and logistics technology. Driver management is particularly challenging, with issues related to scheduling efficiency, document compliance, safety monitoring, and performance tracking requiring systematic solutions.

Fifth, **geographic and seasonal factors** introduce operational variability. Cambodia's tropical climate, with its pronounced wet season from May to November, significantly impacts transportation networks. Flood-prone areas along the Mekong and Tonle Sap river systems become inaccessible during peak flooding, requiring dynamic route planning and contingency logistics. The country's landlocked borders with Laos, while sharing river access with Vietnam, create dependency on specific corridor routes for regional trade.

## 1.3 The Case for a Logistics Management System

Given these challenges, a comprehensive Logistics Management System (LMS) tailored to the Cambodian context offers transformative potential. The system must address the full lifecycle of logistics operations -- from customer acquisition and order management through warehousing, fleet management, shipment processing, delivery, and financial reconciliation. It must be designed to accommodate the specific realities of operating in Cambodia while incorporating international best practices in logistics management.

The system must support **multi-branch operations**, reflecting the reality that logistics companies in Cambodia typically operate across multiple cities (Phnom Penh, Siem Reap, Battambang, Sihanoukville, etc.) with distributed warehousing and fleet resources. **Warehouse management** must account for varying infrastructure quality, from modern climate-controlled facilities in Phnom Penh to basic open-air staging areas in provincial locations. **Fleet management** must accommodate the diverse vehicle types used in Cambodian logistics, from motorcycles for last-mile urban delivery to heavy trucks for intercity freight transport.

**Route optimization** is particularly critical in the Cambodian context, where road conditions vary dramatically, traffic congestion in Phnom Penh is severe and worsening, and seasonal flooding can render standard routes impassable. The system must support dynamic route planning that can adapt to real-time conditions while optimizing for fuel efficiency, delivery time, and vehicle utilization. **Shipment tracking** must work within the constraints of Cambodia's telecommunications infrastructure, leveraging SMS, mobile internet, and GPS technology to provide real-time visibility without requiring expensive proprietary tracking hardware.

The financial management components must handle Cambodia's multi-currency environment (Cambodian Riel and US Dollar are both widely used), comply with Cambodian tax regulations (including VAT, specific tax, and withholding tax), and support the cash-heavy payment ecosystem that characterizes much of Cambodia's commercial activity. **Return processing** and **expense management** must reflect the operational realities of Cambodian logistics, where cash-on-delivery remains prevalent, driver expense reconciliation is a significant administrative burden, and payment collection from customers often requires flexible installment arrangements.

## 1.4 Target Users and Market Scope

The system targets logistics companies operating within Cambodia, ranging from small domestic courier services handling a few hundred deliveries per day to large-scale freight and logistics operators managing thousands of shipments across multiple provinces. The target market includes:

- **Domestic courier and parcel delivery companies** serving the growing e-commerce sector
- **Freight forwarding companies** handling domestic and international cargo
- **Warehouse and distribution center operators** managing inventory for retail and manufacturing clients
- **E-commerce fulfillment providers** offering warehousing, pick-pack-ship services for online retailers
- **Manufacturing logistics departments** managing inbound raw materials and outbound finished goods
- **Agricultural supply chain operators** handling perishable goods from farm to market
- **Third-party logistics (3PL) providers** offering integrated logistics solutions

## 1.5 Competitive Landscape and Differentiation

Cambodia's nascent logistics technology market includes some regional players (such as 17TRACK, J&T Express proprietary systems, and Grab Express) that offer limited logistics management capabilities. However, these solutions are typically designed for specific use cases (e-commerce last-mile delivery) or specific geographies (urban areas), and do not provide the comprehensive, end-to-end logistics management capabilities required by full-service logistics operators. The proposed CLMS differentiates itself through its focus on the Cambodian market specifically, its comprehensive feature set covering all logistics operations, its support for Cambodia's bilingual (Khmer/English) business environment, and its design for scalability from small operators to enterprise-level logistics companies.

---


# 2. FUNCTIONAL REQUIREMENTS

## 2.1 User Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-UM-001 | User Registration | Admin can create user accounts with personal details, role assignment, and branch assignment |
| FR-UM-002 | User Authentication | Email/password authentication with optional two-factor authentication via SMS |
| FR-UM-003 | User Profile Management | Users can view and edit their own profile information including avatar, contact details |
| FR-UM-004 | Role Assignment | Admin can assign single or multiple roles to users |
| FR-UM-005 | User Activation/Deactivation | Admin can enable or disable user accounts |
| FR-UM-006 | Password Reset | Password reset via email or admin-initiated reset |
| FR-UM-007 | Session Management | Concurrent session control with configurable session timeout |
| FR-UM-008 | User Search and Filter | Search users by name, email, role, branch, and status |

## 2.2 Role and Permission Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-RP-001 | Role CRUD | Create, read, update, and delete roles |
| FR-RP-002 | Permission CRUD | Create, read, update, and delete individual permissions |
| FR-RP-003 | Role-Permission Assignment | Assign granular permissions to roles with module-level grouping |
| FR-RP-004 | Permission Guards | Define permission guards for web and API access |
| FR-RP-005 | Role Hierarchy | Define hierarchical role relationships for inheritance |
| FR-RP-006 | Permission Groups | Organize permissions into logical groups by module |

## 2.3 Branch Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-BM-001 | Branch CRUD | Create, read, update, and delete branch records |
| FR-BM-002 | Branch Details | Store name, code, address, contact information, GPS coordinates |
| FR-BM-003 | Branch Status | Activate or deactivate branches |
| FR-BM-004 | Branch Assignment | Assign users, warehouses, and vehicles to branches |
| FR-BM-005 | Branch Dashboard | View branch-specific metrics and KPIs |

## 2.4 Warehouse Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-WH-001 | Warehouse CRUD | Create, read, update, and delete warehouse records |
| FR-WH-002 | Warehouse Zones | Manage warehouse zones (receiving, storage, picking, shipping, quarantine) |
| FR-WH-003 | Warehouse Shelves | Manage individual shelves within zones with capacity tracking |
| FR-WH-004 | Warehouse Status | Track warehouse operational status (active, maintenance, closed) |
| FR-WH-005 | Warehouse Capacity | Monitor total and available capacity in real-time |
| FR-WH-006 | Zone Assignment | Assign zones to specific product categories or operational purposes |
| FR-WH-007 | Shelf Occupancy | Track shelf occupancy levels and location utilization |

## 2.5 Customer Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-CM-001 | Customer CRUD | Create, read, update, and delete customer records |
| FR-CM-002 | Customer Classification | Classify customers (individual, business, government, VIP) |
| FR-CM-003 | Customer Addresses | Manage multiple delivery/pickup addresses per customer |
| FR-CM-004 | Customer Credit | Set credit limits, payment terms, and credit status |
| FR-CM-005 | Customer History | View complete order, shipment, and payment history |
| FR-CM-006 | Customer Search | Full-text search across customer name, phone, email, company |
| FR-CM-007 | Address Geocoding | Store GPS coordinates for delivery addresses |

## 2.6 Supplier Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-SM-001 | Supplier CRUD | Create, read, update, and delete supplier records |
| FR-SM-002 | Supplier Classification | Classify suppliers by type, region, and services provided |
| FR-SM-003 | Supplier Contacts | Manage multiple contacts per supplier |
| FR-SM-004 | Supplier Status | Track supplier status (active, suspended, blacklisted) |
| FR-SM-005 | Supplier Performance | Track supplier performance metrics |

## 2.7 Vehicle Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-VM-001 | Vehicle CRUD | Create, read, update, and delete vehicle records |
| FR-VM-002 | Vehicle Types | Manage vehicle types (motorcycle, tuk-tuk, pickup, truck, heavy truck, van, container) |
| FR-VM-003 | Vehicle Specifications | Store make, model, year, color, license plate, engine number, chassis number |
| FR-VM-004 | Vehicle Capacity | Track weight capacity, volume capacity, and package capacity |
| FR-VM-005 | Vehicle Status | Track status (available, in-use, maintenance, retired) |
| FR-VM-006 | Vehicle Maintenance | Schedule and track preventive and corrective maintenance |
| FR-VM-007 | Maintenance Alerts | Generate alerts for upcoming maintenance based on mileage or date |
| FR-VM-008 | Vehicle Documents | Track registration, insurance, and inspection expiry dates |
| FR-VM-009 | Vehicle Assignment | Assign vehicles to drivers, routes, and dispatches |
| FR-VM-010 | Fuel Tracking | Track fuel consumption and costs per vehicle |

## 2.8 Driver Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-DM-001 | Driver CRUD | Create, read, update, and delete driver records |
| FR-DM-002 | Driver Documents | Track driver license, medical certificates, and other required documents |
| FR-DM-003 | Document Expiry Alerts | Generate alerts for expiring driver documents |
| FR-DM-004 | Driver Schedules | Create and manage driver work schedules |
| FR-DM-005 | Driver Availability | Track real-time driver availability status |
| FR-DM-006 | Driver Performance | Track delivery success rate, on-time delivery rate, and customer ratings |
| FR-DM-007 | Driver Assignment | Assign drivers to vehicles, routes, and dispatches |
| FR-DM-008 | Driver Location | Track driver GPS location during active deliveries |

## 2.9 Shipment Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-SM-001 | Shipment CRUD | Create, read, update, and delete shipment records |
| FR-SM-002 | Shipment Items | Add multiple items to shipments with dimensions and weight |
| FR-SM-003 | Shipment Status | Update shipment status through the lifecycle |
| FR-SM-004 | Status History | Maintain complete audit trail of status changes |
| FR-SM-005 | Shipment Tracking | Generate unique tracking numbers with barcode/QR support |
| FR-SM-006 | Estimated Delivery | Calculate and display estimated delivery times |
| FR-SM-007 | Shipment Cost | Calculate shipping costs based on weight, volume, distance, and service type |
| FR-SM-008 | Shipment Search | Search by tracking number, customer, date range, status |
| FR-SM-009 | Bulk Operations | Create, update, and manage shipments in bulk |
| FR-SM-010 | Shipment Documents | Generate and attach shipping labels, manifests, and receipts |

## 2.10 Package Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-PM-001 | Package CRUD | Create, read, update, and delete package records |
| FR-PM-002 | Package Items | Track individual items within packages |
| FR-PM-003 | Package Weights | Record actual vs. volumetric weight |
| FR-PM-004 | Package Dimensions | Record length, width, height for each package |
| FR-PM-005 | Package Status | Track package status through processing stages |
| FR-PM-006 | Package Labeling | Generate package labels with barcodes |

## 2.11 Pickup Request Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-PR-001 | Pickup Request CRUD | Create, read, update, and delete pickup requests |
| FR-PR-002 | Pickup Scheduling | Schedule pickup date, time window, and location |
| FR-PR-003 | Pickup Assignment | Assign pickup requests to drivers |
| FR-PR-004 | Pickup Confirmation | Confirm pickup with item count and weight verification |
| FR-PR-005 | Pickup Rejection | Reject pickup requests with reason codes |
| FR-PR-006 | Pickup Tracking | Track pickup request status from creation to completion |
| FR-PR-007 | Customer Pickup Requests | Allow customers to submit pickup requests via web interface |

## 2.12 Route Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-RTM-001 | Route CRUD | Create, read, update, and delete route records |
| FR-RTM-002 | Route Stops | Define intermediate stops along routes |
| FR-RTM-003 | Route Optimization | Calculate optimal routes based on stops, distance, and constraints |
| FR-RTM-004 | Route Templates | Save frequently used routes as templates |
| FR-RTM-005 | Route Cost Estimation | Estimate fuel cost and time for routes |
| FR-RTM-006 | Route Status | Track route status (planned, active, completed) |
| FR-RTM-007 | Stop Sequence | Manage ordered sequence of stops on routes |
| FR-RTM-008 | Stop Time Windows | Define pickup/delivery time windows at each stop |

## 2.13 Dispatch Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-DCM-001 | Dispatch CRUD | Create, read, update, and delete dispatch records |
| FR-DCM-002 | Driver Assignment | Assign one or more drivers to a dispatch |
| FR-DCM-003 | Vehicle Assignment | Assign one or more vehicles to a dispatch |
| FR-DCM-004 | Shipment Allocation | Allocate shipments to dispatches |
| FR-DCM-005 | Dispatch Manifest | Generate dispatch manifests with itemized shipment lists |
| FR-DCM-006 | Dispatch Status | Track dispatch lifecycle (pending, loaded, in-transit, delivered, returned) |
| FR-DCM-007 | Dispatch Confirmation | Confirm dispatch departure and arrival |
| FR-DCM-008 | Dispatch Reconciliation | Reconcile dispatched vs. delivered items |

## 2.14 Tracking and GPS Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-TK-001 | GPS Location Recording | Record GPS coordinates at configurable intervals |
| FR-TK-002 | Location History | View historical GPS data for any vehicle or driver |
| FR-TK-003 | Real-time Tracking | Display current location of active vehicles on map |
| FR-TK-004 | Geofencing | Define and monitor geographic boundaries |
| FR-TK-005 | Speed Monitoring | Track and alert on excessive speed events |
| FR-TK-006 | Trip Summary | Generate trip summaries with distance, duration, and stops |
| FR-TK-007 | Tracking History | Store complete tracking history with timestamps |
| FR-TK-008 | Customer Tracking Portal | Allow customers to track their shipments in real-time |

## 2.15 Inventory Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-INV-001 | Inventory CRUD | Create, read, update, and delete inventory records |
| FR-INV-002 | Stock Movements | Record inbound, outbound, and transfer movements |
| FR-INV-003 | Stock Adjustments | Create manual adjustments with approval workflow |
| FR-INV-004 | Stock Levels | Monitor current stock levels per warehouse and zone |
| FR-INV-005 | Low Stock Alerts | Generate alerts when stock falls below minimum thresholds |
| FR-INV-006 | Stock Reports | Generate inventory valuation, movement, and aging reports |
| FR-INV-007 | Barcode Integration | Support barcode scanning for inventory operations |
| FR-INV-008 | Lot Tracking | Track inventory by lot number and expiry date |

## 2.16 Financial Management Module

### 2.16.1 Invoicing

| ID | Feature | Description |
|----|---------|-------------|
| FR-FIN-001 | Invoice Generation | Generate invoices from shipments and services |
| FR-FIN-002 | Invoice Items | Add line items with quantities, rates, and taxes |
| FR-FIN-003 | Invoice Status | Track invoice lifecycle (draft, sent, paid, overdue, cancelled) |
| FR-FIN-004 | Multi-currency | Support KHR and USD pricing and invoicing |
| FR-FIN-005 | Tax Calculation | Calculate applicable taxes (VAT, specific tax) |
| FR-FIN-006 | Invoice PDF | Generate PDF invoices with company branding |
| FR-FIN-007 | Invoice Search | Search invoices by number, customer, date, status |

### 2.16.2 Payment Processing

| ID | Feature | Description |
|----|---------|-------------|
| FR-FIN-008 | Payment Recording | Record payments against invoices |
| FR-FIN-009 | Payment Methods | Support multiple payment methods (cash, bank transfer, mobile payment) |
| FR-FIN-010 | Payment Reconciliation | Match payments to invoices with partial payment support |
| FR-FIN-011 | Payment Receipts | Generate payment receipts |
| FR-FIN-012 | Outstanding Balance | Track customer outstanding balances |
| FR-FIN-013 | Payment History | View complete payment history per customer |

### 2.16.3 Expense Management

| ID | Feature | Description |
|----|---------|-------------|
| FR-FIN-014 | Expense Recording | Record operational expenses with categories |
| FR-FIN-015 | Expense Categories | Manage expense categories (fuel, maintenance, tolls, wages, etc.) |
| FR-FIN-016 | Expense Approval | Submit expenses for approval workflow |
| FR-FIN-017 | Expense Reports | Generate expense reports by category, period, branch |
| FR-FIN-018 | Driver Expense Reconciliation | Reconcile driver-submitted expenses |

### 2.16.4 Income Management

| ID | Feature | Description |
|----|---------|-------------|
| FR-FIN-019 | Income Recording | Record income from various sources |
| FR-FIN-020 | Income Categories | Manage income categories (shipping fees, warehousing, customs, etc.) |
| FR-FIN-021 | Income Reports | Generate income reports by category, period, branch |
| FR-FIN-022 | Revenue Dashboard | Display revenue metrics and trends |

## 2.17 Returns Management Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-RET-001 | Return Request | Customers can initiate return requests |
| FR-RET-002 | Return Approval | Admin reviews and approves/rejects return requests |
| FR-RET-003 | Return Items | Track individual items being returned |
| FR-RET-004 | Return Status | Track return lifecycle (requested, approved, in-transit, received, processed) |
| FR-RET-005 | Return Reason | Record return reason codes and descriptions |
| FR-RET-006 | Return Refund | Process refunds for returned items |
| FR-RET-007 | Return Reports | Generate return analytics and reports |

## 2.18 Notification Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-NOT-001 | In-App Notifications | Real-time in-app notification system |
| FR-NOT-002 | Email Notifications | Send email notifications for key events |
| FR-NOT-003 | SMS Notifications | Send SMS notifications for delivery updates |
| FR-NOT-004 | Notification Preferences | Users can configure notification preferences |
| FR-NOT-005 | Bulk Notifications | Send bulk notifications to groups of users |
| FR-NOT-006 | Notification History | View notification delivery history |

## 2.19 Activity Logging Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-AL-001 | Activity Recording | Record all user and system activities |
| FR-AL-002 | Activity Search | Search and filter activities by user, type, date, module |
| FR-AL-003 | Activity Details | View detailed activity information including before/after states |
| FR-AL-004 | Activity Reports | Generate activity reports for auditing |
| FR-AL-005 | Activity Retention | Configure activity log retention policies |

## 2.20 System Settings Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-SS-001 | General Settings | Configure company name, logo, contact info, timezone |
| FR-SS-002 | Shipping Settings | Configure shipping rates, zones, and service levels |
| FR-SS-003 | Notification Settings | Configure email/SMS templates and providers |
| FR-SS-004 | Currency Settings | Configure default currency and exchange rates |
| FR-SS-005 | Tax Settings | Configure tax rates and rules |
| FR-SS-006 | Maintenance Mode | Toggle system maintenance mode |
| FR-SS-007 | Backup Settings | Configure automated backup schedules |

## 2.21 Reporting and Analytics Module

| ID | Feature | Description |
|----|---------|-------------|
| FR-RPT-001 | Dashboard | Role-based dashboard with KPIs and metrics |
| FR-RPT-002 | Operational Reports | Shipment volume, delivery performance, route efficiency |
| FR-RPT-003 | Financial Reports | Revenue, expenses, profit/loss, outstanding balances |
| FR-RPT-004 | Customer Reports | Customer activity, top customers, customer retention |
| FR-RPT-005 | Fleet Reports | Vehicle utilization, maintenance costs, fuel consumption |
| FR-RPT-006 | Warehouse Reports | Inventory levels, warehouse utilization, stock movement |
| FR-RPT-007 | Export Reports | Export reports to PDF, Excel, CSV formats |
| FR-RPT-008 | Custom Reports | Create custom reports with configurable parameters |

---


# 3. NON-FUNCTIONAL REQUIREMENTS

## 3.1 Performance

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-P-001 | Page load time | < 2 seconds for standard pages |
| NFR-P-002 | API response time | < 500ms for 95th percentile requests |
| NFR-P-003 | Database query time | < 200ms for standard queries |
| NFR-P-004 | Concurrent users | Support 500+ simultaneous users |
| NFR-P-005 | Data processing | Handle 10,000+ shipment records efficiently |
| NFR-P-006 | File upload | Support uploads up to 50MB |
| NFR-P-007 | Search performance | Full-text search results in < 1 second |
| NFR-P-008 | Report generation | Standard reports in < 5 seconds |

## 3.2 Security

| ID | Requirement | Description |
|----|-------------|-------------|
| NFR-S-001 | Authentication | Secure authentication with password hashing (bcrypt) |
| NFR-S-002 | Authorization | Role-based access control with granular permissions |
| NFR-S-003 | Data encryption | AES-256 encryption for sensitive data at rest |
| NFR-S-004 | Transport security | TLS 1.3 for all data in transit |
| NFR-S-005 | SQL injection prevention | Parameterized queries and ORM usage |
| NFR-S-006 | XSS prevention | Input sanitization and output encoding |
| NFR-S-007 | CSRF protection | CSRF token validation for all state-changing requests |
| NFR-S-008 | Rate limiting | API rate limiting to prevent abuse |
| NFR-S-009 | Audit logging | Complete audit trail for all critical operations |
| NFR-S-010 | Session management | Secure session handling with timeout and invalidation |
| NFR-S-011 | Password policy | Minimum 8 characters, mixed case, numbers, special characters |

## 3.3 Scalability

| ID | Requirement | Description |
|----|-------------|-------------|
| NFR-SC-001 | Horizontal scaling | Architecture supports adding more server instances |
| NFR-SC-002 | Database scaling | Support for database read replicas |
| NFR-SC-003 | File storage | Scalable file storage (local, S3-compatible) |
| NFR-SC-004 | Caching layer | Redis-based caching for frequently accessed data |
| NFR-SC-005 | Queue system | Background job processing via queue system |

## 3.4 Availability

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-A-001 | Uptime | 99.9% availability (excluding planned maintenance) |
| NFR-A-002 | Backup frequency | Daily automated backups with 30-day retention |
| NFR-A-003 | Recovery time | < 4 hours for disaster recovery |
| NFR-A-004 | Recovery point | < 1 hour data loss in disaster scenario |

## 3.5 Usability

| ID | Requirement | Description |
|----|-------------|-------------|
| NFR-U-001 | Responsive design | Fully responsive on desktop, tablet, and mobile |
| NFR-U-002 | Multi-language | English and Khmer language support |
| NFR-U-003 | Accessibility | WCAG 2.1 AA compliance |
| NFR-U-004 | Intuitive navigation | Maximum 3 clicks to reach any feature |
| NFR-U-005 | Error messages | Clear, actionable error messages |
| NFR-U-006 | Loading indicators | Visual feedback for all async operations |
| NFR-U-007 | Keyboard navigation | Full keyboard navigation support |

## 3.6 Compatibility

| ID | Requirement | Description |
|----|-------------|-------------|
| NFR-C-001 | Browser support | Chrome, Firefox, Safari, Edge (latest 2 versions) |
| NFR-C-002 | Mobile OS | iOS 14+, Android 10+ for mobile web |
| NFR-C-003 | API compatibility | RESTful API with JSON responses |
| NFR-C-004 | Email clients | Email notifications compatible with major email clients |

## 3.7 Maintainability

| ID | Requirement | Description |
|----|-------------|-------------|
| NFR-M-001 | Code standards | PSR-12 coding standards for PHP |
| NFR-M-002 | Documentation | Complete API documentation with OpenAPI/Swagger |
| NFR-M-003 | Test coverage | Minimum 80% code coverage for unit tests |
| NFR-M-004 | CI/CD pipeline | Automated testing and deployment pipeline |
| NFR-M-005 | Logging | Structured logging with configurable levels |

---

# 4. BUSINESS RULES

## 4.1 Shipment Business Rules

| ID | Rule |
|----|------|
| BR-SHP-001 | Each shipment must have a unique tracking number generated automatically |
| BR-SHP-002 | A shipment cannot be cancelled if it has already been dispatched |
| BR-SHP-003 | Shipment status can only transition to valid next states (e.g., pending -> processing -> dispatched -> in-transit -> delivered) |
| BR-SHP-004 | Shipment cost must be recalculated when weight or dimensions change |
| BR-SHP-005 | A shipment cannot be delivered without proof of delivery (signature, photo) |
| BR-SHP-006 | COD (Cash on Delivery) shipments must collect payment before marking delivered |
| BR-SHP-007 | Maximum shipment weight is limited by assigned vehicle capacity |
| BR-SHP-008 | Shipments to restricted zones require special authorization |

## 4.2 Warehouse Business Rules

| ID | Rule |
|----|------|
| BR-WHS-001 | Inventory cannot be moved to a shelf that exceeds its capacity |
| BR-WHS-002 | Stock adjustments must have an approval from Warehouse Manager or above |
| BR-WHS-003 | A warehouse zone cannot be deactivated if it contains active inventory |
| BR-WHS-004 | Receiving must be confirmed within 24 hours of warehouse arrival |
| BR-WHS-005 | Damaged goods must be moved to quarantine zone before disposition |

## 4.3 Driver Business Rules

| ID | Rule |
|----|------|
| BR-DRV-001 | A driver cannot be assigned to a dispatch if their license has expired |
| BR-DRV-002 | A driver cannot be assigned to multiple concurrent dispatches |
| BR-DRV-003 | Driver documents must be valid for minimum 30 days from assignment date |
| BR-DRV-004 | A driver can only access dispatch information for their assigned dispatches |
| BR-DRV-005 | Driver schedules cannot exceed maximum allowed working hours per day |

## 4.4 Vehicle Business Rules

| ID | Rule |
|----|------|
| BR-VHC-001 | A vehicle cannot be assigned to a dispatch if it is in maintenance status |
| BR-VHC-002 | Vehicle maintenance must be scheduled before next dispatch if due |
| BR-VHC-003 | Vehicle insurance must be valid for all active dispatches |
| BR-VHC-004 | A vehicle exceeding capacity limits cannot be dispatched |
| BR-VHC-005 | Vehicle inspection must be completed before returning to active fleet |

## 4.5 Financial Business Rules

| ID | Rule |
|----|------|
| BR-FIN-001 | Invoices must be generated within 24 hours of shipment completion |
| BR-FIN-002 | Payment must be recorded with a valid payment method |
| BR-FIN-003 | Partial payments must track remaining balance accurately |
| BR-FIN-004 | Expenses above threshold require manager approval |
| BR-FIN-005 | Refunds must be processed within 7 business days of return approval |
| BR-FIN-006 | Currency conversion rates must be updated daily |
| BR-FIN-007 | All financial transactions must maintain audit trail |
| BR-FIN-008 | Credit limit checks must be performed before new shipment creation |

## 4.6 Return Business Rules

| ID | Rule |
|----|------|
| BR-RET-001 | Returns can only be initiated within 14 days of delivery |
| BR-RET-002 | Return requests must include a valid reason code |
| BR-RET-003 | Returned items must be inspected before refund processing |
| BR-RET-004 | Damaged returns must be documented with photographic evidence |
| BR-RET-005 | Return shipping costs are borne by customer unless item was defective |

## 4.7 User Management Business Rules

| ID | Rule |
|----|------|
| BR-USR-001 | Each user must be assigned to at least one branch |
| BR-USR-002 | A user cannot delete their own account |
| BR-USR-003 | Deactivated users must be logged out of all sessions |
| BR-USR-004 | Admin role can only be assigned by Super Admin |
| BR-USR-005 | Role changes are logged with before and after state |

## 4.8 Dispatch Business Rules

| ID | Rule |
|----|------|
| BR-DSP-001 | A dispatch must have at least one driver and one vehicle assigned |
| BR-DSP-002 | Total shipment weight in a dispatch cannot exceed vehicle capacity |
| BR-DSP-003 | Dispatch manifest must match loaded items count |
| BR-DSP-004 | Dispatch cannot be marked delivered if not all shipments are confirmed |
| BR-DSP-005 | Undelivered items must be recorded with reason codes |

---


# 5. USER ROLES

## 5.1 Super Admin

The Super Admin is the highest-level system administrator with unrestricted access to all system features and configurations. This role is responsible for system-level administration including the creation and management of System Admin accounts, configuration of the entire permission system, management of system-wide settings, database backup and recovery operations, and oversight of all system activity. The Super Admin is the only role that can assign the Super Admin role to other users, manage the system_settings table directly, and access the system audit trail across all branches and modules. In the context of CLMS, the Super Admin is typically a technical director or chief technology officer who maintains the overall system integrity.

**Responsibilities:**
- System installation and configuration
- Managing all user accounts across all branches
- Creating and assigning all roles including System Admin
- Configuring global system settings (company info, payment gateways, notification providers)
- Managing the permission system (creating permissions, assigning to roles)
- Database maintenance, backups, and recovery
- Monitoring system performance and security
- Resolving escalated issues that require system-level intervention
- Managing system updates and deployments
- Accessing all modules without restriction

## 5.2 System Admin

The System Admin has broad administrative access but operates within boundaries set by the Super Admin. This role manages day-to-day system operations including user management, branch configuration, role assignments (except Super Admin), and general system configuration. The System Admin can manage all operational modules but does not have authority over system-critical settings like database configuration or core system parameters. This role is typically held by an IT manager or operations manager.

**Responsibilities:**
- Managing user accounts (create, update, deactivate) for non-administrative roles
- Configuring branches, warehouses, and operational settings
- Managing roles and permissions (except Super Admin role)
- Overseeing notification system configuration
- Managing data imports and exports
- Monitoring system logs and user activity
- Troubleshooting operational issues
- Configuring shipping rates and service levels

## 5.3 Warehouse Manager

The Warehouse Manager is responsible for the overall operations of one or more warehouses. This role oversees inventory management, warehouse staff operations, stock accuracy, receiving and shipping operations, and warehouse capacity planning. The Warehouse Manager can approve stock adjustments, manage warehouse zones and shelves, and generate warehouse-specific reports.

**Responsibilities:**
- Managing warehouse zones, shelves, and capacity allocation
- Overseeing receiving operations and confirming inbound shipments
- Approving stock adjustments and inventory corrections
- Managing warehouse staff assignments and schedules
- Monitoring inventory levels and triggering reorder alerts
- Overseeing picking, packing, and shipping operations
- Generating warehouse performance reports
- Coordinating with logistics team for dispatch scheduling
- Managing damaged goods quarantine and disposition
- Ensuring warehouse safety and compliance standards

## 5.4 Warehouse Staff

Warehouse Staff are the front-line workers who execute day-to-day warehouse operations. They handle receiving, put-away, picking, packing, and shipping tasks as directed by the Warehouse Manager. Warehouse Staff have operational access to inventory management, package handling, and shipment processing within their assigned warehouse.

**Responsibilities:**
- Receiving and inspecting inbound shipments
- Recording received items in the inventory system
- Picking items for outbound shipments
- Packing items and preparing them for dispatch
- Moving inventory between zones and shelves
- Conducting cycle counts and stock verification
- Reporting damaged or missing items
- Operating warehouse equipment
- Maintaining warehouse cleanliness and organization
- Scanning barcodes and updating item locations

## 5.5 Logistics Manager

The Logistics Manager oversees the entire logistics operation, coordinating between warehousing, transportation, and customer service. This role is responsible for strategic logistics planning, route optimization, fleet management oversight, performance monitoring, and ensuring service level agreements are met. The Logistics Manager has visibility across all branches and can manage shipments, routes, and dispatches at the system level.

**Responsibilities:**
- Strategic planning of logistics operations across all branches
- Overseeing shipment lifecycle from creation to delivery
- Managing and optimizing delivery routes
- Monitoring fleet performance and utilization
- Analyzing logistics KPIs and generating performance reports
- Coordinating with Warehouse Managers for inventory and dispatch
- Managing customer escalation for delivery issues
- Approving new shipping routes and service levels
- Overseeing driver management and scheduling
- Managing inter-branch transfers and coordination

## 5.6 Dispatcher

The Dispatcher is responsible for the operational coordination of deliveries and pickups. This role assigns drivers and vehicles to routes and dispatches, monitors active deliveries, manages real-time routing decisions, and ensures timely completion of all assigned deliveries. The Dispatcher works closely with drivers and coordinates with customers for delivery scheduling.

**Responsibilities:**
- Creating dispatches and assigning drivers and vehicles
- Allocating shipments to dispatches based on routes and capacity
- Monitoring real-time GPS locations of active drivers
- Managing driver schedules and availability
- Handling driver check-in and check-out
- Coordinating with customers for delivery time windows
- Reassigning shipments when issues arise
- Generating dispatch manifests and delivery documents
- Managing pickup request assignments
- Reporting delivery performance metrics

## 5.7 Driver

The Driver is the field operative responsible for physically transporting shipments from warehouses to customers (and vice versa for pickups). Drivers interact with the system primarily through mobile interfaces, updating shipment status, recording delivery confirmations, capturing proof of delivery, and reporting issues encountered during delivery.

**Responsibilities:**
- Receiving and reviewing assigned dispatches
- Confirming shipment pickup from warehouse
- Navigating assigned routes and making deliveries
- Recording delivery status updates (picked up, in-transit, delivered, failed)
- Capturing proof of delivery (signature, photo)
- Collecting Cash on Delivery (COD) payments
- Recording delivery exceptions (customer unavailable, address not found, damaged)
- Managing personal schedule and availability
- Reporting vehicle issues and maintenance needs
- Submitting expense claims (fuel, tolls, etc.)
- Communicating with dispatch for real-time coordination

## 5.8 Customer

The Customer is the external end-user who utilizes logistics services for shipping and receiving packages. Customers interact with the system through a web portal or mobile interface to create shipment requests, track packages, manage their addresses, view invoices, make payments, and submit return requests.

**Responsibilities:**
- Creating shipping/shipment requests
- Tracking shipments in real-time
- Managing personal and business addresses
- Viewing and paying invoices
- Submitting pickup requests
- Initiating return requests
- Providing delivery feedback and ratings
- Managing account information and preferences
- Viewing shipment and payment history
- Contacting customer support through the platform

## 5.9 Finance

The Finance role is responsible for the financial operations of the logistics company, including invoicing, payment processing, accounts receivable, accounts payable, and financial reporting. This role ensures that all financial transactions are accurately recorded, payments are properly allocated, and financial reports reflect the true state of the business.

**Responsibilities:**
- Generating invoices from completed shipments
- Recording and reconciling payments
- Managing accounts receivable and collections
- Processing refunds for returns and cancellations
- Managing expense approvals and reimbursements
- Recording income from various service categories
- Generating financial reports (P&L, balance sheet, cash flow)
- Managing multi-currency transactions and exchange rates
- Reconciling driver-submitted cash collections
- Managing credit limits and payment terms for customers
- Ensuring tax compliance and generating tax reports

## 5.10 Accountant

The Accountant works within the finance domain but focuses on detailed record-keeping, reconciliation, and compliance. While the Finance role handles strategic financial management, the Accountant ensures day-to-day financial accuracy, maintains ledgers, reconciles accounts, and prepares financial documentation for auditing and regulatory compliance.

**Responsibilities:**
- Recording daily financial transactions
- Reconciling bank statements with system records
- Managing the chart of accounts
- Processing and verifying expense claims
- Preparing monthly financial statements
- Managing tax calculations and filings
- Maintaining accounts payable and receivable ledgers
- Performing bank reconciliation
- Generating aging reports for receivables
- Preparing documentation for external auditors
- Managing petty cash records

## 5.11 Sales

The Sales role focuses on customer acquisition, relationship management, and revenue growth. Sales staff manage the customer pipeline, negotiate pricing and service agreements, process new customer onboarding, and track sales performance against targets.

**Responsibilities:**
- Managing the customer sales pipeline
- Creating and managing customer accounts
- Negotiating shipping rates and service contracts
- Processing new customer registrations
- Tracking sales targets and commissions
- Generating sales reports and forecasts
- Following up on leads and prospects
- Managing customer relationships and satisfaction
- Creating quotations and proposals
- Coordinating with operations for service delivery

## 5.12 Customer Support

The Customer Support role serves as the primary point of contact for customer inquiries, complaints, and service requests. This role handles shipment inquiries, delivery issue resolution, return processing, and general customer communication.

**Responsibilities:**
- Responding to customer inquiries via phone, email, or chat
- Tracking shipment status for customer inquiries
- Handling delivery complaints and escalations
- Processing return requests and coordinating with operations
- Managing customer feedback and satisfaction surveys
- Documenting customer interactions in the system
- Coordinating with dispatch for delivery issue resolution
- Following up on pending customer issues
- Providing shipment quotes and service information
- Managing lost or damaged package claims

## 5.13 Guest

The Guest role represents unauthenticated or minimally authenticated users who access public-facing features of the system. Guests can perform limited actions such as tracking shipments using tracking numbers, viewing public service information, and submitting basic inquiries.

**Responsibilities:**
- Tracking shipments using tracking number
- Viewing company information and services
- Submitting contact/inquiry forms
- Viewing public FAQ and help documentation
- Submitting pickup requests (with limited information)

---


# 6. USER PERMISSIONS

## 6.1 Permission Structure

Permissions follow the format: module.action with optional scope modifiers. Each permission is assigned to one or more roles through the ole_has_permissions pivot table.

## 6.2 User Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager |
|------------|:-----------:|:------------:|:-----------------:|
| users.view | Yes | Yes | Yes (own branch) |
| users.create | Yes | Yes | No |
| users.update | Yes | Yes | No |
| users.delete | Yes | No | No |
| users.activate | Yes | Yes | No |
| users.export | Yes | Yes | No |

## 6.3 Role and Permission Management Permissions

| Permission | Super Admin | System Admin |
|------------|:-----------:|:------------:|
| roles.view | Yes | Yes |
| roles.create | Yes | Yes |
| roles.update | Yes | Yes |
| roles.delete | Yes | No |
| permissions.view | Yes | Yes |
| permissions.create | Yes | Yes |
| permissions.update | Yes | Yes |
| permissions.delete | Yes | No |

## 6.4 Branch Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager |
|------------|:-----------:|:------------:|:-----------------:|
| branches.view | Yes | Yes | Yes (own) |
| branches.create | Yes | Yes | No |
| branches.update | Yes | Yes | No |
| branches.delete | Yes | No | No |

## 6.5 Warehouse Management Permissions

| Permission | Super Admin | System Admin | Warehouse Manager | Warehouse Staff |
|------------|:-----------:|:------------:|:-----------------:|:---------------:|
| warehouses.view | Yes | Yes | Yes (assigned) | Yes (assigned) |
| warehouses.create | Yes | Yes | No | No |
| warehouses.update | Yes | Yes | Yes (assigned) | No |
| warehouse_zones.view | Yes | Yes | Yes | Yes |
| warehouse_zones.manage | Yes | Yes | Yes | No |
| warehouse_shelves.view | Yes | Yes | Yes | Yes |
| warehouse_shelves.manage | Yes | Yes | Yes | No |
| inventory.view | Yes | Yes | Yes (warehouse) | Yes (warehouse) |
| inventory.create | Yes | Yes | Yes | Yes |
| inventory.update | Yes | Yes | Yes | Yes |
| inventory.adjust | Yes | Yes | Yes (approval) | No |
| stock_movements.view | Yes | Yes | Yes | Yes |
| stock_movements.create | Yes | Yes | Yes | Yes |
| stock_adjustments.view | Yes | Yes | Yes | No |
| stock_adjustments.approve | Yes | Yes | Yes | No |

## 6.6 Customer Management Permissions

| Permission | Super Admin | System Admin | Sales | Customer Support |
|------------|:-----------:|:------------:|:-----:|:----------------:|
| customers.view | Yes | Yes | Yes | Yes |
| customers.create | Yes | Yes | Yes | No |
| customers.update | Yes | Yes | Yes | Yes |
| customers.delete | Yes | Yes | No | No |
| customer_addresses.view | Yes | Yes | Yes | Yes |
| customer_addresses.manage | Yes | Yes | Yes | Yes |

## 6.7 Supplier Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager |
|------------|:-----------:|:------------:|:-----------------:|
| suppliers.view | Yes | Yes | Yes |
| suppliers.create | Yes | Yes | Yes |
| suppliers.update | Yes | Yes | Yes |
| suppliers.delete | Yes | Yes | No |

## 6.8 Vehicle Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher |
|------------|:-----------:|:------------:|:-----------------:|:----------:|
| vehicles.view | Yes | Yes | Yes | Yes |
| vehicles.create | Yes | Yes | Yes | No |
| vehicles.update | Yes | Yes | Yes | No |
| vehicles.delete | Yes | Yes | No | No |
| vehicle_types.view | Yes | Yes | Yes | Yes |
| vehicle_types.manage | Yes | Yes | Yes | No |
| vehicle_maintenances.view | Yes | Yes | Yes | Yes |
| vehicle_maintenances.create | Yes | Yes | Yes | No |
| vehicle_maintenances.approve | Yes | Yes | Yes | No |

## 6.9 Driver Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher |
|------------|:-----------:|:------------:|:-----------------:|:----------:|
| drivers.view | Yes | Yes | Yes | Yes |
| drivers.create | Yes | Yes | Yes | No |
| drivers.update | Yes | Yes | Yes | No |
| drivers.delete | Yes | Yes | No | No |
| driver_documents.view | Yes | Yes | Yes | Yes |
| driver_documents.manage | Yes | Yes | Yes | No |
| driver_schedules.view | Yes | Yes | Yes | Yes |
| driver_schedules.manage | Yes | Yes | Yes | Yes |

## 6.10 Shipment Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher | Driver | Customer |
|------------|:-----------:|:------------:|:-----------------:|:----------:|:------:|:--------:|
| shipments.view | Yes | Yes | Yes | Yes | Assigned | Own |
| shipments.create | Yes | Yes | Yes | Yes | No | Yes |
| shipments.update | Yes | Yes | Yes | Yes | Status | No |
| shipments.cancel | Yes | Yes | Yes | Yes | No | Own |
| shipments.delete | Yes | Yes | No | No | No | No |
| shipment_items.view | Yes | Yes | Yes | Yes | Assigned | Own |
| shipment_items.manage | Yes | Yes | Yes | Yes | No | No |
| shipment_status_history.view | Yes | Yes | Yes | Yes | Yes | Yes |

## 6.11 Package Management Permissions

| Permission | Super Admin | System Admin | Warehouse Manager | Warehouse Staff |
|------------|:-----------:|:------------:|:-----------------:|:---------------:|
| packages.view | Yes | Yes | Yes | Yes |
| packages.create | Yes | Yes | Yes | Yes |
| packages.update | Yes | Yes | Yes | Yes |
| packages.delete | Yes | Yes | No | No |
| package_items.view | Yes | Yes | Yes | Yes |
| package_items.manage | Yes | Yes | Yes | Yes |

## 6.12 Pickup Request Permissions

| Permission | Super Admin | System Admin | Dispatcher | Driver | Customer |
|------------|:-----------:|:------------:|:----------:|:------:|:--------:|
| pickup_requests.view | Yes | Yes | Yes | Assigned | Own |
| pickup_requests.create | Yes | Yes | Yes | No | Yes |
| pickup_requests.update | Yes | Yes | Yes | Status | No |
| pickup_requests.assign | Yes | Yes | Yes | No | No |
| pickup_requests.approve | Yes | Yes | Yes | No | No |

## 6.13 Route Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher |
|------------|:-----------:|:------------:|:-----------------:|:----------:|
| routes.view | Yes | Yes | Yes | Yes |
| routes.create | Yes | Yes | Yes | No |
| routes.update | Yes | Yes | Yes | No |
| routes.delete | Yes | Yes | Yes | No |
| route_stops.view | Yes | Yes | Yes | Yes |
| route_stops.manage | Yes | Yes | Yes | Yes |

## 6.14 Dispatch Management Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher |
|------------|:-----------:|:------------:|:-----------------:|:----------:|
| dispatches.view | Yes | Yes | Yes | Yes |
| dispatches.create | Yes | Yes | Yes | Yes |
| dispatches.update | Yes | Yes | Yes | Yes |
| dispatches.cancel | Yes | Yes | Yes | Yes |
| dispatch_drivers.view | Yes | Yes | Yes | Yes |
| dispatch_drivers.manage | Yes | Yes | Yes | Yes |
| dispatch_vehicles.view | Yes | Yes | Yes | Yes |
| dispatch_vehicles.manage | Yes | Yes | Yes | Yes |

## 6.15 Tracking Permissions

| Permission | Super Admin | System Admin | Logistics Manager | Dispatcher | Driver | Customer |
|------------|:-----------:|:------------:|:-----------------:|:----------:|:------:|:--------:|
| tracking.view | Yes | Yes | Yes | Yes | Own | Own |
| tracking_histories.view | Yes | Yes | Yes | Yes | Own | No |
| tracking_histories.create | Yes | Yes | No | No | Auto | No |

## 6.16 Financial Permissions

### Invoicing

| Permission | Super Admin | System Admin | Finance | Accountant | Customer Support |
|------------|:-----------:|:------------:|:-------:|:----------:|:----------------:|
| invoices.view | Yes | Yes | Yes | Yes | Yes |
| invoices.create | Yes | Yes | Yes | Yes | No |
| invoices.update | Yes | Yes | Yes | Yes | No |
| invoices.delete | Yes | Yes | No | No | No |
| invoice_items.view | Yes | Yes | Yes | Yes | Yes |
| invoice_items.manage | Yes | Yes | Yes | Yes | No |

### Payments

| Permission | Super Admin | System Admin | Finance | Accountant | Customer |
|------------|:-----------:|:------------:|:-------:|:----------:|:--------:|
| payments.view | Yes | Yes | Yes | Yes | Own |
| payments.create | Yes | Yes | Yes | Yes | Yes |
| payments.refund | Yes | Yes | Yes | No | No |
| payment_methods.view | Yes | Yes | Yes | Yes | Yes |
| payment_methods.manage | Yes | Yes | Yes | No | No |

### Expenses

| Permission | Super Admin | System Admin | Finance | Accountant | Driver |
|------------|:-----------:|:------------:|:-------:|:----------:|:------:|
| expenses.view | Yes | Yes | Yes | Yes | Own |
| expenses.create | Yes | Yes | Yes | Yes | Yes |
| expenses.approve | Yes | Yes | Yes | No | No |
| expense_categories.view | Yes | Yes | Yes | Yes | Yes |
| expense_categories.manage | Yes | Yes | Yes | No | No |

### Income

| Permission | Super Admin | System Admin | Finance | Accountant |
|------------|:-----------:|:------------:|:-------:|:----------:|
| income.view | Yes | Yes | Yes | Yes |
| income.create | Yes | Yes | Yes | Yes |
| income_categories.view | Yes | Yes | Yes | Yes |
| income_categories.manage | Yes | Yes | Yes | No |

## 6.17 Returns Permissions

| Permission | Super Admin | System Admin | Customer Support | Warehouse Manager |
|------------|:-----------:|:------------:|:----------------:|:-----------------:|
| returns.view | Yes | Yes | Yes | Yes |
| returns.create | Yes | Yes | Yes | No |
| returns.approve | Yes | Yes | Yes | No |
| returns.process | Yes | Yes | No | Yes |
| return_items.view | Yes | Yes | Yes | Yes |
| return_items.manage | Yes | Yes | Yes | Yes |

## 6.18 Notification Permissions

| Permission | Super Admin | System Admin | Logistics Manager |
|------------|:-----------:|:------------:|:-----------------:|
| notifications.view | Yes | Yes | Yes (own) |
| notifications.send | Yes | Yes | Yes (own branch) |
| notifications.manage | Yes | Yes | No |

## 6.19 System Management Permissions

| Permission | Super Admin | System Admin |
|------------|:-----------:|:------------:|
| system_settings.view | Yes | Yes |
| system_settings.manage | Yes | Yes |
| activity_logs.view | Yes | Yes |
| activity_logs.export | Yes | Yes |
| reports.view | Yes | Yes |
| reports.export | Yes | Yes |
| reports.custom | Yes | Yes |

---


# 7. SYSTEM WORKFLOW

## 7.1 Authentication Workflow

`
1. User navigates to login page
2. User enters email and password
3. System validates credentials against database
4. If valid:
   a. System creates authenticated session
   b. System loads user roles and permissions
   c. System logs authentication event
   d. User is redirected to role-based dashboard
5. If invalid:
   a. System increments failed login counter
   b. System displays error message
   c. If threshold reached, account is temporarily locked
6. User can optionally enable 2FA
   a. System sends SMS verification code
   b. User enters verification code
   c. System validates and grants access
`

## 7.2 Shipment Processing Workflow

`
1. Customer submits shipment request
2. System validates shipment details
3. System calculates shipping cost
4. System generates tracking number
5. Shipment status set to "pending"
6. Finance generates invoice
7. Customer makes payment (or COD setup)
8. Shipment status updated to "confirmed"
9. Warehouse receives picking instruction
10. Warehouse staff picks and packs items
11. Package created and labeled
12. Shipment status updated to "processing"
13. Dispatcher creates dispatch
14. Driver and vehicle assigned
15. Package loaded onto vehicle
16. Shipment status updated to "dispatched"
17. Driver picks up shipment
18. GPS tracking initiated
19. Shipment status updated to "in-transit"
20. Driver arrives at destination
21. Delivery attempted
22. If successful:
    a. Proof of delivery captured
    b. Payment collected (if COD)
    c. Shipment status updated to "delivered"
23. If unsuccessful:
    a. Exception recorded
    b. Re-delivery scheduled
    c. Shipment status updated to "exception"
24. Customer receives delivery notification
25. Invoice marked as paid (or pending for COD)
`

## 7.3 Inventory Management Workflow

`
1. Inbound shipment arrives at warehouse
2. Warehouse staff receives shipment
3. Items inspected for damage
4. If items match purchase order:
    a. Items entered into inventory system
    b. Stock levels updated
    c. Items moved to designated zone/shelf
    d. Stock movement recorded
5. If items have discrepancies:
    a. Discrepancy recorded
    b. Stock adjusted with approval
    c. Discrepancy report generated
6. Outbound:
    a. Pick list generated from shipment
    b. Warehouse staff picks items from zones
    c. Items packed and labeled
    d. Stock levels decremented
    e. Stock movement recorded
7. Stock adjustments:
    a. Staff submits adjustment request
    b. Manager reviews and approves
    c. Stock levels updated
    d. Audit trail recorded
`

## 7.4 Fleet Management Workflow

`
1. Vehicle registered in system
2. Vehicle assigned to branch
3. Maintenance schedule created
4. When maintenance is due:
    a. Alert generated for Logistics Manager
    b. Vehicle status changed to "maintenance"
    c. Vehicle removed from active dispatch pool
    d. Maintenance performed and recorded
    e. Vehicle returned to active fleet
5. When dispatch is created:
    a. Available vehicles identified
    b. Vehicle selected based on capacity requirements
    c. Vehicle assigned to dispatch
    d. Vehicle status changed to "in-use"
6. When dispatch is completed:
    a. Vehicle returned to available pool
    b. Fuel consumption recorded
    c. Mileage updated
    d. Vehicle status changed to "available"
`

---

# 8. BUSINESS WORKFLOW

## 8.1 End-to-End Business Process

`
CUSTOMER INTERACTION
    |
    v
[Customer Registration/Authentication]
    |
    v
[Create Shipment Request / Pickup Request]
    |
    v
[Price Calculation & Quotation]
    |
    v
[Customer Confirms & Payment]
    |
    v
WAREHOUSE OPERATIONS
    |
    v
[Receive Items / Pick & Pack]
    |
    v
[Quality Check & Labeling]
    |
    v
[Package Ready for Dispatch]
    |
    v
DISPATCH OPERATIONS
    |
    v
[Create Dispatch & Assign Driver/Vehicle]
    |
    v
[Load Packages & Generate Manifest]
    |
    v
[Driver Picks Up & Departs]
    |
    v
DELIVERY OPERATIONS
    |
    v
[Driver Follows Route & Delivers]
    |
    v
[Proof of Delivery / Payment Collection]
    |
    v
[Delivery Confirmation]
    |
    v
POST-DELIVERY
    |
    v
[Invoice Reconciliation / Return Processing (if applicable)]
    |
    v
[Customer Feedback & Rating]
`

## 8.2 Customer Onboarding Workflow

`
1. Sales team identifies potential customer
2. Customer information captured in system
3. Customer classification assigned (individual/business/VIP)
4. Credit limit and payment terms established
5. Customer account activated
6. Welcome notification sent
7. Customer receives portal access credentials
8. Customer can begin creating shipment requests
`

## 8.3 Return Business Process

`
1. Customer initiates return request (within 14 days)
2. Customer Support reviews return request
3. Return reason code assigned
4. If approved:
    a. Return label generated
    b. Customer ships item back
    c. Driver collects return package
    d. Return shipment status tracked
5. Warehouse receives returned item
6. Item inspection performed
7. Return item condition documented
8. If item is saleable:
    a. Item returned to inventory
    b. Stock levels updated
9. If item is damaged:
    a. Item moved to quarantine
    b. Disposition decision made
10. Refund processed
11. Customer notified of refund
12. Invoice adjusted
13. Return status updated to "completed"
`

## 8.4 Expense Approval Workflow

`
1. Expense submitted (by driver, staff, or admin)
2. Supporting documents attached
3. Expense categorized
4. If amount exceeds threshold:
    a. Routed to Finance for review
    b. Finance verifies against policies
    c. If approved: Payment processed
    d. If rejected: Returned with reason
5. If amount is below threshold:
    a. Auto-approved or manager-approved
    b. Payment processed
6. Expense recorded in financial ledger
7. Driver/staff notified of reimbursement
`

## 8.5 Dispatch Coordination Workflow

`
1. Dispatcher reviews pending shipments
2. Shipments grouped by route/destination
3. Dispatcher creates new dispatch
4. Available drivers identified
5. Driver selected based on:
    a. Schedule availability
    b. Document validity
    c. Route familiarity
    d. Performance metrics
6. Vehicle selected based on:
    a. Capacity requirements
    b. Maintenance status
    c. Fuel level
    d. Location proximity
7. Dispatch manifest generated
8. Driver notified of assignment
9. Driver confirms acceptance
10. Packages loaded and verified
11. Driver departs with dispatch
12. Dispatcher monitors progress
13. Driver marks deliveries as completed
14. Dispatcher reconciles dispatch
15. Undelivered items documented
16. Dispatch marked as completed
`

---


# 9. DATA FLOW

## 9.1 Shipment Data Flow

`
[Customer Portal] --> (Shipment Request) --> [Shipment Service]
                                                    |
                                                    v
                                            [Database: shipments]
                                                    |
                                                    v
                                            [Cost Calculator]
                                                    |
                                                    v
                                            [Invoice Service] --> [Database: invoices]
                                                    |
                                                    v
                                            [Payment Service] --> [Database: payments]
                                                    |
                                                    v
                                            [Warehouse Service] --> [Database: inventory]
                                                    |
                                                    v
                                            [Dispatch Service] --> [Database: dispatches]
                                                    |
                                                    v
                                            [Driver Mobile App] --> [Database: tracking_histories]
                                                    |
                                                    v
                                            [Delivery Confirmation] --> [Database: shipments]
                                                    |
                                                    v
                                            [Notification Service] --> [Customer]
`

## 9.2 Inventory Data Flow

`
[Inbound Shipment] --> [Warehouse Staff] --> [Receiving Process]
                                                    |
                                                    v
                                            [Inventory Update] --> [Database: inventory]
                                                    |
                                                    v
                                            [Stock Movement] --> [Database: stock_movements]
                                                    |
                                                    v
                                            [Zone/Shelf Assignment] --> [Database: warehouse_shelves]
                                                    |
                                            [Outbound Pick]
                                                    |
                                                    v
                                            [Stock Decrement] --> [Database: inventory]
                                                    |
                                                    v
                                            [Package Creation] --> [Database: packages]
`

## 9.3 Financial Data Flow

`
[Shipment Completion] --> [Invoice Generation] --> [Database: invoices]
                                                            |
                                                            v
                                                    [Payment Recording]
                                                            |
                                                            v
                                                    [Database: payments]
                                                            |
                                                            v
                                                    [Ledger Update]
                                                            |
                                        +-------------------+-------------------+
                                        |                   |                   |
                                        v                   v                   v
                                [Revenue Ledger]   [Expense Ledger]   [Accounts Receivable]
                                        |                   |                   |
                                        v                   v                   v
                                [Financial Reports]  [Tax Reports]   [Balance Sheet]
`

## 9.4 Tracking Data Flow

`
[Driver Mobile App] --> (GPS Coordinates) --> [Tracking Service]
                                                        |
                                                        v
                                                [Database: tracking_histories]
                                                        |
                                                        v
                                                [Real-time Processing]
                                                        |
                                        +---------------+---------------+
                                        |               |               |
                                        v               v               v
                                [Dispatcher Map] [Customer Portal] [Admin Dashboard]
`

---

# 10. USE CASE DIAGRAM

`
+================================================================================================+
|                          CAMBODIA LOGISTICS MANAGEMENT SYSTEM                                   |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+                          |
|  |   Guest   |--------| Track Shipment  |        | View Company Info|                          |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  | Customer  |--------| Create Shipment |--------| Make Payment     |---| Track Delivery    |  |
|  |           |--------| Submit Pickup   |--------| View Invoices    |   | Submit Return     |  |
|  |           |--------| Manage Profile  |--------| Manage Addresses |   | Rate Delivery     |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |  Driver   |--------| View Dispatch   |--------| Update Status    |---| Record Delivery   |  |
|  |           |--------| GPS Tracking    |--------| Submit Expenses  |   | Collect Payment   |  |
|  |           |--------| View Schedule   |--------| Manage Documents |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |Dispatcher |--------| Create Dispatch |--------| Assign Driver    |---| Assign Vehicle    |  |
|  |           |--------| Monitor Fleet   |--------| Allocate Shipment|   | Track Dispatches  |  |
|  |           |--------| Manage Routes   |--------| Assign Pickups   |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |Warehouse  |--------| Manage Zones    |--------| Process Inbound  |---| Process Outbound  |  |
|  |Staff      |--------| Scan Barcodes   |--------| Pick & Pack      |   | Update Inventory  |  |
|  |           |--------| Move Inventory  |--------| Verify Shipments |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |Warehouse  |--------| Manage Warehouse|--------| Approve Adjus.   |---| Monitor Capacity  |  |
|  |Manager    |--------| View Reports    |--------| Manage Staff     |   | Plan Operations   |  |
|  |           |--------| Zone Management |--------| Quality Control  |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |Logistics  |--------| Plan Routes     |--------| Monitor Fleet    |---| Optimize Operation|  |
|  |Manager    |--------| Oversee Shipment|--------| Generate Reports |   | SLA Management    |  |
|  |           |--------| Coordinate Brch |--------| Performance KPIs |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  | Sales     |--------| Manage Customer |--------| Create Quotation |---| Track Pipeline    |  |
|  |           |--------| Set Pricing     |--------| Process Orders   |   | Sales Reports     |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  | Customer  |--------| Handle Inquiry  |--------| Process Returns  |---| Resolve Complaint |  |
|  | Support   |--------| Track Issues    |--------| Follow Up        |   | Doc. Interaction  |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  | Finance   |--------| Generate Invoice|--------| Process Payment  |---| Manage Receivable |  |
|  |           |--------| Approve Expense |--------| Process Refund   |   | Financial Reports |  |
|  |           |--------| Reconcile       |--------| Credit Mgmt      |   +-------------------+  |
|  +-----------+        +-----------------+        +------------------+                          |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  | Accountant|--------| Record Trans.   |--------| Reconcile Acct.  |---| Tax Compliance    |  |
|  |           |--------| Maintain Ledger |--------| Bank Reconcil.   |   | Audit Prep        |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |System     |--------| Manage Users    |--------| Configure System |---| Monitor Activity  |  |
|  |Admin      |--------| Manage Roles    |--------| Backup/Restore   |   | System Settings   |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|                                                                                               |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
|  |Super Admin|--------| Full System     |--------| DB Management    |---| Security Mgmt     |  |
|  |           |--------| User Admin      |--------| Permission Mgmt  |   | System Deploy     |  |
|  +-----------+        +-----------------+        +------------------+   +-------------------+  |
+================================================================================================+
`

---


# 11. ENTITY RELATIONSHIP DIAGRAM

## 11.1 High-Level Entity Relationship Diagram

`
+================================================================================================+
|                        ENTITY RELATIONSHIP DIAGRAM - CLMS                                      |
+================================================================================================+

[roles] 1--------* [role_has_permissions] *--------1 [permissions]
                                  |
                                  |
[users] 1--------* [model_has_roles] *--------1 [roles]
  |
  +--1--------* [model_has_permissions] *--------1 [permissions]
  |
  +--1--------* [notifications]
  |
  +--1--------* [activity_logs]

[branches] 1--------* [users]
  |
  +--1--------* [warehouses]
  |               |
  |               +--1--------* [warehouse_zones]
  |               |               |
  |               |               +--1--------* [warehouse_shelves]
  |               |
  |               +--1--------* [inventory] *--------1 [stock_movements]
  |               |
  |               +--1--------* [stock_adjustments]
  |
  +--1--------* [vehicles]
  |               |
  |               +--*--------1 [vehicle_types]
  |               |
  |               +--1--------* [vehicle_maintenances]
  |
  +--1--------* [drivers]
  |               |
  |               +--1--------* [driver_documents]
  |               |
  |               +--1--------* [driver_schedules]
  |
  +--1--------* [customers]
  |               |
  |               +--1--------* [customer_addresses]
  |               |
  |               +--1--------* [shipments]
  |               |               |
  |               |               +--1--------* [shipment_items]
  |               |               |
  |               |               +--1--------* [shipment_status_history]
  |               |               |
  |               |               +--1--------* [packages]
  |               |               |               |
  |               |               |               +--1--------* [package_items]
  |               |               |
  |               |               +--*--------1 [routes]
  |               |               |               |
  |               |               |               +--1--------* [route_stops]
  |               |               |
  |               |               +--*--------* [dispatches]
  |               |                               |
  |               |                               +--1--------* [dispatch_drivers] *--------1 [drivers]
  |               |                               |
  |               |                               +--1--------* [dispatch_vehicles] *--------1 [vehicles]
  |               |
  |               +--1--------* [pickup_requests]
  |               |
  |               +--1--------* [invoices]
  |               |               |
  |               |               +--1--------* [invoice_items]
  |               |               |
  |               |               +--1--------* [payments]
  |               |
  |               +--1--------* [returns]
  |                               |
  |                               +--1--------* [return_items]
  |
  +--*--------1 [payment_methods]
  |
  +--1--------* [expenses] *--------1 [expense_categories]
  |
  +--1--------* [income] *--------1 [income_categories]

[suppliers] --- (referenced by vehicle_maintenances)
[system_settings] --- (global key-value configuration store)
`

## 11.2 Detailed Relationship Map

| Relationship | Type | Description |
|--------------|------|-------------|
| users -> roles | many-to-many | Via model_has_roles pivot table |
| users -> permissions | many-to-many | Via model_has_permissions pivot table |
| roles -> permissions | many-to-many | Via role_has_permissions pivot table |
| branches -> users | one-to-many | A branch has many users |
| branches -> warehouses | one-to-many | A branch has many warehouses |
| branches -> vehicles | one-to-many | A branch has many vehicles |
| branches -> drivers | one-to-many | A branch has many drivers |
| branches -> customers | one-to-many | A branch has many customers |
| warehouses -> warehouse_zones | one-to-many | A warehouse has many zones |
| warehouse_zones -> warehouse_shelves | one-to-many | A zone has many shelves |
| warehouses -> inventory | one-to-many | A warehouse has many inventory records |
| warehouses -> stock_adjustments | one-to-many | A warehouse has many adjustments |
| inventory -> stock_movements | one-to-many | Inventory items have many movements |
| stock_movements -> inventory | many-to-one | A movement references an inventory record |
| vehicle_types -> vehicles | one-to-many | A vehicle type has many vehicles |
| vehicles -> vehicle_maintenances | one-to-many | A vehicle has many maintenance records |
| suppliers -> vehicle_maintenances | one-to-many | A supplier services many maintenances |
| drivers -> driver_documents | one-to-many | A driver has many documents |
| drivers -> driver_schedules | one-to-many | A driver has many schedules |
| routes -> route_stops | one-to-many | A route has many stops |
| customers -> customer_addresses | one-to-many | A customer has many addresses |
| customers -> shipments | one-to-many | A customer has many shipments |
| customers -> invoices | one-to-many | A customer has many invoices |
| customers -> pickup_requests | one-to-many | A customer has many pickup requests |
| customers -> returns | one-to-many | A customer has many returns |
| invoices -> invoice_items | one-to-many | An invoice has many line items |
| invoices -> payments | one-to-many | An invoice has many payments |
| shipments -> shipment_items | one-to-many | A shipment has many items |
| shipments -> shipment_status_history | one-to-many | A shipment has many status changes |
| shipments -> packages | one-to-many | A shipment has many packages |
| packages -> package_items | one-to-many | A package has many items |
| shipments -> routes | many-to-one | A shipment follows a route |
| dispatches -> dispatch_drivers | one-to-many | A dispatch has many driver assignments |
| dispatches -> dispatch_vehicles | one-to-many | A dispatch has many vehicle assignments |
| dispatches -> shipments | many-to-many | Shipments are allocated to dispatches |
| drivers -> dispatches | many-to-many | Via dispatch_drivers pivot |
| vehicles -> dispatches | many-to-many | Via dispatch_vehicles pivot |
| returns -> return_items | one-to-many | A return has many items |
| expenses -> expense_categories | many-to-one | An expense belongs to a category |
| income -> income_categories | many-to-one | Income belongs to a category |
| users -> notifications | one-to-many | A user has many notifications |
| users -> activity_logs | one-to-many | A user has many activity logs |

---

# 12. DATABASE DICTIONARY

---

## 12.1 Authentication & Authorization Tables

---

### Table: users

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each user |
| name | VARCHAR(255) | NOT NULL | - | Full name of the user |
| email | VARCHAR(255) | UNIQUE, NOT NULL | - | Email address used for login |
| email_verified_at | TIMESTAMP | NULLABLE | NULL | Timestamp when email was verified |
| password | VARCHAR(255) | NOT NULL | - | Hashed password (bcrypt) |
| remember_token | VARCHAR(100) | NULLABLE | NULL | Token for "remember me" functionality |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NULLABLE | NULL | Branch assignment for the user |
| phone | VARCHAR(20) | NULLABLE | NULL | Contact phone number |
| avatar | VARCHAR(500) | NULLABLE | NULL | Path to user avatar image |
| status | ENUM('active','inactive','suspended') | NOT NULL | 'active' | Account status |
| last_login_at | TIMESTAMP | NULLABLE | NULL | Timestamp of last successful login |
| last_login_ip | VARCHAR(45) | NULLABLE | NULL | IP address of last login |
| two_factor_enabled | BOOLEAN | NOT NULL | FALSE | Whether 2FA is enabled |
| two_factor_secret | VARCHAR(255) | NULLABLE | NULL | Encrypted 2FA secret |
| language | VARCHAR(5) | NOT NULL | 'en' | Preferred language (en/km) |
| timezone | VARCHAR(50) | NOT NULL | 'Asia/Phnom_Penh' | User timezone preference |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (email)
- INDEX (branch_id)
- INDEX (status)
- INDEX (deleted_at)

---

### Table: oles

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each role |
| name | VARCHAR(125) | UNIQUE, NOT NULL | - | Role name (e.g., 'super_admin') |
| guard_name | VARCHAR(125) | NOT NULL | 'web' | Authentication guard for this role |
| display_name | VARCHAR(255) | NULLABLE | NULL | Human-readable display name |
| description | TEXT | NULLABLE | NULL | Detailed description of the role |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name, guard_name)

---

### Table: permissions

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each permission |
| name | VARCHAR(125) | UNIQUE, NOT NULL | - | Permission identifier (e.g., 'users.create') |
| guard_name | VARCHAR(125) | NOT NULL | 'web' | Authentication guard for this permission |
| display_name | VARCHAR(255) | NULLABLE | NULL | Human-readable permission name |
| description | TEXT | NULLABLE | NULL | Detailed description of the permission |
| module | VARCHAR(100) | NULLABLE | NULL | Module this permission belongs to |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name, guard_name)
- INDEX (module)

---

### Table: model_has_roles

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| role_id | BIGINT UNSIGNED | FK -> roles.id, PK | - | Role identifier |
| model_type | VARCHAR(255) | PK | - | Polymorphic model type (e.g., 'App\\\Models\\\User') |
| model_id | BIGINT UNSIGNED | PK | - | Polymorphic model identifier |

**Indexes:**
- PRIMARY KEY (role_id, model_type, model_id)
- INDEX (model_type, model_id)

---

### Table: model_has_permissions

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| permission_id | BIGINT UNSIGNED | FK -> permissions.id, PK | - | Permission identifier |
| model_type | VARCHAR(255) | PK | - | Polymorphic model type (e.g., 'App\\\Models\\\User') |
| model_id | BIGINT UNSIGNED | PK | - | Polymorphic model identifier |

**Indexes:**
- PRIMARY KEY (permission_id, model_type, model_id)
- INDEX (model_type, model_id)

---

### Table: ole_has_permissions

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| permission_id | BIGINT UNSIGNED | FK -> permissions.id, PK | - | Permission identifier |
| role_id | BIGINT UNSIGNED | FK -> roles.id, PK | - | Role identifier |

**Indexes:**
- PRIMARY KEY (permission_id, role_id)
- INDEX (role_id)

---


## 12.2 Branch & Warehouse Tables

---

### Table: ranches

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each branch |
| name | VARCHAR(255) | NOT NULL | - | Branch name |
| code | VARCHAR(50) | UNIQUE, NOT NULL | - | Short branch code (e.g., 'PP01') |
| address | TEXT | NULLABLE | NULL | Full address of the branch |
| city | VARCHAR(100) | NULLABLE | NULL | City where branch is located |
| province | VARCHAR(100) | NULLABLE | NULL | Province where branch is located |
| country | VARCHAR(50) | NOT NULL | 'Cambodia' | Country |
| postal_code | VARCHAR(10) | NULLABLE | NULL | Postal/ZIP code |
| phone | VARCHAR(20) | NULLABLE | NULL | Branch contact phone |
| email | VARCHAR(255) | NULLABLE | NULL | Branch contact email |
| latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude coordinate |
| longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude coordinate |
| operating_hours_start | TIME | NULLABLE | NULL | Branch opening time |
| operating_hours_end | TIME | NULLABLE | NULL | Branch closing time |
| status | ENUM('active','inactive','maintenance') | NOT NULL | 'active' | Branch operational status |
| manager_name | VARCHAR(255) | NULLABLE | NULL | Name of branch manager |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (status)
- INDEX (city)
- INDEX (province)

---

### Table: warehouses

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each warehouse |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch this warehouse belongs to |
| name | VARCHAR(255) | NOT NULL | - | Warehouse name |
| code | VARCHAR(50) | UNIQUE, NOT NULL | - | Short warehouse code (e.g., 'WH-PP-01') |
| description | TEXT | NULLABLE | NULL | Description of warehouse |
| address | TEXT | NULLABLE | NULL | Warehouse address (may differ from branch) |
| latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude coordinate |
| longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude coordinate |
| total_capacity | DECIMAL(12, 2) | NULLABLE | NULL | Total capacity in cubic meters |
| used_capacity | DECIMAL(12, 2) | NOT NULL | 0.00 | Currently used capacity in cubic meters |
| capacity_unit | VARCHAR(10) | NOT NULL | 'm3' | Unit of capacity measurement |
| warehouse_type | ENUM('standard','cold_storage','bonded','hazmat','open_air') | NOT NULL | 'standard' | Type of warehouse facility |
| status | ENUM('active','inactive','maintenance','closed') | NOT NULL | 'active' | Warehouse operational status |
| manager_id | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User ID of warehouse manager |
| operating_hours_start | TIME | NULLABLE | NULL | Warehouse opening time |
| operating_hours_end | TIME | NULLABLE | NULL | Warehouse closing time |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (branch_id)
- INDEX (status)
- INDEX (warehouse_type)
- INDEX (manager_id)

---

### Table: warehouse_zones

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each zone |
| warehouse_id | BIGINT UNSIGNED | FK -> warehouses.id, NOT NULL | - | Warehouse this zone belongs to |
| name | VARCHAR(255) | NOT NULL | - | Zone name (e.g., 'Receiving Bay A') |
| code | VARCHAR(50) | NOT NULL | - | Zone code (e.g., 'RCV-A') |
| description | TEXT | NULLABLE | NULL | Description of the zone |
| zone_type | ENUM('receiving','storage','picking','packing','shipping','quarantine','staging','returns') | NOT NULL | 'storage' | Functional type of the zone |
| temperature_controlled | BOOLEAN | NOT NULL | FALSE | Whether zone has temperature control |
| min_temperature | DECIMAL(5, 2) | NULLABLE | NULL | Minimum temperature (for cold storage) |
| max_temperature | DECIMAL(5, 2) | NULLABLE | NULL | Maximum temperature (for cold storage) |
| total_capacity | DECIMAL(12, 2) | NULLABLE | NULL | Zone capacity in cubic meters |
| used_capacity | DECIMAL(12, 2) | NOT NULL | 0.00 | Currently used capacity |
| status | ENUM('active','inactive','maintenance','full') | NOT NULL | 'active' | Zone operational status |
| priority | INT | NOT NULL | 0 | Display priority/ordering |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (warehouse_id, code)
- INDEX (warehouse_id)
- INDEX (zone_type)
- INDEX (status)

---

### Table: warehouse_shelves

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each shelf |
| warehouse_zone_id | BIGINT UNSIGNED | FK -> warehouse_zones.id, NOT NULL | - | Zone this shelf belongs to |
| name | VARCHAR(255) | NOT NULL | - | Shelf name/label (e.g., 'A-01-03') |
| code | VARCHAR(50) | NOT NULL | - | Shelf code for barcode generation |
| row | VARCHAR(10) | NULLABLE | NULL | Row identifier within the zone |
| rack | VARCHAR(10) | NULLABLE | NULL | Rack number |
| level | VARCHAR(10) | NULLABLE | NULL | Level/tier on the rack |
| position | VARCHAR(10) | NULLABLE | NULL | Position within the level |
| description | TEXT | NULLABLE | NULL | Description of the shelf |
| max_weight | DECIMAL(10, 2) | NULLABLE | NULL | Maximum weight capacity in kg |
| max_volume | DECIMAL(10, 2) | NULLABLE | NULL | Maximum volume capacity in m3 |
| current_weight | DECIMAL(10, 2) | NOT NULL | 0.00 | Current weight on shelf |
| current_volume | DECIMAL(10, 2) | NOT NULL | 0.00 | Current volume on shelf |
| product_category | VARCHAR(100) | NULLABLE | NULL | Assigned product category (nullable = any) |
| is_fragile | BOOLEAN | NOT NULL | FALSE | Whether shelf is designated for fragile items |
| status | ENUM('available','occupied','reserved','maintenance','blocked') | NOT NULL | 'available' | Shelf status |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (warehouse_zone_id, code)
- INDEX (warehouse_zone_id)
- INDEX (status)
- INDEX (product_category)

---

## 12.3 Customer & Supplier Tables

---

### Table: customers

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each customer |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NULLABLE | NULL | Branch associated with the customer |
| name | VARCHAR(255) | NOT NULL | - | Customer name (person or company) |
| code | VARCHAR(50) | UNIQUE, NOT NULL | - | Customer code (e.g., 'CUST-00001') |
| customer_type | ENUM('individual','business','government','vip') | NOT NULL | 'individual' | Classification of customer |
| company_name | VARCHAR(255) | NULLABLE | NULL | Company name (for business customers) |
| tax_id | VARCHAR(50) | NULLABLE | NULL | Tax identification number |
| email | VARCHAR(255) | NULLABLE | NULL | Customer email address |
| phone | VARCHAR(20) | NOT NULL | - | Primary contact phone number |
| secondary_phone | VARCHAR(20) | NULLABLE | NULL | Secondary contact phone |
| address | TEXT | NULLABLE | NULL | Primary address |
| city | VARCHAR(100) | NULLABLE | NULL | City |
| province | VARCHAR(100) | NULLABLE | NULL | Province |
| country | VARCHAR(50) | NOT NULL | 'Cambodia' | Country |
| latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude of primary location |
| longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude of primary location |
| credit_limit | DECIMAL(12, 2) | NOT NULL | 0.00 | Maximum credit limit |
| current_balance | DECIMAL(12, 2) | NOT NULL | 0.00 | Current outstanding balance |
| payment_terms | INT | NOT NULL | 0 | Payment terms in days (0 = prepaid) |
| preferred_payment_method | VARCHAR(50) | NULLABLE | NULL | Preferred payment method |
| discount_percentage | DECIMAL(5, 2) | NOT NULL | 0.00 | Default discount percentage |
| status | ENUM('active','inactive','suspended','blacklisted') | NOT NULL | 'active' | Customer account status |
| notes | TEXT | NULLABLE | NULL | Internal notes about customer |
| created_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who created the record |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (branch_id)
- INDEX (customer_type)
- INDEX (status)
- INDEX (phone)
- INDEX (email)
- INDEX (deleted_at)

---

### Table: customer_addresses

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each address |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer this address belongs to |
| label | VARCHAR(100) | NOT NULL | - | Address label (e.g., 'Office', 'Warehouse') |
| address_type | ENUM('pickup','delivery','both') | NOT NULL | 'both' | Purpose of this address |
| contact_name | VARCHAR(255) | NULLABLE | NULL | Contact person at this address |
| contact_phone | VARCHAR(20) | NULLABLE | NULL | Contact phone at this address |
| address_line1 | VARCHAR(255) | NOT NULL | - | Address line 1 |
| address_line2 | VARCHAR(255) | NULLABLE | NULL | Address line 2 |
| city | VARCHAR(100) | NOT NULL | - | City |
| province | VARCHAR(100) | NOT NULL | - | Province |
| country | VARCHAR(50) | NOT NULL | 'Cambodia' | Country |
| postal_code | VARCHAR(10) | NULLABLE | NULL | Postal code |
| latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude |
| longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude |
| delivery_instructions | TEXT | NULLABLE | NULL | Special delivery instructions |
| is_default | BOOLEAN | NOT NULL | FALSE | Whether this is the default address |
| status | ENUM('active','inactive') | NOT NULL | 'active' | Address status |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (customer_id)
- INDEX (address_type)
- INDEX (is_default)
- INDEX (province)

---

### Table: suppliers

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each supplier |
| name | VARCHAR(255) | NOT NULL | - | Supplier name |
| code | VARCHAR(50) | UNIQUE, NOT NULL | - | Supplier code (e.g., 'SUP-0001') |
| supplier_type | ENUM('carrier','warehouse','equipment','service','other') | NOT NULL | 'carrier' | Type of supplier |
| company_name | VARCHAR(255) | NULLABLE | NULL | Legal company name |
| tax_id | VARCHAR(50) | NULLABLE | NULL | Tax identification number |
| contact_person | VARCHAR(255) | NULLABLE | NULL | Primary contact person |
| email | VARCHAR(255) | NULLABLE | NULL | Supplier email |
| phone | VARCHAR(20) | NOT NULL | - | Primary phone number |
| secondary_phone | VARCHAR(20) | NULLABLE | NULL | Secondary phone |
| address | TEXT | NULLABLE | NULL | Supplier address |
| city | VARCHAR(100) | NULLABLE | NULL | City |
| province | VARCHAR(100) | NULLABLE | NULL | Province |
| country | VARCHAR(50) | NOT NULL | 'Cambodia' | Country |
| bank_name | VARCHAR(255) | NULLABLE | NULL | Bank name for payments |
| bank_account_number | VARCHAR(50) | NULLABLE | NULL | Bank account number |
| bank_account_name | VARCHAR(255) | NULLABLE | NULL | Bank account holder name |
| payment_terms | INT | NOT NULL | 30 | Payment terms in days |
| rating | DECIMAL(3, 2) | NULLABLE | NULL | Supplier rating (1.00 - 5.00) |
| status | ENUM('active','inactive','suspended','blacklisted') | NOT NULL | 'active' | Supplier status |
| notes | TEXT | NULLABLE | NULL | Internal notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (supplier_type)
- INDEX (status)
- INDEX (deleted_at)

---


## 12.4 Vehicle & Driver Tables

---

### Table: ehicles

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each vehicle |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch this vehicle is assigned to |
| vehicle_type_id | BIGINT UNSIGNED | FK -> vehicle_types.id, NOT NULL | - | Type of vehicle |
| name | VARCHAR(255) | NOT NULL | - | Vehicle display name |
| plate_number | VARCHAR(20) | UNIQUE, NOT NULL | - | License plate number |
| make | VARCHAR(100) | NULLABLE | NULL | Vehicle manufacturer |
| model | VARCHAR(100) | NULLABLE | NULL | Vehicle model |
| year | INT | NULLABLE | NULL | Manufacturing year |
| color | VARCHAR(50) | NULLABLE | NULL | Vehicle color |
| engine_number | VARCHAR(100) | NULLABLE | NULL | Engine serial number |
| chassis_number | VARCHAR(100) | NULLABLE | NULL | Chassis/VIN number |
| max_weight_capacity | DECIMAL(10, 2) | NOT NULL | - | Maximum payload weight in kg |
| max_volume_capacity | DECIMAL(10, 2) | NOT NULL | - | Maximum payload volume in m3 |
| max_package_capacity | INT | NOT NULL | - | Maximum number of packages |
| fuel_type | ENUM('gasoline','diesel','electric','hybrid','cng') | NOT NULL | 'diesel' | Fuel type |
| fuel_tank_capacity | DECIMAL(8, 2) | NULLABLE | NULL | Fuel tank capacity in liters |
| current_mileage | DECIMAL(12, 2) | NOT NULL | 0.00 | Current odometer reading in km |
| current_fuel_level | DECIMAL(5, 2) | NULLABLE | NULL | Current fuel level in liters |
| gps_device_id | VARCHAR(100) | NULLABLE | NULL | GPS tracking device identifier |
| insurance_expiry | DATE | NULLABLE | NULL | Insurance policy expiry date |
| registration_expiry | DATE | NULLABLE | NULL | Vehicle registration expiry date |
| inspection_expiry | DATE | NULLABLE | NULL | Last inspection expiry date |
| last_maintenance_date | DATE | NULLABLE | NULL | Date of last maintenance |
| next_maintenance_date | DATE | NULLABLE | NULL | Scheduled next maintenance date |
| next_maintenance_mileage | DECIMAL(12, 2) | NULLABLE | NULL | Mileage threshold for next maintenance |
| purchase_date | DATE | NULLABLE | NULL | Date vehicle was purchased |
| purchase_price | DECIMAL(12, 2) | NULLABLE | NULL | Purchase price |
| status | ENUM('available','in_use','maintenance','retired','breakdown') | NOT NULL | 'available' | Current vehicle status |
| photo | VARCHAR(500) | NULLABLE | NULL | Path to vehicle photo |
| notes | TEXT | NULLABLE | NULL | Internal notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (plate_number)
- INDEX (branch_id)
- INDEX (vehicle_type_id)
- INDEX (status)
- INDEX (next_maintenance_date)
- INDEX (deleted_at)

---

### Table: ehicle_types

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each vehicle type |
| name | VARCHAR(100) | UNIQUE, NOT NULL | - | Vehicle type name (e.g., 'Motorcycle', 'Truck') |
| code | VARCHAR(20) | UNIQUE, NOT NULL | - | Short code (e.g., 'MOTO', 'TRK') |
| description | TEXT | NULLABLE | NULL | Description of vehicle type |
| default_max_weight | DECIMAL(10, 2) | NULLABLE | NULL | Default weight capacity for this type |
| default_max_volume | DECIMAL(10, 2) | NULLABLE | NULL | Default volume capacity for this type |
| default_max_packages | INT | NULLABLE | NULL | Default package capacity |
| base_rate_per_km | DECIMAL(8, 2) | NULLABLE | NULL | Base rate per kilometer for this type |
| base_rate_per_kg | DECIMAL(8, 2) | NULLABLE | NULL | Base rate per kilogram for this type |
| icon | VARCHAR(100) | NULLABLE | NULL | Icon identifier for UI display |
| status | ENUM('active','inactive') | NOT NULL | 'active' | Vehicle type status |
| sort_order | INT | NOT NULL | 0 | Display sort order |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- UNIQUE (code)
- INDEX (status)

---

### Table: ehicle_maintenances

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each maintenance record |
| vehicle_id | BIGINT UNSIGNED | FK -> vehicles.id, NOT NULL | - | Vehicle being maintained |
| maintenance_type | ENUM('scheduled','unscheduled','repair','inspection','emergency') | NOT NULL | 'scheduled' | Type of maintenance |
| title | VARCHAR(255) | NOT NULL | - | Maintenance title/description |
| description | TEXT | NULLABLE | NULL | Detailed maintenance description |
| started_at | TIMESTAMP | NOT NULL | - | When maintenance started |
| completed_at | TIMESTAMP | NULLABLE | NULL | When maintenance was completed |
| estimated_completion | TIMESTAMP | NULLABLE | NULL | Estimated completion date/time |
| cost | DECIMAL(12, 2) | NOT NULL | 0.00 | Maintenance cost |
| supplier_id | BIGINT UNSIGNED | FK -> suppliers.id, NULLABLE | NULL | Supplier/garage performing maintenance |
| mileage_at_service | DECIMAL(12, 2) | NULLABLE | NULL | Vehicle mileage at time of service |
| parts_replaced | TEXT | NULLABLE | NULL | Description of parts replaced |
| mechanic_name | VARCHAR(255) | NULLABLE | NULL | Name of mechanic |
| mechanic_phone | VARCHAR(20) | NULLABLE | NULL | Mechanic contact number |
| invoice_number | VARCHAR(100) | NULLABLE | NULL | Maintenance invoice number |
| receipt_file | VARCHAR(500) | NULLABLE | NULL | Path to receipt/invoice file |
| status | ENUM('scheduled','in_progress','completed','cancelled') | NOT NULL | 'scheduled' | Maintenance status |
| next_maintenance_date | DATE | NULLABLE | NULL | Next scheduled maintenance |
| next_maintenance_mileage | DECIMAL(12, 2) | NULLABLE | NULL | Next mileage threshold |
| approved_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who approved maintenance |
| notes | TEXT | NULLABLE | NULL | Additional notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (vehicle_id)
- INDEX (maintenance_type)
- INDEX (status)
- INDEX (started_at)
- INDEX (supplier_id)
- INDEX (approved_by)

---

### Table: drivers

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for each driver |
| user_id | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | Associated user account (for app access) |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch assignment |
| employee_id | VARCHAR(50) | UNIQUE, NOT NULL | - | Employee ID number |
| first_name | VARCHAR(100) | NOT NULL | - | Driver first name |
| last_name | VARCHAR(100) | NOT NULL | - | Driver last name |
| phone | VARCHAR(20) | NOT NULL | - | Primary phone number |
| secondary_phone | VARCHAR(20) | NULLABLE | NULL | Secondary phone |
| email | VARCHAR(255) | NULLABLE | NULL | Email address |
| date_of_birth | DATE | NULLABLE | NULL | Date of birth |
| gender | ENUM('male','female','other') | NULLABLE | NULL | Gender |
| national_id | VARCHAR(50) | NULLABLE | NULL | National ID card number |
| address | TEXT | NULLABLE | NULL | Residential address |
| city | VARCHAR(100) | NULLABLE | NULL | City |
| province | VARCHAR(100) | NULLABLE | NULL | Province |
| emergency_contact_name | VARCHAR(255) | NULLABLE | NULL | Emergency contact name |
| emergency_contact_phone | VARCHAR(20) | NULLABLE | NULL | Emergency contact phone |
| license_number | VARCHAR(50) | NOT NULL | - | Driving license number |
| license_class | VARCHAR(20) | NOT NULL | - | License class (A, B, C, etc.) |
| license_expiry | DATE | NOT NULL | - | License expiry date |
| license_file | VARCHAR(500) | NULLABLE | NULL | Path to scanned license |
| medical_cert_expiry | DATE | NULLABLE | NULL | Medical certificate expiry |
| hiring_date | DATE | NOT NULL | - | Employment start date |
| hourly_rate | DECIMAL(8, 2) | NULLABLE | NULL | Hourly pay rate |
| delivery_bonus | DECIMAL(8, 2) | NOT NULL | 0.00 | Bonus per successful delivery |
| total_deliveries | INT | NOT NULL | 0 | Lifetime delivery count |
| successful_deliveries | INT | NOT NULL | 0 | Successfully completed deliveries |
| average_rating | DECIMAL(3, 2) | NULLABLE | NULL | Average customer rating (1-5) |
| current_status | ENUM('available','on_delivery','on_break','off_duty','on_leave') | NOT NULL | 'available' | Real-time status |
| is_active | BOOLEAN | NOT NULL | TRUE | Whether driver is currently active |
| photo | VARCHAR(500) | NULLABLE | NULL | Path to driver photo |
| notes | TEXT | NULLABLE | NULL | Internal notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (employee_id)
- INDEX (user_id)
- INDEX (branch_id)
- INDEX (phone)
- INDEX (current_status)
- INDEX (is_active)
- INDEX (license_expiry)
- INDEX (deleted_at)

---

### Table: driver_documents

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NOT NULL | - | Driver this document belongs to |
| document_type | ENUM('license','medical_cert','insurance','id_card','contract','training_cert','other') | NOT NULL | - | Type of document |
| document_name | VARCHAR(255) | NOT NULL | - | Document title |
| document_number | VARCHAR(100) | NULLABLE | NULL | Document reference number |
| issued_by | VARCHAR(255) | NULLABLE | NULL | Issuing authority |
| issued_date | DATE | NULLABLE | NULL | Date document was issued |
| expiry_date | DATE | NULLABLE | NULL | Date document expires |
| file_path | VARCHAR(500) | NOT NULL | - | Path to uploaded document file |
| file_size | INT | NULLABLE | NULL | File size in bytes |
| is_verified | BOOLEAN | NOT NULL | FALSE | Whether document has been verified |
| verified_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who verified the document |
| verified_at | TIMESTAMP | NULLABLE | NULL | Verification timestamp |
| status | ENUM('active','expired','pending','rejected') | NOT NULL | 'pending' | Document status |
| notes | TEXT | NULLABLE | NULL | Notes about the document |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (driver_id)
- INDEX (document_type)
- INDEX (expiry_date)
- INDEX (status)

---

### Table: driver_schedules

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NOT NULL | - | Driver this schedule belongs to |
| schedule_date | DATE | NOT NULL | - | Date of the schedule |
| shift_start | TIME | NOT NULL | - | Shift start time |
| shift_end | TIME | NOT NULL | - | Shift end time |
| actual_start | TIMESTAMP | NULLABLE | NULL | Actual shift start (clock-in) |
| actual_end | TIMESTAMP | NULLABLE | NULL | Actual shift end (clock-out) |
| break_start | TIME | NULLABLE | NULL | Scheduled break start |
| break_end | TIME | NULLABLE | NULL | Scheduled break end |
| break_duration_minutes | INT | NOT NULL | 60 | Break duration in minutes |
| route_id | BIGINT UNSIGNED | FK -> routes.id, NULLABLE | NULL | Assigned route for this shift |
| status | ENUM('scheduled','active','completed','cancelled','absent') | NOT NULL | 'scheduled' | Schedule status |
| notes | TEXT | NULLABLE | NULL | Schedule notes |
| approved_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | Manager who approved schedule |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (driver_id)
- INDEX (schedule_date)
- INDEX (status)
- INDEX (route_id)

---


## 12.5 Shipment & Package Tables

---

### Table: shipments

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier for shipment |
| tracking_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Unique tracking number (auto-generated) |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Origin branch |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer who owns the shipment |
| route_id | BIGINT UNSIGNED | FK -> routes.id, NULLABLE | NULL | Assigned delivery route |
| shipment_type | ENUM('domestic','international','intra_city','inter_city') | NOT NULL | 'domestic' | Shipment type |
| service_type | ENUM('standard','express','economy','freight','same_day') | NOT NULL | 'standard' | Service level |
| sender_name | VARCHAR(255) | NOT NULL | - | Sender full name |
| sender_phone | VARCHAR(20) | NOT NULL | - | Sender phone number |
| sender_address | TEXT | NOT NULL | - | Sender full address |
| sender_city | VARCHAR(100) | NOT NULL | - | Sender city |
| sender_province | VARCHAR(100) | NOT NULL | - | Sender province |
| sender_latitude | DECIMAL(10, 8) | NULLABLE | NULL | Sender GPS latitude |
| sender_longitude | DECIMAL(11, 8) | NULLABLE | NULL | Sender GPS longitude |
| recipient_name | VARCHAR(255) | NOT NULL | - | Recipient full name |
| recipient_phone | VARCHAR(20) | NOT NULL | - | Recipient phone number |
| recipient_address | TEXT | NOT NULL | - | Recipient full address |
| recipient_city | VARCHAR(100) | NOT NULL | - | Recipient city |
| recipient_province | VARCHAR(100) | NOT NULL | - | Recipient province |
| recipient_latitude | DECIMAL(10, 8) | NULLABLE | NULL | Recipient GPS latitude |
| recipient_longitude | DECIMAL(11, 8) | NULLABLE | NULL | Recipient GPS longitude |
| total_weight | DECIMAL(10, 3) | NOT NULL | - | Total weight in kg |
| total_volume | DECIMAL(10, 4) | NULLABLE | NULL | Total volume in m3 |
| total_packages | INT | NOT NULL | 1 | Number of packages |
| declared_value | DECIMAL(12, 2) | NULLABLE | NULL | Declared value of goods |
| shipping_cost | DECIMAL(12, 2) | NOT NULL | 0.00 | Calculated shipping cost |
| insurance_cost | DECIMAL(12, 2) | NOT NULL | 0.00 | Insurance cost |
| total_cost | DECIMAL(12, 2) | NOT NULL | 0.00 | Total shipment cost |
| cod_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Cash on Delivery amount |
| cod_collected | DECIMAL(12, 2) | NOT NULL | 0.00 | COD amount actually collected |
| discount_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Discount applied |
| tax_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Tax amount |
| currency | VARCHAR(3) | NOT NULL | 'USD' | Currency code (USD/KHR) |
| exchange_rate | DECIMAL(10, 4) | NOT NULL | 1.0000 | Exchange rate applied |
| estimated_delivery_date | DATE | NULLABLE | NULL | Estimated delivery date |
| actual_delivery_date | DATE | NULLABLE | NULL | Actual delivery date |
| delivery_time_slot | VARCHAR(50) | NULLABLE | NULL | Preferred delivery time window |
| special_instructions | TEXT | NULLABLE | NULL | Special delivery instructions |
| fragile | BOOLEAN | NOT NULL | FALSE | Whether shipment contains fragile items |
| hazardous | BOOLEAN | NOT NULL | FALSE | Whether shipment contains hazardous materials |
| temperature_sensitive | BOOLEAN | NOT NULL | FALSE | Whether temperature control is needed |
| status | ENUM('pending','confirmed','processing','warehouse_received','picking','packing','ready_for_dispatch','dispatched','in_transit','out_for_delivery','delivered','failed_delivery','exception','cancelled','returned') | NOT NULL | 'pending' | Current shipment status |
| delivery_status | ENUM('pending','attempted','successful','failed','partial') | NULLABLE | NULL | Last delivery attempt result |
| failure_reason | TEXT | NULLABLE | NULL | Reason for delivery failure |
| proof_of_delivery_type | ENUM('signature','photo','otp','gps','none') | NULLABLE | NULL | Type of proof of delivery |
| proof_of_delivery_file | VARCHAR(500) | NULLABLE | NULL | Path to POD file |
| signed_by | VARCHAR(255) | NULLABLE | NULL | Name of person who signed for delivery |
| created_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who created the shipment |
| assigned_driver_id | BIGINT UNSIGNED | FK -> drivers.id, NULLABLE | NULL | Currently assigned driver |
| invoice_id | BIGINT UNSIGNED | FK -> invoices.id, NULLABLE | NULL | Associated invoice |
| return_id | BIGINT UNSIGNED | FK -> returns.id, NULLABLE | NULL | Associated return (if returned) |
| is_return | BOOLEAN | NOT NULL | FALSE | Whether this is a return shipment |
| original_shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NULLABLE | NULL | Original shipment (for returns) |
| metadata | JSON | NULLABLE | NULL | Additional custom data |
| notes | TEXT | NULLABLE | NULL | Internal notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (tracking_number)
- INDEX (branch_id)
- INDEX (customer_id)
- INDEX (route_id)
- INDEX (status)
- INDEX (service_type)
- INDEX (assigned_driver_id)
- INDEX (created_at)
- INDEX (estimated_delivery_date)
- INDEX (cod_amount)
- INDEX (deleted_at)

---

### Table: shipment_items

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NOT NULL | - | Parent shipment |
| name | VARCHAR(255) | NOT NULL | - | Item name/description |
| description | TEXT | NULLABLE | NULL | Detailed item description |
| sku | VARCHAR(100) | NULLABLE | NULL | Stock Keeping Unit |
| quantity | INT | NOT NULL | 1 | Number of units |
| unit_weight | DECIMAL(10, 3) | NULLABLE | NULL | Weight per unit in kg |
| total_weight | DECIMAL(10, 3) | NULLABLE | NULL | Total weight (quantity * unit_weight) |
| length | DECIMAL(8, 2) | NULLABLE | NULL | Item length in cm |
| width | DECIMAL(8, 2) | NULLABLE | NULL | Item width in cm |
| height | DECIMAL(8, 2) | NULLABLE | NULL | Item height in cm |
| volume | DECIMAL(10, 4) | NULLABLE | NULL | Calculated volume in m3 |
| unit_value | DECIMAL(12, 2) | NULLABLE | NULL | Value per unit |
| total_value | DECIMAL(12, 2) | NULLABLE | NULL | Total value (quantity * unit_value) |
| category | VARCHAR(100) | NULLABLE | NULL | Item category |
| hs_code | VARCHAR(20) | NULLABLE | NULL | Harmonized System code (for customs) |
| country_of_origin | VARCHAR(50) | NULLABLE | NULL | Country of origin |
| barcode | VARCHAR(100) | NULLABLE | NULL | Item barcode |
| image | VARCHAR(500) | NULLABLE | NULL | Path to item image |
| hazardous | BOOLEAN | NOT NULL | FALSE | Whether item is hazardous |
| fragile | BOOLEAN | NOT NULL | FALSE | Whether item is fragile |
| status | ENUM('pending','verified','packed','dispatched','delivered','exception') | NOT NULL | 'pending' | Item status |
| notes | TEXT | NULLABLE | NULL | Item notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (shipment_id)
- INDEX (sku)
- INDEX (status)
- INDEX (barcode)

---

### Table: shipment_status_history

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NOT NULL | - | Shipment this history belongs to |
| status | VARCHAR(50) | NOT NULL | - | Status value |
| previous_status | VARCHAR(50) | NULLABLE | NULL | Previous status value |
| description | TEXT | NULLABLE | NULL | Description of the status change |
| location | VARCHAR(255) | NULLABLE | NULL | Location where status changed |
| latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude |
| longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude |
| changed_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who made the change |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NULLABLE | NULL | Driver associated with this change |
| automated | BOOLEAN | NOT NULL | FALSE | Whether change was automated |
| metadata | JSON | NULLABLE | NULL | Additional status data |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (shipment_id)
- INDEX (status)
- INDEX (created_at)
- INDEX (changed_by)

---

## 12.6 Package Tables

---

### Table: packages

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NOT NULL | - | Parent shipment |
| package_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Unique package number |
| barcode | VARCHAR(100) | NULLABLE | NULL | Package barcode |
| weight | DECIMAL(10, 3) | NOT NULL | - | Package weight in kg |
| length | DECIMAL(8, 2) | NOT NULL | - | Package length in cm |
| width | DECIMAL(8, 2) | NOT NULL | - | Package width in cm |
| height | DECIMAL(8, 2) | NOT NULL | - | Package height in cm |
| volumetric_weight | DECIMAL(10, 3) | NULLABLE | NULL | Calculated volumetric weight |
| chargeable_weight | DECIMAL(10, 3) | NULLABLE | NULL | Higher of actual or volumetric weight |
| description | VARCHAR(255) | NULLABLE | NULL | Package description |
| fragile | BOOLEAN | NOT NULL | FALSE | Whether package is fragile |
| waterproof | BOOLEAN | NOT NULL | FALSE | Whether package needs waterproofing |
| warehouse_zone_id | BIGINT UNSIGNED | FK -> warehouse_zones.id, NULLABLE | NULL | Current zone location |
| warehouse_shelf_id | BIGINT UNSIGNED | FK -> warehouse_shelves.id, NULLABLE | NULL | Current shelf location |
| label_printed | BOOLEAN | NOT NULL | FALSE | Whether package label has been printed |
| status | ENUM('pending','labelled','stored','picking','picked','packing','packed','loaded','in_transit','delivered','exception','returned') | NOT NULL | 'pending' | Package status |
| notes | TEXT | NULLABLE | NULL | Package notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (package_number)
- INDEX (shipment_id)
- INDEX (barcode)
- INDEX (status)
- INDEX (warehouse_zone_id)
- INDEX (warehouse_shelf_id)

---

### Table: package_items

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| package_id | BIGINT UNSIGNED | FK -> packages.id, NOT NULL | - | Parent package |
| shipment_item_id | BIGINT UNSIGNED | FK -> shipment_items.id, NULLABLE | NULL | Linked shipment item |
| name | VARCHAR(255) | NOT NULL | - | Item name |
| description | TEXT | NULLABLE | NULL | Item description |
| quantity | INT | NOT NULL | 1 | Quantity of this item in package |
| unit_weight | DECIMAL(10, 3) | NULLABLE | NULL | Weight per unit in kg |
| total_weight | DECIMAL(10, 3) | NULLABLE | NULL | Total weight |
| unit_value | DECIMAL(12, 2) | NULLABLE | NULL | Value per unit |
| category | VARCHAR(100) | NULLABLE | NULL | Item category |
| barcode | VARCHAR(100) | NULLABLE | NULL | Item barcode |
| status | ENUM('pending','verified','damaged','missing') | NOT NULL | 'pending' | Item verification status |
| notes | TEXT | NULLABLE | NULL | Item notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (package_id)
- INDEX (shipment_item_id)
- INDEX (status)
- INDEX (barcode)

---


## 12.7 Pickup Request Table

---

### Table: pickup_requests

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| request_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated request number |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer making the request |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch to handle the pickup |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NULLABLE | NULL | Assigned driver |
| pickup_name | VARCHAR(255) | NOT NULL | - | Contact name at pickup location |
| pickup_phone | VARCHAR(20) | NOT NULL | - | Contact phone at pickup location |
| pickup_address | TEXT | NOT NULL | - | Pickup address |
| pickup_city | VARCHAR(100) | NOT NULL | - | Pickup city |
| pickup_province | VARCHAR(100) | NOT NULL | - | Pickup province |
| pickup_latitude | DECIMAL(10, 8) | NULLABLE | NULL | GPS latitude |
| pickup_longitude | DECIMAL(11, 8) | NULLABLE | NULL | GPS longitude |
| scheduled_date | DATE | NOT NULL | - | Requested pickup date |
| scheduled_time_start | TIME | NOT NULL | - | Preferred pickup start time |
| scheduled_time_end | TIME | NOT NULL | - | Preferred pickup end time |
| actual_pickup_time | TIMESTAMP | NULLABLE | NULL | Actual pickup timestamp |
| estimated_packages | INT | NOT NULL | 1 | Estimated number of packages |
| estimated_weight | DECIMAL(10, 3) | NULLABLE | NULL | Estimated total weight in kg |
| item_description | TEXT | NULLABLE | NULL | Description of items for pickup |
| special_instructions | TEXT | NULLABLE | NULL | Special pickup instructions |
| pickup_fee | DECIMAL(12, 2) | NOT NULL | 0.00 | Pickup service fee |
| status | ENUM('pending','assigned','picked_up','cancelled','expired') | NOT NULL | 'pending' | Request status |
| rejection_reason | TEXT | NULLABLE | NULL | Reason for rejection |
| created_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who created the request (NULL if customer) |
| source | ENUM('web','phone','api','admin') | NOT NULL | 'web' | Source of the request |
| metadata | JSON | NULLABLE | NULL | Additional request data |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (request_number)
- INDEX (customer_id)
- INDEX (branch_id)
- INDEX (driver_id)
- INDEX (status)
- INDEX (scheduled_date)
- INDEX (pickup_province)

---

## 12.8 Route Tables

---

### Table: outes

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch this route belongs to |
| name | VARCHAR(255) | NOT NULL | - | Route name |
| code | VARCHAR(50) | UNIQUE, NOT NULL | - | Route code |
| description | TEXT | NULLABLE | NULL | Route description |
| origin | VARCHAR(255) | NOT NULL | - | Origin location name |
| origin_latitude | DECIMAL(10, 8) | NULLABLE | NULL | Origin GPS latitude |
| origin_longitude | DECIMAL(11, 8) | NULLABLE | NULL | Origin GPS longitude |
| destination | VARCHAR(255) | NOT NULL | - | Destination location name |
| destination_latitude | DECIMAL(10, 8) | NULLABLE | NULL | Destination GPS latitude |
| destination_longitude | DECIMAL(11, 8) | NULLABLE | NULL | Destination GPS longitude |
| distance_km | DECIMAL(10, 2) | NULLABLE | NULL | Total route distance in km |
| estimated_duration_minutes | INT | NULLABLE | NULL | Estimated travel time in minutes |
| estimated_fuel_cost | DECIMAL(10, 2) | NULLABLE | NULL | Estimated fuel cost |
| estimated_toll_cost | DECIMAL(10, 2) | NULLABLE | NULL | Estimated toll costs |
| route_type | ENUM('regular','express','return','special') | NOT NULL | 'regular' | Route type |
| priority | INT | NOT NULL | 0 | Route priority (higher = more important) |
| max_stops | INT | NOT NULL | 50 | Maximum stops on this route |
| is_template | BOOLEAN | NOT NULL | FALSE | Whether this is a reusable template |
| status | ENUM('active','inactive','archived') | NOT NULL | 'active' | Route status |
| created_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who created the route |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (code)
- INDEX (branch_id)
- INDEX (route_type)
- INDEX (status)
- INDEX (deleted_at)

---

### Table: oute_stops

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| route_id | BIGINT UNSIGNED | FK -> routes.id, NOT NULL | - | Parent route |
| stop_number | INT | NOT NULL | - | Sequential stop order |
| name | VARCHAR(255) | NOT NULL | - | Stop name/label |
| stop_type | ENUM('pickup','delivery','rest','fuel','checkpoint') | NOT NULL | 'delivery' | Type of stop |
| address | TEXT | NOT NULL | - | Stop address |
| city | VARCHAR(100) | NOT NULL | - | City |
| province | VARCHAR(100) | NOT NULL | - | Province |
| latitude | DECIMAL(10, 8) | NOT NULL | - | GPS latitude |
| longitude | DECIMAL(11, 8) | NOT NULL | - | GPS longitude |
| arrival_time_window_start | TIME | NULLABLE | NULL | Earliest arrival time |
| arrival_time_window_end | TIME | NULLABLE | NULL | Latest arrival time |
| estimated_stop_minutes | INT | NOT NULL | 15 | Estimated stop duration in minutes |
| distance_from_previous_km | DECIMAL(10, 2) | NULLABLE | NULL | Distance from previous stop in km |
| cumulative_distance_km | DECIMAL(10, 2) | NULLABLE | NULL | Total distance from origin |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NULLABLE | NULL | Customer at this stop (if delivery/pickup) |
| shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NULLABLE | NULL | Shipment at this stop |
| contact_name | VARCHAR(255) | NULLABLE | NULL | Contact person at stop |
| contact_phone | VARCHAR(20) | NULLABLE | NULL | Contact phone at stop |
| instructions | TEXT | NULLABLE | NULL | Special stop instructions |
| status | ENUM('pending','arrived','completed','skipped','failed') | NOT NULL | 'pending' | Stop status |
| arrival_time | TIMESTAMP | NULLABLE | NULL | Actual arrival time |
| departure_time | TIMESTAMP | NULLABLE | NULL | Actual departure time |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (route_id)
- INDEX (stop_number)
- INDEX (stop_type)
- INDEX (status)
- INDEX (customer_id)
- INDEX (shipment_id)

---

## 12.9 Dispatch Tables

---

### Table: dispatches

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| dispatch_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated dispatch number |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Origin branch |
| route_id | BIGINT UNSIGNED | FK -> routes.id, NULLABLE | NULL | Assigned route |
| warehouse_id | BIGINT UNSIGNED | FK -> warehouses.id, NULLABLE | NULL | Origin warehouse |
| dispatch_date | DATE | NOT NULL | - | Scheduled dispatch date |
| planned_departure_time | TIME | NULLABLE | NULL | Planned departure time |
| planned_arrival_time | TIME | NULLABLE | NULL | Planned arrival time |
| actual_departure_time | TIMESTAMP | NULLABLE | NULL | Actual departure time |
| actual_arrival_time | TIMESTAMP | NULLABLE | NULL | Actual arrival time |
| total_shipments | INT | NOT NULL | 0 | Total shipments in dispatch |
| total_packages | INT | NOT NULL | 0 | Total packages in dispatch |
| total_weight | DECIMAL(12, 3) | NOT NULL | 0.000 | Total weight in kg |
| total_volume | DECIMAL(12, 4) | NOT NULL | 0.0000 | Total volume in m3 |
| total_cod_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Total COD to collect |
| total_cod_collected | DECIMAL(12, 2) | NOT NULL | 0.00 | Total COD actually collected |
| fuel_cost | DECIMAL(10, 2) | NOT NULL | 0.00 | Fuel cost for dispatch |
| other_costs | DECIMAL(10, 2) | NOT NULL | 0.00 | Other associated costs |
| status | ENUM('pending','loading','loaded','departed','in_transit','arrived','delivering','completed','cancelled','exception') | NOT NULL | 'pending' | Dispatch status |
| notes | TEXT | NULLABLE | NULL | Dispatch notes |
| manifest_file | VARCHAR(500) | NULLABLE | NULL | Path to manifest document |
| created_by | BIGINT UNSIGNED | FK -> users.id, NOT NULL | - | User who created dispatch |
| completed_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who marked completed |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (dispatch_number)
- INDEX (branch_id)
- INDEX (route_id)
- INDEX (warehouse_id)
- INDEX (dispatch_date)
- INDEX (status)
- INDEX (created_at)

---

### Table: dispatch_drivers

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| dispatch_id | BIGINT UNSIGNED | FK -> dispatches.id, NOT NULL | - | Parent dispatch |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NOT NULL | - | Assigned driver |
| role | ENUM('primary','assistant','relief') | NOT NULL | 'primary' | Driver role in this dispatch |
| check_in_time | TIMESTAMP | NULLABLE | NULL | When driver checked in |
| check_out_time | TIMESTAMP | NULLABLE | NULL | When driver checked out |
| odometer_start | DECIMAL(12, 2) | NULLABLE | NULL | Odometer reading at start |
| odometer_end | DECIMAL(12, 2) | NULLABLE | NULL | Odometer reading at end |
| total_distance_km | DECIMAL(10, 2) | NULLABLE | NULL | Total distance traveled |
| status | ENUM('assigned','checked_in','departed','delivering','completed') | NOT NULL | 'assigned' | Driver dispatch status |
| notes | TEXT | NULLABLE | NULL | Notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (dispatch_id, driver_id)
- INDEX (dispatch_id)
- INDEX (driver_id)
- INDEX (status)

---

### Table: dispatch_vehicles

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| dispatch_id | BIGINT UNSIGNED | FK -> dispatches.id, NOT NULL | - | Parent dispatch |
| vehicle_id | BIGINT UNSIGNED | FK -> vehicles.id, NOT NULL | - | Assigned vehicle |
| role | ENUM('primary','secondary','backup') | NOT NULL | 'primary' | Vehicle role in this dispatch |
| fuel_level_start | DECIMAL(5, 2) | NULLABLE | NULL | Fuel level at dispatch start |
| fuel_level_end | DECIMAL(5, 2) | NULLABLE | NULL | Fuel level at dispatch end |
| fuel_consumed | DECIMAL(8, 2) | NULLABLE | NULL | Total fuel consumed in liters |
| fuel_cost | DECIMAL(10, 2) | NULLABLE | NULL | Fuel cost for this vehicle |
| odometer_start | DECIMAL(12, 2) | NULLABLE | NULL | Odometer at start |
| odometer_end | DECIMAL(12, 2) | NULLABLE | NULL | Odometer at end |
| total_distance_km | DECIMAL(10, 2) | NULLABLE | NULL | Total distance traveled |
| capacity_utilization | DECIMAL(5, 2) | NULLABLE | NULL | Percentage of capacity used |
| status | ENUM('assigned','loaded','departed','completed') | NOT NULL | 'assigned' | Vehicle dispatch status |
| notes | TEXT | NULLABLE | NULL | Notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (dispatch_id, vehicle_id)
- INDEX (dispatch_id)
- INDEX (vehicle_id)
- INDEX (status)

---


## 12.10 Tracking & GPS Table

---

### Table: 	racking_histories

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| trackable_type | VARCHAR(255) | NOT NULL | - | Polymorphic model type (e.g., 'App\\\Models\\\Shipment') |
| trackable_id | BIGINT UNSIGNED | NOT NULL | - | Polymorphic model identifier |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NULLABLE | NULL | Driver associated with this tracking event |
| vehicle_id | BIGINT UNSIGNED | FK -> vehicles.id, NULLABLE | NULL | Vehicle associated with this tracking event |
| dispatch_id | BIGINT UNSIGNED | FK -> dispatches.id, NULLABLE | NULL | Dispatch associated with this tracking event |
| latitude | DECIMAL(10, 8) | NOT NULL | - | GPS latitude coordinate |
| longitude | DECIMAL(11, 8) | NOT NULL | - | GPS longitude coordinate |
| altitude | DECIMAL(10, 2) | NULLABLE | NULL | Altitude in meters |
| speed | DECIMAL(6, 2) | NULLABLE | NULL | Speed in km/h |
| heading | DECIMAL(5, 2) | NULLABLE | NULL | Heading/direction in degrees (0-360) |
| accuracy | DECIMAL(6, 2) | NULLABLE | NULL | GPS accuracy in meters |
| event_type | ENUM('gps_update','geofence_enter','geofence_exit','speed_alert','idle','stop','departure','arrival','delivery','pickup') | NOT NULL | 'gps_update' | Type of tracking event |
| address | TEXT | NULLABLE | NULL | Reverse-geocoded address |
| location_name | VARCHAR(255) | NULLABLE | NULL | Named location (if applicable) |
| distance_from_previous | DECIMAL(10, 2) | NULLABLE | NULL | Distance from previous tracking point in km |
| battery_level | INT | NULLABLE | NULL | Device battery level percentage |
| signal_strength | VARCHAR(20) | NULLABLE | NULL | Cellular signal strength |
| metadata | JSON | NULLABLE | NULL | Additional tracking metadata |
| recorded_at | TIMESTAMP | NOT NULL | - | When the GPS reading was recorded |
| received_at | TIMESTAMP | NOT NULL | - | When the system received the data |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (trackable_type, trackable_id)
- INDEX (driver_id)
- INDEX (vehicle_id)
- INDEX (dispatch_id)
- INDEX (event_type)
- INDEX (recorded_at)
- INDEX (received_at)

---

## 12.11 Inventory Tables

---

### Table: inventory

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| warehouse_id | BIGINT UNSIGNED | FK -> warehouses.id, NOT NULL | - | Warehouse where inventory is stored |
| warehouse_zone_id | BIGINT UNSIGNED | FK -> warehouse_zones.id, NULLABLE | NULL | Current zone location |
| warehouse_shelf_id | BIGINT UNSIGNED | FK -> warehouse_shelves.id, NULLABLE | NULL | Current shelf location |
| item_name | VARCHAR(255) | NOT NULL | - | Name of the inventory item |
| sku | VARCHAR(100) | UNIQUE, NOT NULL | - | Stock Keeping Unit identifier |
| barcode | VARCHAR(100) | NULLABLE | NULL | Item barcode |
| description | TEXT | NULLABLE | NULL | Item description |
| category | VARCHAR(100) | NULLABLE | NULL | Item category |
| quantity | INT | NOT NULL | 0 | Current quantity on hand |
| unit_of_measure | VARCHAR(20) | NOT NULL | 'pcs' | Unit of measure (pcs, kg, m3, box, pallet) |
| unit_weight | DECIMAL(10, 3) | NULLABLE | NULL | Weight per unit in kg |
| unit_value | DECIMAL(12, 2) | NULLABLE | NULL | Value per unit |
| total_value | DECIMAL(12, 2) | NULLABLE | NULL | Total value (quantity * unit_value) |
| minimum_stock | INT | NOT NULL | 0 | Minimum stock threshold for alerts |
| maximum_stock | INT | NOT NULL | 0 | Maximum stock level |
| reorder_point | INT | NOT NULL | 0 | Reorder trigger point |
| reorder_quantity | INT | NOT NULL | 0 | Standard reorder quantity |
| lot_number | VARCHAR(50) | NULLABLE | NULL | Lot/batch number |
| serial_number | VARCHAR(100) | NULLABLE | NULL | Serial number |
| expiry_date | DATE | NULLABLE | NULL | Item expiry date |
| manufacturing_date | DATE | NULLABLE | NULL | Manufacturing date |
| supplier_id | BIGINT UNSIGNED | FK -> suppliers.id, NULLABLE | NULL | Supplier of this item |
| cost_price | DECIMAL(12, 2) | NULLABLE | NULL | Cost price per unit |
| status | ENUM('available','reserved','damaged','expired','quarantine','in_transit') | NOT NULL | 'available' | Inventory status |
| last_received_at | TIMESTAMP | NULLABLE | NULL | Last date item was received |
| last_issued_at | TIMESTAMP | NULLABLE | NULL | Last date item was issued |
| last_counted_at | TIMESTAMP | NULLABLE | NULL | Last physical count date |
| notes | TEXT | NULLABLE | NULL | Notes about this inventory item |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (sku)
- INDEX (warehouse_id)
- INDEX (warehouse_zone_id)
- INDEX (warehouse_shelf_id)
- INDEX (barcode)
- INDEX (category)
- INDEX (status)
- INDEX (lot_number)
- INDEX (expiry_date)
- INDEX (supplier_id)

---

### Table: stock_movements

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| inventory_id | BIGINT UNSIGNED | FK -> inventory.id, NOT NULL | - | Inventory item being moved |
| warehouse_id | BIGINT UNSIGNED | FK -> warehouses.id, NOT NULL | - | Warehouse where movement occurred |
| movement_type | ENUM('inbound','outbound','transfer','adjustment','return','damaged','expired') | NOT NULL | - | Type of stock movement |
| quantity | INT | NOT NULL | - | Quantity moved (positive for in, negative for out) |
| quantity_before | INT | NOT NULL | - | Stock quantity before movement |
| quantity_after | INT | NOT NULL | - | Stock quantity after movement |
| reference_type | VARCHAR(255) | NULLABLE | NULL | Related entity type (e.g., 'App\\\Models\\\Shipment') |
| reference_id | BIGINT UNSIGNED | NULLABLE | NULL | Related entity ID |
| reference_number | VARCHAR(100) | NULLABLE | NULL | Reference document number |
| from_zone_id | BIGINT UNSIGNED | FK -> warehouse_zones.id, NULLABLE | NULL | Source zone (for transfers) |
| from_shelf_id | BIGINT UNSIGNED | FK -> warehouse_shelves.id, NULLABLE | NULL | Source shelf (for transfers) |
| to_zone_id | BIGINT UNSIGNED | FK -> warehouse_zones.id, NULLABLE | NULL | Destination zone (for transfers) |
| to_shelf_id | BIGINT UNSIGNED | FK -> warehouse_shelves.id, NULLABLE | NULL | Destination shelf (for transfers) |
| unit_cost | DECIMAL(12, 2) | NULLABLE | NULL | Unit cost at time of movement |
| total_cost | DECIMAL(12, 2) | NULLABLE | NULL | Total cost of movement |
| reason | VARCHAR(255) | NULLABLE | NULL | Reason for movement |
| notes | TEXT | NULLABLE | NULL | Additional notes |
| performed_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who performed the movement |
| performed_at | TIMESTAMP | NOT NULL | - | When the movement was performed |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (inventory_id)
- INDEX (warehouse_id)
- INDEX (movement_type)
- INDEX (reference_type, reference_id)
- INDEX (performed_at)
- INDEX (performed_by)

---

### Table: stock_adjustments

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| adjustment_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated adjustment number |
| warehouse_id | BIGINT UNSIGNED | FK -> warehouses.id, NOT NULL | - | Warehouse being adjusted |
| inventory_id | BIGINT UNSIGNED | FK -> inventory.id, NOT NULL | - | Inventory item being adjusted |
| adjustment_type | ENUM('addition','subtraction','correction','damage','expiry','theft','found','recount') | NOT NULL | - | Type of adjustment |
| quantity_before | INT | NOT NULL | - | Quantity before adjustment |
| quantity_adjusted | INT | NOT NULL | - | Quantity adjusted (positive or negative) |
| quantity_after | INT | NOT NULL | - | Quantity after adjustment |
| reason | TEXT | NOT NULL | - | Detailed reason for adjustment |
| supporting_document | VARCHAR(500) | NULLABLE | NULL | Path to supporting document |
| unit_cost | DECIMAL(12, 2) | NULLABLE | NULL | Unit cost of adjusted items |
| total_impact | DECIMAL(12, 2) | NULLABLE | NULL | Total financial impact |
| status | ENUM('pending','approved','rejected','cancelled') | NOT NULL | 'pending' | Approval status |
| requested_by | BIGINT UNSIGNED | FK -> users.id, NOT NULL | - | User who requested adjustment |
| approved_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who approved adjustment |
| approved_at | TIMESTAMP | NULLABLE | NULL | When adjustment was approved |
| rejection_reason | TEXT | NULLABLE | NULL | Reason for rejection |
| notes | TEXT | NULLABLE | NULL | Additional notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (adjustment_number)
- INDEX (warehouse_id)
- INDEX (inventory_id)
- INDEX (adjustment_type)
- INDEX (status)
- INDEX (requested_by)
- INDEX (approved_by)
- INDEX (created_at)

---

## 12.12 Financial Tables

---

### Table: invoices

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| invoice_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated invoice number |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer being invoiced |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Issuing branch |
| invoice_type | ENUM('shipping','warehousing','service','credit_note','debit_note') | NOT NULL | 'shipping' | Type of invoice |
| reference_type | VARCHAR(255) | NULLABLE | NULL | Related entity type (e.g., 'App\\\Models\\\Shipment') |
| reference_id | BIGINT UNSIGNED | NULLABLE | NULL | Related entity ID |
| reference_number | VARCHAR(100) | NULLABLE | NULL | Reference number (e.g., tracking number) |
| subtotal | DECIMAL(12, 2) | NOT NULL | 0.00 | Subtotal before tax |
| discount_percentage | DECIMAL(5, 2) | NOT NULL | 0.00 | Discount percentage |
| discount_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Discount amount |
| tax_percentage | DECIMAL(5, 2) | NOT NULL | 0.00 | Tax percentage (e.g., 10% VAT) |
| tax_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Tax amount |
| total_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Total amount due |
| amount_paid | DECIMAL(12, 2) | NOT NULL | 0.00 | Amount already paid |
| balance_due | DECIMAL(12, 2) | NOT NULL | 0.00 | Remaining balance |
| currency | VARCHAR(3) | NOT NULL | 'USD' | Currency code (USD/KHR) |
| exchange_rate | DECIMAL(10, 4) | NOT NULL | 1.0000 | Exchange rate at time of invoicing |
| issue_date | DATE | NOT NULL | - | Date invoice was issued |
| due_date | DATE | NOT NULL | - | Payment due date |
| paid_date | DATE | NULLABLE | NULL | Date fully paid |
| payment_terms | INT | NOT NULL | 0 | Payment terms in days |
| status | ENUM('draft','sent','viewed','partial','paid','overdue','cancelled','refunded') | NOT NULL | 'draft' | Invoice status |
| notes | TEXT | NULLABLE | NULL | Invoice notes |
| terms_and_conditions | TEXT | NULLABLE | NULL | Terms and conditions |
| pdf_file | VARCHAR(500) | NULLABLE | NULL | Path to generated PDF |
| created_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who created invoice |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (invoice_number)
- INDEX (customer_id)
- INDEX (branch_id)
- INDEX (invoice_type)
- INDEX (status)
- INDEX (issue_date)
- INDEX (due_date)
- INDEX (deleted_at)

---

### Table: invoice_items

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| invoice_id | BIGINT UNSIGNED | FK -> invoices.id, NOT NULL | - | Parent invoice |
| description | VARCHAR(255) | NOT NULL | - | Item description |
| quantity | DECIMAL(10, 3) | NOT NULL | 1.000 | Quantity |
| unit | VARCHAR(20) | NOT NULL | 'pcs' | Unit of measure (pcs, kg, km, hr, etc.) |
| unit_price | DECIMAL(12, 2) | NOT NULL | 0.00 | Price per unit |
| discount_percentage | DECIMAL(5, 2) | NOT NULL | 0.00 | Line item discount percentage |
| discount_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Line item discount amount |
| tax_percentage | DECIMAL(5, 2) | NOT NULL | 0.00 | Line item tax percentage |
| tax_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Line item tax amount |
| total_price | DECIMAL(12, 2) | NOT NULL | 0.00 | Total line item price |
| sort_order | INT | NOT NULL | 0 | Display sort order |
| metadata | JSON | NULLABLE | NULL | Additional item data |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (invoice_id)
- INDEX (sort_order)

---

### Table: payments

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| payment_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated payment number |
| invoice_id | BIGINT UNSIGNED | FK -> invoices.id, NOT NULL | - | Related invoice |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer making payment |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch receiving payment |
| payment_method_id | BIGINT UNSIGNED | FK -> payment_methods.id, NOT NULL | - | Payment method used |
| amount | DECIMAL(12, 2) | NOT NULL | - | Payment amount |
| currency | VARCHAR(3) | NOT NULL | 'USD' | Currency code |
| exchange_rate | DECIMAL(10, 4) | NOT NULL | 1.0000 | Exchange rate applied |
| amount_in_base_currency | DECIMAL(12, 2) | NOT NULL | 0.00 | Amount converted to base currency |
| payment_date | DATE | NOT NULL | - | Date payment was made |
| payment_time | TIME | NULLABLE | NULL | Time payment was made |
| reference_number | VARCHAR(100) | NULLABLE | NULL | External reference (e.g., bank transfer ref) |
| transaction_id | VARCHAR(100) | NULLABLE | NULL | Payment gateway transaction ID |
| cheque_number | VARCHAR(50) | NULLABLE | NULL | Cheque number (if applicable) |
| bank_name | VARCHAR(255) | NULLABLE | NULL | Bank name (for transfer) |
| received_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who recorded the payment |
| is_refund | BOOLEAN | NOT NULL | FALSE | Whether this is a refund payment |
| refund_reason | TEXT | NULLABLE | NULL | Reason for refund |
| status | ENUM('pending','completed','failed','refunded','cancelled') | NOT NULL | 'pending' | Payment status |
| receipt_file | VARCHAR(500) | NULLABLE | NULL | Path to receipt image/document |
| notes | TEXT | NULLABLE | NULL | Payment notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (payment_number)
- INDEX (invoice_id)
- INDEX (customer_id)
- INDEX (branch_id)
- INDEX (payment_method_id)
- INDEX (payment_date)
- INDEX (status)
- INDEX (is_refund)
- INDEX (received_by)

---

### Table: payment_methods

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| name | VARCHAR(100) | UNIQUE, NOT NULL | - | Payment method name |
| code | VARCHAR(20) | UNIQUE, NOT NULL | - | Short code (e.g., 'CASH', 'BT', 'ABA') |
| description | TEXT | NULLABLE | NULL | Description |
| payment_type | ENUM('cash','bank_transfer','credit_card','debit_card','mobile_payment','cheque','cod','other') | NOT NULL | - | Category of payment method |
| icon | VARCHAR(100) | NULLABLE | NULL | Icon identifier for UI |
| account_name | VARCHAR(255) | NULLABLE | NULL | Account holder name |
| account_number | VARCHAR(50) | NULLABLE | NULL | Account number |
| bank_name | VARCHAR(255) | NULLABLE | NULL | Bank name |
| qr_code | VARCHAR(500) | NULLABLE | NULL | Path to QR code image |
| is_active | BOOLEAN | NOT NULL | TRUE | Whether method is active |
| sort_order | INT | NOT NULL | 0 | Display sort order |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- UNIQUE (code)
- INDEX (payment_type)
- INDEX (is_active)

---

### Table: expenses

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| expense_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated expense number |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch for this expense |
| expense_category_id | BIGINT UNSIGNED | FK -> expense_categories.id, NOT NULL | - | Expense category |
| reference_type | VARCHAR(255) | NULLABLE | NULL | Related entity type |
| reference_id | BIGINT UNSIGNED | NULLABLE | NULL | Related entity ID |
| reference_number | VARCHAR(100) | NULLABLE | NULL | Reference number (e.g., dispatch number) |
| title | VARCHAR(255) | NOT NULL | - | Expense title |
| description | TEXT | NULLABLE | NULL | Detailed description |
| amount | DECIMAL(12, 2) | NOT NULL | - | Expense amount |
| currency | VARCHAR(3) | NOT NULL | 'USD' | Currency code |
| expense_date | DATE | NOT NULL | - | Date of expense |
| payment_method | VARCHAR(50) | NULLABLE | NULL | How expense was paid |
| receipt_file | VARCHAR(500) | NULLABLE | NULL | Path to receipt image/document |
| receipt_number | VARCHAR(100) | NULLABLE | NULL | Receipt number |
| vendor | VARCHAR(255) | NULLABLE | NULL | Vendor/payee name |
| driver_id | BIGINT UNSIGNED | FK -> drivers.id, NULLABLE | NULL | Driver (for driver expenses) |
| vehicle_id | BIGINT UNSIGNED | FK -> vehicles.id, NULLABLE | NULL | Vehicle (for vehicle expenses) |
| dispatch_id | BIGINT UNSIGNED | FK -> dispatches.id, NULLABLE | NULL | Dispatch (for dispatch expenses) |
| is_recurring | BOOLEAN | NOT NULL | FALSE | Whether this is a recurring expense |
| recurring_frequency | ENUM('daily','weekly','monthly','quarterly','yearly') | NULLABLE | NULL | Recurrence frequency |
| status | ENUM('pending','approved','rejected','paid','cancelled') | NOT NULL | 'pending' | Expense status |
| submitted_by | BIGINT UNSIGNED | FK -> users.id, NOT NULL | - | User who submitted expense |
| approved_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who approved expense |
| approved_at | TIMESTAMP | NULLABLE | NULL | Approval timestamp |
| rejection_reason | TEXT | NULLABLE | NULL | Reason for rejection |
| notes | TEXT | NULLABLE | NULL | Expense notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (expense_number)
- INDEX (branch_id)
- INDEX (expense_category_id)
- INDEX (reference_type, reference_id)
- INDEX (expense_date)
- INDEX (driver_id)
- INDEX (vehicle_id)
- INDEX (dispatch_id)
- INDEX (status)
- INDEX (submitted_by)
- INDEX (approved_by)

---

### Table: expense_categories

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| name | VARCHAR(100) | UNIQUE, NOT NULL | - | Category name |
| code | VARCHAR(20) | UNIQUE, NOT NULL | - | Short code |
| description | TEXT | NULLABLE | NULL | Category description |
| parent_id | BIGINT UNSIGNED | FK -> expense_categories.id, NULLABLE | NULL | Parent category (for sub-categories) |
| icon | VARCHAR(100) | NULLABLE | NULL | Icon identifier for UI |
| budget_amount | DECIMAL(12, 2) | NULLABLE | NULL | Monthly budget for this category |
| requires_receipt | BOOLEAN | NOT NULL | FALSE | Whether receipts are mandatory |
| requires_approval | BOOLEAN | NOT NULL | FALSE | Whether approval is required |
| approval_threshold | DECIMAL(12, 2) | NULLABLE | NULL | Amount threshold for approval |
| is_active | BOOLEAN | NOT NULL | TRUE | Whether category is active |
| sort_order | INT | NOT NULL | 0 | Display sort order |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- UNIQUE (code)
- INDEX (parent_id)
- INDEX (is_active)

---

### Table: income

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| income_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated income number |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch for this income |
| income_category_id | BIGINT UNSIGNED | FK -> income_categories.id, NOT NULL | - | Income category |
| reference_type | VARCHAR(255) | NULLABLE | NULL | Related entity type |
| reference_id | BIGINT UNSIGNED | NULLABLE | NULL | Related entity ID |
| reference_number | VARCHAR(100) | NULLABLE | NULL | Reference number |
| title | VARCHAR(255) | NOT NULL | - | Income title |
| description | TEXT | NULLABLE | NULL | Detailed description |
| amount | DECIMAL(12, 2) | NOT NULL | - | Income amount |
| currency | VARCHAR(3) | NOT NULL | 'USD' | Currency code |
| income_date | DATE | NOT NULL | - | Date of income |
| payment_method | VARCHAR(50) | NULLABLE | NULL | How payment was received |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NULLABLE | NULL | Related customer |
| received_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who recorded income |
| is_recurring | BOOLEAN | NOT NULL | FALSE | Whether this is recurring income |
| recurring_frequency | ENUM('daily','weekly','monthly','quarterly','yearly') | NULLABLE | NULL | Recurrence frequency |
| status | ENUM('pending','confirmed','cancelled') | NOT NULL | 'confirmed' | Income status |
| notes | TEXT | NULLABLE | NULL | Income notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (income_number)
- INDEX (branch_id)
- INDEX (income_category_id)
- INDEX (reference_type, reference_id)
- INDEX (income_date)
- INDEX (customer_id)
- INDEX (status)
- INDEX (received_by)

---

### Table: income_categories

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| name | VARCHAR(100) | UNIQUE, NOT NULL | - | Category name |
| code | VARCHAR(20) | UNIQUE, NOT NULL | - | Short code |
| description | TEXT | NULLABLE | NULL | Category description |
| parent_id | BIGINT UNSIGNED | FK -> income_categories.id, NULLABLE | NULL | Parent category |
| icon | VARCHAR(100) | NULLABLE | NULL | Icon identifier for UI |
| target_amount | DECIMAL(12, 2) | NULLABLE | NULL | Monthly revenue target |
| is_active | BOOLEAN | NOT NULL | TRUE | Whether category is active |
| sort_order | INT | NOT NULL | 0 | Display sort order |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (name)
- UNIQUE (code)
- INDEX (parent_id)
- INDEX (is_active)

---


## 12.13 Returns Tables

---

### Table: eturns

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| return_number | VARCHAR(50) | UNIQUE, NOT NULL | - | Auto-generated return number |
| customer_id | BIGINT UNSIGNED | FK -> customers.id, NOT NULL | - | Customer requesting return |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NOT NULL | - | Branch handling the return |
| shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NOT NULL | - | Original shipment being returned |
| return_reason | ENUM('damaged','wrong_item','not_as_described','quality_issue','changed_mind','duplicate_order','late_delivery','other') | NOT NULL | - | Reason for return |
| reason_description | TEXT | NULLABLE | NULL | Detailed return reason |
| original_invoice_id | BIGINT UNSIGNED | FK -> invoices.id, NULLABLE | NULL | Original invoice |
| return_shipment_id | BIGINT UNSIGNED | FK -> shipments.id, NULLABLE | NULL | Return shipment record |
| refund_amount | DECIMAL(12, 2) | NOT NULL | 0.00 | Amount to be refunded |
| refund_method | VARCHAR(50) | NULLABLE | NULL | Method of refund |
| return_shipping_paid_by | ENUM('customer','company') | NOT NULL | 'customer' | Who pays return shipping |
| return_shipping_cost | DECIMAL(12, 2) | NOT NULL | 0.00 | Cost of return shipping |
| received_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who received return |
| received_at | TIMESTAMP | NULLABLE | NULL | When return was received |
| inspection_notes | TEXT | NULLABLE | NULL | Notes from inspection |
| inspection_result | ENUM('saleable','damaged','defective','incomplete','wrong_item') | NULLABLE | NULL | Inspection result |
| photos | JSON | NULLABLE | NULL | Array of photo paths for evidence |
| status | ENUM('requested','approved','in_transit','received','inspecting','processed','completed','rejected','cancelled') | NOT NULL | 'requested' | Return status |
| approved_by | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who approved return |
| approved_at | TIMESTAMP | NULLABLE | NULL | Approval timestamp |
| rejection_reason | TEXT | NULLABLE | NULL | Reason for rejection |
| refund_processed | BOOLEAN | NOT NULL | FALSE | Whether refund has been processed |
| refund_processed_at | TIMESTAMP | NULLABLE | NULL | When refund was processed |
| notes | TEXT | NULLABLE | NULL | Return notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (return_number)
- INDEX (customer_id)
- INDEX (branch_id)
- INDEX (shipment_id)
- INDEX (return_reason)
- INDEX (status)
- INDEX (approved_by)
- INDEX (received_by)
- INDEX (created_at)

---

### Table: eturn_items

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| return_id | BIGINT UNSIGNED | FK -> returns.id, NOT NULL | - | Parent return |
| shipment_item_id | BIGINT UNSIGNED | FK -> shipment_items.id, NULLABLE | NULL | Original shipment item |
| name | VARCHAR(255) | NOT NULL | - | Item name |
| description | TEXT | NULLABLE | NULL | Item description |
| quantity | INT | NOT NULL | 1 | Quantity being returned |
| unit_value | DECIMAL(12, 2) | NULLABLE | NULL | Value per unit |
| total_value | DECIMAL(12, 2) | NULLABLE | NULL | Total value of returned items |
| condition | ENUM('new','good','fair','poor','damaged','destroyed') | NOT NULL | 'good' | Condition of returned item |
| condition_notes | TEXT | NULLABLE | NULL | Detailed condition description |
| photos | JSON | NULLABLE | NULL | Array of photo paths |
| is_saleable | BOOLEAN | NULLABLE | NULL | Whether item can be resold |
| restocked | BOOLEAN | NOT NULL | FALSE | Whether item has been restocked |
| restocked_at | TIMESTAMP | NULLABLE | NULL | When item was restocked |
| notes | TEXT | NULLABLE | NULL | Item notes |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (return_id)
- INDEX (shipment_item_id)
- INDEX (condition)
- INDEX (restocked)

---

## 12.14 Notification Table

---

### Table: 
otifications

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| user_id | BIGINT UNSIGNED | FK -> users.id, NOT NULL | - | User receiving notification |
| type | VARCHAR(100) | NOT NULL | - | Notification type class |
| title | VARCHAR(255) | NOT NULL | - | Notification title |
| message | TEXT | NOT NULL | - | Notification message body |
| channel | ENUM('in_app','email','sms','push') | NOT NULL | 'in_app' | Delivery channel |
| category | VARCHAR(50) | NULLABLE | NULL | Notification category (shipment, payment, system, etc.) |
| reference_type | VARCHAR(255) | NULLABLE | NULL | Related entity type |
| reference_id | BIGINT UNSIGNED | NULLABLE | NULL | Related entity ID |
| action_url | VARCHAR(500) | NULLABLE | NULL | URL to navigate when notification is clicked |
| is_read | BOOLEAN | NOT NULL | FALSE | Whether notification has been read |
| read_at | TIMESTAMP | NULLABLE | NULL | When notification was read |
| is_emailed | BOOLEAN | NOT NULL | FALSE | Whether notification was sent via email |
| emailed_at | TIMESTAMP | NULLABLE | NULL | When email was sent |
| is_sms_sent | BOOLEAN | NOT NULL | FALSE | Whether notification was sent via SMS |
| sms_sent_at | TIMESTAMP | NULLABLE | NULL | When SMS was sent |
| priority | ENUM('low','normal','high','urgent') | NOT NULL | 'normal' | Notification priority |
| metadata | JSON | NULLABLE | NULL | Additional notification data |
| expires_at | TIMESTAMP | NULLABLE | NULL | When notification expires |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (type)
- INDEX (channel)
- INDEX (category)
- INDEX (is_read)
- INDEX (reference_type, reference_id)
- INDEX (priority)
- INDEX (created_at)
- INDEX (expires_at)

---

## 12.15 System Tables

---

### Table: ctivity_logs

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| user_id | BIGINT UNSIGNED | FK -> users.id, NULLABLE | NULL | User who performed the action |
| branch_id | BIGINT UNSIGNED | FK -> branches.id, NULLABLE | NULL | Branch context |
| log_name | VARCHAR(100) | NULLABLE | NULL | Log channel name |
| description | TEXT | NOT NULL | - | Human-readable description of the activity |
| subject_type | VARCHAR(255) | NULLABLE | NULL | Eloquent model type of the subject |
| subject_id | BIGINT UNSIGNED | NULLABLE | NULL | Eloquent model ID of the subject |
| event | VARCHAR(50) | NULLABLE | NULL | Event type (created, updated, deleted, etc.) |
| old_values | JSON | NULLABLE | NULL | Previous attribute values |
| new_values | JSON | NULLABLE | NULL | New attribute values |
| ip_address | VARCHAR(45) | NULLABLE | NULL | IP address of the user |
| user_agent | VARCHAR(500) | NULLABLE | NULL | Browser/device user agent |
| method | VARCHAR(10) | NULLABLE | NULL | HTTP method (GET, POST, PUT, DELETE) |
| url | VARCHAR(500) | NULLABLE | NULL | Request URL |
| route_name | VARCHAR(255) | NULLABLE | NULL | Named route |
| module | VARCHAR(100) | NULLABLE | NULL | System module affected |
| action | VARCHAR(50) | NULLABLE | NULL | Specific action performed |
| session_id | VARCHAR(100) | NULLABLE | NULL | User session identifier |
| status | ENUM('success','failed','warning') | NOT NULL | 'success' | Activity status |
| metadata | JSON | NULLABLE | NULL | Additional activity data |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (branch_id)
- INDEX (subject_type, subject_id)
- INDEX (event)
- INDEX (log_name)
- INDEX (module)
- INDEX (action)
- INDEX (created_at)
- INDEX (status)

---

### Table: system_settings

| Column | Type | Constraints | Default | Description |
|--------|------|-------------|---------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | - | Unique identifier |
| group | VARCHAR(100) | NOT NULL | - | Settings group (general, shipping, notification, currency, tax, etc.) |
| key | VARCHAR(255) | UNIQUE, NOT NULL | - | Unique setting key |
| value | TEXT | NULLABLE | NULL | Setting value |
| type | ENUM('string','integer','float','boolean','json','text','file','color','date','time') | NOT NULL | 'string' | Data type of the value |
| label | VARCHAR(255) | NULLABLE | NULL | Human-readable label |
| description | TEXT | NULLABLE | NULL | Description of the setting |
| options | JSON | NULLABLE | NULL | Available options (for select/dropdown) |
| validation_rules | VARCHAR(255) | NULLABLE | NULL | Laravel validation rules |
| is_public | BOOLEAN | NOT NULL | FALSE | Whether setting is visible to non-admins |
| is_required | BOOLEAN | NOT NULL | FALSE | Whether setting is required |
| default_value | TEXT | NULLABLE | NULL | Default value |
| placeholder | VARCHAR(255) | NULLABLE | NULL | Placeholder text for form |
| sort_order | INT | NOT NULL | 0 | Display sort order within group |
| created_at | TIMESTAMP | NULLABLE | NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NULLABLE | NULL | Record last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (key)
- INDEX (group)
- INDEX (type)
- INDEX (is_public)
- INDEX (sort_order)

---

# 13. DATABASE RELATIONSHIP EXPLANATION

## 13.1 Authentication Relationships

The authentication system follows the **Spatie Laravel Permission** package pattern. The users table serves as the central user entity. Roles and permissions are managed through many-to-many relationships via three pivot tables: model_has_roles (assigns roles to users), model_has_permissions (assigns direct permissions to users), and ole_has_permissions (assigns permissions to roles). This polymorphic relationship structure allows the permission system to be extended to other model types beyond users in the future if needed.

The users table has a many-to-one relationship with ranches through the ranch_id foreign key, ensuring every user operates within a branch context.

## 13.2 Branch and Warehouse Relationships

Each ranch serves as an organizational unit and has a one-to-many relationship with users, warehouses, ehicles, drivers, and customers. This establishes a clear organizational hierarchy where all operational entities are scoped to a branch.

Warehouses belong to a ranch and contain warehouse_zones, which in turn contain warehouse_shelves. This creates a hierarchical location structure: Branch > Warehouse > Zone > Shelf. Each level tracks its own capacity and utilization. The warehouse also references a users record as its manager.

## 13.3 Customer and Shipment Relationships

Customers have a one-to-many relationship with shipments, invoices, pickup_requests, and eturns. Each customer can have multiple customer_addresses for delivery and pickup purposes.

Shipments are the central operational entity. A shipment belongs to a customer and a ranch, and optionally references a oute. Each shipment has multiple shipment_items describing the contents, and multiple shipment_status_history records providing an audit trail of the shipment lifecycle.

Shipments are broken into one or more packages for physical handling. Each package contains package_items that map back to shipment_items. Packages reference their physical location in the warehouse through warehouse_zone_id and warehouse_shelf_id.

## 13.4 Dispatch Relationships

Dispatches represent the physical movement of goods. A dispatch belongs to a ranch and optionally references a oute and warehouse. Through two pivot tables -- dispatch_drivers and dispatch_vehicles -- a dispatch can assign multiple drivers and vehicles. This many-to-many relationship through dispatch_drivers and dispatch_vehicles allows complex dispatch scenarios including convoy operations, relay driving, and backup vehicle assignments.

Shipments are allocated to dispatches through a relationship tracked via the dispatch_id field or a join table, enabling a dispatch to carry multiple shipments along a route.

## 13.5 Route Relationships

Routes belong to a ranch and contain multiple oute_stops in sequential order. Each route stop can reference a customer and a shipment for delivery/pickup operations. Routes are referenced by shipments, dispatches, and driver_schedules.

## 13.6 Financial Relationships

Invoices belong to a customer and a ranch, containing multiple invoice_items. Each invoice can receive multiple payments through the payments table. Payments reference payment_methods for the transaction type.

Expenses and income records belong to a ranch and reference their respective category tables (expense_categories and income_categories). Expenses can optionally reference drivers, ehicles, or dispatches for operational cost tracking.

## 13.7 Inventory Relationships

Inventory records belong to a warehouse and optionally reference a warehouse_zone and warehouse_shelf for physical location tracking. Each inventory record has multiple stock_movements recording every inbound, outbound, transfer, or adjustment event. Stock_adjustments provide an approval workflow for manual inventory corrections.

## 13.8 Fleet Relationships

Vehicles belong to a ranch and reference a ehicle_type. Each vehicle has multiple ehicle_maintenances records, which can reference a supplier for external service providers.

Drivers belong to a ranch and can optionally reference a users account for system access. Drivers have multiple driver_documents for compliance tracking and driver_schedules for shift management.

## 13.9 Tracking Relationships

The 	racking_histories table uses polymorphic relationships (	rackable_type and 	rackable_id) to record GPS and event data for any trackable entity -- primarily shipments but potentially also packages or custom entities. Tracking records also reference drivers, ehicles, and dispatches for fleet tracking purposes.

## 13.10 Cross-Cutting Relationships

The 
otifications table references users for delivery and optionally references any entity through eference_type and eference_id polymorphic fields. Similarly, ctivity_logs provides system-wide auditing with polymorphic subject tracking and user context.

The system_settings table serves as a global configuration store with grouped key-value pairs, independent of other entities.

---

# 14. ACTIVITY DIAGRAM

`
+============================================================================================+
|                        CLMS SHIPMENT LIFECYCLE ACTIVITY DIAGRAM                             |
+============================================================================================+

                           [START]
                              |
                              v
                    +-------------------+
                    | Customer Submits  |
                    | Shipment Request  |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | System Validates  |
                    | Shipment Details  |
                    +-------------------+
                              |
                     +--------+--------+
                     |                 |
              [Valid]              [Invalid]
                     |                 |
                     v                 v
          +------------------+  +------------------+
          | Calculate Cost & |  | Return Error to  |
          | Generate Tracking|  | Customer with    |
          | Number           |  | Validation Details|
          +------------------+  +------------------+
                     |                 |
                     v                 v
          +------------------+        [END]
          | Create Invoice   |
          +------------------+
                     |
                     v
          +------------------+
          | Customer Payment |
          +------------------+
                     |
            +--------+--------+
            |                 |
     [Paid]            [COD Setup]
            |                 |
            +--------+--------+
                     |
                     v
          +-------------------+
          | Warehouse Receives|
          | Picking Instruction|
          +-------------------+
                     |
                     v
          +-------------------+
          | Staff Picks Items |
          | from Designated   |
          | Zone/Shelf        |
          +-------------------+
                     |
                     v
          +-------------------+
          | Staff Packs Items |
          | & Creates Package |
          +-------------------+
                     |
                     v
          +-------------------+
          | Generate Package  |
          | Labels & Barcode  |
          +-------------------+
                     |
                     v
          +-------------------+
          | Dispatcher Creates|
          | Dispatch & Assigns|
          | Driver + Vehicle  |
          +-------------------+
                     |
                     v
          +-------------------+
          | Packages Loaded   |
          | onto Vehicle      |
          +-------------------+
                     |
                     v
          +-------------------+
          | Driver Departs    |
          | GPS Tracking Starts|
          +-------------------+
                     |
                     v
          +-------------------+
          | Driver Follows    |
          | Route & Makes     |
          | Deliveries        |
          +-------------------+
                     |
            +--------+--------+
            |                 |
     [Success]          [Failed]
            |                 |
            v                 v
  +------------------+  +-------------------+
  | Capture Proof of |  | Record Exception  |
  | Delivery         |  | Schedule Re-delivery|
  | (Signature/Photo)|  +-------------------+
  +------------------+
            |
     +------+------+                    +-------------------+
     |             |                    | Return to         |
  [COD]       [Non-COD]                | Warehouse         |
     |             |                    +-------------------+
     v             v                           |
  +--------+  +-----------+                    |
  | Collect|  | Confirm   |                    |
  | Payment|  | Delivery  |                    |
  +--------+  +-----------+                    |
     |             |                           |
     +------+------+                           |
            |                                  |
            v                                  |
  +-------------------+                        |
  | Send Notification |<-----------------------+
  | to Customer       |
  +-------------------+
            |
            v
  +-------------------+
  | Invoice Reconciled|
  | Status Updated    |
  +-------------------+
            |
            v
       [END]

+============================================================================================+
|                     DISPATCH OPERATIONS ACTIVITY DIAGRAM                                    |
+============================================================================================+

                         [Dispatcher Starts]
                              |
                              v
                    +-------------------+
                    | Review Pending    |
                    | Shipments         |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Group Shipments   |
                    | by Route/Dest.    |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Identify Available|
                    | Drivers & Vehicles|
                    +-------------------+
                              |
                  +-----------+-----------+
                  |           |           |
            [Drivers]  [Vehicles]  [Routes]
            [Available] [Available] [Available]
                  |           |           |
                  +-----------+-----------+
                              |
                              v
                    +-------------------+
                    | Create Dispatch   |
                    | Assign Resources  |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Generate Manifest |
                    & Notify Driver    |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Driver Check-In  |
                    | & Load Packages   |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Verify Load       |
                    | Match Manifest    |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Driver Departure  |
                    | Odometer Recorded |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Monitor Real-Time |
                    | GPS & Progress    |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Driver Returns   |
                    | Deliveries Done   |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Reconcile Dispatch|
                    | Document Results  |
                    +-------------------+
                              |
                              v
                    +-------------------+
                    | Complete Dispatch |
                    | Update Status     |
                    +-------------------+
                              |
                              v
                         [END]

---

# 15. SEQUENCE DIAGRAM

## 15.1 Shipment Creation Sequence
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
| Customer |    | Web App |    | Shipment |    | Invoice |    | Warehouse|    | Database |
|          |    |         |    | Service  |    | Service |    | Service  |    |          |
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
    |               |               |               |               |               |
    |--Create------>|               |               |               |               |
    |  Shipment     |               |               |               |               |
    |               |--Validate---->|               |               |               |
    |               |  & Create     |               |               |               |
    |               |               |--Validate---->|               |               |
    |               |               |  Data         |               |               |
    |               |               |               |               |               |
    |               |               |<--Insert------|--------------->|               |
    |               |               |  Shipment     |               |               |
    |               |               |               |               |               |
    |               |               |--Generate---->|               |               |
    |               |               |  Tracking No. |               |               |
    |               |               |<--Return------|               |               |
    |               |               |  Tracking No. |               |               |
    |               |               |               |               |               |
    |               |               |--Create------>|               |               |
    |               |               |  Invoice      |               |               |
    |               |               |               |               |               |
    |               |<--Return------|---------------|               |               |
    |               |  Tracking No. |               |               |               |
    |               |  & Invoice    |               |               |               |
    |               |               |               |               |               |
    |<--Display-----|               |               |               |               |
    |  Confirmation |               |               |               |               |
    |               |               |               |               |               |
    |               |               |--Notify------>|               |               |
    |               |               |  Warehouse    |               |               |
    |               |               |               |               |               |

## 15.2 Delivery Confirmation Sequence
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
| Driver   |    | Mobile  |    | Tracking |    | Shipment|    |Notificat.|    | Database |
|          |    | App     |    | Service  |    | Service |    | Service  |    |          |
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
    |               |               |               |               |               |
    |--Arrive at--->|               |               |               |               |
    |  Destination  |               |               |               |               |
    |--Update Status|               |               |               |               |
    |  "Delivered"  |               |               |               |               |
    |               |--Record GPS--->               |               |               |
    |               |  Location     |               |               |               |
    |               |               |--Save-------->|--------------->|               |
    |               |               |  Tracking     |               |               |
    |               |               |               |               |               |
    |--Capture POD->|               |               |               |               |
    |  (Signature)  |               |               |               |               |
    |               |--Upload POD-->|               |               |               |
    |               |               |--Save POD---->|--------------->|               |
    |               |               |               |               |               |
    |               |--Update Status|               |               |               |
    |               |  "Delivered"  |               |               |               |
    |               |               |--Update------>|               |               |
    |               |               |  Status       |               |               |
    |               |               |               |--Mark Delivered|               |
    |               |               |               |               |               |
    |               |               |               |--Trigger------>|               |
    |               |               |               |  Notification  |               |
    |               |               |               |               |--Send SMS/Email|
    |               |               |               |               |  to Customer  |
    |<--Confirm----|               |               |               |               |
    |  Delivery     |               |               |               |               |

## 15.3 Dispatch Workflow Sequence
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
|Dispatch  |    | Web App |    | Dispatch |    | Driver  |    | Vehicle  |    | Database |
| Manager  |    |         |    | Service  |    | Service |    | Service  |    |          |
+----------+    +---------+    +----------+    +---------+    +----------+    +---------+
    |               |               |               |               |               |
    |--Create------>|               |               |               |               |
    |  Dispatch     |               |               |               |               |
    |               |--Validate---->|               |               |               |
    |               |               |--Check-------->|               |               |
    |               |               |  Availability  |               |               |
    |               |               |<--Return-------|               |               |
    |               |               |               |               |               |
    |               |               |--Assign------->|               |               |
    |               |               |  Driver        |               |               |
    |               |               |--Assign------->|               |               |
    |               |               |  Vehicle       |               |               |
    |               |               |               |               |               |
    |               |               |--Save--------->|--------------->|               |
    |               |               |  Dispatch      |               |               |
    |               |               |               |               |               |
    |               |               |--Notify------->|               |               |
    |<--Return------|               |               |               |               |
    |  Confirmation |               |               |               |               |

## 15.4 Payment Processing Sequence
+----------+    +---------+    +----------+    +---------+    +----------+
| Customer |    | Web App |    | Payment  |    | Invoice |    | Database |
|          |    |         |    | Service  |    | Service |    |          |
+----------+    +---------+    +----------+    +---------+    +----------+
    |               |               |               |               |
    |--View-------->|               |               |               |
    |  Invoice      |               |               |               |
    |<--Display-----|               |               |               |
    |  Invoice      |               |               |               |
    |               |               |               |               |
    |--Make-------->|               |               |               |
    |  Payment      |               |               |               |
    |               |--Process----->|               |               |
    |               |               |--Validate---->|               |
    |               |               |  Amount       |               |
    |               |               |<--Confirm-----|               |
    |               |               |               |               |
    |               |               |--Save Payment->               |
    |               |               |--Update------>|               |
    |               |               |  Invoice      |               |
    |               |               |  Balance      |               |
    |               |<--Return------|               |               |
    |               |  Receipt      |               |               |
    |<--Display-----|               |               |               |
    |  Receipt      |               |               |               |


# 16. SYSTEM ARCHITECTURE

## 16.1 High-Level Architecture

`
+============================================================================+
|                        SYSTEM ARCHITECTURE                                 |
+============================================================================+

+--------------------------------------------------------------------------+
|                        CLIENT LAYER                                       |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  Web Application  |  |  Mobile Web App  |  |  Customer Portal     |    |
|  |  (Admin Panel)    |  |  (Driver App)    |  |  (Public Facing)     |    |
|  |  Blade/Tailwind   |  |  PWA/Responsive  |  |  Blade/Tailwind      |    |
|  +------------------+  +------------------+  +----------------------+    |
+--------------------------------------------------------------------------+
                              |
                              v
+--------------------------------------------------------------------------+
|                        API GATEWAY LAYER                                  |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  Laravel Router   |  |  Rate Limiter    |  |  Authentication      |    |
|  |  (Web Routes)     |  |  (Throttle)      |  |  (Sanctum/Breeze)    |    |
|  +------------------+  +------------------+  +----------------------+    |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  API Routes       |  |  CORS Handler    |  |  CSRF Protection     |    |
|  |  (RESTful JSON)   |  |                  |  |                      |    |
|  +------------------+  +------------------+  +----------------------+    |
+--------------------------------------------------------------------------+
                              |
                              v
+--------------------------------------------------------------------------+
|                     APPLICATION LAYER                                     |
|                                                                          |
|  +--------------------------------------------------------------------+  |
|  |                    CONTROLLERS                                       |  |
|  |  UserController, ShipmentController, DispatchController, etc.       |  |
|  +--------------------------------------------------------------------+  |
|                              |                                             |
|  +--------------------------------------------------------------------+  |
|  |                    SERVICES                                          |  |
|  |  ShipmentService, DispatchService, InvoiceService, etc.            |  |
|  +--------------------------------------------------------------------+  |
|                              |                                             |
|  +--------------------------------------------------------------------+  |
|  |                    MODELS (Eloquent ORM)                             |  |
|  |  User, Shipment, Dispatch, Invoice, etc.                            |  |
|  +--------------------------------------------------------------------+  |
|                              |                                             |
|  +--------------------------------------------------------------------+  |
|  |                    MIDDLEWARE                                         |  |
|  |  Auth, RBAC, BranchScope, ActivityLog, AuditTrail                   |  |
|  +--------------------------------------------------------------------+  |
+--------------------------------------------------------------------------+
                              |
                              v
+--------------------------------------------------------------------------+
|                      DATA LAYER                                           |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  MySQL/MariaDB   |  |  Redis Cache     |  |  File Storage        |    |
|  |  (Primary DB)    |  |  (Session/Cache/ |  |  (Local/S3/MinIO)   |    |
|  |                  |  |   Queue/Realtime) |  |  (Documents/Photos)  |    |
|  +------------------+  +------------------+  +----------------------+    |
+--------------------------------------------------------------------------+
                              |
                              v
+--------------------------------------------------------------------------+
|                   EXTERNAL SERVICES                                       |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  SMS Gateway     |  |  Email Service   |  |  Payment Gateway     |    |
|  |  (Smart/Cellcard)|  |  (Mailgun/SES)   |  |  (ABA/Wing/ACLEDA)   |    |
|  +------------------+  +------------------+  +----------------------+    |
|                                                                          |
|  +------------------+  +------------------+  +----------------------+    |
|  |  Google Maps API |  |  Currency API    |  |  Backup Service      |    |
|  |  (Geocoding/     |  |  (Exchange Rates)|  |  (Automated Cron)    |    |
|  |   Directions)    |  |                  |  |                      |    |
|  +------------------+  +------------------+  +----------------------+    |
+--------------------------------------------------------------------------+
`

## 16.2 Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Frontend Framework | Laravel Blade + Tailwind CSS + Alpine.js | Admin panel, responsive UI |
| JavaScript Framework | Alpine.js / Livewire | Reactive UI components |
| CSS Framework | Tailwind CSS | Utility-first CSS styling |
| Backend Framework | Laravel 11.x (PHP 8.2+) | Application framework |
| Authentication | Laravel Breeze + Sanctum | Web and API authentication |
| Authorization | Spatie Laravel Permission | Role and permission management |
| Database | MySQL 8.0 / MariaDB 10.6+ | Primary data store |
| Cache | Redis 7.x | Caching, sessions, queues |
| Queue | Redis / Database | Background job processing |
| File Storage | Local / S3-compatible (MinIO) | Document and image storage |
| Search | Laravel Scout + Meilisearch | Full-text search |
| Maps | Google Maps JavaScript API | GPS tracking, geocoding |
| Charts | Chart.js / ApexCharts | Dashboard visualizations |
| PDF Generation | dompdf / wkhtmltopdf | Invoice and report generation |
| Testing | PHPUnit + Laravel Dusk | Unit and browser testing |
| Build Tools | Vite | Asset compilation |


# 17. FOLDER STRUCTURE

```
combodia-logistics/
+-- app/
|   +-- Console/Commands/ (5 command files)
|   +-- Http/Controllers/ (20+ controllers across 12 modules)
|   +-- Http/Middleware/ (4 middleware files)
|   +-- Http/Requests/ (validation request classes)
|   +-- Models/ (40+ Eloquent models)
|   +-- Services/ (15 service classes)
|   +-- Notifications/ (8 notification classes)
|   +-- Jobs/ (8 queued job classes)
|   +-- Observers/ (6 model observer classes)
|   +-- Policies/ (7 authorization policy classes)
+-- config/ (8 configuration files)
+-- database/
|   +-- migrations/ (40+ migration files for all tables)
|   +-- seeders/ (10 seeder files)
|   +-- factories/ (6 factory files)
+-- resources/views/ (organized by module with layouts and components)
+-- routes/ (web.php, api.php, console.php)
+-- tests/ (Unit and Feature test suites)
+-- public/ (css/, js/, images/, uploads/)
+-- DATABASE_DOCUMENTATION.md
```


# 18. API STRUCTURE

## 18.1 API Endpoint Overview

All API endpoints are prefixed with /api/v1/ and require authentication via Bearer token (Sanctum).

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| POST | /api/v1/auth/login | User login | No |
| POST | /api/v1/auth/logout | User logout | Yes |
| POST | /api/v1/auth/register | User registration | No |
| POST | /api/v1/auth/forgot-password | Request password reset | No |
| POST | /api/v1/auth/reset-password | Reset password | No |
| GET | /api/v1/auth/me | Get current user profile | Yes |
| PUT | /api/v1/auth/profile | Update profile | Yes |
| POST | /api/v1/auth/change-password | Change password | Yes |


### Shipments

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/shipments | List shipments (paginated, filterable) | Yes |
| POST | /api/v1/shipments | Create new shipment | Yes |
| GET | /api/v1/shipments/{id} | Get shipment details | Yes |
| PUT | /api/v1/shipments/{id} | Update shipment | Yes |
| DELETE | /api/v1/shipments/{id} | Cancel/delete shipment | Yes |
| GET | /api/v1/shipments/{id}/items | Get shipment items | Yes |
| POST | /api/v1/shipments/{id}/items | Add shipment item | Yes |
| GET | /api/v1/shipments/{id}/status-history | Get status history | Yes |
| PUT | /api/v1/shipments/{id}/status | Update shipment status | Yes |
| POST | /api/v1/shipments/bulk | Bulk create shipments | Yes |

### Tracking

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/tracking/{trackingNumber} | Track shipment by number (public) | No |
| GET | /api/v1/tracking/{id}/history | Get tracking history | Yes |
| POST | /api/v1/tracking/gps-update | Submit GPS location (driver) | Yes |
| GET | /api/v1/tracking/vehicle/{vehicleId}/location | Get vehicle current location | Yes |
| GET | /api/v1/tracking/vehicle/{vehicleId}/history | Get vehicle location history | Yes |

### Dispatches

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/dispatches | List dispatches | Yes |
| POST | /api/v1/dispatches | Create dispatch | Yes |
| GET | /api/v1/dispatches/{id} | Get dispatch details | Yes |
| PUT | /api/v1/dispatches/{id} | Update dispatch | Yes |
| POST | /api/v1/dispatches/{id}/assign-driver | Assign driver | Yes |
| POST | /api/v1/dispatches/{id}/assign-vehicle | Assign vehicle | Yes |
| POST | /api/v1/dispatches/{id}/start | Start dispatch (departure) | Yes |
| POST | /api/v1/dispatches/{id}/complete | Complete dispatch | Yes |
| GET | /api/v1/dispatches/{id}/manifest | Get dispatch manifest | Yes |
| GET | /api/v1/dispatches/{id}/reconciliation | Get reconciliation data | Yes |


### Drivers

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/drivers | List drivers | Yes |
| POST | /api/v1/drivers | Create driver | Yes |
| GET | /api/v1/drivers/{id} | Get driver details | Yes |
| PUT | /api/v1/drivers/{id} | Update driver | Yes |
| GET | /api/v1/drivers/{id}/documents | Get driver documents | Yes |
| POST | /api/v1/drivers/{id}/documents | Upload driver document | Yes |
| GET | /api/v1/drivers/{id}/schedule | Get driver schedule | Yes |
| POST | /api/v1/drivers/{id}/schedule | Create schedule entry | Yes |
| GET | /api/v1/drivers/{id}/performance | Get performance metrics | Yes |

### Vehicles

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/vehicles | List vehicles | Yes |
| POST | /api/v1/vehicles | Create vehicle | Yes |
| GET | /api/v1/vehicles/{id} | Get vehicle details | Yes |
| PUT | /api/v1/vehicles/{id} | Update vehicle | Yes |
| GET | /api/v1/vehicles/{id}/maintenance | Get maintenance history | Yes |
| POST | /api/v1/vehicles/{id}/maintenance | Schedule maintenance | Yes |
| PUT | /api/v1/vehicles/{id}/status | Update vehicle status | Yes |
| GET | /api/v1/vehicle-types | List vehicle types | Yes |
| POST | /api/v1/vehicle-types | Create vehicle type | Yes |

### Customers

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/customers | List customers | Yes |
| POST | /api/v1/customers | Create customer | Yes |
| GET | /api/v1/customers/{id} | Get customer details | Yes |
| PUT | /api/v1/customers/{id} | Update customer | Yes |
| GET | /api/v1/customers/{id}/addresses | Get customer addresses | Yes |
| POST | /api/v1/customers/{id}/addresses | Add customer address | Yes |
| GET | /api/v1/customers/{id}/shipments | Get customer shipments | Yes |
| GET | /api/v1/customers/{id}/invoices | Get customer invoices | Yes |
| GET | /api/v1/customers/{id}/balance | Get customer balance | Yes |


### Invoices and Payments

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/invoices | List invoices | Yes |
| POST | /api/v1/invoices | Create invoice | Yes |
| GET | /api/v1/invoices/{id} | Get invoice details | Yes |
| PUT | /api/v1/invoices/{id} | Update invoice | Yes |
| GET | /api/v1/invoices/{id}/items | Get invoice items | Yes |
| POST | /api/v1/invoices/{id}/items | Add invoice item | Yes |
| GET | /api/v1/invoices/{id}/payments | Get invoice payments | Yes |
| POST | /api/v1/invoices/{id}/payments | Record payment | Yes |
| GET | /api/v1/invoices/{id}/pdf | Download invoice PDF | Yes |
| GET | /api/v1/payments | List all payments | Yes |
| GET | /api/v1/payments/{id} | Get payment details | Yes |
| GET | /api/v1/payment-methods | List payment methods | Yes |
| POST | /api/v1/payment-methods | Create payment method | Yes |

### Expenses and Income

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/expenses | List expenses | Yes |
| POST | /api/v1/expenses | Create expense | Yes |
| PUT | /api/v1/expenses/{id}/approve | Approve expense | Yes |
| GET | /api/v1/expense-categories | List categories | Yes |
| POST | /api/v1/expense-categories | Create category | Yes |
| GET | /api/v1/income | List income records | Yes |
| POST | /api/v1/income | Record income | Yes |
| GET | /api/v1/income-categories | List categories | Yes |
| POST | /api/v1/income-categories | Create category | Yes |

### Warehouse and Inventory

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/warehouses | List warehouses | Yes |
| POST | /api/v1/warehouses | Create warehouse | Yes |
| GET | /api/v1/warehouses/{id}/zones | Get warehouse zones | Yes |
| POST | /api/v1/warehouses/{id}/zones | Create zone | Yes |
| GET | /api/v1/warehouses/{id}/inventory | Get warehouse inventory | Yes |
| POST | /api/v1/inventory | Add inventory item | Yes |
| PUT | /api/v1/inventory/{id} | Update inventory | Yes |
| POST | /api/v1/inventory/adjust | Adjust stock levels | Yes |
| GET | /api/v1/stock-movements | List stock movements | Yes |
| POST | /api/v1/stock-adjustments | Create adjustment request | Yes |
| PUT | /api/v1/stock-adjustments/{id}/approve | Approve adjustment | Yes |

### Returns

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/returns | List returns | Yes |
| POST | /api/v1/returns | Create return request | Yes |
| GET | /api/v1/returns/{id} | Get return details | Yes |
| PUT | /api/v1/returns/{id}/approve | Approve return | Yes |
| PUT | /api/v1/returns/{id}/process | Process return | Yes |
| GET | /api/v1/returns/{id}/items | Get return items | Yes |
| POST | /api/v1/returns/{id}/items | Add return item | Yes |


### Reports

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/reports/dashboard | Get dashboard data | Yes |
| GET | /api/v1/reports/shipments | Shipment reports | Yes |
| GET | /api/v1/reports/financial | Financial reports | Yes |
| GET | /api/v1/reports/fleet | Fleet reports | Yes |
| GET | /api/v1/reports/warehouse | Warehouse reports | Yes |
| GET | /api/v1/reports/customers | Customer reports | Yes |
| GET | /api/v1/reports/export/{type} | Export report (pdf/excel/csv) | Yes |

### System Administration

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|:-------------:|
| GET | /api/v1/branches | List branches | Yes |
| POST | /api/v1/branches | Create branch | Yes |
| GET | /api/v1/roles | List roles | Yes |
| GET | /api/v1/notifications | List notifications | Yes |
| PUT | '/api/v1/notifications/{id}/read' | Mark notification as read | Yes |
| GET | /api/v1/activity-logs | List activity logs | Yes |
| GET | /api/v1/system-settings | Get system settings | Yes |
| PUT | /api/v1/system-settings | Update settings | Yes |



## 18.2 API Response Format

All API responses follow a consistent JSON structure:

`json
{
  "success": true,
  "message": "Success message or error description",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
`"
"


| HTTP Status | Error Code | Description |
|-------------|------------|-------------|
| 400 | BAD_REQUEST | Invalid request parameters |
| 401 | UNAUTHENTICATED | Authentication required |
| 403 | FORBIDDEN | Insufficient permissions |
| 404 | NOT_FOUND | Resource not found |
| 422 | VALIDATION_ERROR | Request validation failed |
| 429 | RATE_LIMITED | Too many requests |
| 500 | SERVER_ERROR | Internal server error |

# 19. NAVIGATION FLOW

## 19.1 Main Navigation Structure

The system uses a role-based navigation structure. Upon login, users are redirected to their role-specific dashboard. The main navigation is displayed as a collapsible sidebar with module grouping.

LOGIN PAGE
    |
    +---> Authentication Check
              |
              +---> Role-Based Redirect
                      |
                      +--- Super Admin ---> Admin Dashboard
                      |       |
                      |       +-- Dashboard (KPIs, metrics, alerts)
                      |       +-- User Management (Users List, Create User, Edit User)
                      |       +-- Role Management (Roles List, Permissions Matrix)
                      |       +-- Branch Management (Branches List, Create Branch)
                      |       +-- System Settings (General, Shipping, Tax, Currency, Notifications, Backup)
                      |       +-- Activity Logs
                      |       +-- Notifications Center
                      |
                      +--- Warehouse Manager ---> Warehouse Dashboard
                      |       |
                      |       +-- Dashboard (Capacity, Stock levels, Receiving queue)
                      |       +-- Warehouses (Warehouse List, Zones, Shelves)
                      |       +-- Inventory (Inventory List, Stock Movements, Stock Adjustments)
                      |       +-- Shipments (Receive, Pack, Label)
                      |       +-- Reports (Warehouse utilization, Stock aging)
                      |
                      +--- Warehouse Staff ---> Warehouse Dashboard (Limited)
                      |       |
                      |       +-- Dashboard (Today's tasks)
                      |       +-- Inventory (View, Barcode Scanner)
                      |       +-- Shipments (Receive, Pack)
                      |       +-- Stock Movements (Create records)
                      |
                      +--- Logistics Manager ---> Logistics Dashboard
                      |       |
                      |       +-- Dashboard (Shipment volume, Delivery rates, Fleet status)
                      |       +-- Shipments (Shipment List, Create, Detail, Tracking)
                      |       +-- Dispatches (Dispatch List, Create Dispatch)
                      |       +-- Routes (Route List, Create Route, Optimize)
                      |       +-- Fleet (Vehicles, Drivers, Maintenance)
                      |       +-- Tracking (Real-time map, GPS history)
                      |       +-- Reports (Operational, Performance KPIs)
                      |
                      +--- Dispatcher ---> Dispatch Dashboard
                      |       |
                      |       +-- Dashboard (Today dispatches, Pending shipments)
                      |       +-- Dispatches (Active, Create, Assign Resources)
                      |       +-- Fleet Status (Available Vehicles, Available Drivers)
                      |       +-- Pending Shipments (queue for dispatch)
                      |       +-- Pickup Requests (Assign to drivers)
                      |       +-- Real-Time Tracking Map
                      |
                      +--- Driver ---> Driver Mobile Dashboard
                      |       |
                      |       +-- Dashboard (Today deliveries, earnings)
                      |       +-- My Dispatches (Active, Delivery List, Update Status, Capture POD)
                      |       +-- My Schedule (Weekly view)
                      |       +-- My Expenses (Submit, History)
                      |       +-- My Documents (Upload, Expiry alerts)
                      |       +-- Profile
                      |
                      +--- Customer ---> Customer Portal
                      |       |
                      |       +-- Dashboard (Active shipments, Pending invoices)
                      |       +-- Shipments (Create, My Shipments, Track)
                      |       +-- Pickup Requests
                      |       +-- Invoices (Invoice List, Make Payment)
                      |       +-- Returns (Request, Track returns)
                      |       +-- My Addresses (Manage delivery addresses)
                      |       +-- Profile
                      |
                      +--- Finance ---> Finance Dashboard
                      |       |
                      |       +-- Dashboard (Revenue, Outstanding, Cash flow)
                      |       +-- Invoices (List, Create, Detail, PDF)
                      |       +-- Payments (List, Record, Reconcile)
                      |       +-- Expenses (List, Create, Approve)
                      |       +-- Income (List, Record)
                      |       +-- Financial Reports (P&L, Balance sheet)
                      |       +-- Customer Balances
                      |
                      +--- Accountant ---> Finance Dashboard (Limited)
                      |       |
                      |       +-- Dashboard (Daily transactions)
                      |       +-- Invoices (View, Record payments)
                      |       +-- Payments (View, Record)
                      |       +-- Expenses (View, Record)
                      |       +-- Income (View, Record)
                      |       +-- Bank Reconciliation
                      |
                      +--- Sales ---> Sales Dashboard
                      |       |
                      |       +-- Dashboard (Pipeline, Revenue targets)
                      |       +-- Customers (List, Create, Detail, History)
                      |       +-- Leads Pipeline (Track opportunities)
                      |       +-- Quotations (Create, Track)
                      |       +-- Sales Reports (Performance, Forecasts)
                      |
                      +--- Customer Support ---> Support Dashboard
                      |       |
                      |       +-- Dashboard (Open issues, Pending returns)
                      |       +-- Active Issues (Track, Resolve)
                      |       +-- Shipment Tracking (Look up any shipment)
                      |       +-- Returns Management (Process returns)
                      |       +-- Customer Communications (Log interactions)
                      |       +-- Knowledge Base (FAQs, guides)
                      |
                      +--- Guest ---> Public Pages
                              |
                              +-- Track Shipment (by tracking number)
                              +-- Company Information
                              +-- Contact Form
                              +-- Login Page
`

## 19.2 Cross-Module Navigation

Certain actions trigger navigation across modules:

- **Shipment Creation** (Customer/Sales) triggers Invoice Generation (Finance) and Warehouse Notification (Warehouse)
- **Payment Recording** (Finance) updates Invoice Status and Customer Balance
- **Dispatch Creation** (Dispatcher) assigns Drivers and Vehicles (Fleet) and links Shipments (Shipment)
- **Delivery Confirmation** (Driver) updates Shipment Status and triggers Customer Notification
- **Return Request** (Customer) triggers Return Workflow (Returns) and Inventory Adjustment (Warehouse)
- **Expense Approval** (Finance) triggers Payment Processing and Driver Notification


# 20. UI WIREFRAME DESCRIPTION

## 20.1 Login Page

The login page features a split-screen layout. The left panel (60% width) displays the CLMS branding, a welcome message in Khmer and English, and an illustrative logistics image. The right panel (40% width) contains the login form with email and password fields, a "Remember Me" checkbox, a "Forgot Password" link, and a prominent "Sign In" button. The design uses a clean, professional color palette with blue primary accent. On mobile, the layout stacks vertically with branding on top.

## 20.2 Admin Dashboard

The admin dashboard is a full-width layout with a collapsible left sidebar and a top header bar. The sidebar contains the main navigation grouped by module (Admin, Operations, Fleet, Finance, Reports) with icons and collapsible sub-menus. The header bar shows the company logo, a global search bar, a notification bell with badge count, a language switcher (EN/KM), and a user avatar dropdown with profile/logout options.

The main content area contains:
- **Top Row**: 4-6 stat cards showing key metrics (Total Shipments, Active Dispatches, Revenue, Pending Payments, Fleet Status, Warehouse Capacity) with trend indicators (up/down arrows with percentages).
- **Second Row**: A shipment volume line chart (last 30 days) and a delivery performance donut chart (delivered vs pending vs exception).
- **Third Row**: A recent shipments table (last 10) with tracking number, customer, origin, destination, status badge, and date. A pending tasks panel showing items needing attention (approvals, alerts, expiring documents).
- **Fourth Row**: A map widget showing active vehicle locations and a top customers sidebar.

## 20.3 Shipment List Page

The shipment list page features:
- **Header**: Page title "Shipments", breadcrumb navigation, and a "Create Shipment" button.
- **Filter Bar**: A horizontal filter row with dropdowns for Status, Service Type, Date Range, and branch scope. A search input for tracking number, customer name, or phone. A filter toggle button to show advanced filters (destination city, province, driver).
- **Bulk Actions**: A checkbox column for multi-select with bulk action dropdown (Update Status, Assign Driver, Export, Delete).
- **Data Table**: Columns for Tracking Number (clickable link), Customer Name, Origin City, Destination City, Weight, Cost, Status (color-coded badge), Date Created, and Actions (view/edit/delete dropdown). Rows are sortable by clicking column headers.
- **Pagination**: Bottom pagination with "Showing X to Y of Z results" text, page size selector (10, 25, 50, 100), and page navigation buttons.

## 20.4 Shipment Create/Edit Page

The shipment creation form is organized in a multi-section card layout:
- **Section 1 - Service Details**: Service type dropdown (Standard/Express/Economy/Freight/Same Day), Shipment type (Domestic/International/Intra-city/Inter-city), Special flags (Fragile, Hazardous, Temperature Sensitive toggles).
- **Section 2 - Sender Information**: Auto-fill from customer profile, Name, Phone, Address (with address autocomplete from Google Maps), City, Province, and GPS coordinates (auto-captured from address).
- **Section 3 - Recipient Information**: Same fields as sender, plus "Save as Address" checkbox for customer address book.
- **Section 4 - Package Details**: Dynamic item rows with Name, Quantity, Weight (kg), Dimensions (L x W x H cm), Value. An "Add Item" button for more rows. Auto-calculated total weight, volume, and estimated cost displayed on the right.
- **Section 5 - Pricing**: Shipping cost (auto-calculated), Insurance cost, Discount, Tax, COD amount, Total. Currency selector (USD/KHR).
- **Section 6 - Special Instructions**: Text area for delivery instructions, Preferred delivery time window, and Internal notes.
- **Action Buttons**: "Save as Draft", "Create and Confirm", "Cancel" at the bottom.

## 20.5 Shipment Detail/Tracking Page

The shipment detail page is a comprehensive view with:
- **Header**: Tracking number (prominently displayed with copy button), Status badge (color-coded), Shipment type, Service type.
- **Timeline Widget**: A vertical timeline showing all status changes with timestamps, descriptions, and location pins. Current status highlighted.
- **Tabbed Content Area**:
  - **Overview Tab**: Sender/recipient details, package summary, cost breakdown, special instructions.
  - **Items Tab**: Table of shipment items with quantity, weight, dimensions, value.
  - **Packages Tab**: List of physical packages with barcode, dimensions, current warehouse location.
  - **Tracking Tab**: Interactive map with route visualization, GPS breadcrumbs, and real-time driver location.
  - **Documents Tab**: Generated labels, manifests, receipts, and proof of delivery.
  - **History Tab**: Complete audit trail of all changes with user attribution.
- **Action Buttons**: Status update, Assign to Dispatch, Print Label, Cancel, and more (context-dependent).

## 20.6 Dispatch Management Page

- **Dispatch Dashboard**: Kanban-style board with columns for Pending, Loading, In Transit, Delivering, and Completed. Dispatch cards show dispatch number, driver name, vehicle plate, shipment count, and progress bar.
- **Create Dispatch Form**: Step-by-step wizard:
  - Step 1: Select Origin Warehouse and Destination Route
  - Step 2: Allocate Shipments (drag-and-drop from pending list, capacity validation)
  - Step 3: Assign Driver(s) and Vehicle(s) with availability check
  - Step 4: Review manifest and confirm
- **Dispatch Detail Page**: Shows manifest, driver assignment, vehicle assignment, real-time progress tracking on map, and delivery status for each shipment in the dispatch.

## 20.7 Fleet Management Pages

- **Vehicle List**: Table with vehicle photo thumbnail, plate number, type, branch, status badge (available/in-use/maintenance/retired), current mileage, next maintenance date, and actions. Status filter tabs at the top.
- **Vehicle Detail**: Tabbed view with Overview (specs, capacity, documents), Maintenance History (timeline), Documents (registration, insurance, inspection with expiry alerts), and Activity Log.
- **Driver List**: Similar table with driver photo, name, employee ID, phone, status badge, current dispatch (if any), total deliveries, average rating (star display), and license expiry date.
- **Driver Detail**: Tabbed view with Overview (personal info, performance metrics), Documents (with verification status), Schedule (calendar view), Active Dispatch (real-time), and Expense History.

## 20.8 Warehouse Management Pages

- **Warehouse Overview**: Visual floor plan representation (simplified grid) showing zones with color-coded capacity (green = available, yellow = filling, red = full). Zone cards with name, type, capacity bar, and item count.
- **Inventory List**: Searchable table with SKU, item name, category, quantity, location (zone-shelf), value, status, and last movement date. Quick stock adjustment button. Filter by zone, category, status.
- **Stock Adjustment Form**: Item selector, adjustment type (addition/subtraction/correction), quantity change with before/after preview, reason textarea, supporting document upload, and submit for approval.

## 20.9 Financial Pages

- **Invoice List**: Filterable table with invoice number, customer, amount, balance due, status badge, issue date, and due date. Color-coded overdue indicators.
- **Invoice Detail**: Professional invoice layout with company header, customer details, line items table, subtotals, tax, discounts, total, payment history, and status. Buttons for "Send to Customer", "Record Payment", "Download PDF".
- **Payment Recording Form**: Invoice selector (auto-fills remaining balance), payment method dropdown, amount input (with remaining balance display), date, reference number, notes, and receipt upload.
- **Expense List/Entry**: Category-based expense tracking with approval workflow indicators, receipt attachments, and monthly budget vs actual comparison charts.

## 20.10 Customer Portal

- **Portal Dashboard**: Simplified layout with 3 main cards: Active Shipments (count with quick track button), Pending Invoices (total amount due), Recent Deliveries. A quick shipment creation form and a tracking input.
- **Track Shipment**: Large centered search input for tracking number. Results show a visual timeline/progress bar (similar to e-commerce tracking), package details, estimated delivery, and real-time map.
- **Create Shipment**: Simplified version of the admin form, pre-populated with saved addresses. Address autocomplete. Quick pricing calculator. COD setup option.

## 20.11 Driver Mobile Interface

- **Mobile Dashboard**: Card-based layout optimized for one-hand use. Today's summary (deliveries to complete, completed, earnings). Active dispatch card with prominent "Start" button. Quick action buttons (Update Status, Report Issue, View Schedule).
- **Delivery List**: Scrollable list of today's deliveries with recipient name, address, status icon, and time window. Swipe actions for quick status update. Tap to expand for details and navigation.
- **Proof of Delivery**: Camera integration for photo capture, signature pad for digital signature, OTP input field, and GPS auto-capture. Delivery confirmation button.
- **Expense Submission**: Quick form with category dropdown, amount, receipt photo capture, and submit. Running total of today's expenses displayed.

## 20.12 Reports Pages

- **Report Selection**: Grid of report cards organized by category (Operational, Financial, Fleet, Warehouse, Customer). Each card shows report name, description, and icon.
- **Report Parameters**: Dynamic parameter form based on report type - date range picker, branch selector, category filters, and grouping options.
- **Report Display**: Interactive charts (line, bar, pie, donut) with hover tooltips. Data table below with sortable columns. Export buttons (PDF, Excel, CSV) in the toolbar.
- **Saved Reports**: Ability to save report configurations for quick access. Scheduled report generation via email.

## 20.13 Responsive Design Notes

- **Desktop (1200px+)**: Full sidebar navigation, multi-column layouts, spacious data tables with all columns visible.
- **Tablet (768px-1199px)**: Collapsible sidebar (icon-only mode), two-column layouts where applicable, horizontally scrollable tables with essential columns visible.
- **Mobile (below 768px)**: Hidden sidebar with hamburger menu toggle, single-column stacked layouts, card-based data display instead of tables, bottom navigation bar for driver and customer mobile interfaces, swipe gestures for common actions.

---

# END OF DOCUMENTATION

---

**Document Version:** 1.0.0
**Total Tables Documented:** 42
**Total Columns Documented:** 600+
**Total Relationships Defined:** 50+
**Total Permissions Defined:** 80+
**Total API Endpoints:** 100+

---

*This document serves as the comprehensive technical reference for the Cambodia Logistics Management System database and application architecture. All development, testing, and deployment activities should reference this document as the authoritative source of truth.*
