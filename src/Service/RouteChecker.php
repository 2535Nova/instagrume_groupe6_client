<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ApiLinker;

#[AsRoutingConditionService(alias: 'route_checker')]
class RouteChecker {

    private $apiLinker;

    public function __construct(ApiLinker $apiLinker) {
        $this->apiLinker = $apiLinker;
    }

    public function checkUser(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');

        if(empty($token)) {
            return false;
        }

        return true;
    }

    public function checkAdmin(Request $request) {
        $session = $request->getSession();
        $token = $session->get('token-session');
        if(empty($token)) {
            return false;
        }

        $jsonUser = $this->apiLinker->getData('/myself', $token);
        $user = json_decode($jsonUser);
   
        if(!in_array('ROLE_ADMIN', $user->roles)) {
            return false;
        }

        return true;
    }
}