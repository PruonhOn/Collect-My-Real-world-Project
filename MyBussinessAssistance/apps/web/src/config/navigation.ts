import {
  ArrowLeftRight,
  BarChart3,
  Bell,
  Boxes,
  Building2,
  Clock,
  ClipboardList,
  CreditCard,
  FileText,
  Handshake,
  History,
  LayoutDashboard,
  Package,
  Receipt,
  Settings,
  ShoppingBag,
  ShoppingCart,
  Sparkles,
  UserCog,
  Users,
  type LucideIcon,
} from "lucide-react";

export type NavItem = {
  key: string;
  to: string;
  icon: LucideIcon;
  phase: string;
  ready?: boolean;
  module: string;
  alwaysAllowed?: boolean;
};

export type NavGroup = {
  key: string;
  items: NavItem[];
};

export const navGroups: NavGroup[] = [
  {
    key: "groupOverview",
    items: [
      {
        key: "dashboard",
        to: "/app",
        icon: LayoutDashboard,
        phase: "Phase 0",
        ready: true,
        module: "dashboard",
        alwaysAllowed: true,
      },
    ],
  },
  {
    key: "groupCatalog",
    items: [
      { key: "products", to: "/app/products", icon: Package, phase: "Phase 2", ready: true, module: "products" },
      { key: "inventory", to: "/app/inventory", icon: Boxes, phase: "Phase 2", ready: true, module: "inventory" },
    ],
  },
  {
    key: "groupSales",
    items: [
      { key: "orders", to: "/app/orders", icon: ClipboardList, phase: "Phase 3", ready: true, module: "orders" },
      { key: "sales", to: "/app/sales", icon: ShoppingCart, phase: "Phase 2", ready: true, module: "sales" },
      { key: "pos", to: "/app/pos", icon: CreditCard, phase: "Phase 7", ready: true, module: "pos" },
      { key: "customers", to: "/app/customers", icon: Users, phase: "Phase 2", ready: true, module: "customers" },
      { key: "invoices", to: "/app/invoices", icon: FileText, phase: "Phase 2", ready: true, module: "invoices" },
    ],
  },
  {
    key: "groupSupply",
    items: [
      { key: "purchases", to: "/app/purchases", icon: ShoppingBag, phase: "Phase 2", ready: true, module: "purchases" },
      { key: "suppliers", to: "/app/suppliers", icon: Handshake, phase: "Phase 2", ready: true, module: "suppliers" },
    ],
  },
  {
    key: "groupFinance",
    items: [
      { key: "expenses", to: "/app/expenses", icon: Receipt, phase: "Phase 2", ready: true, module: "expenses" },
      { key: "reports", to: "/app/reports", icon: BarChart3, phase: "Phase 2", ready: true, module: "reports" },
      { key: "payroll", to: "/app/payroll", icon: Clock, phase: "Phase 8", ready: true, module: "payroll" },
    ],
  },
  {
    key: "groupOrganization",
    items: [
      { key: "employees", to: "/app/employees", icon: UserCog, phase: "Phase 3", ready: true, module: "employees" },
      { key: "branches", to: "/app/branches", icon: Building2, phase: "Phase 3", ready: true, module: "branches" },
      { key: "transfers", to: "/app/transfers", icon: ArrowLeftRight, phase: "Phase 3", ready: true, module: "transfers" },
    ],
  },
  {
    key: "groupIntelligence",
    items: [
      { key: "ai", to: "/app/ai", icon: Sparkles, phase: "Phase 5", ready: true, module: "ai" },
    ],
  },
  {
    key: "groupManage",
    items: [
      { key: "audit", to: "/app/audit", icon: History, phase: "Phase 4", ready: true, module: "audit" },
      { key: "notifications", to: "/app/notifications", icon: Bell, phase: "Phase 9", ready: true, module: "notifications" },
      {
        key: "settings",
        to: "/app/settings",
        icon: Settings,
        phase: "Phase 1",
        ready: true,
        module: "settings",
        alwaysAllowed: true,
      },
      {
        key: "subscription",
        to: "/app/subscription",
        icon: CreditCard,
        phase: "Phase 3",
        ready: true,
        module: "subscription",
        alwaysAllowed: true,
      },
    ],
  },
];

export const allNavItems = navGroups.flatMap((group) => group.items);

export function findNavItem(pathname: string) {
  const matches = allNavItems
    .filter((item) =>
      item.to === "/app"
        ? pathname === "/app"
        : pathname.startsWith(item.to),
    )
    .sort((a, b) => b.to.length - a.to.length);

  return matches[0];
}
