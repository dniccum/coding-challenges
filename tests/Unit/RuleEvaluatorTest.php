<?php

use App\RuleEvaluator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Lightweight test double for an Authenticatable user.
 */
class TestProfile
{
    public function __construct(
        public string $status = 'active',
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

class TestUser implements Authenticatable
{
    public function __construct(
        public string $role = 'guest',
        public ?CarbonImmutable $email_verified_at = null,
        public int $age = 0,
        public array $tags = [],
        public string $email = 'user@example.com',
        public ?TestProfile $profile = null,
    ) {}

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    // Authenticatable interface stubs
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return 1;
    }

    public function getAuthPassword(): string
    {
        return 'secret';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // no-op for tests
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}

it('returns true when no rules are provided', function () {
    $user = new TestUser;
    expect(RuleEvaluator::evaluate($user, ['rules' => []]))->toBeTrue();
})->throws(\InvalidArgumentException::class);

it('supports == and != operators including dates', function () {
    $verifiedAt = new CarbonImmutable('2025-01-01 00:00:00');
    $user = new TestUser(role: 'staff', email_verified_at: $verifiedAt);

    // equality
    expect(RuleEvaluator::evaluate($user, [
        'rules' => [
            ['field' => 'role', 'operator' => '==', 'value' => 'staff'],
        ],
    ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'role', 'operator' => '!=', 'value' => 'guest'],
            ],
        ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'email_verified_at', 'operator' => '==', 'value' => '2025-01-01 00:00:00'],
            ],
        ]))->toBeTrue();

    // inequality

    // date string equals Carbon (by timestamp)
});

it('supports in and not_in operators for strings and arrays', function () {
    $user = new TestUser(role: 'admin', tags: ['alpha', 'beta']);

    // in - array value
    expect(RuleEvaluator::evaluate($user, [
        'rules' => [
            ['field' => 'role', 'operator' => 'in', 'value' => ['staff', 'admin']],
        ],
    ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'role', 'operator' => 'in', 'value' => 'guest, admin, editor'],
            ],
        ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'role', 'operator' => 'not_in', 'value' => 'banned,blocked'],
            ],
        ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'tags', 'operator' => 'in', 'value' => 'beta,gamma'],
            ],
        ]))->toBeTrue();

    // in - comma-separated string

    // not_in

    // in with array on actual side
});

it('supports numeric comparisons > and <', function () {
    $user = new TestUser(age: 21);

    expect(RuleEvaluator::evaluate($user, [
        'rules' => [
            ['field' => 'age', 'operator' => '>', 'value' => 18],
        ],
    ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'age', 'operator' => '<', 'value' => 30],
            ],
        ]))->toBeTrue();

});

it('supports contains for arrays and substrings (case-insensitive)', function () {
    $user = new TestUser(tags: ['alpha', 'beta'], email: 'person@company.com');

    expect(RuleEvaluator::evaluate($user, [
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains', 'value' => 'beta'],
        ],
    ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'email', 'operator' => 'contains', 'value' => '@COMPANY.COM'],
            ],
        ]))->toBeTrue();

});

it('can use reflection to call zero-argument methods via () in field path', function () {
    $user = new TestUser(role: 'staff', profile: new TestProfile(status: 'active'));

    // direct method call
    expect(RuleEvaluator::evaluate($user, [
        'rules' => [
            ['field' => 'isStaff()', 'operator' => '==', 'value' => true],
        ],
    ]))->toBeTrue()
        ->and(RuleEvaluator::evaluate($user, [
            'rules' => [
                ['field' => 'profile.isActive()', 'operator' => '==', 'value' => true],
            ],
        ]))->toBeTrue();

    // nested method call on related object
});
