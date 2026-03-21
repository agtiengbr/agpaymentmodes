<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_2($module)
{
    //cria o campo de variação de preço
    $instance = new AgPaymentModesMode();
    $instance->createMissingColumns();

    return true;
}
