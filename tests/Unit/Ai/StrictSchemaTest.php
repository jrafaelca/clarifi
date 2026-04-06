<?php

use App\Ai\Schemas\ImportExtractionSchema;
use App\Ai\Tools\GetAccountsSummaryTool;
use App\Ai\Tools\GetMonthlyBudgetStatusTool;
use App\Ai\Tools\GetRecentTransactionsTool;
use App\Models\Team;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;

test('finance assistant tool schemas are compatible with strict mode', function () {
    $team = new Team;

    $accountsSchema = (new ObjectSchema(
        (new GetAccountsSummaryTool($team))->schema(new JsonSchemaTypeFactory),
    ))->toSchema();

    $transactionsSchema = (new ObjectSchema(
        (new GetRecentTransactionsTool($team))->schema(new JsonSchemaTypeFactory),
    ))->toSchema();

    $budgetSchema = (new ObjectSchema(
        (new GetMonthlyBudgetStatusTool($team))->schema(new JsonSchemaTypeFactory),
    ))->toSchema();

    expect($accountsSchema['required'])->toBe(['include_inactive'])
        ->and($transactionsSchema['required'])->toBe(['limit', 'month', 'type'])
        ->and($transactionsSchema['properties']['month']['type'])->toBe(['string', 'null'])
        ->and($transactionsSchema['properties']['type']['type'])->toBe(['string', 'null'])
        ->and($budgetSchema['required'])->toBe(['month'])
        ->and($budgetSchema['properties']['month']['type'])->toBe(['string', 'null']);
});

test('finance import schema marks nullable fields as required for strict mode', function () {
    $schema = (new ObjectSchema(
        ImportExtractionSchema::definition(new JsonSchemaTypeFactory),
    ))->toSchema();

    $accountItemSchema = $schema['properties']['accounts']['items'];
    $categoryItemSchema = $schema['properties']['categories']['items'];
    $transactionItemSchema = $schema['properties']['transactions']['items'];

    expect($accountItemSchema['required'])->toBe(['name', 'institution', 'type', 'currency'])
        ->and($accountItemSchema['properties']['institution']['type'])->toBe(['string', 'null'])
        ->and($categoryItemSchema['required'])->toBe(['name', 'type', 'parent_name', 'icon', 'color'])
        ->and($categoryItemSchema['properties']['parent_name']['type'])->toBe(['string', 'null'])
        ->and($transactionItemSchema['required'])->toBe([
            'transaction_date',
            'description',
            'amount',
            'type',
            'status',
            'notes',
            'account_name',
            'account_type',
            'category_name',
            'category_type',
            'source_excerpt',
            'confidence',
        ])
        ->and($transactionItemSchema['properties']['notes']['type'])->toBe(['string', 'null'])
        ->and($transactionItemSchema['properties']['confidence']['type'])->toBe(['number', 'null']);
});
