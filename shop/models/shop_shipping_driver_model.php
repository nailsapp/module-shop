<?php

/**
 * This model abstracts Shop Shipping Drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @todo        use base driver implementation
 */

use Nails\Factory;
use Nails\Shop\Exception\ShippingDriverException;

class Shop_shipping_driver_model
{
    protected $aAvailable;
    protected $oDriverConfig;
    protected $oDriver;

    // --------------------------------------------------------------------------

    const DEFAULT_DRIVER = 'nailsapp/driver-shop-shipping-flatrate';

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable = _NAILS_GET_DRIVERS('nailsapp/module-shop', 'shipping');

        //  Load the active shipping driver
        $sDriverSlug         = appSetting('enabled_shipping_driver', 'nailsapp/module-shop') ?: self::DEFAULT_DRIVER;
        $this->oDriverConfig = $this->get($sDriverSlug);

        if (empty($this->oDriverConfig)) {
            throw new ShippingDriverException(
                'Could not find driver "' . $sDriverSlug . '".',
                1
            );
        }

        $this->oDriver = _NAILS_GET_DRIVER_INSTANCE($this->oDriverConfig);

        if (empty($this->oDriver)) {
            throw new ShippingDriverException(
                'Failed to load shipping driver "' . $sDriverSlug . '".',
                2
            );
        }

        if (!($this->oDriver instanceof \Nails\Shop\Driver\ShippingBase)) {
            throw new ShippingDriverException(
                'Driver "' . $sDriverSlug . '" must extend \Nails\Shop\Driver\ShippingBase',
                3
            );
        }

        if (empty($this->defaultOption())) {
            throw new ShippingDriverException(
                'Driver "' . $sDriverSlug . '" must specify a default shipping option',
                4
            );
        }

        //  Apply driver configurations
        $aSettings = [
            'sSlug' => $this->oDriverConfig->slug,
        ];
        if (!empty($this->oDriverConfig->data->settings)) {
            $aSettings = array_merge(
                $aSettings,
                $this->extractDriverSettings(
                    $this->oDriverConfig->data->settings,
                    $this->oDriverConfig->slug
                )
            );
        }

