<?php

class AgPaymentModesSavemessageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $message = new Message();
        $message->id_cart = $this->context->cart->id;
        $message->id_customer = $this->context->customer->id;
        $message->message = Tools::getValue('message');

        $message->add();

        echo json_encode(array(
            'success' => 1
        ));

        exit();
    }
}
