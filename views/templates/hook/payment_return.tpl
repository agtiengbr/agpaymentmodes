{* Payment return block showing payment instructions *}
<div class="box agpaymentmodes-payment-return">
  <h3 class="page-subheading">
    {l s='Payment instructions' d='Modules.Agpaymentmodes.Shop'}
  </h3>
  {if $instructions}
    <div class="agpaymentmodes-instructions">
      {$instructions nofilter}
    </div>
  {else}
    <p>{l s='There are no instructions for this payment method.' d='Modules.Agpaymentmodes.Shop'}</p>
  {/if}
</div>
