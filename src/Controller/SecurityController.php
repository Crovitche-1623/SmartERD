<?php

declare(strict_types=1);

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class SecurityController extends AbstractController
{
    private JWTEncoderInterface $jwtEncoder;

    public function __construct(JWTEncoderInterface $encoder)
    {
        $this->jwtEncoder = $encoder;
    }

    /**
     * @Route(
     *     "/login",
     *     name = "login",
     *     methods = "POST"
     * )
     *
     * @return  JsonResponse
     * @throws  JWTEncodeFailureException if the JWT cannot be encoded
     */
    public function login(): JsonResponse
    {
        $currentUser = $this->getUser();

        // It's only null if firewall hasn't been configured.
        if (!$currentUser) {
            $response = new JsonResponse(['erreur' => 'Authentification nécessaire']);
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            $response->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
            return $response;
        }

        $token = $this->jwtEncoder->encode([
            'username' => $currentUser->getUsername(),
            'exp' => time() + 1800 // 30 minutes expiration
        ]);

        return new JsonResponse(['token' => $token]);
    }
}