<?php

namespace App\Controller;

use App\Repository\StoreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreLocatorController extends AbstractController
{
    #[Route('/stores', name: 'store_locator')]
    public function index(): Response
    {
        return $this->render('store/index.html.twig');
    }

    #[Route('/api/stores', name: 'api_stores', methods: ['GET'])]
    public function apiStores(StoreRepository $repository): JsonResponse
    {
        $stores = array_map(fn($s) => $s->toArray(), $repository->findAll());
        return $this->json($stores);
    }
}