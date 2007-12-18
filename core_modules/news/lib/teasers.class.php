<?php
/**
 * Teasers
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author Comvation Development Team <info@comvation.com>
 * @version 1.0.0
 * @package     contrexx
 * @subpackage  core_module_news
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Includes
 */
require_once ASCMS_CORE_MODULE_PATH . '/news/lib/newsLib.class.php';

/**
 * Teasers
 *
 * class to show the news teasers
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author Comvation Development Team <info@comvation.com>
 * @access public
 * @version 1.0.0
 * @package     contrexx
 * @subpackage  core_module_news
 */
class Teasers extends newsLibrary
{
	var $_pageTitle;
	var $_objTpl;
	var $administrate;
	var $arrTeaserTemplates = array();
	var $arrTeaserFrameTemplates = array();

	var $arrTeaserFrames;
	var $arrTeaserFrameNames;
	var $arrTeasers;

	var $arrFrameTeaserIds;

	var $arrNewsTeasers = array();
	var $arrNewsCategories = array();

	var $_currentXMLElementId;
	var $_currentXMLElement;
	var $_currentXMLArrayToFill;

	/**
	* constructor
	*/
	function Teasers($administrate = false)
	{
		$this->__construct($administrate);
	}

	/**
	* PHP5 constructor
	*
	* @global object $objTemplate
	* @global array $_ARRAYLANG
	* @see HTML_Template_Sigma::setErrorHandling, HTML_Template_Sigma::setVariable, initialize()
	*/
	function __construct($administrate = false)
	{
		global $objTemplate, $_ARRAYLANG;

		$this->administrate = $administrate;

		$this->_objTpl = &new HTML_Template_Sigma('.');
		$this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

		$this->_initialize();

	}



	function _initialize()
	{
		$this->initializeTeasers();
		$this->initializeTeaserFrames();

		//$this->_initializeTeaserTemplates();
		$this->initializeTeaserFrameTemplates();
	}



