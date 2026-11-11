import { execFileSync } from "node:child_process";
import { mkdtempSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const args = process.argv.slice(2);
const email = args.find((arg) => !arg.startsWith("--"));
const remote = args.includes("--remote");
const support = args.includes("--support");
const role = support ? "support_admin" : "super_admin";

if (!email || !email.includes("@")) {
  console.error("Usage: npm run admin:grant -- <email> [--remote] [--support]");
  console.error("  default    grants super_admin (full access)");
  console.error("  --support  grants support_admin (view-only)");
  process.exit(1);
}

const dir = mkdtempSync(join(tmpdir(), "mba-admin-"));
const file = join(dir, "grant.sql");

writeFileSync(
  file,
  `INSERT INTO platform_admins (id, user_id, role)
SELECT 'pa_' || u.id, u.id, '${role}'
FROM users u
WHERE lower(u.email) = lower('${email.replace(/'/g, "''")}')
ON CONFLICT(user_id) DO UPDATE SET role = excluded.role;
`,
);

const config = remote
  ? ["-c", "apps/api/wrangler.jsonc", "--remote"]
  : ["-c", "apps/api/wrangler.dev.jsonc", "--local"];

execFileSync(
  "npx",
  ["wrangler", "d1", "execute", "mybusiness-db", ...config, "--file", file],
  { stdio: "inherit", shell: process.platform === "win32" },
);

console.log(
  remote
    ? `Granted ${role} to ${email} on the production database.`
    : `Granted ${role} to ${email} on the local database.`,
);
