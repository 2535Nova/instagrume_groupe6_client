<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PageNotFoundController extends AbstractController {

    public function pageNotFoundAction() {
        return $this->redirect('/');
    }
}
