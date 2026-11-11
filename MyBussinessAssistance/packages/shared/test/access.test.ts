import { describe, expect, it } from "vitest";
import {
  canManageMemberRole,
  hasPermission,
  isSystemRoleKey,
} from "../src/access";

describe("hasPermission", () => {
  it("checks permission sets and arrays", () => {
    expect(hasPermission(new Set(["sale:read"]), "sale:read")).toBe(true);
    expect(hasPermission(new Set(["sale:read"]), "sale:create")).toBe(false);
    expect(hasPermission(["member:read"], "member:read")).toBe(true);
    expect(hasPermission([], "member:read")).toBe(false);
  });
});

describe("isSystemRoleKey", () => {
  it("recognizes system roles only", () => {
    expect(isSystemRoleKey("owner")).toBe(true);
    expect(isSystemRoleKey("sales_staff")).toBe(true);
    expect(isSystemRoleKey("custom_role")).toBe(false);
  });
});

describe("canManageMemberRole", () => {
  it("prevents anyone from managing the owner", () => {
    expect(canManageMemberRole("owner", "owner")).toBe(false);
    expect(canManageMemberRole("admin", "owner")).toBe(false);
  });

  it("lets owners and admins manage other roles", () => {
    expect(canManageMemberRole("owner", "manager")).toBe(true);
    expect(canManageMemberRole("admin", "sales_staff")).toBe(true);
    expect(canManageMemberRole("manager", "sales_staff")).toBe(false);
  });
});
