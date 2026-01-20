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
        $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return redirect()->back()->with('error', 'Akses Ditolak: Anda tidak memiliki izin untuk melakukan aksi ini.');
        });

        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->wantsJson() && !$request->inertia()) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            return \Inertia\Inertia::render('Errors/NotFound')
                ->toResponse($request)
                ->setStatusCode(404);
        });
    }
}
