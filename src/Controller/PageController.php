<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\ApiLinker;


class PageController extends AbstractController {

    private $apiLinker;
  
    public function __construct(ApiLinker $apiLinker) {
        $this->apiLinker = $apiLinker;
     }

    #[Route('/', methods: ['GET'])]
    public function displayConnexionPage() {
        return $this->render('connexion.html.twig', []);
    }

    #[Route('/inscription', methods: ['GET'])]
    public function displayInscriptionPage() {
        return $this->render('inscription.html.twig', []);
    }

    #[Route('/myself', methods: ['GET'], condition: "service('route_checker').checkUser(request)")]
    public function displayUserInfoPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $jsonUser = $this->apiLinker->getData('/myself', $token);
        $user = json_decode($jsonUser);

        return $this->render('selfuser.html.twig', ['user' => $user]);
    }

    #[Route('/users', methods: ['GET'], condition: "service('route_checker').checkAdmin(request)")]
    public function displayUtilisateursPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $response = $this->apiLinker->getData('/user', $token);
        $users = json_decode($response);

        return $this->render('users.html.twig', ['users' => $users, 'role' => 'admin']);
    }
}