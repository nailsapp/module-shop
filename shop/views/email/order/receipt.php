<?php

/**
 * We're rendering this template using PHP as it's a bit more complex than Mustache
 * allows, specifically the order_details sub-view.
 */

?>
<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    We have now received full payment for your order, please don't hesitate
    to contact us if you have any questions or concerns.
</p>
<?php

$sWord = $emailObject->data->order->delivery_type === 'COLLECT' ? 'All' : 'Some';

if (in_array($emailObject->data->order->delivery_type, array('COLLECT', 'DELIVER_COLLECT'))) {

    $aAddress   = array();
    $aAddress[] = appSetting('warehouse_addr_addressee', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_line1', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_line2', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_town', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_postcode', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_state', 'nailsapp/module-shop');
    $aAddress[] = appSetting('warehouse_addr_country', 'nailsapp/module-shop');
    $aAddress   = array_filter($aAddress);

    if ($aAddress) {

        ?>
        <div class="heads-up warning">
            <strong>Important:</strong> <?=$sWord?> items in this order should be collected from:
            <hr>
            <?=implode('<br />', $aAddress)?>
        </div>
        <?php

    } else {

        ?>
        <p class="heads-up warning">
            <strong>Important:</strong> <?=$sWord?> items in this order should be collected.
        </p>
        <?php
    }
}

// --------------------------------------------------------------------------

$oView = \Nails\Factory::service('View');
$oView->load('shop/email/order/_component/order_details', array('order' => $emailObject->data->order));
$oView->load('shop/email/order/_component/other_details');
