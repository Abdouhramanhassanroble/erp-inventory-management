<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RouteController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est connecté (via token JWT côté client)
        // La redirection se fait côté JavaScript si nécessaire
        return $this->render('auth/home.html.twig');
    }

    #[Route('/login', name: 'login_page')]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'register_page')]
    public function register(): Response
    {  
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }
        
        return $this->render('auth/register.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('auth/dashboard.html.twig');
    }
}