        $this->oDriver->setConfig($aSettings);
    }

    // --------------------------------------------------------------------------

    /**
     * Recursively gets all the settings from the settings array
     *
     * @param  array  $aSettings The array of fieldsets and/or settings
     * @param  string $sSlug     The driver's slug
     *
     * @return array
     */
    protected function extractDriverSettings($aSettings, $sSlug)
    {
        $aOut = [];

        foreach ($aSettings as $oSetting) {

            //  If the object contains a `fields` property then consider this a field set and inception
            if (isset($oSetting->fields)) {

                $aOut = array_merge($aOut, $this->extractDriverSettings($oSetting->fields, $sSlug));

            } else {

                $sValue = appSetting($oSetting->key, $sSlug);
                if (is_null($sValue) && isset($oSetting->default)) {
                    $sValue = $oSetting->default;
                }
                $aOut[$oSetting->key] = $sValue;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available shipping drivers
     *
     * @param  boolean $refresh Fetch from refresh - skip the cache
     *
     * @return array
     */
    public function getAvailable($refresh = false)
    {
        return $this->aAvailable;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single driver
     *
     * @param  string $sSlug The driver's slug
     *
     * @return stdClass|boolean
     */
    public function get($sSlug)
    {
        $aShippingDrivers = $this->getAvailable();
        foreach ($aShippingDrivers as $oDriver) {
            if ($oDriver->slug == $sSlug) {
                return $oDriver;
            }
        }
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the config for the enabled driver
     * @return \stdClass
     */
    public function getEnabled()
    {
        return $this->oDriverConfig;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the shippable items from the basket object
     *
     * @param  object $basket The basket object
     *
     * @return array
     */
    private function getShippableItemsFromBasket($basket)
    {
        $aShippableItems = [];

        foreach ($basket->items as $item) {
            if (!empty($item->product->type->is_physical) && empty($item->variant->shipping->collection_only)) {
                $aShippableItems[] = $item;
            }
        }

        return $aShippableItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the available shipping options from the driver
     * @return array
     */
    public function options()
    {
        $aOut = [];

        //  If warehouse collection is enabled then add it as an option
        if (appSetting('warehouse_collection_enabled', 'nailsapp/module-shop')) {

            $aOut[] = [
                'slug'    => 'COLLECTION',
                'label'   => 'Collection',
                'default' => false,
            ];
        }

        return array_merge($aOut, $this->oDriver->options());
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the available shipping options, including the cost of
     * shipping the supplied basket
     *
     * @param \stdClass $oBasket The basket object
     *
     * @throws ShippingDriverException
     * @return array
     */
    public function optionsWithCost($oBasket)
    {
        /**
         * Ask the driver what the available options are, pass it the shippable items so it can
         * amend it's responses as necessary and work out the cost
         */

        $aShippableItems = $this->getShippableItemsFromBasket($oBasket);
        $aOptions        = $this->options();
        $oCurrencyModel  = Factory::model('Currency', 'nailsapp/module-shop');

        $aSlugs      = [];
        $bHasDefault = false;
        $aOut        = [];

        if ($oBasket->shipping->isPossible) {

            //  Test options
            foreach ($aOptions as &$aOption) {

                if (empty($aOption['slug'])) {
                    throw new ShippingDriverException('Each shipping option must provide a unique slug', 5);
                }

                if (in_array($aOption['slug'], $aSlugs)) {
                    throw new ShippingDriverException('"' . $aOption['slug'] . '" is not a unique shipping option slug', 6);
                }

                //  Can only have one default value, the first defined.
                if (!empty($aOption['default']) && $bHasDefault) {
                    $aOption['default'] = false;
                } elseif (!empty($aOption['default'])) {
                    $bHasDefault = true;
                }

                $aSlugs[] = $aOption['slug'];
            }

            //  Prepare each item
            foreach ($aOptions as &$aOption) {

                if ($aOption['slug'] !== 'COLLECTION') {

                    $oCost           = $this->oDriver->calculate($aShippableItems, $aOption['slug'], $oBasket);
                    $aOption['cost'] = $oCost->total;

                    if (is_int($aOption['cost']) || is_numeric($aOption['cost'])) {

                        $aOption['cost'] = (int) $aOption['cost'];

                    } else {

                        $aOption['cost'] = 0;
                    }

                } else {

                    $aOption['cost'] = 0;
                }

                $oTemp                 = new \stdClass();
                $oTemp->slug           = $aOption['slug'];
                $oTemp->label          = $aOption['label'];
                $oTemp->cost           = $oCurrencyModel->convertBaseToUser($aOption['cost']);
                $oTemp->cost_formatted = $aOption['cost'] > 0 ? $oCurrencyModel->formatUser($aOption['cost']) : 'FREE';
                $oTemp->default        = (bool) $aOption['default'];

                $aOut[] = $oTemp;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the desired option
     *
     * @param  string $sOptionSlug The desired option
     *
     * @return stdClass
     */
    public function getOption($sOptionSlug)
    {
        $aOptions = $this->options();
        foreach ($aOptions as $aOption) {
            if ($aOption['slug'] === $sOptionSlug) {
                return $aOption;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the default option slug
     * @return string
     */
    public function defaultOption()
    {
        $aOptions = $this->options();
        foreach ($aOptions as $aOption) {
            if (!empty($aOption['default'])) {
                return $aOption['slug'];
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a basket object and calculates the cost of shipping
     *
     * @param \stdClass $basket the basket object
     *
     * @return object
     * @throws ShippingDriverException
     */
    public function calculate($basket)
    {
        $oFree = (object) [
            'total_inc_tax' => 0,
            'total_ex_tax'  => 0,
            'tax'           => 0,
        ];

        // --------------------------------------------------------------------------

        //  If the shipping type is COLLECTION (a special type) then shipping is FREE
        if ($basket->shipping->option == 'COLLECTION') {
            return $oFree;
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return an object
         * consisting of two properties: `total`, an integer of the cost of shipping
         * which is in the base currency and `tax_rate`, a float describing the percentage
         * of tax to apply. It is passed an array of all shippable items (i.e., items who's
         * type marks them as `is_physical` and is not set to collect only`), as well as a
         * reference to the basket, should the driver need to know anything else about the order.
         */

        $aShippableItems = $this->getShippableItemsFromBasket($basket);
        $oCost           = $this->oDriver->calculate($aShippableItems, $basket->shipping->option, $basket);

        if (!property_exists($oCost, 'total') || $oCost->total < 0) {
            throw new ShippingDriverException(
                'The value returned from the shipping driver must specify an item cost greater than, or equal to 0.',
                7
            );
        }

        if (!property_exists($oCost, 'tax_rate') || $oCost->tax_rate < 0) {
            throw new ShippingDriverException(
                'The value returned from the shipping driver must specify a tax rate greater than, or equal to 0.',
                7
            );
        }

        // --------------------------------------------------------------------------

        /**
         * Calculate the shipping values, inclusive and exclusive of tax (respecting the
         * `price_exclude_tax` setting).
         */

        $oOut = (object) [
            'total_inc_tax' => 0,
            'total_ex_tax'  => 0,
            'tax'           => 0,
        ];

        if (appSetting('price_exclude_tax', 'nailsapp/module-shop')) {

            //  Prices exclude tax, so bump it on top
            $oOut->tax           = $oCost->total * $oCost->tax_rate;
            $oOut->total_inc_tax = $oCost->total + $oOut->tax;
            $oOut->total_ex_tax  = $oCost->total;

        } else {

            //  Prices include tax, so the tax value is what is removed from the total
            $oOut->tax           = ($oCost->tax_rate * $oCost->total) / (1 + $oCost->tax_rate);
            $oOut->tax           = round($oOut->tax, 0, PHP_ROUND_HALF_UP);
            $oOut->total_inc_tax = $oCost->total;
            $oOut->total_ex_tax  = $oCost->total - $oOut->tax;
        }

        return $oOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a product variant ID and works out what the shipping would be on it
     *
     * @param  integer $iVariantId  ID of the variant in question
     * @param  string  $sOptionSlug The slug for the shipping option to calculate for
     *
     * @throws ShippingDriverException
     * @return \stdClass
     */
    public function calculateVariant($iVariantId, $sOptionSlug = null)
    {
        $oFree = (object) [
            'total_inc_tax' => 0,
            'total_ex_tax'  => 0,
            'tax'           => 0,
        ];

        // --------------------------------------------------------------------------

        //  Use the default option
        if (is_null($sOptionSlug)) {
            $sOptionSlug = $this->defaultOption();
        }

        // --------------------------------------------------------------------------

        //  If the shipping type is COLLECTION (a special type) then shipping is FREE
        if ($sOptionSlug == 'COLLECTION') {
            return $oFree;
        }

        // --------------------------------------------------------------------------

        //  Check that we have a valid item
        //  @todo make this model a non-CI one when this line is replaced with a factory call
        $oItem = get_instance()->shop_product_model->getByVariantId($iVariantId);

        /**
         * If for whatever reason we can't find the product, or it isn't physical return
         * free (no charge)
         */

        if (!$oItem || empty($oItem->type->is_physical)) {
            return $oFree;
        }

        // --------------------------------------------------------------------------

        $oVariant = null;
        foreach ($oItem->variations as $oVariation) {
            if ($oVariation->id == $iVariantId) {
                if (!empty($oVariation->ship_collection_only)) {
                    //  Item is collect only, assume no charge for delivery
                    return $oFree;
                } else {
                    $oVariant = $oVariation;
                }
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return an object
         * consisting of two properties: `total`, an integer of the cost of shipping
         * which is in the base currency and `tax_rate`, a float describing the percentage
         * of tax to apply. It is passed the variant provided it is shippable (i.e., product
         * type marks them as `is_physical` and is not set to collect only`).
         */


        $oCost = $this->oDriver->calculateVariant($oVariant, $sOptionSlug);

        if (!property_exists($oCost, 'total') || $oCost->total < 0) {
            throw new ShippingDriverException(
                'The value returned from the shipping driver must specify an item cost greater than, or equal to 0.',
                7
            );
        }

        if (!property_exists($oCost, 'tax_rate') || $oCost->tax_rate < 0) {
            throw new ShippingDriverException(
                'The value returned from the shipping driver must specify a tax rate greater than, or equal to 0.',
                7
            );
        }

        // --------------------------------------------------------------------------

        /**
         * Calculate the shipping values, inclusive and exclusive of tax (respecting the
         * `price_exclude_tax` setting).
         */

        $oOut = (object) [
            'total_inc_tax' => 0,
            'total_ex_tax'  => 0,
            'tax'           => 0,
        ];

        if (appSetting('price_exclude_tax', 'nailsapp/module-shop')) {

            //  Prices exclude tax, so bump it on top
            $oOut->tax           = $oCost->total * $oCost->tax_rate;
            $oOut->total_inc_tax = $oCost->total + $oOut->tax;
            $oOut->total_ex_tax  = $oCost->total;

        } else {

            //  Prices include tax, so the tax value is what is removed from the total
            $oOut->tax           = ($oCost->tax_rate * $oCost->total) / (1 + $oCost->tax_rate);
            $oOut->tax           = round($oOut->tax, 0, PHP_ROUND_HALF_UP);
            $oOut->total_inc_tax = $oCost->total;
            $oOut->total_ex_tax  = $oCost->total - $oOut->tax;
        }

        return $oOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for products which can be set by admin
     * @return array
     */
    public function fieldsProduct()
    {
        return $this->oDriver->fieldsProduct();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for variants which can be set by admin
     * @return array
     */
    public function fieldsVariant()
    {
        return $this->oDriver->fieldsVariant();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an object containing the shipping promotions strings, if any
     * promotion is available.
     *
     * @param  stdClass $basket A basket object
     *
     * @return object
     */
    public function getPromotion($basket)
    {
        $oEmptyPromo = (object) [
            'title'   => '',
            'body'    => '',
            'applied' => false,
        ];

        if (method_exists($this->oDriver, 'getPromotion')) {
            $aShippableItems = $this->getShippableItemsFromBasket($basket);
            return $this->oDriver->getPromotion($aShippableItems, $basket);
        } else {
            return $oEmptyPromo;
        }
    }
}
