<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Document;
use App\Enum\DocumentStatus;
use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use App\Security\Voter\DocumentVoter;
use App\Service\Storage\DocumentStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/documents', name: 'app_admin_document_')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        DocumentRepository $documentRepo,
        ClientRepository $clientRepo,
    ): Response {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(5, min(100, (int) $request->query->get('limit', 10)));

        $status = null;
        if ($request->query->get('status')) {
            $status = DocumentStatus::tryFrom($request->query->get('status'));
        }

        $client = null;
        if ($request->query->get('client')) {
            $client = $clientRepo->find((int) $request->query->get('client'));
        }

        $pagination = $documentRepo->paginateForAdmin($page, $limit, $status, $client);

        return $this->render('admin/document/list.html.twig', [
            'pagination' => $pagination,
            'limit' => $limit,
            'clients' => $clientRepo->findAllOrdered(),
            'statuses' => DocumentStatus::cases(),
            'currentStatus' => $status,
            'currentClient' => $client,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Document $document): Response
    {
        // Admin : pas de restriction Voter sur VIEW, mais on garde le check
        // pour cohérence (le Voter autorise tout pour ROLE_ADMIN).
        $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

        return $this->render('admin/document/show.html.twig', [
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