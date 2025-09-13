<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Exceptions\Business\StoreException;
use App\Exceptions\Business\ReportException;
use App\Exceptions\Business\PermissionException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
            // Log security-related exceptions
            if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
                \Log::warning('Security Exception: ' . $e->getMessage(), [
                    'user_id' => auth()->id() ?? 'guest',
                    'ip' => request()->ip(),
                    'url' => request()->fullUrl(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Security: Don't expose sensitive information in production
        if (app()->environment('production')) {
            return $this->renderProductionException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle exceptions in production environment
     */
    private function renderProductionException(Request $request, Throwable $e)
    {
        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        // Business logic exceptions
        if ($e instanceof StoreException || $e instanceof ReportException || $e instanceof PermissionException) {
            return $isApiRequest
                ? response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400)
                : redirect()->back()->with('error', $e->getMessage());
        }

        if ($e instanceof ValidationException) {
            return $isApiRequest 
                ? response()->json(['errors' => $e->errors()], 422)
                : redirect()->back()->withErrors($e->errors())->withInput();
        }

        if ($e instanceof ModelNotFoundException) {
            return $isApiRequest
                ? response()->json(['error' => 'Resource not found'], 404)
                : abort(404);
        }

        if ($e instanceof AuthenticationException) {
            return $isApiRequest
                ? response()->json(['error' => 'Unauthorized'], 401)
                : redirect()->guest(route('login'));
        }

        if ($e instanceof AuthorizationException) {
            return $isApiRequest
                ? response()->json(['error' => 'Forbidden'], 403)
                : abort(403);
        }

        if ($e instanceof NotFoundHttpException) {
            return $isApiRequest
                ? response()->json(['error' => 'Endpoint not found'], 404)
                : abort(404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $isApiRequest
                ? response()->json(['error' => 'Method not allowed'], 405)
                : abort(405);
        }

        // Log the error but don't expose details to user
        \Log::error('Unhandled Exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id() ?? 'guest',
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
        ]);

        return $isApiRequest
            ? response()->json(['error' => 'Internal server error'], 500)
            : abort(500);
    }
}
