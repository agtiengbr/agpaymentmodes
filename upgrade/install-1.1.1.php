<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_1($module)
{
    $dir = _PS_MODULE_DIR_ . 'agpaymentmodes/views/templates/mail/br';

    unlink($dir . '/payment_info.txt');
    unlink($dir . '/payment_info.html');

    rmdir($dir);
    rmdir(_PS_MODULE_DIR_ . 'agpaymentmodes/views/templates/mail/');

    return true;
}
