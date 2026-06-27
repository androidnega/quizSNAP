<?php

namespace App\Support;

/**
 * Plain-language messages for end users. Avoid technical jargon and internal details.
 */
final class UserFriendlyMessages
{
    public const CONNECTION = 'We could not reach the server. Check your connection and try again.';

    public const GENERIC = 'Something went wrong. Please try again.';

    public const NOT_FOUND = 'We could not find what you were looking for.';

    public const ACCESS_DENIED = 'You do not have permission to do that.';

    public const SIGN_IN_REQUIRED = 'Please sign in to continue.';

    public const SESSION_EXPIRED = 'Your session has ended. Please sign in again.';

    public const QUIZ_UNAVAILABLE = 'This quiz is not available right now. Please try again later.';

    public const DOWNLOAD_UNAVAILABLE = 'This file is not available to download right now. Please try again later.';

    public const STAFF_ONLY = 'This area is for staff only.';

    public const ADMIN_ONLY = 'This action is only available to administrators.';

    public const INDEX_NOT_IN_CLASS = 'Your index number is not on the class list yet. Please contact your class rep or lecturer to have your index added — do not message platform support on WhatsApp for this.';

    public const INDEX_NOT_IN_CLASS_SHORT = 'Index not found on the class list.';

    public static function isIndexNotFoundMessage(?string $message): bool
    {
        if ($message === null || $message === '') {
            return false;
        }

        $lower = strtolower($message);

        return str_contains($lower, 'index number not found')
            || str_contains($lower, 'not on the class list')
            || str_contains($lower, 'must belong to a class');
    }

    public static function isIndexNotFoundCode(?string $code): bool
    {
        return $code === 'index_not_found';
    }

    public const PROFILE_ONLY = 'You can only update your own profile.';

    public const PASSWORD_INCORRECT = 'That password is not correct. Please try again.';

    public const SAVED = 'Your changes have been saved.';

    public const UPDATED = 'Updated successfully.';

    /**
     * Return a safe message for API/JSON responses.
     */
    public static function forJson(?string $message = null): string
    {
        if ($message === null || $message === '' || self::looksTechnical($message)) {
            return self::GENERIC;
        }

        return $message;
    }

    /**
     * Hide exception details, SQL, stack traces, and other technical leakage.
     */
    public static function looksTechnical(string $message): bool
    {
        $lower = strtolower($message);

        if (in_array(trim($message), ['error', 'not found', 'failed', 'exception'], true)) {
            return true;
        }

        $patterns = [
            'sqlstate',
            'syntax error',
            'undefined',
            'class "',
            'class \'',
            'stack trace',
            'fatal error',
            'call to',
            'not found in',
            'failed to generate',
            '::',
            'pdoexception',
            'queryexception',
            'illuminate\\',
            'app\\',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
