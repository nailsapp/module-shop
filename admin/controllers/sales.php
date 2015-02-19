<?php

/**
 * Manage shop sales
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Sales extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:sales:manage')) {

            $navGroup = new \Nails\Admin\Nav('Shop', 'fa-shopping-cart');
            $navGroup->addAction('Manage Sales');
            return $navGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Manage sales';
        $permissions['create'] = 'Create sales';
        $permissions['edit']   = 'Edit sales';
        $permissions['delete'] = 'Delete sales';

        return $permissions;
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
     * Browse sales
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:shop:sales:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage Sales';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new sale
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:shop:sales:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Sale';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a sale
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:shop:sales:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Sale "xxx"';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a sale
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:shop:sales:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('message', '<strong>TODO:</strong> Delete a sale.');
        redirect('admin/shop/sales/index');
    }
}