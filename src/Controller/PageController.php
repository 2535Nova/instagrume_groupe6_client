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

       // $this->apiLinker->getData("/users/", $token);

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

        try {
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

    #[Route('/deletecomment', methods: ['DELETE'])]
    public function deleteComment(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        $data= file_get_contents("php://input");
        $json= json_decode($data);

        $response= $this->apiLinker->deleteData("/commentaire/".$json->id, $token);
        return new Response($response);
    }

    #[Route('/deletereponse', methods: ['DELETE'])]
    public function deleteReponse(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        $data= file_get_contents("php://input");
        $json= json_decode($data);

        $response= $this->apiLinker->deleteData("/reponse/".$json->id, $token);
        return new Response($response);
    }
    #[Route('/ban', methods: ['POST'])]
    public function BanUser(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        
        $data= $this->jsonConverter->encodeToJson(['ban' => true, "password" => $_POST["password"], "avatar"=> $_POST["avatar"], "username"=> $_POST["username"]]);
        $this->apiLinker->putData('/users/'.$_POST["user_id"], $data, $token);

        return $this->redirect("/");
    }

    #[Route('/unban', methods: ['PUT'])]
    public function UnBanUser(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');
        
        $data= $this->jsonConverter->encodeToJson(['ban' => false, "password" => $_POST["password"], "avatar"=> $_POST["avatar"]]);
        $this->apiLinker->putData('/users/'.$_POST["user_id"], $data, $token);

        return $this->redirect("/");
    }

    #[Route('/createpost', methods: ['POST'])]
    public function createpost(Request $request): Response
    {
        $description= htmlspecialchars($_POST["description"], ENT_QUOTES);
        if (empty($description) || empty($_FILES["file"]["name"])) {
            return new Response('Les champs description ou file sont obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        if ($_FILES["file"]["size"] > (5 * 1024 * 1024)) {
            return new Response("La taille du fichier est trop grande, la limite de taille du fichier est de 5Mo", Response::HTTP_BAD_REQUEST);
        }

        $session= $request->getSession();
        $token= $session->get('token-session');
        
        $jsonUser= $this->apiLinker->getData('/myself', $token);
        $selfuser= json_decode($jsonUser); 

        $jsUser= $this->apiLinker->getData('/users/search?username='.$selfuser->username, $token);
        $user= json_decode($jsUser); 

        $fileContent= file_get_contents($_FILES["file"]["tmp_name"]);
        $base64File= base64_encode($fileContent);

        $data= $this->jsonConverter->encodeToJson(['description' => $description, "islock"=> false, "user_id" => $user->id, "image"=> $base64File]);
        $this->apiLinker->postData('/posts', $data, $token);

        return $this->redirect("/");
    }

    #[Route('/modifcomment', methods: ['POST'])]
    public function modifcomment(Request $request): Response
    {
        $content= htmlspecialchars($_POST["content"], ENT_QUOTES);
        $commentid= htmlspecialchars($_POST["commentid"], ENT_QUOTES);
        if (empty($content)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session= $request->getSession();
        $token= $session->get('token-session');

        $data= $this->jsonConverter->encodeToJson(['content' => $content]);
        $this->apiLinker->putData('/commentaire/'.$commentid, $data, $token);

        return $this->redirect("/");
    }

    #[Route('/modifreponse', methods: ['POST'])]
    public function modifreponse(Request $request): Response
    {
        $content= htmlspecialchars($_POST["content"], ENT_QUOTES);
        $reponseid= htmlspecialchars($_POST["reponseid"], ENT_QUOTES);
        if (empty($content)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session= $request->getSession();
        $token= $session->get('token-session');

        $data= $this->jsonConverter->encodeToJson(['content' => $content]);
        $this->apiLinker->putData('/reponse/'.$reponseid, $data, $token);

        return $this->redirect("/myself");
    }

    #[Route('/modifpost', methods: ['POST'])]
    public function modifpost(Request $request): Response
    {
        $description= htmlspecialchars($_POST["description"], ENT_QUOTES);
        $islock= htmlspecialchars($_POST["islock"], ENT_QUOTES);
        $username= htmlspecialchars($_POST["userid"], ENT_QUOTES);
        $postid= htmlspecialchars($_POST["postid"], ENT_QUOTES);
        if (empty($description)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session= $request->getSession();
        $token= $session->get('token-session');

        $user= $this->apiLinker->getData("/search?username=".$username, $token);
        $user= json_decode($user);

        $thispost= $this->apiLinker->getData("/posts/".$postid, $token);
        $thispost= json_decode($thispost);

        $data= $this->jsonConverter->encodeToJson(['description' => $description, "islock"=> $islock, "user_id" => $user->id, "image"=> $thispost->image]);
        $this->apiLinker->putData('/posts/'.$postid, $data, $token);

        return $this->redirect("/");
    }
    
    #[Route('/createcomment', methods: ['POST'])]
    public function createcomment(Request $request): Response
    {
        $commentaire= htmlspecialchars($request->request->get('commentaire'), ENT_QUOTES);
        if (empty($commentaire) || empty($commentaire)) {
            return new Response('Les champs commentaire est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session= $request->getSession();
        $token= $session->get('token-session');
        
        $jsonUser= $this->apiLinker->getData('/myself', $token);
        $selfuser= json_decode($jsonUser); 

        $jsUser= $this->apiLinker->getData('/users/search?username='.$selfuser->username, $token);
        $user= json_decode($jsUser); 

        $data= $this->jsonConverter->encodeToJson(['content' => $commentaire, "post_id" => $_POST['post_id'], "user_id" => $user->id]);
        $this->apiLinker->postData('/commentaire', $data, $token);

        return $this->redirect("/myself");
    }

    #[Route('/createreponse', methods: ['POST'])]
    public function createreponse(Request $request): Response
    {
        $reponse= htmlspecialchars($request->request->get('reponse'), ENT_QUOTES);
        if (empty($reponse)) {
            return new Response('Les champs reponse est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session= $request->getSession();
        $token= $session->get('token-session');
        
        $jsonUser= $this->apiLinker->getData('/myself', $token);
        $selfuser= json_decode($jsonUser); 

        $jsUser= $this->apiLinker->getData('/users/search?username='.$selfuser->username, $token);
        $user= json_decode($jsUser); 

        $data= $this->jsonConverter->encodeToJson(['content' => $reponse, "commentaire_id" => $_POST['commentaire_id'], "user_id" => $user->id]);
        $response= $this->apiLinker->postData('/reponse', $data, $token);
        $responseObject= json_decode($response);

        return $this->redirect("/");
    }

    #[Route('/lockpost', methods: ['POST'])]
    public function lockpost(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');

        $data= $this->jsonConverter->encodeToJson(['islock' => true, "description" => $_POST['description'], "image" => $_POST["image"]]);
        $this->apiLinker->putData('/posts/'.$_POST["post_id"], $data, $token);

        return $this->redirect("/");
    }

    #[Route('/unlockpost', methods: ['POST'])]
    public function unlockpost(Request $request){
        $session= $request->getSession();
        $token= $session->get('token-session');

        $data= $this->jsonConverter->encodeToJson(['islock' => false, "description" => $_POST['description'], "image" => $_POST["image"]]);
        $this->apiLinker->putData('/posts/'.$_POST["post_id"], $data, $token);

        return $this->redirect("/");
    }

}