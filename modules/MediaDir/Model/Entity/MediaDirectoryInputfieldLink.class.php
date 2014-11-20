<?php

/**
 * Media Directory Inputfield Link Class
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Link Class
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldLink extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE','MEDIADIR_INPUTFIELD_VALUE_HREF','MEDIADIR_INPUTFIELD_VALUE_NAME');



    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
    }



    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $_LANGID, $objInit;

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View                  
                $intId = intval($arrInputfield['id']);

                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                        LIMIT 1
                    ");
                    $strValue = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    $strValue = null;
                }

                if(empty($strValue)) {
                    if(substr($arrInputfield['default_value'][0],0,2) == '[[') {
                        $objPlaceholder = new \Cx\Modules\MediaDir\Controller\MediaDirectoryPlaceholder($this->moduleName);
                        $strValue = $objPlaceholder->getPlaceholder($arrInputfield['default_value'][0]);
                    } else {
                        $strValue = empty($arrInputfield['default_value'][$_LANGID]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$_LANGID];
                    }
                }
                
                if(!empty($arrInputfield['info'][0])){
                	$strInfoValue = empty($arrInputfield['info'][$_LANGID]) ? 'title="'.$arrInputfield['info'][0].'"' : 'title="'.$arrInputfield['info'][$_LANGID].'"';
                	$strInfoClass = 'mediadirInputfieldHint';
                } else {
                    $strInfoValue = null;
                    $strInfoClass = '';
                }

                if($objInit->mode == 'backend') {
                    $strInputfield = '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'" value="'.$strValue.'" style="width: 300px" onfocus="this.select();" />';
                } else {
                    $strInputfield = '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'" class="'.$this->moduleNameLC.'InputfieldLink '.$strInfoClass.'" '.$strInfoValue.' value="'.$strValue.'" onfocus="this.select();" />';
                }  

                return $strInputfield;

                break;
            case 2:
                //search View
                break;
        }
    }



    function saveInputfield($intInputfieldId, $strValue)
    {
        $strValue = contrexx_strip_tags(contrexx_input2raw($strValue));
        return $strValue;
    }


    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        $objDeleteInputfield = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE `entry_id`='".intval($intEntryId)."' AND  `field_id`='".intval($intIputfieldId)."'");

        if($objDeleteEntry !== false) {
            return true;
        } else {
            return false;
        }
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);
        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".$intEntryId."
            LIMIT 1
        ");

        $strValue = strip_tags(htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET));

        // replace the links
        $strValue = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $strValue);
        \LinkGenerator::parseTemplate($strValue, true);
        
        //make link name without protocol
        $strValueName = preg_replace('#^.*://#', '', $strValue);

        if (strlen($strValueName) >= 55 ) {
            $strValueName = substr($strValueName, 0, 55)." [...]";
        }

        //make link href with "http://"
        $strValueHref = $strValue;
        if (!preg_match('#^.*://#', $strValueHref)) {
            $strValueHref = "http://".$strValueHref;
        }

        //make hyperlink with <a> tag
        $strValueLink = '<a href="'.$strValueHref.'" class="'.$this->moduleNameLC.'InputfieldLink" target="_blank">'.$strValueName.'</a>';

        if(!empty($strValue)) {
            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = $strValueLink;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE_HREF'] = $strValueHref;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE_NAME'] = $strValueName;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }


    function getJavascriptCheck()
    {
        $fieldName = $this->moduleNameLC."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'link':
                value = document.getElementById('$fieldName' + field).value;
                if (value == "" && isRequiredGlobal(inputFields[field][1], value)) {
                	isOk = false;
                	document.getElementById('$fieldName' + field).style.border = "#ff0000 1px solid";
                } else if (value != "" && !matchType(inputFields[field][2], value)) {
                	isOk = false;
                	document.getElementById('$fieldName' + field).style.border = "#ff0000 1px solid";
                } else {
                	document.getElementById('$fieldName' + field).style.borderColor = '';
                }
                break;

EOF;
        return $strJavascriptCheck;
    }
    
    
    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }
}