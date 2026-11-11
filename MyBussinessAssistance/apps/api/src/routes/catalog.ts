import { Hono } from "hono";
import { and, desc, eq, like, ne, or, sql } from "drizzle-orm";
import {
  branches,
  categories,
  inventoryLevels,
  products,
  stockMovements,
  users,
} from "@mba/db";
import {
  adjustStockSchema,
  createCategorySchema,
  createProductSchema,
  updateCategorySchema,
  updateProductSchema,
} from "@mba/shared";
import type { AppEnv } from "../env";
import { getDb } from "../db";
import { badRequest, conflict, notFound } from "../lib/errors";
import { created, ok } from "../lib/response";
import { newId } from "../lib/ids";
import { parseJson } from "../lib/validate";
import { requireModule, requirePermission } from "../middleware/workspace";
import { writeAudit } from "../services/audit";
import { assertWithinLimit } from "../services/entitlements";

export const categoryRoutes = new Hono<AppEnv>();
export const productRoutes = new Hono<AppEnv>();
export const stockRoutes = new Hono<AppEnv>();

categoryRoutes.get("/", requirePermission("product:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const rows = await db
    .select()
    .from(categories)
    .where(eq(categories.workspaceId, access.workspace.id))
    .orderBy(categories.name);

  const counts = await db
    .select({
      categoryId: products.categoryId,
      count: sql<number>`count(*)`,
    })
    .from(products)
    .where(eq(products.workspaceId, access.workspace.id))
    .groupBy(products.categoryId);

  const countMap = new Map(
    counts.map((row) => [row.categoryId, Number(row.count)]),
  );

  return ok(context, {
    categories: rows.map((row) => ({
      ...row,
      productCount: countMap.get(row.id) ?? 0,
    })),
  });
});

categoryRoutes.post("/", requirePermission("product:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createCategorySchema);
  const db = getDb(context.env);

  const duplicate = await db
    .select({ id: categories.id })
    .from(categories)
    .where(
      and(
        eq(categories.workspaceId, access.workspace.id),
        eq(categories.name, data.name),
      ),
    )
    .limit(1);

  if (duplicate.length > 0) {
    throw conflict("A category with this name already exists.");
  }

  const id = newId("cat");
  const rows = await db
    .insert(categories)
    .values({
      id,
      workspaceId: access.workspace.id,
      name: data.name,
      description: data.description ?? null,
    })
    .returning();

  await writeAudit(context.env, {
    workspaceId: access.workspace.id,
    actorUserId: user.id,
    action: "category.created",
    entityType: "category",
    entityId: id,
    metadata: { name: data.name },
  });

  return created(context, { category: { ...rows[0], productCount: 0 } });
});

