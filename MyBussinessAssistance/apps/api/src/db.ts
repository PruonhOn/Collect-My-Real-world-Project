import { createDb } from "@mba/db";
import type { Env } from "./env";

export function getDb(env: Env) {
  return createDb(env.DB);
}
