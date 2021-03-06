<?php

/**
 * This class registers some handlers for shop settings
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Settings extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:settings:update')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Settings');
            $oNavGroup->setIcon('fa-wrench');
            $oNavGroup->addAction('Shop');
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');

        // --------------------------------------------------------------------------

        //  @todo Move this into a common constructor
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Pass data to the views
        $this->data['shopName'] = $this->shopName;
        $this->data['shopUrl']  = $this->shopUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['update'] = 'Can update settings';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Shop settings
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:shop:settings:update')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load models
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_shipping_driver_model');
        $this->load->model('shop/shop_payment_gateway_model');
        $this->load->model('shop/shop_tax_rate_model');
        $this->load->model('shop/shop_product_model');

        $oDb              = Factory::service('Database');
        $oAppSettingModel = Factory::model('AppSetting');
        $oCountryModel    = Factory::model('Country');
        $oCurrencyModel   = Factory::model('Currency', 'nailsapp/module-shop');
        $oFeedModel       = Factory::model('Feed', 'nailsapp/module-shop');

        // --------------------------------------------------------------------------

        $oShopPageModel = Factory::model('Page', 'nailsapp/module-shop');
        $this->data['pages'] = $oShopPageModel->getAll();
        if (isModuleEnabled('nailsapp/module-cms')) {
            $oPageModel = Factory::model('Page', 'nailsapp/module-cms');
            $this->data['cmsPages'] = $oPageModel->getAllFlat(
                null,
                null,
                [
                    'where' => [
                        '`published_hash` IS NOT NULL'
                    ]
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  Settings keys
            $sKeyFeedDriver = $oFeedModel->getSettingKey();

            //  Validation
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('firstFinancialYearEndDate', '', 'valid_date');
            $oFormValidation->set_rules($sKeyFeedDriver, '', '');
            $oFormValidation->set_message('valid_date', lang('fv_valid_date'));

            if ($oFormValidation->run()) {

                try {

                    $aSettings = array(

                        //  General Settings
                        'maintenance_enabled'                   => trim($this->input->post('maintenance_enabled')),
                        'maintenance_title'                     => trim($this->input->post('maintenance_title')),
                        'maintenance_body'                      => trim($this->input->post('maintenance_body')),
                        'name'                                  => $this->input->post('name'),
                        'url'                                   => $this->input->post('url'),
                        'price_exclude_tax'                     => $this->input->post('price_exclude_tax'),
                        'enable_external_products'              => (bool) $this->input->post('enable_external_products'),
                        'firstFinancialYearEndDate'             => $this->input->post('firstFinancialYearEndDate'),
                        'invoice_company'                       => $this->input->post('invoice_company'),
                        'invoice_company'                       => $this->input->post('invoice_company'),
                        'invoice_address'                       => $this->input->post('invoice_address'),
                        'invoice_vat_no'                        => $this->input->post('invoice_vat_no'),
                        'invoice_company_no'                    => $this->input->post('invoice_company_no'),
                        'invoice_footer'                        => $this->input->post('invoice_footer'),
                        'warehouse_collection_enabled'          => (bool) $this->input->post('warehouse_collection_enabled'),
                        'warehouse_addr_addressee'              => $this->input->post('warehouse_addr_addressee'),
                        'warehouse_addr_line1'                  => $this->input->post('warehouse_addr_line1'),
                        'warehouse_addr_line2'                  => $this->input->post('warehouse_addr_line2'),
                        'warehouse_addr_town'                   => $this->input->post('warehouse_addr_town'),
                        'warehouse_addr_postcode'               => $this->input->post('warehouse_addr_postcode'),
                        'warehouse_addr_state'                  => $this->input->post('warehouse_addr_state'),
                        'warehouse_addr_country'                => $this->input->post('warehouse_addr_country'),
                        'warehouse_collection_delivery_enquiry' => (bool) $this->input->post('warehouse_collection_delivery_enquiry'),
                        'delivery_url'                          => $this->input->post('delivery_url'),
                        'delivery_text'                         => $this->input->post('delivery_text'),
                        'page_brand_listing'                    => $this->input->post('page_brand_listing'),
                        'page_category_listing'                 => $this->input->post('page_category_listing'),
                        'page_collection_listing'               => $this->input->post('page_collection_listing'),
                        'page_range_listing'                    => $this->input->post('page_range_listing'),
                        'page_sale_listing'                     => $this->input->post('page_sale_listing'),
                        'page_tag_listing'                      => $this->input->post('page_tag_listing'),

                        //  Browse Settings
                        'expand_variants'          => (bool) $this->input->post('expand_variants'),
                        'default_product_per_page' => $this->input->post('default_product_per_page'),
                        'default_product_sort'     => $this->input->post('default_product_sort'),
                        'area_product_footer'      => $this->input->post('area_product_footer'),

                        //  Skin settings
                        'skin_front'    => $this->input->post('skin_front'),
                        'skin_checkout' => $this->input->post('skin_checkout'),

                        //  Payment gteway settings
                        'enabled_payment_gateways' => array_filter((array) $this->input->post('enabled_payment_gateways')),

                        //  Shipping driver settings
                        'enabled_shipping_driver' => $this->input->post('enabled_shipping_driver'),

                        //  Currency Settings
                        'additional_currencies' => $this->input->post('additional_currencies'),

                        //  Pages
                        'pages' => array(),
                    );

                    if ($this->input->post('base_currency')) {
                        $aSettings['base_currency'] = $this->input->post('base_currency');
                    }

                    if ($this->input->post('pages')) {

                        $aPages = $this->input->post('pages');

                        foreach ($this->data['pages'] as $sSlug => $sLabel) {

                            $aSettings['pages'][$sSlug] = array(
                                'cmsPageId' => !empty($aPages[$sSlug]['cmsPageId']) ? $aPages[$sSlug]['cmsPageId'] : null,
                                'body' => !empty($aPages[$sSlug]['body']) ? $aPages[$sSlug]['body'] : null
                            );
                        }
                    }

                    $aSettingsEncrypted = array(

                        //  Currency settings
                        'openexchangerates_app_id' => $this->input->post('openexchangerates_app_id')
                    );

                    // --------------------------------------------------------------------------

                    //  Sanitize shop url
                    $aSettings['url'] .= substr($aSettings['url'], -1) != '/' ? '/' : '';

                    //  Sanitize default_product_per_page
                    if (is_numeric($aSettings['default_product_per_page'])) {
                        $aSettings['default_product_per_page'] = (int) $aSettings['default_product_per_page'];
                    }

                    // --------------------------------------------------------------------------

                    $oDb->trans_begin();

                    //  Normal settings
                    if (!$oAppSettingModel->set($aSettings, 'nailsapp/module-shop')) {
                        throw new NailsException($oAppSettingModel->lastError(), 1);
                    }

                    //  Encrypted settings
                    if (!$oAppSettingModel->set($aSettingsEncrypted, 'nailsapp/module-shop', null, true)) {
                        throw new NailsException($oAppSettingModel->lastError(), 1);
                    }

                    //  Drivers & Skins
                    $oFeedModel->saveEnabled($this->input->post($sKeyFeedDriver));

                    $oDb->trans_commit();
                    $this->data['success'] = 'Shop settings were saved.';

                    // --------------------------------------------------------------------------

                    //  Rewrite routes
                    $oRoutesModel = Factory::model('Routes');
                    if (!$oRoutesModel->update()) {
                        $this->data['warning']  = '<strong>Warning:</strong> while the shop settings were updated, ';
                        $this->data['warning'] .= 'the routes file could not be updated. The shop may not behave ';
                        $this->data['warning'] .= 'as expected.';
                    }

                    // --------------------------------------------------------------------------

                    /**
                     * If there are multiple currencies and an Open Exchange Rates App ID provided
                     * then attempt a sync
                     */

                    $bHasAdditionalCurrency  = !empty($aSettings['additional_currencies']);
                    $bHasOpenExchangeRatesId = !empty($aSettingsEncrypted['openexchangerates_app_id']);
                    if ($bHasAdditionalCurrency && $bHasOpenExchangeRatesId) {

                        //  Force a refresh of the settings
                        appSetting(null, 'nailsapp/module-shop', true);

                        if (!$oCurrencyModel->sync()) {
                            $this->data['message']  = '<strong>Warning:</strong> an attempted sync with Open Exchange ';
                            $this->data['message'] .= 'Rates service failed with the following reason: ';
                            $this->data['message'] .= $oCurrencyModel->lastError();
                        }
                    }

                } catch (\Exception $e) {

                    $oDb->trans_rollback();
                    $this->data['error'] = 'There was a problem saving settings. ' . $e->getMessage();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings']         = appSetting(null, 'nailsapp/module-shop', true);
        $this->data['currencies']       = $oCurrencyModel->getAll();
        $this->data['tax_rates']        = $this->shop_tax_rate_model->getAll();
        $this->data['tax_rates_flat']   = $this->shop_tax_rate_model->getAllFlat();
        $this->data['countries_flat']   = $oCountryModel->getAllFlat();
        $this->data['continents_flat']  = $oCountryModel->getAllContinentsFlat();
        array_unshift($this->data['tax_rates_flat'], 'No Tax');

        $oSkinModel = Factory::model('Skin', 'nailsapp/module-shop');

        //  "Front of house" skins
        $this->data['skins_front']         = $oSkinModel->getAvailable('front');
        $this->data['skin_front_selected'] = $oSkinModel->getEnabled('front');

        //  "Checkout" skins
        $this->data['skins_checkout']         = $oSkinModel->getAvailable('checkout');
        $this->data['skin_checkout_selected'] = $oSkinModel->getEnabled('checkout');

        //  Count the number of products (including deleted) - base currency is locked if > 1
        $this->data['productCount'] = $this->shop_product_model->countAll(null, true);

        //  Payment Drivers
        $this->data['payment_gateways']         = $this->shop_payment_gateway_model->getAvailable();
        $this->data['payment_gateways_enabled'] = $this->shop_payment_gateway_model->getEnabled();

        //  Shipping Drivers
        $this->data['shipping_drivers']         = $this->shop_shipping_driver_model->getAvailable();
        $this->data['shipping_drivers_enabled'] = $this->shop_shipping_driver_model->getEnabled();

        // --------------------------------------------------------------------------

        //  Load assets
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.settings.min.js', 'nailsapp/module-shop');
        $oAsset->library('MUSTACHE');
        $oAsset->inline('<script>_nails_settings = new NAILS_Admin_Shop_Settings();</script>');

        // --------------------------------------------------------------------------

        //  Set page title
        $this->data['page']->title = 'Settings &rsaquo; Shop';

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    public function shop_skin()
    {
        $oSkinModel = Factory::model('Skin', 'nailsapp/module-shop');
        $sType      = $this->input->get('type');
        $sSlug      = $this->input->get('slug');

        $this->data['skin'] = $oSkinModel->get($sType, $sSlug);
        if (empty($this->data['skin']) || empty($this->data['skin']->data->settings)) {
            show_404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Shop Skin Configuration &rsaquo; ' . $this->data['skin']->name;
        $this->data['isModal']     = $this->input->get('isModal');

        // --------------------------------------------------------------------------

        Helper::loadView('shop_skin');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Payment Gateway settings
     * @return void
     */
    public function shop_pg()
    {
        //  Check if valid gateway
        $this->load->model('shop/shop_payment_gateway_model');

        $gateway   = strtolower($this->input->get('gateway'));
        $available = $this->shop_payment_gateway_model->isAvailable($gateway);

        if ($available) {

            $params = $this->shop_payment_gateway_model->getDefaultParameters($gateway);

            $this->data['params']       = $params;
            $this->data['gateway_name'] = ucwords(str_replace('_', ' ', $gateway));
            $this->data['gateway_slug'] = $this->shop_payment_gateway_model->getCorrectCasing($gateway);

            //  Handle POST
            if ($this->input->post()) {

                $aRules = array();

                //  Common
                $aRules['omnipay_' . $this->data['gateway_slug'] . '_customise_label'] = '';
                $aRules['omnipay_' . $this->data['gateway_slug'] . '_customise_img']   = '';


                //  Gateway specific
                foreach ($params as $key => $value) {

                    if ($key == 'testMode') {

                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_' . $key] = '';

                    } else {

                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_' . $key] = 'required';
                    }
                }

                //  Additional params (manually added in view)
                switch ($gateway) {

                    case 'stripe':
                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_publishableKey'] = '';
                        break;

                    case 'paypal_express':
                        //  Defining these here despite being default parameters to make them not required.
                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_brandName']      = '';
                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_headerImageUrl'] = '';
                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_logoImageUrl']   = '';
                        $aRules['omnipay_' . $this->data['gateway_slug'] . '_borderColor']    = '';
                        break;
                }

                //  Format rules into format accepted by validation class
                $aRulesFV = array();
                foreach ($aRules as $sKey => $sRules) {
                    $aRulesFV[] = array(
                        'field' => $sKey,
                        'label' => '',
                        'rules' => $sRules
                    );
                }

                $oFormValidation = Factory::service('FormValidation');
                $oFormValidation->set_rules($aRulesFV);

                $oFormValidation->set_message('required', lang('fv_required'));

                if ($oFormValidation->run()) {

                    $settings           = array();
                    $settings_encrypted = array();

                    //  Customisation params
                    $settings['omnipay_' . $this->data['gateway_slug'] . '_customise_label'] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_customise_label');
                    $settings['omnipay_' . $this->data['gateway_slug'] . '_customise_img']   = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_customise_img');

                    //  Gateway params
                    foreach ($params as $key => $value) {

                        $settings_encrypted['omnipay_' . $this->data['gateway_slug'] . '_' . $key] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_' . $key);
                    }

                    //  Additional params
                    switch ($gateway) {

                        case 'stripe':
                            $settings_encrypted['omnipay_' . $this->data['gateway_slug'] . '_publishableKey'] = $this->input->post('omnipay_' . $this->data['gateway_slug'] . '_publishableKey');
                            break;
                    }

                    $oDb = Factory::service('Database');
                    $oDb->trans_begin();

                    $oAppSettingModel = Factory::model('AppSetting');

                    $bResult          = $oAppSettingModel->set($settings, 'nailsapp/module-shop', null, false);
                    $bResultEncrypted = $oAppSettingModel->set($settings_encrypted, 'nailsapp/module-shop', null, true);

                    if ($oDb->trans_status() !== false && $bResult && $bResultEncrypted) {

                        $oDb->trans_commit();
                        $this->data['success'] = '' . $this->data['gateway_name'] . ' Payment Gateway settings have been saved.';

                    } else {

                        $oDb->trans_rollback();
                        $this->data['error'] = 'There was a problem saving the ' . $this->data['gateway_name'] . ' Payment Gateway settings.';
                    }

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }

            //  Render the interface
            $this->data['page']->title = 'Shop Payment Gateway Configuration &rsaquo; ' . $this->data['gateway_name'];
            $this->data['isModal']     = $this->input->get('isModal');

            //  Load common assets
            $oAsset = Factory::service('Asset');
            $oAsset->load('nails.admin.settings.min.js', 'NAILS');

            $sMethodName = strtolower($gateway);
            $sMethodName = str_replace('_', ' ', $sMethodName);
            $sMethodName = ucwords($sMethodName);
            $sMethodName = str_replace(' ', '', $sMethodName);

            if (method_exists($this, 'shopPg' . ucfirst(strtolower($gateway)))) {

                //  Specific configuration form available
                $this->{'shopPg' . ucfirst(strtolower($gateway))}();

            } else {

                //  Show the generic gateway configuration form
                $this->shopPgGeneric($gateway);
            }

        } else {

            //  Bad gateway name
            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a generic Payment Gateway configuration interface
     * @return void
     */
    protected function shopPgGeneric()
    {
        Helper::loadView('shop_pg/generic');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for WorldPay
     * @return void
     */
    protected function shopPgWorldpay()
    {
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.settings.paymentgateway.worldpay.min.js', 'nailsapp/module-shop');
        $oAsset->inline('<script>_worldpay_config = new NAILS_Admin_Shop_Settings_PaymentGateway_WorldPay();</script>');

        // --------------------------------------------------------------------------

        Helper::loadView('shop_pg/worldpay');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for Stripe
     * @return void
     */
    protected function shopPgStripe()
    {
        //  Additional params
        Helper::loadView('shop_pg/stripe');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an interface specific for PayPal_Express
     * @return void
     */
    protected function shopPgPaypalExpress()
    {
        //  Additional params
        Helper::loadView('shop_pg/paypal_express');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Shipping Driver settings
     * @return void
     */
    public function shop_sd()
    {
        $this->load->model('shop/shop_shipping_driver_model');

        $this->data['driver'] = $this->shop_shipping_driver_model->get($this->input->get('driver'));

        if (empty($this->data['driver'])) {
            show_404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Shop Shipping Driver Configuration &rsaquo; ' . $this->data['driver']->name;
        $this->data['isModal']     = $this->input->get('isModal');

        // --------------------------------------------------------------------------

        Helper::loadView('shop_sd');
    }
}
