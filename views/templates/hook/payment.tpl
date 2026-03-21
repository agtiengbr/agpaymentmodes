{foreach from=$payment_modes item=payment_mode}
	<div class="row">
		<div class="col-xs-12">
			<p class="payment_module agpaymentmodes">
				<a href="{$link->getModuleLink('agpaymentmodes', 'confirmation', ['id_agpaymentmode' => $payment_mode->id])|escape:'html'}" title="{l s='Pay by %s' d='Modules.Agpaymentmodes.Shop' sprintf=[$payment_mode->name]}">
					{if $payment_mode->image}
						<img src="{$payment_mode->image}" alt="{l s='Pay by %s' d='Modules.Agpaymentmodes.Shop' sprintf=[$payment_mode->name]}" width="86" height="49"/>
					{/if}
					{l s='Pay by %s' d='Modules.Agpaymentmodes.Shop' sprintf=[$payment_mode->name]}
					<span>({$payment_mode->additional_info})</span>
				</a>
			</p>
		</div>
	</div>
{/foreach}