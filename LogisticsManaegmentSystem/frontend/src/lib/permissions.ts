/**
 * Permission helpers.
 *
 * The server is the only authority. These helpers exist so the UI can hide
 * actions a user cannot perform; they are never a security control. Every
 * protected endpoint independently enforces the same permission server-side.
 */

export function can(
  permissions: string[] | undefined,
  permission: string | string[],
): boolean {
  if (!permissions || permissions.length === 0) {
    return false;
  }

  const required = Array.isArray(permission) ? permission : [permission];
  for (let i = 0; i < required.length; i++) {
    if (permissions.includes(required[i])) {
      return true;
    }
  }
  return false;
}

export function canAll(
  permissions: string[] | undefined,
  required: string[],
): boolean {
  if (!permissions || permissions.length === 0) {
    return false;
  }
  for (let i = 0; i < required.length; i++) {
    if (!permissions.includes(required[i])) {
      return false;
    }
  }
  return true;
}

export function hasRole(roles: string[] | undefined, role: string): boolean {
  if (!roles || roles.length === 0) {
    return false;
  }
  return roles.includes(role);
}
