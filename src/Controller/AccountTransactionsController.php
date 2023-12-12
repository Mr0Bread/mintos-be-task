<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;

class AccountTransactionsController extends AbstractController
{
    #[Route('/v1/accounts/{account_id}/transactions', name: 'app_transactions', methods: ['GET'])]
    public function index(
        string $account_id,
        TransactionRepository $transactionRepository,
        #[MapQueryParameter] int $from = 0,
        #[MapQueryParameter] int $to = 20,
    ): JsonResponse
    {
        $transactions = $transactionRepository->findByAccount($account_id, $from, $to);

        return $this->json([
            'transactions' => array_map(function (Transaction $transaction) use ($account_id) {
                if ($transaction->getPayer()->getId() === (int) $account_id) {
                    return [
                        'amount' => $transaction->getAmount() * -1,
                        'payee' => $transaction->getPayee()->getId(),
                        'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                } else {
                    return [
                        'amount' => $transaction->getAmount(),
                        'payer' => $transaction->getPayer()->getId(),
                        'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                }
                
            }, $transactions),
        ]);
    }
}
