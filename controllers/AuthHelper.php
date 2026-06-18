<?php
/**
 * AuthHelper
 *
 * Centralizes the rules for where authenticated user state is stored:
 *
 *   - cookie_consent === "accepted"
 *       User identity is persisted in cookies (cross-tab / browser-session safe).
 *       Sessions are still used server-side, but only as a runtime mirror of
 *       the cookie values, so we can survive session expiry without logging
 *       the user out.
 *
 *   - cookie_consent === "rejected"  OR  no cookie_consent cookie
 *       User identity lives ONLY in the PHP session. No auth cookies are set.
 *
 *   - remember_me === true on login
 *       Always set the auth cookies (even if consent was rejected) because
 *       the user explicitly opted in by ticking "Remember me". In that case
 *       the cookie_consent cookie is also forced to "accepted" so the rest
 *       of the app stays consistent.
 */

class AuthHelper
{
    /** Cookie lifetime when the user ticks "remember me". 30 days. */
    public const REMEMBER_COOKIE_LIFETIME = 86400 * 30;

    /**
     * Returns the current cookie-consent state:
     *   "accepted", "rejected", or null (no choice yet).
     */
    public static function consentState(): ?string
    {
        if (!isset($_COOKIE['cookie_consent'])) {
            return null;
        }

        $value = (string) $_COOKIE['cookie_consent'];
        if ($value === 'accepted' || $value === 'rejected') {
            return $value;
        }

        return null;
    }

    /**
     * Should auth state be persisted in cookies right now?
     *
     *   - If user accepted cookies → yes.
     *   - If user ticks "remember me" → yes (and we upgrade consent to accepted).
     *   - Otherwise → no, session only.
     */
    public static function shouldUseCookies(bool $rememberMe = false): bool
    {
        if ($rememberMe) {
            return true;
        }

        return self::consentState() === 'accepted';
    }

    /**
     * Persist auth state to cookies and (optionally) session.
     *
     * @param array $user Row from the users table.
     */
    public static function persistLogin(array $user, bool $rememberMe = false): void
    {
        $lifetime = $rememberMe ? self::REMEMBER_COOKIE_LIFETIME : 0; // 0 = session cookie
        $expires  = $lifetime > 0 ? time() + $lifetime : 0;

        $fullName = self::buildFullName(
            $user['first_name'] ?? '',
            $user['middle_name'] ?? '',
            $user['last_name'] ?? ''
        );
        if ($fullName === '') {
            $fullName = trim((string) ($user['name'] ?? ''));
        }

        // Always keep session values — this lets PHP code keep working
        // without changes everywhere else in the app.
        $_SESSION['user_id']         = (int) $user['id'];
        $_SESSION['user_name']       = $fullName;
        $_SESSION['user_first_name'] = $user['first_name'] ?? '';
        $_SESSION['user_middle_name'] = $user['middle_name'] ?? '';
        $_SESSION['user_last_name']  = $user['last_name'] ?? '';
        $_SESSION['user_email']      = $user['email'] ?? '';
        $_SESSION['user_phone_num']  = $user['phone_num'] ?? null;
        $_SESSION['user_role']       = $user['role'] ?? 'user';

        if (!self::shouldUseCookies($rememberMe)) {
            // User rejected cookies and did not tick remember-me — session only.
            return;
        }

        // If they ticked remember-me but never made a consent choice,
        // treat that as an implicit "accepted" so subsequent pages stay
        // consistent.
        if ($rememberMe && self::consentState() === null) {
            self::setCookieValue('cookie_consent', 'accepted', self::REMEMBER_COOKIE_LIFETIME);
        }

        self::setCookieValue('user_id', (string) (int) $user['id'], $lifetime);
        self::setCookieValue('user_name', $fullName, $lifetime);
        self::setCookieValue('user_first_name', (string) ($user['first_name'] ?? ''), $lifetime);
        self::setCookieValue('user_middle_name', (string) ($user['middle_name'] ?? ''), $lifetime);
        self::setCookieValue('user_last_name', (string) ($user['last_name'] ?? ''), $lifetime);

        if (!empty($user['phone_num'])) {
            self::setCookieValue('user_phone_num', (string) $user['phone_num'], $lifetime);
        } else {
            self::setCookieValue('user_phone_num', '', -3600);
        }

        self::setCookieValue('user_role', (string) ($user['role'] ?? 'user'), $lifetime);
    }

    /**
     * Re-hydrate the session from auth cookies on every page load.
     *
     * This runs only when:
     *   - the user has no PHP session, AND
     *   - they have valid auth cookies (which only exist because either
     *     they accepted cookies or they ticked remember-me).
     *
     * It performs NO database lookup. Callers that need fresh data
     * (e.g. profile.php) should still query the DB.
     */
    public static function hydrateSessionFromCookies(): void
    {
        if (!empty($_SESSION['user_id'])) {
            return; // session already populated
        }

        if (empty($_COOKIE['user_id'])) {
            return; // no auth cookie present
        }

        // Only trust auth cookies when consent was granted or remember-me
        // upgraded the consent value to accepted.
        if (self::consentState() !== 'accepted') {
            return;
        }

        $_SESSION['user_id']          = (int) $_COOKIE['user_id'];
        $_SESSION['user_name']        = $_COOKIE['user_name'] ?? '';
        $_SESSION['user_first_name']  = $_COOKIE['user_first_name'] ?? '';
        $_SESSION['user_middle_name'] = $_COOKIE['user_middle_name'] ?? '';
        $_SESSION['user_last_name']   = $_COOKIE['user_last_name'] ?? '';
        $_SESSION['user_email']       = $_COOKIE['user_email'] ?? '';
        $_SESSION['user_phone_num']   = isset($_COOKIE['user_phone_num']) && $_COOKIE['user_phone_num'] !== ''
            ? $_COOKIE['user_phone_num']
            : null;
        $_SESSION['user_role']        = $_COOKIE['user_role'] ?? 'user';
    }

    /**
     * Clear all auth state (used on logout).
     */
    public static function clearAuthState(): void
    {
        $cookieNames = [
            'user_id',
            'user_name',
            'user_first_name',
            'user_middle_name',
            'user_last_name',
            'user_email',
            'user_phone_num',
            'user_role',
        ];

        foreach ($cookieNames as $name) {
            self::setCookieValue($name, '', -3600);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /* ───────── helpers ───────── */

    private static function buildFullName(?string $first, ?string $middle, ?string $last): string
    {
        $parts = [];
        foreach ([$first, $middle, $last] as $piece) {
            $piece = trim((string) $piece);
            if ($piece !== '') {
                $parts[] = $piece;
            }
        }
        return trim(implode(' ', $parts));
    }

    private static function setCookieValue(string $name, string $value, int $expires): void
    {
        if ($expires < 0) {
            // Negative expiry → delete.
            setcookie($name, '', [
                'expires'  => time() + $expires,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
            // Also clear from the in-memory cookie array for the current request.
            unset($_COOKIE[$name]);
            return;
        }

        setcookie($name, $value, [
            'expires'  => $expires > 0 ? $expires : 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        // Keep $_COOKIE in sync so the rest of this request sees the value.
        if ($expires > 0) {
            $_COOKIE[$name] = $value;
        }
    }
}
