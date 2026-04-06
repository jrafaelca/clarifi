<?php

namespace App\Ai\Schemas;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

class ImportExtractionSchema
{
    /**
     * Get the structured extraction schema for financial imports.
     *
     * @return array<string, Type>
     */
    public static function definition(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'accounts' => $schema->array()->items(
                $schema->object([
                    'name' => $schema->string()->required(),
                    'institution' => $schema->string()->nullable(),
                    'type' => $schema->string()->enum(['cash', 'bank', 'savings', 'credit_card'])->required(),
                    'currency' => $schema->string()->nullable(),
                ]),
            )->required(),
            'categories' => $schema->array()->items(
                $schema->object([
                    'name' => $schema->string()->required(),
                    'type' => $schema->string()->enum(['income', 'expense'])->required(),
                    'parent_name' => $schema->string()->nullable(),
                    'icon' => $schema->string()->nullable(),
                    'color' => $schema->string()->nullable(),
                ]),
            )->required(),
            'transactions' => $schema->array()->items(
                $schema->object([
                    'transaction_date' => $schema->string()->format('date')->required(),
                    'description' => $schema->string()->required(),
                    'amount' => $schema->string()->required(),
                    'type' => $schema->string()->enum(['income', 'expense'])->required(),
                    'status' => $schema->string()->enum(['confirmed', 'pending'])->required(),
                    'notes' => $schema->string()->nullable(),
                    'account_name' => $schema->string()->required(),
                    'account_type' => $schema->string()->enum(['cash', 'bank', 'savings', 'credit_card'])->nullable(),
                    'category_name' => $schema->string()->nullable(),
                    'category_type' => $schema->string()->enum(['income', 'expense'])->nullable(),
                    'source_excerpt' => $schema->string()->nullable(),
                    'confidence' => $schema->number()->nullable(),
                ]),
            )->required(),
        ];
    }
}
