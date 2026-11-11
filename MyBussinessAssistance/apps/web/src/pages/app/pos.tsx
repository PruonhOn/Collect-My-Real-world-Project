import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ChangeEvent,
  type FormEvent,
} from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  ArrowRight,
  Calculator,
  Plus,
  Printer,
  Search,
  ShoppingCart,
  Trash2,
  X,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Dialog } from "@/components/ui/dialog";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import {
  createSale,
  fetchBranches,
  fetchCustomers,
  fetchInventory,
  fetchProducts,
  fetchWorkspaceSettings,
} from "@/lib/business-api";
import { formatCents, formatNumber, inputToCents } from "@/lib/money";
import { formatDate } from "@/lib/format";
import { useBusinessContext } from "@/lib/use-business";

type CartItem = {
  productId: string;
  productName: string;
  sku: string | null;
  unit: string;
  unitPriceCents: number;
  taxRateBasisPoints: number;
  trackInventory: boolean;
  quantity: number;
};

type PaymentMethod =
  | "cash"
  | "khqr"
  | "aba"
  | "card"
  | "bank_transfer"
  | "credit"
  | "other";

const PAYMENT_METHODS: PaymentMethod[] = [
  "cash",
  "khqr",
  "aba",
  "card",
  "bank_transfer",
  "credit",
  "other",
];

function taxCentsFor(
  subtotalCents: number,
  rateBasisPoints: number,
  taxEnabled: boolean,
) {
  if (!taxEnabled) return 0;
  return Math.max(0, Math.round((subtotalCents * rateBasisPoints) / 10_000));
}

type Receipt = {
  number: string;
  totalCents: number;
  paidCents: number;
  balanceCents: number;
  paymentMethod: string;
  branchName: string;
  currency: string;
  items: CartItem[];
  createdAt: number;
};

