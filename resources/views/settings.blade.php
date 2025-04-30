@php
    $name = 'PayU Colombia';
    $description = trans('plugins/payu::payu.description');
    $link = 'https://www.payulatam.com/co/';
    $image = asset('vendor/core/plugins/payu/images/payu.png');
    $moduleName = \FriendsOfBotble\PayU\Providers\PayUServiceProvider::MODULE_NAME;
    $status = (bool) get_payment_setting('status', $moduleName);
@endphp

<table class="table payment-method-item">
    <tbody>
    <tr class="border-pay-row">
        <td class="border-pay-col">
            <i class="fa fa-theme-payments"></i>
        </td>
        <td style="width: 20%">
            <img class="filter-black" src="{{ $image }}" alt="{{ $name }}">
        </td>
        <td class="border-right">
            <ul>
                <li>
                    <a href="{{ $link }}" target="_blank">{{ $name }}</a>
                    <p>{{ $description }}</p>
                </li>
            </ul>
        </td>
    </tr>
    <tr class="bg-white">
        <td colspan="3">
            <div class="float-start" style="margin-top: 5px;">
                <div @class(['payment-name-label-group', 'hidden' => ! $status])>
                    <span class="payment-note v-a-t">{{ trans('plugins/payment::payment.use') }}:</span>
                    <label class="ws-nm inline-display method-name-label">{{ get_payment_setting('name', $moduleName) }}</label>
                </div>
            </div>
            <div class="float-end">
                <a @class(['btn btn-secondary toggle-payment-item edit-payment-item-btn-trigger', 'hidden' => ! $status])>{{ trans('plugins/payment::payment.edit') }}</a>
                <a @class(['btn btn-secondary toggle-payment-item save-payment-item-btn-trigger', 'hidden' => $status])>{{ trans('plugins/payment::payment.settings') }}</a>
            </div>
        </td>
    </tr>
    <tr class="paypal-online-payment payment-content-item hidden">
        <td class="border-left" colspan="3">
            <form>
                <input type="hidden" name="type" value="{{ $moduleName }}" class="payment_type">

                <div class="row">
                    <div class="col-sm-6">
                        <ul>
                            <li>
                                <label>{{ trans('plugins/payment::payment.configuration_instruction', ['name' => $name]) }}</label>
                            </li>
                            <li class="payment-note">
                                <p>{{ trans('plugins/payment::payment.configuration_requirement', ['name' => $name]) }}:</p>
                                <ul class="m-md-l" style="list-style-type:decimal">
                                    <li style="list-style-type:decimal">
                                        <a href="https://www.payulatam.com/co/registrate/" target="_blank">
                                            {{ trans('plugins/payment::payment.service_registration', ['name' => $name]) }}
                                        </a>
                                    </li>
                                    <li style="list-style-type:decimal">
                                        <p>{{ trans('plugins/payment::payment.after_service_registration_msg', ['name' => $name]) }}</p>
                                    </li>
                                    <li style="list-style-type:decimal">
                                        <p>{{ __('Enter your Merchant ID, Account ID, API Key and API Login from PayU Colombia merchant account') }}</p>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <div class="well bg-white">
                            <x-core-setting::text-input
                                name="payment_payu_name"
                                :label="trans('plugins/payment::payment.method_name')"
                                :value="get_payment_setting('name', $moduleName, trans('plugins/payment::payment.pay_online_via', ['name' => $name]))"
                                data-counter="400"
                            />

                            <x-core-setting::form-group>
                                <label class="text-title-field" for="payment_payu_description">{{ trans('core/base::forms.description') }}</label>
                                <textarea class="next-input" name="payment_payu_description" id="payment_payu_description">{{ get_payment_setting('description', $moduleName, __('Payment with PayU Colombia')) }}</textarea>
                            </x-core-setting::form-group>

                            <x-core-setting::text-input
                                :name="'payment_' . $moduleName . '_merchant_id'"
                                :label="trans('plugins/payu::payu.merchant_id')"
                                :value="get_payment_setting('merchant_id', $moduleName)"
                                placeholder="xxxxxx"
                            />

                            <x-core-setting::text-input
                                :name="'payment_' . $moduleName . '_account_id'"
                                :label="trans('plugins/payu::payu.account_id')"
                                :value="get_payment_setting('account_id', $moduleName)"
                                placeholder="xxxxxx"
                            />

                            <x-core-setting::text-input
                                :name="'payment_' . $moduleName . '_api_key'"
                                :label="trans('plugins/payu::payu.api_key')"
                                :value="get_payment_setting('api_key', $moduleName)"
                                placeholder="xxxxxxxx"
                            />

                            <x-core-setting::text-input
                                :name="'payment_' . $moduleName . '_api_login'"
                                :label="trans('plugins/payu::payu.api_login')"
                                :value="get_payment_setting('api_login', $moduleName)"
                                placeholder="xxxxxxxx"
                            />

                            <x-core-setting::select
                                :name="'payment_' . $moduleName . '_environment'"
                                :label="trans('plugins/payu::payu.environment')"
                                :options="[
                                    'test' => trans('plugins/payu::payu.test'),
                                    'production' => trans('plugins/payu::payu.production'),
                                ]"
                                :value="get_payment_setting('environment', $moduleName)"
                            />
                            
                            <!-- Sección de métodos de pago para Colombia -->
                            <div class="mt-4 mb-3">
                                <h5>{{ trans('plugins/payu::payu.payment_methods') }}</h5>
                                <p>{{ trans('plugins/payu::payu.payment_methods_description') }}</p>
                            </div>
                            
                            <x-core-setting::on-off
                                :name="'payment_' . $moduleName . '_credit_card_enabled'"
                                :label="trans('plugins/payu::payu.credit_card_enabled')"
                                :value="get_payment_setting('credit_card_enabled', $moduleName, '1')"
                            />
                            
                            <x-core-setting::on-off
                                :name="'payment_' . $moduleName . '_pse_enabled'"
                                :label="trans('plugins/payu::payu.pse_enabled')"
                                :value="get_payment_setting('pse_enabled', $moduleName, '0')"
                            />
                            
                            <x-core-setting::on-off
                                :name="'payment_' . $moduleName . '_bank_transfer_enabled'"
                                :label="trans('plugins/payu::payu.bank_transfer_enabled')"
                                :value="get_payment_setting('bank_transfer_enabled', $moduleName, '0')"
                            />
                            
                            <x-core-setting::on-off
                                :name="'payment_' . $moduleName . '_cash_enabled'"
                                :label="trans('plugins/payu::payu.cash_enabled')"
                                :value="get_payment_setting('cash_enabled', $moduleName, '0')"
                            />
                            
                            <x-core-setting::text-input
                                :name="'payment_' . $moduleName . '_payment_timeout'"
                                :label="trans('plugins/payu::payu.payment_timeout')"
                                :value="get_payment_setting('payment_timeout', $moduleName, '60')"
                                placeholder="60"
                            />
                            <small class="form-text text-muted">{{ trans('plugins/payu::payu.payment_timeout_description') }}</small>

                            {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, $moduleName) !!}
                        </div>
                    </div>
                </div>

                <div class="col-12 bg-white text-end">
                    <button @class(['btn btn-warning disable-payment-item', 'hidden' => ! $status]) type="button">{{ trans('plugins/payment::payment.deactivate') }}</button>
                    <button @class(['btn btn-info save-payment-item btn-text-trigger-save', 'hidden' => $status]) type="button">{{ trans('plugins/payment::payment.activate') }}</button>
                    <button @class(['btn btn-info save-payment-item btn-text-trigger-update', 'hidden' => ! $status]) type="button">{{ trans('plugins/payment::payment.update') }}</button>
                </div>
            </form>
        </td>
    </tr>
    </tbody>
</table>