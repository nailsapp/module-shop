<div class="group-shop manage product-type-meta overview">
    <?php

        if ($isFancybox) {

            echo '<h1>' . $page->title . '</h1>';
            $class = 'system-alert';

        } else {

            $class = '';

        }

    ?>
    <p class="<?=$class?>">
        Product Type Meta fields allow the shop to store additional information for variants. The store
        also uses this data to provide a user friendly filtering system which responds to the products
        available in the current view.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab active">
            <?=anchor('admin/shop/manage/product_type_meta' . $isFancybox, 'Overview')?>
        </li>
        <li class="tab">
            <?=anchor('admin/shop/manage/productTypeMeta/create' . $isFancybox, 'Create Product Type Meta Field')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="label">Label</th>
                            <th class="label">Associated Product Types</th>
                            <th class="modified">Modified</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php

                        if ($meta_fields) {

                            foreach ($meta_fields as $field) {

                                echo '<tr>';
                                    echo '<td class="label">';
                                        echo $field->label;
                                    echo '</td>';
                                    echo '<td class="associated">';
                                        foreach ($field->associated_product_types as $association) {

                                            echo '<span class="badge">' . anchor('admin/shop/manage/productType/edit/' . $association->id, $association->label) . '</span>';
                                        }
                                    echo '</td>';
                                    echo \Nails\Admin\Helper::loadDatetimeCell($field->modified);
                                    echo '<td class="actions">';

                                        if (userHasPermission('admin.shop:0.product_type_meta_edit')) {

                                            echo anchor(
                                                'admin/shop/manage/productTypeMeta/edit/' . $field->id . $isFancybox,
                                                lang('action_edit'),
                                                'class="awesome small"'
                                            );
                                        }

                                        if (userHasPermission('admin.shop:0.product_type_meta_delete')) {

                                            echo anchor(
                                                'admin/shop/manage/productTypeMeta/delete/' . $field->id . $isFancybox,
                                                lang('action_delete'),
                                                'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."'
                                            );
                                        }

                                    echo '</td>';
                                echo '</tr>';

                            }

                        } else {

                            echo '<tr>';
                                echo '<td colspan="8" class="no-data">';
                                    echo 'No Meta Fields Found';
                                echo '</td>';
                            echo '</tr>';
                        }

                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>