<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DispensationException extends RuntimeException
{
    /**
     * @param  array<int|string, mixed>  $details
     */
    private function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public static function insufficientStock(string $message, array $details = []): self
    {
        return new self('INSUFFICIENT_STOCK', 409, $message, $details);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public static function prescriptionExceeded(string $message, array $details = []): self
    {
        return new self('PRESCRIPTION_EXCEEDED', 422, $message, $details);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public static function requiresAuthorization(string $message, array $details = []): self
    {
        return new self('REQUIRES_AUTHORIZATION', 422, $message, $details);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public static function stockChanged(string $message, array $details = []): self
    {
        return new self('STOCK_CHANGED', 409, $message, $details);
    }

    /**
     * @param  array<int|string, mixed>  $details
     */
    public static function duplicateIdempotency(string $message, array $details = []): self
    {
        return new self('DUPLICATE_IDEMPOTENCY', 409, $message, $details);
    }

    /**
     * Errores de negocio esperados: no se reportan como fallos del servidor.
     */
    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'errors' => $this->details,
        ], $this->status);
    }
}
