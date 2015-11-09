<div class="group-shop vouchers browse">
    <p>
        Browse all vouchers (including gift cards) which are associated with the shop.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="code">Code</th>
                    <th class="boolean">Active</th>
                    <th class="details">Details</th>
                    <th class="user">Created By</th>
                    <th class="datetime">Created</th>
                    <th class="value">Discount</th>
                    <th class="datetime">Valid From</th>
                    <th class="datetime">Expires</th>
                    <th class="uses">Uses</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                if ($vouchers) {

                    foreach ($vouchers as $voucher) {

                        ?>
                        <tr id="order-<?=$voucher->id?>">
                            <td class="code">
                                <?=$voucher->code?>
                                <button class="btn btn-xs btn-info btn-block copy-code" data-clipboard-text="<?=$voucher->code?>">
                                    <span class="waiting">
                                        <b class="fa fa-clipboard"></b>
                                        Copy
                                    </span>
                                    <span class="copied">
                                        <b class="fa fa-check-circle"></b>
                                        Copied!
                                    </span>
                                </button>
                            </td>
                            <?php

                            switch ($voucher->status) {

                                case \Nails\Shop\Model\Voucher::STATUS_PENDING:

                                    ?>
                                    <td class="boolean notice">
                                        <b class="fa fa-clock-o fa-lg"></b>
                                        <small>Pending</small>
                                    </td>
                                    <?php
                                    break;

                                case \Nails\Shop\Model\Voucher::STATUS_EXPIRED:

                                    ?>
                                    <td class="boolean error">
                                        <b class="fa fa-times-circle fa-lg"></b>
                                        <small>Expired</small>
                                    </td>
                                    <?php
                                    break;

                                case \Nails\Shop\Model\Voucher::STATUS_LIMIT_REACHED:

                                    ?>
                                    <td class="boolean error">
                                        <b class="fa fa-times-circle fa-lg"></b>
                                        <small>Limit Reached</small>
                                    </td>
                                    <?php
                                    break;

                                case \Nails\Shop\Model\Voucher::STATUS_ZERO_BALANCE:

                                    ?>
                                    <td class="boolean error">
                                        <b class="fa fa-times-circle fa-lg"></b>
                                        <small>Zero Balance</small>
                                    </td>
                                    <?php
                                    break;

                                case \Nails\Shop\Model\Voucher::STATUS_ACTIVE:

                                    ?>
                                    <td class="boolean success">
                                        <b class="fa fa-check-circle fa-lg"></b>
                                    </td>
                                    <?php
                                    break;

                                case \Nails\Shop\Model\Voucher::STATUS_INACTIVE:

                                    ?>
                                    <td class="boolean success">
                                        <b class="fa fa-check-circle fa-lg"></b>
                                    </td>
                                    <?php
                                    break;
                            }

                            ?>
                            <td class="details">
                                <?php

                                echo $voucher->label;

                                switch ($voucher->type) {

                                    case 'NORMAL':

                                        ?>
                                        <small>
                                            Type: Normal
                                        </small>
                                        <?php
                                        break;

                                    // --------------------------------------------------------------------------

                                    case 'LIMITED_USE':

                                        ?>
                                        <small>
                                            Type: Limited Use
                                        </small>
                                        <small>
                                            Limited to <?=$voucher->limited_use_limit?> uses;
                                            used <?=$voucher->use_count?> times
                                        </small>
                                        <?php
                                        break;

                                    // --------------------------------------------------------------------------

                                    case 'GIFT_CARD':

                                        ?>
                                        <small>
                                            Type: Gift card
                                        </small>
                                        <small>
                                            Remaining Balance:
                                            <?php

                                            echo SHOP_BASE_CURRENCY_SYMBOL;
                                            echo number_format(
                                                $voucher->gift_card_balance,
                                                SHOP_BASE_CURRENCY_PRECISION
                                            );

                                            ?>
                                        </small>
                                        <?php
                                        break;
                                }

                                ?>
                                <small>
                                    Applies to:
                                    <?php

                                    switch ($voucher->discount_application) {

                                        case 'PRODUCTS':

                                            echo 'Purchases only';
                                            break;

                                        case 'SHIPPING':

                                            echo 'Shipping only';
                                            break;

                                        case 'PRODUCT_TYPES':

                                            echo 'Certain product types only &rsaquo; ' . $voucher->product->label;
                                            break;

                                        case 'ALL':

                                            echo 'Both Products and Shipping';
                                            break;
                                    }

                                    ?>
                                </small>
                            </td>
                            <?php

                            echo \Nails\Admin\Helper::loadUserCell($voucher->creator);
                            echo \Nails\Admin\Helper::loadDatetimeCell($voucher->created);

                            ?>

                            <td class="value">
                                <?php

                                switch ($voucher->discount_type) {

                                    case 'AMOUNT':

                                        echo SHOP_BASE_CURRENCY_SYMBOL;
                                        echo number_format($voucher->discount_value, SHOP_BASE_CURRENCY_PRECISION);
                                        break;

                                    case 'PERCENTAGE':

                                        echo $voucher->discount_value . '%';
                                        break;
                                }

                                ?>
                            </td>
                            <?php

                            echo \Nails\Admin\Helper::loadDatetimeCell($voucher->valid_from);
                            echo \Nails\Admin\Helper::loadDatetimeCell($voucher->valid_to, 'Does not expire');

                            ?>
                            <td class="uses">
                                <?=number_format($voucher->use_count)?>
                            </td>
                            <td class="actions">
                                <?php

                                $buttons = array();

                                // --------------------------------------------------------------------------

                                if ($voucher->is_active) {

                                    if (userHasPermission('admin:shop:vouchers:deactivate')) {

                                        $buttons[] = anchor(
                                            'admin/shop/vouchers/deactivate/' . $voucher->id,
                                            'Suspend',
                                            'class="btn btn-xs btn-danger confirm"'
                                        );
                                    }

                                } else {

                                    if (userHasPermission('admin:shop:vouchers:activate')) {

                                        $buttons[] = anchor(
                                            'admin/shop/vouchers/activate/' . $voucher->id,
                                            'Activate',
                                            'class="btn btn-xs btn-success"'
                                        );
                                    }
                                }

                                // --------------------------------------------------------------------------

                                if ($buttons) {

                                    foreach ($buttons as $button) {

                                        echo $button;
                                    }

                                } else {

                                    echo '<span class="blank">There are no actions you can do on this item.</span>';
                                }

                                ?>
                            </td>
                        </tr>
                        <?php

                    }

                } else {

                    ?>
                    <tr>
                        <td colspan="10" class="no-data">
                            <p>
                                No Vouchers found
                            </p>
                        </td>
                    </tr>
                    <?php
                }

                ?>
            </tbody>
        </table>
    </div>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>