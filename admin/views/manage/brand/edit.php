<div class="group-shop manage brands edit">
    <?php

        if ($isFancybox) {

            echo '<h1>' . $page->title . '</h1>';
            $class = 'system-alert';

        } else {

            $class = '';
        }

        echo form_open(uri_string() . $isFancybox);

    ?>
    <p class="<?=$class?>">
        Manage which brands are available for your products.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab">
        <?php

            echo anchor(
                'admin/shop/manage/brand' . $isFancybox,
                'Overview',
                'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."'
            );

        ?>
        </li>
        <li class="tab active">
            <?=anchor('admin/shop/manage/brand/create' . $isFancybox, 'Create Brand')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
            <fieldset>
                <legend>Basic Details</legend>
                <p>
                    These fields describe the brand.
                </p>
                <?php

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'label';
                    $field['label']       = 'Label';
                    $field['required']    = true;
                    $field['default']     = isset($brand->label) ? $brand->label : '';
                    $field['placeholder'] = 'The label to give your brand';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'logo_id';
                    $field['label']   = 'Logo';
                    $field['default'] = isset($brand->logo_id) ? $brand->logo_id : '';
                    $field['bucket']  = 'shop-brand-logo';

                    echo form_field_mm_image($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'cover_id';
                    $field['label']   = 'Cover Image';
                    $field['default'] = isset($brand->cover_id) ? $brand->cover_id : '';
                    $field['bucket']  = 'shop-brand-cover';

                    echo form_field_mm_image($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'description';
                    $field['label']       = 'Description';
                    $field['placeholder'] = 'This text may be used on the brand\'s overview page.';
                    $field['default']     = isset($brand->description) ? $brand->description : '';

                    echo form_field_wysiwyg($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'is_active';
                    $field['label']   = 'Active';
                    $field['default'] = isset($brand->is_active) ? $brand->is_active : true;

                    echo form_field_boolean($field);

                ?>
            </fieldset>
            <fieldset>
                <legend>Search Engine Optimisation</legend>
                <p>
                    These fields help describe the brand to search engines. These fields won't be seen publicly.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'seo_title';
                    $field['label']       = 'SEO Title';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'An alternative, SEO specific title for the brand.';
                    $field['default']      = isset($brand->seo_title) ? $brand->seo_title : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_description';
                    $field['label']       = 'SEO Description';
                    $field['sub_label']   = 'Max. 300 characters';
                    $field['type']        = 'textarea';
                    $field['placeholder'] = 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
                    $field['default']     = isset($brand->seo_description) ? $brand->seo_description : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_keywords';
                    $field['label']       = 'SEO Keywords';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
                    $field['default']     = isset($brand->seo_keywords) ? $brand->seo_keywords : '';

                    echo form_field($field);

                ?>
            </fieldset>
            <p style="margin-top:1em;">
            <?php

                echo form_submit('submit', 'Save', 'class="awesome"');
                echo anchor(
                    'admin/shop/manage/brand' . $isFancybox,
                    'Cancel',
                    'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
                );

            ?>
            </p>
        </div>
    </section>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $brands));
