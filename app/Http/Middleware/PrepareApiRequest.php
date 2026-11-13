<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PrepareApiRequest
{
    public const string REQUEST_ID_ATTRIBUTE = 'request_id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $requestId = (string) Str::ulid();

        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);
        // API failures must stay JSON even when a client omits its Accept header.
        $request->headers->set('Accept', 'application/json');
        Context::add('request_id', $requestId);

        $content = $request->getContent();

        // Laravel treats malformed JSON as empty input, so reject it before it becomes a 422 validation error.
        if ($request->isJson() && $content !== '' && ! json_validate($content)) {
            throw new BadRequestHttpException('The request body contains malformed JSON.');
        }

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