export function PosPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const queryClient = useQueryClient();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const canCreate = can("sale:create");

  const [branchId, setBranchId] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>("cash");
  const [customerSearch, setCustomerSearch] = useState("");
  const [customerId, setCustomerId] = useState<string | null>(null);
  const [customerName, setCustomerName] = useState("");
  const [productSearch, setProductSearch] = useState("");
  const [tendered, setTendered] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [receipt, setReceipt] = useState<Receipt | null>(null);
  const [creating, setCreating] = useState(false);
  const [stockMap, setStockMap] = useState<Map<string, number>>(new Map());
  const [cart, setCart] = useState<CartItem[]>([]);
  const productInputRef = useRef<HTMLInputElement>(null);

  const branchesQuery = useQuery({
    queryKey: ["pos-branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    enabled: Boolean(workspaceId),
  });
  const branches = branchesQuery.data?.branches ?? [];

  const refreshStock = useCallback(async () => {
    if (!branchId) {
      setStockMap(new Map());
      return;
    }
    try {
      const data = await fetchInventory(workspaceId, { branchId });
      const map = new Map<string, number>();
      for (const level of data.levels) {
        map.set(level.productId, level.quantity);
      }
      setStockMap(map);
    } catch {
      setStockMap(new Map());
    }
  }, [workspaceId, branchId]);

  useEffect(() => {
    if (!branchId) {
      if (branches[0]) setBranchId(branches[0].id);
      return;
    }
    void refreshStock();
  }, [refreshStock, branchId, branches]);

  useEffect(() => {
    if (!branchId && branches[0]) setBranchId(branches[0].id);
  }, [branches]);

  const settingsQuery = useQuery({
    queryKey: ["pos-settings", workspaceId],
    queryFn: () => fetchWorkspaceSettings(workspaceId),
    enabled: Boolean(workspaceId),
    retry: 1,
  });
  const taxEnabled = settingsQuery.data?.settings?.taxEnabled ?? false;
  const taxRateBasisPoints =
    settingsQuery.data?.settings?.taxRateBasisPoints ?? 0;

  const productsQuery = useQuery({
    queryKey: ["pos-products", workspaceId, productSearch],
    queryFn: () => fetchProducts(workspaceId, { search: productSearch }),
    enabled: Boolean(workspaceId) && productSearch.length >= 1,
  });
  const products = productsQuery.data?.products ?? [];

  const customersQuery = useQuery({
    queryKey: ["pos-customers", workspaceId, customerSearch],
    queryFn: () =>
      fetchCustomers(workspaceId, customerSearch || undefined),
    enabled: Boolean(workspaceId) && customerSearch.length >= 1,
  });
  const customerResults = customersQuery.data?.customers ?? [];

  const subtotalCents = useMemo(
    () =>
      cart.reduce((sum, item) => sum + item.unitPriceCents * item.quantity, 0),
    [cart],
  );

  const taxCents = useMemo(
    () =>
      cart.reduce((sum, item) => {
        const lineSubtotal = item.unitPriceCents * item.quantity;
        const rate =
          item.taxRateBasisPoints ??
          (taxEnabled ? taxRateBasisPoints : 0);
        return sum + taxCentsFor(lineSubtotal, rate, taxEnabled);
      }, 0),
    [cart, taxEnabled, taxRateBasisPoints],
  );

  const totalCents = subtotalCents + taxCents;
  const tenderedCents = inputToCents(tendered);
  const changeCents = Math.max(0, tenderedCents - totalCents);

  const createSaleMutation = useMutation({
    mutationFn: () =>
      createSale(workspaceId, {
        branchId,
        customerId: customerId || undefined,
        customerName: customerName.trim() || undefined,
        paymentMethod,
        paidCents: Math.min(tenderedCents, totalCents),
        discountCents: 0,
        items: cart.map((item) => ({
          productId: item.productId,
          quantity: item.quantity,
          unitPriceCents: item.unitPriceCents,
        })),
      }),
    onSuccess: (data) => {
      const branch = branches.find((b) => b.id === branchId);
      void refreshStock();
      void queryClient.invalidateQueries({ queryKey: ["pos-products", workspaceId] });
      void queryClient.invalidateQueries({ queryKey: ["products", workspaceId] });
      void queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] });
      void queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] });
      void queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] });
      setReceipt({
        number: data.sale.number,
        totalCents: data.sale.totalCents,
        paidCents: data.sale.paidCents,
        balanceCents: data.sale.balanceCents,
        paymentMethod,
        branchName: branch?.name ?? "",
        currency: workspace.defaultCurrency,
        items: [...cart],
        createdAt: Date.now(),
      });
      setCreating(false);
    },
    onError: (cause: unknown) => {
      setError(
        cause instanceof Error ? cause.message : "Unable to complete the sale.",
      );
      setCreating(false);
    },
  });

  const resetSale = () => {
    setReceipt(null);
    setCart([]);
    setError(null);
    setTendered("");
    setProductSearch("");
    setCustomerId(null);
    setCustomerName("");
  };

  const isOutOfStock = (product: {
    trackInventory?: boolean;
    id: string;
  }): boolean => {
    if (!product.trackInventory) return false;
    const available = stockMap.get(product.id) ?? 0;
    return available <= 0;
  };

  const cartQty = (productId: string) => {
    const item = cart.find((c) => c.productId === productId);
    return item ? item.quantity : 0;
  };

  const canAdd = (product: {
    trackInventory?: boolean;
    id: string;
  }): boolean => {
    if (!product.trackInventory) return true;
    const available = stockMap.get(product.id) ?? 0;
    return available > cartQty(product.id);
  };

  const addToCart = (product: {
    id: string;
    name: string;
    sku: string | null;
    unit: string;
    sellingPriceCents: number;
    taxRateBasisPoints: number | null;
    trackInventory?: boolean;
  }) => {
    if (!canAdd(product)) return;
    setError(null);
    setCart((current) => {
      const existing = current.find((c) => c.productId === product.id);
      if (existing) {
        return current.map((c) =>
          c.productId === product.id
            ? { ...c, quantity: c.quantity + 1 }
            : c,
        );
      }
      return [
        ...current,
        {
          productId: product.id,
          productName: product.name,
          sku: product.sku,
          unit: product.unit,
          unitPriceCents: product.sellingPriceCents,
          taxRateBasisPoints: product.taxRateBasisPoints ?? 0,
          trackInventory: product.trackInventory ?? false,
          quantity: 1,
        },
      ];
    });
    if (productInputRef.current) {
      productInputRef.current.value = "";
      productInputRef.current.focus();
    }
  };

  const removeFromCart = (productId: string) => {
    setCart((current) => current.filter((c) => c.productId !== productId));
  };

  const changeQty = (productId: string, delta: number) => {
    setCart((current) => {
      const item = current.find((c) => c.productId === productId);
      if (!item) return current;
      const next = item.quantity + delta;
      if (next <= 0) {
        return current.filter((c) => c.productId !== productId);
      }
      if (item.trackInventory && (stockMap.get(productId) ?? 0) < next) {
        return current;
      }
      return current.map((c) =>
        c.productId === productId ? { ...c, quantity: next } : c,
      );
    });
  };

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    if (!canCreate) {
      setError("You do not have permission to create sales.");
      return;
    }
    if (!branchId) {
      setError("Select a branch.");
      return;
    }
    if (cart.length === 0) {
      setError("Add at least one product to the cart.");
      return;
    }
    setCreating(true);
    void createSaleMutation.mutate();
  };

  const printReceipt = () => {
    window.print();
    resetSale();
  };

  const disablePay =
    !canCreate || !branchId || cart.length === 0 || creating;

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.pos.title")}
        description={t("business.pos.subtitle")}
      />

      {error ? (
        <Card>
          <CardContent className="p-4 text-sm text-destructive">{error}</CardContent>
        </Card>
      ) : null}

      <div className="grid gap-4 lg:grid-cols-12">
        <div className="lg:col-span-7 space-y-4">
          <Card>
            <CardHeader className="flex items-center gap-2 p-4">
              <Search className="h-4 w-4" />
              <CardTitle className="text-sm flex-1">
                {t("business.pos.searchProducts")}
              </CardTitle>
              <X
                className="h-4 w-4 cursor-pointer hover:text-muted-foreground"
                onClick={() => {
                  setProductSearch("");
                  if (productInputRef.current)
                    productInputRef.current.focus();
                }}
              />
            </CardHeader>
            <CardContent className="p-4">
              <Input
                ref={productInputRef}
                placeholder={t("business.pos.searchProducts")}
                value={productSearch}
                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                  setProductSearch(event.target.value)
                }
                className="w-full"
              />
              {products.length === 0 && productSearch.length >= 1 ? (
                <EmptyState icon={Search} title={t("business.pos.noProducts")} />
              ) : (
                <div className="space-y-2 mt-3">
                  {products.map((product) => {
                    const outOfStock = isOutOfStock(product);
                    const disabled = outOfStock || !canAdd(product);
                    return (
                      <Card
                        key={product.id}
                        className="p-3 border transition-hover hover:bg-accent/50"
                      >
                        <CardContent className="flex items-center justify-between gap-3">
                          <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-1.5">
                              <span className="font-medium truncate">
                                {product.name}
                              </span>
                              {outOfStock ? (
                                <Badge variant="destructive" className="text-[10px]">
                                  {t("business.pos.lowStock")}
                                </Badge>
                              ) : null}
                            </div>
                            {product.sku ? (
                              <p className="text-xs text-muted-foreground truncate">
                                {product.sku}
                              </p>
                            ) : null}
                            <p className="text-xs text-muted-foreground">
                              {formatNumber(
                                stockMap.get(product.id) ?? 0,
                                locale,
                              )}{" "}
                              {product.unit}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold">
                              {formatCents(
                                product.sellingPriceCents,
                                workspace.defaultCurrency,
                                locale,
                              )}
                            </p>
                            <Button
                              size="sm"
                              onClick={() => addToCart(product)}
                              disabled={disabled}
                            >
                              {cartQty(product.id) > 0 ? (
                                <span className="flex items-center gap-2">
                                  <ShoppingCart className="h-3 w-3" />
                                  {cartQty(product.id)}
                                </span>
                              ) : (
                                <Plus className="h-3 w-3" />
                              )}
                            </Button>
                          </div>
                        </CardContent>
                      </Card>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>

          {customerResults.length > 0 && customerSearch.length >= 1 ? (
            <Card>
              <CardContent className="p-3 space-y-2">
                <CardTitle className="text-sm">
                  {t("business.pos.customer")}
                </CardTitle>
                <div className="space-y-1">
                  {customerResults.map((customer) => (
                    <Button
                      key={customer.id}
                      variant="outline"
                      size="sm"
                      className="w-full text-left text-sm px-3 py-2"
                      onClick={() => {
                        setCustomerId(customer.id);
                        setCustomerName(customer.name);
                        setCustomerSearch("");
                      }}
                    >
                      <div className="font-medium">{customer.name}</div>
                      {customer.phone ? (
                        <span className="text-xs text-muted-foreground">
                          {customer.phone}
                        </span>
                      ) : null}
                    </Button>
                  ))}
                </div>
              </CardContent>
            </Card>
          ) : null}
        </div>

        <div className="lg:col-span-5 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-sm flex items-center gap-2">
                <ShoppingCart className="h-3 w-3" />
                {t("business.pos.cart")}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-4">
              {cart.length === 0 ? (
                <EmptyState
                  icon={ShoppingCart}
                  title={t("business.pos.emptyCart")}
                />
              ) : (
                <>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="text-left w-1/3">
                          {t("business.pos.product")}
                        </TableHead>
                        <TableHead className="text-center">
                          {t("business.pos.qty")}
                        </TableHead>
                        <TableHead className="text-right">Price</TableHead>
                        <TableHead className="text-right">
                          {t("business.pos.total")}
                        </TableHead>
                        <TableHead className="text-center w-10" />
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {cart.map((item) => (
                        <TableRow key={item.productId}>
                          <TableCell className="text-sm">
                            <div className="font-medium truncate">
                              {item.productName}
                            </div>
                            <p className="text-xs text-muted-foreground truncate">
                              {item.sku ?? ""}
                            </p>
                          </TableCell>
                          <TableCell className="text-center">
                            <div className="flex items-center justify-center gap-1">
                              <Button
                                size="icon"
                                variant="outline"
                                onClick={() => changeQty(item.productId, -1)}
                                disabled={item.quantity <= 1}
                              >
                                <X className="h-3 w-3" />
                              </Button>
                              <span className="w-5 text-center text-sm font-medium">
                                {item.quantity}
                              </span>
                              <Button
                                size="icon"
                                variant="outline"
                                onClick={() => changeQty(item.productId, 1)}
                                disabled={!canAdd({ id: item.productId })}
                              >
                                <Plus className="h-3 w-3" />
                              </Button>
                            </div>
                          </TableCell>
                          <TableCell className="text-right text-sm font-medium">
                            {formatCents(
                              item.unitPriceCents,
                              workspace.defaultCurrency,
                              locale,
                            )}
                          </TableCell>
                          <TableCell className="text-right text-sm font-medium">
                            {formatCents(
                              item.unitPriceCents * item.quantity,
                              workspace.defaultCurrency,
                              locale,
                            )}
                          </TableCell>
                          <TableCell className="text-center">
                            <Button
                              size="icon"
                              variant="destructive"
                              onClick={() => removeFromCart(item.productId)}
                            >
                              <Trash2 className="h-3 w-3" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>

                  <div className="mt-4 space-y-2 border-t pt-4">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">
                        {t("business.pos.subtotal")}
                      </span>
                      <span className="font-medium">
                        {formatCents(
                          subtotalCents,
                          workspace.defaultCurrency,
                          locale,
                        )}
                      </span>
                    </div>
                    {taxCents > 0 ? (
                      <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">
                          {t("business.pos.tax")}
                        </span>
                        <span className="font-medium">
                          {formatCents(
                            taxCents,
                            workspace.defaultCurrency,
                            locale,
                          )}
                        </span>
                      </div>
                    ) : null}
                    <div className="flex justify-between text-base font-bold">
                      <span>{t("business.pos.totalDue")}</span>
                      <span>
                        {formatCents(
                          totalCents,
                          workspace.defaultCurrency,
                          locale,
                        )}
                      </span>
                    </div>
                  </div>

                  <div className="mt-5 space-y-3">
                    <div>
                      <label className="text-xs font-medium text-muted-foreground block mb-1">
                        {t("business.pos.paymentMethod")}
                      </label>
                      <Select
                        value={paymentMethod}
                        onChange={(event) =>
                          setPaymentMethod(event.target.value as PaymentMethod)
                        }
                        className="w-full"
                      >
                        {PAYMENT_METHODS.map((method) => (
                          <option key={method} value={method}>
                            {t(`business.payments.${method}`)}
                          </option>
                        ))}
                      </Select>
                    </div>

                    {paymentMethod === "cash" ? (
                      <div>
                        <label className="text-xs font-medium text-muted-foreground block mb-1">
                          {t("business.pos.tendered")}
                        </label>
                        <Input
                          type="number"
                          step="0.01"
                          value={tendered}
                          onChange={(event: ChangeEvent<HTMLInputElement>) =>
                            setTendered(event.target.value)
                          }
                          placeholder="0.00"
                          className="w-full"
                        />
                        <p className="text-xs text-muted-foreground mt-1">
                          {t("business.pos.change")}:{" "}
                          {formatCents(
                            changeCents,
                            workspace.defaultCurrency,
                            locale,
                          )}
                        </p>
                      </div>
                    ) : null}

                    <Button
                      onClick={handleSubmit}
                      disabled={disablePay}
                      className="w-full py-3"
                    >
                      {creating ? (
                        <Calculator className="h-4 w-4 animate-spin" />
                      ) : (
                        <ArrowRight className="h-4 w-4" />
                      )}
                      <span className="ml-2">
                        {t("business.pos.pay")}
                      </span>
                    </Button>
                  </div>
                </>
              )}
            </CardContent>
          </Card>

          {customerId ? (
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                setCustomerId(null);
                setCustomerName("");
                setCustomerSearch("");
              }}
              className="w-full flex items-center justify-center gap-1.5 text-sm"
            >
              <X className="h-3 w-3" />
              <span>{t("business.pos.walkIn")}</span>
            </Button>
          ) : null}
        </div>
      </div>

      {receipt ? (
        <Dialog
          open
          onClose={resetSale}
          title={t("business.pos.receiptTitle")}
          size="sm"
        >
          <div className="p-6 max-w-xs print:hidden">
            <div className="border border-secondary p-4 rounded-lg bg-background">
              <div className="text-center">
                <h3 className="font-bold text-center">
                  {t("business.pos.receiptBusiness")}
                </h3>
                <p className="text-xs text-muted-foreground">
                  {workspace.name}
                </p>
                <p className="text-xs text-muted-foreground">
                  {formatDate(receipt.createdAt, locale)}
                </p>
              </div>
              <hr className="my-3" />
              <div className="text-sm space-y-2">
                {receipt.items.map((item) => (
                  <div
                    key={item.productId}
                    className="flex justify-between text-sm"
                  >
                    <span className="truncate">
                      {item.productName}
                    </span>
                    <span className="text-right">
                      {formatNumber(item.quantity, locale)} ×{" "}
                      {formatCents(
                        item.unitPriceCents,
                        receipt.currency,
                        locale,
                      )}
                    </span>
                    <span className="text-right font-medium">
                      {formatCents(
                        item.unitPriceCents * item.quantity,
                        receipt.currency,
                        locale,
                      )}
                    </span>
                  </div>
                ))}
              </div>
              <hr className="my-3" />
              <div className="flex justify-between text-sm font-semibold mb-2">
                <span>{t("business.pos.totalDue")}</span>
                <span>
                  {formatCents(
                    receipt.totalCents,
                    receipt.currency,
                    locale,
                  )}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span>{t("business.pos.paymentMethod")}</span>
                <span>
                  {t(`business.payments.${receipt.paymentMethod}`)}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span>{t("business.pos.tendered")}</span>
                <span>
                  {formatCents(receipt.paidCents, receipt.currency, locale)}
                </span>
              </div>
              <div className="flex justify-between text-sm font-medium">
                <span>{t("business.pos.change")}</span>
                <span>
                  {formatCents(
                    Math.max(0, receipt.paidCents - receipt.totalCents),
                    receipt.currency,
                    locale,
                  )}
                </span>
              </div>
              <div className="mt-4 text-center text-xs text-muted-foreground">
                {t("business.pos.receiptThankYou")}
              </div>
            </div>
            <div className="mt-4 flex gap-2">
              <Button
                variant="outline"
                onClick={printReceipt}
                className="flex-1"
              >
                <Printer className="h-4 w-4" />
                Print receipt
              </Button>
              <Button
                variant="outline"
                onClick={resetSale}
                className="flex-1"
              >
                <ArrowRight className="h-4 w-4" />
                New sale
              </Button>
            </div>
          </div>
        </Dialog>
      ) : null}
    </div>
  );
}
