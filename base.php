<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

require_once _PS_MODULE_DIR_ . 'agcliente/lib/AgPaymentModule.php';
class BaseAgPaymentModes extends AgPaymentModule
{
    protected $hooks = array('payment', 'paymentOptions', 'displayOrderConfirmation', 'displayHeader', 'actionObjectOrderPaymentAddAfter');
    public function __construct()
    {
        $this->name     = 'agpaymentmodes';
        $this->tab      = 'payments_gateways';
        $this->version  = '1.2.13';
        $this->author   = 'AGTI';
        $this->controllers = array('payment', 'validation');

        $this->bootstrap = true;
        
        parent::__construct();

        $this->displayName = $this->trans('Custom payment modes', [], 'Modules.Agpaymentmodes.Admin');
        $this->description = $this->trans('Offer custom payment methods such as check, bank transfer, bank deposit, cash, and more.', [], 'Modules.Agpaymentmodes.Admin');
    }

    
    /**
     * Build configuration URL:
     * - PS 9+: Symfony AdminModulesSf route
     * - PS <= 8: Legacy AdminModules controller with configure param
     */
    protected function getModuleConfigureUrl(array $extraParams = [])
    {
        $isPs9 = version_compare(_PS_VERSION_, '9.0.0', '>=');

        if ($isPs9) {
            // Symfony route (includes CSRF in _token query param)
            $url = $this->context->link->getAdminLink('AdminModulesSf', true, [
                'route' => 'admin_module_configure_action',
                'module_name' => $this->name,
            ]);
        } else {
            // Legacy AdminModules controller
            $url = $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
            ]);
        }

        if (!empty($extraParams)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($extraParams);
        }

        return $url;
    }
    
    public function isUsingNewTranslationSystem()
    {
        return true;
    }
    

    public function hookDisplayHeader()
    {
        // Só executa no checkout (OrderController) - check PRIMEIRO para evitar auth()
        if (!($this->context->controller instanceof OrderController)) {
            return;
        }

        if (!$this->active) {
            return;
        }

        //por hora o campo para troco só é compatível com a versão 1.7 do PS
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->addCss(_PS_MODULE_DIR_ . $this->name . '/views/css/front.css');
            $this->context->controller->addJs(_PS_MODULE_DIR_ . $this->name . '/views/js/front.js');
            $this->context->controller->addJs(_PS_MODULE_DIR_ . $this->name . '/views/js/agpaymentmodes_checkout.js');
            
            Media::addJsDef(array(
                'agpaymentmodes_url_save_message' => array($this->context->link->getModuleLink('agpaymentmodes', 'savemessage'))
            ));
        }
    }

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $payment_modes = AgPaymentModesMode::getActives();
        $qtt = count($payment_modes);

        $options = array();
        for ($i=0; $i<$qtt; $i++) {
            if (!$this->context->cart->isVirtualCart() && !$payment_modes[$i]->isAvailableForCarrier($this->context->cart->id_carrier)) {
                continue;
            }

            /** @var Cart */
            $cart = Context::getContext()->cart;
        
            $this->context->smarty->assign(array(
                'payment_mode' => $payment_modes[$i],
                'context' => $this->context,
                'installments' => $this->displaySimulation($payment_modes[$i],$cart->getOrderTotal()),
                'form_action' => $this->context->link->getModuleLink($this->name, 'payment', array('id_agpaymentmode' => $payment_modes[$i]->id), true)
            ));
        
            $additional_info = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/hook/additional_info.tpl');

            $newOption = new PaymentOption();
            $newOption->setCallToActionText($payment_modes[$i]->name[$this->context->language->id])
                      ->setForm($additional_info)
                      ->setLogo($payment_modes[$i]->getImageUrl());

            $options[] = $newOption;
        }

        return $options;
    }


    public function hookPayment()
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/front.css');

        $payment_modes = AgPaymentModesMode::getActives();
        $qtt = count($payment_modes);

        for ($i=0; $i<$qtt; $i++) {
            $payment_modes[$i]->name = $payment_modes[$i]->name[$this->context->language->id];
            $payment_modes[$i]->additional_info = $payment_modes[$i]->additional_info[$this->context->language->id];
            $payment_modes[$i]->confirmation_message = $payment_modes[$i]->confirmation_message[$this->context->language->id];
            if ($payment_modes[$i]->image) {
                $payment_modes[$i]->image = $payment_modes[$i]->getImageUrl();
            }
        }

        $this->context->smarty->assign(array(
            'payment_modes' => $payment_modes
        ));
        
        return $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/hook/payment.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $id_lang = (int) Context::getContext()->language->id;

        // Resolve Order instance from various possible sources
        $order = null;
        if (isset($params['order']) && $params['order'] instanceof Order) {
            $order = $params['order'];
        } elseif (isset($params['objOrder']) && $params['objOrder'] instanceof Order) {
            $order = $params['objOrder'];
        } else {
            $idOrder = (int) Tools::getValue('id_order');
            if ($idOrder) {
                $maybeOrder = new Order($idOrder);
                if (Validate::isLoadedObject($maybeOrder)) {
                    $order = $maybeOrder;
                }
            }
        }

        // If we can't determine the order, abort gracefully
        if (!$order) {
            return '';
        }

        $payment_mode = new AgPaymentModesMode((int) Tools::getValue('id_agpaymentmode'), $id_lang);

        if (!Validate::isLoadedObject($payment_mode) || !$payment_mode->enabled) {
            return '';
        }

        $priceFormatter = new PriceFormatter();
        $currency = Currency::getCurrencyInstance((int) $order->id_currency);
        $replaces = [
            '{order_price}' => $priceFormatter->format((float) $order->getOrdersTotalPaid(), $currency),
            '{order_price_unformatted}' => round((float) $order->getOrdersTotalPaid(), 2),
        ];

        $instructions = isset($payment_mode->confirmation_message) && $payment_mode->confirmation_message
            ? $payment_mode->confirmation_message
            : (isset($payment_mode->additional_info) ? $payment_mode->additional_info : '');

        foreach ($replaces as $key => $replace) {
            $instructions = str_replace($key, $replace, $instructions);
        }

        $this->context->smarty->assign([
            'instructions' => $instructions,
            'payment_mode' => $payment_mode,
        ]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/hook/payment_return.tpl');
    }

    protected function createPaymentModeFromForm()
    {
        $paymentmode = new AgPaymentModesMode(Tools::getValue('id_agpaymentmode'));

        $paymentmode->name = array();
        $paymentmode->confirmation_message = array();

        $new_image = self::uploadPaymentModeImage();
        
        if ($new_image) {
            $paymentmode->image = $new_image;
        }

        foreach (Language::getLanguages() as $lang) {
            $paymentmode->name[$lang['id_lang']] = Tools::getValue('name_' . $lang['id_lang']);
            $paymentmode->confirmation_message[$lang['id_lang']] = Tools::getValue('confirmation_message_' . $lang['id_lang']);
            $paymentmode->additional_info[$lang['id_lang']] = Tools::getValue('additional_info_' . $lang['id_lang']);
            $paymentmode->input_label[$lang['id_lang']] = Tools::getValue('input_label_' . $lang['id_lang']);
        }

        $paymentmode->enabled = Tools::getValue('enabled');
        $paymentmode->mapped_status = Tools::getValue('mapped_status');
        $paymentmode->price_variation = Tools::getValue('price_variation');
        $paymentmode->ask_for_input = Tools::getValue('ask_for_input');

        $paymentmode->installment_enabled = Tools::getValue('installment_enabled');
        $paymentmode->installment_max = Tools::getValue('installment_max');
        $paymentmode->installment_min_value = Tools::getValue('installment_min_value');
        
        if (!$paymentmode->save()) {
            throw new Exception(Db::getInstance()->getMsgError());
        }

    
        if($paymentmode->installment_enabled){
          
            for ($i = 0; $i < $paymentmode->installment_max; $i++) {
                $paymentmode->setFixexInterestRatio(
                    $i + 1,
                    Tools::getValue('installment_fixed_tax_' . $i, '0.0')
                );
            }
            
        }
        $this->paymentmode = $paymentmode;
    }

    protected function updatePaymentModeCarriers()
    {
        $carriers = Carrier::getCarriers((int)Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $carrier_ids = array();
        foreach ($carriers as $carrier) {
            $carrier_ids[] = $carrier['id_reference'];
        }

        foreach ($carrier_ids as $id_carrier) {
            //se a transportadora foi ativada agora, cria o registro na tabela adequada
            if (Tools::getIsSet('agcustomers_carrier_' . $id_carrier . '_on') && !$this->paymentmode->isAvailableForCarrier($id_carrier)) {
                $this->paymentmode->makeAvailableForCarrier($id_carrier);
            } elseif (!Tools::getIsSet('agcustomers_carrier_' . $id_carrier . '_on') && $this->paymentmode->isAvailableForCarrier($id_carrier)) {
                $this->paymentmode->makeUnavailableForCarrier($id_carrier);
            }
        }
    }

    /**
     *  Trata upload das imagens de uma forma de pagamento
     */

    protected function uploadPaymentModeImage()
    {
        //salvamento da imagem
        $pic = new AgPaymentModesPic();
        if (empty($_FILES['image']['name'])) {
            return;
        }

        if ($_FILES['image']['error']) {
            throw new Exception($this->trans('Error uploading image', [], 'Modules.Agpaymentmodes.Admin'));
        }

        if (!$pic->open($_FILES['image']['tmp_name'])) {
            throw new Exception(
                $this->trans('Error loading the uploaded file. Is it really an image?', [], 'Modules.Agpaymentmodes.Admin')
            );
        }

        $image_name = uniqid();
        $pic->resize(array('width' => 100));
        $pic->save(_PS_MODULE_DIR_ . $this->name . "/views/img/$image_name.png");

        $pic->clear();

        return $image_name;
    }

    /**
     *  Exibe o formulário para cadastro de formas de pagamento
     */
    protected function renderNewPaymentModeTab($id_agpaymentmode = 0)
    {
        if (Tools::isSubmit('agpaymentmodes-save') || Tools::isSubmit('submit' . $this->name)) {
            try {
                $this->createPaymentModeFromForm();
                $this->updatePaymentModeCarriers();

                $conf = $id_agpaymentmode ? 4 : 3;
                Tools::redirectAdmin($this->getModuleConfigureUrl(['conf' => $conf]));
                exit();
            } catch (Exception $e) {
                $this->context->controller->errors[] = $e->getMessage();
            }
        }

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module          = $this;
        $helper->name_controller = $this->name;

        $isPs9 = version_compare(_PS_VERSION_, '9.0.0', '>=');
        if ($isPs9) {
            // Post to the Symfony route (includes _token already). Do not append legacy token param.
            $helper->token        = null;
            $helper->currentIndex = $this->getModuleConfigureUrl();
            $sep = (strpos($helper->currentIndex, '?') !== false ? '&' : '?');
            // Preserve the current Symfony CSRF token when posting back
            $sfToken = Tools::getValue('_token');
            if (!empty($sfToken)) {
                $helper->currentIndex .= $sep . '_token=' . urlencode($sfToken);
                $sep = '&';
            }
        } else {
            // Legacy AdminModules target
            $helper->token        = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
            $sep = '&';
        }
        if (!$id_agpaymentmode) {
            $helper->currentIndex .= $sep . 'action=new';
        } else {
            $helper->currentIndex .= $sep . 'action=edit&id_agpaymentmode=' . (int) $id_agpaymentmode;
        }

        // title and Toolbar
        $helper->title          = $this->displayName;
        $helper->show_toolbar   = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action  = 'submit' . $this->name;
        $helper->show_cancel_button = true;
    $helper->back_url = $this->getModuleConfigureUrl();

        //para multi-idiomas
        $languages = Language::getLanguages(false);
        foreach ($languages as &$_language) {
            $_language['is_default'] = false;
        }

        $helper->languages = $languages;
        $helper->default_form_language = $this->context->language->id;

        $order_statuses = OrderState::getOrderStates($this->context->language->id);

        $statuses = [
            [
                'id_option' => 0,
                'name'       => $this->trans('Select the default status', [], 'Modules.Agpaymentmodes.Admin')
            ],
        ];

        foreach ($order_statuses as $order_status)
        {
            $statuses[] = array(
                'id_option' => $order_status['id_order_state'],
                'name'      => $order_status['name'],
            );
        }

        $fields = array();
        $fields[0] = array();
        $fields[0]['form'] = array(
            'legend'      => array(
                'title' => $this->trans('Create new payment mode', [], 'Modules.Agpaymentmodes.Admin'),
            ),
            'input'       => array(
                array(
                    'type'  => 'text',
                    'label' => $this->trans('Name', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'  => 'name',
                    'lang' => true,
                    'required' => true,
                    'maxlength' => 50,
                    'col' => 3
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->trans('Additional information', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'  => 'additional_info',
                    'lang' => true,
                    'required' => true,
                    'maxlength' => 75,
                    'col' => 4
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->trans('Discount', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'  => 'price_variation',
                    'desc' => $this->trans('E.g. 10%; 3.10', [], 'Modules.Agpaymentmodes.Admin'),
                    'maxlength' => 75,
                    'col' => 4
                ),
                array(
                    'type' => 'file',
                    'label' => $this->trans('Image', [], 'Modules.Agpaymentmodes.Admin'),
                    'name' => 'image',
                    'desc' => $this->trans('Width: 86px, use landscape images', [], 'Modules.Agpaymentmodes.Admin')
                ),
             array(
                    'type'  => 'textarea',
                    'tinymce' => true,
                    'label' => $this->trans('Confirmation message', [], 'Modules.Agpaymentmodes.Admin'),
                    'desc' => '{order_price} - ' . $this->trans('Order value', [], 'Modules.Agpaymentmodes.Admin') . '<br/>' . '{order_price_unformatted} - ' . $this->trans('Order value (unformatted)', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'  => 'confirmation_message',
                    'autoload_rte' => true,
                    'lang' => true,
                    'required' => true
                ),
                array(
                    'type'   => 'switch',
                    'label'  => $this->trans('Enabled', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'   => 'enabled',
                    'values' => array(
                        array(
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Yes', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                        array(
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('No', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                    ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => $this->trans('Default status', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'    => 'mapped_status',
                    'options' => [
                        'query' => $statuses,
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ],
                ),
                array(
                    'type'   => 'switch',
                    'label'  => $this->trans('Ask for information', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'   => 'ask_for_input',
                    'values' => array(
                        array(
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Yes', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                        array(
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('No', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                    ),
                ),
                array(
                    'type'   => 'text',
                    'label'  => $this->trans('Information label', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'   => 'input_label',
                    'lang' => true
                ),
            ),
            'submit' => [
                'title' => $this->trans('Save', [], 'Modules.Agpaymentmodes.Admin'),
                'name'  => 'agpaymentmodes-save',
            ],
        );

        $payment_mode = new AgPaymentModesMode($id_agpaymentmode);

        $helper->fields_value['name'] = $payment_mode->name;
        $helper->fields_value['confirmation_message'] = $payment_mode->confirmation_message;
        $helper->fields_value['enabled'] = $payment_mode->enabled;
        $helper->fields_value['mapped_status'] = $payment_mode->mapped_status;
        $helper->fields_value['additional_info'] = $payment_mode->additional_info;

        if ($payment_mode->price_variation) {
            $helper->fields_value['price_variation'] = $payment_mode->price_variation;
        } else {
            $helper->fields_value['price_variation'] = '0.00';
        }

        $helper->fields_value['ask_for_input'] = $payment_mode->ask_for_input;
        $helper->fields_value['input_label'] = $payment_mode->input_label;

        if (!$id_agpaymentmode) {
            foreach (Language::getLanguages() as $lang) {
                $helper->fields_value['name'][$lang['id_lang']] = '';
                $helper->fields_value['confirmation_message'][$lang['id_lang']] = '';
                $helper->fields_value['additional_info'] = '';
                $helper->fields_value['input_label'] = '';
            }
        }

        $fields[1]['form'] = array(
            'legend' => array(
                'title' => $this->trans('Installments', [], 'Modules.Agpaymentmodes.Admin'),
            ),
            'input' => array(
                array(
                    'type'   => 'switch',
                    'label'  => $this->trans('Installments', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'   => 'installment_enabled',
                    'values' => array(
                        array(
                            'id'    => 'installment_enabled_on',
                            'value' => 1,
                            'label' => $this->trans('Yes', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                        array(
                            'id'    => 'installment_enabled_off',
                            'value' => 0,
                            'label' => $this->trans('No', [], 'Modules.Agpaymentmodes.Admin'),
                        ),
                    ),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->trans('Maximum number of installments', [], 'Modules.Agpaymentmodes.Admin'),
                    'name'  => 'installment_max',
                    'maxlength' => 10,
                    'col' => 4
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->trans('Minimum installment amount', [], 'Modules.Agpaymentmodes.Admin'),
                    'desc' => $this->trans('E.g. 3.10', [], 'Modules.Agpaymentmodes.Admin'),
                    'prefix' => '$',
                    'name'  => 'installment_min_value',
                    'maxlength' => 10,
                    'col' => 4
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', [], 'Modules.Agpaymentmodes.Admin'),
                'name'  => 'agpaymentmodes-save',
            )
        );
        $helper->fields_value['installment_enabled'] = $payment_mode->installment_enabled;
        $helper->fields_value['installment_max'] = $payment_mode->installment_max;
        $helper->fields_value['installment_min_value'] = $payment_mode->installment_min_value;
        
        $InterestRatio  = new AgPaymentModesFixedInterestRatio();
        $interest_ratios = $InterestRatio->getAllByPaymentModeId($id_agpaymentmode);

        $radios = [];
        foreach ($interest_ratios as $key => $interest_ratio) {
            $radios[$interest_ratio['number_of_payments']] = $interest_ratio['interest_ratio'];
        }
        Media::addJsDef([
            'interest_ratio' => $radios,
        ]);

        $carriers = Carrier::getCarriers((int)Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);

        //renderiza o painel para seleção das transportadoras disponíveis para esta forma de pagamento
        $fields[2]['form'] = array(
            'legend' => array(
                'title' => $this->trans('Carriers selection', [], 'Modules.Agpaymentmodes.Admin'),
            ),
            'input' => array(
            ),
            'submit' => array(
                'title' => $this->trans('Save', [], 'Modules.Agpaymentmodes.Admin'),
                'name'  => 'agpaymentmodes-save',
            )
        );

        foreach ($carriers as $carrier){
            $fields[2]['form']['input'][] = array(
                'type' => 'checkbox',
                'name' => 'agcustomers_carrier_' . $carrier['id_reference'],
                'values' => array(
                    'query' => array(
                        array(
                            'id' => 'on',
                            'name' => $carrier['name'],
                            'val' => '1'
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name'
                )
            );

            //verifica se a opção de pagamento já está disponível para esta transportadora
            $payment_mode = new AgPaymentModesMode($id_agpaymentmode);

            if (!$id_agpaymentmode) {
                $helper->fields_value['agcustomers_carrier_' . $carrier['id_reference'] . '_on'] = 1;                
            } else {
                $helper->fields_value['agcustomers_carrier_' . $carrier['id_reference'] . '_on'] = $payment_mode->isAvailableForCarrier($carrier['id_carrier']) ? 1 : 0;
            }
        }

        return $helper->generateForm($fields);
    }

    protected function processDelete()
    {
        if (Tools::getValue('id_agpaymentmode')) {
            try {
                $payment_mode = new AgPaymentModesMode(Tools::getValue('id_agpaymentmode'));
                if ($payment_mode->delete()) {
                    Tools::redirectAdmin($this->getModuleConfigureUrl(['conf' => 1]));
                }
            } catch (Exception $e) {
                $this->context->controller->errors[] = $e->getMessage();
            }
        }
    }

    protected function renderConfigTab()
    {
        //extrai informações multi-idiomas
        $payment_modes = AgPaymentModesMode::getAll();

        if (is_array($payment_modes))  {
            $qtt = count($payment_modes);
            for ($i=0; $i<$qtt; $i++) {
                $payment_modes[$i]->name = $payment_modes[$i]->name[$this->context->language->id];
                $payment_modes[$i]->additional_info = $payment_modes[$i]->additional_info[$this->context->language->id];
                $payment_modes[$i]->confirmation_message = $payment_modes[$i]->confirmation_message[$this->context->language->id];
                $payment_modes[$i]->image = $payment_modes[$i]->getImageUrl();

                $status = new OrderState($payment_modes[$i]->mapped_status);
                $payment_modes[$i]->mapped_status = $status->name[$this->context->language->id];
                if ($payment_modes[$i]->ask_for_input) {
                    $payment_modes[$i]->input_label = $payment_modes[$i]->input_label[$this->context->language->id];
                } else {
                    $payment_modes[$i]->input_label = '';
                }
            }
        }

        $formUrl = $this->getModuleConfigureUrl();
        $this->context->smarty->assign(array(
            "rows" => $payment_modes,
            'link' => $this->context->link,
            'license' => $this->getLicenseKey(),
            'is_authenticated' => true,
            'form_action' => $formUrl,
            'form_action_sep' => (strpos($formUrl, '?') !== false ? '&' : '?')
        ));

        $html = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/admin/configuration.tpl');
        return $html;
    }

    public function getContent()
    {
        $this->context->controller->addJs(array(
            '//cdn.jsdelivr.net/bluebird/3.5.0/bluebird.min.js',
            _PS_MODULE_DIR_ . $this->name . '/views/js/configuration.js'
        ));

        $this->context->controller->addCss(_PS_MODULE_DIR_ . $this->name . '/views/css/custom-table.css');

        // JS i18n for admin
        Media::addJsDef([
            'agpaymentmodes_admin_i18n' => [
                'confirm_delete' => $this->trans('Are you sure?', [], 'Modules.Agpaymentmodes.Admin'),
                'fixed_interest_label' => $this->trans('Interest per number of payments (%)', [], 'Modules.Agpaymentmodes.Admin'),
            ],
        ]);

        if (Tools::getValue('action') === 'new') {
            return self::renderNewPaymentModeTab();
        } elseif (Tools::getValue('action') === 'remove') {
            return self::processDelete();
        } elseif (Tools::getValue('action') === 'edit') {
            return self::renderNewPaymentModeTab(Tools::getValue('id_agpaymentmode'));
        } else {
            return self::renderConfigTab();
        }
    }

    public function hookActionObjectOrderPaymentAddAfter($params)
    {
        $obj = $params['object'];

        $orders = Order::getByReference($obj->order_reference);
        $order = $orders->getFirst();

        $obj->payment_method = $order->payment;
        $obj->update();
    }

     public function displaySimulation($payment_mode,$price)
    {
        if ($price < 0.01) {
            return false;
        }

        $payment_modes = $payment_mode->getValue($payment_mode,$price);

        if (!count($payment_modes)) {
            return;
        }

        usort($payment_modes, function ($a, $b) {
            return isset($a['values'][count($a['values']) - 1]) && isset($b['values'][count($b['values']) - 1]) ? $a['values'][count($a['values']) - 1]['value_each_payment'] > $b['values'][count($b['values']) - 1]['value_each_payment'] : '';
        });
        // Add formatted prices to avoid relying on Smarty displayPrice tag (which may be unavailable in some contexts)
        try {
            $priceFormatter = new PriceFormatter();
            $currency = $this->context->currency ?: Currency::getCurrencyInstance((int)$this->context->cart->id_currency);
            foreach ($payment_modes as &$row) {
                if (isset($row['value_each_payment'])) {
                    $row['value_each_payment_formatted'] = $priceFormatter->format((float)$row['value_each_payment'], $currency);
                }
                if (isset($row['total_to_pay'])) {
                    $row['total_to_pay_formatted'] = $priceFormatter->format((float)$row['total_to_pay'], $currency);
                }
            }
            unset($row);
        } catch (\Throwable $e) { /* ignore formatting errors */ }

        return $payment_modes;
    }
}
