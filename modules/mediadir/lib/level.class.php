<?php
/**
 * Media  Directory Level Class
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

class mediaDirectoryLevel extends mediaDirectoryLibrary
{
    private $intLevelId;
    private $intParentId;
    private $intNumEntries;
    private $bolGetChildren;
    private $intRowCount;
    private $arrExpandedLevelIds = array();

    private $strSelectedOptions;
    private $strNotSelectedOptions;
    private $arrSelectedLevels;

    public $arrLevels = array();

    /**
     * Constructor
     */
    function __construct($intLevelId=null, $intParentId=null, $bolGetChildren=1)
    {
        $this->intLevelId = intval($intLevelId);
        $this->intParentId = intval($intParentId);
        $this->bolGetChildren = intval($bolGetChildren);

        parent::getSettings();
        parent::getFrontendLanguages();

        $this->arrLevels = self::getLevels($this->intLevelId, $this->intParentId);
    }

    function getLevels($intLevelId=null, $intParentId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID, $objInit;

        $arrLevels = array();

        if(!empty($intLevelId)) {
            $whereLevelId = "level.id='".$intLevelId."' AND";
            $whereParentId = '';
        } else {
            if(!empty($intParentId)) {
                $whereParentId = "AND (level.parent_id='".$intParentId."') ";
            } else {
                $whereParentId = "AND (level.parent_id='0') ";
            }

            $whereLevelId = null;
        }

        if($objInit->mode == 'frontend') {
            $whereActive = "AND (level.active='1') ";
        }

        switch($this->arrSettings['settingsLevelOrder']) {
            case 0;
                //custom order
                $sortOrder = "level.`order` ASC";
                break;
            case 1;
            case 2;
                //abc order
                $sortOrder = "level_names.`level_name`";
                break;
        }

        $objLevels = $objDatabase->Execute("
            SELECT
                level.`id` AS `id`,
                level.`parent_id` AS `parent_id`,
                level.`order` AS `order`,
                level.`show_sublevels` AS `show_sublevels`,
                level.`show_categories` AS `show_categories`,
                level.`show_entries` AS `show_entries`,
                level.`picture` AS `picture`,
                level.`active` AS `active`,
                level_names.`level_name` AS `name`,
                level_names.`level_description` AS `description`
            FROM
                ".DBPREFIX."module_mediadir_levels AS level,
                ".DBPREFIX."module_mediadir_level_names AS level_names
            WHERE
                ($whereLevelId level_names.level_id=level.id)
                $whereParentId
                $whereActive
                AND (level_names.lang_id='".$_LANGID."')
            ORDER BY
                ".$sortOrder."
        ");

        if ($objLevels !== false) {
            while (!$objLevels->EOF) {
                $arrLevel = array();
                $arrLevelName = array();
                $arrLevelDesc = array();
                $this->intNumEntries = 0;

                //get lang attributes
                $arrLevelName[0] = $objLevels->fields['name'];
                $arrLevelDesc[0] = $objLevels->fields['description'];

                $objLevelAttributes = $objDatabase->Execute("
                    SELECT
                        `lang_id` AS `lang_id`,
                        `level_name` AS `name`,
                        `level_description` AS `description`
                    FROM
                        ".DBPREFIX."module_mediadir_level_names
                    WHERE
                        level_id=".$objLevels->fields['id']."
                ");

                if ($objLevelAttributes !== false) {
                    while (!$objLevelAttributes->EOF) {
                        $arrLevelName[$objLevelAttributes->fields['lang_id']] = htmlspecialchars($objLevelAttributes->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrLevelDesc[$objLevelAttributes->fields['lang_id']] = htmlspecialchars($objLevelAttributes->fields['description'], ENT_QUOTES, CONTREXX_CHARSET);

                        $objLevelAttributes->MoveNext();
                    }
                }

                $arrLevel['levelId'] = intval($objLevels->fields['id']);
                $arrLevel['levelOrder'] = intval($objLevels->fields['order']);
                $arrLevel['levelParentId'] = intval($objLevels->fields['parent_id']);
                $arrLevel['levelName'] = $arrLevelName;
                $arrLevel['levelDescription'] = $arrLevelDesc;
                $arrLevel['levelPicture'] = htmlspecialchars($objLevels->fields['picture'], ENT_QUOTES, CONTREXX_CHARSET);
                if($this->arrSettings['settingsCountEntries'] == 1 || $objInit->mode == 'backend') {
                    $arrLevel['levelNumEntries'] = $this->countEntries(intval($objLevels->fields['id']));
                }
                $arrLevel['levelShowEntries'] = intval($objLevels->fields['show_entries']);
                $arrLevel['levelShowSublevels'] = intval($objLevels->fields['show_sublevels']);
                $arrLevel['levelShowCategories'] = intval($objLevels->fields['show_categories']);
                $arrLevel['levelActive'] = intval($objLevels->fields['active']);

                if($this->bolGetChildren){
                    $arrLevel['levelChildren'] = self::getLevels(null, $objLevels->fields['id']);
                }

                $arrLevels[$objLevels->fields['id']] = $arrLevel;
                $objLevels->MoveNext();
            }
        }

        return $arrLevels;
    }



    function listLevels($objTpl, $intView, $intLevelId=null, $arrParentIds=null, $intEntryId=null, $arrExistingBlocks=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        if(!isset($arrParentIds)) {
            $arrLevels = $this->arrLevels;
        } else {
            $arrLevelChildren = $this->arrLevels;

            foreach ($arrParentIds as $key => $intParentId) {
                $arrLevelChildren = $arrLevelChildren[$intParentId]['levelChildren'];
            }
            $arrLevels = $arrLevelChildren;
        }

        switch ($intView) {
            case 1:
                //Backend View
                foreach ($arrLevels as $key => $arrLevel) {
                    //generate space
                    $spacer = null;
                    $intSpacerSize = null;
                    $intSpacerSize = (count($arrParentIds)*21);
                    $spacer .= '<img src="images/icons/pixel.gif" border="0" width="'.$intSpacerSize.'" height="11" alt="" />';

                    //check expanded categories
                    if($_GET['exp_level'] == 'all') {
                        $bolExpandLevel = true;
                    } else {
                        $this->arrExpandedLevelIds = array();
                        $bolExpandLevel = $this->getExpandedLevels($_GET['exp_level'], array($arrLevel));
                    }

                    if(!empty($arrLevel['levelChildren'])) {
                        if((in_array($arrLevel['levelId'], $this->arrExpandedLevelIds) && $bolExpandLevel) || $_GET['exp_level'] == 'all'){
                            $strLevelIcon = '<a href="index.php?cmd=mediadir&amp;exp_level='.$arrLevel['levelParentId'].'"><img src="images/icons/minuslink.gif" border="0" alt="{MEDIADIR_LEVEL_NAME}" title="{MEDIADIR_LEVEL_NAME}" /></a>';
                        } else {
                            $strLevelIcon = '<a href="index.php?cmd=mediadir&amp;exp_level='.$arrLevel['levelId'].'"><img src="images/icons/pluslink.gif" border="0" alt="{MEDIADIR_LEVEL_NAME}" title="{MEDIADIR_LEVEL_NAME}" /></a>';
                        }
                    } else {
                        $strLevelIcon = '<img src="images/icons/pixel.gif" border="0" width="11" height="11" alt="{MEDIADIR_LEVEL_NAME}" title="{MEDIADIR_LEVEL_NAME}" />';
                    }

                    //parse variables
                    $objTpl->setVariable(array(
                        'MEDIADIR_LEVEL_ROW_CLASS' =>  $this->intRowCount%2==0 ? 'row1' : 'row2',
                        'MEDIADIR_LEVEL_ID' => $arrLevel['levelId'],
                        'MEDIADIR_LEVEL_ORDER' => $arrLevel['levelOrder'],
                        'MEDIADIR_LEVEL_NAME' => $arrLevel['levelName'][0],
                        'MEDIADIR_LEVEL_DESCRIPTION' => $arrLevel['levelDescription'][0],
                        'MEDIADIR_LEVEL_PICTURE' => $arrLevel['levelPicture'],
                        'MEDIADIR_LEVEL_NUM_ENTRIES' => $arrLevel['levelNumEntries'],
                        'MEDIADIR_LEVEL_ICON' => $spacer.$strLevelIcon,
                        'MEDIADIR_LEVEL_VISIBLE_STATE_ACTION' => $arrLevel['levelActive'] == 0 ? 1 : 0,
                        'MEDIADIR_LEVEL_VISIBLE_STATE_IMG' => $arrLevel['levelActive'] == 0 ? 'off' : 'on',
                    ));

                    $objTpl->parse('mediadirLevelsList');
                    $arrParentIds[] = $arrLevel['levelId'];
                    $this->intRowCount++;

                    //get children
                    if(!empty($arrLevel['levelChildren'])){
                        if($bolExpandLevel) {
                            self::listLevels($objTpl, 1, $intLevelId, $arrParentIds);
                        }
                    }

                    @array_pop($arrParentIds);
                }
                break;
            case 2:
                //Frontend View
                $intNumBlocks = count($arrExistingBlocks);
                $i = $intNumBlocks-1;

                //set first index header
                if($this->arrSettings['settingsLevelOrder'] == 2) {
                    $strFirstIndexHeader = null;
                }

                foreach ($arrLevels as $key => $arrLevel) {
                    if($this->arrSettings['settingsLevelOrder'] == 2) {
                        $strIndexHeader = strtoupper(substr($arrLevel['levelName'][0],0,1));

                        if($strFirstIndexHeader != $strIndexHeader) {
                            if ($i < $intNumBlocks-1) {
                                ++$i;
                            } else {
                                $i = 0;
                            }
                            $strIndexHeaderTag = '<span class="mediadirLevelCategoryIndexHeader">'.$strIndexHeader.'</span><br />';
                        } else {
                            $strIndexHeaderTag = null;
                        }
                    } else {
                        if ($i < $intNumBlocks-1) {
                            ++$i;
                        } else {
                            $i = 0;
                        }
                        $strIndexHeaderTag = null;
                    }

                    //get ids
                    if(isset($_GET['cmd'])) {
                        $strLevelCmd = '&amp;cmd='.$_GET['cmd'];
                    } else {
                        $strLevelCmd = null;
                    }

                    //parse variables
                    $objTpl->setVariable(array(
                        'MEDIADIR_CATEGORY_LEVEL_ID' => $arrLevel['levelId'],
                        'MEDIADIR_CATEGORY_LEVEL_NAME' => $arrLevel['levelName'][0],
                        'MEDIADIR_CATEGORY_LEVEL_LINK' => $strIndexHeaderTag.'<a href="index.php?section=mediadir'.$strLevelCmd.'&amp;lid='.$arrLevel['levelId'].'">'.$arrLevel['levelName'][0].'</a>',
                        'MEDIADIR_CATEGORY_LEVEL_DESCRIPTION' => $arrLevel['catDescription'][0],
                        'MEDIADIR_CATEGORY_LEVEL_PICTURE' => '<img src="'.$arrLevel[$intLevelId]['levelPicture'].'" border="0" alt="'.$arrLevel[$intLevelId]['levelName'][0].'" />',
                        'MEDIADIR_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrLevel[$intLevelId]['levelPicture'],
                        'MEDIADIR_CATEGORY_LEVEL_NUM_ENTRIES' => $arrLevel['levelNumEntries'],
                    ));

                    $intBlockId = $arrExistingBlocks[$i];

                    $objTpl->parse('mediadirCategoriesLevels_row_'.$intBlockId);
                    $objTpl->clearVariables();

                    $strFirstIndexHeader = $strIndexHeader;
                }
                break;
            case 3:
                //Dropdown Menu
                foreach ($arrLevels as $key => $arrLevel) {
                    $spacer = null;
                    $intSpacerSize = null;

                    if($arrLevel['levelId'] == $intLevelId) {
                        $strSelected = 'selected="selected"';
                    } else {
                        $strSelected = '';
                    }

                    //generate space
                    $intSpacerSize = (count($arrParentIds));
                    for($i = 0; $i < $intSpacerSize; $i++) {
                        $spacer .= "----";
                    }

                    if($spacer != null) {
                    	$spacer .= "&nbsp;";
                    }

                    $strDropdownOptions .= '<option value="'.$arrLevel['levelId'].'" '.$strSelected.' >'.$spacer.$arrLevel['levelName'][0].'</option>';

                    if(!empty($arrLevel['levelChildren'])) {
                        $arrParentIds[] = $arrLevel['levelId'];
                        $strDropdownOptions .= self::listLevels($objTpl, 3, $intLevelId, $arrParentIds);
                        @array_pop($arrParentIds);
                    }
                }

                return $strDropdownOptions;
                break;
            case 4:
                //level Selector (modify view)
                if(!isset($this->arrSelectedLevels) && $intEntryId!=null) {
                    $this->arrSelectedLevels = array();

                    $objLevelSelector = $objDatabase->Execute("
                        SELECT
                            `level_id`
                        FROM
                            ".DBPREFIX."module_mediadir_rel_entry_levels
                        WHERE
                            `entry_id` = '".$intEntryId."'
                    ");

                    if ($objLevelSelector !== false) {
                        while (!$objLevelSelector->EOF) {
                            $this->arrSelectedLevels[] = intval($objLevelSelector->fields['level_id']);
                            $objLevelSelector->MoveNext();
                        }
                    }
                }

                foreach ($arrLevels as $key => $arrLevel) {
                    $spacer = null;
                    $intSpacerSize = null;

                     //generate space
                    $intSpacerSize = (count($arrParentIds));
                    for($i = 0; $i < $intSpacerSize; $i++) {
                        $spacer .= "----";
                    }

                    if($spacer != null) {
                    	$spacer .= "&nbsp;";
                    }

                    if(in_array($arrLevel['levelId'], $this->arrSelectedLevels)) {
                        $this->strSelectedOptions .= '<option value="'.$arrLevel['levelId'].'">'.$spacer.$arrLevel['levelName'][0].'</option>';
                    } else {
                        $this->strNotSelectedOptions .= '<option value="'.$arrLevel['levelId'].'">'.$spacer.$arrLevel['levelName'][0].'</option>';
                    }

                    if(!empty($arrLevel['levelChildren'])) {
                        $arrParentIds[] = $arrLevel['levelId'];
                        self::listLevels($objTpl, 4, $intLevelId, $arrParentIds, $intEntryId);
                        @array_pop($arrParentIds);
                    }
                }

                $arrSelectorOptions['selected'] = $this->strSelectedOptions;
                $arrSelectorOptions['not_selected'] = $this->strNotSelectedOptions;

                return $arrSelectorOptions;
                break;
            case 5:
                //Frontend View Detail
                $objTpl->setVariable(array(
                    'MEDIADIR_CATEGORY_LEVEL_ID' => $arrLevels[$intLevelId]['levelId'],
                    'MEDIADIR_CATEGORY_LEVEL_NAME' => $arrLevels[$intLevelId]['levelName'][0],
                    'MEDIADIR_CATEGORY_LEVEL_LINK' => '<a href="index.php?section=mediadir&amp;cid='.$arrLevels[$intCategoryId]['levelId'].'">'.$arrLevels[$intLevelId]['levelName'][0].'</a>',
                    'MEDIADIR_CATEGORY_LEVEL_DESCRIPTION' => $arrLevels[$intLevelId]['levelDescription'][0],
                    'MEDIADIR_CATEGORY_LEVEL_PICTURE' => '<img src="'.$arrLevels[$intLevelId]['levelPicture'].'.thumb" border="0" alt="'.$arrLevels[$intLevelId]['levelName'][0].'" />',
                    'MEDIADIR_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrLevels[$intLevelId]['levelPicture'],
                    'MEDIADIR_CATEGORY_LEVEL_NUM_ENTRIES' => $arrLevels[$intLevelId]['levelNumEntries'],
                ));

                if(!empty($arrLevels[$intLevelId]['levelPicture']) && $this->arrSettings['settingsShowLevelImage'] == 1) {
                    $objTpl->parse('mediadirCategoryLevelPicture');
                } else {
                    $objTpl->hideBlock('mediadirCategoryLevelPicture');
                }

                if(!empty($arrLevels[$intLevelId]['levelDescription'][0]) && $this->arrSettings['settingsShowLevelDescription'] == 1) {
                    $objTpl->parse('mediadirCategoryLevelDescription');
                } else {
                    $objTpl->hideBlock('mediadirCategoryLevelDescription');
                }

                if(!empty($arrLevels)) {
                    $objTpl->parse('mediadirCategoryLevelDetail');
                } else {
                    $objTpl->hideBlock('mediadirCategoryLevelDetail');
                }

                break;
        }
    }



    function getExpandedLevels($intExpand, $arrData)
    {
        foreach ($arrData as $key => $arrLevel) {
            if ($arrLevel['levelId'] != $intExpand) {
                if(!empty($arrLevel['levelChildren'])) {
                    $this->arrExpandedLevelIds[] = $arrLevel['levelId'];
                    $this->getExpandedLevels($intExpand, $arrLevel['levelChildren']);
                }
            } else {
                $this->arrExpandedLevelIds[] = $arrLevel['levelId'];
                $this->arrExpandedLevelIds[] = "found";
            }
        }

        if(in_array("found", $this->arrExpandedLevelIds)) {
            return true;
        } else {
           return false;
        }


    }




    function saveLevel($arrData, $intLevelId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID;

        //get data
        $intId = intval($intLevelId);
        $intParentId = intval($arrData['levelPosition']);
        $intOrder = intval(0);
        $intShowEntries = intval($arrData['levelShowEntries']);
        $intShowSublevels = intval($arrData['levelShowSublevels']);
        $intShowCategories = intval($arrData['levelShowCategories']);
        $intActive = intval($arrData['levelActive']);
        $strPicture = contrexx_addslashes(contrexx_strip_tags($arrData['levelImage']));

        $arrName = $arrData['levelName'];
        $arrDescription = $arrData['levelDescription'];

        if(empty($intId)) {
            //insert new category
            $objInsertAttributes = $objDatabase->Execute("
                INSERT INTO
                    ".DBPREFIX."module_mediadir_levels
                SET
                    `parent_id`='".$intParentId."',
                    `order`='".$intOrder."',
                    `show_entries`='".$intShowEntries."',
                    `show_sublevels`='".$intShowSublevels."',
                    `show_categories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
            ");

            if($objInsertAttributes !== false) {
                $intId = $objDatabase->Insert_ID();

                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    if(empty($arrName[0])) $arrName[0] = "[[".$_ARRAYLANG['TXT_MEDIADIR_NEW_LEVEL']."]]";

                    $strName = $arrName[$arrLang['id']];
                    $strDescription = $arrDescription[$arrLang['id']];

                    if(empty($strName)) $strName = contrexx_addslashes(contrexx_strip_tags($arrName[0]));
                    if(empty($strDescription)) $strDescription = contrexx_addslashes(contrexx_strip_tags($arrDescription[0]));

                    $objInsertNames = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_mediadir_level_names
                        SET
                            `lang_id`='".intval($arrLang['id'])."',
                            `level_id`='".intval($intId)."',
                            `level_name`='".contrexx_addslashes(contrexx_strip_tags($strName))."',
                            `level_description`='".contrexx_addslashes(contrexx_strip_tags($strDescription))."'
                    ");
                }

                if($objInsertNames !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            //update category
            if($intParentId == $intLevelId) {
                $parentSql = null;
            } else {
                $parentSql = "`parent_id`='".$intParentId."',";
            }

            $objUpdateAttributes = $objDatabase->Execute("
                UPDATE
                    ".DBPREFIX."module_mediadir_levels
                SET
                    ".$parentSql."
                    `order`='".$intOrder."',
                    `show_entries`='".$intShowEntries."',
                    `show_sublevels`='".$intShowSublevels."',
                    `show_categories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
                WHERE
                    `id`='".$intId."'
            ");

            if($objUpdateAttributes !== false) {
                $objDefaultLang = $objDatabase->Execute("
                    SELECT
                        `level_name` AS `name`,
                        `level_description` AS `description`
                    FROM
                        ".DBPREFIX."module_mediadir_level_names
                    WHERE
                        lang_id=".$_LANGID."
                        AND `level_id` = '".$intId."'
                    LIMIT
                        1
                ");

                if ($objDefaultLang !== false) {
                    $strOldDefaultName = $objDefaultLang->fields['name'];
                    $strOldDefaultDescription = $objDefaultLang->fields['description'];
                }

                $objDeleteNames = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_mediadir_level_names WHERE level_id='".$intId."'");

                if($objInsertNames !== false) {
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $strName = $arrName[$arrLang['id']];
                        $strDescription = $arrDescription[$arrLang['id']];

                        if($arrLang['id'] == $_LANGID) {
                            if($arrName[0] != $strOldDefaultName) $strName = $arrName[0];
                            if($arrName[$arrLang['id']] != $strOldDefaultName) $strName = $arrName[$arrLang['id']];

                            if($arrDescription[0] != $strOldDefaultDescription) $strDescription = $arrDescription[0];
                            if($arrDescription[$arrLang['id']] != $strOldDefaultDescription) $strDescription = $arrDescription[$arrLang['id']];
                        }

                        if(empty($strName)) $strName = $arrName[0];
                        if(empty($strDescription)) $strDescription = $arrDescription[0];

                        $objInsertNames = $objDatabase->Execute("
                            INSERT INTO
                                ".DBPREFIX."module_mediadir_level_names
                            SET
                                `lang_id`='".intval($arrLang['id'])."',
                                `level_id`='".intval($intId)."',
                                `level_name`='".contrexx_addslashes(contrexx_strip_tags($strName))."',
                                `level_description`='".contrexx_addslashes(contrexx_strip_tags($strDescription))."'
                        ");
                    }

                    if($objInsertNames !== false) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }



    function deleteLevel($intLevelId=null)
    {
        global $objDatabase;

        $intLevelId = intval($intLevelId);

        $objSubLevelsRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_mediadir_levels WHERE parent_id='".$intLevelId."'");
        if ($objSubLevelsRS !== false) {
            while (!$objSubLevelsRS->EOF) {
                $intSubLevelId = $objSubLevelsRS->fields['id'];
                $this->deleteLevel($intSubLevelId);
                $objSubLevelsRS->MoveNext();
            };
        }

        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_mediadir_levels WHERE id='$intLevelId'");
        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_mediadir_level_names WHERE level_id='$intLevelId'");
        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_mediadir_rel_entry_levels WHERE level_id='$intLevelId'");

        if ($objDeleteLevelRS !== false) {
            return true;
        } else {
            return false;
        }
    }



    function countEntries($intLevelId=null)
    {
        global $objDatabase;

        $intLevelId = intval($intLevelId);

        $objSubLevelsRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_mediadir_levels WHERE parent_id='".$intLevelId."'");
        if ($objSubLevelsRS !== false) {
            while (!$objSubLevelsRS->EOF) {
                $intSubLevelId = $objSubLevelsRS->fields['id'];
                $this->countEntries($intSubLevelId);
                $objSubLevelsRS->MoveNext();
            };
        }

        $objCountEntriesRS = $objDatabase->Execute("SELECT
                                                        entry_id
                                                    FROM
                                                        ".DBPREFIX."module_mediadir_rel_entry_levels
                                                    WHERE
                                                        level_id ='$intLevelId'
                                                   ");

        $this->intNumEntries += $objCountEntriesRS->RecordCount();

        return intval($this->intNumEntries);
    }



    function saveOrder($arrData) {
        global $objDatabase;

        foreach($arrData['levelOrder'] as $intLevelId => $intLevelOrder) {
            $objRSLevelOrder = $objDatabase->Execute("UPDATE ".DBPREFIX."module_mediadir_levels SET `order`='".intval($intLevelOrder)."' WHERE `id`='".intval($intLevelId)."'");

            if ($objRSLevelOrder === false) {
                return false;
            }
        }

        return true;
    }
}
?>