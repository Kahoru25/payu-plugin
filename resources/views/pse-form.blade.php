@php
    $isPSEEnabled = get_payment_setting('pse_enabled', 'payu') === '1';
@endphp

@if($isPSEEnabled)
<div class="pse-form-container" style="display: none;">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group mb-3">
                <label for="pse_bank_select" class="control-label">{{ __('Select your bank') }}</label>
                <select id="pse_bank_select" name="pse_bank" class="form-control">
                    <option value="">{{ __('Loading banks...') }}</option>
                </select>
                <div class="invalid-feedback">{{ __('Please select a bank') }}</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="pse_person_type" class="control-label">{{ __('Person Type') }}</label>
                <select id="pse_person_type" name="pse_person_type" class="form-control">
                    <option value="N">{{ __('Natural Person') }}</option>
                    <option value="J">{{ __('Legal Person') }}</option>
                </select>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="pse_document_type" class="control-label">{{ __('Document Type') }}</label>
                <select id="pse_document_type" name="pse_document_type" class="form-control">
                    <option value="CC">{{ __('Citizenship Card') }}</option>
                    <option value="CE">{{ __('Foreigner ID') }}</option>
                    <option value="NIT">{{ __('Tax ID') }}</option>
                    <option value="TI">{{ __('Identity Card') }}</option>
                    <option value="PP">{{ __('Passport') }}</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-group mb-3">
                <label for="pse_document" class="control-label">{{ __('Document Number') }}</label>
                <input type="text" id="pse_document" name="pse_document" class="form-control" required>
                <div class="invalid-feedback">{{ __('Please enter your document number') }}</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle PSE form when payment method changes
        const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
        const paymentTypeInputs = document.querySelectorAll('input[name="payment_method_type"]');
        const pseFormContainer = document.querySelector('.pse-form-container');
        
        if (paymentMethodInputs.length) {
            paymentMethodInputs.forEach(input => {
                input.addEventListener('change', function() {
                    togglePSEForm();
                });
            });
        }
        
        if (paymentTypeInputs.length) {
            paymentTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    togglePSEForm();
                });
            });
        }
        
        function togglePSEForm() {
            const isPayUSelected = document.querySelector('input[name="payment_method"]:checked')?.value === 'payu';
            const isPSESelected = document.querySelector('input[name="payment_method_type"]:checked')?.value === 'PSE';
            
            if (pseFormContainer) {
                pseFormContainer.style.display = (isPayUSelected && isPSESelected) ? 'block' : 'none';
            }
        }
        
        // Initial check
        togglePSEForm();
    });
</script>

<!-- Include the PSE banks script -->
<script src="{{ asset('vendor/core/plugins/payu/js/pse-banks.js') }}"></script>
@endif