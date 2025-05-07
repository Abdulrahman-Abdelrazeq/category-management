<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use App\Traits\Response;

class Handler extends ExceptionHandler
{
    use Response;
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

    public function render($request, Throwable $exception)
    {
        // Handle model not found exceptions (e.g., when a Category is not found)
        if ($exception instanceof ModelNotFoundException) {
            $modelClass = $exception->getModel(); // Full class name, e.g. App\Models\Category
            $modelName = class_basename($modelClass); // Extracts "Category"
            $modelName = Str::headline(Str::snake($modelName));

            return $this->sendRes(false, "$modelName not found.", null, null, 404);
        }

        return parent::render($request, $exception);
    }
}
