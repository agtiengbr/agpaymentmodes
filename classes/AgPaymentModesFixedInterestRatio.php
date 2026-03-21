<?php
 
class AgPaymentModesFixedInterestRatio extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agpaymentmode_fixed_interest_ratio',
        'primary'   => 'id_agpaymentmode_fixed_interest_ratio',
        'multilang' => false,
        'fields'    => array(
            'id_agpaymentmode_fixed_interest_ratio'    => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'payment_mode_id'                          => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'number_of_payments'                       => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'interest_ratio'                           => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'db_type' => 'decimal(6, 3)'),
        ),
    );

    public $id_agpaymentmode_fixed_interest_ratio;
    public $payment_mode_id;
    public $number_of_payments;
    public $interest_ratio;

    public function getAllByPaymentModeId($id)
    {
        $sql = new DbQuery();
        $sql = $sql->from(self::$definition['table'])
            ->where('payment_mode_id='.(int)$id);

        return Db::getInstance()->executeS($sql);
    }

    public function getByPaymentModeId($id, $number_of_payments)
    {
        $sql = new DbQuery();
        $sql->from(self::$definition['table'])
            ->where('payment_mode_id='.(int) $id)
            ->where('number_of_payments='.(int) $number_of_payments);

        return Db::getInstance()->getRow($sql);
    }
}
