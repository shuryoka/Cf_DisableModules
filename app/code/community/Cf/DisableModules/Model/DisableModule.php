<?php
/**
 * Description of DisableModules
 *
 * @category Cf
 * @package Cf_DisableModules
 * @author Christoph Frenes <cfrenes@animachi.de>
 */
class Cf_DisableModules_Model_DisableModule
{
    /**
     * get the config files for given module (Namespace_Name, Namespace_All or both)
     * 
     * @param string $moduleName
     * @return array
     */
    public function getModuleFiles($moduleName)
    {
        $modulePath  = Mage::getBaseDir() . DS . 'app' . DS . 'etc' . DS . 'modules' . DS;
        $moduleAll   = substr($moduleName, 0, stripos($moduleName, '_')) . '_All';
        $defaultFile = $modulePath . $moduleName . '.xml';
        $allFile     = $modulePath . $moduleAll  . '.xml';
        $files       = array();
        
        if (file_exists($defaultFile)) {
            $files['default'] = $defaultFile;
        }
        
        if (file_exists($allFile)) {
            $files['all'] = $allFile;
        }
        
        return $files;
    }
    
    /**
     * load the given file and parse it as xml
     * 
     * @todo check if existing file is xml file
     * @param stringe $filename
     * @return mixed SimpleXml if file exists and false if not
     */
    protected function _loadModuleFile($filename)
    {
        if (!file_exists($filename)) {
                return false;
        }
        
        $xml = simplexml_load_file($filename);
        return $xml;
    }
    
    /**
     * check if given modules active state is true or false
     * 
     * @param string $moduleName
     * @return boolean 
     */
    public function checkModuleState($moduleName)
    {
        $active = false;
        
        foreach ($this->getModuleFiles($moduleName) AS $file) {
            $xml = $this->_loadModuleFile($file);
            
            if ($xml !== false) {
                $state  = (string)$xml->modules->$moduleName->active;

                if ($state == 'true') {
                    $active = true;
                    break;
                }
            }
        }
        
        return $active;
    }
    
    /**
     * get list of modules the given module depends on
     * 
     * @param string $moduleName
     * @return array
     */
    public function getDependency($moduleName)
    {
        $dependency = array();
        
        foreach ($this->getModuleFiles($moduleName) AS $file) {
            $xml     = $this->_loadModuleFile($file);
            $depends = $xml->modules->$moduleName->depends;

            try {
                if (is_object($depends)) {
                    foreach ($depends->children() AS $dep) {
                        $dependency[] = (string)$dep->getName();
                    }
                }
            } catch (Exception $e) {
                
            }
        }
        
        return $dependency;
    }
    
    /**
     * get list of modules which have given modulename in their dependency-node
     * 
     * @todo make regexp more accurate (comments, other nodes then  'depends')
     * @param string $moduleName
     * @return array
     */
    public function getNeededBy($moduleName)
    {
        $needed = array();
        
        $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
        sort($modules);

        foreach ($modules as $module) {
            foreach ($this->getModuleFiles($module) AS $file) {
                $xml = file_get_contents($file);
                
                if (preg_match("/<$moduleName \/>/i", $xml)) {
                    $needed[] = $module;
                }
            }
        }
  
        $needed = array_unique($needed);
        
        foreach ($needed AS $key => $need) {
            if ($need == $moduleName) {
                unset($needed[$key]);
            }
        }
        
        sort ($needed);
        
        return $needed;
    }
    
    /**
     * change active node of given module
     * 
     * @param string $moduleName
     * @param boolean $state will be converted to string
     * @return boolean 
     */
    public function setModuleState($moduleName, $state = false)
    {
        $session = Mage::getSingleton('adminhtml/session'); /* @var $session Mage_Adminhtml_Model_Session */
        $state   = ($state === false ? 'false' : 'true');
        
        foreach($this->getModuleFiles($moduleName) AS $file) {
            if (!is_writable($file)) {
                $session->addError(Mage::helper('disablemodules')->__('File for module %s is not writeable.', $moduleName));
            } else {
                $xml = simplexml_load_file($file); 
                $xml->modules->$moduleName->active = $state;
                $xml->asXml($file);  
                return true;
            }
        }
        
        return false;
    }

    /**
     * loop over list of modules and set state
     * 
     * @param array $list modulenames as keys
     */
    public function setMassStates($list)
    {
        foreach($list AS $moduleName => $values) {
            if (!empty($values['new']) && intval($values['old']) == 0) {
                $this->setModuleState($moduleName, false);  // switch from on to off
            }

            if (empty($values['new']) && intval($values['old']) == 1) {
                $this->setModuleState($moduleName, true);   // switch from off to on
            }
        }
    }
}