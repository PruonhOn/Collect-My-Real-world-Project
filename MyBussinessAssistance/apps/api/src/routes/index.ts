import { Hono } from "hono";
import { APP_NAME } from "@mba/shared";
import type { AppEnv } from "../env";
import { ok } from "../lib/response";
import { adminRoutes } from "./admin";
import { authRoutes } from "./auth";
import { googleAuthRoutes } from "./google-auth";
import { healthRoutes } from "./health";
import { invitationRoutes } from "./invitations";
import { metaRoutes } from "./meta";
import { workspaceRoutes } from "./workspaces";

export const apiRouter = new Hono<AppEnv>();

apiRouter.get("/v1", (context) =>
  ok(context, {
    name: APP_NAME,
    version: "0.1.0",
    status: "operational",
    docs: "/api/v1/meta",
  }),
);

apiRouter.route("/v1/health", healthRoutes);
apiRouter.route("/v1/meta", metaRoutes);
apiRouter.route("/v1/auth", authRoutes);
apiRouter.route("/v1/auth", googleAuthRoutes);
apiRouter.route("/v1/workspaces", workspaceRoutes);
apiRouter.route("/v1/invitations", invitationRoutes);
apiRouter.route("/v1/admin", adminRoutes);
