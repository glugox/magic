<?php

namespace Glugox\Magic\Support\Action;

/**
 * Represents the result of an action.
 * This class can be expanded to include status, messages, data, etc.
 */
class ActionResult
{
    /**
     * Action completed successfully.
     */
    public const string STATUS_SUCCESS = 'success';

    /**
     * Action encountered an error.
     */
    public const string STATUS_ERROR = 'error';

    /**
     * Action did not execute due to unmet conditions.
     */
    public const string STATUS_SKIPPED = 'skipped';

    /**
     * Constructor for ActionResult.
     */
    public function __construct(

        /**
         * Data returned by the action.
         */
        protected mixed $data = null,

        /**
         * Status of the action, e.g., 'success', 'error'.
         */
        protected string $status = self::STATUS_SUCCESS,

        /**
         * Optional message providing additional context about the action result.
         */
        protected ?string $message = null,
    ) {}

    /**
     * Return self from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            data: $data['data'] ?? null,
            status: $data['status'] ?? self::STATUS_SUCCESS,
            message: $data['message'] ?? null,
        );
    }

    /**
     * Return self with success status.
     */
    public static function success(mixed $data = null, ?string $message = null): self
    {
        return new self(data: $data, status: self::STATUS_SUCCESS, message: $message);
    }

    /**
     * Return self with error status.
     */
    public static function error(?string $message = null, mixed $data = null): self
    {
        return new self(data: $data, status: self::STATUS_ERROR, message: $message);
    }

    /**
     * Return self with skipped status.
     */
    public static function skipped(?string $message = null, mixed $data = null): self
    {
        return new self(data: $data, status: self::STATUS_SKIPPED, message: $message);
    }
}
