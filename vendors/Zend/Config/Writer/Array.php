<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Config
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Array.php 18951 2009-11-12 16:26:19Z alexander $
 */

/**
 * @see Zend_Config_Writer
 */
require_once 'Zend/Config/Writer.php';

/**
 * @category   Zend
 * @package    Zend_Config
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Config_Writer_Array extends Zend_Config_Writer
{
    /**
     * Filename to write to
     *
     * @var string
     */
    protected $_filename = null;

    /**
     * Wether to exclusively lock the file or not
     *
     * @var boolean
     */
    protected $_exclusiveLock = false;

    /**
     * Set the target filename
     *
     * @param  string $filename
     * @return Zend_Config_Writer_Array
     */
    public function setFilename($filename)
    {
        $this->_filename = $filename;

        return $this;
    }

    /**
     * Set wether to exclusively lock the file or not
     *
     * @param  boolean     $exclusiveLock
     * @return Zend_Config_Writer_Array
     */
    public function setExclusiveLock($exclusiveLock)
    {
        $this->_exclusiveLock = $exclusiveLock;

        return $this;
    }

    /**
     * Defined by Zend_Config_Writer
     *
     * @param  string      $filename
     * @param  Zend_Config $config
     * @param  boolean     $exclusiveLock
     * @throws Zend_Config_Exception When filename was not set
     * @throws Zend_Config_Exception When filename is not writable
     * @return void
     */
    public function write($filename = null, Zend_Config $config = null, $exclusiveLock = null)
    {
        if ($filename !== null) {
            $this->setFilename($filename);
        }

        if ($config !== null) {
            $this->setConfig($config);
        }

        if ($exclusiveLock !== null) {
            $this->setExclusiveLock($exclusiveLock);
        }

        if ($this->_filename === null) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('No filename was set');
        }

        if ($this->_config === null) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('No config was set');
        }

        $data        = $this->_config->toArray();
        $sectionName = $this->_config->getSectionName();

        if (is_string($sectionName)) {
            $data = array($sectionName => $data);
        }

        $arrayString = "<?php\n"
                     . "return " . var_export($data, true) . ";\n";

        $flags = 0;

        if ($this->_exclusiveLock) {
            $flags |= LOCK_EX;
        }

        $result = @file_put_contents($this->_filename, $arrayString, $flags);

        if ($result === false) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('Could not write to file "' . $this->_filename . '"');
        }
    }
}
