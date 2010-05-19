<?php
/**
 * Media  Directory Inputfield Textarea Class
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Includes
 */
require_once ASCMS_MODULE_PATH . '/mediadir/lib/lib.class.php';
require_once ASCMS_MODULE_PATH . '/mediadir/lib/inputfields/inputfield.interface.php';

class mediaDirectoryInputfieldTextarea extends mediaDirectoryLibrary implements inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE','MEDIADIR_INPUTFIELD_VALUE_ALLOW_TAGS');


    /**
     * Constructor
     */
    function __construct()
    {
        parent::getFrontendLanguages();
    }



    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $_LANGID, $objInit, $_ARRAYLANG;

        $intId = intval($arrInputfield['id']);

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`,
                            `lang_id`
                        FROM
                            ".DBPREFIX."module_mediadir_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                    ");

                    if ($objInputfieldValue !== false) {
                        while (!$objInputfieldValue->EOF) {
                            $arrValue[intval($objInputfieldValue->fields['lang_id'])] = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                            $objInputfieldValue->MoveNext();
                        }
                        $arrValue[0] = $arrValue[$_LANGID];
                    }
                } else {
                    $arrValue = null;
                }

                if(empty($arrValue)) {
                    $arrValue[0] = empty($arrInputfield['default_value'][$_LANGID]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$_LANGID];
                }

                if($objInit->mode == 'backend') {
                    $strInputfield = '<div id="mediadirInputfield_'.$intId.'_Minimized" style="display: block;"><textarea name="mediadirInputfield['.$intId.'][0]" id="mediadirInputfield_'.$intId.'_0" style="width: 300px; height: 60px;" onfocus="this.select();" />'.$arrValue[0].'</textarea>&nbsp;<a href="javascript:ExpandMinimize(\''.$intId.'\');">'.$_ARRAYLANG['TXT_MEDIADIR_MORE'].'&nbsp;&raquo;</a></div>';

                    $strInputfield .= '<div id="mediadirInputfield_'.$intId.'_Expanded" style="display: none;">';
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $intLangId = $arrLang['id'];

                        if(($key+1) == count($this->arrFrontendLanguages)) {
                            $minimize = "&nbsp;<a href=\"javascript:ExpandMinimize('".$intId."');\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                        } else {
                            $minimize = "";
                        }

                        $strInputfield .= '<textarea name="mediadirInputfield['.$intId.']['.$intLangId.']" id="mediadirInputfield_'.$intId.'_'.$intLangId.'" style="height: 60px; width: 279px; margin-bottom: 2px; padding-left: 21px; background: #ffffff url(\'images/flags/flag_'.$arrLang['lang'].'.gif\') no-repeat 3px 3px;" onfocus="this.select();" />'.$arrValue[$intLangId].'</textarea>&nbsp;'.$arrLang['name'].'<a href="javascript:ExpandMinimize(\''.$intId.'\');">&nbsp;'.$minimize.'</a><br />';
                    }
                    $strInputfield .= '<textarea name="mediadirInputfield['.$intId.'][old]" style="display: none;" onfocus="this.select();" />'.$arrValue[0].'</textarea>';
                    $strInputfield .= '</div>';
                } else {
                     $strInputfield = '<textarea name="mediadirInputfield['.$intId.'][0]" id="mediadirInputfield_'.$intId.'_0" class="mediadirInputfieldTextarea" onfocus="this.select();" />'.$arrValue[0].'</textarea>';
                }


                return $strInputfield;
                break;
            case 2:
                //search View
                $strValue = $_GET[$intId];
                $strInputfield = '<input type="text" name="'.$intId.'" " class="mediadirInputfieldSearch" value="'.$strValue.'" />';

                return $strInputfield;

                break;
        }
    }



    function saveInputfield($intInputfieldId, $strValue)
    {
        $strValue = contrexx_addslashes($strValue);
        return $strValue;
    }


    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        $objDeleteInputfield = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_mediadir_rel_entry_inputfields WHERE `entry_id`='".intval($intEntryId)."' AND  `field_id`='".intval($intIputfieldId)."'");

        if($objDeleteEntry !== false) {
            return true;
        } else {
            return false;
        }
    }



    function getContent($intEntryId, $arrInputfield)
    {
        global $objDatabase, $_LANGID;

        $intId = intval($arrInputfield['id']);
        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_mediadir_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".$intEntryId."
            AND
                lang_id=".$_LANGID."
            LIMIT 1
        ");
        
        if(empty($objInputfieldValue->fields['value'])) {
            $objInputfieldValue = $objDatabase->Execute("
                SELECT
                    `value`
                FROM
                    ".DBPREFIX."module_mediadir_rel_entry_inputfields
                WHERE
                    field_id=".$intId."
                AND
                    entry_id=".$intEntryId."
                LIMIT 1
            ");
        }

        $strValueAllowTags = nl2br($objInputfieldValue->fields['value']);
        $strValue = nl2br(htmlspecialchars(strip_tags($objInputfieldValue->fields['value']), ENT_QUOTES, CONTREXX_CHARSET));

        if(!empty($strValue)) {
            $arrContent['TXT_MEDIADIR_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent['MEDIADIR_INPUTFIELD_VALUE'] = $strValue;
            $arrContent['MEDIADIR_INPUTFIELD_VALUE_ALLOW_TAGS'] = $strValueAllowTags;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }



    function getJavascriptCheck()
    {
        $strJavascriptCheck = <<<EOF

            case 'textarea':
                value = document.getElementById('mediadirInputfield_' + field + '_0').value;
                if (value == "" && isRequiredGlobal(inputFields[field][1], value)) {
                	isOk = false;
                	document.getElementById('mediadirInputfield_' + field + '_0').style.border = "#ff0000 1px solid";
                } else if (value != "" && !matchType(inputFields[field][2], value)) {
                	isOk = false;
                	document.getElementById('mediadirInputfield_' + field + '_0').style.border = "#ff0000 1px solid";
                } else {
                	document.getElementById('mediadirInputfield_' + field + '_0').style.borderColor = '';
                }
                break;
EOF;
        return $strJavascriptCheck;
    }
}