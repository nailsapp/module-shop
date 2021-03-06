<?php

/**
 * This model provides voucher functionality to the Nails shop.
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

use Nails\Factory;
use Nails\Common\Model\Base;

class Voucher extends Base
{
    const TYPE_NORMAL                       = 'NORMAL';
    const TYPE_LIMITED_USE                  = 'LIMITED_USE';
    const TYPE_GIFT_CARD                    = 'GIFT_CARD';
    const STATUS_ACTIVE                     = 'ACTIVE';
    const STATUS_INACTIVE                   = 'INACTIVE';
    const STATUS_EXPIRED                    = 'EXPIRED';
    const STATUS_PENDING                    = 'PENDING';
    const STATUS_LIMIT_REACHED              = 'LIMIT_REACHED';
    const STATUS_ZERO_BALANCE               = 'ZERO_BALANCE';
    const DISCOUNT_TYPE_PERCENT             = 'PERCENTAGE';
    const DISCOUNT_TYPE_AMOUNT              = 'AMOUNT';
    const DISCOUNT_APPLICATION_PRODUCTS     = 'PRODUCTS';
    const DISCOUNT_APPLICATION_PRODUCT      = 'PRODUCT';
    const DISCOUNT_APPLICATION_PRODUCT_TYPE = 'PRODUCT_TYPES';
    const DISCOUNT_APPLICATION_SHIPPING     = 'SHIPPING';
    const DISCOUNT_APPLICATION_ALL          = 'ALL';

    // --------------------------------------------------------------------------

    protected $oCurrencyModel;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->table             = NAILS_DB_PREFIX . 'shop_voucher';
        $this->tableAlias       = 'sv';
        $this->destructiveDelete = false;
        $this->oCurrencyModel    = Factory::model('Currency', 'nailsapp/module-shop');
    }

    // --------------------------------------------------------------------------

    /**
     * Return the varying types of voucher
     * @return array
     */
    public function getTypes()
    {
        return array(
            self::TYPE_NORMAL      => 'Normal',
            self::TYPE_LIMITED_USE => 'Limited Use',
            // self::TYPE_GIFT_CARD   => 'Gift Card'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a passed string is a valid status
     * @param  string  $sType The string to check
     * @return boolean
     */
    public function isValidStatus($sStatus)
    {
        $aStatuses = $this->getStatuses();
        return !empty($aStatuses[$sStatus]);
    }

    // --------------------------------------------------------------------------

    /**
     * Redeems a voucher
     * @param  int      $voucherId The ID of the voucher to redeem
     * @param  stdClass $mOrder     The order ID or object
     * @return boolean
     */
    public function redeem($iVoucherId, $mOrder)
    {
        if (is_numeric($mOrder)) {

            $this->load->model('shop/shop_order_model');
            $oOrder = $this->shop_order_model->getById($mOrder);

            if (empty($oOrder)) {

                $this->setError('Invalid Order ID');
                return false;
            }

        } else {

            $oOrder = $mOrder;
        }

        // --------------------------------------------------------------------------

        $oVoucher = $this->getById($iVoucherId);

        if (empty($oVoucher)) {

            $this->setError('Invalid Voucher ID');
            return false;
        }

        // --------------------------------------------------------------------------

        switch ($oVoucher->type) {

            case self::TYPE_GIFT_CARD:
                return $this->redeemGiftCard($oVoucher, $oOrder);
                break;

            case self::TYPE_LIMITED_USE:
                return $this->redeemLimitedUse($oVoucher);
                break;

            case self::TYPE_NORMAL:
            default:
                return $this->redeemNormal($oVoucher);
                break;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the last_used and modified dates of the voucher and bumps the use_count column
     * @param  stdClass $oVoucher The voucher object
     * @return boolean
     */
    protected function redeemNormal($oVoucher)
    {
        //  Bump the use count
        $oDb = Factory::service('Database');
        $oDb->set('last_used', 'NOW()', false);
        $oDb->set('modified', 'NOW()', false);
        $oDb->set('use_count', 'use_count+1', false);

        $oDb->where('id', $oVoucher->id);
        return $oDb->update($this->table);
    }

    // --------------------------------------------------------------------------

    /**
     * Calls redeemNormal();
     * @param  stdClass $oVoucher The voucher object
     * @return boolean
     */
    protected function redeemLimitedUse($oVoucher)
    {
        return $this->redeemNormal($oVoucher);
    }

    // --------------------------------------------------------------------------

    /**
     * @todo   Work out what this method does
     * @param  stdClass $voucher The voucher object
     * @param  stdClass $order   The order object
     * @return boolean
     */
    protected function redeemGiftCard($voucher, $order)
    {
        throw new \Exception('Gift Cards are @todo');
        if ($order->shipping->isRequired) {

            if (appSetting('free_shipping_threshold', 'nailsapp/module-shop') <= $order->totals->sub) {

                /**
                 * The order qualifies for free shipping, ignore the discount given
                 * in discount->shipping
                 */

                $spend = $order->discount->items;

            } else {

                /**
                 * The order doesn't qualify for free shipping, include the discount
                 * given in discount->shipping
                 */

                $spend = $order->discount->items + $order->discount->shipping;
            }

        } else {

            //  The discount given by the giftcard is that of discount->items
            $spend = $order->discount->items;
        }

        //  Bump the use count
        $oDb = Factory::service('Database');
        $oDb->set('last_used', 'NOW()', false);
        $oDb->set('modified', 'NOW()', false);
        $oDb->set('use_count', 'use_count+1', false);

        // --------------------------------------------------------------------------

        //  Alter the available balance
        $oDb->set('gift_card_balance', 'gift_card_balance-' . $spend, false);

        $oDb->where('id', $voucher->id);
        return $oDb->update($this->table);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all vouchers, optionally paginated.
     * @param int    $page    The page number of the results, if null then no pagination
     * @param int    $perPage How many items per page of paginated results
     * @param mixed  $data    Any data to pass to getCountCommon()
     * @return array
     **/
    public function getAll($page = null, $perPage = null, array $data = array())
    {
        //  If the first value is an array then treat as if called with getAll(null, null, $aData);
        //  @todo (Pablo - 2017-11-09) - Convert these to expandable fields
        if (is_array($page)) {
            $data = $page;
            $page = null;
        }

        $result = parent::getAll($page, $perPage, $data, false);

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');

        //  Fetch extra data
        foreach ($result as $voucher) {

            switch ($voucher->discount_application) {

                case self::DISCOUNT_APPLICATION_PRODUCT:
                    $cacheKey = 'voucher-product-type-' . $voucher->product_type_id;
                    $cache    = $this->getCache($cacheKey);
                    if ($cache) {

                        //  Exists in cache
                        $voucher->product = $cache;

                    } else {

                        //  Doesn't exist, fetch and save
                        $voucher->product = $this->shop_product_model->getById($voucher->product_id);
                        $this->setCache($cacheKey, $voucher->product);
                    }
                    break;

                case self::DISCOUNT_APPLICATION_PRODUCT_TYPE:
                    $cacheKey = 'voucher-product-type-' . $voucher->product_type_id;
                    $cache    = $this->getCache($cacheKey);
                    if ($cache) {

                        //  Exists in cache
                        $voucher->product_type = $cache;

                    } else {

                        //  Doesn't exist, fetch and save
                        $voucher->product_type = $this->shop_product_type_model->getById($voucher->product_type_id);
                        $this->setCache($cacheKey, $voucher->product_type);
                    }
                    break;
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     * @param  mixed  $where  A conditional to pass to $oDb->where()
     * @param  array $search Keywords to restrict the query by
     * @return void
     */
    protected function getCountCommon(array $data = array())
    {
        //  Search
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.code',
                'value'  => $data['keywords']
            );
        }

        parent::getCountCommon($data);

        $oDb = Factory::service('Database');
        $oDb->select($this->tableAlias . '.*,u.first_name, u.last_name, u.gender, u.profile_img, ue.email');
        $oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->tableAlias . '.created_by', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');
    }

    // --------------------------------------------------------------------------

    /**
     * Get a voucher by its code
     * @todo Update once getAll is updated
     * @param  string $code The voucher's code
     * @param  mixed  $data Any data to pass to getCountCommon()
     * @return mixed        stdClass on success, false on failure
     */
    public function getByCode($code, $data = array())
    {
        if (!isset($data['where'])) {

            $data['where'] = array();
        }

        $data['where'][] = array($this->tableAlias . '.code', $code);

        $result = $this->getAll(null, null, $data);

        // --------------------------------------------------------------------------

        if (!$result) {

            return false;
        }

        // --------------------------------------------------------------------------

        return $result[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a voucher is valid or not
     * @param  string   $code   The voucher's code
     * @param  stdClass $basket The basket object
     * @return mixed            stdClass (the voucher object) on success, false on failure
     */
    public function validate($code, $basket = null)
    {
        if (!$code) {

            $this->setError('No voucher code supplied.');
            return false;
        }

        $voucher = $this->getByCode($code);

        if (!$voucher) {

            $this->setError('Invalid voucher code.');
            return false;
        }

        //  @todo allow the use of gift cards
        if ($voucher->type == self::TYPE_GIFT_CARD) {

            $this->setError('Gift Cards are not currently supported in this store.');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Voucher exists, now we need to check it's still valid.
         */

        //  Check voucher's status
        switch ($voucher->status) {

            case self::STATUS_PENDING:
                // @todo: User user datetime functions
                $bIsValid  = false;
                $sMessage  = 'Voucher is not available yet. This voucher becomes available on the ';
                $sMessage .= date('jS F Y \a\t H:i', strtotime($voucher->valid_from)) . '.';

                $this->setError($sMessage);
                break;

            case self::STATUS_EXPIRED:
                $bIsValid = false;
                $sMessage = 'Voucher has expired.';

                $this->setError($sMessage);
                break;

            case self::STATUS_INACTIVE:
                $bIsValid = false;
                $sMessage = 'Invalid voucher code.';

                $this->setError($sMessage);
                break;

            default:
                $bIsValid = true;
                break;
        }

        if (!$bIsValid) {
            return false;
        }

        //  Check the voucher is valid for the basket
        if (!is_null($basket)) {

            //  Is this a shipping voucher being applied to an order with no shippable items?
            $bIsShippingVoucher = $voucher->discount_application == self::DISCOUNT_APPLICATION_SHIPPING;
            if ($bIsShippingVoucher && !$basket->shipping->isRequired) {

                $this->setError('Your order does not contian any items which require shipping, voucher not needed!');
                return false;
            }

            /**
             * Is there a shipping threshold? If so, and the voucher is type SHIPPING and
             * the threshold has been reached then prevent it being added as it doesn't
             * make sense.
             */

            if (appSetting('free_shipping_threshold', 'nailsapp/module-shop') && $voucher->discount_application == self::DISCOUNT_APPLICATION_SHIPPING) {

                if ($basket->totals->sub >= appSetting('free_shipping_threshold', 'nailsapp/module-shop')) {

                    $this->setError('Your order qualifies for free shipping, voucher not needed!');
                    return false;
                }
            }

            /**
             * If the voucher applies to a particular product type, check the basket contains
             * that product, otherwise it doesn't make sense to add it
             */

            if ($voucher->discount_application == self::DISCOUNT_APPLICATION_PRODUCT_TYPE) {

                $matched = false;
                foreach ($basket->items as $item) {
                    if ($item->product->type->id == $voucher->product_type_id) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    $this->setError('This voucher does not apply to any items in your basket.');
                    return false;
                }
            }

            /**
             * If the voucher applies to a particular product, check the basket contains
             * that product, otherwise it doesn't make sense to add it
             */

            if ($voucher->discount_application == self::DISCOUNT_APPLICATION_PRODUCT) {

                $matched = false;
                foreach ($basket->items as $item) {
                    if ($item->product->id == $voucher->product_id) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    $this->setError('This voucher does not apply to any items in your basket.');
                    return false;
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Check custom voucher type conditions
        Factory::helper('string');
        $method = 'validate' . underscoreToCamelcase(strtolower($voucher->type));

        if (method_exists($this, $method)) {

            if ($this->$method($voucher)) {

                return $voucher;

            } else {

                return false;
            }

        } else {

            $this->setError('This voucher is corrupt and cannot be used just now.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type "NORMAL"
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateNormal($voucher)
    {
        /**
         * So long as the voucher is within date limits then it's valid. If we got
         * here then it's valid and has not expired
         */

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type self::TYPE_LIMITED_USE
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateLimitedUse(&$voucher)
    {
        if ($voucher->use_count < $voucher->limited_use_limit) {

            return true;

        } else {

            $this->setError('Voucher has exceeded its use limit.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Additional checks for vouchers of type "GIFT_CARD"
     * @param  stdClass $voucher The voucher being validated
     * @return boolean
     */
    protected function validateGiftCard(&$voucher)
    {
        if ($voucher->gift_card_balance > 0) {

            return true;

        } else {

            $this->setError('Gift card has no available balance.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a voucher as activated
     * @param  int     $voucherId The voucher's ID
     * @return boolean
     */
    public function activate($voucherId)
    {
        $voucher = $this->getById($voucherId);

        if (!$voucher) {

            $this->setError('Invalid voucher ID');
            return false;
        }

        $data = array(
            'is_active' => true
        );

        return $this->update($voucher->id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a voucher as suspended
     * @param  int     $voucherId The voucher's ID
     * @return boolean
     */
    public function suspend($voucherId)
    {
        $voucher = $this->getById($voucherId);

        if (!$voucher) {

            $this->setError('Invalid voucher ID');
            return false;
        }

        $data = array(
            'is_active' => false
        );

        return $this->update($voucher->id, $data);
    }

    // --------------------------------------------------------------------------

    public function generateValidCode()
    {
        Factory::helper('string');
        $oDb = Factory::service('Database');

        do {

            $sCode = strtoupper(random_string('alnum'));
            $oDb->where('code', $sCode);
            $bCodeExists = (bool) $oDb->count_all_results($this->table);

        } while ($bCodeExists);

        return $sCode;
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new voucher
     * @param  array   $aData         The data to create the voucher with
     * @param  boolean $bReturnObject Whether to return just the new ID or the full voucher
     * @return mixed
     */
    public function create(array $aData = array(), $bReturnObject = false)
    {
        if (empty($aData['label'])) {
            $this->setError('Discount label is a required field.');
            return false;
        }

        if (empty($aData['type'])) {
            $this->setError('Voucher type is a required field.');
            return false;
        }

        if (empty($aData['code'])) {
            $this->setError('Voucher code is a required field.');
            return false;
        }

        if (empty($aData['discount_type'])) {
            $this->setError('Discount type is a required field.');
            return false;
        }

        if (empty($aData['discount_value'])) {
            $this->setError('Discount value is a required field.');
            return false;
        }

        if (empty($aData['discount_application'])) {
            $this->setError('Discount application is a required field.');
            return false;
        }

        /**
         * Ensure that fields which might contain figures are properly stored
         * in their minimum units. This only applies in certain conditions
         */

        switch ($aData['discount_type']) {

            case self::DISCOUNT_TYPE_PERCENT:
                if ($aData['discount_value'] < 0 || $aData['discount_value'] > 100) {

                    $this->setError('Discount value must be within the range 0-100.');
                    return false;
                }
                break;

            case self::DISCOUNT_TYPE_AMOUNT:
                if ($aData['discount_value'] < 0) {

                    $this->setError('Discount value must be greater than 0.');
                    return false;

                } else {

                    //  Convert to integer
                    $aData['discount_value'] = $this->oCurrencyModel->floatToInt(
                        $aData['discount_value'],
                        SHOP_BASE_CURRENCY_CODE
                    );
                }
                break;
        }

        return parent::create($aData, $bReturnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        array $aData = [],
        array $aIntegers = [],
        array $aBools = [],
        array $aFloats = []
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oObj->limited_use_limit           = (int) $oObj->limited_use_limit;
        $oObj->discount_value              = (int) $oObj->discount_value;
        $oObj->discount_value_formatted    = $this->oCurrencyModel->formatBase($oObj->discount_value);
        $oObj->gift_card_balance           = (int) $oObj->gift_card_balance;
        $oObj->gift_card_balance_formatted = $this->oCurrencyModel->formatBase($oObj->gift_card_balance);

        //  Creator
        $oObj->creator               = new \stdClass();
        $oObj->creator->id           = (int) $oObj->created_by;
        $oObj->creator->email        = $oObj->email;
        $oObj->creator->first_name   = $oObj->first_name;
        $oObj->creator->last_name    = $oObj->last_name;
        $oObj->creator->profile_img  = $oObj->profile_img;
        $oObj->creator->gender       = $oObj->gender;

        unset($oObj->created_by);
        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->profile_img);
        unset($oObj->gender);

        // --------------------------------------------------------------------------

        $oDate = Factory::factory('DateTime');
        $iNow  = $oDate->format('U');

        if ($oObj->is_active && strtotime($oObj->valid_from) > $iNow) {

            $oObj->status = self::STATUS_PENDING;

        } elseif ($oObj->is_active && !empty($oObj->valid_to) && strtotime($oObj->valid_to) < $iNow) {

            $oObj->status = self::STATUS_EXPIRED;

        } elseif ($oObj->is_active) {

            $oObj->status = self::STATUS_ACTIVE;

        } else {

            $oObj->status = self::STATUS_INACTIVE;
        }

        //  Has the voucher reached it's us limit
        if ($oObj->type === self::TYPE_LIMITED_USE && $oObj->use_count >= $oObj->limited_use_limit) {
            $oObj->status = self::STATUS_LIMIT_REACHED;
        }

        if ($oObj->type === self::TYPE_GIFT_CARD && $oObj->gift_card_balance == 0) {
            $oObj->status = self::STATUS_ZERO_BALANCE;
        }
    }
}
