<?php

namespace App\Controller\Client;

use App\Messenger\Command\CommandBus;
use App\UseCase\Document\Upload\UploadDocumentsCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/documents', name: 'app_client_document_')]
class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, CommandBus $commandBus): Response
    {
        if ($request->isMethod('POST')) {
            /** @var UploadedFile[] $files */
            $files = $request->files->get('files', []);

            if (empty($files)) {
                $this->addFlash('error', 'Aucun fichier reçu.');
                return $this->redirectToRoute('app_client_document_upload');
            }

            $user = $this->getUser();
            $client = $user->getClient();

            if (!$client) {
                throw $this->createAccessDeniedException('Aucun dossier client associé.');
            }

            try {
                $documents = $commandBus->dispatch(new UploadDocumentsCommand(
                    client: $client,
                    uploader: $user,
                    files: $files,
                ));

                $this->addFlash('success', sprintf(
                    '%d fichier(s) déposé(s) avec succès.',
                    count($documents)
                ));

                return $this->redirectToRoute('app_client_document_list');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Erreur lors du dépôt : ' . $e->getMessage());
            }
        }

        return $this->render('client/document/upload.html.twig');
    }
}