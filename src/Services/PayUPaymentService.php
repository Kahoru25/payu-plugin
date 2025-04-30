<?php

namespace FriendsOfBotble\PayU\Services;

use Botble\Payment\Models\Payment;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class PayUPaymentService extends PaymentServiceAbstract
{
    public function isSupportRefundOnline(): bool
    {
        return true;
    }

    public function refund(string $chargeId, float $amount): array
    {
        try {
            $payment = app(PaymentInterface::class)->getFirstBy([
                'charge_id' => $chargeId,
            ]);

            if (! $payment) {
                return [
                    'error' => true,
                    'message' => __('Payment not found.'),
                ];
            }

            // Get the refund result from PayU
            $result = (new PayUService())->refund($chargeId, $amount);

            // If the refund was successful, store the refund information
            if (!$result['error'] && $payment instanceof Payment) {
                $metadata = $payment->metadata;
                $refunds = $metadata['refunds'] ?? [];
                
                // Add the new refund to the list
                $refunds[] = [
                    '_data_request' => [
                        'refund_amount' => $amount,
                        'currency' => $payment->currency,
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'transaction_id' => $result['data']['transactionResponse']['transactionId'] ?? null,
                    ],
                ];
                
                // Update the payment metadata
                $metadata['refunds'] = $refunds;
                $payment->metadata = $metadata;
                $payment->save();
                
                Log::info('PayU Refund completed', [
                    'charge_id' => $chargeId,
                    'amount' => $amount,
                    'result' => $result,
                ]);
            }

            return $result;
        } catch (Exception $exception) {
            Log::error('PayU Refund failed', [
                'charge_id' => $chargeId,
                'amount' => $amount,
                'exception' => $exception->getMessage(),
            ]);
            
            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }
}