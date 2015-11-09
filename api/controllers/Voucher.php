<?php

/**
 * Admin API end points: Shop Vouchers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

use Nails\Factory;

class Voucher extends \Nails\Api\Controller\Base
{
    public static $requiresAuthentication = true;
    protected $maintenance;
    protected $oVoucherModel;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) app_setting('maintenance_enabled', 'shop');
        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
            }
        }

        $this->oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance ehaders and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $this->output->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $this->output->set_header('Status: 503 Service Temporarily Unavailable');
        $this->output->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid voucher code
     * @return array
     */
    public function getGenerateCode()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        if (!userHasPermission('admin:shop:vouchers:create')) {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to create vouchers.'
            );

        } else {

            return array(
                'code' => $this->oVoucherModel->generateValidCode()
            );
        }
    }
}
