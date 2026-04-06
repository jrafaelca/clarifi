<?php

namespace App\Application\Accounts;

use App\Application\Transactions\RecalculateAccountBalance;
use App\Domain\Accounts\Enums\AccountType;
use App\Domain\Accounts\Models\Account;
use App\Models\Team;
use Illuminate\Support\Str;

class CreateAccount
{
    public function __construct(
        protected RecalculateAccountBalance $recalculateAccountBalance,
    ) {}

    /**
     * Create or reuse an account for the given workspace.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data, bool $matchExisting = false): Account
    {
        if ($matchExisting) {
            $existing = $this->findExisting(
                $team,
                (string) $data['name'],
                isset($data['institution']) ? (string) $data['institution'] : null,
            );

            if ($existing !== null) {
                return $existing;
            }
        }

        $account = Account::create([
            'team_id' => $team->id,
            'name' => $data['name'],
            'type' => $data['type'] instanceof AccountType
                ? $data['type']
                : AccountType::from((string) $data['type']),
            'currency' => $data['currency'] ?? $team->currency,
            'initial_balance' => $data['initial_balance'] ?? 0,
            'current_balance' => $data['current_balance'] ?? $data['initial_balance'] ?? 0,
            'institution' => $data['institution'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->recalculateAccountBalance->handle($account);

        return $account->fresh();
    }

    protected function findExisting(Team $team, string $name, ?string $institution = null): ?Account
    {
        return Account::query()
            ->forTeam($team)
            ->get()
            ->first(function (Account $account) use ($name, $institution) {
                return $this->normalize($account->name) === $this->normalize($name)
                    && $this->normalize($account->institution) === $this->normalize($institution);
            });
    }

    protected function normalize(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();
    }
}
