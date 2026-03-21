$(function(){
    function validatePriceVariation(price_variation)
    {
        if (price_variation === '') {
            return true;
        }
        
        regexp = "^[0-9]+(.[0-9]+)?%?$"
        var reg = new RegExp(regexp);

        return reg.test(price_variation);
    }

    var table = $('#tabPaymentModes table');

    //página que está sendo utilizada no momento
    var page;

    //se a tabela de formas pagamentos existir, a tela atual é a página inicial de configurações
    if (table.length != 0) {
        page = 'list';
    }


    if (page == 'list') {
        $(table).find('tr').each(function(){            
            var tds = $(this).find('td');
            var td = $(tds[5]);
            var image_src = $.trim($(td).text());

            if (image_src) {
                var img = new Image();

                img.src = image_src;
                td.empty().append(img);
            }
        });

        // Confirm delete
        $(document).on('click', '.agpm-delete', function(){
            const msg = (window.agpaymentmodes_admin_i18n && agpaymentmodes_admin_i18n.confirm_delete) ? agpaymentmodes_admin_i18n.confirm_delete : 'Are you sure?';
            return confirm(msg);
        });
    }

    $('#price_variation').change(function(){
        if ($('#price_variation').val() === '') {
            $('#price_variation').val('0.00');
        }

        if (!validatePriceVariation($(this).val())) {
            $(this).closest('.form-group').addClass('has-error');
        } else {
            $(this).closest('.form-group').removeClass('has-error');
        }
    });

    $('#configuration_form').submit(function(){
        if ($('#price_variation').val() === '') {
            $('#price_variation').val('0.00');
        }

        if (!validatePriceVariation($('#price_variation').val())) {
            return false;
        }
    })

    let formRow =`<div class="form-group form-group-payment-mode-fixed-tax" style="">
        <label class="col-lg-3 control-label">
            ${ (window.agpaymentmodes_admin_i18n && agpaymentmodes_admin_i18n.fixed_interest_label) ? agpaymentmodes_admin_i18n.fixed_interest_label : 'Interest per number of payments (%)' }
        </label>
        <div class="col-lg-9">
        </div>
    </div>`;

    $('#installment_max').parent().parent().after(formRow);

    updateFixedTaxPaymentInputs();
    $("#installment_max").change(()=>{
        updateFixedTaxPaymentInputs();
    });

    function updateFixedTaxPaymentInputs()
    {
        //quantidade de inputs existentes
        var inputs = $('.form-group-payment-mode-fixed-tax .col-lg-2');
        var qtt_inputs = inputs.length;

        //quantidade de inputs que deveria existir
        var qtt_payments = parseInt($('#installment_max').val());

        //remove inputs adicionais
        if (qtt_payments < qtt_inputs) {
            for (var i = qtt_inputs; i > qtt_payments; i--) {
                $(inputs[i-1]).remove();
            }
        }
        else {
            var div_container = $('.form-group-payment-mode-fixed-tax .col-lg-9');

                    for (var i = qtt_inputs; i<qtt_payments; i++) {
                    var div = $('<div/>', {
                        'class' : 'col-lg-2'
                    });

                    var input = $('<input/>', {
                        'type' : 'text',
                        'name' : 'installment_fixed_tax_' + i,
                        'id' : 'installment_fixed_tax_' + i,
                        'value' : interest_ratio[i+1],
                        'placeholder' : i + 1
                    });

                div.appendTo(div_container);
                input.appendTo(div);
            }
        }
    }
});
