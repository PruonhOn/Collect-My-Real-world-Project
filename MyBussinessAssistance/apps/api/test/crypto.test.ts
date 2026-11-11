import { describe, expect, it } from "vitest";
import {
  hashPassword,
  randomToken,
  sha256Hex,
  verifyPassword,
} from "../src/lib/crypto";
import { buildWorkspaceSlug, slugify } from "../src/lib/slug";

describe("password hashing", () => {
  it("verifies the original password and rejects a wrong one", async () => {
    const hash = await hashPassword("StrongPass123!");
    expect(hash.startsWith("pbkdf2$600000$")).toBe(true);

    await expect(verifyPassword("StrongPass123!", hash)).resolves.toBe(true);
    await expect(verifyPassword("WrongPass123!", hash)).resolves.toBe(false);
  });

  it("uses a unique salt for every hash", async () => {
    const [first, second] = await Promise.all([
      hashPassword("StrongPass123!"),
      hashPassword("StrongPass123!"),
    ]);
    expect(first).not.toBe(second);
    await expect(verifyPassword("StrongPass123!", second)).resolves.toBe(true);
  });

  it("rejects malformed stored hashes", async () => {
    await expect(verifyPassword("anything", "not-a-hash")).resolves.toBe(false);
    await expect(
      verifyPassword("anything", "pbkdf2$10$abc$def"),
    ).resolves.toBe(false);
  });
});

describe("tokens", () => {
  it("hashes deterministically with SHA-256", async () => {
    const digest = await sha256Hex("hello");
    expect(digest).toBe(
      "2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824",
    );
  });

  it("generates URL-safe random tokens", () => {
    const first = randomToken(32);
    const second = randomToken(32);
    expect(first).not.toBe(second);
    expect(first).toMatch(/^[A-Za-z0-9_-]+$/);
  });
});

describe("workspace slugs", () => {
  it("slugifies names and appends a random suffix", () => {
    expect(slugify("Dara Mart & Cafe")).toBe("dara-mart-cafe");
    const slug = buildWorkspaceSlug("Dara Mart");
    expect(slug).toMatch(/^dara-mart-[a-z0-9]{6}$/);
  });
});
