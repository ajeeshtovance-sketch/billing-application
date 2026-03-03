<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Billing App API",
 *     version="1.0",
 *     description="Billing Application API - JWT Secured. Use `/api/v1/auth/login` to get a token, then add header: `Authorization: Bearer {token}`"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="API Server (localhost)"
 * )
 *
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server (relative)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token"
 * )
 *
 * @OA\SecurityRequirement(
 *     name="bearerAuth",
 *     scopes={}
 * )
 */
abstract class Controller
{
    //
}
