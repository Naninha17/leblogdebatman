<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewPublicationFormType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

            // Rediriger sur la page qui montre le nouvel article
            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $newArticle->getSlug(),
            ]);
        }

        return $this->render('blog/new_publication.html.twig', [
            'new_publication_form' => $form->createView(),
        ]);
    }


    /**
     * Contrôleur de la page qui liste tous les articles
     */
    #[Route('/publications/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {
        $requestedPage = $request->query->getInt('page', 1);

        // Si le numéro de page demandé dans l'url est inférieur à 1, erreur 404
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        // Récupération du manager des entités
        $em = $doctrine->getManager();

        // Création d'une requête qui servira au paginator pour récupérer les articles de la page courante
        $query = $em->createQuery('SELECT a FROM App\Entity\Article a ORDER BY a.publicationDate DESC');

        // On stocke dans $articles les 10 articles de la page demandée dans l'URL
        $articles = $paginator->paginate(
            $query,     // Requête de selection des articles en BDD
            $requestedPage, // Numéro de la page dont on veux les articles
            10      // Nombre d'articles par page
        );

        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Contrôleur de la page permettant de voir un article en détail
     */

    #[Route('/publication/{slug}/', name: 'publication_view')]
    public function publicationView(Article $article): Response
    {

        return $this->render('blog/publication_view.html.twig', [
            'article' => $article,
        ]);
    }

}
