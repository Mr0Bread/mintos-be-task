<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AccountRepository;
use App\Entity\Account;

class ClientAccountsController extends AbstractController
{
    #[Route('/v1/clients/{client_id}/accounts', name: 'app_accounts', methods: ['GET'])]
    public function index(
        string $client_id,
        AccountRepository $accountRepository,
    ): JsonResponse
    {
        $accounts = $accountRepository->findBy(['client' => $client_id]);

        return $this->json([
            'accounts' => array_map(function (Account $account) {
                return [
                    'id' => $account->getId(),
                    'client' => $account->getClient()->getId(),
                    'balance' => $account->getBalance(),
                    'currency' => $account->getCurrency(),
                ];
            }, $accounts),
        ]);
    }
}
