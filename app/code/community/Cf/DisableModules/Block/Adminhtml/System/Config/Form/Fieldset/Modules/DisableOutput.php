<?php
/**
 * Description of DisableOutput
 *
 * @category Cf
 * @package Cf_DisableModules
 * @author Christoph Frenes <cfrenes@animachi.de>
 */
class Cf_DisableModules_Block_Adminhtml_System_Config_Form_Fieldset_Modules_DisableOutput
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset_Modules_DisableOutput
{
    /**
     * set own renderer for this view
     * 
     * @return Cf_DisableModules_Block_Adminhtml_System_Config_Form_Field
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('disablemodules/adminhtml_system_config_form_field');
        }
        return $this->_fieldRenderer;
    }
    
    /**
     * render module to table row
     * 
     * @todo move css to external file
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string 
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html    = $this->_getHeaderHtml($element);
        $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
        sort($modules);

        foreach ($modules as $moduleName) {
            if ($moduleName==='Mage_Adminhtml') {
                continue;
            }
            $html.= $this->_getFieldHtml($element, $moduleName);
        }
        
//        $html .= '<thead><tr><td>Name</td><td>Disable Output</td><td>Disable Module</td><td></td></tr></thead>';
//        $html .= '<tfoot><tr><td>Name</td><td>Disable Output</td><td>Disable Module</td><td></td></tr></tfoot>';
        $html .= $this->_getFooterHtml($element);
        $html .= '<input type="hidden" id="disablemodules_controller" name="disablemodules_controller" value="1" />
            <style type="text/css">
            #advanced_modules_disable_output table { width: 100%; border-spacing: 0 2px; }
            #advanced_modules_disable_output table tr { border: 1px solid red; }
            
            .disableRow { background-color: #9FF781; }
            .inactive { background-color: #FA5858; } 
</style>';

        return $html;
    }

    /**
     * build select for disabling/enabling module output
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param string $moduleName
     * @return string
     */
    protected function _getFieldHtml($fieldset, $moduleName)
    {
        $configData = $this->getConfigData();
        $path = 'advanced/modules_disable_output/'.$moduleName; //TODO: move as property of form
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        } else {
            $data = (int)(string)$this->getForm()->getConfigRoot()->descend($path);
            $inherit = true;
        }

        $e = $this->_getDummyElement();

        $disModule = Mage::getModel('disablemodules/disableModule'); /* @var $disModule Cf_DisableModules_Model_DisableModule */
        $select    = array(
            'name'          => 'groups[modules_disable_output][fields]['.$moduleName.'][value]',
            'label'         => $moduleName,
            'value'         => $data,
            'values'        => $this->_getValues(),
            'inherit'       => $inherit,
            'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
            'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
        );
        
        if (!$disModule->checkModuleState($moduleName)) {
            $select['disabled'] = true;
        }
        
        $field = $fieldset->addField($moduleName, 'select', $select)->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }
}