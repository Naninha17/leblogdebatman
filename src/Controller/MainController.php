<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\EditPhotoFormType;
use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{

    /**
     * Contrôleur de la page d'accueil
     */

    #[Route('/', name: 'main_home')]
    public function home(ManagerRegistry $doctrine): Response
    {
        // Récupération du repo des articles
        $articleRepo = $doctrine->getRepository(Article::class);

        // Récupération des 3 derniers articles
        // SELECT * FROM article ORDER BY publication_date DESC LIMIT 3;
        $articles = $articleRepo->findBy([], ['publicationDate' => 'DESC'], $this->getParameter('app.article.number_of_latest_articles_on_home'));


        return $this->render('main/home.html.twig', [
            'articles' => $articles,

        ]);
    }

    /**
     * Contrôleur de la page de profil
     *
     * Accès réservé aux personnes connectés (ROLE_USER)
     */

    #[Route('/mon-profil/', name: 'main_profil')]
    #[IsGranted('ROLE_USER')]
    public function profil(): Response
    {
        return $this->render('main/profil.html.twig');
    }

    /**
     * Contrôleur de la page de modification de la photo de profil
     *
     * Accès réservé aux utilisateurs connectés (ROLE_USER)
     */


    #[Route('/changer-photo-de-profil/', name: 'main_edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine, CacheManager $cacheManager): Response
    {
        $form = $this->createForm(EditPhotoFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            // Récupération du champ photo du formulaire
            $photo = $form->get('photo')->getData();

            // Récupération de l'utilisateur connecté
            $connectedUser = $this->getUser();

            // Récupération de l'emplacement ou on sauvegarde toutes les photos de profil
            $photoLocation = $this->getParameter('app.user.photo.directory');

            // Création d'un nouveau nom pour la nouvelle photo (ex : "user54.png" si utilisateur 54 a envoyé une image png
            $newFileName = 'user' . $connectedUser->getId() . '.' . $photo->guessExtension();

            // Si l'utilisateur possède déjà une photo de profil et si cette photo existe dans le dossier, on la supprime
           if($connectedUser->getPhoto() != null && file_exists( $photoLocation . $connectedUser->getPhoto() )){
               $cacheManager->remove('images/profils/' . $connectedUser->getPhoto() );
               unlink( $photoLocation . $connectedUser->getPhoto() );
           }

           // Actualisation du nom de la photo de profil de l'utilisateur dans la base de données
            $em = $doctrine->getManager();
            $connectedUser->setPhoto( $newFileName );
            $em->flush();


            // Sauvegarde de la photo avec son nouvel emplacement et son nouveau nom
            $photo->move(
                $photoLocation,
                $newFileName,
            );

            // Message flash + redirection

            $this->addFlash('success', 'Photo de profil modifiée avec succès !');
            return $this->redirectToRoute('main_profil');


        }

        return $this->render('main/edit_photo.html.twig', [
            'edit_photo_form' => $form->createView(),
        ]);
    }

}
