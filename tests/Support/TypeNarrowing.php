<?php

declare(strict_types=1);

if (! function_exists('mustBeString')) {
    /**
     * Narrow a value to a string, failing by name when it is not one.
     *
     * Replaces `\assert(\is_string($x))`, which typically guards a getter whose
     * union includes `null` (`Email::getHtmlBody()`, `Entry::slug()`).
     *
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return string The value, typed as a string.
     */
    function mustBeString(mixed $value, string $subject): string
    {
        if (! is_string($value)) {
            throw new RuntimeException(sprintf(
                'Expected %s to be a string, got %s.',
                $subject,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}

if (! function_exists('mustBeArray')) {
    /**
     * Narrow a value to an array, failing by name when it is not one.
     *
     * @param  mixed  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return array<array-key, mixed> The value, typed as an array.
     */
    function mustBeArray(mixed $value, string $subject): array
    {
        if (! is_array($value)) {
            throw new RuntimeException(sprintf(
                'Expected %s to be an array, got %s.',
                $subject,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}

if (! function_exists('mustExist')) {
    /**
     * Narrow a nullable value to its non-null type, failing by name when absent.
     *
     * Replaces `\assert($x !== null)`. Several call sites paired that with an
     * `expect($x)->not->toBeNull()` immediately above, the expectation was the
     * real check and the assertion existed only to satisfy PHPStan, so both
     * collapse into this one call.
     *
     * @template TValue
     *
     * @param  TValue|null  $value  The value to narrow.
     * @param  string  $subject  What the value is, named for the failure message.
     * @return TValue The value, typed as non-null.
     */
    function mustExist(mixed $value, string $subject): mixed
    {
        if ($value === null) {
            throw new RuntimeException(sprintf('Expected %s to exist, got null.', $subject));
        }

        return $value;
    }
}
