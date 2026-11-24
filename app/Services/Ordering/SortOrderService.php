<?php

namespace App\Services\Ordering;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SortOrderService
{
    public function move(Model $item, string $direction): void
    {
        DB::transaction(function () use ($item, $direction) {
            $rows = $this->siblings($item);
            $index = $rows->search(fn (Model $row) => $row->is($item));
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $rows->has($target)) {
                return;
            }

            [$rows[$index], $rows[$target]] = [$rows[$target], $rows[$index]];
            $this->renumber($rows);
        });
    }

    public function placeAt(Model $item, int $position): void
    {
        DB::transaction(function () use ($item, $position) {
            $rows = $this->siblings($item)->reject(fn (Model $row) => $row->is($item))->values();
            $index = max(0, min($position - 1, $rows->count()));

            $rows->splice($index, 0, [$item]);
            $this->renumber($rows);
        });
    }

    private function siblings(Model $item): Collection
    {
        return $item->sortSiblings()->ordered()->lockForUpdate()->get()->values();
    }

    private function renumber(Collection $rows): void
    {
        $rows->values()->each(function (Model $row, int $index) {
            $row->sort_order = $index + 1;
            $row->save();
        });
    }
}
