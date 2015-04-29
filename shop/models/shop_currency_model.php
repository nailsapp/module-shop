<?php

/**
 * This model manages Shop Currencies
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

class NAILS_Shop_currency_model extends NAILS_Model
{
    protected $oerUrl;
    protected $rates;

    // --------------------------------------------------------------------------

    /**
     * Construct the model, define defaults and load dependencies.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Load required config file
        $this->config->load('shop/currency');

        // --------------------------------------------------------------------------

        //  Defaults
        $this->oerUrl = 'http://openexchangerates.org/api/latest.json';
        $this->rates  = null;
    }

    // --------------------------------------------------------------------------

    /**
     * Get all defined currencies.
     * @return array
     */
    public function getAll()
    {
        return $this->config->item('currency');
    }

    // --------------------------------------------------------------------------

    /**
     * Get all defined currencies as a flat array; the index is the currency's code,
     * the value is the currency's label.
     * @return array
     */
    public function getAllFlat()
    {
        $out      = array();
        $currency = $this->getAll();

        foreach ($currency as $c) {

            $out[$c->code] = $c->label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all currencies supported by the shop.
     * @return array
     */
    public function getAllSupported()
    {
        $currencies = $this->getAll();
        $additional = app_setting('additional_currencies', 'shop');
        $base       = app_setting('base_currency', 'shop');
        $supported  = array();

        if (isset($currencies[$base])) {

            $supported[] = $currencies[$base];
        }

        if (is_array($additional)) {

            foreach ($additional as $additional) {

                if (isset($currencies[$additional])) {

                    $supported[] = $currencies[$additional];
                }
            }
        }

        return $supported;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all supported currencies as a flat array; the index is the currency's
     * code, the value is the currency's label.
     * @return array
     */
    public function getAllSupportedFlat()
    {
        $out      = array();
        $currency = $this->getAllSupported();

        foreach ($currency as $c) {

            $out[$c->code] = $c->label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets an individual currency by it's 3 letter code.
     * @param  string $code The code to return
     * @return mixed        stdClass on success, false on failure
     */
    public function getByCode($code)
    {
        $currency = $this->getAll();

        return !empty($currency[$code]) ? $currency[$code] : false;
    }

    // --------------------------------------------------------------------------

    /**
     * Syncs exchange rates to the Open Exchange Rates service.
     * @param  boolean $muteLog Whether or not to write errors to the log
     * @return boolean
     */
    public function sync($muteLog = true)
    {
        $oerAppId             = app_setting('openexchangerates_app_id', 'shop');
        $oerEtag              = app_setting('openexchangerates_etag', 'shop');
        $oerLastModified      = app_setting('openexchangerates_last_modified', 'shop');
        $additionalCurrencies = app_setting('additional_currencies', 'shop');

        if (empty($additionalCurrencies)) {

            $message = 'No additional currencies are supported, aborting sync.';
            $this->_set_error($message);

            if (empty($muteLog)) {

                _LOG('... ' . $message);
            }

            return false;
        }

        if ($oerAppId) {

            //  Make sure we know what the base currency is
            if (defined('SHOP_BASE_CURRENCY_CODE')) {

                $this->load->model('shop/shop_model');
            }

            if (empty($muteLog)) {

                _LOG('... Base Currency is ' . SHOP_BASE_CURRENCY_CODE);
            }

            /**
             * Set up the CURL request
             * First attempt to get the rates using the Shop's base currency
             * (only available to paid subscribers, but probably more accurate)
             */

            $this->load->library('curl/curl');

            $params            = array();
            $params['app_id']  = $oerAppId;
            $params['base']    = SHOP_BASE_CURRENCY_CODE;

            $this->curl->create($this->oerUrl . '?' . http_build_query($params));
            $this->curl->option(CURLOPT_FAILONERROR, false);
            $this->curl->option(CURLOPT_HEADER, true);

            if (!empty($oerEtag) && !empty($oerLastModified)) {

                $this->curl->http_header('If-None-Match', '"' . $oerEtag . '"');
                $this->curl->http_header('If-Modified-Since', $oerLastModified);
            }

            $response = $this->curl->execute();

            /**
             * If this failed, it's probably due to requesting a non-USD base
             * Try again with but using USD base this time.
             */

            if (empty($this->curl->info['http_code']) || $this->curl->info['http_code'] != 200) {

                //  Attempt to extract the body and see if the reason is an invalid App ID
                $response = explode("\r\n\r\n", $response, 2);
                $response = !empty($response[1]) ? @json_decode($response[1]) : null;

                if (!empty($response->message) && $response->message == 'invalid_app_id') {

                    $message = $oerAppId . ' is not a valid OER app ID.';
                    $this->_set_error($message);

                    if (empty($muteLog)) {

                        _LOG($message);
                    }

                    return false;
                }

                if (empty($muteLog)) {

                    _LOG('... Query using base as ' . SHOP_BASE_CURRENCY_CODE  . ' failed, trying agian using USD');
                }

                $params['base'] = 'USD';

                $this->curl->create($this->oerUrl . '?' . http_build_query($params));
                $this->curl->option(CURLOPT_FAILONERROR, false);
                $this->curl->option(CURLOPT_HEADER, true);

                if (!empty($oerEtag) && !empty($oerLastModified)) {

                    $this->curl->http_header('If-None-Match', '"' . $oerEtag . '"');
                    $this->curl->http_header('If-Modified-Since', $oerLastModified);
                }

                $response = $this->curl->execute();

            } elseif (!empty($this->curl->info['http_code']) && $this->curl->info['http_code'] == 304) {

                //  304 Not Modified, abort sync.
                if (empty($muteLog)) {

                    _LOG('... OER reported 304 Not Modified, aborting sync');
                }

                return true;
            }

            if (!empty($this->curl->info['http_code']) && $this->curl->info['http_code'] == 200) {

                /**
                 * Ok, now we know the rates we need to work out what the base_exchange rate is.
                 * If the store's base rate is the same as the API's base rate then we're golden,
                 * if it's not then we'll need to do some calculations.
                 *
                 * Attempt to extract the headers (so we can use the E-Tag) and then parse
                 * the body.
                 */

                $response = explode("\r\n\r\n", $response, 2);

                if (empty($response[1])) {

                    $message = 'Could not extract the body of the request.';
                    $this->_set_error($message);

                    if (empty($muteLog)) {

                        _LOG($message);
                        _LOG(print_r($response, true));
                    }

                    return false;
                }

                //  Body
                $response[1] = !empty($response[1]) ? @json_decode($response[1]) : null;

                if (empty($response[1])) {

                    $message = 'Could not parse the body of the request.';
                    $this->_set_error($message);

                    if (empty($muteLog)) {

                        _LOG($message);
                        _LOG(print_r($response, true));
                    }

                    return false;
                }

                //  Headers, look for the E-Tag and last modified
                preg_match('/ETag: "(.*?)"/', $response[0], $matches);
                if (!empty($matches[1])) {

                    //  Save ETag to shop settings
                    set_app_setting('openexchangerates_etag', 'shop', $matches[1]);
                }

                preg_match('/Last-Modified{ (.*)/', $response[0], $matches);
                if (!empty($matches[1])) {

                    //  Save Last-Modified to shop settings
                    set_app_setting('openexchangerates_last_modified', 'shop', $matches[1]);
                }

                $response = $response[1];

                $toSave = array();

                if (SHOP_BASE_CURRENCY_CODE == $response->base) {

                    foreach ($response->rates as $toCurrency => $rate) {

                        if (array_search($toCurrency, $additionalCurrencies) !== false) {

                            if (empty($muteLog)) {

                                _LOG('... ' . $toCurrency . ' > ' . $rate);
                            }

                            $toSave[] = array(
                                'from'     => $response->base,
                                'to'       => $toCurrency,
                                'rate'     => $rate,
                                'modified' => date('Y-m-d H:i{s')
                            );
                        }
                    }

                } else {

                    if (empty($muteLog)) {

                        _LOG('... API base is ' . $response->base . '; calculating differences...');
                    }

                    $base = 1;
                    foreach ($response->rates as $code => $rate) {

                        if ($code == SHOP_BASE_CURRENCY_CODE) {

                            $base = $rate;
                            break;
                        }
                    }

                    foreach ($response->rates as $toCurrency => $rate) {

                        if (array_search($toCurrency, $additionalCurrencies) !== false) {

                            //  We calculate the new exchange rate as so: $rate / $base
                            $newRate  = $rate / $base;
                            $toSave[] = array(
                                'from'     => SHOP_BASE_CURRENCY_CODE,
                                'to'       => $toCurrency,
                                'rate'     => $newRate,
                                'modified' => date('Y-m-d H:i{s')
                            );

                            if (empty($muteLog)) {

                                _LOG('... Calculating and saving new exchange rate for ' . SHOP_BASE_CURRENCY_CODE . ' > ' . $toCurrency . ' (' . $newRate . ')');
                            }
                        }
                    }
                }

                // --------------------------------------------------------------------------

                /**
                 * Ok, we've done all the BASE -> CURRENCY conversions, now how about we work
                 * out the reverse?
                 */
                $toSaveReverse = array();

                //  Easy one first, base to base, base, bass, drop da bass. BASS.
                $toSaveReverse[] = array(
                    'from'     => SHOP_BASE_CURRENCY_CODE,
                    'to'       => SHOP_BASE_CURRENCY_CODE,
                    'rate'     => 1,
                    'modified' => date('Y-m-d H:i{s')
                );

                foreach ($toSave as $old) {

                    $toSaveReverse[] = array(
                        'from'     => $old['to'],
                        'to'       => SHOP_BASE_CURRENCY_CODE,
                        'rate'     => 1 / $old['rate'],
                        'modified' => date('Y-m-d H:i{s')
                    );

                }

                $toSave = array_merge($toSave, $toSaveReverse);

                // --------------------------------------------------------------------------

                if ($this->db->truncate(NAILS_DB_PREFIX . 'shop_currency_exchange')) {

                    if (!empty($toSave)) {

                        if ($this->db->insert_batch(NAILS_DB_PREFIX . 'shop_currency_exchange', $toSave)) {

                            return true;

                        } else {

                            $message = 'Failed to insert new currency data.';
                            $this->_set_error($message);

                            if (empty($muteLog)) {

                                _LOG('... ' . $message);
                            }

                            return false;
                        }

                    } else {

                        return true;
                    }

                } else {

                    $message = 'Failed to truncate currency table.';
                    $this->_set_error($message);

                    if (empty($muteLog)) {

                        _LOG('... ' . $message);
                    }

                    return false;
                }

            } elseif (!empty($this->curl->info['http_code']) && $this->curl->info['http_code'] == 304) {

                //  304 Not Modified, abort sync.
                if (empty($muteLog)) {

                    _LOG('... OER reported 304 Not Modified, aborting sync');
                }

                return true;

            } else {

                //  Attempt to extract the body so we can get our failure reason
                $response = explode("\r\n\r\n", $response, 2);
                $response = !empty($response[1]) ? @json_decode($response[1]) : null;

                $message = 'An error occurred when querying the API.';
                $this->_set_error($message);

                if (empty($muteLog)) {

                    _LOG('... ' . $message);
                    _LOG(print_r($response, true));
                }

                return false;
            }

        } else {

            $message = '`openexchangerates_app_id` setting is not set. Sync aborted.';
            $this->_set_error($message);

            if (empty($muteLog)) {

                _LOG('... ' . $message);
            }

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value between currencies.
     * @param  mixed  $value The value to convert
     * @param  string $from  The currency to convert from
     * @param  string $to    The currency to convert too
     * @return mixed         Float on success, false on failure
     */
    public function convert($value, $from, $to)
    {
        /**
         * If we're "converting" between the same currency then we don't need to
         * look up rates
         */
        if ($from === $to) {

            return $value;
        }

        // --------------------------------------------------------------------------

        $currencyFrom = $this->getByCode($from);

        if (!$currencyFrom) {

            $this->_set_error('Invalid `from` currency code.');
            return false;
        }

        $currencyTo = $this->getByCode($to);

        if (!$currencyTo) {

            $this->_set_error('Invalid `to` currency code.');
            return false;
        }

        // --------------------------------------------------------------------------

        if (is_null($this->rates)) {

            $this->rates = array();
            $rates       = $this->db->get(NAILS_DB_PREFIX . 'shop_currency_exchange')->result();

            foreach ($rates as $rate) {

                $this->rates[$rate->from . $rate->to] = $rate->rate;
            }
        }

        if (isset($this->rates[$from . $to])) {

            if ($currencyFrom->decimal_precision === $currencyTo->decimal_precision) {

                $result = $value * $this->rates[$from . $to];
                $result = round($result, 0, PHP_ROUND_HALF_UP);

            } else {

                $result = round($value * $this->rates[$from . $to], 0, PHP_ROUND_HALF_UP);
                $result = $result / pow(10, $currencyFrom->decimal_precision);
                $result = round($result, $currencyTo->decimal_precision, PHP_ROUND_HALF_UP);
            }

            return $result;

        } else {

            $this->_set_error('No exchange rate available for those currencies; does the system need to sync?');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value from the base currency to the user's currency.
     * @param  mixed $value The value to convert
     * @return mixed        Float on success, false on failure
     */
    public function convertBaseToUser($value)
    {
        return $this->convert($value, SHOP_BASE_CURRENCY_CODE, SHOP_USER_CURRENCY_CODE);
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value from the user's currency to the base currency.
     * @param  mixed $value The value to convert
     * @return mixed        Float on success, false on failure
     */
    public function convertUserToBase($value)
    {
        return $this->convert($value, SHOP_USER_CURRENCY_CODE, SHOP_BASE_CURRENCY_CODE);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for a given currency.
     * @param  integer $value     The value to format, as an integer
     * @param  string  $code      The currency to format as
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function format($value, $code, $incSymbol = true)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->_set_error('Invalid currency code.');
            return false;
        }

        /**
         * The input comes in as an integer, convert into a decimal with the
         * correct number of decimal places.
         */

        $value = $this->intToFloat($value, $code);
        $value = number_format($value, $currency->decimal_precision, $currency->decimal_symbol, $currency->thousands_seperator);

        if ($incSymbol) {

            if ($currency->symbol_position == 'BEFORE') {

                $value = $currency->symbol . $value;

            } else {

                $value = $value . $currency->symbol;
            }
        }

        return $value;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for the base currency.
     * @param  mixed   $value     The value to format, string, int or float
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function formatBase($value, $incSymbol = true)
    {
        return $this->format($value, SHOP_BASE_CURRENCY_CODE, $incSymbol);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for the user's currency.
     * @param  mixed   $value     The value to format, string, int or float
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function formatUser($value, $incSymbol = true)
    {
        return $this->format($value, SHOP_USER_CURRENCY_CODE, $incSymbol);
    }

    // --------------------------------------------------------------------------

    /**
     * Converts an integer to a float with the correct number of decimal points
     * as the currency requires
     * @param  integer $value The integer to convert
     * @param  string  $code  The curreny code to convert for
     * @return float
     */
    public function intToFloat($value, $code)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->_set_error('Invalid currency code.');
            return false;
        }

        $result = $value / pow(10, $currency->decimal_precision);

        return (float) $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a float to an integer
     * @param  integer $value The integer to convert
     * @param  string  $code  The curreny code to convert for
     * @return integer
     */
    public function floatToInt($value, $code)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->_set_error('Invalid currency code.');
            return false;
        }

        $result = $value * pow(10, $currency->decimal_precision);

        /**
         * Due to the nature of floating point numbers (best explained here
         * http://stackoverflow.com/a/4934594/789224) simply casting as an integer
         * can cause some odd rounding behaviour (although eprfectly rational). If we
         * cast as a string, then cast as an integer we can be sure that the value is
         * correct. Others said to use round() but that gives me the fear.
         */

        $result = (string) $result;

        return (int) $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the exchange rate between currencies
     * @param  string $from The currency to convert from
     * @param  string $to   The currency to convert to
     * @return float
     */
    public function getExchangeRate($from, $to)
    {
        $this->db->select('rate');
        $this->db->where('from', $from);
        $this->db->where('to', $to);

        $rate = $this->db->get(NAILS_DB_PREFIX . 'shop_currency_exchange')->row();

        if (!$rate) {

            return null;
        }

        return (float) $rate->rate;
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_CURRENCY_MODEL')) {

    class Shop_currency_model extends NAILS_Shop_currency_model
    {
    }
}
