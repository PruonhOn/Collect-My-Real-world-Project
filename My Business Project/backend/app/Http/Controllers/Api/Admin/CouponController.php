<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::query()->withCount("redemptions");

        if ($search = $request->query("search")) {
            $query->where("code", "like", "%{$search}%");
        }

        if ($request->query("status") === "active") {
            $query->where("is_active", true)
                ->where(function ($q) {
                    $q->whereNull("expires_at")->orWhereDate("expires_at", ">=", now()->toDateString());
                })
                ->where(function ($q) {
                    $q->whereNull("starts_at")->orWhereDate("starts_at", "<=", now()->toDateString());
                });
        }

        $coupons = $query->orderBy("created_at", "desc")
            ->paginate(min(100, (int) $request->query("per_page", 20)));

        return response()->json($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "code" => ["required", "string", "max:64", "alpha_dash", "unique:coupons,code"],
            "type" => ["required", Rule::in(["percentage", "fixed"])],
            "value" => ["required", "integer", "min:0"],
            "min_amount" => ["nullable", "integer", "min:0"],
            "max_uses" => ["nullable", "integer", "min:0"],
            "starts_at" => ["nullable", "date"],
            "expires_at" => ["nullable", "date", "after_or_equal:starts_at"],
            "is_active" => ["nullable", "boolean"],
            "metadata" => ["nullable", "array"],
        ]);

        $coupon = Coupon::create([
            "code" => strtoupper($validated["code"]),
            "type" => $validated["type"],
            "value" => $validated["value"],
            "min_amount" => $validated["min_amount"] ?? null,
            "max_uses" => $validated["max_uses"] ?? null,
            "uses_count" => 0,
            "starts_at" => $validated["starts_at"] ?? null,
            "expires_at" => $validated["expires_at"] ?? null,
            "is_active" => $validated["is_active"] ?? true,
            "metadata" => $validated["metadata"] ?? null,
        ]);

        $this->audit($request, $coupon, "create_coupon", "Created coupon {$coupon->code}");

        return response()->json(["data" => $coupon], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json(["data" => $coupon->loadCount("redemptions")->load("redemptions")]);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            "code" => ["sometimes", "string", "max:64", "alpha_dash", Rule::unique("coupons", "code")->ignore($coupon->id)],
            "type" => ["sometimes", Rule::in(["percentage", "fixed"])],
            "value" => ["sometimes", "integer", "min:0"],
            "min_amount" => ["nullable", "integer", "min:0"],
            "max_uses" => ["nullable", "integer", "min:0"],
            "starts_at" => ["nullable", "date"],
            "expires_at" => ["nullable", "date", "after_or_equal:starts_at"],
            "is_active" => ["sometimes", "boolean"],
            "metadata" => ["nullable", "array"],
        ]);

        if (isset($validated["code"])) {
            $validated["code"] = strtoupper($validated["code"]);
        }

        $coupon->fill($validated)->save();
        $this->audit($request, $coupon, "update_coupon", "Updated coupon {$coupon->code}");

        return response()->json(["data" => $coupon->fresh()]);
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        $this->audit($request, $coupon, "delete_coupon", "Deleted coupon {$coupon->code}");
        $coupon->delete();

        return response()->json(["message" => "Coupon deleted."]);
    }

    protected function audit(Request $request, Coupon $target, string $action, string $reason): void
    {
        \App\Models\AdminAuditLog::create([
            "admin_id" => $request->user()->id,
            "target_type" => Coupon::class,
            "target_id" => $target->id,
            "action" => $action,
            "reason" => $reason,
            "ip_address" => $request->ip(),
            "user_agent" => $request->userAgent(),
        ]);
    }
}