'use strict';

class PayUPSEBanks {
    constructor() {
        this.apiUrl = '';
        this.merchantId = '';
        this.accountId = '';
        this.environment = 'test';
        this.apiKey = '';
        this.bankSelector = '#pse_bank_select';
        
        // Set API URLs based on environment
        this.setApiUrl();
        
        // Initialize event listeners
        this.initEvents();
    }
    
    /**
     * Set API URL based on environment
     */
    setApiUrl() {
        // Set base API URL for PSE banks
        if (this.environment === 'test') {
            this.apiUrl = 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
        } else {
            this.apiUrl = 'https://api.payulatam.com/payments-api/4.0/service.cgi';
        }
    }
    
    /**
     * Initialize event listeners
     */
    initEvents() {
        // When PSE payment method is selected
        document.addEventListener('DOMContentLoaded', () => {
            const pseRadio = document.querySelector('input[name="payment_method_type"][value="PSE"]');
            const paymentMethodSelector = document.querySelector('select[name="payment_method"]');
            
            if (paymentMethodSelector) {
                paymentMethodSelector.addEventListener('change', (e) => {
                    if (e.target.value === 'payu') {
                        this.loadConfiguration();
                    }
                });
            }
            
            if (pseRadio) {
                pseRadio.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        this.loadBanks();
                    }
                });
            }
        });
    }
    
    /**
     * Load PayU configuration from hidden fields
     */
    loadConfiguration() {
        this.merchantId = document.querySelector('input[name="merchantId"]')?.value || '';
        this.accountId = document.querySelector('input[name="accountId"]')?.value || '';
        this.environment = document.querySelector('input[name="test"]')?.value === '1' ? 'test' : 'production';
        this.apiKey = document.querySelector('input[name="apiKey"]')?.value || '';
        
        this.setApiUrl();
    }
    
    /**
     * Load bank list from PayU Colombia API
     */
    loadBanks() {
        if (!this.merchantId || !this.accountId) {
            console.error('PayU configuration not loaded');
            return;
        }
        
        const bankSelector = document.querySelector(this.bankSelector);
        if (!bankSelector) {
            console.error('Bank selector not found');
            return;
        }
        
        // Clear existing options
        bankSelector.innerHTML = '<option value="">Seleccione un banco</option>';
        
        // Show loading indicator
        bankSelector.disabled = true;
        
        // Build request data for PSE banks query
        const data = {
            language: 'es',
            command: 'GET_BANKS_LIST',
            merchant: {
                apiLogin: this.apiLogin,
                apiKey: this.apiKey
            },
            bankListInformation: {
                paymentMethod: 'PSE',
                paymentCountry: 'CO'
            }
        };
        
        // Fetch banks from PayU API
        fetch(`${this.apiUrl}/4.0/service.cgi`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.code === 'SUCCESS' && data.banks) {
                // Populate bank selector with options
                data.banks.forEach(bank => {
                    const option = document.createElement('option');
                    option.value = bank.pseCode;
                    option.textContent = bank.description;
                    bankSelector.appendChild(option);
                });
                
                // Enable selector
                bankSelector.disabled = false;
            } else {
                console.error('Error loading banks:', data);
                // Add fallback message
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Error al cargar bancos';
                bankSelector.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error fetching banks:', error);
            // Add fallback message
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Error al cargar bancos';
            bankSelector.appendChild(option);
            bankSelector.disabled = false;
        });
    }
}

// Initialize PSE Banks handler
const payuPSEBanks = new PayUPSEBanks();