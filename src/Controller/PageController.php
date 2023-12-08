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

        $jsonposts= $this->apiLinker->getData("/posts", $token);
        $posts= json_decode($jsonposts);
        shuffle($posts);
        $self= null;
        if ($token != null) {
            $jsoninfos= $this->apiLinker->getData("/myself", $token);
            $self= json_decode($jsoninfos);
        }    

        return $this->render("accueil.html.twig", ["token"=>$token, "posts"=>$posts, "selfuser"=>$self]);
    }

    #[Route('/myself', methods: ['GET'], condition: "service('route_checker').checkUser(request)")]
    public function displayUserInfoPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $self = $this->apiLinker->getData('/myself', $token);
        $selfuser = json_decode($self); 
        $jsonuser= $this->apiLinker->getData("/users/search?username=".$selfuser->username, $token);
        $user= json_decode($jsonuser);
        
        return $this->render('searchuser.html.twig', ['user' => $user, "token" => $token, "myself"=>"My Profile"]);
    }

    #[Route('/users', methods: ['GET'], condition: "service('route_checker').checkAdmin(request)")]
    public function displayUtilisateursPage(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $response = $this->apiLinker->getData('/user', $token);
        $users = json_decode($response);

        return $this->render('accueil.html.twig', ['users' => $users]);
    }

    #[Route('/search', methods: ['GET'])]
    public function getuserbyusername(Request $request): Response
    {
        $username= htmlspecialchars($request->query->get('username'), ENT_QUOTES);
        
        if (empty($username)) {
            return new Response('Le champ username est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');
        $self= null;
        if (!empty($token)) {
            $self= $this->apiLinker->getData('/myself', $token); 
        }
        $selfuser= json_decode($self); 

        $response = $this->apiLinker->getData('/users/search?username=' . $username, $token);
            if ($response) {
                $param= json_decode($response);
                if ($selfuser && $param->username === $selfuser->username) { 
                    return $this->redirect('/myself');
                }
                return $this->render("searchuser.html.twig", ["user" => $param, "token" => $token, "acctualuser"=> $selfuser]);
            } else {
                return new JsonResponse([ 'error' => 'Le champ username est obligatoire.'], Response::HTTP_BAD_REQUEST);

            }


        try {
            
        } catch (\Exception $e) {
            return new Response('Erreur lors de la communication avec l\'API.:'.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/deletepost', methods: ['DELETE'])]
    public function deletePost(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        $data= file_get_contents("php://input");
        $json= json_decode($data);

        $response= $this->apiLinker->deleteData("/posts/".$json->id, $token);
        return new Response($response);
    }

    #[Route('/ban', methods: ['PUT'])]
    public function BanUser(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        $data= file_get_contents("php://input");
        $json= json_decode($data);

        $response= $this->apiLinker->putData("/users/".$json->id, $json, $token);
        return new Response($response);
    }

    #[Route('/unban', methods: ['PUT'])]
    public function UnBanUser(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        $data= file_get_contents("php://input");
        $json= json_decode($data);

        $response= $this->apiLinker->putData("/users/".$json->id, $json, $token);
        return new Response($response);
    }
}