categoryRoutes.patch(
  "/:categoryId",
  requirePermission("product:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const categoryId = context.req.param("categoryId");
    const data = await parseJson(context, updateCategorySchema);
    const db = getDb(context.env);

    const existing = await db
      .select()
      .from(categories)
      .where(
        and(
          eq(categories.id, categoryId),
          eq(categories.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    if (!existing[0]) throw notFound("Category not found.");

    if (data.name && data.name !== existing[0].name) {
      const duplicate = await db
        .select({ id: categories.id })
        .from(categories)
        .where(
          and(
            eq(categories.workspaceId, access.workspace.id),
            eq(categories.name, data.name),
            ne(categories.id, categoryId),
          ),
        )
        .limit(1);
      if (duplicate.length > 0) {
        throw conflict("A category with this name already exists.");
      }
    }

    await db
      .update(categories)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.description !== undefined
          ? { description: data.description ?? null }
          : {}),
        updatedAt: Date.now(),
      })
      .where(eq(categories.id, categoryId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "category.updated",
      entityType: "category",
      entityId: categoryId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(categories)
      .where(eq(categories.id, categoryId))
      .limit(1);

    return ok(context, { category: updated[0] });
  },
);

categoryRoutes.delete(
  "/:categoryId",
  requirePermission("product:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const categoryId = context.req.param("categoryId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: categories.id })
      .from(categories)
      .where(
        and(
          eq(categories.id, categoryId),
          eq(categories.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    if (!existing[0]) throw notFound("Category not found.");

    await db.delete(categories).where(eq(categories.id, categoryId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "category.deleted",
      entityType: "category",
      entityId: categoryId,
    });

    return ok(context, { deleted: true });
  },
);

productRoutes.get("/", requirePermission("product:read"), async (context) => {
  const access = context.get("workspace");
  const db = getDb(context.env);
  const search = context.req.query("search")?.trim();
  const status = context.req.query("status");

  const filters = [eq(products.workspaceId, access.workspace.id)];
  if (status === "active" || status === "inactive") {
    filters.push(eq(products.status, status));
  }
  if (search) {
    const term = `%${search}%`;
    filters.push(
      or(
        like(products.name, term),
        like(products.sku, term),
        like(products.barcode, term),
      )!,
    );
  }

  const rows = await db
    .select()
    .from(products)
    .where(and(...filters))
    .orderBy(desc(products.createdAt));

  const categoryRows = await db
    .select({ id: categories.id, name: categories.name })
    .from(categories)
    .where(eq(categories.workspaceId, access.workspace.id));
  const categoryMap = new Map(categoryRows.map((row) => [row.id, row.name]));

  const stockRows = await db
    .select({
      productId: inventoryLevels.productId,
      total: sql<number>`coalesce(sum(${inventoryLevels.quantity}), 0)`,
    })
    .from(inventoryLevels)
    .where(eq(inventoryLevels.workspaceId, access.workspace.id))
    .groupBy(inventoryLevels.productId);
  const stockMap = new Map(
    stockRows.map((row) => [row.productId, Number(row.total)]),
  );

  return ok(context, {
    products: rows.map((row) => {
      const totalStock = stockMap.get(row.id) ?? 0;
      return {
        ...row,
        categoryName: row.categoryId
          ? (categoryMap.get(row.categoryId) ?? null)
          : null,
        totalStock,
        lowStock:
          row.trackInventory && totalStock <= row.lowStockThreshold,
      };
    }),
  });
});

productRoutes.get(
  "/:productId",
  requirePermission("product:read"),
  async (context) => {
    const access = context.get("workspace");
    const productId = context.req.param("productId");
    const db = getDb(context.env);

    const rows = await db
      .select()
      .from(products)
      .where(
        and(
          eq(products.id, productId),
          eq(products.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);

    const product = rows[0];
    if (!product) throw notFound("Product not found.");

    const levels = await db
      .select({
        id: inventoryLevels.id,
        branchId: inventoryLevels.branchId,
        branchName: branches.name,
        branchCode: branches.code,
        quantity: inventoryLevels.quantity,
        updatedAt: inventoryLevels.updatedAt,
      })
      .from(inventoryLevels)
      .innerJoin(branches, eq(inventoryLevels.branchId, branches.id))
      .where(eq(inventoryLevels.productId, productId));

    const movements = await db
      .select({
        id: stockMovements.id,
        type: stockMovements.type,
        quantity: stockMovements.quantity,
        note: stockMovements.note,
        createdAt: stockMovements.createdAt,
        branchName: branches.name,
        actorName: users.name,
      })
      .from(stockMovements)
      .innerJoin(branches, eq(stockMovements.branchId, branches.id))
      .leftJoin(users, eq(stockMovements.createdByUserId, users.id))
      .where(eq(stockMovements.productId, productId))
      .orderBy(desc(stockMovements.createdAt))
      .limit(50);

    return ok(context, { product, levels, movements });
  },
);

productRoutes.post("/", requirePermission("product:create"), async (context) => {
  const access = context.get("workspace");
  const user = context.get("user");
  const data = await parseJson(context, createProductSchema);
  const db = getDb(context.env);
  const workspaceId = access.workspace.id;

  const productCount = await db
    .select({ count: sql<number>`count(*)` })
    .from(products)
    .where(
      and(
        eq(products.workspaceId, workspaceId),
        eq(products.status, "active"),
      ),
    );
  await assertWithinLimit(
    context.env,
    workspaceId,
    "maxProducts",
    Number(productCount[0]?.count ?? 0),
  );

  if (data.categoryId) {
    const category = await db
      .select({ id: categories.id })
      .from(categories)
      .where(
        and(
          eq(categories.id, data.categoryId),
          eq(categories.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!category[0]) throw badRequest("Select a valid category.");
  }

  const duplicate = await db
    .select({ id: products.id })
    .from(products)
    .where(
      and(eq(products.workspaceId, workspaceId), eq(products.sku, data.sku)),
    )
    .limit(1);
  if (duplicate.length > 0) {
    throw conflict("A product with this SKU already exists.");
  }

  if (data.barcode) {
    const barcodeDuplicate = await db
      .select({ id: products.id })
      .from(products)
      .where(
        and(
          eq(products.workspaceId, workspaceId),
          eq(products.barcode, data.barcode),
        ),
      )
      .limit(1);
    if (barcodeDuplicate.length > 0) {
      throw conflict("A product with this barcode already exists.");
    }
  }

  const productId = newId("prd");
  const branchRows = await db
    .select()
    .from(branches)
    .where(eq(branches.workspaceId, workspaceId));
  const defaultBranch =
    branchRows.find((branch) => branch.isDefault) ?? branchRows[0] ?? null;

  const now = Date.now();
  const queries = [
    db.insert(products).values({
      id: productId,
      workspaceId,
      categoryId: data.categoryId ?? null,
      name: data.name,
      sku: data.sku,
      barcode: data.barcode ?? null,
      description: data.description ?? null,
      unit: data.unit,
      costPriceCents: data.costPriceCents,
      sellingPriceCents: data.sellingPriceCents,
      taxRateBasisPoints: data.taxRateBasisPoints ?? null,
      trackInventory: data.trackInventory,
      lowStockThreshold: data.lowStockThreshold,
    }),
  ];

  if (data.trackInventory) {
    for (const branch of branchRows) {
      const opening =
        defaultBranch && branch.id === defaultBranch.id
          ? (data.openingStock ?? 0)
          : 0;
      queries.push(
        db.insert(inventoryLevels).values({
          id: newId("inv"),
          workspaceId,
          branchId: branch.id,
          productId,
          quantity: opening,
          updatedAt: now,
        }) as never,
      );
    }

    if (defaultBranch && (data.openingStock ?? 0) > 0) {
      queries.push(
        db.insert(stockMovements).values({
          id: newId("stk"),
          workspaceId,
          branchId: defaultBranch.id,
          productId,
          type: "opening",
          quantity: data.openingStock ?? 0,
          referenceType: "product",
          referenceId: productId,
          note: "Opening stock",
          createdByUserId: user.id,
        }) as never,
      );
    }
  }

  await db.batch(queries as never);

  await writeAudit(context.env, {
    workspaceId,
    actorUserId: user.id,
    action: "product.created",
    entityType: "product",
    entityId: productId,
    metadata: { name: data.name, sku: data.sku },
  });

  const inserted = await db
    .select()
    .from(products)
    .where(eq(products.id, productId))
    .limit(1);

  return created(context, { product: inserted[0] });
});

productRoutes.patch(
  "/:productId",
  requirePermission("product:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const productId = context.req.param("productId");
    const data = await parseJson(context, updateProductSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const existing = await db
      .select()
      .from(products)
      .where(
        and(
          eq(products.id, productId),
          eq(products.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Product not found.");

    if (data.categoryId) {
      const category = await db
        .select({ id: categories.id })
        .from(categories)
        .where(
          and(
            eq(categories.id, data.categoryId),
            eq(categories.workspaceId, workspaceId),
          ),
        )
        .limit(1);
      if (!category[0]) throw badRequest("Select a valid category.");
    }

    if (data.sku && data.sku !== existing[0].sku) {
      const duplicate = await db
        .select({ id: products.id })
        .from(products)
        .where(
          and(
            eq(products.workspaceId, workspaceId),
            eq(products.sku, data.sku),
            ne(products.id, productId),
          ),
        )
        .limit(1);
      if (duplicate.length > 0) {
        throw conflict("A product with this SKU already exists.");
      }
    }

    if (data.barcode) {
      const duplicate = await db
        .select({ id: products.id })
        .from(products)
        .where(
          and(
            eq(products.workspaceId, workspaceId),
            eq(products.barcode, data.barcode),
            ne(products.id, productId),
          ),
        )
        .limit(1);
      if (duplicate.length > 0) {
        throw conflict("A product with this barcode already exists.");
      }
    }

    await db
      .update(products)
      .set({
        ...(data.name ? { name: data.name } : {}),
        ...(data.sku ? { sku: data.sku } : {}),
        ...(data.barcode !== undefined
          ? { barcode: data.barcode ?? null }
          : {}),
        ...(data.categoryId !== undefined
          ? { categoryId: data.categoryId ?? null }
          : {}),
        ...(data.description !== undefined
          ? { description: data.description ?? null }
          : {}),
        ...(data.unit ? { unit: data.unit } : {}),
        ...(data.costPriceCents !== undefined
          ? { costPriceCents: data.costPriceCents }
          : {}),
        ...(data.sellingPriceCents !== undefined
          ? { sellingPriceCents: data.sellingPriceCents }
          : {}),
        ...(data.taxRateBasisPoints !== undefined
          ? { taxRateBasisPoints: data.taxRateBasisPoints }
          : {}),
        ...(data.trackInventory !== undefined
          ? { trackInventory: data.trackInventory }
          : {}),
        ...(data.lowStockThreshold !== undefined
          ? { lowStockThreshold: data.lowStockThreshold }
          : {}),
        ...(data.status ? { status: data.status } : {}),
        updatedAt: Date.now(),
      })
      .where(eq(products.id, productId));

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "product.updated",
      entityType: "product",
      entityId: productId,
      metadata: data,
    });

    const updated = await db
      .select()
      .from(products)
      .where(eq(products.id, productId))
      .limit(1);

    return ok(context, { product: updated[0] });
  },
);

productRoutes.delete(
  "/:productId",
  requirePermission("product:delete"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const productId = context.req.param("productId");
    const db = getDb(context.env);

    const existing = await db
      .select({ id: products.id })
      .from(products)
      .where(
        and(
          eq(products.id, productId),
          eq(products.workspaceId, access.workspace.id),
        ),
      )
      .limit(1);
    if (!existing[0]) throw notFound("Product not found.");

    await db
      .update(products)
      .set({ status: "inactive", updatedAt: Date.now() })
      .where(eq(products.id, productId));

    await writeAudit(context.env, {
      workspaceId: access.workspace.id,
      actorUserId: user.id,
      action: "product.deactivated",
      entityType: "product",
      entityId: productId,
    });

    return ok(context, { deactivated: true });
  },
);

stockRoutes.get(
  "/",
  requirePermission("inventory:read"),
  async (context) => {
    const access = context.get("workspace");
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;
    const branchId = context.req.query("branchId");
    const search = context.req.query("search")?.trim();

    const branchRows = await db
      .select()
      .from(branches)
      .where(eq(branches.workspaceId, workspaceId));

    const filters = [eq(inventoryLevels.workspaceId, workspaceId)];
    if (branchId) filters.push(eq(inventoryLevels.branchId, branchId));
    if (search) {
      const term = `%${search}%`;
      filters.push(
        or(
          like(products.name, term),
          like(products.sku, term),
          like(products.barcode, term),
        )!,
      );
    }

    const rows = await db
      .select({
        levelId: inventoryLevels.id,
        branchId: inventoryLevels.branchId,
        branchName: branches.name,
        branchCode: branches.code,
        productId: products.id,
        productName: products.name,
        sku: products.sku,
        unit: products.unit,
        quantity: inventoryLevels.quantity,
        lowStockThreshold: products.lowStockThreshold,
        trackInventory: products.trackInventory,
        updatedAt: inventoryLevels.updatedAt,
      })
      .from(inventoryLevels)
      .innerJoin(branches, eq(inventoryLevels.branchId, branches.id))
      .innerJoin(products, eq(inventoryLevels.productId, products.id))
      .where(and(...filters))
      .orderBy(products.name);

    const movements = await db
      .select({
        id: stockMovements.id,
        type: stockMovements.type,
        quantity: stockMovements.quantity,
        note: stockMovements.note,
        createdAt: stockMovements.createdAt,
        branchName: branches.name,
        productName: products.name,
        actorName: users.name,
      })
      .from(stockMovements)
      .innerJoin(branches, eq(stockMovements.branchId, branches.id))
      .innerJoin(products, eq(stockMovements.productId, products.id))
      .leftJoin(users, eq(stockMovements.createdByUserId, users.id))
      .where(eq(stockMovements.workspaceId, workspaceId))
      .orderBy(desc(stockMovements.createdAt))
      .limit(30);

    return ok(context, {
      levels: rows.map((row) => ({
        ...row,
        lowStock: row.trackInventory && row.quantity <= row.lowStockThreshold,
      })),
      movements,
      branches: branchRows,
      summary: {
        lowStockCount: rows.filter(
          (row) => row.trackInventory && row.quantity <= row.lowStockThreshold,
        ).length,
        totalUnits: rows.reduce((sum, row) => sum + row.quantity, 0),
      },
    });
  },
);

stockRoutes.post(
  "/adjust",
  requirePermission("inventory:update"),
  async (context) => {
    const access = context.get("workspace");
    const user = context.get("user");
    const data = await parseJson(context, adjustStockSchema);
    const db = getDb(context.env);
    const workspaceId = access.workspace.id;

    const branch = await db
      .select({ id: branches.id })
      .from(branches)
      .where(
        and(
          eq(branches.id, data.branchId),
          eq(branches.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!branch[0]) throw badRequest("Select a valid branch.");

    const product = await db
      .select()
      .from(products)
      .where(
        and(
          eq(products.id, data.productId),
          eq(products.workspaceId, workspaceId),
        ),
      )
      .limit(1);
    if (!product[0]) throw badRequest("Select a valid product.");
    if (!product[0].trackInventory) {
      throw badRequest("This product does not track inventory.");
    }

    const level = await db
      .select()
      .from(inventoryLevels)
      .where(
        and(
          eq(inventoryLevels.branchId, data.branchId),
          eq(inventoryLevels.productId, data.productId),
        ),
      )
      .limit(1);

    const previous = level[0]?.quantity ?? 0;
    const delta = data.quantity - previous;
    const now = Date.now();

    const levelQueries: unknown[] = [];

    if (level[0]) {
      levelQueries.push(
        db
          .update(inventoryLevels)
          .set({
            quantity: data.quantity,
            updatedAt: now,
          })
          .where(eq(inventoryLevels.id, level[0].id)) as never,
      );
    } else {
      levelQueries.push(
        db.insert(inventoryLevels).values({
          id: newId("inv"),
          workspaceId,
          branchId: data.branchId,
          productId: data.productId,
          quantity: data.quantity,
          updatedAt: now,
        }) as never,
      );
    }

    levelQueries.push(
      db.insert(stockMovements).values({
        id: newId("stk"),
        workspaceId,
        branchId: data.branchId,
        productId: data.productId,
        type: "adjustment",
        quantity: delta,
        referenceType: "adjustment",
        note: data.note ?? "Manual stock adjustment",
        createdByUserId: user.id,
      }) as never,
    );

    await db.batch(levelQueries as never);

    await writeAudit(context.env, {
      workspaceId,
      actorUserId: user.id,
      action: "inventory.adjusted",
      entityType: "inventory_level",
      entityId: level[0]?.id ?? data.productId,
      metadata: {
        branchId: data.branchId,
        productId: data.productId,
        previous,
        next: data.quantity,
      },
    });

    return ok(context, {
      productId: data.productId,
      branchId: data.branchId,
      previous,
      quantity: data.quantity,
    });
  },
);