	function initializeTeasers()
	{
		global $objDatabase, $objInit, $_LANGID;
$objDatabase->debug=1;
		$this->arrTeasers = array();

		if ($this->administrate) {
			$langId = $objInit->userFrontendLangId;
		} else {
			$langId = $_LANGID;
		}

		$objResult = $objDatabase->Execute("SELECT	 news.id AS id,
									                 news.date AS date,
									                 news.userid AS userid,
									                 news.title AS title,
									                 news.teaser_frames AS teaser_frames,
									                 news.catid AS catid,
									                 news.redirect AS redirect,
									                 cat.name AS category_name,
									                 news.teaser_text AS teaser_text,
									                 news.teaser_show_link AS teaser_show_link,
									                 news.teaser_image_path AS teaser_image_path".
									                 ($this->administrate == false ? ",
									                 users.id AS usId,
													 users.username AS username,
													 users.firstname AS firstname,
													 users.lastname AS lastname" : '')."
									            FROM ".DBPREFIX."module_news AS news
										INNER JOIN   ".DBPREFIX."module_news_categories AS cat on cat.catid = news.catid "
										.($this->administrate == false ? " INNER JOIN ".DBPREFIX."access_users AS users on users.id = news.userid " : '').
									           " WHERE news.lang=".$langId."
									             ".($this->administrate == false ? "
									             AND news.validated='1'
									             AND news.status='1'
									             AND (news.startdate<=CURDATE() OR news.startdate='0000-00-00') AND (news.enddate>=CURDATE() OR news.enddate='0000-00-00')" : "" )."
									        ORDER BY date DESC");

    	if ($objResult !== false) {
    		while (!$objResult->EOF) {
    			$arrFrames = explode(';', $objResult->fields['teaser_frames']);

    			foreach ($arrFrames as $frameId) {
    				if (!isset($this->arrFrameTeaserIds[$frameId])) {
    					$this->arrFrameTeaserIds[$frameId] = array();
    				}
    				array_push($this->arrFrameTeaserIds[$frameId], $objResult->fields['id']);
    			}

    			if(!empty($objResult->fields['redirect'])) {
    				$extUrl = substr($objResult->fields['redirect'], 7);
    				$tmp	= explode('/', $extUrl);
    				$extUrl = "(".$tmp[0].")";
    			} else {
    				$extUrl = "";
    			}

    			if($this->administrate == false){
	    			if(!empty($objResult->fields['firstname']) && !empty($objResult->fields['lastname'])) {
	    				$author = $objResult->fields['firstname']." ".$objResult->fields['lastname'];
	    			} else {
	    				$author = $objResult->fields['username'];
	    			}
	    		} else {
	    			$author = '';
	    		}

				$this->arrTeasers[$objResult->fields['id']] = array(
    				'id'					=> $objResult->fields['id'],
    				'date'					=> $objResult->fields['date'],
    				'title'					=> $objResult->fields['title'],
    				'teaser_frames'			=> $objResult->fields['teaser_frames'],
    				'redirect'				=> $objResult->fields['redirect'],
    				'ext_url'				=> $extUrl,
    				'category'			    => $objResult->fields['category_name'],
    				'teaser_text'			=> $objResult->fields['teaser_text'],
    				'teaser_show_link'		=> $objResult->fields['teaser_show_link'],
    				'author'				=> $author,
    				'teaser_image_path'		=> !empty($objResult->fields['teaser_image_path']) ? $objResult->fields['teaser_image_path'] : ASCMS_MODULE_IMAGE_WEB_PATH.'/news/pixel.gif'
				);
    			$objResult->MoveNext();
    		}
    	}
	}

	function initializeTeaserFrames($id = 0)
	{
		global $objDatabase, $objInit;

		$this->arrTeaserFrames = array();
		$this->arrTeaserFrameNames = array();

		if ($objInit->mode == 'frontend') {
			$langId = $objInit->getFrontendLangId();
		} else {
			$langId = $objInit->getUserFrontendLangId();
		}

		if ($id != 0) {
			$objResult = $objDatabase->SelectLimit("SELECT id, frame_template_id, name FROM ".DBPREFIX."module_news_teaser_frame WHERE lang_id=".$langId." AND id=".$id, 1);
		} else {
			$objResult = $objDatabase->Execute("SELECT id, frame_template_id, name FROM ".DBPREFIX."module_news_teaser_frame WHERE lang_id=".$langId." ORDER BY name");
		}
		if ($objResult !== false) {
			while (!$objResult->EOF) {
				$this->arrTeaserFrames[$objResult->fields['id']] = array(
					'id'				=> $objResult->fields['id'],
					'frame_template_id'	=> $objResult->fields['frame_template_id'],
					'name'				=> $objResult->fields['name']
				);

				$this->arrTeaserFrameNames[$objResult->fields['name']] = $objResult->fields['id'];
				$objResult->MoveNext();
			}
		}
	}



	/**
	* Inizialize teaser frame templates
	*
	* @access private
	*/
	function initializeTeaserFrameTemplates($id = 0)
	{
		global $objDatabase;

		if ($id == 0) {
			$objResult = $objDatabase->Execute("SELECT id, description, html, source_code_mode FROM ".DBPREFIX."module_news_teaser_frame_templates");
		} else {
			$objResult = $objDatabase->Execute("SELECT id, description, html, source_code_mode FROM ".DBPREFIX."module_news_teaser_frame_templates WHERE id=".$id);
		}
		if ($objResult !== false) {
			while (!$objResult->EOF) {
				$this->arrTeaserFrameTemplates[$objResult->fields['id']] = array(
					'id'				=> $objResult->fields['id'],
					'description'		=> $objResult->fields['description'],
					'html'				=> $objResult->fields['html'],
					'source_code_mode'	=> $objResult->fields['source_code_mode']
				);
				$objResult->MoveNext();
			}
		}
	}


	function getTeaserFrame($teaserFrameId, $templateId)
	{
		return $this->_getTeaserFrame($teaserFrameId, $templateId);
	}


	function setTeaserFrames($arrTeaserFrames, &$code)
	{
		global $objDatabase;

		$arrTeaserFramesNames = array_flip($this->arrTeaserFrameNames);

		foreach ($arrTeaserFrames as $teaserFrameName) {
			$arrMatches = preg_grep('/^'.$teaserFrameName.'$/i', $arrTeaserFramesNames);

			if (count($arrMatches)>0) {
				$frameId = array_keys($arrMatches);
				$id = $frameId[0];
				$templateId = $this->arrTeaserFrames[$id]['frame_template_id'];
				$code = str_replace("{TEASERS_".$teaserFrameName."}", $this->_getTeaserFrame($id, $templateId), $code);

			}
		}
	}

	/**
	* Get teaser frame
	*
	* Returns the selected teaser frame by $id with its teaserboxes
	*
	* @access private
	* @return string
	*/



	function _getTeaserFrame($id, $templateId)
	{
		$teaserFrame = "";

		if (isset($this->arrTeaserFrameTemplates[$templateId]['html'])) {
			$teaserFrame = $this->arrTeaserFrameTemplates[$templateId]['html'];
			if (preg_match_all('/<!-- BEGIN (teaser_[0-9]+) -->/ms', $teaserFrame, $arrTeaserBlocks)) {
				foreach ($arrTeaserBlocks[1] as $nr => $teaserBlock) {
					if (isset($this->arrFrameTeaserIds[$id][$nr])) {
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_CATEGORY\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.$this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['category'].'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_DATE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.date(ASCMS_DATE_SHORT_FORMAT, $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date']).'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_LONG_DATE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.date(ASCMS_DATE_FORMAT, $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date']).'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_TITLE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.htmlentities($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['title'], ENT_QUOTES, CONTREXX_CHARSET).'${2}', $teaserFrame);
						if ($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_show_link']) {
							$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_URL\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.((empty($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect'])) ? 'index.php?section=news&amp;cmd=details&amp;newsid='.$this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['id'].'&amp;teaserId='.$this->arrTeaserFrames[$id]['id'] : $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect']).'${2}', $teaserFrame);
							$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_URL_TARGET\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.((empty($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect'])) ? '_self' : '_blank').'${2}', $teaserFrame);
							$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\<!-- BEGIN teaser_link -->([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.'${2}', $teaserFrame);
							$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\<!-- END teaser_link -->([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.'${2}', $teaserFrame);
						} else {
							$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\<!-- BEGIN teaser_link -->[\S\s]*<!-- END teaser_link -->([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.'${2}', $teaserFrame);
						}
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_IMAGE_PATH\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.$this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_image_path'].'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_TEXT\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.nl2br($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_text']).'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_AUTHOR\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.$this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['author'].'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_EXT_URL\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.$this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['ext_url'].'${2}', $teaserFrame);
					} elseif ($this->administrate) {
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_CATEGORY\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_CATEGORY${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_DATE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_DATE${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_LONG_DATE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_LONG_DATE${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_TITLE\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_TITLE${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_URL\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_URL${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_URL_TARGET\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_URL_TARGET${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_IMAGE_PATH\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_IMAGE_PATH${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_TEXT\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}TXT_TEXT${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_AUTHOR\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.'TEASER_AUTHOR'.'${2}', $teaserFrame);
						$teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->[\S\s]*)\{TEASER_EXT_URL\}([\S\s]*<!-- END '.$teaserBlock.' -->)/', '${1}'.'TEASER_EXT_URL'.'${2}', $teaserFrame);
					} else {
						$teaserFrame = preg_replace('/<!-- BEGIN '.$teaserBlock.' -->[\S\s]*<!-- END '.$teaserBlock.' -->/', '&nbsp;', $teaserFrame);
					}

					if (!$this->administrate) {
						$teaserFrame = preg_replace('/<!-- BEGIN '.$teaserBlock.' -->/', '', $teaserFrame);
						$teaserFrame = preg_replace('/<!-- END '.$teaserBlock.' -->/', '', $teaserFrame);
					} else {
						$teaserFrame = preg_replace('/<!-- BEGIN '.$teaserBlock.' -->/', '<table cellspacing="0" cellpadding="0" style="border:1px dotted #aaaaaa;"><tr><td>', $teaserFrame);
						$teaserFrame = preg_replace('/<!-- END '.$teaserBlock.' -->/', '</td></tr></table>', $teaserFrame);
					}
				}
			}
		}

		return $teaserFrame;
	}




	function getFirstTeaserFrameTemplateId()
	{
		reset($this->arrTeaserFrameTemplates);
		$arrFrameTeamplte = current($this->arrTeaserFrameTemplates);
		return $arrFrameTeamplte['id'];
	}





	function getTeaserFrameTemplateMenu($selectedId, $attributeStr = '')
	{
		$menu = "";
		foreach ($this->arrTeaserFrameTemplates as $teaserFrameTemplateId => $teaserFrameTemplate) {
			if ($selectedId == $teaserFrameTemplateId) {
				$selected = "selected=\"selected\"";
			} else {
				$selected = "";
			}
			$menu .= "<option value=\"".$teaserFrameTemplateId."\" ".$selected.">".$teaserFrameTemplate['description']."</option>\n";
		}
		return $menu;
	}



	function updateTeaserFrame($id, $templateId, $name)
    {
    	global $objDatabase;

    	if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_news_teaser_frame SET frame_template_id=".$templateId.", name='".$name."' WHERE id=".$id) !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }

    function addTeaserFrame($id, $templateId, $name)
    {
    	global $objDatabase, $objInit;

    	if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_teaser_frame (`frame_template_id`, `name`, `lang_id`) VALUES (".$templateId.", '".$name."', ".$objInit->userFrontendLangId.")") !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }

    function updateTeaserFrameTemplate($id, $description, $html, $sourceCodeMode)
    {
    	global $objDatabase;

    	if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_news_teaser_frame_templates SET description='".$description."', html='".$html."', source_code_mode='".$sourceCodeMode."' WHERE id=".$id) !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }

    function addTeaserFrameTemplate($description, $html, $sourceCodeMode)
    {
    	global $objDatabase, $objInit;

    	if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_teaser_frame_templates (`description`, `html`, `source_code_mode`) VALUES ('".$description."', '".$html."', '".$sourceCodeMode."')") !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }


    function deleteTeaserFrame($frameId)
    {
    	global $objDatabase;

    	if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_teaser_frame WHERE id=".$frameId) !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }

    function deleteTeaserFrameTeamplte($templateId)
    {
    	global $objDatabase, $_ARRAYLANG;

    	foreach ($this->arrTeaserFrames as $arrTeaserFrame) {
    		if ($arrTeaserFrame['frame_template_id'] == $templateId) {
    			return $_ARRAYLANG['TXT_COULD_NOT_DELETE_TEMPLATE_TEXT'];
    		}
    	}

    	if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_teaser_frame_templates WHERE id=".$templateId) !== false) {
    		return true;
    	} else {
    		return false;
    	}
    }



    function isUniqueFrameName($frameId, $frameName)
    {
    	$arrFrameNames = array_flip($this->arrTeaserFrameNames);
    	$arrEqualFrameNames = preg_grep('/^'.$frameName.'$/i', $arrFrameNames);

    	if (count($arrEqualFrameNames) == 0 || array_key_exists($frameId, $arrEqualFrameNames)) {
    		return true;
    	} else {
    		return false;
    	}
    }
}
?>
