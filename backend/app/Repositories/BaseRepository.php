<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function findById(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findByIdOrFail(string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    public function all(array $filters = []): Collection
    {
        return $this->model->where($filters)->get();
    }

    /**
     * Generate a sequential number like INV-2026-00001.
     *
     * Uses SELECT FOR UPDATE inside a transaction to prevent duplicate numbers
     * under concurrent requests. Nested transactions are handled via savepoints.
     */
    protected function generateSequentialNumber(string $prefix, string $column, string $table): string
    {
        return \DB::transaction(function () use ($prefix, $column, $table) {
            $year = now()->year;

            $lastRecord = \DB::table($table)
                ->where($column, 'like', "{$prefix}-{$year}-%")
                ->orderByDesc($column)
                ->lockForUpdate()   // prevents concurrent reads of the same last value
                ->value($column);

            $nextSeq = $lastRecord
                ? (int) substr($lastRecord, -5) + 1
                : 1;

            return sprintf('%s-%d-%05d', $prefix, $year, $nextSeq);
        });
    }
}
