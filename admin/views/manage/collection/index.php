<div class="group-shop manage collections overview">
    <p>
        Manage which collections are available for your products. Products grouped together
        into a collection are deemed related and can have their own customised landing page.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label &amp; Description</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="active">Active</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($collections) {

                    foreach ($collections as $collection) {

                        echo '<tr>';
                            echo '<td class="label">';

                            echo $collection->label;
                            echo $collection->description ? '<small>' . character_limiter(strip_tags($collection->description), 225) . '</small>' : '<small>No Description</small>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($collection->product_count) ? 'Unknown' : $collection->product_count;
                            echo '</td>';

                            echo \Nails\Admin\Helper::loadDatetimeCell($collection->modified);
                            echo \Nails\Admin\Helper::loadBoolCell($collection->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin.shop:0.collection_edit')) {

                                    echo anchor('admin/shop/manage/collection/edit/' . $collection->id . $isModal, lang('action_edit'), 'class="awesome small"');
                                }

                                if (userHasPermission('admin.shop:0.collection_delete')) {

                                    echo anchor('admin/shop/manage/collection/delete/' . $collection->id . $isModal, lang('action_delete'), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."');
                                }

                                echo anchor($shopUrl . 'collection/' . $collection->slug, lang('action_view'), 'class="awesome small orange" target="_blank"');

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Collections Found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $collections));
