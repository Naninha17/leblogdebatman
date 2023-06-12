<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    /**
     * Contrôleur de la page d'inscription
     */

    #[Route('/creer-un-compte/', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {

            // Récupération valeur du captcha
            $captchaResponse = $request->request->get('g-recaptcha-response',  null);

            // Récupération adresse IP
            $ip = $request->server->get('REMOTE_ADDR');

            // Si le captcha contient "null" ou n'est pas valide, on ajoute une erreur dans le formulaire
            if($captchaResponse == null || $recaptcha->verify($captchaResponse, $ip)){
                $form->addError( new FormError('Veuillez remplir le captcha de sécurité') );

            }

            if($form->isValid()) {

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            // Hydratation de la date d'inscription du nouvel utilisateur
            $user->setRegistrationDate( new \DateTime() );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            // Message flash de succès
            $this->addFlash('success', 'Votre compte à bien été créé avec succès !');

            return $this->redirectToRoute('app_login');
        }
    }


        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
