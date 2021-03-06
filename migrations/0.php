<?php

/**
 * Migration:     0
 * Started:         09/01/2015
 * Finalised:     09/01/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration0 extends Base
{
        /**
         * Execute the migration
         * @return Void
         */
        public function execute()
        {
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_attribute` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `label` varchar(100) NOT NULL DEFAULT '',
                    `description` text,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_attribute_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_attribute_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_brand` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(150) DEFAULT NULL,
                    `label` varchar(255) NOT NULL DEFAULT '',
                    `logo_id` int(11) unsigned DEFAULT NULL,
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(300) DEFAULT NULL,
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `logo_id` (`logo_id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_brand_ibfk_1` FOREIGN KEY (`logo_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_brand_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_brand_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_brand_ibfk_4` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_category` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(500) DEFAULT NULL,
                    `slug_end` varchar(150) DEFAULT NULL,
                    `parent_id` int(11) unsigned DEFAULT NULL,
                    `children_ids` varchar(500) DEFAULT NULL,
                    `breadcrumbs` text,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(300) DEFAULT NULL,
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `parent_id` (`parent_id`),
                    KEY `cretead_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `slug` (`slug`(255)),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_category_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_category` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_category_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_category_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_category_ibfk_4` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_collection` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(150) DEFAULT NULL,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(300) DEFAULT NULL,
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_collection_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_collection_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_collection_ibfk_3` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_currency_exchange` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `from` char(3) NOT NULL DEFAULT '',
                    `to` char(3) NOT NULL DEFAULT '',
                    `rate` float(10,6) DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_inform_product_available` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) unsigned NOT NULL,
                    `variation_id` int(11) unsigned DEFAULT NULL,
                    `email` varchar(300) DEFAULT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `product_id` (`product_id`),
                    KEY `variation_id` (`variation_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_inform_product_available_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_inform_product_available_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_inform_product_available_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_inform_product_available_ibfk_4` FOREIGN KEY (`variation_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_variation` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `ref` char(20) NOT NULL DEFAULT '',
                    `code` char(32) NOT NULL DEFAULT '',
                    `user_id` int(11) unsigned DEFAULT NULL,
                    `user_email` varchar(255) DEFAULT NULL,
                    `user_first_name` varchar(150) DEFAULT NULL,
                    `user_last_name` varchar(150) DEFAULT NULL,
                    `user_telephone` varchar(20) DEFAULT NULL,
                    `ip_address` varchar(25) DEFAULT NULL,
                    `currency` char(3) NOT NULL DEFAULT '',
                    `base_currency` char(3) NOT NULL DEFAULT '',
                    `voucher_id` int(11) unsigned DEFAULT NULL,
                    `status` enum('UNPAID','PAID','ABANDONED','CANCELLED','FAILED','PENDING') NOT NULL DEFAULT 'UNPAID',
                    `requires_shipping` tinyint(1) unsigned NOT NULL DEFAULT '1',
                    `delivery_type` enum('DELIVER','COLLECT') NOT NULL DEFAULT 'DELIVER',
                    `fulfilment_status` enum('UNFULFILLED','FULFILLED') NOT NULL DEFAULT 'UNFULFILLED',
                    `note` text,
                    `created` datetime NOT NULL,
                    `modified` datetime NOT NULL,
                    `fulfilled` datetime DEFAULT NULL,
                    `total_base_item` float(10,6) NOT NULL,
                    `total_base_shipping` float(10,6) NOT NULL,
                    `total_base_tax` float(10,6) DEFAULT NULL,
                    `total_base_grand` float(10,6) DEFAULT NULL,
                    `total_user_item` float(10,6) NOT NULL,
                    `total_user_shipping` float(10,6) NOT NULL,
                    `total_user_tax` float(10,6) DEFAULT NULL,
                    `total_user_grand` float(10,6) DEFAULT NULL,
                    `shipping_line_1` varchar(150) DEFAULT NULL,
                    `shipping_line_2` varchar(150) DEFAULT NULL,
                    `shipping_town` varchar(150) DEFAULT NULL,
                    `shipping_state` varchar(150) DEFAULT NULL,
                    `shipping_postcode` varchar(150) DEFAULT NULL,
                    `shipping_country` varchar(150) DEFAULT NULL,
                    `billing_line_1` varchar(150) DEFAULT NULL,
                    `billing_line_2` varchar(150) DEFAULT NULL,
                    `billing_town` varchar(150) DEFAULT NULL,
                    `billing_state` varchar(150) DEFAULT NULL,
                    `billing_postcode` varchar(150) DEFAULT NULL,
                    `billing_country` varchar(150) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `ref` (`ref`),
                    KEY `user_id_2` (`user_id`,`status`),
                    KEY `voucher_id` (`voucher_id`),
                    KEY `user_email` (`user_email`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_ibfk_5` FOREIGN KEY (`voucher_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_voucher` (`id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order_note` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) unsigned NOT NULL,
                    `note` text NOT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_note_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_note_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_note_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order_payment` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) unsigned NOT NULL,
                    `payment_gateway` varchar(100) NOT NULL DEFAULT '',
                    `transaction_id` varchar(100) NOT NULL DEFAULT '',
                    `amount` float(10,6) unsigned NOT NULL,
                    `amount_base` float(10,6) unsigned NOT NULL,
                    `currency` char(3) NOT NULL DEFAULT '',
                    `currency_base` char(3) NOT NULL DEFAULT '',
                    `raw_post` text NOT NULL,
                    `raw_get` text NOT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(10) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_payment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_payment_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON UPDATE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order_product` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) unsigned NOT NULL,
                    `product_id` int(11) unsigned NOT NULL,
                    `product_label` varchar(150) DEFAULT NULL,
                    `variant_id` int(11) unsigned NOT NULL,
                    `variant_label` varchar(150) DEFAULT NULL,
                    `quantity` tinyint(1) unsigned NOT NULL DEFAULT '1',
                    `tax_rate_id` int(11) unsigned DEFAULT NULL,
                    `price_base_value` float(10,2) unsigned NOT NULL,
                    `price_base_value_inc_tax` float(10,2) unsigned NOT NULL,
                    `price_base_value_ex_tax` float(10,2) unsigned NOT NULL,
                    `price_base_value_tax` float(10,2) unsigned NOT NULL,
                    `price_user_value` float(10,2) unsigned NOT NULL,
                    `price_user_value_inc_tax` float(10,2) unsigned NOT NULL,
                    `price_user_value_ex_tax` float(10,2) unsigned NOT NULL,
                    `price_user_value_tax` float(10,2) unsigned NOT NULL,
                    `sale_price_base_value` float(10,2) unsigned NOT NULL,
                    `sale_price_base_value_inc_tax` float(10,2) unsigned NOT NULL,
                    `sale_price_base_value_ex_tax` float(10,2) unsigned NOT NULL,
                    `sale_price_base_value_tax` float(10,2) unsigned NOT NULL,
                    `sale_price_user_value` float(10,2) unsigned NOT NULL,
                    `sale_price_user_value_inc_tax` float(10,2) unsigned NOT NULL,
                    `sale_price_user_value_ex_tax` float(10,2) NOT NULL,
                    `sale_price_user_value_tax` float(10,2) NOT NULL,
                    `processed` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `refunded` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `refunded_date` datetime DEFAULT NULL,
                    `extra_data` text,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`),
                    KEY `product_id` (`product_id`),
                    KEY `variant_id` (`variant_id`),
                    KEY `tax_rate_id` (`tax_rate_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_product_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_product_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_variation` (`id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_product_ibfk_4` FOREIGN KEY (`tax_rate_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_tax_rate` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(150) NOT NULL DEFAULT '',
                    `type_id` int(11) unsigned NOT NULL,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `description` text NOT NULL,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(255) DEFAULT NULL,
                    `seo_keywords` varchar(255) DEFAULT NULL,
                    `tax_rate_id` int(11) unsigned DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
                    `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `is_external` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `external_vendor_label` varchar(150) DEFAULT NULL,
                    `external_vendor_url` varchar(500) DEFAULT NULL,
                    `published` datetime DEFAULT NULL,
                    `created` datetime DEFAULT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime DEFAULT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `type_id` (`type_id`),
                    KEY `created_by` (`created_by`),
                    KEY `tax_rate_id` (`tax_rate_id`),
                    KEY `modified_by` (`modified_by`),
                    KEY `slug` (`slug`,`is_active`,`is_deleted`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_type` (`id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_ibfk_5` FOREIGN KEY (`tax_rate_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_tax_rate` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_attribute` (
                    `product_id` int(11) unsigned NOT NULL,
                    `attribute_id` int(11) unsigned NOT NULL,
                    `value` varchar(255) DEFAULT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `attribute_id` (`attribute_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_attribute_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_attribute_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_attribute` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_brand` (
                    `product_id` int(11) unsigned NOT NULL,
                    `brand_id` int(11) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `brand_id` (`brand_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_brand_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_brand_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_brand` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_category` (
                    `product_id` int(11) unsigned NOT NULL,
                    `category_id` int(11) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `category_id` (`category_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_category_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_category` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_collection` (
                    `product_id` int(11) unsigned NOT NULL,
                    `collection_id` int(11) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `collection_id` (`collection_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_collection_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_collection_ibfk_2` FOREIGN KEY (`collection_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_collection` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_gallery` (
                    `product_id` int(11) unsigned NOT NULL,
                    `object_id` int(11) unsigned DEFAULT NULL,
                    `order` tinyint(1) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `image_id` (`object_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_gallery_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_gallery_ibfk_2` FOREIGN KEY (`object_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_range` (
                    `product_id` int(11) unsigned NOT NULL,
                    `range_id` int(11) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `range_id` (`range_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_range_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_range_ibfk_2` FOREIGN KEY (`range_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_range` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_tag` (
                    `product_id` int(11) unsigned NOT NULL,
                    `tag_id` int(11) unsigned NOT NULL,
                    KEY `product_id` (`product_id`),
                    KEY `tag_id` (`tag_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_tag_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_tag` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_type` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `description` text,
                    `is_physical` tinyint(1) unsigned NOT NULL DEFAULT '1',
                    `ipn_method` varchar(50) DEFAULT NULL,
                    `max_per_order` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `max_variations` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_type_meta_field` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `label` varchar(150) DEFAULT NULL,
                    `admin_form_sub_label` varchar(150) DEFAULT NULL,
                    `admin_form_placeholder` varchar(150) DEFAULT NULL,
                    `admin_form_tip` varchar(150) DEFAULT NULL,
                    `is_filter` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `allow_multiple` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_meta_field_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_meta_field_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_type_meta_taxonomy` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `product_type_id` int(11) unsigned NOT NULL,
                    `meta_field_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `product_type_id` (`product_type_id`),
                    KEY `meta_field_id` (`meta_field_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_meta_taxonomy_ibfk_1` FOREIGN KEY (`product_type_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_type` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_type_meta_taxonomy_ibfk_2` FOREIGN KEY (`meta_field_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_type_meta_field` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_variation` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) unsigned NOT NULL,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `sku` varchar(150) DEFAULT NULL,
                    `stock_status` enum('IN_STOCK','OUT_OF_STOCK') NOT NULL DEFAULT 'OUT_OF_STOCK',
                    `quantity_available` int(11) unsigned DEFAULT NULL,
                    `lead_time` varchar(50) DEFAULT NULL,
                    `order` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `ship_collection_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `ship_driver_data` text,
                    `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `out_of_stock_behaviour` enum('TO_ORDER','OUT_OF_STOCK') NOT NULL DEFAULT 'OUT_OF_STOCK',
                    `out_of_stock_to_order_lead_time` varchar(50) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_gallery` (
                    `variation_id` int(11) unsigned NOT NULL,
                    `object_id` int(11) unsigned NOT NULL,
                    KEY `variation_id` (`variation_id`),
                    KEY `object_id` (`object_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_gallery_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_variation` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_gallery_ibfk_3` FOREIGN KEY (`object_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_price` (
                    `variation_id` int(11) unsigned NOT NULL,
                    `product_id` int(11) unsigned NOT NULL COMMENT 'Used when sorting products by price',
                    `currency` char(3) NOT NULL DEFAULT '',
                    `price` float(10,2) DEFAULT NULL,
                    `sale_price` float(10,2) DEFAULT NULL,
                    KEY `variation_id` (`variation_id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_price_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_variation` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_price_ibfk_4` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_product_type_meta` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `variation_id` int(11) unsigned NOT NULL,
                    `meta_field_id` int(11) unsigned NOT NULL,
                    `value` varchar(150) NOT NULL DEFAULT '',
                    PRIMARY KEY (`id`),
                    KEY `meta_field_id` (`meta_field_id`),
                    KEY `variation_id` (`variation_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_product_type_meta_ibfk_2` FOREIGN KEY (`meta_field_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_type_meta_field` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_variation_product_type_meta_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_variation` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_range` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(150) DEFAULT NULL,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(300) DEFAULT NULL,
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_range_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_range_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_range_ibfk_3` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_sale` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(150) DEFAULT NULL,
                    `label` varchar(255) DEFAULT NULL,
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `date_start` datetime NOT NULL,
                    `date_end` datetime NOT NULL,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(300) DEFAULT NULL,
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_ibfk_1` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_sale_collection` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `sale_id` int(11) unsigned NOT NULL,
                    `collection_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `sale_id` (`sale_id`),
                    KEY `collection_id` (`collection_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_collection_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_sale` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_collection_ibfk_2` FOREIGN KEY (`collection_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_collection` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_sale_product` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `sale_id` int(11) unsigned NOT NULL,
                    `product_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `sale_id` (`sale_id`),
                    KEY `product_id` (`product_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_product_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_sale` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_sale_range` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `sale_id` int(11) unsigned NOT NULL,
                    `range_id` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `sale_id` (`sale_id`),
                    KEY `range_id` (`range_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_range_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_sale` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_sale_range_ibfk_2` FOREIGN KEY (`range_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_range` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_tag` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `slug` varchar(100) DEFAULT NULL,
                    `label` varchar(100) NOT NULL DEFAULT '',
                    `cover_id` int(11) unsigned DEFAULT NULL,
                    `description` text,
                    `seo_title` varchar(150) DEFAULT NULL,
                    `seo_description` varchar(255) DEFAULT '',
                    `seo_keywords` varchar(150) DEFAULT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `label` (`label`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    KEY `cover_id` (`cover_id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_tag_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_tag_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_tag_ibfk_3` FOREIGN KEY (`cover_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_tax_rate` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `rate` float(10,4) NOT NULL,
                    `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_tax_rate_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_tax_rate_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->query("
                CREATE TABLE `{{NAILS_DB_PREFIX}}shop_voucher` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `code` varchar(25) NOT NULL,
                    `type` enum('NORMAL','LIMITED_USE','GIFT_CARD') NOT NULL DEFAULT 'NORMAL',
                    `discount_type` enum('PERCENTAGE','AMOUNT') NOT NULL DEFAULT 'PERCENTAGE',
                    `discount_value` float(10,6) unsigned NOT NULL,
                    `discount_application` enum('PRODUCTS','PRODUCT_TYPES','SHIPPING','ALL') NOT NULL DEFAULT 'PRODUCTS',
                    `label` varchar(150) NOT NULL DEFAULT '',
                    `valid_from` datetime NOT NULL,
                    `valid_to` datetime DEFAULT NULL,
                    `use_count` tinyint(1) unsigned NOT NULL,
                    `limited_use_limit` int(11) unsigned NOT NULL,
                    `gift_card_balance` float(10,6) unsigned NOT NULL,
                    `product_type_id` int(11) unsigned DEFAULT NULL,
                    `created` datetime NOT NULL,
                    `created_by` int(11) unsigned DEFAULT NULL,
                    `modified` datetime NOT NULL,
                    `modified_by` int(11) unsigned DEFAULT NULL,
                    `last_used` datetime DEFAULT NULL,
                    `is_active` tinyint(1) unsigned NOT NULL,
                    `is_deleted` tinyint(1) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `code` (`code`),
                    KEY `code_2` (`code`,`is_deleted`),
                    KEY `code_3` (`code`,`is_active`,`is_deleted`),
                    KEY `product_type_id` (`product_type_id`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_by` (`modified_by`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_voucher_ibfk_1` FOREIGN KEY (`product_type_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product_type` (`id`),
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_voucher_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `{{NAILS_DB_PREFIX}}shop_voucher_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }
}
