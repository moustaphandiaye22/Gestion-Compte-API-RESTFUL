<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Always return JSON for API requests to avoid HTML view errors
        if ($request->expectsJson() || $request->is('api/*') || $request->is('ndiaye/*')) {
            $statusCode = 500; // Default to 500 for server errors

            // Handle specific exception types
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $statusCode = 422;
            } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $statusCode = 403;
            } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $statusCode = 404;
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            ], $statusCode);
        }

        return parent::render($request, $e);
    }






}
