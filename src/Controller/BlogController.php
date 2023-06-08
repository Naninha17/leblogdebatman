<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewPublicationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Préfixe de la route du nom de toutes les pages de la partie blog du site
 */

#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{

    /**
     * Contrôleur de la page permettant de créer un nouvel article
     */

    #[Route('/nouvelle-publication/', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function newPublication(Request $request, ManagerRegistry $doctrine): Response
    {

        // Création d'un nouvel article vide
        $newArticle = new Article();

        // Création d'un formulaire de création d'article, lié à l'article vide
        $form = $this->createForm(NewPublicationFormType::class, $newArticle);

        // Liaison des données POST au formulaire
        $form->handleRequest($request);

        // Si le formulaire a bien été envoyé et sans erreurs
        if ($form->isSubmitted() && $form->isValid()) {

            // On termine d'hydrater l'article
            $newArticle
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser());

            // Sauvegarde en base de données grâce au manager des entités
            $em = $doctrine->getManager();
            $em->persist($newArticle);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Article publié avec succès !');

            //TODO: penser à rediriger sur la page qui montre le nouvel article
            return $this->redirectToRoute('main_home');
        }

        return $this->render('blog/new_publication.html.twig', [
            'new_publication_form' => $form->createView(),
        ]);
    }


    /**
     * Contrôleur de la page qui liste tous les articles
     */
    #[Route('/publications/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine): Response
    {

        // Récupération du repository des articles
        $articleRepo = $doctrine->getRepository(Article::class);

        // On demande au repository de nous donner tous les articles qui sont en BDD
        $articles = $articleRepo->findAll();

        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);
    }

}
