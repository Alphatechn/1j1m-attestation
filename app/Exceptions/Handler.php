<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
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
            //
        });
    }

    /**
     * Sur un long formulaire public, une session expirée (419) affichait la
     * page blanche par défaut de Laravel, donnant l'impression que le site
     * était cassé. On renvoie plutôt l'utilisateur sur la page précédente,
     * avec ses réponses texte conservées (les fichiers ne peuvent pas être
     * repréremplis pour des raisons de sécurité navigateur).
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            return redirect()
                ->back(fallback: route('public.participants.request'))
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Votre session a expiré (formulaire resté ouvert trop longtemps, ou lien ouvert depuis une application comme WhatsApp). Vos réponses ont été conservées : merci de resélectionner vos captures d\'écran et de soumettre à nouveau.');
        }

        return parent::render($request, $e);
    }
}
