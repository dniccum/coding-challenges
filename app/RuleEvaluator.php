<?php

namespace App;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionException;

/**
 * Evaluates a set of JSON-like rules against a given user (or any Authenticatable model).
 *
 * Expected rule set shape:
 * [
 *     'action' => 'submit_form',
 *     'rules' => [
 *         ['field' => 'role', 'operator' => '==', 'value' => 'staff'],
 *         ['field' => 'email_verified_at', 'operator' => '!=', 'value' => null],
 *     ],
 * ]
 */
class RuleEvaluator
{
    /**
     * List of supported operators.
     *
     * @var array<int, string>
     */
    public const array SUPPORTED_OPERATORS = ['==', '!=', 'in', 'not_in', '>', '<', 'contains'];

    /**
     * Evaluate the given rule set against the provided user.
     */
    public static function evaluate(Authenticatable $user, array $ruleSet): bool
    {
        $rules = $ruleSet;

        if (array_key_exists('rules', $ruleSet)) {
            $rules = Arr::get($rules, 'rules', []);
        }

        if (! is_array($rules) || $rules === []) {
            throw new \InvalidArgumentException('Invalid rule set provided.');
        }

        foreach ($rules as $rule) {
            if (! self::evaluateRule($user, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single rule.
     *
     * @param  array{field?: string, operator?: string, value?: mixed}  $rule
     */
    protected static function evaluateRule(Authenticatable $user, array $rule): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? '==';
        $expected = $rule['value'] ?? null;

        if (! is_string($field) || $field === '') {
            return false;
        }

        if (! in_array($operator, self::SUPPORTED_OPERATORS, true)) {
            return false;
        }

        // Resolve the field value. Supports dot-notation and zero-argument method invocations via reflection
        // when a segment ends with "()", e.g. "isStaff()" or "profile.isActive()".
        $actual = self::resolveValue($user, $field);

        return self::compare($actual, $operator, $expected);
    }

    /**
     * Perform the comparison with type-aware logic, including dates and arrays.
     */
    protected static function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        // Normalize date strings against DateTimeInterface values
        if ($actual instanceof DateTimeInterface && is_string($expected)) {
            $expected = self::toCarbon($expected) ?? $expected;
        }

        if ($expected instanceof DateTimeInterface && is_string($actual)) {
            $actual = self::toCarbon($actual) ?? $actual;
        }

        // Normalize numeric strings for inequality comparisons
        $actualNumeric = is_numeric($actual) ? (float) $actual : null;
        $expectedNumeric = is_numeric($expected) ? (float) $expected : null;

        return match ($operator) {
            '==' => self::looselyEquals($actual, $expected),
            '!=' => ! self::looselyEquals($actual, $expected),
            'in' => self::inOperator($actual, $expected),
            'not_in' => ! self::inOperator($actual, $expected),
            '>' => self::greaterThan($actual, $expected, $actualNumeric, $expectedNumeric),
            '<' => self::lessThan($actual, $expected, $actualNumeric, $expectedNumeric),
            'contains' => self::containsOperator($actual, $expected),
            default => false,
        };
    }

    protected static function looselyEquals(mixed $a, mixed $b): bool
    {
        // Handle DateTime comparisons
        if ($a instanceof DateTimeInterface && $b instanceof DateTimeInterface) {
            return $a->getTimestamp() === $b->getTimestamp();
        }

        // Normalize null-like strings
        if ($a === null && is_string($b) && strtolower($b) === 'null') {
            return true;
        }
        if ($b === null && is_string($a) && strtolower($a) === 'null') {
            return true;
        }

        // Strict compare first, then fallback to string compare
        if ($a === $b) {
            return true;
        }

        // Numeric loose equality
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return (string) $a === (string) $b;
    }

    protected static function inOperator(mixed $actual, mixed $expected): bool
    {
        if (is_string($expected)) {
            // allow comma-separated lists in string form
            $expected = array_map('trim', $expected !== '' ? explode(',', $expected) : []);
        }

        if (! is_array($expected)) {
            $expected = [$expected];
        }

        // If actual is an array, check any match
        if (is_array($actual)) {
            foreach ($actual as $item) {
                if (self::inOperator($item, $expected)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($expected as $value) {
            if (self::looselyEquals($actual, $value)) {
                return true;
            }
        }

        return false;
    }

    protected static function greaterThan(mixed $a, mixed $b, ?float $aNum, ?float $bNum): bool
    {
        if ($a instanceof DateTimeInterface && $b instanceof DateTimeInterface) {
//            dd($a->getTimestamp(), $b->getTimestamp(), $a->getTimestamp() > $b->getTimestamp());
            return $a->getTimestamp() > $b->getTimestamp();
        }
        if ($aNum !== null && $bNum !== null) {
            return $aNum > $bNum;
        }

        return (string) $a > (string) $b;
    }

    protected static function lessThan(mixed $a, mixed $b, ?float $aNum, ?float $bNum): bool
    {
        if ($a instanceof DateTimeInterface && $b instanceof DateTimeInterface) {
            return $a->getTimestamp() < $b->getTimestamp();
        }
        if ($aNum !== null && $bNum !== null) {
            return $aNum < $bNum;
        }

        return (string) $a < (string) $b;
    }

    protected static function containsOperator(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            foreach ($actual as $item) {
                if (self::looselyEquals($item, $expected)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($actual)) {
            $needle = is_string($expected) ? $expected : (string) $expected;

            return $needle === '' || mb_stripos($actual, $needle) !== false;
        }

        return false;
    }

    protected static function toCarbon(string $value): ?CarbonImmutable
    {
        try {
            return new CarbonImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve a value from the given target using dot-notation and optional zero-argument
     * method invocation markers (segments ending with "()").
     *
     * Examples:
     * - "role" → property/attribute access via data_get
     * - "isStaff()" → invokes public zero-arg method isStaff() via reflection
     * - "profile.isActive()" → resolves profile then calls isActive() on that object
     */
    protected static function resolveValue(object $target, string $path): mixed
    {
        // Fast path: if there's no method markers and data_get resolves a non-null value, use it.
        if (! str_contains($path, '()')) {
            return data_get($target, $path);
        }

        $segments = explode('.', $path);
        $current = $target;

        foreach ($segments as $segment) {
            $isMethod = str_ends_with($segment, '()');
            $name = $isMethod ? substr($segment, 0, -2) : $segment;

            if (! is_object($current)) {
                return null;
            }

            if ($isMethod) {
                // Invoke a public zero-argument method via reflection
                try {
                    $ref = new ReflectionClass($current);
                    if (! $ref->hasMethod($name)) {
                        return null;
                    }
                    $method = $ref->getMethod($name);
                    if (! $method->isPublic()) {
                        return null; // do not call non-public methods
                    }
                    if ($method->getNumberOfRequiredParameters() > 0) {
                        return null; // only zero-arg methods supported
                    }

                    $current = $method->invoke($current);
                } catch (ReflectionException) {
                    return null;
                }
            } else {
                // Property/attribute access for this segment using data_get on the current object only
                $current = data_get($current, $name);
            }
        }

        return $current;
    }
}
