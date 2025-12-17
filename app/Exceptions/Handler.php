<?php

namespace App\Exceptions;

use App\Exceptions\Business\PermissionException;
use App\Exceptions\Business\ReportException;
use App\Exceptions\Business\StoreException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
                \Log::warning('Security Exception: '.$e->getMessage(), [
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
        // Handle CSRF token mismatch (419 errors) - redirect to login
        if ($e instanceof TokenMismatchException) {
            return $this->handleTokenMismatch($request);
        }

        // Security: Don't expose sensitive information in production
        if (app()->environment('production')) {
            return $this->renderProductionException($request, $e);
        }

        // Handle token mismatch in non-production environments too
        return parent::render($request, $e);
    }

    /**
     * Handle CSRF token mismatch exception (419 errors)
     */
    protected function handleTokenMismatch(Request $request)
    {
        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        if ($isApiRequest) {
            return response()->json([
                'error' => 'Session expired. Please refresh and try again.',
                'session_expired' => true
            ], 419);
        }

        // Clear the session and redirect to login
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('error', 'Your session has expired due to inactivity. Please log in again.');
    }

    /**
     * Handle exceptions in production environment
     */
    private function renderProductionException(Request $request, Throwable $e)
    {
        $isApiRequest = $request->expectsJson() || $request->is('api/*');

        // Handle CSRF token mismatch (419 errors) - redirect to login
        if ($e instanceof TokenMismatchException) {
            return $this->handleTokenMismatch($request);
        }

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
                : parent::render($request, $e);
        }

        if ($e instanceof AuthenticationException) {
            return $isApiRequest
                ? response()->json(['error' => 'Unauthorized'], 401)
                : redirect()->guest(route('login'));
        }

        if ($e instanceof AuthorizationException) {
            return $isApiRequest
                ? response()->json(['error' => 'Forbidden'], 403)
                : parent::render($request, $e);
        }

        if ($e instanceof NotFoundHttpException) {
            return $isApiRequest
                ? response()->json(['error' => 'Endpoint not found'], 404)
                : parent::render($request, $e);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $isApiRequest
                ? response()->json(['error' => 'Method not allowed'], 405)
                : parent::render($request, $e);
        }

        // Generic HTTP exceptions (e.g. abort(403), 429, etc.)
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            $defaultMessage = match ($statusCode) {
                400 => 'Bad request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not found',
                405 => 'Method not allowed',
                429 => 'Too many requests',
                default => 'HTTP error',
            };

            $message = $e->getMessage() ?: $defaultMessage;

            return $isApiRequest
                ? response()->json(['error' => $message], $statusCode)
                : parent::render($request, $e);
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
            : parent::render($request, $e);
    }
}
