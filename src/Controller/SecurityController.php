<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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
     * @Route(path = "/login", name = "login", methods = "POST")
     *
     * @return  JsonResponse
     * @throws  JWTEncodeFailureException  if the JWT cannot be encoded
     */
    public function login(): JsonResponse
    {
        /** @var  User  $currentUser */
        $currentUser = $this->getUser();

        // It's null only if firewall hasn't been configured.
        if (!$currentUser) {
            $response = new JsonResponse([
                'erreur' => 'Authentification nécessaire'
            ]);
            return $response
                ->setEncodingOptions(JSON_UNESCAPED_UNICODE)
                ->setStatusCode(JsonResponse::HTTP_BAD_REQUEST)
            ;
        }

        $token = $this->jwtEncoder->encode([
            'sub' => $currentUser->getId(),
            'username' => $currentUser->getUserIdentifier(),
            'roles' => $currentUser->getRoles(),
            'exp' => time() + 1800 // 30 minutes expiration
        ]);

        return new JsonResponse(['token' => $token]);
    }
}
