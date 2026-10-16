<?php

namespace App\Controller\Client;

use App\Entity\Document;
use App\Enum\DocumentNature;
use App\Messenger\Command\CommandBus;
use App\Repository\DocumentRepository;
use App\UseCase\Document\UpdateMetadata\UpdateDocumentMetadataCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/documents/bulk', name: 'app_client_document_bulk_')]
class BulkEditController extends AbstractController
{
    #[Route('', name: 'form', methods: ['GET', 'POST'])]
    public function edit(Request $request, DocumentRepository $repo, CommandBus $commandBus): Response
    {
        $client = $this->getUser()->getClient();

        if ($request->isMethod('POST')) {
            $ids = $request->request->all('document_ids');
            $nature = $request->request->get('nature')
                ? DocumentNature::tryFrom($request->request->get('nature'))
                : null;
            $year = $request->request->get('periodYear') ? (int) $request->request->get('periodYear') : null;
            $month = $request->request->get('periodMonth') ? (int) $request->request->get('periodMonth') : null;

            if (empty($ids)) {
                $this->addFlash('error', 'Aucun document sélectionné.');
                return $this->redirectToRoute('app_client_document_bulk_form');
            }

            if (!$this->isCsrfTokenValid('bulk_edit', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide.');
            }

            foreach ($ids as $id) {
                $commandBus->dispatch(new UpdateDocumentMetadataCommand(
                    documentId: (int) $id,
                    nature: $nature,
                    periodYear: $year,
                    periodMonth: $month,
                ));
            }

            $this->addFlash('success', sprintf('%d document(s) mis à jour.', count($ids)));
            return $this->redirectToRoute('app_client_document_list');
        }

        // GET : afficher uniquement les documents non classés
        $documents = $repo->createQueryBuilder('d')
            ->andWhere('d.client = :client')
            ->andWhere('d.nature IS NULL OR d.periodYear IS NULL')
            ->setParameter('client', $client)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();


        return $this->render('client/document/bulk_edit.html.twig', [
            'documents' => $documents,
            'natures' => DocumentNature::cases(),
        ]);
    }
}