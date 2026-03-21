<form action="{$form_action}" method="post" class="agpaymentmodes" id="agpaymentmodes">
	<p>{$payment_mode->additional_info[$context->language->id]}</p>

	<input type="hidden" class="payment_id" value="{$payment_mode->id}">

	<div class="alert alert-danger alert-installment" style="display:none" id="alert-installment-{$payment_mode->id}">{l s='Please fill the fields below to complete your purchase.' d='Modules.Agpaymentmodes.Shop'}</div>

	{if $payment_mode->ask_for_input}
		<div class="form-group row">
			<label class="col-md-3 form-control-label required" for="agpaymentmode_input_{$payment_mode->id}">{$payment_mode->input_label[$context->language->id]}</label>
			<div class="col-md-9">
				<textarea class="form-control agpaymentmode agpaymentmode_validate" name="agpaymentmode_input_{$payment_mode->id}" id="agpaymentmode_input_{$payment_mode->id}"></textarea>
			</div>
		</div>
	{/if}

	{if $payment_mode->installment_enabled}
		<div class="form-group row">
			<label class="col-md-3 form-control-label required" for="agpaymentmode_installment_{$payment_mode->id}">{l s='Installments' d='Modules.Agpaymentmodes.Shop'}</label>
			<div class="col-md-9">
				<select paymentmode="{$payment_mode->id}" name="agpaymentmode_installment_{$payment_mode->id}" class="agpaymentmode_installment form-control agpaymentmode_validate" id="agpaymentmode_installment_{$payment_mode->id}" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="24">
				<option value="-1">{l s='Choose the number of installments' d='Modules.Agpaymentmodes.Shop'}</option>
					{foreach from=$installments item=$installment}
						<option value="{$installment['interest_ratio_id']}">{$installment['number_of_payments']}x {$installment['value_each_payment_formatted']} ({l s='Total of %s' d='Modules.Agpaymentmodes.Shop' sprintf=[{$installment['total_to_pay_formatted']}]})</option>
					{/foreach}
				</select>
			</div>
		</div>
	{/if}
</form>
