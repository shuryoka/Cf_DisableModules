<?php
/**
 * Description of Field
 *
 * @todo refactoring/code smells
 * @category Cf
 * @package Cf_DisableModules
 * @author Christoph Frenes <cfrenes@animachi.de>
 */
class Cf_DisableModules_Block_Adminhtml_System_Config_Form_Field
    extends Mage_Adminhtml_Block_System_Config_Form_Field
    implements Varien_Data_Form_Element_Renderer_Interface
{    
    /**
     * render module to table row
     *
     * @todo move css to external file
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $id          = $element->getHtmlId();
        $disMod      = Mage::getModel('disablemodules/disableModule'); /* @var $disMod Cf_DisableModules_Model_DisableModule */
        $active      = $disMod->checkModuleState($id);
        $depends     = $disMod->getDependency($id);
        $needed      = $disMod->getNeededBy($id);
        $lineThrough = $disabled = $checked = '';
        $disableRow  = 'disableRow active';
        $oldValue    = 0;
        
        if (!$active) {
            $lineThrough = ' style="text-decoration:line-through;"';
            $disabled    = ' disabled';
            $checked     = ' checked';
            $disableRow  = ' disableRow inactive';
            $oldValue    = 1;
        }
        
        $dependingStr = '';
        if (!empty($depends)) {
            $dependingStr = '<strong>'.Mage::helper('disablemodules')->__('Depends on').':</strong><br />';
                
            foreach($depends AS $depend) {
                $dependingStr .= '<a href="#'.$depend.'">'.$depend.'</a>, ';
            }
            
            $dependingStr = trim($dependingStr);
            $dependingStr = rtrim($dependingStr, ',');
        }
        
        $neededStr = '';
        if (!empty($needed)) {
            $neededStr = '<strong>'.Mage::helper('disablemodules')->__('Needed by').':</strong><br />';
                
              foreach($needed AS $need) {
                $neededStr .= '<a href="#'.$need.'">'.$need.'</a>, ';
            }
            
            $neededStr = trim($neededStr);
            $neededStr = rtrim($neededStr, ',');
        }

        $useContainerId = $element->getData('use_container_id');
        $html = '<tr class="'.$disableRow.'" id="row_' . $id . '">'
              . '<td class="label"><a name="'.$id.'"></a><label for="'.$id.'"'.$lineThrough.'>'.$element->getLabel().'</label></td>';

        //$isDefault = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');
        $isMultiple = $element->getExtType()==='multiple';

        // replace [value] with [inherit]
        $namePrefix = preg_replace('#\[value\](\[\])?$#', '', $element->getName());

        $options = $element->getValues();

        $addInheritCheckbox = false;
        if ($element->getCanUseWebsiteValue()) {
            $addInheritCheckbox = true;
            $checkboxLabel = Mage::helper('adminhtml')->__('Use Website');
        }
        elseif ($element->getCanUseDefaultValue()) {
            $addInheritCheckbox = true;
            $checkboxLabel = Mage::helper('adminhtml')->__('Use Default');
        }

        if ($addInheritCheckbox) {
            $inherit = $element->getInherit()==1 ? 'checked="checked"' : '';
            if ($inherit) {
                $element->setDisabled(true);
            }
        }

        $html.= '<td class="value">';
        $html.= $this->_getElementHtml($element);
        if ($element->getComment()) {
            $html.= '<p class="note"><span>'.$element->getComment().'</span></p>';
        }
        $html.= '</td>';

        if ($addInheritCheckbox) {

            $defText = $element->getDefaultValue();
            if ($options) {
                $defTextArr = array();
                foreach ($options as $k=>$v) {
                    if ($isMultiple) {
                        if (is_array($v['value']) && in_array($k, $v['value'])) {
                            $defTextArr[] = $v['label'];
                        }
                    } elseif ($v['value']==$defText) {
                        $defTextArr[] = $v['label'];
                        break;
                    }
                }
                $defText = join(', ', $defTextArr);
            }

            // default value
            $html.= '<td class="use-default">';
            $html.= '<input'.$disabled.' id="'.$id.'_inherit" name="'.$namePrefix.'[inherit]" type="checkbox" value="1" class="checkbox config-inherit" '.$inherit.' onclick="toggleValueElements(this, Element.previous(this.parentNode))" /> ';
            $html.= '<label for="'.$id.'_inherit" class="inherit" title="'.htmlspecialchars($defText).'">'.$checkboxLabel.'</label>';
            $html.= '</td>';
        }

        $html.= '<td class="scope-label">';
        if ($element->getScope()) {
            $html .= $element->getScopeLabel();
        }
        $html.= '</td>';

        $html.= '<td class="">';
        if ($element->getHint()) {
            $html.= '<div class="hint" >';
            $html.= '<div style="display: none;">' . $element->getHint() . '</div>';
            $html.= '</div>';
        }
        $html.= '</td>';
        
        if (!$addInheritCheckbox) {
            $html.='<td class="use-default disablemodule">
                    <input type="checkbox" id="disablemodule_'.$id.'" name="disable['.$id.'][new]" value="1"'.$checked.' />
                    <input type="hidden" id="disablemodule_'.$id.'_hidden" name="disable['.$id.'][old]" value="'.$oldValue.'" />
                    <label for="disablemodule_'.$id.'">'.Mage::helper('disablemodules')->__('disabled').'</label>
                </td>';
        }
            $html.= '</tr>
                <tr>
                    <td colspan="5" style="background-color: #E7EFEF;">';
            
            if (!empty($dependingStr) || !empty($neededStr)) {
                $html .= '<ul style="margin-left: 15px; font-size: 0.8em;">
                            <li>'.$dependingStr.'</li>
                            <li>'.$neededStr.'</li>
                        </ul>';
            }
                    
            $html .= '
                        
                    </td>
                </tr>
            ';
        
        return $html;
    }
}