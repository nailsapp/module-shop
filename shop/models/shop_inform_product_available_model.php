<?php

/**
 * This model manages product availability requests
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use Nails\Common\Model\Base;

class Shop_inform_product_available_model extends Base
{
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->table             = NAILS_DB_PREFIX . 'shop_inform_product_available';
        $this->tableAlias       = 'sipa';
        $this->defaultSortColumn = null;

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');
    }

    // --------------------------------------------------------------------------

    protected function getCountCommon($data = array())
    {
        parent::getCountCommon($data);

        $oDb = Factory::service('Database');

        if (empty($data['sort'])) {
            $oDb->order_by($this->tableAlias . '.created', 'DESC');
        }

        $oDb->select($this->tableAlias . '.*, ue.user_id, u.first_name, u.last_name, u.profile_img, u.gender');
        $oDb->select('sp.label product_label, spv.label variation_label');

        //  Join the User tables
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ' . $this->tableAlias . '.email', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = ue.user_id', 'LEFT');

        //  Join the product & variartion tables
        $oDb->join(NAILS_DB_PREFIX . 'shop_product sp', 'sp.id = ' . $this->tableAlias . '.product_id');
        $oDb->join(NAILS_DB_PREFIX . 'shop_product_variation spv', 'spv.id = ' . $this->tableAlias . '.variation_id');
    }

    // --------------------------------------------------------------------------

    public function add($variantId, $email)
    {
        Factory::helper('email');

        if (!valid_email($email)) {

            $this->setError('"' . $email . '" is not a valid email address.');
            return false;
        }

        $product = $this->shop_product_model->getByVariantId($variantId);

        if (!$product) {

            $this->setError('Invalid Variant ID.');
            return false;
        }

        // --------------------------------------------------------------------------

        $_data                 = array();
        $_data['product_id']   = $product->id;
        $_data['variation_id'] = $variantId;
        $_data['email']        = $email;

        return (bool) parent::create($_data);
    }

    // --------------------------------------------------------------------------

    public function inform($productId, $variationIds)
    {
        $oDb = Factory::service('Database');

        $variationIds = (array) $variationIds;
        $variationIds = array_filter($variationIds);
        $variationIds = array_unique($variationIds);

        $sent = array();

        if ($variationIds) {

            $product = $this->shop_product_model->getById($productId);

            if ($product && $product->is_active && !$product->is_deleted) {

                foreach ($variationIds as $variationId) {

                    $oDb->select($this->tableAlias . '.*');
                    $oDb->where($this->tableAlias . '.product_id', $productId);
                    $oDb->where($this->tableAlias . '.variation_id', $variationId);
                    $results = $oDb->get($this->table . ' ' . $this->tableAlias)->result();

                    foreach ($results as $result) {

                        //  Have we already sent this notification?
                        $sentStr = $_email->to_email . '|' . $product->id . '|' . $variationId;

                        if (in_array($sentStr, $sent)) {

                            continue;
                        }

                        $_email                             = new \stdClass();
                        $_email->to_email                   = $result->email;
                        $_email->type                       = 'shop_inform_product_available';
                        $_email->data                       = new \stdClass();
                        $_email->data->product              = new \stdClass();
                        $_email->data->product->label       = $product->label;
                        $_email->data->product->description = $product->description;
                        $_email->data->product->url         = $product->url;
                        $_email->data->product->img         = cdnScale($product->featured_img, 250, 250);

                        $this->emailer->send($_email);

                        $sent[] = $sentStr;
                    }
                }
            }
        }

        //  Delete requests
        $oDb->where('product_id', $productId);
        $oDb->where_in('variation_id', $variationIds);
        $oDb->delete($this->table);
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
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oObj->product        = new \stdClass();
        $oObj->product->id    = (int) $oObj->product_id;
        $oObj->product->label = $oObj->product_label;
        unset($oObj->product_id);
        unset($oObj->product_label);

        $oObj->variation        = new \stdClass();
        $oObj->variation->id    = (int) $oObj->variation_id;
        $oObj->variation->label = $oObj->variation_label;
        unset($oObj->variation_id);
        unset($oObj->variation_label);

        $oObj->user              = new \stdClass();
        $oObj->user->id          = $oObj->user_id;
        $oObj->user->email       = $oObj->email;
        $oObj->user->first_name  = $oObj->first_name;
        $oObj->user->last_name   = $oObj->last_name;
        $oObj->user->profile_img = $oObj->profile_img;
        $oObj->user->gender      = $oObj->gender;
        unset($oObj->user_id);
        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->profile_img);
        unset($oObj->gender);
    }
}
