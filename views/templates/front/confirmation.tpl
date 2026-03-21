{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Back to checkout' d='Modules.Agpaymentmodes.Shop'}">{l s='Checkout' d='Modules.Agpaymentmodes.Shop'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Payment via %s' sprintf=[$payment_mode->name] d='Modules.Agpaymentmodes.Shop'} 
{/capture}

<h1 class="page-heading">
    {l s='Order payment' d='Modules.Agpaymentmodes.Shop'}
    </h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('agpaymentmodes', 'payment', ['id_agpaymentmode' => $payment_mode->id], true)|escape:'html':'UTF-8'}" method="post" id="agmoipmarketplace_credit_card">
	<div class="box">
        <h3 class="page-subheading">
            {l s='Payment via %s' sprintf=[$payment_mode->name] d='Modules.Agpaymentmodes.Shop'}
        </h3>

        <div class="row">
        	<p>
            {l s='You have chosen to pay via %s.' sprintf=[$payment_mode->name] d='Modules.Agpaymentmodes.Shop'}
            {l s='The total for your order will be %s.' sprintf=[{displayPrice price=$total}] d='Modules.Agpaymentmodes.Shop'}
        	{if $payment_mode->ask_for_input}
                {l s='To confirm your order, fill the following information:' d='Modules.Agpaymentmodes.Shop'}</p>

        		<div class="form-group">
	        		<label class="control-label" for="agpaymentmode-input">{$payment_mode->input_label}</label>
	        		<textarea class="form-control" type="text" name="agpaymentmode-input" id="agpaymentmode-input" required="true" rows="4"></textarea>
	        	</div>
	        {else}
            	</p>
            	{* Show payment instructions, if any *}
            	{if $payment_mode->confirmation_message}
            		<div class="alert alert-info agpaymentmodes-instructions">{$payment_mode->confirmation_message nofilter}</div>
            	{elseif $payment_mode->additional_info}
            		<div class="alert alert-info agpaymentmodes-instructions">{$payment_mode->additional_info nofilter}</div>
            	{/if}
        	{/if}
        </div>
    </div>

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Other payment modes' d='Modules.Agpaymentmodes.Shop'}
        </a>
        <button class="button btn btn-default button-medium" type="submit">
            <span>{l s='Confirm your order' d='Modules.Agpaymentmodes.Shop'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
</form>