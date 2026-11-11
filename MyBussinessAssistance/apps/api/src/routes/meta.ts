import { Hono } from "hono";
import {
  APP_NAME,
  APP_TIMEZONE,
  ENTITLEMENT_KEYS,
  PERMISSION_CATALOG,
  SUBSCRIPTION_STATUSES,
  SUPPORTED_CURRENCIES,
  SUPPORTED_LOCALES,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { ok } from "../lib/response";

export const metaRoutes = new Hono<AppEnv>();

metaRoutes.get("/", (context) =>
  ok(context, {
    name: APP_NAME,
    timezone: APP_TIMEZONE,
    locales: SUPPORTED_LOCALES,
    currencies: SUPPORTED_CURRENCIES,
    subscriptionStatuses: SUBSCRIPTION_STATUSES,
    entitlementKeys: ENTITLEMENT_KEYS,
    permissions: PERMISSION_CATALOG,
  }),
);
