<?php

namespace App\Controller;

use App\Service\JsonConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Service\ApiLinker;


class PageController extends AbstractController
{

    private $apiLinker;
    private $jsonConverter;
    private $logger;

    public function __construct(ApiLinker $apiLinker, JsonConverter $jsonConverter, LoggerInterface $logger)
    {
        $this->apiLinker = $apiLinker;
        $this->jsonConverter = $jsonConverter;
        $this->logger = $logger;
    }

    #[Route("/", methods: ["GET"])]
    public function displayAccueilPage(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');

        // $this->apiLinker->getData("/users/", $token);

        $jsonposts = $this->apiLinker->getData("/posts", $token);
        $posts = json_decode($jsonposts);
        shuffle($posts);
        $self = null;
        if ($token != null) {
            $jsoninfos = $this->apiLinker->getData("/myself", $token);
            $self = json_decode($jsoninfos);
        }

        return $this->render("accueil.html.twig", ["token" => $token, "posts" => $posts, "selfuser" => $self]);
    }

    #[Route('/myself', methods: ['GET'], condition: "service('route_checker').checkUser(request)")]
    public function displayUserInfoPage(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $self = $this->apiLinker->getData('/myself', $token);
        $selfuser = json_decode($self);
        $jsonuser = $this->apiLinker->getData("/users/search?username=" . $selfuser->username, $token);
        $user = json_decode($jsonuser);

        return $this->render('searchuser.html.twig', ['user' => $user, "token" => $token, "myself" => "My Profile"]);
    }

    #[Route('/users', methods: ['GET'], condition: "service('route_checker').checkAdmin(request)")]
    public function displayUtilisateursPage(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $response = $this->apiLinker->getData('/user', $token);
        $users = json_decode($response);

        return $this->render('accueil.html.twig', ['users' => $users]);
    }

