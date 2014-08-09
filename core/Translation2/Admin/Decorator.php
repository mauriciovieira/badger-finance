<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains the Translation2_Admin_Decorator class
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Internationalization
 * @package    Translation2
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @copyright  2004-2005 Lorenzo Alberton
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Decorator.php,v 1.8 2005/02/22 17:55:07 quipo Exp $
 * @link       http://pear.php.net/package/Translation2
 */

/**
 * Load Translation2_Decorator class
 */
require_once 'Translation2/Decorator.php';
 
/**
 * Decorates a Translation2_Admin class.
 *
 * Create a subclass of this class for your own "decoration".
 *
 * @category   Internationalization
 * @package    Translation2
 * @author     Lorenzo Alberton <l dot alberton at quipo dot it>
 * @copyright  2004-2005 Lorenzo Alberton
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link       http://pear.php.net/package/Translation2
 * @abstract
 * @todo       Don't allow stacking on top of regular Decorators, 
 *             since that will break things.
 */
class Translation2_Admin_Decorator extends Translation2_Decorator
{
    // {{{ addLang()

    /**
     * Create a new language
     *
     * @see  Translation2_Admin::addLang()
     */
    function addLang($langData)
    {
        return $this->translation2->addLang($langData);
    }
    
    // }}}
    // {{{ removeLang()

    /**
     * Remove a language
     *
     * @see  Translation2_Admin::removeLang()
     */
    function removeLang($langID = null, $force = false)
    {
        return $this->translation2->removeLang($langID, $force);
    }

    // }}}
    // {{{ add()

    /**
     * Add a translation
     *
     * @see  Translation2_Admin::add()
     */
    function add($stringID, $pageID = null, $stringArray)
    {
        return $this->translation2->add($stringID, $pageID, $stringArray);
    }

    // }}}
    // {{{ update()

    /**
     * Update a translation
     *
     * @see  Translation2_Admin::update()
     */
    function update($stringID, $pageID = null, $stringArray)
    {
        return $this->translation2->update($stringID, $pageID, $stringArray);
    }

    // }}}
    // {{{ remove()

    /**
     * Remove a translation
     *
     * @see  Translation2_Admin::remove()
     */
    function remove($stringID, $pageID = null)
    {
        return $this->translation2->remove($stringID, $pageID);
    }

    // }}}
    // {{{ getPageNames()

    /**
     * Get a list of all the pageIDs in any table.
     *
     * @see  Translation2_Admin::getPageNames()
     */
    function getPageNames()
    {
        return $this->translation2->getPageNames();
    }

    // }}}
    // {{{ cleanCache()

    /**
     * Clean the cache
     *
     * @see  Translation2_Admin::cleanCache()
     */
    function cleanCache()
    {
        return $this->translation2->cleanCache();
    }

    // }}}
}
?>