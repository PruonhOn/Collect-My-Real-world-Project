import type { Env } from "../env";
import { sendEmail } from "./email";

function escapeHtml(value: string) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function layout(title: string, body: string) {
  return `<!doctype html>
<html>
  <body style="margin:0;padding:32px 16px;background:#f1f5f9;font-family:Inter,Arial,sans-serif;color:#0f172a;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
      <div style="background:#0f766e;padding:20px 24px;color:#ffffff;">
        <div style="font-size:16px;font-weight:700;letter-spacing:-0.2px;">MyBusiness Assistant</div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:2px;opacity:0.8;">Cambodia business platform</div>
      </div>
      <div style="padding:24px;">
        <h1 style="margin:0 0 12px;font-size:20px;">${escapeHtml(title)}</h1>
        ${body}
      </div>
      <div style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;">
        Sent by MyBusiness Assistant. If you did not request this email, you can ignore it.
      </div>
    </div>
  </body>
</html>`;
}

function button(url: string, label: string) {
  return `<p style="margin:20px 0;"><a href="${url}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;">${escapeHtml(
    label,
  )}</a></p><p style="font-size:12px;color:#64748b;word-break:break-all;">${url}</p>`;
}

export async function sendVerificationEmail(
  env: Env,
  input: { to: string; name: string; token: string },
) {
  const url = `${env.APP_URL}/verify-email?token=${encodeURIComponent(input.token)}`;
  return sendEmail(env, {
    to: input.to,
    subject: "Verify your MyBusiness Assistant email",
    html: layout(
      `Welcome, ${input.name}`,
      `<p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#334155;">Confirm your email address to secure your account and unlock team invitations.</p>${button(url, "Verify email")}`,
    ),
    text: `Verify your email: ${url}`,
  });
}

export async function sendPasswordResetEmail(
  env: Env,
  input: { to: string; name: string; token: string },
) {
  const url = `${env.APP_URL}/reset-password?token=${encodeURIComponent(input.token)}`;
  return sendEmail(env, {
    to: input.to,
    subject: "Reset your MyBusiness Assistant password",
    html: layout(
      "Reset your password",
      `<p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#334155;">Hi ${escapeHtml(
        input.name,
      )}, we received a request to reset your password. This link expires in one hour.</p>${button(
        url,
        "Choose a new password",
      )}`,
    ),
    text: `Reset your password: ${url}`,
  });
}

export async function sendInvitationEmail(
  env: Env,
  input: {
    to: string;
    workspaceName: string;
    inviterName: string;
    roleName: string;
    token: string;
  },
) {
  const url = `${env.APP_URL}/invitations/${encodeURIComponent(input.token)}`;
  return sendEmail(env, {
    to: input.to,
    subject: `You are invited to ${input.workspaceName}`,
    html: layout(
      `Join ${input.workspaceName}`,
      `<p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#334155;">${escapeHtml(
        input.inviterName,
      )} invited you to join <strong>${escapeHtml(
        input.workspaceName,
      )}</strong> as <strong>${escapeHtml(input.roleName)}</strong>.</p>${button(
        url,
        "Accept invitation",
      )}`,
    ),
    text: `Join ${input.workspaceName}: ${url}`,
  });
}
