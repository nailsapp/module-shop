<div class="group-shop orders browse">
    <p>
        Browse all orders which have been processed by the site from this page.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="checkAll">
                    <?php

                        if (!empty($orders)) {

                            echo '<input type="checkbox" id="toggle-all" />';
                        }

                    ?>
                    </th>
                    <th class="ref">Ref</th>
                    <th class="datetime">Placed</th>
                    <th class="user">Customer</th>
                    <th class="value">Items</th>
                    <th class="value">Tax</th>
                    <th class="value">Shipping</th>
                    <th class="value">Total</th>
                    <th class="value">Discount</th>
                    <th class="status">Status</th>
                    <th class="fulfilment">Fulfilled</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if ($orders) {

                        foreach ($orders as $order) {

                            ?>
                            <tr id="order-<?=$order->id?>">
                                <td class="checkAll">
                                    <input type="checkbox" class="batch-checkbox" value="<?=$order->id?>" />
                                </td>
                                <td class="ref"><?=$order->ref?></td>
                                <?php

                                    echo adminHelper('loadDatetimeCell', $order->created);
                                    echo adminHelper('loadUserCell', $order->user);

                                ?>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->item;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->item . '</small>';
                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->tax;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->tax . '</small>';
                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->shipping;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->shipping . '</small>';
                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->grand;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->grand . '</small>';
                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->grand_discount;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->grand_discount . '</small>';
                                    }

                                ?>
                                </td>
                                <?php

                                    switch ($order->status) {

                                        case 'UNPAID':
                                            $status = 'error';
                                            break;

                                        case 'PAID':
                                            $status = 'success';
                                            break;

                                        case 'ABANDONED':
                                            $status = 'message';
                                            break;

                                        case 'CANCELLED':
                                            $status = 'message';
                                            break;

                                        case 'FAILED':
                                            $status = 'error';
                                            break;

                                        case 'PENDING':
                                            $status = 'notice';
                                           break;

                                        default:
                                            $status = '';
                                            break;
                                    }

                                    echo '<td class="status ' . $status . '">';
                                        echo $order->status;
                                    echo '</td>';

                                    $boolValue = $order->fulfilment_status == 'FULFILLED';
                                    echo adminHelper('loadBoolCell', $boolValue, $order->fulfilled);

                                ?>
                                <td class="actions">
                                    <?php

                                        //  Render buttons
                                        $_buttons = array();

                                        // --------------------------------------------------------------------------

                                        if (userHasPermission('admin:shop:orders:view')) {

                                            $_buttons[] = anchor(
                                                'admin/shop/orders/view/' . $order->id,
                                                lang('action_view'),
                                                'class="btn btn-xs btn-default"'
                                            );
                                            $_buttons[] = anchor(
                                                'admin/shop/orders/download_invoice/' . $order->id,
                                                'Download',
                                                'class="btn btn-xs btn-primary"'
                                            );
                                        }

                                        // --------------------------------------------------------------------------

                                        if (userHasPermission('admin:shop:orders:reprocess')) {

                                             $_buttons[] = anchor(
                                                'admin/shop/orders/reprocess/' . $order->id,
                                                'Process',
                                                'class="btn btn-xs btn-warning confirm" data-body="Processing the order again may result in multiple dispatch of items, or dispatch of unpaid items."'
                                            );
                                        }

                                        // --------------------------------------------------------------------------

                                        if ($_buttons) {

                                            foreach ($_buttons aS $button) {

                                                echo $button;
                                            }

                                        } else {

                                            echo '<span class="blank">There are no actions you can perform on this item.</span>';
                                        }

                                    ?>
                                </td>
                            </tr>
                            <?php

                        }

                    } else {

                        ?>
                        <tr>
                            <td colspan="11" class="no-data">
                                <p>No Orders found</p>
                            </td>
                        </tr>
                        <?php
                    }

                ?>
            </tbody>
        </table>
        <?php

            if ($orders) {

                $_options                     = array();
                $_options['']                 = 'Choose';
                $_options['mark-fulfilled']   = 'Mark Fulfilled';
                $_options['mark-unfulfilled'] = 'Mark Unfulfilled';
                $_options['mark-cancelled']   = 'Mark Cancelled';
                $_options['download']         = 'Download';

                echo '<div class="panel" id="batch-action">';
                    echo '<div class="panel-body">';
                        echo 'With checked: ';
                        echo form_dropdown('', $_options, null);
                        echo ' <a href="#" class="btn btn-xs btn-primary">Go</a>';
                    echo '</div>';
                echo '</div>';
            }

        ?>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>
