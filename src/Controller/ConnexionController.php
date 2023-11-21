<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\JsonConverter;
use App\Service\ApiLinker;

class ConnexionController extends AbstractController {

    private $jsonConverter;
    private $apiLinker;

    public function __construct(ApiLinker $apiLinker, JsonConverter $jsonConverter) {
        $this->apiLinker = $apiLinker;
        $this->jsonConverter = $jsonConverter;
    }

    #[Route('/login', methods: ['POST'])]
    public function connexion(Request $request) {

        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if (!empty($username) && !empty($password)) {
            $data = $this->jsonConverter->encodeToJson(['username' => $username, 'password' => $password]);
            $response = $this->apiLinker->postData('/login', $data, null);
            $responseObject = json_decode($response);

            $session = $request->getSession();
            $session->set('token-session', $responseObject->token);

            return $this->redirect('/incidents');
        }

        return $this->redirect('/login');
    }

    #[Route('/logout', methods: ['GET'])]
    public function deconnexion(Request $request) {
        $session = $request->getSession();
        $session->remove('token-session');
        $session->clear();

        return $this->redirect('/');
    }
}
