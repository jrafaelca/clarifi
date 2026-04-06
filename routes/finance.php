<?php

use App\Http\Controllers\Accounts\AccountController;
use App\Http\Controllers\Budgets\BudgetController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\IngestionBatchController;
use App\Http\Controllers\Chat\IngestionSuggestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Debts\DebtController;
use App\Http\Controllers\Debts\DebtPaymentController;
use App\Http\Controllers\Goals\GoalContributionController;
use App\Http\Controllers\Goals\GoalController;
use App\Http\Controllers\Transactions\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
Route::patch('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
Route::patch('accounts/{account}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');

Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
Route::patch('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');

Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
Route::patch('goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
Route::post('goals/{goal}/contributions', [GoalContributionController::class, 'store'])->name('goals.contributions.store');

Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
Route::post('debts', [DebtController::class, 'store'])->name('debts.store');
Route::patch('debts/{debt}', [DebtController::class, 'update'])->name('debts.update');
Route::post('debts/{debt}/payments', [DebtPaymentController::class, 'store'])->name('debts.payments.store');

Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');
Route::get('chat/ingestion-batches/{ingestionBatch}', [IngestionBatchController::class, 'show'])->name('chat.ingestion-batches.show');
Route::post('chat/ingestion-batches/{ingestionBatch}/approve-all', [IngestionBatchController::class, 'approveAll'])->name('chat.ingestion-batches.approve-all');
Route::patch('chat/ingestion-suggestions/{ingestionSuggestion}', [IngestionSuggestionController::class, 'update'])->name('chat.ingestion-suggestions.update');
