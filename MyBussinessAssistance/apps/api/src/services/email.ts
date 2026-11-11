import type { Env } from "../env";
import { ApiError } from "../lib/errors";

export type EmailMessage = {
  to: string | string[];
  subject: string;
  html: string;
  text?: string;
};

export type EmailResult = {
  skipped: boolean;
  messageId?: string;
};

export async function sendEmail(
  env: Env,
  message: EmailMessage,
): Promise<EmailResult> {
  const apiKey = env.RESEND_API_KEY;
  const fromEmail = env.RESEND_FROM_EMAIL;

  if (!apiKey || !fromEmail) {
    if (env.ENVIRONMENT !== "production") {
      return { skipped: true };
    }

    throw new ApiError(
      503,
      "email_not_configured",
      "Email delivery is not configured.",
    );
  }

  const fromName = env.RESEND_FROM_NAME ?? "MyBusiness Assistant";
  const recipients = Array.isArray(message.to) ? message.to : [message.to];

  const response = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      authorization: `Bearer ${apiKey}`,
      "content-type": "application/json",
    },
    body: JSON.stringify({
      from: `${fromName} <${fromEmail}>`,
      to: recipients,
      subject: message.subject,
      html: message.html,
      ...(message.text ? { text: message.text } : {}),
    }),
  });

  if (!response.ok) {
    const detail = await response.text();
    console.error("Resend send failed", response.status, detail);
    throw new ApiError(
      502,
      "email_send_failed",
      "Unable to send email right now.",
    );
  }

  const result = (await response.json()) as { id?: string };
  return { skipped: false, messageId: result.id };
}
