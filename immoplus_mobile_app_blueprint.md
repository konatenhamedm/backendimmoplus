# Immoplus Mobile App Blueprint

## 1. Project Overview
Immoplus is a comprehensive property management mobile application. It allows administrators and agents to manage properties (houses, apartments), landlords (propriétaires), tenants (locataires), lease contracts (contrats de location), inventory of fixtures (état des lieux), and rent payments (paiements & factures). This blueprint is designed for LIA to automatically generate the React Native (or alternative framework) screens and integrate the necessary APIs.

## 2. Global Architecture & Thematic Styling
- **Framework Specifications**: React Native / Expo.
- **Navigation**: Bottom Tab Navigation + Stack Navigation.
- **UI/UX Aesthetics**:
  - Consistent with the Ateliya/Immoplus brand design.
  - Professional teal/blue gradient themes.
  - Clean white backgrounds for data-heavy views.
  - Soft glowing elements and micro-animations for a premium feel.
  - Dynamic table borders and clean list views.

## 3. Screens Definitions & API Integrations

### Screen 1: Authentication (Login)
- **Route Path**: `/login`
- **Description**: Secure entry point for administrators and agents.
- **UI Elements**:
  - Logo Immoplus.
  - Email/Username input field (with validation).
  - Password input field (with visibility toggle).
  - Modern gradient background (mesh gradient + subtle grid pattern).
  - Login Button with loading state.
- **API Integration**: 
  - `POST /api/auth/login` (Expects a JWT token on success).
- **Behavior**: On success, token is securely stored (e.g., AsyncStorage/SecureStore) and user is redirected to the Dashboard.

### Screen 2: Dashboard (Home)
- **Route Path**: `/dashboard`
- **Description**: High-level overview of the property management system.
- **UI Elements**:
  - Welcome Header greeting the user.
  - KPI Cards: Total Houses, Total Tenants, Rents Collected, Outstanding Rentals.
  - Recent Activities feed (e.g., latest payments, new contracts).
  - Quick Action FABs: Add Tenant, Record Payment, New Lease.
- **API Integration**: 
  - `GET /api/dashboard` (Retrieves aggregate data for KPIs).
- **Behavior**: Supports pull-to-refresh to fetch the latest analytics.

### Screen 3: Property Management (Maisons & Appartements)
- **Route Path**: `/properties`
- **Description**: Central hub for exploring all real estate units.
- **UI Elements**:
  - Top Tabs: "Maisons" | "Appartements".
  - Search bar with filters (Status: Available / Occupied).
  - List of properties displayed via Cards (thumbnail, reference, location, rent amount, occupancy badge).
- **API Integration**: 
  - `GET /api/maisons`
  - `GET /api/appartements` (and potentially `GET /api/appartements/free` for availability).
- **Behavior**: Tapping a property card navigates to the Property Details screen.

### Screen 4: Property Details
- **Route Path**: `/properties/details/:id`
- **Description**: Deep dive into a specific unit's information.
- **UI Elements**:
  - Image Carousel for property photos.
  - Details Section: Number of rooms, rent price, specific amenities.
  - Associated Tenant Card (if currently occupied).
  - Actions: Edit, View Lease, Delete (with confirmation modal).

### Screen 5: Tenants (Locataires) & Landlords (Propriétaires)
- **Route Path**: `/people`
- **Description**: Directory of all stakeholders.
- **UI Elements**:
  - Top Tabs: "Locataires" | "Propriétaires".
  - Clean List view displaying contact details, avatars, and linked properties.
- **API Integration**: 
  - `GET /api/locataires`
  - `GET /api/proprios`

### Screen 6: Lease Contracts (Contrats de Location) & E-Signature
- **Route Path**: `/contracts`
- **Description**: Management of lease documents and signing process.
- **UI Elements**:
  - Searchable list of active and pending contracts.
  - Detail view outlining terms, dates, and parties involved.
  - **Electronic Signature Canvas**: A specific component allowing the tenant and landlord to draw their signatures directly on the mobile screen.
- **API Integration**: 
  - `GET /api/contrat_locations`
  - `POST /api/contrat_locations/{id}/sign` (Submits the base64-encoded signature image).

### Screen 7: Inventory of Fixtures (État des Lieux)
- **Route Path**: `/etat-lieux`
- **Description**: Digital record for check-in and check-out of properties.
- **UI Elements**:
  - Stepper form to review the condition of each room (Walls, Floors, Fixtures).
  - Photo attachment button (utilizing the device camera) to document damages.
- **API Integration**: 
  - `GET /api/etat_lieux`
  - `POST /api/etat_lieux` (Form-data submission handling both text details and images).

### Screen 8: Payments & Billing (Paiements & Factures)
- **Route Path**: `/payments`
- **Description**: Financial log for rent collections.
- **UI Elements**:
  - History list of recent payments and pending invoices.
  - Unpaid values highlighted in distinct red text.
  - "Record Payment" Modal (Select tenant, input amount, choose payment method).
- **API Integration**: 
  - `GET /api/facture_locations`
  - `GET /api/paiements`
  - `POST /api/paiements` (Registers a new rent payment).

## 4. Root Navigation Flow
The mobile app will implement the following navigation structure:

- **AuthStack**: 
  - Login Screen -> Forgot Password Screen.
- **MainApp (BottomTabNavigator)**:
  - **Home** (Dashboard)
  - **Properties** (Maisons/Appartements)
  - **Tenants** (Locataires/Propriétaires)
  - **More** (Contracts, État des Lieux, Payments, Settings)
