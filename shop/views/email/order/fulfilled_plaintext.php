<?php

//  Country
$this->load->model('country_model');
$countriesFlat = $this->country_model->getAllFlat();

?>
Thank you very much for your order with <?=APP_NAME?>.

Your order has been marked as fulfilled, indicating the dispatch of your goods. If you have any questions, please get in touch using the details below.

YOUR ORDER
----------

REFERENCE
<?=$order->ref?>


PLACED
<?=toUserDatetime($order->created)?>


CUSTOMER
<?php

echo $order->user->first_name . ' ' . $order->user->last_name;
echo "\n" . $order->user->email;
echo "\n" . $order->user->telephone;

?>


SHIPPING ADDRESS
<?php

foreach ($order->shipping_address as $key => $line) {

    if (!empty($line)) {

        if ($key == 'country' && isset($countriesFlat[$line])) {

            echo $countriesFlat[$line] . "\n";

        } else {

            echo $line . "\n";
        }
    }
}

?>

BILLING ADDRESS
<?php

foreach ($order->billing_address as $key => $line) {

    if (!empty($line)) {

        if ($key == 'country' && isset($countriesFlat[$line])) {

            echo $countriesFlat[$line] . "\n";

        } else {

            echo $line . "\n";
        }
    }
}


if (!empty($order->note)) {

    echo "\n" . 'NOTE' . "\n";
    echo $order->note . "\n";
}

?>


YOUR ITEMS
----------

<?php

foreach ($order->items as $item) {

    echo strtoupper($item->product_label) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper($item->variant_label) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";

    echo strtoupper($item->product_label) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper($item->variant_label) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";
}

?>
---

Sub Total: <?=$order->totals->user_formatted->item . "\n"?>
Shipping:  <?=$order->totals->user_formatted->shipping . "\n"?>
Tax:       <?=$order->totals->user_formatted->tax . "\n"?>
Total:     <?=$order->totals->user_formatted->grand . "\n"?>
<?php

$_invoice_company    = app_setting('invoice_company', 'shop');
$_invoice_address    = app_setting('invoice_address', 'shop');
$_invoice_vat_no     = app_setting('invoice_vat_no', 'shop');
$_invoice_company_no = app_setting('invoice_company_no', 'shop');
$_invoice_footer     = app_setting('invoice_footer', 'shop');

if (empty($_invoice_company)||!empty($_invoice_address)||!empty($_invoice_vat_no)||!empty($_invoice_company_no)) {

    echo "\n\n" . 'OTHER DETAILS' . "\n";
    echo '-------------' . "\n\n";

    if (!empty($_invoice_company)||!empty($_invoice_address)) {

        echo 'MERCHANT' . "\n";
        echo $_invoice_company  ? $_invoice_company : APP_NAME;
        echo $_invoice_address  ? "\n" . $_invoice_address : '';
        echo "\n\n";
    };

    if (!empty($_invoice_vat_no)) {

        echo 'VAT NUMBER' . "\n";
        echo $_invoice_vat_no ? $_invoice_vat_no : '';
        echo "\n\n";
    };

    if (!empty($_invoice_company_no)) {

        echo 'COMPANY NUMBER' . "\n";
        echo $_invoice_company_no ? $_invoice_company_no : '';
        echo "\n\n";
    };
}

if (!empty($_invoice_footer)) {

    echo "\n\n" . $_invoice_footer;
}
