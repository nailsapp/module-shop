<?php

    //    Counter string
    //    If the $variation var is passed then we're loading this in PHP and we want
    //    to prefill the fields. The form_helper functions don't pick up the fields
    //    automatically because of the Mustache ' . $_counter . ' variable.

    $_counter = isset($attribute) ? $counter : '{{counter}}';

?>
<tr class="attribute">
    <td class="attribute">
        <?php

            $_selected = isset($attribute['attribute_id']) ? $attribute['attribute_id'] : null;
            echo form_dropdown('attributes[' . $_counter . '][attribute_id]', $attributes, $_selected    , 'class="attributes select2"');

        ?>
    </td>
    <td class="value">
        <?php

            $_value = isset($attribute['value']) ? $attribute['value'] : null;
            echo form_input('attributes[' . $_counter . '][value]', $_value, 'placeholder="Specify the value"');

        ?>
    </td>
    <td class="delete">
        <a href="#" class="delete">
            <b class="fa fa-times-circle fa-lg"></b>
        </a>
    </td>
</tr>