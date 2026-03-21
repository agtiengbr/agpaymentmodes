<?php
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class AgPaymentModesPaymentModuleFrontController extends ModuleFrontController
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

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'agpaymentmodes') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', [], 'Modules.Agpaymentmodes.Shop'));
        }

        $payment_mode = new AgPaymentModesMode(Tools::getValue('id_agpaymentmode'), $this->context->language->id);
        if (!Validate::isLoadedObject($payment_mode) || !$payment_mode->enabled) {
            die($this->trans('This payment method is not available.', [], 'Modules.Agpaymentmodes.Shop'));
        }


        if (Tools::getValue('agpaymentmode-input')) {
            $message = new Message();
            $message->id_cart = $this->context->cart->id;
            $message->id_customer = $this->context->customer->id;
            $message->message = Tools::getValue('agpaymentmode-input');

            $result = $message->add();
        } elseif ($payment_mode->ask_for_input && version_compare(_PS_VERSION_, '1.7', '<')) {
            //na versão 1.6, o campo de 'informações adicionais' é salvo apenas no fechamento do pedido
            Logger::addLog(
                sprintf(
                    $this->trans('agpaymentmodes - Error in the cart %d - additional information is empty.', [], 'Modules.Agpaymentmodes.Shop'),
                    $this->context->cart->id
                ),
                3
            );

            Tools::redirect('index.php?controller=order&step=1');
        }


        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->removeDiscount($payment_mode);
        if ($payment_mode->price_variation) {
            $this->createDiscount($payment_mode);
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder($cart->id, $payment_mode->mapped_status, $total, $payment_mode->name, NULL, null, (int)$currency->id, false, $customer->secure_key);
    $orderId = (int) Order::getIdByCartId($cart->id);
    $ps_order = $orderId ? new Order($orderId) : null;
        
        if ($ps_order && $payment_mode->confirmation_message) {
            $shop = new Shop($ps_order->id_shop, $ps_order->id_lang);
            $priceFormatter = new PriceFormatter();

            $replaces =[
                '{order_price}' =>  $priceFormatter->format((float) $ps_order->getOrdersTotalPaid(), Currency::getCurrencyInstance((int) $ps_order->id_currency)),
                '{order_price_unformatted}' =>  round($ps_order->getOrdersTotalPaid(),2)
            ];
            foreach ($replaces as $key => $replace) {
                $payment_mode->confirmation_message = str_replace($key, $replace, $payment_mode->confirmation_message);
            }

            $data = array(
                '{order_name}' => $ps_order->reference,
                '{payment_mode_name}' => $payment_mode->name,
                '{payment_mode_additional_info}' => $payment_mode->confirmation_message,
                '{shop_name}' => $shop->name,
                '{firstname}' => $this->context->customer->firstname,
                '{lastname}' => $this->context->customer->lastname
            );

            if ($ps_order && Validate::isLoadedObject($ps_order)) {
                Mail::Send(
                    (int)$ps_order->id_lang,
                    'payment_info',
                    $this->trans('Complete the payment for your order', [], 'Modules.Agpaymentmodes.Shop'),
                    $data,
                    $this->context->customer->email,
                    $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
                    null,
                    null,
                    null,
                    null,
                    _PS_MODULE_DIR_ . $this->module->name . '/mails/'
                );
            }
        }

        // Add this message in the customer thread
    if ($ps_order && isset($msg) && Validate::isLoadedObject($msg)) {
            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = 0;
            $customer_thread->id_customer = (int)$ps_order->id_customer;
            $customer_thread->id_shop = (int)$this->context->shop->id;
            $customer_thread->id_order = (int)$ps_order->id;
            $customer_thread->id_lang = (int)$this->context->language->id;
            $customer_thread->email = $customer->email;
            $customer_thread->status = 'closed';
            $customer_thread->token = Tools::passwdGen(12);
            $customer_thread->add();

            $messages = Db::getInstance()->executeS('
                SELECT *
                FROM `'._DB_PREFIX_.'message`
                WHERE `id_cart` = '.(int) $this->context->cart->id
            );
            
            foreach ($messages as $message) {
                if ($message['id_order']) {
                    continue;
                }

                $msg = new Message($message['id_message']);
                $msg->id_order = $ps_order->id;
                $msg->update();

                $customer_message = new CustomerMessage();
                $customer_message->id_customer_thread = $customer_thread->id;
                $customer_message->id_employee = 0;
                $customer_message->message = $msg->message;
                $customer_message->private = 0;
                $customer_message->add();
            }
        }
   
        if ($ps_order) {
            $this->saveMessages($payment_mode,$total,$ps_order->id);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key . '&id_agpaymentmode=' . $payment_mode->id);
        } else {
            // Fallback in case order creation hasn't yet produced an id
            Tools::redirect('index.php?controller=history');
        }
    }

    protected function saveMessages($payment_mode,$total,$id_order)
    {
        $txtMessage = Tools::getValue('agpaymentmode_input_'.$payment_mode->id);
        $installment_id = Tools::getValue('agpaymentmode_installment_'.$payment_mode->id);

        $msgs=[];
        if($txtMessage){
            $msgs[]= $txtMessage;
        }

        if($installment_id){
            $values = $payment_mode->getValueToMessage($total,(new AgPaymentModesFixedInterestRatio($installment_id))->number_of_payments);
            $priceFormatter = new PriceFormatter();
            $currency = $id_order ? Currency::getCurrencyInstance((int) (new Order((int)$id_order))->id_currency) : $this->context->currency;
            $msgs[]= $values['number_of_payments'].'x '.$priceFormatter->format((float) $values['value_each_payment'], $currency).' (Total de '.$priceFormatter->format((float) $values['total_to_pay'], $currency).')';
        }

        if(count($msgs)){
            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = 0;
            $customer_thread->id_customer = (int) $this->context->customer->id;
            $customer_thread->id_shop = (int) $this->context->shop->id;
            $customer_thread->id_order = (int) $id_order;
            $customer_thread->id_lang = (int) $this->context->language->id;
            $customer_thread->email = $this->context->customer->email;
            $customer_thread->status = 'closed';
            $customer_thread->token = Tools::passwdGen(12);
            $customer_thread->add();

            foreach ($msgs as $msg) {
                $customer_message = new CustomerMessage();
                $customer_message->id_customer_thread = $customer_thread->id;
                $customer_message->id_employee = 0;
                $customer_message->message = $msg;
                $customer_message->private = 0;
                $customer_message->add();
            }
        }

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
            $cart_rule->name[$lang['id_lang']] = $this->trans('Discount %s', ['%s' => $payment_mode->name], 'Modules.Agpaymentmodes.Shop', $locale);
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