    #[Route('/search', methods: ['GET'])]
    public function getuserbyusername(Request $request): Response
    {
        $username = htmlspecialchars($request->query->get('username'), ENT_QUOTES);

        if (empty($username)) {
            return new Response('Le champ username est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');
        $self = null;
        if (!empty($token)) {
            $self = $this->apiLinker->getData('/myself', $token);
        }
        $selfuser = json_decode($self);

        try {
            $response = $this->apiLinker->getData('/users/search?username=' . $username, $token);
            if ($response) {
                $param = json_decode($response);
                if ($selfuser && $param->username === $selfuser->username) {
                    return $this->redirect('/myself');
                }
                return $this->render("searchuser.html.twig", ["user" => $param, "token" => $token, "acctualuser" => $selfuser]);
            } else {
                return new JsonResponse(['error' => 'Le champ username est obligatoire.'], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return new Response('Erreur lors de la communication avec l\'API.:' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/deletepost', methods: ['DELETE'])]
    public function deletePost(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $data = file_get_contents("php://input");
        $json = json_decode($data);

        $response = $this->apiLinker->deleteData("/posts/" . $json->id, $token);
        return new Response($response);
    }

    #[Route('/deletecomment', methods: ['DELETE'])]
    public function deleteComment(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $data = file_get_contents("php://input");
        $json = json_decode($data);

        $response = $this->apiLinker->deleteData("/commentaire/" . $json->id, $token);
        return new Response($response);
    }

    #[Route('/deletereponse', methods: ['DELETE'])]
    public function deleteReponse(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');
        $data = file_get_contents("php://input");
        $json = json_decode($data);

        $response = $this->apiLinker->deleteData("/reponse/" . $json->id, $token);
        return new Response($response);
    }

    #[Route('/ban', methods: ['POST'])]
    public function BanUser(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');
        
        $targetuser= $this->apiLinker->getData("/users/".$_POST["user_id"], $token);
        $targetuser= json_decode($targetuser);
        
        $data = $this->jsonConverter->encodeToJson(['ban' => true, "roles" => $targetuser->roles]);
        $this->apiLinker->putData('/users/' . $_POST["user_id"], $data, $token);

        return $this->redirect("/");
    }

    #[Route('/unban', methods: ['POST'])]
    public function UnBanUser(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $targetuser= $this->apiLinker->getData("/users/".$_POST["user_id"], $token);
        $targetuser= json_decode($targetuser);
        
        $data = $this->jsonConverter->encodeToJson(['ban' => false, "roles" => $targetuser->roles]);
        $this->apiLinker->putData('/users/' . $_POST["user_id"], $data, $token);

        return $this->redirect("/");
    }


    #[Route('/createpost', methods: ['POST'])]
    public function createpost(Request $request): Response
{
    $description = htmlspecialchars($request->request->get('description'), ENT_QUOTES);
    $file = $request->files->get('file');
    
    if (empty($description) || empty($file)) {
        return new Response('Les champs description ou file sont obligatoires.', Response::HTTP_BAD_REQUEST);
    }
    
    $session = $request->getSession();
    $token = $session->get('token-session');

    $jsonUser = $this->apiLinker->getData('/myself', $token);
    $selfuser = json_decode($jsonUser);

    $jsUser = $this->apiLinker->getData('/users/search?username=' . $selfuser->username, $token);
    $user = json_decode($jsUser);

    $fileContent = file_get_contents($file->getPathname());

    // Obtenez l'extension du fichier
    $fileExtension = $file->getClientOriginalExtension();
    
    // Encodez le contenu du fichier en base64
    $base64FileContent = base64_encode($fileContent);
    
    // Concaténez l'extension, le type MIME et le contenu du fichier encodé en base64
    $base64File = 'data:image/' . $fileExtension . ';base64,' . $base64FileContent;
    

    $data = $this->jsonConverter->encodeToJson([
        'user_id' => $user->id,
        'image' => $base64File,
        'islock' => false,
        'description' => $description
    ]);
    
    $this->apiLinker->postData('/posts', $data, $token);

    return $this->redirect("/");
}

    

    #[Route('/modifcomment', methods: ['POST'])]
    public function modifcomment(Request $request): Response
    {
        $content = htmlspecialchars($_POST["content"], ENT_QUOTES);
        $commentid = htmlspecialchars($_POST["commentid"], ENT_QUOTES);
        if (empty($content)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');

        $data = $this->jsonConverter->encodeToJson(['content' => $content]);
        $this->apiLinker->putData('/commentaire/' . $commentid, $data, $token);

        return $this->redirect("/");
    }

    #[Route('/modifreponse', methods: ['POST'])]
    public function modifreponse(Request $request): Response
    {
        $content = htmlspecialchars($_POST["content"], ENT_QUOTES);
        $reponseid = htmlspecialchars($_POST["reponseid"], ENT_QUOTES);
        if (empty($content)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');

        $data = $this->jsonConverter->encodeToJson(['content' => $content]);
        $this->apiLinker->putData('/reponse/' . $reponseid, $data, $token);

        return $this->redirect("/myself");
    }

    #[Route('/modifprofil', methods: ['POST'])]
    public function modifprofil(Request $request): Response
    {
        $password = htmlspecialchars($request->request->get("password"), ENT_QUOTES);
        $repassword = htmlspecialchars($request->request->get("repassword"), ENT_QUOTES);   
        // Check file size
        if ($_FILES["file"]["size"] > (5 * 1024 * 1024)) {
            return new Response("La taille du fichier est trop grande, la limite de taille du fichier est de 5Mo", Response::HTTP_BAD_REQUEST);
        }  
        // Get session and token
        $session = $request->getSession();
        $token = $session->get('token-session'); 
        // Fetch user data
        $myself = json_decode($this->apiLinker->getData("/myself", $token));
        $user = json_decode($this->apiLinker->getData("/users/search?username=" . $myself->username, $token));
    
        if (!empty($_FILES["file"]["tmp_name"])) {
            $fileContent = file_get_contents($_FILES["file"]["tmp_name"]);
            $fileExtension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            $base64FileContent = base64_encode($fileContent);
            $base64File = 'data:image/' . $fileExtension . ';base64,' . $base64FileContent;
            $this->apiLinker->putData("/users/".$user->id."/avatar", $this->jsonConverter->encodeToJson(["avatar" => $base64File]), $token);
        }
        if (!empty($password) && !empty($repassword)) {
            if ($password === $repassword) {
                $this->apiLinker->putData("/users/".$user->id."/password", $this->jsonConverter->encodeToJson(["password" => $password]), $token);
            }
        }
        return $this->redirect("/myself");
    }
    


    #[Route('/modifpost', methods: ['POST'])]
    public function modifpost(Request $request): Response
    {
        $description = htmlspecialchars($_POST["description"], ENT_QUOTES);
        $islock = htmlspecialchars($_POST["islock"], ENT_QUOTES);
        $username = htmlspecialchars($_POST["userid"], ENT_QUOTES);
        $postid = htmlspecialchars($_POST["postid"], ENT_QUOTES);
        if (empty($description)) {
            return new Response('Le champ description est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');


        $thispost = $this->apiLinker->getData("/posts/" . $postid, $token);
        $thispost = json_decode($thispost);

        $data = $this->jsonConverter->encodeToJson(['description' => $description, "image" => $thispost->image]);
        $this->apiLinker->putData('/posts/' . $postid, $data, $token);

        return $this->redirect("/");
    }

    #[Route('/createcomment', methods: ['POST'])]
    public function createcomment(Request $request): Response
    {
        $commentaire = htmlspecialchars($request->request->get('commentaire'), ENT_QUOTES);
        if (empty($commentaire) || empty($commentaire)) {
            return new Response('Les champs commentaire est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');

        $jsonUser = $this->apiLinker->getData('/myself', $token);
        $selfuser = json_decode($jsonUser);

        $jsUser = $this->apiLinker->getData('/users/search?username=' . $selfuser->username, $token);
        $user = json_decode($jsUser);

        $data = $this->jsonConverter->encodeToJson(['content' => $commentaire, "post_id" => $_POST['post_id'], "user_id" => $user->id]);
        $this->apiLinker->postData('/commentaire', $data, $token);

        return $this->redirect("/myself");
    }

    #[Route('/createreponse', methods: ['POST'])]
    public function createreponse(Request $request): Response
    {
        $reponse = htmlspecialchars($request->request->get('reponse'), ENT_QUOTES);
        if (empty($reponse)) {
            return new Response('Les champs reponse est obligatoire.', Response::HTTP_BAD_REQUEST);
        }
        $session = $request->getSession();
        $token = $session->get('token-session');

        $jsonUser = $this->apiLinker->getData('/myself', $token);
        $selfuser = json_decode($jsonUser);

        $jsUser = $this->apiLinker->getData('/users/search?username=' . $selfuser->username, $token);
        $user = json_decode($jsUser);

        $data = $this->jsonConverter->encodeToJson(['content' => $reponse, "commentaire_id" => $_POST['commentaire_id'], "user_id" => $user->id]);
        $response = $this->apiLinker->postData('/reponse', $data, $token);
        $responseObject = json_decode($response);

        return $this->redirect("/");
    }

    #[Route('/lockpost', methods: ['POST'])]
    public function lockPost(Request $request)
    {
        try {
            // Get the session and token
            $session = $request->getSession();
            $token = $session->get('token-session');
    
            // Validate and sanitize input data
            $postId = $_POST['post_id'] ?? null;
            
    
            if (empty($postId)) {
                throw new \InvalidArgumentException('Invalid input data.');
            }
    
            // Encode data to JSON
            $data = $this->jsonConverter->encodeToJson(['islock' => true]);
    
            // Make an API request to update the post
            $this->apiLinker->putData('/posts/lock/' . $postId, $data, $token);
    
            // Redirect to the home page after successful locking
            return $this->redirect('/');
        } catch (\Exception $e) {
            // Handle errors gracefully
            // You might want to log the error, display a user-friendly message, or redirect to an error page
            return new Response('Error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/unlockpost', methods: ['POST'])]
    public function unlockpost(Request $request)
    {
        $session = $request->getSession();
        $token = $session->get('token-session');

        $data = $this->jsonConverter->encodeToJson(['islock' => false]);
        $this->apiLinker->putData('/posts/lock/' . $_POST["post_id"], $data, $token);

        return $this->redirect("/");
    }
}
