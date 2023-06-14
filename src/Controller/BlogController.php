<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentFormType;
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

    #[Route('/nouvelle-publication/', name: 'publication_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationNew(Request $request, ManagerRegistry $doctrine): Response
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

        return $this->render('blog/publication_new.html.twig', [
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
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si l'utilisateur n'est pas connecté, on appel la vue en bloquant la suite du chargement du contrôleur
        if (!$this->getUser()) {
            return $this->render('blog/publication_view.html.twig', [
                'article' => $article,
            ]);
        }

        $comment = new Comment();

        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $comment
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser())
                ->setArticle($article);

            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            unset($comment);
            unset($form);

            $comment = new Comment();
            $form = $this->createForm(CommentFormType::class, $comment);

            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');

        }

        return $this->render('blog/publication_view.html.twig', [
            'article' => $article,
            'comment_create_form' => $form->createView(),
        ]);
    }

    /**
     * Contrôleur de la page admin servant à supprimer un article via son Id passé dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationDelete(Article $article, ManagerRegistry $doctrine, Request $request): Response
    {

        // Vérif si le token csrf est valide
        if (!$this->isCsrfTokenValid('blog_publication_delete' . $article->getId(), $request->query->get('csrf_token') )){

            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer.');

        }else {

            $em = $doctrine->getManager();
            $em->remove($article);
            $em->flush();

            $this->addFlash('success', 'La publication a été supprimée avec succès !');

        }

        return $this->redirectToRoute('blog_publication_list');

    }

    /**
     * Contrpleur de la page admin servant à modifier un article existant via son id passé dans l'url
     *
     * Accès réservé aux admins (ROLE_ADMIN)
     */


    #[Route('/publication/modifier/{id}', name: 'publication_edit', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        $form = $this->createForm(NewPublicationFormType::class, $article);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $em = $doctrine->getManager();

            $em->flush();

            $this->addFlash('success', 'Publication modifiée avec succès !');

            return $this->redirectToRoute('blog_publication_view', [
                'slug' => $article->getSlug(),

            ]);

        }

        return $this->render('blog/publication_edit.html.twig', [
            'edit_form' => $form->createView(),
        ]);

    }

}
