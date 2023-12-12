<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use App\Service\TransferService;
use App\Model\TransferArgument;
use App\Repository\AccountRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransferController extends AbstractController
{
    #[Route('/v1/transfer', name: 'app_transfer')]
    public function index(
        TransferService $transferService,
        AccountRepository $accountRepository,
        #[MapQueryParameter] int $payerId,
        #[MapQueryParameter] int $payeeId,
        #[MapQueryParameter] int $amount,
        #[MapQueryParameter] bool $tolerateStaleRates = false
    ): JsonResponse
    {
        $payer = $accountRepository->find($payerId);
        $payee = $accountRepository->find($payeeId);

        if (!$payer) {
            throw $this->createNotFoundException('Payer account not found');
        }

        if (!$payee) {
            throw $this->createNotFoundException('Payee account not found');
        }

        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than 0');
        }

        $transferArgument = new TransferArgument(
            $payer,
            $payee,
            $amount,
            $tolerateStaleRates
        );
        $transferResult = [];

        try {
            $transferResult = $transferService->transfer($transferArgument);
        } catch (\Exception $e) {
            throw new HttpException(400, $e->getMessage(), $e);
        }

        return $this->json([
            'message' => 'Transfer successful',
            'deducted_amount' => $transferResult['amount'],
            'amount_to_receive' => $amount,
            'base_currency' => $payer->getCurrency(),
            'target_currency' => $payee->getCurrency(),
        ]);
    }
}
