<?php
class AgPaymentModesCarrier extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agpaymentmode_carrier',
        'primary'   => 'id_agpaymentmode_carrier',
        'fields'    => array(
            'id_agpaymentmode_carrier' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt',
            ),
            'id_shop' => array(
                'type' => self::TYPE_INT,
                'db_type' => 'int unsigned',
                'validate' => 'isInt',
            ),
            'id_agpaymentmode' => array(
                'type' => self::TYPE_INT,
                'db_type' => 'int unsigned',
                'validate' => 'isInt',
            ),
            'id_carrier' => array(
                'type' => self::TYPE_INT,
                'db_type' => 'int unsigned',
                'validate' => 'isInt',
            )
        ),
        'indexes' => array(
            array(
                'prefix' => 'unique',
                'name' => 'unique_payment_carrier',
                'fields' => array('id_shop', 'id_agpaymentmode', 'id_carrier')
            )
        )
    );

    public $id_agpaymentmode_carrier;
    public $id_shop;
    public $id_agpaymentmode;
    public $id_carrier;
}
