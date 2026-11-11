const encoder = new TextEncoder();

const PBKDF2_ITERATIONS = 600_000;
const KEY_LENGTH_BYTES = 32;
const SALT_LENGTH_BYTES = 16;

function bytesToBase64(bytes: Uint8Array) {
  let binary = "";
  for (const byte of bytes) {
    binary += String.fromCharCode(byte);
  }
  return btoa(binary);
}

function base64ToBytes(value: string) {
  const binary = atob(value);
  const bytes = new Uint8Array(binary.length);
  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index);
  }
  return bytes;
}

export function randomToken(byteLength = 32) {
  const bytes = new Uint8Array(byteLength);
  crypto.getRandomValues(bytes);
  return bytesToBase64(bytes)
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/g, "");
}

export async function sha256Hex(input: string) {
  const digest = await crypto.subtle.digest("SHA-256", encoder.encode(input));
  return Array.from(new Uint8Array(digest))
    .map((byte) => byte.toString(16).padStart(2, "0"))
    .join("");
}

async function deriveKey(
  password: string,
  salt: Uint8Array,
  iterations: number,
) {
  const baseKey = await crypto.subtle.importKey(
    "raw",
    encoder.encode(password),
    "PBKDF2",
    false,
    ["deriveBits"],
  );

  return crypto.subtle.deriveBits(
    {
      name: "PBKDF2",
      salt,
      iterations,
      hash: "SHA-256",
    },
    baseKey,
    KEY_LENGTH_BYTES * 8,
  );
}

export async function hashPassword(password: string) {
  const salt = new Uint8Array(SALT_LENGTH_BYTES);
  crypto.getRandomValues(salt);
  const key = await deriveKey(password, salt, PBKDF2_ITERATIONS);
  return `pbkdf2$${PBKDF2_ITERATIONS}$${bytesToBase64(salt)}$${bytesToBase64(
    new Uint8Array(key),
  )}`;
}

export async function verifyPassword(password: string, stored: string) {
  const parts = stored.split("$");
  if (parts.length !== 4 || parts[0] !== "pbkdf2") {
    return false;
  }

  const iterations = Number(parts[1]);
  if (!Number.isFinite(iterations) || iterations < 1_000) {
    return false;
  }

  try {
    const salt = base64ToBytes(parts[2]);
    const expected = base64ToBytes(parts[3]);
    const actual = new Uint8Array(await deriveKey(password, salt, iterations));

    if (actual.length !== expected.length) {
      return false;
    }

    let difference = 0;
    for (let index = 0; index < actual.length; index += 1) {
      difference |= actual[index] ^ expected[index];
    }

    return difference === 0;
  } catch {
    return false;
  }
}
