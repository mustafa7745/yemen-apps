<?php

namespace App\Exceptions;

use Illuminate\Database\CustomException;
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

    protected $dontReport = [
        CustomException::class,
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

    public function render($request, Throwable $exception)
    {
        // Check if the exception is a PhoneOrPasswordException
        if ($exception instanceof CustomException) {
            return response()->json([
                'message' => $exception->getMessage(),        // Message from the exception
                'code' => $exception->getErrorCode(),         // Custom error code
                'errors' => $exception->getErrors(),                               // Custom errors (empty array here)
                'response_code' => $exception->getResponseCode(), // Custom response code
            ], $exception->getResponseCode());  // Use the response code from the exception
        }

        // For other exceptions, use default rendering
        return parent::render($request, $exception);
    }

}
