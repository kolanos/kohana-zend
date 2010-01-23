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
 * @package    Zend_Tool
 * @subpackage Framework
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Console.php 18951 2009-11-12 16:26:19Z alexander $
 */

/**
 * @see Zend_Loader
 */
require_once 'Zend/Loader.php';

/**
 * @see Zend_Tool_Framework_Client_Abstract
 */
require_once 'Zend/Tool/Framework/Client/Abstract.php';

/**
 * @see Zend_Tool_Framework_Client_Console_ArgumentParser
 */
require_once 'Zend/Tool/Framework/Client/Console/ArgumentParser.php';

/**
 * @see Zend_Tool_Framework_Client_Interactive_InputInterface
 */
require_once 'Zend/Tool/Framework/Client/Interactive/InputInterface.php';

/**
 * @see Zend_Tool_Framework_Client_Interactive_OutputInterface
 */
require_once 'Zend/Tool/Framework/Client/Interactive/OutputInterface.php';

/**
 * @see Zend_Tool_Framework_Client_Response_ContentDecorator_Separator
 */
require_once 'Zend/Tool/Framework/Client/Response/ContentDecorator/Separator.php';

/**
 * Zend_Tool_Framework_Client_Console - the CLI Client implementation for Zend_Tool_Framework
 *
 * @category   Zend
 * @package    Zend_Tool
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Tool_Framework_Client_Console
    extends Zend_Tool_Framework_Client_Abstract
    implements Zend_Tool_Framework_Client_Interactive_InputInterface,
               Zend_Tool_Framework_Client_Interactive_OutputInterface
{

    /**
     * @var array
     */
    protected $_configOptions = null;

    /**
     * @var array
     */
    protected $_storageOptions = null;

    /**
     * @var Zend_Filter_Word_CamelCaseToDash
     */
    protected $_filterToClientNaming = null;

    /**
     * @var Zend_Filter_Word_DashToCamelCase
     */
    protected $_filterFromClientNaming = null;

    /**
     * main() - This is typically called from zf.php. This method is a
     * self contained main() function.
     *
     */
    public static function main($options = array())
    {
        ini_set('display_errors', true);
        $cliClient = new self($options);
        $cliClient->dispatch();
    }

    public function setConfigOptions($configOptions)
    {
        $this->_configOptions = $configOptions;
        return $this;
    }

    public function setStorageOptions($storageOptions)
    {
        $this->_storageOptions = $storageOptions;
        return $this;
    }

    /**
     * getName() - return the name of the client, in this case 'console'
     *
     * @return string
     */
    public function getName()
    {
        return 'console';
    }

    /**
     * _init() - Tasks processed before the constructor, generally setting up objects to use
     *
     */
    protected function _preInit()
    {
        $config = $this->_registry->getConfig();

        if ($this->_configOptions != null) {
            $config->setOptions($this->_configOptions);
        }

        $storage = $this->_registry->getStorage();

        if ($this->_storageOptions != null && isset($this->_storageOptions['directory'])) {
            require_once 'Zend/Tool/Framework/Client/Storage/Directory.php';
            $storage->setAdapter(
                new Zend_Tool_Framework_Client_Storage_Directory($this->_storageOptions['directory'])
                );
        }

        // support the changing of the current working directory, necessary for some providers
        if (isset($_ENV['ZEND_TOOL_CURRENT_WORKING_DIRECTORY'])) {
            chdir($_ENV['ZEND_TOOL_CURRENT_WORKING_DIRECTORY']);
        }

        // support setting the loader from the environment
        if (isset($_ENV['ZEND_TOOL_FRAMEWORK_LOADER_CLASS'])) {
            if (class_exists($_ENV['ZEND_TOOL_FRAMEWORK_LOADER_CLASS'])
                || Zend_Loader::loadClass($_ENV['ZEND_TOOL_FRAMEWORK_LOADER_CLASS'])
            ) {
                $this->_registry->setLoader(new $_ENV['ZEND_TOOL_FRAMEWORK_LOADER_CLASS']);
            }
        }

        return;
    }

    /**
     * _preDispatch() - Tasks handed after initialization but before dispatching
     *
     */
    protected function _preDispatch()
    {
        $response = $this->_registry->getResponse();

        if (function_exists('posix_isatty')) {
            require_once 'Zend/Tool/Framework/Client/Console/ResponseDecorator/Colorizer.php';
            $response->addContentDecorator(new Zend_Tool_Framework_Client_Console_ResponseDecorator_Colorizer());
        }

        $response->addContentDecorator(new Zend_Tool_Framework_Client_Response_ContentDecorator_Separator())
            ->setDefaultDecoratorOptions(array('separator' => true));

        $optParser = new Zend_Tool_Framework_Client_Console_ArgumentParser();
        $optParser->setArguments($_SERVER['argv'])
            ->setRegistry($this->_registry)
            ->parse();

        return;
    }

    /**
     * _postDispatch() - Tasks handled after dispatching
     *
     */
    protected function _postDispatch()
    {
        $request = $this->_registry->getRequest();
        $response = $this->_registry->getResponse();

        if ($response->isException()) {
            require_once 'Zend/Tool/Framework/Client/Console/HelpSystem.php';
            $helpSystem = new Zend_Tool_Framework_Client_Console_HelpSystem();
            $helpSystem->setRegistry($this->_registry)
                ->respondWithErrorMessage($response->getException()->getMessage(), $response->getException())
                ->respondWithSpecialtyAndParamHelp(
                    $request->getProviderName(),
                    $request->getActionName()
                    );
        }

        echo PHP_EOL;
        return;
    }

    /**
     * handleInteractiveInputRequest() is required by the Interactive InputInterface
     *
     *
     * @param Zend_Tool_Framework_Client_Interactive_InputRequest $inputRequest
     * @return string
     */
    public function handleInteractiveInputRequest(Zend_Tool_Framework_Client_Interactive_InputRequest $inputRequest)
    {
        fwrite(STDOUT, $inputRequest->getContent() . PHP_EOL . 'zf> ');
        $inputContent = fgets(STDIN);
        return rtrim($inputContent); // remove the return from the end of the string
    }

    /**
     * handleInteractiveOutput() is required by the Interactive OutputInterface
     *
     * This allows us to display output immediately from providers, rather
     * than displaying it after the provider is done.
     *
     * @param string $output
     */
    public function handleInteractiveOutput($output)
    {
        echo $output;
    }

    /**
     * getMissingParameterPromptString()
     *
     * @param Zend_Tool_Framework_Provider_Interface $provider
     * @param Zend_Tool_Framework_Action_Interface $actionInterface
     * @param string $missingParameterName
     * @return string
     */
    public function getMissingParameterPromptString(Zend_Tool_Framework_Provider_Interface $provider, Zend_Tool_Framework_Action_Interface $actionInterface, $missingParameterName)
    {
        return 'Please provide a value for $' . $missingParameterName;
    }


    /**
     * convertToClientNaming()
     *
     * Convert words to client specific naming, in this case is lower, dash separated
     *
     * Filters are lazy-loaded.
     *
     * @param string $string
     * @return string
     */
    public function convertToClientNaming($string)
    {
        if (!$this->_filterToClientNaming) {
            require_once 'Zend/Filter.php';
            require_once 'Zend/Filter/Word/CamelCaseToDash.php';
            require_once 'Zend/Filter/StringToLower.php';
            $filter = new Zend_Filter();
            $filter->addFilter(new Zend_Filter_Word_CamelCaseToDash());
            $filter->addFilter(new Zend_Filter_StringToLower());

            $this->_filterToClientNaming = $filter;
        }

        return $this->_filterToClientNaming->filter($string);
    }

    /**
     * convertFromClientNaming()
     *
     * Convert words from client specific naming to code naming - camelcased
     *
     * Filters are lazy-loaded.
     *
     * @param string $string
     * @return string
     */
    public function convertFromClientNaming($string)
    {
        if (!$this->_filterFromClientNaming) {
            require_once 'Zend/Filter/Word/DashToCamelCase.php';
            $this->_filterFromClientNaming = new Zend_Filter_Word_DashToCamelCase();
        }

        return $this->_filterFromClientNaming->filter($string);
    }

}
