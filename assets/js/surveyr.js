function initializeSurvey({
    surveyMode, 
    surveyJson, 
    formTheme, 
    formId, 
    webhookUrl, 
    nonceToken = null
}) {
    const SurveyThemeLight = SurveyTheme[formTheme + 'LightPanelless'];
    const survey = new Survey.Model(surveyJson);

    // Display banner if survey is in restricted mode
    if (surveyMode === 'restricted') {
        const banner = document.createElement('div');
        banner.innerText = "This form is restricted or is no longer receiving responses";
        banner.style.cssText = `
            padding: 30px 10px;
            background-color: #ffcccc;
            color: #b00;
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            position: fixed;
            width: 100%;
            bottom: 0;  
            left: 0;
            z-index: 1;
        `;
        document.body.insertBefore(banner, document.body.firstChild);
    }

    // AJAX request handler
    const sendAjaxRequest = (url, data, includeNonce = false) => {
        if (surveyMode === 'restricted') {
            toast.error({ message: 'This form is restricted or is no longer receiving responses' });
            return Promise.reject();
        }

        const nonce = includeNonce ? nonceToken : null;
        return jQuery.ajax({
            url,
            method: 'POST',
            data: {
                // action: formId,
                // _wpnonce: nonce,
                content: data
            }
        });
    };

    // Survey completion event
    survey.onComplete.add((result) => {
        
        let submissionUrl = '/wp-json/surveyr/v1/submit/' + formId;
        
        const requests = [
            // Send data to the main server
            sendAjaxRequest(submissionUrl, result.data)
        ];

        // Optionally send data to the webhook
        if (webhookUrl) {
            requests.push(sendAjaxRequest(webhookUrl, result.data));
        }

        // Handle responses
        Promise.all(requests)
            .then(([mainResponse, webhookResponse]) => {
                if (mainResponse.status) {
                    toast.success({ message: mainResponse.message });
                } else {
                    toast.error({ message: mainResponse.message || 'An unknown error occurred' });
                }

                if (webhookUrl && webhookResponse) {
                    console.log('Webhook Response:', webhookResponse);
                }
            })
            .catch(() => {
                toast.error({ message: 'An error occurred while submitting the form' });
            });
    });

    // Initialize and render survey
    survey.render(document.getElementById(formId));
    survey.applyTheme(SurveyThemeLight);
}