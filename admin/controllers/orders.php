<?php

/**
 * Manage shop vouchers and gift cards
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Orders extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.shop:0.orders_manage')) {

            $navGroup = new \Nails\Admin\Nav('Shop');
            $navGroup->addMethod('Manage Orders');
            return $navGroup;
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
    }

    // --------------------------------------------------------------------------

    /**
     * Browse shop orders
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Orders';

        // --------------------------------------------------------------------------

        //  Searching, sorting, ordering and paginating.
        $hash = 'search_' . md5(uri_string()) . '_';

        if ($this->input->get('reset')) {

            $this->session->unset_userdata($hash . 'per_page');
            $this->session->unset_userdata($hash . 'sort');
            $this->session->unset_userdata($hash . 'order');
        }

        $default_per_page = $this->session->userdata($hash . 'per_page') ? $this->session->userdata($hash . 'per_page') : 50;
        $default_sort     = $this->session->userdata($hash . 'sort') ?    $this->session->userdata($hash . 'sort') : 'o.id';
        $default_order    = $this->session->userdata($hash . 'order') ?   $this->session->userdata($hash . 'order') : 'desc';

        //  Define vars
        $search = array('keywords' => $this->input->get('search'), 'columns' => array());

        foreach ($this->shop_orders_sortfields as $field) {

            $search['columns'][strtolower($field['label'])] = $field['col'];
        }

        $limit      = array(
                        $this->input->get('per_page') ? $this->input->get('per_page') : $default_per_page,
                        $this->input->get('offset') ? $this->input->get('offset') : 0
                    );
        $order      = array(
                        $this->input->get('sort') ? $this->input->get('sort') : $default_sort,
                        $this->input->get('order') ? $this->input->get('order') : $default_order
                    );

        //  Set sorting and ordering info in session data so it's remembered for when user returns
        $this->session->set_userdata($hash . 'per_page', $limit[0]);
        $this->session->set_userdata($hash . 'sort', $order[0]);
        $this->session->set_userdata($hash . 'order', $order[1]);

        //  Set values for the page
        $this->data['search']               = new \stdClass();
        $this->data['search']->per_page     = $limit[0];
        $this->data['search']->sort         = $order[0];
        $this->data['search']->order        = $order[1];
        $this->data['search']->show         = $this->input->get('show');
        $this->data['search']->fulfilled    = $this->input->get('fulfilled');

        /**
         * Small hack(?) - if no status has been specified, and the $GET array is
         * empty (i.e no form of searching is being done) then set a few defaults.
         */

        if (empty($GET) && empty($this->data['search']->show)) {

            $this->data['search']->show = array('paid' => true);
        }

        // --------------------------------------------------------------------------

        //  Prepare the where
        if ($this->data['search']->show || $this->data['search']->fulfilled) {

            $where = '(';

            if ($this->data['search']->show) {

                $where .= '`o`.`status` IN (';

                    $statuses = array_keys($this->data['search']->show);
                    foreach ($statuses as &$stat) {

                        $stat = strtoupper($stat);
                    }
                    $where .= "'" . implode("', '", $statuses) . "'";

                $where .= ')';
            }

            // --------------------------------------------------------------------------

            if ($this->data['search']->show && $this->data['search']->fulfilled) {

                $where .= ' AND ';
            }

            // --------------------------------------------------------------------------

            if ($this->data['search']->fulfilled) {

                $where .= '`o`.`fulfilment_status` IN (';

                    $statuses = array_keys($this->data['search']->fulfilled);
                    foreach ($statuses as &$stat) {

                        $stat = strtoupper($stat);
                    }
                    $where .= "'" . implode("', '", $statuses) . "'";

                $where .= ')';
            }

            $where .= ')';

        } else {

            $where = null;
        }

        // --------------------------------------------------------------------------

        //  Pass any extra data to the view
        $this->data['actions']      = $this->shop_orders_actions;
        $this->data['sortfields']   = $this->shop_orders_sortfields;

        // --------------------------------------------------------------------------

        //  Fetch orders
        $this->load->model('shop/shop_order_model');

        $this->data['orders']       = new \stdClass();
        $this->data['orders']->data = $this->shop_order_model->get_all($order, $limit, $where, $search);

        //  Work out pagination
        $this->data['orders']->pagination                   = new \stdClass();
        $this->data['orders']->pagination->total_results    = $this->shop_order_model->count_orders($where, $search);

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.order.browse.min.js', true);
        $this->asset->inline('var _SHOP_ORDER_BROWSE = new NAILS_Admin_Shop_Order_Browse()', 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * View a single order
     * @return void
     */
    public function view()
    {
        if (!userHasPermission('admin.shop:0.orders_view')) {

            $this->session->set_flashdata('error', 'You do not have permission to view order details.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$this->data['order']) {

            $this->session->set_flashdata('error', 'No order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Get associated payments
        $this->load->model('shop/shop_order_payment_model');
        $this->data['payments'] = $this->shop_order_payment_model->get_for_order($this->data['order']->id);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'View Order &rsaquo; ' . $this->data['order']->ref;

        // --------------------------------------------------------------------------

        if ($this->input->get('isFancybox')) {

            $this->data['headerOverride'] = 'structure/headerBlank';
            $this->data['footerOverride'] = 'structure/footerBlank';
        }

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.order.view.min.js', true);
        $this->asset->inline('var _SHOP_ORDER_VIEW = new NAILS_Admin_Shop_Order_View()', 'JS');

        // --------------------------------------------------------------------------

        if ($this->data['order']->fulfilment_status != 'FULFILLED' && !$this->data['order']->requires_shipping) {

            $this->data['error']  = '<strong>Do not ship this order!</strong>';

            if ($this->data['order']->delivery_type == 'COLLECT') {

                $this->data['error'] .= '<br />This order will be collected by the customer.';

            } else {

                $this->data['error'] .= '<br />This order does not require shipping.';
            }
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('view');
    }

    // --------------------------------------------------------------------------

    /**
     * Reprocess an order
     * @return void
     */
    public function reprocess()
    {
        if (!userHasPermission('admin.shop:0.orders_reprocess')) {

            $this->session->set_flashdata('error', 'You do not have permission to reprocess orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Check order exists
        $this->load->model('shop/shop_order_model');
        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $this->session->set_flashdata('error', 'I couldn\'t find an order by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  PROCESSSSSS...
        $this->shop_order_model->process($order);

        // --------------------------------------------------------------------------

        //  Send a receipt to the customer
        $this->shop_order_model->send_receipt($order);

        // --------------------------------------------------------------------------

        //  Send a notification to the store owner(s)
        $this->shop_order_model->send_order_notification($order);

        // --------------------------------------------------------------------------

        if ($order->voucher) {

            //  Redeem the voucher, if it's there
            $this->load->model('shop/shop_voucher_model');
            $this->shop_voucher_model->redeem($order->voucher->id, $order);
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('success', 'Order was processed succesfully. The user has been sent a receipt.');
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Process an order
     * @return void
     */
    public function process()
    {
        if (!userHasPermission('admin.shop:0.orders_process')) {

            $this->session->set_flashdata('error', 'You do not have permission to process order items.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        $order_id       = $this->uri->segment(5);
        $product_id = $this->uri->segment(6);
        $isFancybox    = $this->input->get('isFancybox') ? '?isFancybox=true' : '';

        // --------------------------------------------------------------------------

        //  Update item
        if ($this->uri->segment(7) == 'processed') {

            $this->db->set('processed', true);

        } else {

            $this->db->set('processed', false);
        }

        $this->db->where('order_id',    $order_id);
        $this->db->where('id',          $product_id);

        $this->db->update(NAILS_DB_PREFIX . 'shop_order_product');

        if ($this->db->affected_rows()) {

            //  Product updated, check if order has been fulfilled
            $this->db->where('order_id', $order_id);
            $this->db->where('processed', false);

            if (!$this->db->count_all_results(NAILS_DB_PREFIX . 'shop_order_product')) {

                //  No unprocessed items, consider order FULFILLED
                $this->load->model('shop/shop_order_model');
                $this->shop_order_model->fulfil($order_id);

            } else {

                //  Still some unprocessed items, mark as unfulfilled (in case it was already fulfilled)
                $this->load->model('shop/shop_order_model');
                $this->shop_order_model->unfulfil($order_id);
            }

            // --------------------------------------------------------------------------

            $this->session->set_flashdata('success', 'Product\'s status was updated successfully.');
            redirect('admin/shop/orders/view/' . $order_id . $isFancybox);

        } else {

            $this->session->set_flashdata('error', 'I was not able to update the status of that product.');
            redirect('admin/shop/orders/view/' . $order_id . $isFancybox);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Download an order's invoice
     * @return void
     */
    public function download_invoice()
    {
        if (!userHasPermission('admin.shop:0.orders_view')) {

            $this->session->set_flashdata('error', 'You do not have permission to download orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$this->data['order']) {

            $this->session->set_flashdata('error', 'No order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Load up the shop's skin
        $skin = app_setting('skin_checkout', 'shop') ? app_setting('skin_checkout', 'shop') : 'shop-skin-checkout-classic';

        $this->load->model('shop/shop_skin_checkout_model');
        $skin = $this->shop_skin_checkout_model->get($skin);

        if (!$skin) {

            showFatalError('Failed to load shop skin "' . $skin . '"', 'Shop skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_checkout_model->last_error());
        }

        // --------------------------------------------------------------------------

        //  Views
        $this->data['for_user'] = 'ADMIN';
        $this->load->library('pdf/pdf');
        $this->pdf->set_paper_size('A4', 'landscape');
        $this->pdf->load_view($skin->path . 'views/order/invoice', $this->data);
        $this->pdf->download('INVOICE-' . $this->data['order']->ref . '.pdf');
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as fulfilled
     * @return void
     */
    public function fulfil()
    {
        if (!userHasPermission('admin.shop:0.orders_edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->fulfil($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as fulfilled.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as fulfilled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Batch fulfil orders
     * @return void
     */
    public function fulfil_batch()
    {
        if (!userHasPermission('admin.shop:0.orders_edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->fulfilBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as fulfilled.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as fulfilled. ';
            $msg    .= $this->shop_order_model->last_error();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as unfulfilled
     * @return void
     */
    public function unfulfil()
    {
        if (!userHasPermission('admin.shop:0.orders_edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->unfulfil($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as unfulfilled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    //---------------------------------------------------------------------------

    /**
     * Batch unfulfil orders
     * @return void
     */
    public function unfulfil_batch()
    {
        if (!userHasPermission('admin.shop:0.orders_edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->unfulfilBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as unfulfilled. ';
            $msg    .= $this->shop_order_model->last_error();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }
}
