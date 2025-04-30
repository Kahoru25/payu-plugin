<?php

namespace FriendsOfBotble\PayU\Services;

use FriendsOfBotble\PayU\Providers\PayUServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayUService
{
    protected string $merchantId;
    
    protected string $accountId;

    protected string $apiKey;

    protected string $apiLogin;

    protected array $processUrls = [
        'test' => 'https://sandbox.api.payulatam.com',
        'production' => 'https://api.payulatam.com',
    ];
    
    // Añadimos URLs específicas para Colombia
    protected array $checkoutUrls = [
        'test' => 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu',
        'production' => 'https://checkout.payulatam.com/ppp-web-gateway-payu',
    ];

    protected array $data = [];
    
    // Añadimos los métodos de pago disponibles para Colombia
    protected array $paymentMethods = [];

    public function __construct()
    {
        $this->merchantId = get_payment_setting('merchant_id', PayUServiceProvider::MODULE_NAME);
        $this->accountId = get_payment_setting('account_id', PayUServiceProvider::MODULE_NAME);
        $this->apiKey = get_payment_setting('api_key', PayUServiceProvider::MODULE_NAME);
        $this->apiLogin = get_payment_setting('api_login', PayUServiceProvider::MODULE_NAME);
        
        // Configuramos los métodos de pago habilitados
        $this->paymentMethods = $this->getEnabledPaymentMethods();
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        $this->withAdditionalData();

        return $this;
    }

    public function redirectToCheckoutPage(): void
    {
        echo view('plugins/payu::form', [
            'data' => $this->data,
            'action' => $this->getCheckoutUrl(),
        ]);

        exit();
    }

    public function refund(string $chargeId, float $amount): array
    {
        $data = [
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->apiLogin,
                'apiKey' => $this->apiKey,
            ],
            'transaction' => [
                'type' => 'REFUND',
                'parentTransactionId' => $chargeId,
                'order' => [
                    'id' => Str::random(10),
                ],
                'reason' => 'Reembolso solicitado por el cliente',
            ],
            'test' => get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) === 'test',
        ];

        $response = Http::withoutVerifying()
            ->post($this->getProcessUrl('/payments-api/4.0/service.cgi'), $data);

        if ($response->failed()) {
            return [
                'error' => true,
                'message' => $response->reason(),
            ];
        }

        $responseData = $response->json();

        return [
            'error' => !isset($responseData['code']) || $responseData['code'] !== 'SUCCESS',
            'message' => $responseData['error'] ?? $responseData['transactionResponse']['responseMessage'] ?? null,
            'data' => $responseData,
        ];
    }

    public function verifyPayment(string $chargeId): array
    {
        $data = [
            'language' => 'es',
            'command' => 'ORDER_DETAIL',
            'merchant' => [
                'apiLogin' => $this->apiLogin,
                'apiKey' => $this->apiKey,
            ],
            'details' => [
                'transactionId' => $chargeId,
            ],
            'test' => get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) === 'test',
        ];

        $response = Http::withoutVerifying()
            ->post($this->getProcessUrl('/reports-api/4.0/service.cgi'), $data);

        if ($response->failed()) {
            return [
                'error' => true,
                'message' => $response->reason(),
                'data' => [],
            ];
        }

        $responseData = $response->json();
        
        return [
            'error' => !isset($responseData['code']) || $responseData['code'] !== 'SUCCESS',
            'message' => $responseData['error'] ?? null,
            'data' => $responseData['result'] ?? [],
        ];
    }

    public function transactionId(): string
    {
        return Str::random(10);
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }
    
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiLogin(): string
    {
        return $this->apiLogin;
    }

    // Obtener los métodos de pago habilitados desde la configuración
    protected function getEnabledPaymentMethods(): array
    {
        $enabledMethods = [];
        $methods = [
            'credit_card' => 'CREDIT_CARD',
            'pse' => 'PSE',
            'bank_transfer' => 'BANK_TRANSFER',
            'cash' => 'CASH',
        ];
        
        foreach ($methods as $key => $value) {
            if (get_payment_setting($key . '_enabled', PayUServiceProvider::MODULE_NAME, '0') === '1') {
                $enabledMethods[] = $value;
            }
        }
        
        return $enabledMethods;
    }

    protected function getSignature(): string
    {
        // Para PayU Latam Colombia, la firma se calcula como:
        // md5(apiKey~merchantId~referenceCode~amount~currency)
        return md5(
            $this->getApiKey() . '~' . 
            $this->getMerchantId() . '~' . 
            $this->data['referenceCode'] . '~' . 
            $this->data['amount'] . '~' . 
            $this->data['currency']
        );
    }

    protected function withAdditionalData(): void
    {
        $this->data = array_merge($this->data, [
            'merchantId' => $this->getMerchantId(),
            'accountId' => $this->getAccountId(),
            'signature' => $this->getSignature(),
            'test' => get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) === 'test' ? 1 : 0,
            'paymentMethods' => implode(',', $this->paymentMethods),
            'lap' => '', // Deja vacío para permitir todos los métodos de pago o especifica métodos específicos
        ]);
    }

    protected function getProcessUrl(string $uri = ''): string
    {
        return $this->processUrls[
            get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) ?: 'test'
        ] . $uri;
    }

    protected function getCheckoutUrl(): string
    {
        return $this->checkoutUrls[
            get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) ?: 'test'
        ];
    }

    protected function getPaymentUrl(): string
    {
        return $this->getProcessUrl('/payments-api/4.0/service.cgi');
    }

    public function getApiPaymentUrl(): string
    {
        return $this->getProcessUrl('/payments-api/4.0/service.cgi');
    }

    public function makePayment(array $paymentData): array
    {
        $data = [
            'language' => 'es',
            'command' => 'SUBMIT_TRANSACTION',
            'merchant' => [
                'apiLogin' => $this->apiLogin,
                'apiKey' => $this->apiKey,
            ],
            'transaction' => [
                'order' => [
                    'accountId' => $this->accountId,
                    'referenceCode' => $paymentData['referenceCode'],
                    'description' => $paymentData['description'],
                    'language' => 'es',
                    'signature' => $this->getSignature(),
                    'notifyUrl' => route('payment.payu.webhook'),
                    'additionalValues' => [
                        'TX_VALUE' => [
                            'value' => $paymentData['amount'],
                            'currency' => $paymentData['currency'] ?? 'COP',
                        ],
                    ],
                    'buyer' => [
                        'merchantBuyerId' => $paymentData['buyerId'] ?? '',
                        'fullName' => $paymentData['buyerName'],
                        'emailAddress' => $paymentData['buyerEmail'],
                        'contactPhone' => $paymentData['buyerPhone'],
                        'dniNumber' => $paymentData['buyerDocument'] ?? '',
                        'shippingAddress' => [
                            'street1' => $paymentData['buyerAddress'],
                            'city' => $paymentData['buyerCity'],
                            'state' => $paymentData['buyerState'],
                            'country' => $paymentData['buyerCountry'],
                            'postalCode' => $paymentData['buyerPostalCode'] ?? '',
                        ],
                    ],
                ],
                'payer' => [
                    'merchantPayerId' => $paymentData['payerId'] ?? '',
                    'fullName' => $paymentData['payerName'] ?? $paymentData['buyerName'],
                    'emailAddress' => $paymentData['payerEmail'] ?? $paymentData['buyerEmail'],
                    'contactPhone' => $paymentData['payerPhone'] ?? $paymentData['buyerPhone'],
                    'dniNumber' => $paymentData['payerDocument'] ?? $paymentData['buyerDocument'] ?? '',
                    'billingAddress' => [
                        'street1' => $paymentData['payerAddress'] ?? $paymentData['buyerAddress'],
                        'city' => $paymentData['payerCity'] ?? $paymentData['buyerCity'],
                        'state' => $paymentData['payerState'] ?? $paymentData['buyerState'],
                        'country' => $paymentData['payerCountry'] ?? $paymentData['buyerCountry'],
                        'postalCode' => $paymentData['payerPostalCode'] ?? $paymentData['buyerPostalCode'] ?? '',
                    ],
                ],
                'creditCard' => [
                    'number' => $paymentData['cardNumber'] ?? '',
                    'securityCode' => $paymentData['cardCvv'] ?? '',
                    'expirationDate' => $paymentData['cardExpMonth'] && $paymentData['cardExpYear'] ? 
                        $paymentData['cardExpYear'] . '/' . $paymentData['cardExpMonth'] : '',
                    'name' => $paymentData['cardName'] ?? '',
                ],
                'extraParameters' => [
                    'INSTALLMENTS_NUMBER' => $paymentData['installments'] ?? 1,
                ],
                'type' => $paymentData['paymentMethod'] ?? 'CREDIT_CARD',
                'paymentMethod' => $paymentData['paymentMethod'] ?? 'VISA',
                'paymentCountry' => 'CO',
                'deviceSessionId' => $paymentData['deviceSessionId'] ?? '',
                'ipAddress' => $paymentData['ipAddress'] ?? request()->ip(),
                'cookie' => $paymentData['cookie'] ?? '',
                'userAgent' => $paymentData['userAgent'] ?? request()->userAgent(),
            ],
            'test' => get_payment_setting('environment', PayUServiceProvider::MODULE_NAME) === 'test',
        ];

        $response = Http::withoutVerifying()
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->post($this->getApiPaymentUrl(), $data);

        if ($response->failed()) {
            return [
                'error' => true,
                'message' => $response->reason(),
                'data' => [],
            ];
        }

        $responseData = $response->json();
        
        return [
            'error' => !isset($responseData['code']) || $responseData['code'] !== 'SUCCESS',
            'message' => $responseData['error'] ?? $responseData['transactionResponse']['responseMessage'] ?? null,
            'data' => $responseData,
        ];
    }
}