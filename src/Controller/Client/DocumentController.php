<?php

namespace App\Controller\Client;

use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Document;
use App\Security\Voter\DocumentVoter;
use App\Service\Storage\DocumentStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/app/documents', name: 'app_client_document_')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, DocumentRepository $repo): Response
    {
        $client = $this->getUser()->getClient();
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(5, min(100, (int) $request->query->get('limit', 20)));

        $pagination = $repo->paginateForClient($client, $page,$limit);

        return $this->render('client/document/list.html.twig', [
            'pagination' => $pagination,
            'limit' => $limit,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Document $document): Response
    {
        // Le Voter garantit qu'un client ne peut voir que ses documents
        $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

        return $this->render('client/document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(Document $document, DocumentStorage $storage): StreamedResponse
    {
        $this->denyAccessUnlessGranted(DocumentVoter::DOWNLOAD, $document);

        if (!$storage->exists($document->getStoredPath())) {
            throw $this->createNotFoundException('Fichier introuvable sur le serveur.');
        }

        $stream = $storage->readStream($document->getStoredPath());

        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        });

        $response->headers->set('Content-Type', $document->getMimeType());
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $document->getOriginalName())
        );
        $response->headers->set('Content-Length', (string) $document->getSize());

        return $response;
    }
}