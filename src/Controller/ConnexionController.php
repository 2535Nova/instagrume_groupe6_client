<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\JsonConverter;
use App\Service\ApiLinker;

class ConnexionController extends AbstractController{
    private $jsonConverter;
    private $apiLinker;

    public function __construct(ApiLinker $apiLinker, JsonConverter $jsonConverter)
    {
        $this->apiLinker = $apiLinker;
        $this->jsonConverter = $jsonConverter;
    }

    #[Route('/login', methods: ['GET'])]
    public function displayConnexionPage() {
        return $this->render('connexion.html.twig', []);
    }

    #[Route('/inscription', methods: ['GET'])]
    public function displayInscriptionPage() {
        return $this->render('inscription.html.twig', []);
    }

    #[Route('/login', methods: ['POST'])]
    public function connexion(Request $request): Response
    {
        $username = htmlspecialchars($request->request->get('username'), ENT_QUOTES);
        $password = htmlspecialchars($request->request->get('password'), ENT_QUOTES);
        if (empty($username) || empty($password)) {
            return new Response('Les champs username et password sont obligatoires.', Response::HTTP_BAD_REQUEST);
        }

        $data = $this->jsonConverter->encodeToJson(['username' => $username, 'password' => $password]);
        $response = $this->apiLinker->postData('/login', $data, null);
        $responseObject = json_decode($response);
        
        $jsonUser = $this->apiLinker->getData('/users/search?username='.$username, $responseObject->token);
        $user = json_decode($jsonUser); 
        if ($user->ban == true){
            return $this->redirect("/logout");
        }
        if (!empty($responseObject->token)) {
            $session = $request->getSession();
            $session->set('token-session', $responseObject->token);

            return $this->redirect('/');
        } else {            
            return new Response('Réponse API invalide.:'.var_dump($response), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/logout', methods: ['GET'])]
    public function deconnexion(Request $request)
    {
        $session = $request->getSession();
        $session->remove('token-session');
        $session->clear();

        return $this->redirect('/');
    }


    #[Route('/inscription', methods: ['POST'])]
    public function inscription(Request $request): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $confirm_password = $request->request->get("confirm_password");
        if (empty($username) || empty($password) || empty($confirm_password)) {
            return new Response('Les champs username, password et confirmation password sont obligatoires.', Response::HTTP_BAD_REQUEST);
        }
        if ($password === $confirm_password) {
            try {
                $data = $this->jsonConverter->encodeToJson(['username' => $username, 'password' => $password]);
                $this->apiLinker->postData('/inscription', $data, null);
                return $this->redirect("/");
            } catch (\Exception $e) {
                return new Response('Erreur lors de la communication avec l\'API : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return new Response('Les champs password et confirmation password ne sont pas identique.', Response::HTTP_BAD_REQUEST);
        }

    }
}
