<?php

/**
 * This model manages the Invoice payment drivers
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

use Nails\Common\Model\BaseDriver;
use Nails\Common\Traits\ErrorHandling;

class Feed extends BaseDriver
{
    use ErrorHandling;

    // --------------------------------------------------------------------------

    protected $sModule = 'nailsapp/module-shop';
    protected $sType   = 'feed';

    // --------------------------------------------------------------------------

    /**
     * Search a text file containing a list of Google shopping categories
     * @todo find a home for this other than here, should probably live within the Google driver
     *
     * @param  string $sTerm The search term
     *
     * @return array|boolean
     */
    public function searchGoogleCategories($sTerm)
    {
        //  Open the cache file, if it's not available then fetch a new one
        $sCacheFile = CACHE_PATH . 'shop-feed-google-categories-' . date('m-Y') . '.txt';

        if (!file_exists($sCacheFile)) {

            //  @todo handle multiple locales
            $sData = file_get_contents('http://www.google.com/basepages/producttype/taxonomy.en-GB.txt');

            if (empty($sData)) {
                $this->setError('Failed to fetch feed from Google.');
                return false;
            }

            file_put_contents($sCacheFile, $sData);
        }

        $oHandle  = fopen($sCacheFile, 'r');
        $aResults = [];

        if ($oHandle) {

            while (($sLine = fgets($oHandle)) !== false) {

                if (substr($sLine, 0, 1) === '#') {
                    continue;
                }

                if (preg_match('/' . $sTerm . '/i', $sLine)) {
                    $aResults[] = $sLine;
                }
            }

            fclose($oHandle);

            return $aResults;

        } else {
            $this->setError('Failed to read feed from cache.');
            return false;
        }
    }
}
