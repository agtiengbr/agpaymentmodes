<?php

class AgPaymentModesMode extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agpaymentmode',
        'primary'   => 'id_agpaymentmode',
        'multilang' => true,
        'multilang_shop' => true,
        'fields'    => array(
            'id_agpaymentmode' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt',
                'lang' => false,
            ),
            'name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'db_type' => 'varchar(150)',
                'size' => 50,
                'lang' => true,
                'required' => true
            ),
            'additional_info' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'db_type' => 'varchar(150)',
                'size' => 75,
                'lang' => true
            ),            
            'price_variation' => array(
                'type' => self::TYPE_STRING,
                'db_type' => 'varchar(15)',
                'size' => 15,
            ),
            'ask_for_input' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'db_type' => 'boolean',
                'default' => 0
            ),
            'input_label' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'db_type' => 'varchar(255)',
                'lang' => true
            ),
            'image' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'db_type' => 'varchar(50)',
                'size' => 50,
                'lang' => false,
            ),
            'confirmation_message' => array(
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
                'lang' => true,
                'db_type' => 'text'
            ),
            'mapped_status' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'db_type' => 'int'
            ),
            'enabled' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'db_type' => 'boolean',
                'default' => 0,
                'lang' => false,
            ),
            'installment_enabled' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'db_type' => 'boolean',
                'default' => 0,
                'lang' => false,
            ),
            'installment_max' => array(
                'type' => self::TYPE_INT,
                'db_type' => 'int',
                'lang' => false
            ),
            'installment_min_value' => array(
                'type' => self::TYPE_FLOAT,
                'db_type' => 'float',
                'lang' => false
            ),
        ),
        'indexes' => array(
        )
    );

    public $id_agpaymentmode;
    public $name;
    public $additional_info;
    public $price_variation;
    public $image;
    public $confirmation_message;
    public $enabled;
    public $mapped_status;
    public $ask_for_input;
    public $input_label;
    public $installment_enabled;
    public $installment_max;
    public $installment_min_value;


    public static function getAll()
    {
        $collection = new PrestaShopCollection('AgPaymentModesMode');
        return $collection->getResults();
    }

    public static function getActives()
    {
        $collection = new PrestaShopCollection('AgPaymentModesMode');
        $collection->where('enabled', '=', 1);
        return $collection->getResults();
    }

    public function getFixedInterestRatio()
    {
        $collection = new PrestaShopCollection('AgPaymentModesFixedInterestRatio');
        $collection->where('payment_mode_id', '=', $this->id_agpaymentmode);
        return $collection->getResults();
    }

    public function getImageUrl()
    {
        if ($this->image) {
            //url das imagens
            $context = Context::getContext();
            $urls = $context->shop->getUrls();
            $urls = $urls[0];
            $base_url = $urls['domain'] . $urls['physical_uri'];

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $base_url = 'https://' . $base_url;
            } else {
                $base_url = 'http://' . $base_url;
            }

            return $base_url . 'modules/agpaymentmodes/views/img/' . $this->image . '.png';
        }
    }

    //verifica se esta forma de pagamento está ativa para a transportadora de ID $id_carrier
    public function isAvailableForCarrier($id_carrier)
    {
        $carrier = new Carrier($id_carrier);
        $id_carrier = $carrier->id_reference;
        
        $context = Context::getContext();

        $sql = new DbQuery();
        $sql->from('agpaymentmode_carrier');
        $sql->where('id_shop=' . (int)$context->shop->id);
        $sql->where('id_agpaymentmode=' . (int)$this->id);
        $sql->where('id_carrier=' . (int)$id_carrier);

        $row = Db::getInstance()->getRow($sql);

        return is_array($row);
    }

    public function makeAvailableForCarrier($id_carrier, $id_shop = null)
    {
        if (is_null($id_shop)) {
            $id_shop = Context::getContext()->shop->id;
        }

        return Db::getInstance()->insert('agpaymentmode_carrier', array(
            'id_shop' => (int) $id_shop,
            'id_agpaymentmode' => (int) $this->id,
            'id_carrier' => (int) $id_carrier
        ));
    }

    public function makeUnavailableForCarrier($id_carrier)
    {
        return Db::getInstance()->delete(
            'agpaymentmode_carrier',
            'id_shop=' .(int) Context::getContext()->shop->id . ' AND ' .
            'id_agpaymentmode=' . (int) $this->id . ' AND ' .
            'id_carrier=' .(int) $id_carrier
        );
    }

    public function setFixexInterestRatio(
        $number_of_payments,
        $interest_ratio_value
    ) {
       //verifica se já há um registro referente à forma de pagamento atual para a quantidade de meses desejada.
        $InterestRatio  = new AgPaymentModesFixedInterestRatio();
        $interest_ratio = $InterestRatio->getByPaymentModeId($this->id_agpaymentmode, $number_of_payments);

        if (!empty($interest_ratio)) {
            $InterestRatio->id_agpaymentmode_fixed_interest_ratio = $interest_ratio['id_agpaymentmode_fixed_interest_ratio'];
            $InterestRatio->id = $interest_ratio['id_agpaymentmode_fixed_interest_ratio'];
        }

        $InterestRatio->number_of_payments = $number_of_payments;
        $InterestRatio->payment_mode_id    = $this->id;
        $InterestRatio->interest_ratio     = $interest_ratio_value;

        return $InterestRatio->save();
    }

     
    public function getValue($payment_mode,$value)
    {

        $return = array();

        $total_with_discount = self::applyDiscount(array(
            'payment_total' => (string) $value,
            'discount' => (string) $payment_mode->price_variation,
        ));


        for ($i = 1; $i <= $payment_mode->installment_max; $i++) {

            $payment_value = self::calculatePaymentValue(array(
                'payment_mode_id'    => $payment_mode->id_agpaymentmode,
                'payment_total'      => $total_with_discount,
                'number_of_payments' => $i
            ));

            $total_to_pay = $payment_value * $i;
            $interest = Tools::ps_round($total_to_pay - $total_with_discount, 2);
               
            if ($payment_value >= $payment_mode->installment_min_value || $i == 1) {

                $InterestRatio  = new AgPaymentModesFixedInterestRatio();
                $interest_ratio = $InterestRatio->getByPaymentModeId($payment_mode->id_agpaymentmode, $i);
              
                $return[] = array(
                    'number_of_payments' => $i,
                    'value_each_payment' => $payment_value,
                    'total_to_pay' => $total_to_pay,
                    'interest' => $interest,
                    'interest_ratio_id' => $interest_ratio['id_agpaymentmode_fixed_interest_ratio']
                );
            }
        }
        return $return;
    }

    private static function applyDiscount($params)
    {
        $signal = 1;
        $type   = 'absolute';

        if (empty($params['discount'])) {
            $params['discount'] = "0";
        }

        if (is_string($params['discount']) && $params['discount'][0] === '-') {
            $signal = -1;
        }

        if ($params['discount'][Tools::strlen($params['discount']) - 1] === '%') {
            $type = 'percentage';
        }

        $params['discount'] = trim($params['discount'], '+-%');

        if ($type === 'percentage') {
            return $params['payment_total'] * (100 - $signal * $params['discount']) / 100;
        }
        
        return $params['payment_total'] - $signal * $params['discount'];
    }

    public static function calculatePaymentValue(array $params)
    {
        $payment_mode = self::getById($params['payment_mode_id']);

        if (empty($payment_mode) || !$payment_mode['enabled']) {
            throw new Exception(sprintf(
                'Payment mode #%d does not exists or is not active.',
                $params['payment_mode_id']
            ));
        }

        return self::calculatePaymentTotalWithInterest($params) / $params['number_of_payments'];
    }


    public static function calculatePaymentTotalWithInterest(array $params)
    {
        return self::calculatePaymentTotalWithFixedTax($params);
    }

      //calcula o valor total a ser pago aplicando a taxa de juros fixa
    private static function calculatePaymentTotalWithFixedTax(array $params)
    {

        $InterestRatio  = new AgPaymentModesFixedInterestRatio();
        $interest_ratio = $InterestRatio->getByPaymentModeId($params['payment_mode_id'], $params['number_of_payments']);
        if (empty($interest_ratio)) {
            throw new Exception(sprintf(
                'Can not use payment mode #%d with %d payments',
              $params['payment_mode_id'],
              $params['number_of_payments']
          ));
        }
        return $params['payment_total'] * (1 + $interest_ratio['interest_ratio'] / 100);
      }

       public static function getById($id)
    {
        $sql = new DbQuery();
        $sql->from(self::$definition['table'])
            ->where('id_agpaymentmode=' . (int)$id);

        return Db::getInstance()->getRow($sql);
    }

     
    public function getValueToMessage($value,$number_of_payments)
    {
        $payment_value = self::calculatePaymentValue(array(
            'payment_mode_id'    => $this->id_agpaymentmode,
            'payment_total'      => $value,
            'number_of_payments' => $number_of_payments
        ));

        $total_to_pay = $payment_value * $number_of_payments;
        $interest = Tools::ps_round($total_to_pay - $value, 2);
               
        if ($payment_value >= $this->installment_min_value || $number_of_payments == 1) {

            $InterestRatio  = new AgPaymentModesFixedInterestRatio();
            $interest_ratio = $InterestRatio->getByPaymentModeId($this->id_agpaymentmode, $number_of_payments);
              
            return array(
                'number_of_payments' => $number_of_payments,
                'value_each_payment' => $payment_value,
                'total_to_pay' => $total_to_pay,
                'interest' => $interest,
                'interest_ratio_id' => $interest_ratio['id_agpaymentmode_fixed_interest_ratio']
            );
        }
    }
}