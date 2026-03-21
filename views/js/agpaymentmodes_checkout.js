$(document).ready(function() {

    setInterval(function() {
        let id = $('.agpaymentmode_installment:visible').val();

        if($('.agpaymentmodes-error-input').is(":visible") || id == -1){
            $(".alert-installment").show();
        }else{
            $(".alert-installment").hide();
        }
    }, 300);


    setInterval(function() {
        if($('.agcheckout').length > 0){
            validateAllInputsOnePage();
        }else{
            validateAllInputs();
        }
    }, 300);

    function validateAllInputsOnePage(){
        
        inputListErrors=[];
        $(".agpaymentmode_validate").each(function( ) {
        input = $(this);
        typeInput = input.prop('nodeName');

        if(typeInput == 'TEXTAREA'){
            text = input.val().trim();

            if (text.length <= 0) {
                    $(input).addClass('agpaymentmodes-error-input');
                inputListErrors.push(input);
            } else {
                $(input).removeClass('agpaymentmodes-error-input');
            }

        }else if(typeInput == 'SELECT'){

            if(input.val() < 0){
                    $(input).addClass('agpaymentmodes-error-input');
                inputListErrors.push(input);
            }else{
                $(input).removeClass('agpaymentmodes-error-input');
            }
        }
        if(inputListErrors.length <= 0 && ($('#psgdpr').is(':checked') || $('#psgdpr').length == 0)){
            $(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', false);
        }else{
            $(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', true);
        }
        return inputListErrors;
      });
    }

    function validateAllInputs(markError = true){
        inputListValidate =[
            'agpaymentmode_input_',
            'agpaymentmode_installment_'
        ];
          
        idMethodPayment = $('#pay-with-'+$('[name=payment-option]:checked').attr('id')+'-form .payment_id').val();
          
        if(idMethodPayment <= 0 || idMethodPayment == null){
            return;
        }
        inputListErrors=[];
      inputListValidate.forEach(element => {
        input = $('#'+element+idMethodPayment);
        typeInput = input.prop('nodeName');

        if(typeInput == 'TEXTAREA'){
            text = input.val().trim();

            if (text.length <= 0) {
                if(markError){
                    $(input).addClass('agpaymentmodes-error-input');
                }
                inputListErrors.push(input);
            } else {
                $(input).removeClass('agpaymentmodes-error-input');
            }

        }else if(typeInput == 'SELECT'){

            if(input.val() < 0){
                if(markError){
                    $(input).addClass('agpaymentmodes-error-input');
                }
                inputListErrors.push(input);
            }else{
                $(input).removeClass('agpaymentmodes-error-input');
            }
        }
        
        if(inputListErrors.length <= 0 && ($(`[name='conditions_to_approve[terms-and-conditions]']`).is(':checked') || $(`[name='conditions_to_approve[terms-and-conditions]']`).length == 0)){
            $('#payment-confirmation').find('button').prop('disabled', false);
        }else{
            $('#payment-confirmation').find('button').prop('disabled', true);
        }
        return inputListErrors;
      });
    }
});
