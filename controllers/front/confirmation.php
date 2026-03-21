<?php

class AgPaymentModesConfirmationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
    	$cart = $this->context->cart;
        if (
            $cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }


        $payment_mode = new AgPaymentModesMode(Tools::getValue('id_agpaymentmode'), $this->context->language->id);
        if (!Validate::isLoadedObject($payment_mode) || !$payment_mode->enabled) {
            Logger::addLog(
                sprintf(
                    $this->module->trans('agpaymentmodes - Error in the cart %d - payment mode %s not found or disabled.', [], 'Modules.Agpaymentmodes.Shop'),
                    $this->context->cart->id,
                    $payment_mode->name
                ),
                3
            );

        	Tools::redirect('index.php?controller=order&step=1');
        }

        $this->removeDiscount($payment_mode);
        if ($payment_mode->price_variation) {
            $this->createDiscount($payment_mode);
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $this->context->smarty->assign(array(
            'payment_mode' => $payment_mode,
            'total' => $total
        ));

        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/confirmation.css');
        $this->setTemplate('confirmation.tpl');
    }

    protected function removeDiscount($payment_mode)
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'discount_boleto') {
                $this->context->cart->removeCartRule($rule['id_cart_rule']);
            }
        }
    }

    protected function createDiscount($payment_mode)
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto ' . $payment_mode->name) {
                return;
            }
        }

        $cart_rule = new CartRule();

        foreach (Language::getLanguages() as $lang) {
            $locale = isset($lang['locale']) ? $lang['locale'] : null;
            $cart_rule->name[$lang['id_lang']] = $this->module->trans('Discount %s', ['%s' => $payment_mode->name], 'Modules.Agpaymentmodes.Shop', $locale);
        }

        $cart_rule->id_customer = $this->context->cart->id_customer;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime("+1 day",strtotime(date('Y-m-d'))));
        $cart_rule->description = 'discount_boleto';
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->priority = 1;
        $cart_rule->partial_use = 1;
        $cart_rule->code = md5('discount_' . $payment_mode->name .$this->context->cart->id_customer . date('Y-m-d H:i:s'));

        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 0;
        $cart_rule->minimum_amount_currency = 1;
        $cart_rule->minimum_amount_shipping = 0;
        $cart_rule->country_restriction = 0;
        $cart_rule->carrier_restriction = 0;
        $cart_rule->group_restriction = 0;
        $cart_rule->cart_rule_restriction = 0;
        $cart_rule->product_restriction = 0;
        $cart_rule->shop_restriction = 0;
        $cart_rule->free_shipping = 0;

        $total = $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $cart_rule->reduction_percent = 0;
        $cart_rule->reduction_amount = $total - AgClienteMathHelper::applyPriceVariation($total, '-' . $payment_mode->price_variation);

        $cart_rule->reduction_tax = 1;
        $cart_rule->reduction_currency = $this->context->currency->id;
        $cart_rule->reduction_product = 0;

        $cart_rule->gift_product = 0;
        $cart_rule->gift_product_attribute = 0;
        $cart_rule->highlight = 0;
        $cart_rule->active = 1;

        $cart_rule->add();
        $this->context->cart->addCartRule($cart_rule->id);

        $this->context->cart->save();
    }
}
