<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ClientRepository;
use App\Repository\DocumentRepository;
use App\Enum\DocumentStatus;

#[Route('/admin', name: 'app_admin_')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        DocumentRepository $documentRepo,
        ClientRepository $clientRepo,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'countClients' => count($clientRepo->findAllOrdered()),
            'countDocuments' => count($documentRepo->findAll()),
            'countFailed' => $documentRepo->countByStatus(DocumentStatus::FAILED),
            'countProcessing' => $documentRepo->countByStatus(DocumentStatus::PROCESSING),
            'countCompleted' => $documentRepo->countByStatus(DocumentStatus::COMPLETED),
            'failedDocuments' => $documentRepo->findFailed(10),
        ]);
    }
}