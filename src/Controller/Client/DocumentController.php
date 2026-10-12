<?php

namespace App\Controller\Client;

use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/documents', name: 'app_client_document_')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, DocumentRepository $repo): Response
    {
        $client = $this->getUser()->getClient();
        $page = max(1, (int) $request->query->get('page', 1));

        $pagination = $repo->paginateForClient($client, $page);

        return $this->render('client/document/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}