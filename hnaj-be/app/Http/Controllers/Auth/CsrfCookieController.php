<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Response;

final class CsrfCookieController
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
