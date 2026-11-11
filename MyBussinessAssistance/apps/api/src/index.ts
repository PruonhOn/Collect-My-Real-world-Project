import { Hono } from "hono";
import { cors } from "hono/cors";
import { logger } from "hono/logger";
import { secureHeaders } from "hono/secure-headers";
import type { AppEnv } from "./env";
import { errorHandler, notFoundHandler } from "./middleware/error";
import { apiRouter } from "./routes";
import { runBillingSweep } from "./services/billing";

const app = new Hono<AppEnv>();

const worker = {
  fetch: app.fetch,
  scheduled: async (event: ScheduledEvent, env: AppEnv["Bindings"]) => {
    try {
      const processed = await runBillingSweep(env);
      console.log(`Billing sweep processed ${processed} subscription(s)`);
    } catch (error) {
      console.error("Billing sweep failed", error);
    }
  },
};

export default worker;

app.use("*", logger());
app.use("*", secureHeaders());
app.use("/api/*", async (context, next) => {
  const allowedOrigins = new Set<string>([context.env.APP_URL]);

  if (context.env.ENVIRONMENT === "development") {
    allowedOrigins.add("http://localhost:5173");
    allowedOrigins.add("http://127.0.0.1:5173");
  }

  const handler = cors({
    origin: (origin) => (allowedOrigins.has(origin) ? origin : null),
    credentials: true,
    allowMethods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allowHeaders: ["Content-Type", "Authorization", "X-Requested-With"],
    maxAge: 86400,
  });

  return handler(context, next);
});

app.route("/api", apiRouter);

app.onError(errorHandler);
app.notFound(notFoundHandler);
