<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueAccessToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, IssueAccessToken $issueAccessToken): JsonResponse
    {
        $credentials = $request->validated();
        $result = $issueAccessToken->handle(
            $credentials['email'],
            $credentials['password'],
            $credentials['device_name'],
        );

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => UserResource::make($result['user'])->resolve($request),
            ],
        ]);
    }
}
