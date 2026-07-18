<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The shared search + pagination pattern for every Phase 2 registry page.
 */
trait SearchesAndPaginates
{
    protected int $registryPageSize = 15;

    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->query('search', ''));
    }

    /**
     * Apply a LIKE search across plain columns or single-level relation
     * columns (e.g. "school.name") when a term is present.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $columns
     */
    protected function applySearch(Builder $query, string $search, array $columns): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $grouped) use ($search, $columns) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $related] = explode('.', $column, 2);

                    $grouped->orWhereHas($relation, function (Builder $sub) use ($related, $search) {
                        $sub->where($related, 'like', "%{$search}%");
                    });
                } else {
                    $grouped->orWhere($column, 'like', "%{$search}%");
                }
            }
        });
    }
}
