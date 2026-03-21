<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_4($module)
{
    //cria o campo de variação de preço
    $instance = new AgPaymentModesCarrier();
    $instance->createDatabase();
    $instance->createIndexes();

    $module->registerHook('actionObjectCarrierAddAfter');

    //ativa as formas de pagamento para todas as transportadoras
    $payment_modes = AgPaymentModesMode::getAll();

    $carriers = Carrier::getCarriers((int)Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
    $carrier_ids = array();
    foreach ($carriers as $carrier) {
        $carrier_ids[] = $carrier['id_reference'];
    }

    $shops = Shop::getShops(true, null, true);

    foreach ($payment_modes as $payment_mode) {
    	foreach ($shops as $shop) {
    		foreach ($carrier_ids as $carrier_id) {
    			$payment_mode->makeAvailableForCarrier($carrier_id, $shop);
    		}
    	}
    }

    return true;
}
