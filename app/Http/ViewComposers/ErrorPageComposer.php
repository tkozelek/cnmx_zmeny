<?php

namespace App\Http\ViewComposers;

use Illuminate\Support\Facades\Vite;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Plain values for the error pages; no auth or database here - they must render when those broke. */
class ErrorPageComposer
{
    public function compose(View $view): void
    {
        $previousUrl = url()->previous();
        $exception = $view->getData()['exception'] ?? null;

        $view->with([
            'previousUrl' => $previousUrl,
            'hasPreviousPage' => $previousUrl !== url()->current() && rtrim($previousUrl, '/') !== rtrim(url('/'), '/'),
            'retryAfter' => $this->retryAfter($exception),
            'reason' => $this->reason($exception),
            'fontUrls' => $this->fontUrls(),
        ]);
    }

    /**
     * The app's own Inter, as on every other page. A broken or missing build must not take the
     * error page down with it - then it falls back to the system font.
     *
     * @return array{latin: string, latinExt: string}|null
     */
    private function fontUrls(): ?array
    {
        try {
            return [
                'latin' => Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2'),
                'latinExt' => Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-ext-wght-normal.woff2'),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function retryAfter(?Throwable $exception): ?string
    {
        $seconds = $exception instanceof HttpExceptionInterface ? (int) ($exception->getHeaders()['Retry-After'] ?? 0) : 0;

        return match (true) {
            $seconds <= 0 => null,
            $seconds >= 60 => ceil($seconds / 60).' min',
            default => $seconds.' s',
        };
    }

    /**
     * Several aborts pass a Slovak sentence (abort(403, '...'), Response::deny('...')). Laravel's
     * own default is English and means "no reason given", so it is not shown to the user.
     */
    private function reason(?Throwable $exception): ?string
    {
        $message = trim($exception?->getMessage() ?? '');

        return $message === '' || $message === 'This action is unauthorized.' ? null : $message;
    }
}
