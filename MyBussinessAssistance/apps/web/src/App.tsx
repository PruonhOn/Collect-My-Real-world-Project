import { Route, Routes } from "react-router-dom";
import { PublicOnly, RequireAuth } from "@/components/auth/require-auth";
import { AppShell } from "@/components/layout/app-shell";
import { RequireModule } from "@/components/app/module-locked";
import { ForgotPasswordPage } from "@/pages/auth/forgot-password";
import { LoginPage } from "@/pages/auth/login";
import { OnboardingPage } from "@/pages/auth/onboarding";
import { RegisterPage } from "@/pages/auth/register";
import { ResetPasswordPage } from "@/pages/auth/reset-password";
import { VerifyEmailPage } from "@/pages/auth/verify-email";
import { SettingsPage } from "@/pages/app/settings";
import { AiPage } from "@/pages/app/ai";
import { AuditPage } from "@/pages/app/audit";
import { BranchesPage } from "@/pages/app/branches";
import { CustomersPage } from "@/pages/app/customers";
import { EmployeesPage } from "@/pages/app/employees";
import { NotificationsPage } from "@/pages/app/notifications";
import { PayrollPage } from "@/pages/app/payroll";
import { PosPage } from "@/pages/app/pos";
import { OrdersPage } from "@/pages/app/orders";
import { SubscriptionPage } from "@/pages/app/subscription";
import { TransfersPage } from "@/pages/app/transfers";
import { ExpensesPage } from "@/pages/app/expenses";
import { InventoryPage } from "@/pages/app/inventory";
import { InvoicePage } from "@/pages/app/invoice";
import { InvoicesPage } from "@/pages/app/invoices";
import { ProductsPage } from "@/pages/app/products";
import { PurchasesPage } from "@/pages/app/purchases";
import { ReportsPage } from "@/pages/app/reports";
import { SalesPage } from "@/pages/app/sales";
import { SuppliersPage } from "@/pages/app/suppliers";
import { DashboardPage } from "@/pages/dashboard";
import { InvitationPage } from "@/pages/invitation";
import { LandingPage } from "@/pages/landing";
import { LegalPage } from "@/pages/legal";
import { AdminPage } from "@/pages/admin";
import { ModulePlaceholderPage } from "@/pages/module-placeholder";
import { NotFoundPage } from "@/pages/not-found";

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/privacy" element={<LegalPage kind="privacy" />} />
      <Route path="/terms" element={<LegalPage kind="terms" />} />
      <Route path="/admin" element={<AdminPage />} />

      <Route
        path="/login"
        element={
          <PublicOnly>
            <LoginPage />
          </PublicOnly>
        }
      />
      <Route
        path="/register"
        element={
          <PublicOnly>
            <RegisterPage />
          </PublicOnly>
        }
      />
      <Route
        path="/forgot-password"
        element={
          <PublicOnly>
            <ForgotPasswordPage />
          </PublicOnly>
        }
      />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route path="/verify-email" element={<VerifyEmailPage />} />

      <Route
        path="/onboarding"
        element={
          <RequireAuth requireWorkspace={false}>
            <OnboardingPage />
          </RequireAuth>
        }
      />
      <Route
        path="/invitations/:token"
        element={
          <RequireAuth requireWorkspace={false}>
            <InvitationPage />
          </RequireAuth>
        }
      />

      <Route
        path="/app"
        element={
          <RequireAuth>
            <AppShell />
          </RequireAuth>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route path="products" element={<ProductsPage />} />
        <Route path="inventory" element={<InventoryPage />} />
        <Route path="customers" element={<CustomersPage />} />
        <Route path="suppliers" element={<SuppliersPage />} />
        <Route
          path="orders"
          element={
            <RequireModule moduleKey="orders">
              <OrdersPage />
            </RequireModule>
          }
        />
        <Route path="sales" element={<SalesPage />} />
        <Route
          path="pos"
          element={
            <RequireModule moduleKey="pos">
              <PosPage />
            </RequireModule>
          }
        />
        <Route
          path="notifications"
          element={<NotificationsPage />}
        />
        <Route path="invoices" element={<InvoicesPage />} />
        <Route path="invoices/:saleId" element={<InvoicePage />} />
        <Route path="purchases" element={<PurchasesPage />} />
        <Route path="expenses" element={<ExpensesPage />} />
        <Route
          path="payroll"
          element={
            <RequireModule moduleKey="payroll">
              <PayrollPage />
            </RequireModule>
          }
        />
        <Route path="reports" element={<ReportsPage />} />
        <Route path="employees" element={<EmployeesPage />} />
        <Route
          path="branches"
          element={
            <RequireModule moduleKey="branches">
              <BranchesPage />
            </RequireModule>
          }
        />
        <Route
          path="transfers"
          element={
            <RequireModule moduleKey="transfers">
              <TransfersPage />
            </RequireModule>
          }
        />
        <Route path="subscription" element={<SubscriptionPage />} />
        <Route
          path="ai"
          element={
            <RequireModule moduleKey="ai">
              <AiPage />
            </RequireModule>
          }
        />
        <Route
          path="audit"
          element={
            <RequireModule moduleKey="audit">
              <AuditPage />
            </RequireModule>
          }
        />
        <Route path="settings" element={<SettingsPage />} />
        <Route path=":module" element={<ModulePlaceholderPage />} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
