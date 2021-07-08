<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TestController extends AbstractController
{
    /**
     * @Route(path = "/phpinfo", name = "phpinfo", methods = "GET")
     */
    public function phpInfo(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        ob_start();
        phpinfo();
        $response = ob_get_contents();
        ob_get_clean();

        return new Response($response);
    }
}
