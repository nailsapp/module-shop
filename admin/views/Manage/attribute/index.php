<div class="group-shop manage attributes overview">
    <p>
        Manage which attributes are available for your products.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label &amp; Description</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($attributes) {

                    foreach ($attributes as $attribute) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $attribute->label;
                                echo $attribute->description ? '<small>' . character_limiter(strip_tags($attribute->description), 225) . '</small>' : '<small>No Description</small>';
                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($attribute->product_count) ? 'Unknown' : $attribute->product_count;
                            echo '</td>';
                            echo adminHelper('loadDatetimeCell', $attribute->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:attribute:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/attribute/edit/' . $attribute->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:attribute:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/attribute/delete/' . $attribute->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No Attributes Found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>
<?php

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $attributes));
