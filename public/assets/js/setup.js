// WhatsApp Bitrix24 Integration - Setup JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Set webhook URL
    const webhookUrl = window.location.origin + '/webhook/';
    document.getElementById('webhook_url').value = webhookUrl;
    
    // Step navigation
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const nextButtons = document.querySelectorAll('.next-btn');
    const prevButtons = document.querySelectorAll('.prev-btn');
    const form = document.getElementById('setup-form');
    
    // Copy webhook URL button
    const copyWebhookUrlButton = document.getElementById('copy-webhook-url');
    copyWebhookUrlButton.addEventListener('click', function() {
        const webhookUrlInput = document.getElementById('webhook_url');
        webhookUrlInput.select();
        document.execCommand('copy');
        
        // Show copied message
        const originalText = copyWebhookUrlButton.textContent;
        copyWebhookUrlButton.textContent = 'Copied!';
        setTimeout(function() {
            copyWebhookUrlButton.textContent = originalText;
        }, 2000);
    });
    
    // Next button click handler
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = button.closest('.step-content');
            const currentStepNumber = parseInt(currentStep.getAttribute('data-step'));
            
            // Validate current step
            if (validateStep(currentStepNumber)) {
                // Hide current step
                currentStep.classList.remove('active');
                
                // Show next step
                const nextStep = document.querySelector(`.step-content[data-step="${currentStepNumber + 1}"]`);
                nextStep.classList.add('active');
                
                // Update step indicators
                updateStepIndicators(currentStepNumber + 1);
                
                // Update summary if moving to step 4
                if (currentStepNumber + 1 === 4) {
                    updateSummary();
                }
            }
        });
    });
    
    // Previous button click handler
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = button.closest('.step-content');
            const currentStepNumber = parseInt(currentStep.getAttribute('data-step'));
            
            // Hide current step
            currentStep.classList.remove('active');
            
            // Show previous step
            const prevStep = document.querySelector(`.step-content[data-step="${currentStepNumber - 1}"]`);
            prevStep.classList.add('active');
            
            // Update step indicators
            updateStepIndicators(currentStepNumber - 1);
        });
    });
    
    // Form submit handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(form);
        
        // Show loading state
        const submitButton = document.querySelector('.submit-btn');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Connecting...';
        submitButton.disabled = true;
        
        // Send form data to server
        fetch('save_config.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('WhatsApp integration configured successfully!');
                
                // Redirect to Bitrix24
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                // Show error message
                alert('Error: ' + data.error);
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        });
    });
    
    // Validate step
    function validateStep(stepNumber) {
        const stepContent = document.querySelector(`.step-content[data-step="${stepNumber}"]`);
        const inputs = stepContent.querySelectorAll('input[required]');
        
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('error');
                isValid = false;
            } else {
                input.classList.remove('error');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields.');
        }
        
        return isValid;
    }
    
    // Update step indicators
    function updateStepIndicators(activeStep) {
        steps.forEach(step => {
            const stepNumber = parseInt(step.getAttribute('data-step'));
            
            if (stepNumber === activeStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
            
            if (stepNumber < activeStep) {
                step.classList.add('completed');
            } else {
                step.classList.remove('completed');
            }
        });
    }
    
    // Update summary
    function updateSummary() {
        document.getElementById('summary-business-account-id').textContent = document.getElementById('business_account_id').value;
        document.getElementById('summary-phone-number-id').textContent = document.getElementById('phone_number_id').value;
        document.getElementById('summary-webhook-url').textContent = document.getElementById('webhook_url').value;
        document.getElementById('summary-bitrix-domain').textContent = document.getElementById('bitrix_domain').value;
        document.getElementById('summary-bitrix-user-id').textContent = document.getElementById('bitrix_user_id').value;
    }
});
