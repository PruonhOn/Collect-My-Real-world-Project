import { SYSTEM_ROLES, type SystemRoleKey } from "./permissions";

export function hasPermission(
  granted: ReadonlySet<string> | readonly string[],
  required: string,
) {
  if (typeof (granted as ReadonlySet<string>).has === "function") {
    return (granted as ReadonlySet<string>).has(required);
  }
  return (granted as readonly string[]).includes(required);
}

export function isSystemRoleKey(key: string): key is SystemRoleKey {
  return SYSTEM_ROLES.some((role) => role.key === key);
}

export function canManageMemberRole(actorRoleKey: string, targetRoleKey: string) {
  if (targetRoleKey === "owner") {
    return false;
  }
  if (actorRoleKey === "owner") {
    return true;
  }
  return actorRoleKey === "admin" && targetRoleKey !== "owner";
}
