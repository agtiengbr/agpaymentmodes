<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_2_0($module)
{
    $sql = [];

    $sql[] = 'ALTER TABLE '._DB_PREFIX_.'agpaymentmode 
    ADD COLUMN installment_enabled BOOLEAN NOT NULL DEFAULT 0,
    ADD COLUMN installment_max INT(11) ,
    ADD COLUMN installment_min_value FLOAT NOT NULL DEFAULT 0
    ;';

    $sql[] = 'CREATE TABLE '._DB_PREFIX_.'agpaymentmode_fixed_interest_ratio (
    id_agpaymentmode_fixed_interest_ratio int(11) PRIMARY KEY AUTO_INCREMENT,
    payment_mode_id int(11) ,
    number_of_payments int(11),
    interest_ratio decimal(6,2) DEFAULT 0.00);';

               /*
     ** Here we execute the SQL
     */
    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            return Db::getInstance()->getMsgError();
        }
    }
    return true;
}
