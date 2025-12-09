<?php

namespace App\Builder;

use App\Exceptions\InvalidSearchValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class NestedSearchBuilder extends Builder
{
    /**
     * Applies JSON-based search rules to the query builder.
     *
     * This function decodes a JSON string or accepts an array of rules,
     * where each rule consists of a dot-notated key referencing a
     * relationship and column, and the value to filter by. The method
     * builds a query applying the provided rules while handling both
     * relationships and direct column matches.
     *
     * Validates the structure of the incoming rules, checks for model
     * relationships, and adds the necessary conditions to the query
     * either as 'and' or 'or', based on the specified boolean operator.
     *
     * <code>
     * <?php
     * Appointment::query()->jsonSearch('{"patient.name": "Alan Roth", "appointment.status": "confirmed", "patient.city": "Dallas"}');
     * </code>
     *
     * @param array|string $rules JSON string or array containing search rules.
     * @param string $boolean Logical operator ('and' or 'or') to combine conditions.
     *
     * @return static
     * @throws InvalidSearchValue|\Throwable if the rule format or column is invalid.
     *
     */
    public function jsonSearch(array|string $rules, string $boolean = 'and'): static
    {
        if (is_string($rules)) {
            $rules = str_replace(["\r\n", "\n", "\r"], '', $rules);
            $decoded = json_decode($rules, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return $this; // ignore invalid input silently
            }
            $rules = $decoded;
        }

        foreach ($rules as $notation => $value) {
            $notationParts = explode('.', $notation);

            throw_if(count($notationParts) < 2, InvalidSearchValue::class, "Invalid JSON search rule: $notation");

            $relation = $notationParts[0];
            $column = $notationParts[1];

            if ($this->model->isRelation($relation)) {
                $existingWhereHas = $this->getQuery()->wheres;
                $this->when(count($existingWhereHas) === 0 || $boolean === 'and', function (Builder $q) use ($relation, $column, $value) {
                    $q->whereHas($relation, function (Builder $q) use ($column, $value) {
                        $q->where($column, $value);
                    });
                })->when(count($existingWhereHas) > 0 && $boolean === 'or', function (Builder $q) use ($relation, $column, $value) {
                    $q->orWhereHas($relation, function (Builder $q) use ($column, $value) {
                        $q->where($column, $value);
                    });
                });
            } else {
                // Since this is not a relation, check for a direct column match
                $availableColumns = \DB::getSchemaBuilder()->getColumnListing($this->model->getTable());
                throw_if(!in_array($column, $availableColumns), InvalidSearchValue::class, "The column '$column' does not exist in the model {$this->model->getTable()}");
                // Perform a direct match
                $this->where($column, $value);
            }
        }

        return $this;
    }
}
