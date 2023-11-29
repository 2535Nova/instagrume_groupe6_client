<?php

namespace App\Controller;
use App\Service\JsonConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use App\Service\ApiLinker;


class PageController extends AbstractController {

    private $apiLinker;
    private $jsonConverter;
  
    public function __construct(ApiLinker $apiLinker, JsonConverter $jsonConverter) {
        $this->apiLinker = $apiLinker;
        $this->jsonConverter= $jsonConverter;
    }

    #[Route("/", methods: ["GET"])]
    public function displayAccueilPage(Request $request){
        $session = $request->getSession();
        $token = $session->get('token-session');
        return $this->render("accueil.html.twig", ["token"=>$token]);
    }

    #[Route('/myself', methods: ['GET'], condition: "service('route_checker').checkUser(request)")]
    public function displayUserInfoPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $jsonUser = $this->apiLinker->getData('/myself', $token);
        $user = json_decode($jsonUser); 

        return $this->render('accueil.html.twig', ['user' => $user, "token" => $token]);
    }

    #[Route('/users', methods: ['GET'], condition: "service('route_checker').checkAdmin(request)")]
    public function displayUtilisateursPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $response = $this->apiLinker->getData('/user', $token);
        $users = json_decode($response);

        return $this->render('users.html.twig', ['users' => $users, 'role' => 'admin']);
    }

    #[Route('/search', methods: ['GET'])]
    public function getuserbyusername(Request $request): Response
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            return new Response('Le champ username est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $token = $this->getAuthenticationToken($request);
        try {
            $response = $this->apiLinker->getData('/users/search?username=' . $username, $token);
    
            if ($response) {
                return new Response($this->jsonConverter->encodeToJson($response));
            } else {
                return new JsonResponse(['error' => 'Le champ username est obligatoire.'], Response::HTTP_BAD_REQUEST);

            }
        } catch (\Exception $e) {
            return new Response('Erreur lors de la communication avec l\'API.:'.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getAuthenticationToken(Request $request): string
    {
        $session = $request->getSession();
        return $session->get('token-session', '');
    }
}