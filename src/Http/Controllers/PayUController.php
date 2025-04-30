<?php

namespace FriendsOfBotble\PayU\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Models\Booking;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Payment\Supports\PaymentHelper;
use FriendsOfBotble\PayU\Providers\PayUServiceProvider;
use FriendsOfBotble\PayU\Services\PayUService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PayUController extends BaseController
{
    public function callback(Request $request, BaseHttpResponse $response): BaseHttpResponse
    {
        Log::info('PayU Callback received', $request->all());
        
        $metadata = json_decode(html_entity_decode($request->input('udf1')), true);
        
        // Mapeo de estado de PayU Colombia a estados del sistema
        $status = match ($request->input('state_pol') ?? $request->input('status')) {
            '4', 'success', 'APPROVED' => PaymentStatusEnum::COMPLETED,
            '5', '6', 'failure', 'REJECTED', 'DECLINED' => PaymentStatusEnum::FAILED,
            '7', 'PENDING' => PaymentStatusEnum::PENDING,
            '104', 'EXPIRED' => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        if ($status === PaymentStatusEnum::FAILED) {
            $errorMessage = $request->input('error_message') ?? $request->input('message') ?? $request->input('response_message_pol') ?? 'Transaction failed';
            
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL($metadata['token'] ?? ''))
                ->setMessage($errorMessage);
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'order_id' => $metadata['order_id'] ?? $request->input('extra1', []),
            'currency' => $metadata['currency'] ?? $request->input('currency'),
            'amount' => $request->input('amount') ?? $request->input('value'),
            'charge_id' => $request->input('mihpayid') ?? $request->input('transaction_id') ?? $request->input('reference_sale'),
            'payment_channel' => PayUServiceProvider::MODULE_NAME,
            'status' => $status,
            'customer_id' => $metadata['customer_id'] ?? 0,
            'customer_type' => $metadata['customer_type'] ?? null,
            'payment_type' => 'direct',
            'payment_method' => $request->input('payment_method_type') ?? $request->input('payment_method_name') ?? 'PayU',
        ], $request);

        if (is_plugin_active('hotel')) {
            $booking = Booking::query()
                ->select('transaction_id')
                ->find(Arr::first($metadata['order_id'] ?? []));

            if (! $booking) {
                return $response
                    ->setNextUrl(PaymentHelper::getCancelURL())
                    ->setMessage(__('Checkout failed!'));
            }

            return $response
                ->setNextUrl(PaymentHelper::getRedirectURL($booking->transaction_id))
                ->setMessage(__('Checkout successfully!'));
        }

        $nextUrl = PaymentHelper::getRedirectURL($metadata['token'] ?? '');

        if (is_plugin_active('job-board') || is_plugin_active('real-estate')) {
            $transactionId = $request->input('mihpayid') ?? $request->input('transaction_id') ?? $request->input('reference_sale');
            $nextUrl = $nextUrl . '?charge_id=' . $transactionId;
        }

        return $response
            ->setNextUrl($nextUrl)
            ->setMessage(__('Checkout successfully!'));
    }

    public function webhook(Request $request, PaymentInterface $paymentRepository, PayUService $payUService): void
    {
        Log::info('PayU Webhook received', $request->all());
        
        // Identificar el ID de transacción según los parámetros recibidos
        $transactionId = $request->input('mihpayid') ?? $request->input('transaction_id') ?? $request->input('reference_sale');
        
        // Verificar si tenemos un ID de transacción y un estado válido
        if (empty($transactionId)) {
            Log::error('PayU Webhook: Missing transaction ID');
            abort(400, 'Missing transaction ID');
        }

        $response = $payUService->verifyPayment($transactionId);

        if ($response['error']) {
            Log::error('PayU Webhook: Error verifying payment', $response);
            abort(400, 'Error verifying payment');
        }

        $payment = $paymentRepository->getFirstBy([
            'charge_id' => $transactionId,
        ]);

        if (! $payment) {
            Log::error('PayU Webhook: Payment not found for transaction ' . $transactionId);
            return;
        }

        // Mapeo de estado de PayU Colombia
        $statusCode = $response['data']['status'] ?? $request->input('state_pol');
        $status = match ($statusCode) {
            '4', 'success', 'APPROVED' => PaymentStatusEnum::COMPLETED,
            '5', '6', 'failure', 'REJECTED', 'DECLINED' => PaymentStatusEnum::FAILED,
            '7', 'PENDING' => PaymentStatusEnum::PENDING,
            '104', 'EXPIRED' => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        // Actualizar el estado del pago si no está en un estado final
        if (! in_array($payment->status, [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::FAILED, $status])) {
            $payment->status = $status;
            $payment->save();
            
            Log::info('PayU Webhook: Payment updated', [
                'transaction_id' => $transactionId,
                'previous_status' => $payment->status,
                'new_status' => $status,
            ]);
        }
    }
}