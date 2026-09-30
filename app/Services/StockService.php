<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function apply(
        Material $material,
        string $type,
        float $quantity,
        ?string $date = null,
        ?int $projectId = null,
        ?int $locationId = null,
        ?string $remarks = null,
        ?User $user = null,
        ?Model $reference = null,
    ): MaterialTransaction {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($material, $type, $quantity, $date, $projectId, $locationId, $remarks, $user, $reference) {
            $stock = MaterialStock::firstOrCreate(['material_id' => $material->id]);
            $stock = MaterialStock::whereKey($stock->id)->lockForUpdate()->first();

            $column = match ($type) {
                'opening' => 'opening_qty',
                'received' => 'received_qty',
                'issued' => 'issued_qty',
                'used' => 'used_qty',
                'returned' => 'returned_qty',
                default => throw ValidationException::withMessages(['type' => 'Unknown stock movement.']),
            };

            $stock->{$column} = (float) $stock->{$column} + $quantity;
            $current = (float) $stock->opening_qty
                + (float) $stock->received_qty
                + (float) $stock->returned_qty
                - (float) $stock->issued_qty
                - (float) $stock->used_qty;

            if ($current < -0.001) {
                throw ValidationException::withMessages([
                    'quantity' => 'That quantity would take '.$material->name.' below zero.',
                ]);
            }

            $current = round(max($current, 0), 2);
            $stock->current_qty = $current;
            $stock->save();
            $material->update(['current_stock' => $current]);

            return MaterialTransaction::create([
                'material_id' => $material->id,
                'project_id' => $projectId,
                'project_location_id' => $locationId,
                'type' => $type,
                'quantity' => $quantity,
                'transacted_on' => $date ?: now()->toDateString(),
                'remarks' => $remarks,
                'user_id' => $user?->id,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }
}
