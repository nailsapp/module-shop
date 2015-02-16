<?php

/**
 * This model manages Shop Product categories
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

class NAILS_Shop_category_model extends NAILS_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->_table        = NAILS_DB_PREFIX . 'shop_category';
        $this->_table_prefix = 'sc';

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }


    // --------------------------------------------------------------------------


    public function create($data, $return_object = false)
    {
        //  Some basic sanity testing
        if (empty($data->label)) {

            $this->_set_error('"label" is a required field.');
            return false;
        }

        if (empty($data->cover_id)) {

            $data->cover_id = null;
        }

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        //  Create a new blank object to work with
        $_data = array('label' => $data->label);
        $_id   = parent::create($_data);

        if (!$_id) {

            $this->_set_error('Unable to create base category object.');
            $this->db->trans_rollback();
            return false;

        } elseif ($this->update($_id, $data)) {

            $this->db->trans_commit();

            if ($return_object) {

                return $this->get_by_id($_id);

            } else {

                return $_id;
            }

        } else {

            $this->db->trans_rollback();
            return false;
        }
    }


    // --------------------------------------------------------------------------


    public function update($id, $data = array())
    {
        $_data = new \stdClass();

        // --------------------------------------------------------------------------

        //  Prep the data
        if (empty($data->label)) {

            $this->_set_error('"label" is a required field.');
            return false;

        } else {

            $_data->label = trim($data->label);
        }

        if (isset($data->parent_id)) {

            $_data->parent_id = (int) $data->parent_id;

            if (empty($_data->parent_id)) {

                $_data->parent_id = null;
            }

            if ($_data->parent_id == $id) {

                $this->_set_error('"parent_id" cannot be the same as the category\'s ID.');
                return false;
            }
        }

        if (!empty($data->cover_id)) {

            $_data->cover_id = $data->cover_id;
        }

        if (isset($data->description)) {

            $_data->description = $data->description;
        }

        if (isset($data->seo_title)) {

            $_data->seo_title = strip_tags($data->seo_title);
        }

        if (isset($data->seo_description)) {

            $_data->seo_description = strip_tags($data->seo_description);
        }

        if (isset($data->seo_keywords)) {

            $_data->seo_keywords = strip_tags($data->seo_keywords);
        }

        // --------------------------------------------------------------------------

        //  Generate the slug
        //  If there's a parent then prefix the slug with the parent's slug

        if (!empty($_data->parent_id)) {

            $this->db->select('slug');
            $this->db->where('id', $_data->parent_id);
            $_parent = $this->db->get($this->_table)->row();

            if (empty($_parent)) {

                $_prefix = '';

                //  Also, invalid aprent, so null out parent_id
                $_data->parent_id = null;

            } else {

                $_prefix = $_parent->slug . '/';

            }

        } else {

            //  No parent == no prefix
            $_prefix = '';

        }

        $_data->slug        = $this->_generate_slug($_data->label, $_prefix, '', null, null, $id);
        $_data->slug_end    = array_pop(explode('/', $_data->slug));

        // --------------------------------------------------------------------------

        //  Find all childen
        $_data->children_ids = implode(',', $this->get_ids_of_children($id));

        if (empty($_data->children_ids)) {

            $_data->children_ids = null;

        }

        //  And all the [old] parents
        $_parents = $this->get_ids_of_parents($id);

        // --------------------------------------------------------------------------

        //  Attempt the update
        $this->db->trans_begin();

        if (parent::update($id, $_data)) {

            //  Success!Generate this category's breadcrumbs
            $_data              = new \stdClass();
            $_data->breadcrumbs = json_encode($this->_generate_breadcrumbs($id));

            if (!parent::update($id, $_data)) {

                $this->db->trans_rollback();
                $this->_set_error('Failed to update category breadcrumbs.');
                return false;

            }

            // --------------------------------------------------------------------------

            //  Also regenerate breadcrumbs and slugs for all children
            $_children = $this->get_ids_of_children($id);

            if ($_children) {

                foreach ($_children as $child_id) {

                    $_child_data = new \stdClass();

                    //  Breadcrumbs is easy
                    $_child_data->breadcrumbs = json_encode($this->_generate_breadcrumbs($child_id));

                    //  Slugs are slightly harder, we need to get the child's parent's slug
                    //  and use it as a prefix

                    $this->db->select('parent_id, label');
                    $this->db->where('id', $child_id);
                    $_child = $this->db->get($this->_table)->row();

                    if (!empty($_child)) {

                        $this->db->select('slug');
                        $this->db->where('id', $_child->parent_id);
                        $_parent = $this->db->get($this->_table)->row();
                        $_prefix = empty($_parent) ? '' : $_parent->slug . '/';

                        $_child_data->slug      = $this->_generate_slug($_child->label, $_prefix, '', null, null, $child_id);
                        $_child_data->slug_end  = array_pop(explode('/', $_child_data->slug));

                    }

                    if (!parent::update($child_id, $_child_data)) {

                        $this->db->trans_rollback();
                        $this->_set_error('Failed to update child category.');
                        return false;

                    }

                }

            }

            // --------------------------------------------------------------------------

            //  Fetch the new parents
            $_parents = array_merge($_parents, $this->get_ids_of_parents($id));
            $_parents = array_filter($_parents);
            $_parents = array_unique($_parents);

            foreach ($_parents as $parent_id) {

                $_data                  = new \stdClass();
                $_data->children_ids    = implode(',', $this->get_ids_of_children($parent_id));

                if (empty($_data->children_ids)) {

                    $_data->children_ids = null;

                }

                if (!parent::update($parent_id, $_data)) {

                    $this->db->trans_rollback();
                    $this->_set_error('Failed to update parent\'s children IDs.');
                    return false;

                }

            }

            // --------------------------------------------------------------------------

            $this->db->trans_commit();
            return true;

        } else {

            $this->db->trans_rollback();
            return false;

        }
    }


    // --------------------------------------------------------------------------


    public function delete($id)
    {
        $_current = $this->get_by_id($id);

        if (!$_current) {

            $this->_set_error('Invalid Category ID');
            return false;

        }

        $_parents = $this->get_ids_of_parents($id);

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        if (parent::delete($id)) {

            foreach ($_parents as $parent_id) {

                $_data                  = new \stdClass();
                $_data->children_ids    = implode(',', $this->get_ids_of_children($parent_id));

                if (empty($_data->children_ids)) {

                    $_data->children_ids = null;

                }

                if (!parent::update($parent_id, $_data)) {

                    $this->db->trans_rollback();
                    $this->_set_error('Failed to update parent\'s children IDs.');
                    return false;

                }

            }

            $this->db->trans_commit();
            return true;

        } else {

            $this->_set_error('Invalid Category ID');
            $this->db->trans_rollback();
            return false;

        }

    }


    // --------------------------------------------------------------------------


    protected function _generate_breadcrumbs($id)
    {
        //  Fetch current
        $this->db->select('id,slug,label');
        $this->db->where('id', $id);
        $_current = $this->db->get($this->_table)->result();

        if (empty($_current)) {

            return false;

        }

        //  Fetch parents
        $_parents = $this->get_ids_of_parents($id);

        if (!empty($_parents)) {

            $this->db->select('id,slug,label');
            $this->db->where_in('id', $_parents);
            $_parents = $this->db->get($this->_table)->result();

        }

        //  Finally, build breadcrumbs
        return array_merge($_parents, $_current);
    }


    // --------------------------------------------------------------------------


    public function get_ids_of_parents($id)
    {
        $_return = array();

        $this->db->select('parent_id');
        $this->db->where('id', $id);
        $_parent = $this->db->get($this->_table)->row();

        if (!empty($_parent->parent_id)) {

            $_temp      = array($_parent->parent_id);
            $_return    = array_merge($_return, $_temp, $this->get_ids_of_parents($_parent->parent_id));

        }

        return array_unique(array_filter($_return));
    }


    // --------------------------------------------------------------------------


    /**
     * Fetches the IDs of a category's descendants
     * @param  int   $id             The ID of the starting category
     * @param  boolean $only_immediate Whether to recurscively fetch all descendants, or just the immediate descendants
     * @return array
     */
    public function get_ids_of_children($id, $only_immediate = false)
    {
        $_return = array();

        $this->db->select('id');
        $this->db->where('parent_id', $id);
        $_children = $this->db->get($this->_table)->result();

        if ($only_immediate) {

            foreach ($_children as $child) {

                $_return[] = $child->id;

            }

        } else {

            if (!empty($_children)) {

                foreach ($_children as $child) {

                    $_temp      = array($child->id);
                    $_return    = array_merge($_return, $_temp, $this->get_ids_of_children($child->id, false));

                }

            }

        }

        return array_unique(array_filter($_return));
    }


    // --------------------------------------------------------------------------


    /**
     * Gets a category's descendants in object form
     * @param  int   $category_id   The ID of the category
     * @param  boolean $only_immediate Whether to recurscively fetch all descendants, or just the immediate descendants
     * @return array
     */
    public function get_children($category_id, $only_immediate = false, $_data = array())
    {
        $_children = $this->get_ids_of_children($category_id, $only_immediate);

        if (!empty($_children)) {

            return $this->get_by_ids($_children, $_data);

        }

        return array();
    }


    // --------------------------------------------------------------------------


    public function get_ids_of_siblings($category_id)
    {
        $this->db->select('parent_id');
        $this->db->where('id', $category_id);
        $_parent = $this->db->get($this->_table)->row();

        if (!$_parent) {

            return array();

        }

        $this->db->where('id !=', $category_id);
        return $this->get_ids_of_children($_parent->parent_id, true);
    }


    // --------------------------------------------------------------------------


    public function get_siblings($category_id, $_data = array())
    {
        $_children = $this->get_ids_of_siblings($category_id);

        if (!empty($_children)) {

            return $this->get_by_ids($_children, $_data);

        }

        return array();
    }


    // --------------------------------------------------------------------------


    public function get_all_nested($_parent_id = null, $data = array())
    {
        return $this->_nest_items($this->get_all(null, null, $data), null);
    }


    // --------------------------------------------------------------------------


    public function get_top_level($data = array())
    {
        if (empty($data['where'])) {

            $data['where'][] = array('column' => 'parent_id', 'value' => null);

        }

        if (!isset($data['include_count'])) {

            $data['include_count'] = true;

        }

        // --------------------------------------------------------------------------

        return $this->get_all(null, null, $data);
    }


    // --------------------------------------------------------------------------


    /**
     *  Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     **/
    protected function _nest_items(&$list, $parent = null)
    {
        $result = array();

        for ($i = 0, $c = count($list); $i < $c; $i++) :

            if ($list[$i]->parent_id == $parent) {

                $list[$i]->children = $this->_nest_items($list, $list[$i]->id);
                $result[]           = $list[$i];

            }

        endfor;

        return $result;
    }

    // --------------------------------------------------------------------------


    public function get_all_nested_flat($separator = ' &rsaquo; ')
    {
        $_categories    = $this->get_all();
        $_out           = array();

        foreach ($_categories as $cat) {

            $_out[$cat->id] = array();

            foreach ($cat->breadcrumbs as $crumb) {

                $_out[$cat->id][] = $crumb->label;

            }

            $_out[$cat->id] = implode($separator, $_out[$cat->id]);

        }

        return $_out;
    }


    // --------------------------------------------------------------------------


    protected function _getcount_common($data = array(), $_caller = null)
    {
        if (empty($data['sort'])) {

            $data['sort'] = 'slug';

        } else {

            $data = array('sort' => 'slug');

        }

        // --------------------------------------------------------------------------

        if (!empty($data['include_count'])) {

            if (empty($this->db->ar_select)) {

                //   No selects have been called, call this so that we don't *just* get the product count
                $_prefix = $this->_table_prefix ? $this->_table_prefix . '.' : '';
                $this->db->select($_prefix . '*');

            }

            $query  = 'SELECT COUNT(DISTINCT(`nspc`.`product_id`)) ';
            $query .= 'FROM ' . NAILS_DB_PREFIX . 'shop_product_category nspc ';
            $query .= 'JOIN ' . NAILS_DB_PREFIX . 'shop_product nsp ON `nspc`.`product_id` = `nsp`.`id` ';
            $query .= 'WHERE ';
            $query .= '(';
            $query .= '`nspc`.`category_id` = `' . $this->_table_prefix . '`.`id` ';
            $query .= 'OR FIND_IN_SET (`nspc`.`category_id`, `' . $this->_table_prefix . '`.`children_ids`)';
            $query .= ') ';
            $query .= 'AND `nsp`.`is_active` = 1 ';
            $query .= 'AND `nsp`.`is_deleted` = 0';

            $this->db->select('(' . $query . ') product_count', false);

        }

        // --------------------------------------------------------------------------

        return parent::_getcount_common($data, $_caller);
    }


    // --------------------------------------------------------------------------


    public function format_url($slug)
    {
        return site_url($this->shopUrl . 'category/' . $slug);
    }


    // --------------------------------------------------------------------------


    /**
     * If the seo_description or seo_keywords fields are empty this method will
     * generate some content for category.
     * @param  object $category A category object
     * @return void
     */
    public function generate_seo_content(&$category)
    {
        /**
         * Autogenerate some SEO content if it's not been set
         * Buy {{CATEGORY}} at {{STORE}}
         **/

        if (empty($category->seo_description)) {

            //  Base string
            $category->seo_description = 'Buy ' . $category->label . ' at ' . APP_NAME;
            $category->seo_description = htmlentities($category->seo_description);

        }

        if (empty($category->seo_keywords)) {

            //  Extract common keywords
            $this->lang->load('shop/shop');
            $_common = explode(',', lang('shop_common_words'));
            $_common = array_unique($_common);
            $_common = array_filter($_common);

            //  Remove them and return the most popular words
            $_description = strtolower($category->description);
            $_description = str_replace("\n", ' ', strip_tags($_description));
            $_description = str_word_count($_description, 1);
            $_description = array_count_values($_description    );
            arsort($_description);
            $_description = array_keys($_description);
            $_description = array_diff($_description, $_common);
            $_description = array_slice($_description, 0, 10);

            $category->seo_keywords = implode(',', $_description);

            //  Encode entities
            $category->seo_keywords = htmlentities($category->seo_keywords);

        }
    }


    // --------------------------------------------------------------------------


    protected function _format_object(&$object)
    {
        //  Type casting
        $object->id             = (int) $object->id;
        $object->parent_id      = $object->parent_id ? (int) $object->parent_id : null;
        $object->created_by     = $object->created_by ? (int) $object->created_by : null;
        $object->modified_by    = $object->modified_by ? (int) $object->modified_by : null;
        $object->children       = array();

        $object->breadcrumbs    = (array) @json_decode($object->breadcrumbs);

        $object->depth          = count(explode('/', $object->slug)) - 1;
        $object->url            = $this->format_url($object->slug);
    }
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_CATEGORY_MODEL')) {

    class Shop_category_model extends NAILS_Shop_category_model
    {
    }

}

/* End of file shop_category_model.php */
/* Location: ./modules/shop/models/shop_category_model.php */