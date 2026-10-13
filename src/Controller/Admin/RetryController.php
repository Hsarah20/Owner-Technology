<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Enum\DocumentStatus;
use App\Messenger\Command\CommandBus;
use App\UseCase\Document\Retry\RetryAnalysisCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/documents', name: 'app_admin_document_')]
class RetryController extends AbstractController
{
    #[Route('/{id}/retry', name: 'retry', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function retry(Document $document, Request $request, CommandBus $commandBus): Response
    {
        if (!$this->isCsrfTokenValid('retry_' . $document->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }

        if ($document->getStatus() !== DocumentStatus::FAILED) {
            $this->addFlash('error', 'Seul un document en échec peut être relancé.');
            return $this->redirectToRoute('app_admin_document_show', ['id' => $document->getId()]);
        }

        $commandBus->dispatch(new RetryAnalysisCommand(
            documentId: $document->getId(),
            admin: $this->getUser(),
        ));

        $this->addFlash('success', 'Analyse relancée. Le résultat sera disponible dans quelques secondes.');
        return $this->redirectToRoute('app_admin_document_show', ['id' => $document->getId()]);
    }
}