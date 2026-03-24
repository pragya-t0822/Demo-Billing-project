<?php

/**
 * IDE stubs for tymon/jwt-auth facades.
 * The JWTAuth facade has no @method annotations, so Intelephense cannot
 * resolve any methods through it. These stubs declare them explicitly.
 *
 * NOT loaded at runtime — static analysis only.
 */

namespace Tymon\JWTAuth\Facades;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Payload;
use Tymon\JWTAuth\Token;

/**
 * @see \Tymon\JWTAuth\JWTAuth
 * @see \Tymon\JWTAuth\JWT
 */
class JWTAuth extends \Illuminate\Support\Facades\Facade
{
    // ── From JWTAuth (auth-specific) ─────────────────────────────────────

    /**
     * Attempt to authenticate and return a JWT token string, or false.
     *
     * @param  array<string,mixed>  $credentials
     * @return string|bool  Token string on success, false on failure
     * @throws JWTException
     */
    public static function attempt(array $credentials): string|bool { return ''; }

    /**
     * Authenticate via a token already present on the request.
     *
     * @return JWTSubject|bool
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws JWTException
     */
    public static function authenticate(): JWTSubject|bool { return false; }

    /**
     * Alias for authenticate().
     *
     * @return JWTSubject|bool
     */
    public static function toUser(): JWTSubject|bool { return false; }

    /**
     * Get the authenticated user.
     *
     * @return JWTSubject|bool
     */
    public static function user(): JWTSubject|bool { return false; }

    // ── From JWT (token operations) ──────────────────────────────────────

    /**
     * Generate a token for the given subject.
     */
    public static function fromSubject(JWTSubject $subject): string { return ''; }

    /**
     * Alias for fromSubject().
     */
    public static function fromUser(JWTSubject $user): string { return ''; }

    /**
     * Refresh the current token and return the new token string.
     *
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws JWTException
     */
    public static function refresh(bool $forceForever = false, bool $resetClaims = false): string { return ''; }

    /**
     * Invalidate (blacklist) the current token.
     *
     * @throws JWTException
     */
    public static function invalidate(bool $forceForever = false): static { return new static; }

    /**
     * Validate the token or throw.
     *
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws JWTException
     */
    public static function checkOrFail(): Payload { return new Payload(new \Tymon\JWTAuth\Claims\Collection, new \Tymon\JWTAuth\Validators\PayloadValidator); }

    /**
     * Check if the token is valid without throwing.
     *
     * @return bool|Payload
     */
    public static function check(bool $getPayload = false): bool|Payload { return false; }

    /**
     * Get the raw token currently set.
     *
     * @return Token|bool  Token instance, or false if none set
     */
    public static function getToken(): Token|bool { return false; }

    /**
     * Parse the token from the current request.
     *
     * @throws JWTException
     */
    public static function parseToken(): static { return new static; }

    /**
     * Get the validated payload from the current token.
     *
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws JWTException
     */
    public static function getPayload(): Payload { return new Payload(new \Tymon\JWTAuth\Claims\Collection, new \Tymon\JWTAuth\Validators\PayloadValidator); }

    /** Alias for getPayload(). */
    public static function payload(): Payload { return new Payload(new \Tymon\JWTAuth\Claims\Collection, new \Tymon\JWTAuth\Validators\PayloadValidator); }

    /**
     * Set a token on the instance.
     *
     * @param  string|Token  $token
     */
    public static function setToken(string|Token $token): static { return new static; }

    /** Unset the current token. */
    public static function unsetToken(): static { return new static; }

    /** Set the request instance. */
    public static function setRequest(\Illuminate\Http\Request $request): static { return new static; }
}
