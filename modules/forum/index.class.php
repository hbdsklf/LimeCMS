<?php
/**
 * Forum
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version	    $Id: index.inc.php,v 1.00 $
 * @package     contrexx
 * @subpackage  module_forum
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Includes
 */
require_once ASCMS_MODULE_PATH.'/forum/lib/forumLib.class.php';

/**
 * Forum
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version	    $Id: index.inc.php,v 1.00 $
 * @package     contrexx
 * @subpackage  module_forum
 */
class Forum extends ForumLibrary {

	var $_objTpl;
	var $strError = ''; //errormessage for captcha


	/**
	* Constructor-Fix for non PHP5-Servers
    *
    */
	function Forum($strPageContent) {
		$this->__constructor($strPageContent);
	}


	/**
	* Constructor	-> Call parent-constructor, set language id and create local template-object
    *
    * @global	integer		$_LANGID
    */
	function __constructor($strPageContent) {
		global $_LANGID;

		ForumLibrary::__constructor();

		$this->_intLangId = intval($_LANGID);

	    $this->_objTpl = &new HTML_Template_Sigma('.');
		$this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
		$this->_objTpl->setTemplate($strPageContent);

	}


	/**
	* Reads $_GET['act'] and selects (depending on the value) an action
	*
    */
	function getPage()
	{
		if(!isset($_GET['cmd'])) {
    		$_GET['cmd'] = '';
    	}

    	if(!empty($_GET['postId']) && $_GET['act'] == 'rate'){
	    	return $this->_rate();
    	}

    	switch ($_GET['cmd']) {
    		case 'searchTags':
    			$this->_showTags();
    			break;
    		case 'board':
    			$this->showForum($_GET['id']);
    			break;
    		case 'thread':
    			$this->showThread($_GET['id']);
    			break;
    		case 'cat':
    			$this->showCategory($_GET['id']);
    			break;
    		case 'userinfo':
    			$this->showProfile($_GET['id']);
    			break;
    		case 'notification':
    			$this->showNotifications();
    			break;
    		case 'toplist':
    			$this->showTopList();
    			break;
    		case 'cloud':
    			$this->showTagCloud();
    			break;
    		default:
    			$this->showForumOverview();
    			break;
    	}
    	return $this->_objTpl->get();
	}


	/**
	 * shows the search results for tags
	 *
	 * @return boolean success
	 */
	function _showTags(){
		global $objDatabase, $_CONFIG, $_ARRAYLANG;
		$pos = !empty($_GET['pos']) ? intval($_GET['pos']) : '0';
		$term = contrexx_addslashes($_GET['term']);
		$searchContentToo = !empty($_GET['searchContent']) ? intval($_GET['searchContent']) : '0';

		$this->_objTpl->setVariable(array(
			'FORUM_SEARCH_TERM'				=> htmlentities($term, ENT_QUOTES, CONTREXX_CHARSET),
			'FORUM_SEARCH_CONTENT_CHECKED'	=> $searchContentToo == 1 ? 'checked="checked"' : '',
		));

		if(strlen($term) < 3 && !empty($_REQUEST['search'])){
			$this->_objTpl->setVariable('FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_SEARCH_TERM_TOO_SHORT']);
			$this->_objTpl->parse('forumError');
			return false;
		}

		$queryAdd = '';
		if($searchContentToo > 0){
			$queryAdd = " OR content LIKE '%".$term."%'";
		}

		$query = "SELECT count(1) as `cnt`
					FROM `".DBPREFIX."module_forum_postings`
					WHERE `keywords` LIKE '%".$term."%'
					OR `subject` LIKE '%".$term."%' ".$queryAdd;
		$objRS = $objDatabase->Execute($query);
		$count = $objRS->fields['cnt'];
		$query = "SELECT id, thread_id, category_id, subject, content, keywords,
						 MATCH (content,subject,keywords) AGAINST ('%".$term."%') AS score
			FROM `".DBPREFIX."module_forum_postings`
			WHERE `keywords` LIKE '%".$term."%'
			OR `subject` LIKE '%".$term."%' ".$queryAdd. " ORDER BY score DESC";
		$objRS = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);
		while(!$objRS->EOF){
			$postId = $objRS->fields['id'];
			$threadId = $objRS->fields['thread_id'];
			$catId = $objRS->fields['category_id'];
			$link = 'index.php?section=forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
								'&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

			$subject = $objRS->fields['subject'];
			$content = $objRS->fields['content'];
			$keywords = $objRS->fields['keywords'];

			if(strlen($content) > 60){
				$content = substr($content, 0, 60).'[...]';
			}

			$this->_objTpl->setVariable(array(
				'FORUM_THREAD_SUBJECT'	 => $subject,
				'FORUM_THREAD_LINK'		 => $link,
				'FORUM_THREAD_KEYWORDS'	 => $keywords,
				'FORUM_THREAD_CONTENT'	 => $content,
			));
			$this->_objTpl->parse('threadList');
			$objRS->MoveNext();
		}

		if($count > $_CONFIG['corePagingLimit']){
			$paging = getPaging($count, $pos, '&amp;section=forum&amp;cmd=searchTags&amp;term='.$term, $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS']);
			$this->_objTpl->setVariable('FORUM_SEARCH_PAGING', $paging);
		}
	}

	/**
	 * parse the tag cloud and hitlist
	 *
	 */
	function showTagCloud(){
			$this->_objTpl->setVariable(array(
				'FORUM_TAG_CLOUD'	=> $this->getTagCloud(),
				'FORUM_TAG_HITLIST'	=> $this->getTagHitlist()
			));
	}

	/**
	 * parse the top lists (most viewed + top rated)
	 *
	 */
	function showTopList(){
		error_reporting(E_ALL);ini_set('display_errors',1);
		$this->_parseMostViewed($this->_getMostViewed());
		$this->_parseTopRated($this->_getTopRated());
	}

	function _parseMostViewed($objRS){
		while(!$objRS->EOF){
			$postId = $objRS->fields['id'];
			$threadId = $objRS->fields['thread_id'];
			$catId = $objRS->fields['category_id'];
			$link = 'index.php?section=forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
								'&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

			$subject = $objRS->fields['subject'];
			$content = $objRS->fields['content'];
			$keywords = $objRS->fields['keywords'];

			if(strlen($content) > 60){
				$content = substr($content, 0, 60).'[...]';
			}

			$this->_objTpl->setVariable(array(
				'FORUM_MOST_VIEWED_SUBJECT'	 => $subject,
				'FORUM_MOST_VIEWED_LINK'	 => $link,
				'FORUM_MOST_VIEWED_KEYWORDS' => $keywords,
				'FORUM_MOST_VIEWED_CONTENT'	 => $content,
				'FORUM_MOST_VIEWED_VIEWS'	 => $objRS->fields['views'],
			));
			$this->_objTpl->parse('mostViewed');
			$objRS->MoveNext();
		}
	}

	function _getMostViewed(){
		global $objDatabase;
		$query = "	SELECT id, thread_id, category_id, subject, content, keywords, views
					FROM `".DBPREFIX."module_forum_postings`
					ORDER BY `views` DESC";
		$objRS = $objDatabase->SelectLimit($query, $this->_topListLimit);
		return $objRS;
	}


	function _parseTopRated($objRS){
		while(!$objRS->EOF){
			$postId = $objRS->fields['id'];
			$threadId = $objRS->fields['thread_id'];
			$catId = $objRS->fields['category_id'];
			$link = 'index.php?section=forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
								'&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

			$subject = $objRS->fields['subject'];
			$content = $objRS->fields['content'];
			$keywords = $objRS->fields['keywords'];

			if(strlen($content) > 60){
				$content = substr($content, 0, 60).'[...]';
			}

			$this->_objTpl->setVariable(array(
				'FORUM_TOP_RATED_SUBJECT'	 => $subject,
				'FORUM_TOP_RATED_LINK'	 	 => $link,
				'FORUM_TOP_RATED_KEYWORDS'	 => $keywords,
				'FORUM_TOP_RATED_CONTENT'	 => $content,
				'FORUM_TOP_RATED_RATING'	 => $objRS->fields['rating'],
			));
			$this->_objTpl->parse('topRated');
			$objRS->MoveNext();
		}
	}

	function _getTopRated(){
		global $objDatabase;
		$query = "	SELECT id, thread_id, category_id, subject, content, keywords, rating
					FROM `".DBPREFIX."module_forum_postings`
					ORDER BY `rating` DESC";
		$objRS = $objDatabase->SelectLimit($query, $this->_topListLimit);
		return $objRS;
	}



	/**
	 * checks if a user has already rated a post
	 *
	 * @param integer $postId
	 * @return boolean
	 */
	function _hasRated($postId){
		global $objDatabase;
		$query = "	DELETE FROM `".DBPREFIX."module_forum_rating`
					WHERE `time`+".$this->_rateTimeout."  < unix_timestamp()";
		$objDatabase->Execute($query);

		$query = "	SELECT 1 FROM `".DBPREFIX."module_forum_rating`
					WHERE `user_id` = ".$_SESSION['auth']['userid']
				."	AND `post_id` = ".$postId;
		$objRS = $objDatabase->Execute($query);
		if($objRS->RecordCount() > 0){
			return true;
		} else {
			return false;
		}
	}


	/**
	 * updates the rated table to prevent multiple ratings with a timeout
	 *
	 * @param integer $postId
	 * @return boolean
	 */
	function _updateRated($postId){
		global $objDatabase;
		$query = "	INSERT INTO `".DBPREFIX."module_forum_rating`
					(`user_id`, `post_id`, `time`) VALUES
					(".$_SESSION['auth']['userid'].", $postId, ".time().")";
		return $objDatabase->Execute($query);
	}

	/**
	 * update the rating for a post
	 *
	 */
	function _rate(){
		global $objDatabase;
		if($_SESSION['auth']['userid'] < 1){
			die('not allowed to vote.');
		}
		$postId = intval($_GET['postId']);

		if($this->_hasRated($postId)){
			die('already voted.');
		}

		if(!$this->_updateRated($postId)){
			die('DB error.');
		}

		if(intval($_GET['value']) == 1){
			$set = "`rating` = `rating` + 1";
		}elseif(intval($_GET['value']) == -1){
			$set = "`rating` = `rating` - 1";
		}
		$query = "	UPDATE `".DBPREFIX."module_forum_postings`
					SET $set
					WHERE `id` = $postId";
		$objDatabase->Execute($query);
		header ("Content-type: image/gif");
		//1x1px gif to make onload succeed
		die("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\xF7\x00\x00\x00\x00\x00\x80\x00\x00\x00\x80\x00\x80\x80\x00\x00\x00\x80\x80\x00\x80\x00\x80\x80\x80\x80\x80\xC0\xC0\xC0\xFF\x00\x00\x00\xFF\x00\xFF\xFF\x00\x00\x00\xFF\xFF\x00\xFF\x00\xFF\xFF\xFF\xFF\xFF\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x33\x00\x00\x66\x00\x00\x99\x00\x00\xCC\x00\x00\xFF\x00\x33\x00\x00\x33\x33\x00\x33\x66\x00\x33\x99\x00\x33\xCC\x00\x33\xFF\x00\x66\x00\x00\x66\x33\x00\x66\x66\x00\x66\x99\x00\x66\xCC\x00\x66\xFF\x00\x99\x00\x00\x99\x33\x00\x99\x66\x00\x99\x99\x00\x99\xCC\x00\x99\xFF\x00\xCC\x00\x00\xCC\x33\x00\xCC\x66\x00\xCC\x99\x00\xCC\xCC\x00\xCC\xFF\x00\xFF\x00\x00\xFF\x33\x00\xFF\x66\x00\xFF\x99\x00\xFF\xCC\x00\xFF\xFF\x33\x00\x00\x33\x00\x33\x33\x00\x66\x33\x00\x99\x33\x00\xCC\x33\x00\xFF\x33\x33\x00\x33\x33\x33\x33\x33\x66\x33\x33\x99\x33\x33\xCC\x33\x33\xFF\x33\x66\x00\x33\x66\x33\x33\x66\x66\x33\x66\x99\x33\x66\xCC\x33\x66\xFF\x33\x99\x00\x33\x99\x33\x33\x99\x66\x33\x99\x99\x33\x99\xCC\x33\x99\xFF\x33\xCC\x00\x33\xCC\x33\x33\xCC\x66\x33\xCC\x99\x33\xCC\xCC\x33\xCC\xFF\x33\xFF\x00\x33\xFF\x33\x33\xFF\x66\x33\xFF\x99\x33\xFF\xCC\x33\xFF\xFF\x66\x00\x00\x66\x00\x33\x66\x00\x66\x66\x00\x99\x66\x00\xCC\x66\x00\xFF\x66\x33\x00\x66\x33\x33\x66\x33\x66\x66\x33\x99\x66\x33\xCC\x66\x33\xFF\x66\x66\x00\x66\x66\x33\x66\x66\x66\x66\x66\x99\x66\x66\xCC\x66\x66\xFF\x66\x99\x00\x66\x99\x33\x66\x99\x66\x66\x99\x99\x66\x99\xCC\x66\x99\xFF\x66\xCC\x00\x66\xCC\x33\x66\xCC\x66\x66\xCC\x99\x66\xCC\xCC\x66\xCC\xFF\x66\xFF\x00\x66\xFF\x33\x66\xFF\x66\x66\xFF\x99\x66\xFF\xCC\x66\xFF\xFF\x99\x00\x00\x99\x00\x33\x99\x00\x66\x99\x00\x99\x99\x00\xCC\x99\x00\xFF\x99\x33\x00\x99\x33\x33\x99\x33\x66\x99\x33\x99\x99\x33\xCC\x99\x33\xFF\x99\x66\x00\x99\x66\x33\x99\x66\x66\x99\x66\x99\x99\x66\xCC\x99\x66\xFF\x99\x99\x00\x99\x99\x33\x99\x99\x66\x99\x99\x99\x99\x99\xCC\x99\x99\xFF\x99\xCC\x00\x99\xCC\x33\x99\xCC\x66\x99\xCC\x99\x99\xCC\xCC\x99\xCC\xFF\x99\xFF\x00\x99\xFF\x33\x99\xFF\x66\x99\xFF\x99\x99\xFF\xCC\x99\xFF\xFF\xCC\x00\x00\xCC\x00\x33\xCC\x00\x66\xCC\x00\x99\xCC\x00\xCC\xCC\x00\xFF\xCC\x33\x00\xCC\x33\x33\xCC\x33\x66\xCC\x33\x99\xCC\x33\xCC\xCC\x33\xFF\xCC\x66\x00\xCC\x66\x33\xCC\x66\x66\xCC\x66\x99\xCC\x66\xCC\xCC\x66\xFF\xCC\x99\x00\xCC\x99\x33\xCC\x99\x66\xCC\x99\x99\xCC\x99\xCC\xCC\x99\xFF\xCC\xCC\x00\xCC\xCC\x33\xCC\xCC\x66\xCC\xCC\x99\xCC\xCC\xCC\xCC\xCC\xFF\xCC\xFF\x00\xCC\xFF\x33\xCC\xFF\x66\xCC\xFF\x99\xCC\xFF\xCC\xCC\xFF\xFF\xFF\x00\x00\xFF\x00\x33\xFF\x00\x66\xFF\x00\x99\xFF\x00\xCC\xFF\x00\xFF\xFF\x33\x00\xFF\x33\x33\xFF\x33\x66\xFF\x33\x99\xFF\x33\xCC\xFF\x33\xFF\xFF\x66\x00\xFF\x66\x33\xFF\x66\x66\xFF\x66\x99\xFF\x66\xCC\xFF\x66\xFF\xFF\x99\x00\xFF\x99\x33\xFF\x99\x66\xFF\x99\x99\xFF\x99\xCC\xFF\x99\xFF\xFF\xCC\x00\xFF\xCC\x33\xFF\xCC\x66\xFF\xCC\x99\xFF\xCC\xCC\xFF\xCC\xFF\xFF\xFF\x00\xFF\xFF\x33\xFF\xFF\x66\xFF\xFF\x99\xFF\xFF\xCC\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x10\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x08\x04\x00\xFF\x05\x04\x00\x3B");
	}



	/**
	 * Show all threads of a forum
	 *
	 * @global 	array		$_ARRAYLANG
	 * @param	integer		$intForumId: The id of the forum which should be shown
	 */
	function showForum($intForumId) {
		global $objDatabase, $_ARRAYLANG, $objCache;
		require_once ASCMS_LIBRARY_PATH . "/spamprotection/captcha.class.php";
		if ($intForumId == 0) {
			//wrong id, redirect
			header('location: index.php?section=forum');
			die();
		}

		$captcha = new Captcha();

		$this->_communityLogin();

		$intCounter = 1;
		$intForumId = intval($intForumId);
		$intThreadId = !empty($_REQUEST['threadid']) ? intval($_REQUEST['threadid']) : 0;
		$pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;

		if($_SESSION['auth']['userid'] > 0){
			$this->_objTpl->touchBlock('notificationRow');
		}else{
			$this->_objTpl->hideBlock('notificationRow');
		}

		$_REQUEST['act'] = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
		if($_REQUEST['act'] == 'delete'){
			if($this->_checkAuth($intForumId, 'delete')){
				if($this->_deleteThread($intThreadId, $intForumId)){
					$this->_objTpl->setVariable('TXT_FORUM_SUCCESS', $_ARRAYLANG['TXT_FORUM_DELETED_SUCCESSFULLY']);
				}else{
					$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_DELETE_FAILED']);
				}
			}else{
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
				return false;
			}
		}

		$arrThreads = $this->createThreadArray($intForumId, $pos);

		$subject = !empty($_REQUEST['thread_subject']) ? contrexx_stripslashes($_REQUEST['thread_subject']) : '';
		$keywords = !empty($_REQUEST['thread_keywords']) ? contrexx_stripslashes($_REQUEST['thread_keywords']) : '';
		$content = !empty($_REQUEST['thread_message']) ? contrexx_stripslashes($_REQUEST['thread_message']) : '';

		$offset = $captcha->getOffset();
		$alt	= $captcha->getAlt();
		$url 	= $captcha->getUrl();


		if($this->_arrSettings['wysiwyg_editor'] == 1) { //IF WYSIWIG enabled..
			require ASCMS_CORE_PATH.'/wysiwyg.class.php';
			global $wysiwygEditor, $FCKeditorBasePath;
			$wysiwygEditor = "FCKeditor";
			$FCKeditorBasePath = "/editor/fckeditor/";
			$strMessageInputHTML = get_wysiwyg_editor('thread_message', $content, 'forum');
		}else{ //plain textarea
			$strMessageInputHTML = '<textarea style="width: 400px; height: 150px;" rows="5" cols="10" name="thread_message">'.$content.'</textarea>';
		}

		$this->_objTpl->setGlobalVariable(array(
			'FORUM_NAME'				=>	$this->_shortenString($this->_arrTranslations[$intForumId][$this->_intLangId]['name'], $this->_maxStringLenght),
			'FORUM_TREE'				=>	$this->_createNavTree($intForumId),
			'FORUM_DROPDOWN'			=>	$this->createForumDD('forum_quickaccess', $intForumId, 'onchange="gotoForum(this);"', ''),
			'FORUM_JAVASCRIPT_GOTO'		=> 	$this->getJavascript('goto'),
			'FORUM_JAVASCRIPT_DELETE'	=> 	$this->getJavascript('deleteThread'),
			'FORUM_JAVASCRIPT_INSERT_TEXT'	=> 	$this->getJavascript('insertText'),
			'TXT_FORUM_ICON'			=>	$_ARRAYLANG['TXT_FORUM_ICON'],
			'TXT_FORUM_CREATE_THREAD'	=>	$_ARRAYLANG['TXT_FORUM_CREATE_THREAD'],
			'TXT_FORUM_NOTIFY_NEW_POSTS' =>	$_ARRAYLANG['TXT_FORUM_NOTIFY_NEW_POSTS'],
			'TXT_FORUM_UPDATE_NOTIFICATION' =>	$_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION'],
			'FORUM_NOTIFICATION_CHECKBOX_CHECKED'	=>	$this->_hasNotification($intThreadId) ? 'checked="checked"' : '',

			'FORUM_CAPTCHA_OFFSET'		=>	$offset,
			'FORUM_CAPTCHA_IMAGE_URL'	=>	$url,
			'FORUM_CAPTCHA_IMAGE_ALT'	=>	$alt,
			'FORUM_FORUM_ID'			=>	$intForumId, // the category id via GET
			'FORUM_SUBJECT'				=>	$subject,
			'FORUM_KEYWORDS'			=>	$keywords,
			'FORUM_MESSAGE_INPUT'		=>	$strMessageInputHTML,
		));

		if($_SESSION['auth']['userid'] > 0){
			$this->_objTpl->hideBlock('captcha');
		} else {
			$this->_objTpl->touchBlock('captcha');
		}

		$this->_setIcons($this->_getIcons());

		$this->_objTpl->setVariable(array(
				'TXT_THREADS_SUBJECTAUTHOR'		=>	$_ARRAYLANG['TXT_FORUM_THREADS_SUBJECTAUTHOR'],
				'TXT_THREADS_LASTTOPIC'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
				'TXT_THREADS_REPLIES'			=>	$_ARRAYLANG['TXT_FORUM_THREADS_REPLIES'],
				'TXT_THREADS_HITS'				=>	$_ARRAYLANG['TXT_FORUM_THREADS_HITS'],
				'TXT_FORUM_ADD_THREAD'			=>	$_ARRAYLANG['TXT_FORUM_ADD_THREAD'],
				'TXT_FORUM_SUBJECT'				=>	$_ARRAYLANG['TXT_FORUM_SUBJECT'],
				'TXT_FORUM_MESSAGE'				=>	$_ARRAYLANG['TXT_FORUM_MESSAGE'],
				'TXT_FORUM_RESET'				=>	$_ARRAYLANG['TXT_FORUM_RESET'],
				'TXT_FORUM_CREATE_THREAD'		=>	$_ARRAYLANG['TXT_FORUM_CREATE_THREAD'],
				'TXT_FORUM_PREVIEW'				=>	$_ARRAYLANG['TXT_FORUM_PREVIEW'],
				'TXT_FORUM_FILE_ATTACHMENT'		=>	$_ARRAYLANG['TXT_FORUM_FILE_ATTACHMENT'],
				'TXT_FORUM_COMMA_SEPARATED_KEYWORDS'	=>	$_ARRAYLANG['TXT_FORUM_COMMA_SEPARATED_KEYWORDS'],
				'TXT_FORUM_KEYWORDS'			=>	$_ARRAYLANG['TXT_FORUM_KEYWORDS'],

		));

		if(!$this->_checkAuth($intForumId, 'write')){
			$this->_objTpl->hideBlock('addThread');
			$this->_objTpl->hideBlock('addPostAnchor');
		}else{
			$this->_objTpl->touchBlock('addPostAnchor');
		}

		if (count($arrThreads) > 0) {
			if(!$this->_checkAuth($intForumId, 'read')){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
				return false;
			}
			$intCounter = 0;
			foreach ($arrThreads as $threadId => $arrValues) {
				$strUserProfileLink = ($arrValues['user_id'] > 0) ? '<a href="?section=forum&amp;cmd=userinfo&amp;id='.$arrValues['user_id'].'">'.$arrValues['user_name'].'</a>': $this->_anonymousName;
				$this->_objTpl->setVariable(array(
					'FORUM_THREADS_ROWCLASS'		=>	($intCounter++ % 2) + 1,
					'FORUM_THREADS_SYMBOL'			=>	'<img title="comment.gif" alt="comment.gif" src="'.ASCMS_MODULE_IMAGE_WEB_PATH.'/forum/comment.gif" border="0" />',
					'FORUM_THREADS_ICON'			=>	$arrValues['thread_icon'],
					'FORUM_THREADS_ID'				=>	$arrValues['thread_id'],
					'FORUM_THREADS_NAME'			=>	$arrValues['subject'],
					'FORUM_THREADS_AUTHOR'			=>	$strUserProfileLink,
					'FORUM_THREADS_LASTPOST_DATE'	=>	$arrValues['lastpost_time'],
					'FORUM_THREADS_LASTPOST_AUTHOR'	=>	$arrValues['lastpost_author'],
					'FORUM_THREADS_REPLIES'			=>	$arrValues['replies'],
					'FORUM_THREADS_HITS'			=>	$arrValues['views'],
				));

				if($this->_checkAuth($intForumId, 'delete')){
					$this->_objTpl->setVariable('FORUM_THREAD_ID', $intThreadId);
					$this->_objTpl->touchBlock('deleteThread');
				}else{
					$this->_objTpl->hideBlock('deleteThread');
				}
				$this->_objTpl->parse('forumThreads');
			}
			$this->_objTpl->setVariable(array(
				'FORUM_THREADS_PAGING'	=>	getPaging($this->_threadCount, $pos, '&amp;section=forum&amp;cmd=board&amp;id='.$intForumId, $_ARRAYLANG['TXT_FORUM_THREAD'], true, $this->_arrSettings['thread_paging']),
			));
			$this->_objTpl->hideBlock('forumNoThreads');
		} else {
			//no threads in this board, show message
			$this->_objTpl->setVariable('TXT_FORUM_NO_THREADS', $_ARRAYLANG['TXT_FORUM_NO_THREADS']);
			$this->_objTpl->parse('forumNoThreads');
			$this->_objTpl->hideBlock('forumThreads');
		}

		if(!empty($_REQUEST['create']) && $_REQUEST['create'] == $_ARRAYLANG['TXT_FORUM_CREATE_THREAD']){
			//addthread code
			if(!$this->_checkAuth($intForumId, 'write')){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
				$this->_objTpl->hideBlock('addThread');
				return false;
			}

			if ($_SESSION['auth']['userid'] < 1 && !$captcha->compare($_POST['captcha'], $_POST['offset'])) {
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_INVALID_CAPTCHA']);
				return false;
			}

			if(strlen(trim($content)) < $this->_minPostLenght){//content check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostLenght));
				return false;
			}

			if(false !== ($match = $this->_hasBadWords($content))){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
				return false;
			}
			$fileInfo = $this->_handleUpload('forum_attachment');
			if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
				return false;
			}

			$maxIdQuery = '	SELECT max( thread_id ) as max_thread_id
							FROM '.DBPREFIX.'module_forum_postings';
			if( ($objRSmaxId = $objDatabase->SelectLimit($maxIdQuery, 1)) !== false){
				$intLastThreadId = $objRSmaxId->fields['max_thread_id'] + 1;
			}else{
				die($objDatabase->ErrorMsg());
			}

			$userId = !empty($_SESSION['auth']['userid']) ? $_SESSION['auth']['userid'] : 0;
			$icon = !empty($_REQUEST['icons']) ? intval($_REQUEST['icons']) : 1;


			$insertQuery = 'INSERT INTO '.DBPREFIX.'module_forum_postings (
							id, 		category_id, 		thread_id, 			prev_post_id,
							user_id, 	time_created, 		time_edited, 		is_locked,
							is_sticky, 	views, 				icon, 				subject,
							keywords,	content,			attachment
						) VALUES (
							NULL, '.	$intForumId.', '.	$intLastThreadId.', 0,
							'.$userId.', '.time().', 		0,					0,
						    0, 			0, '.				$icon.", '".		$subject."',
						    '".$keywords."' ,'".$content."' , '".$fileInfo['name']."'
						)";
			if($objDatabase->Execute($insertQuery) !== false){
				$lastInsertId = $objDatabase->Insert_ID();
				$this->_updateNotification($intLastThreadId);
				$this->_sendNotifications($intLastThreadId, $subject, $content);
				$this->updateViewsNewItem($intForumId, $lastInsertId);
				$objCache = &new Cache();
				$objCache->deleteAllFiles();
			}
			header('Location: ?section=forum&cmd=board&id='.$intForumId);
			die();
		}
	}


	/**
	 * show thread
	 *
	 * @param integer $intThreadId
	 * @return bool
	 */
	function showThread($intThreadId)
	{
		global $objDatabase, $_ARRAYLANG, $objCache;

		$this->_communityLogin();
		$intThreadId = intval($intThreadId);

		if(!empty($_REQUEST['notification_update']) && $_REQUEST['notification_update'] == $_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION']){
			$this->_updateNotification($intThreadId);
		}

		$intCatId = !empty($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : '0';
		if($intCatId == 0){
			$intCatId = $this->_getCategoryIdFromThread($intThreadId);
		}

		if(empty($intCatId)){
			header('Location: index.php?section=forum');
			die();
		}


		if($_SESSION['auth']['userid'] > 0){
			$this->_objTpl->touchBlock('notificationRow');
		}else{
			$this->_objTpl->hideBlock('notificationRow');
		}

		$intPostId = !empty($_REQUEST['postid']) ? intval($_REQUEST['postid']) : 0;
		$intPostId = ($intPostId == 0 && !empty($_REQUEST['post_id'])) ? intval($_REQUEST['post_id']) : $intPostId;
		$this->_objTpl->setVariable('FORUM_EDIT_POST_ID', $intPostId);

		$_REQUEST['act'] = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
		if($_REQUEST['act'] == 'delete'){
			if($this->_checkAuth($intCatId, 'delete')){
				if($this->_deletePost($intCatId, $intThreadId, $_REQUEST['postid'])){
					$this->_objTpl->setVariable('TXT_FORUM_SUCCESS', $_ARRAYLANG['TXT_FORUM_DELETED_SUCCESSFULLY']);
				}else{
					$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_DELETE_FAILED']);
				}
			}else{
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
			}
		}

		require_once ASCMS_LIBRARY_PATH . "/spamprotection/captcha.class.php";
		$captcha = new Captcha();
		$offset = $captcha->getOffset();
		$alt 	= $captcha->getAlt();
		$url 	= $captcha->getUrl();

		$pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
		$this->_objTpl->setVariable(array(
			'FORUM_PAGING_POS'	=>	$pos
		));

		if(!empty($_REQUEST['preview_new'])){
			$pos = $this->_getLastPos($intPostId, $intThreadId);
		}

		if(!empty($_REQUEST['postid'])){
			if($_REQUEST['act'] == 'quote'){
				$pos = $this->_getLastPos($intPostId, $intThreadId);
			}
			if($_REQUEST['act'] == 'edit'){
				$pos = $this->_getEditPos($intPostId, $intThreadId);
			}
		}

		if(!empty($_REQUEST['l']) && $_REQUEST['l'] == 1){
			$pos = $this->_getEditPos($intPostId, $intThreadId);
		}

		$arrPosts = $this->createPostArray($intThreadId, $pos);

		if(!empty($_REQUEST['preview_edit']) && $_REQUEST['post_id'] != 0 && $_REQUEST['act'] != 'quote'){
			$intPostId = intval($intPostId);
			$pos = $this->_getEditPos($intPostId, $intThreadId);
			$arrPosts = $this->createPostArray($intThreadId, $pos);
			$arrPosts[$intPostId]['subject'] = !empty($_REQUEST['subject']) ? contrexx_strip_tags($_REQUEST['subject']) : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'];
			$arrPosts[$intPostId]['content'] = $this->BBCodeToHTML(contrexx_stripslashes($_REQUEST['message']));
		}

		$userId  = !empty($_SESSION['auth']['userid']) ? $_SESSION['auth']['userid'] : 0;
		$icon 	 = !empty($_REQUEST['icons']) ? intval($_REQUEST['icons']) : 1;


		if($_REQUEST['act'] == 'edit'){
			//submit is an edit
			$arrEditedPost = $this->_getPostingData($intPostId);
			$subject = addcslashes(htmlentities($arrEditedPost['subject'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
			$content =  $arrEditedPost['content'];
			$keywords =  addcslashes(htmlentities($arrEditedPost['keywords'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
			$attachment = $arrEditedPost['attachment'];
			$this->_objTpl->setVariable('FORUM_POST_EDIT_USERID', $arrPosts[$intPostId]['user_id']);
			if(!empty($attachment)){
				$this->_objTpl->setVariable('TXT_FORUM_DELETE_ATTACHMENT', sprintf($_ARRAYLANG['TXT_FORUM_DELETE_ATTACHMENT'], $attachment));
			}
			$this->_objTpl->touchBlock('updatePost');
			$this->_objTpl->hideBlock('createPost');
			$this->_objTpl->hideBlock('previewNewPost');
			$this->_objTpl->touchBlock('previewEditPost');
		}else{
			//new post
			if($this->_objTpl->blockExists('delAttachment')){
				$this->_objTpl->hideBlock('delAttachment');
			}
			$subject = !empty($_REQUEST['subject']) ? contrexx_strip_tags($_REQUEST['subject']) : '';
			$content = !empty($_REQUEST['message']) ? contrexx_strip_tags($_REQUEST['message']) : '';
			$keywords = !empty($_REQUEST['keywords']) ? contrexx_strip_tags($_REQUEST['keywords']) : '';
			$attachment = !empty($_REQUEST['attachment']) ? contrexx_strip_tags($_REQUEST['attachment']) : '';
			$this->_objTpl->touchBlock('createPost');
			$this->_objTpl->hideBlock('updatePost');
			$this->_objTpl->touchBlock('previewNewPost');
			$this->_objTpl->hideBlock('previewEditPost');
		}

		if($_REQUEST['act'] == 'quote'){
			$quoteContent = $this->_getPostingData($intPostId);
			$subject = 'RE: '.addcslashes(htmlentities($quoteContent['subject'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
			$content = '[quote='.$arrPosts[$intPostId]['user_name'].']'.strip_tags($quoteContent['content']).'[/quote]';
		}

		$firstPost = current($arrPosts);

		if($this->_arrSettings['wysiwyg_editor'] == 1) { //IF WYSIWIG enabled..
			require ASCMS_CORE_PATH.'/wysiwyg.class.php';
			global $wysiwygEditor, $FCKeditorBasePath;
			$wysiwygEditor = "FCKeditor";
			$FCKeditorBasePath = "/editor/fckeditor/";
			$strMessageInputHTML = get_wysiwyg_editor('message', $content, 'forum');
		}else{ //plain textarea
			$strMessageInputHTML = '<textarea style="width: 400px; height: 150px;" rows="5" cols="10" name="message">'.$content.'</textarea>';
		}
		$this->_objTpl->setGlobalVariable(array(
			'FORUM_JAVASCRIPT_GOTO'		=>	$this->getJavascript('goto'),
			'FORUM_JAVASCRIPT_DELETE'	=>	$this->getJavascript('deletePost'),
			'FORUM_JAVASCRIPT_SCROLLTO'	=>	$this->getJavascript('scrollto'),
			'FORUM_SCROLLPOS'			=>	!empty($_REQUEST['scrollpos']) ? intval($_REQUEST['scrollpos']) : '0',
			'FORUM_JAVASCRIPT_INSERT_TEXT'	=> 	$this->getJavascript('insertText'),
			'FORUM_NAME'				=>	$this->_shortenString($firstPost['subject'], $this->_maxStringLenght),
			'FORUM_TREE'				=>	$this->_createNavTree($intCatId).'<a title="'.$this->_arrTranslations[$intCatId][$this->_intLangId]['name'].'" href="?section=forum&amp;cmd=board&amp;id='.$intCatId.'">'.$this->_shortenString($this->_arrTranslations[$intCatId][$this->_intLangId]['name'], $this->_maxStringLenght).'</a> > ' ,
			'FORUM_DROPDOWN'			=>	$this->createForumDD('forum_quickaccess', $intCatId, 'onchange="gotoForum(this);"', ''),
			'TXT_FORUM_COMMA_SEPARATED_KEYWORDS'	=>	$_ARRAYLANG['TXT_FORUM_COMMA_SEPARATED_KEYWORDS'],
			'TXT_FORUM_KEYWORDS'		=>	$_ARRAYLANG['TXT_FORUM_KEYWORDS'],
			'TXT_FORUM_FILE_ATTACHMENT'	=>	$_ARRAYLANG['TXT_FORUM_FILE_ATTACHMENT'],
			'TXT_FORUM_RATING'			=>	$_ARRAYLANG['TXT_FORUM_RATING'],
			'TXT_FORUM_ADD_POST'		=>	$_ARRAYLANG['TXT_FORUM_ADD_POST'],
			'TXT_FORUM_SUBJECT'			=>	$_ARRAYLANG['TXT_FORUM_SUBJECT'],
			'TXT_FORUM_MESSAGE'			=>	$_ARRAYLANG['TXT_FORUM_MESSAGE'],
			'TXT_FORUM_RESET'			=>	$_ARRAYLANG['TXT_FORUM_RESET'],
			'TXT_FORUM_CREATE_POST'		=>	$_ARRAYLANG['TXT_FORUM_CREATE_POST'],
			'TXT_FORUM_ICON'			=>	$_ARRAYLANG['TXT_FORUM_ICON'],
			'TXT_FORUM_QUOTE'			=>	$_ARRAYLANG['TXT_FORUM_QUOTE'],
			'TXT_FORUM_EDIT'			=>	$_ARRAYLANG['TXT_FORUM_EDIT'],
			'TXT_FORUM_DELETE'			=>	$_ARRAYLANG['TXT_FORUM_DELETE'],
			'TXT_FORUM_PREVIEW'			=>	$_ARRAYLANG['TXT_FORUM_PREVIEW'],
			'TXT_FORUM_UPDATE_POST'		=>	$_ARRAYLANG['TXT_FORUM_UPDATE_POST'],
			'TXT_FORUM_NOTIFY_NEW_POSTS' =>	$_ARRAYLANG['TXT_FORUM_NOTIFY_NEW_POSTS'],
			'TXT_FORUM_QUICKACCESS' 	=>	$_ARRAYLANG['TXT_FORUM_QUICKACCESS'],
			'TXT_FORUM_UPDATE_NOTIFICATION' =>	$_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION'],
			'FORUM_NOTIFICATION_CHECKBOX_CHECKED'	=>	$this->_hasNotification($intThreadId) ? 'checked="checked"' : '',
			'FORUM_SUBJECT'				=>	stripslashes($subject),
			'FORUM_KEYWORDS'			=>	stripslashes($keywords),
			'FORUM_ATTACHMENT_OLDNAME'	=>	$attachment,
			'FORUM_MESSAGE_INPUT'		=>	$strMessageInputHTML,
			'FORUM_CAPTCHA_OFFSET'		=>	$offset,
			'FORUM_CAPTCHA_IMAGE_URL'	=>	$url,
			'FORUM_CAPTCHA_IMAGE_ALT'	=>	$alt,
			'FORUM_THREAD_ID'			=>	$intThreadId,
			'FORUM_CATEGORY_ID'			=>	$intCatId,
			'FORUM_POSTS_PAGING'		=>	getPaging($this->_postCount, $pos, '&amp;section=forum&amp;cmd=thread&amp;id='.$intThreadId, $_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'], true, $this->_arrSettings['posting_paging']),
		));

		if($_SESSION['auth']['userid'] > 0){
			$this->_objTpl->hideBlock('captcha');
		} else {
			$this->_objTpl->touchBlock('captcha');
		}

		$this->_setIcons($this->_getIcons());

		if(!$this->_checkAuth($intCatId, 'read')){
			$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
			return false;
		}

		$intCounter	= 0;
		foreach ($arrPosts as $postId => $arrValues) {
			$strRating = '<span id="forum_current_rating_'.$postId.'" class="rating_%s">%s</span>';
			if($arrValues['rating'] == 0){
				$class = 'none';
			} elseif ($arrValues['rating'] > 0){
				$class = 'pos';
			} else {
				$class = 'neg';
			}
			$strRating = sprintf($strRating, $class, $arrValues['rating']);
			$strUserProfileLink = ($arrValues['user_id'] > 0) ? '<a title="'.$arrValues['user_name'].'" href="?section=forum&amp;cmd=userinfo&amp;id='.$arrValues['user_id'].'">'.$arrValues['user_name'].'</a>' : $this->_anonymousName;

			$arrAttachment = $this->_getAttachment($arrValues['attachment']);
			$this->_objTpl->setGlobalVariable(array(
				'FORUM_POST_ROWCLASS'			=>	($intCounter++ % 2) + 1,
			));
			$this->_objTpl->setVariable(array(
				'FORUM_POST_DATE'				=>	$arrValues['time_created'],
				'FORUM_POST_LAST_EDITED'		=>	($arrValues['time_edited'] != date(ASCMS_DATE_FORMAT, 0)) ? $_ARRAYLANG['TXT_FORUM_LAST_EDITED'].$arrValues['time_edited'] : '',
				'FORUM_USER_ID'					=>	$arrValues['user_id'],
				'FORUM_USER_NAME'				=>	$strUserProfileLink,
				'FORUM_USER_IMAGE'				=>	!empty($arrValues['user_image']) ? '<img border="0" width="60" height="60" src="'.$arrValues['user_image'].'" title="'.$arrValues['user_name'].'\'s avatar" alt="'.$arrValues['user_name'].'\'s avatar" />' : '',
				'FORUM_USER_GROUP'				=>	'',
				'FORUM_USER_RANK'				=>	'',

				'FORUM_USER_REGISTERED_SINCE'	=>	'',
				'FORUM_USER_POSTING_COUNT'		=>	'',
				'FORUM_USER_CONTACTS'			=>	'',

				'FORUM_POST_NUMBER'				=>	'#'.$arrValues['post_number'],
				'FORUM_POST_ICON'				=>	$arrValues['post_icon'],
				'FORUM_POST_SUBJECT'			=>	$arrValues['subject'],
				'FORUM_POST_MESSAGE'			=>	addcslashes($arrValues['content'], '\\'),
				'FORUM_POST_RATING'				=>	$strRating,
				'FORUM_POST_ATTACHMENT_LINK'	=>	$arrAttachment['webpath'],
				'FORUM_POST_ATTACHMENT_FILENAME' =>	$arrAttachment['name'],
				'FORUM_POST_ATTACHMENT_ICON'	=>	$arrAttachment['icon'],
				'FORUM_POST_ATTACHMENT_FILESIZE' =>	$arrAttachment['size'],
			));

			$this->_objTpl->setVariable(array(
				'FORUM_POST_ID' 		=> $postId,
				'FORUM_RATING_POST_ID' 	=> $postId
				));
			if(($this->_checkAuth($intCatId, 'edit') || $arrValues['user_id'] == $_SESSION['auth']['userid']) && $_SESSION['auth']['userid'] != $this->_anonymousGroupId){
				$this->_objTpl->touchBlock('postEdit');
			}else{
				$this->_objTpl->hideBlock('postEdit');
			}

			if($this->_checkAuth($intCatId, 'write')){
				$this->_objTpl->touchBlock('postQuote');
			}else{
				$this->_objTpl->hideBlock('postQuote');
			}

			if($this->_checkAuth($intCatId, 'delete') && $arrValues['post_number'] != 1){
				$this->_objTpl->setVariable(array(
					'FORUM_POST_ID' 	=> $postId,
				));
				$this->_objTpl->touchBlock('postDelete');
			}else{
				$this->_objTpl->hideBlock('postDelete');
			}

			if($this->_objTpl->blockExists('rating')){
				if($_SESSION['auth']['userid'] > 0 && !$this->_hasRated($postId)){
					$this->_objTpl->parse('rating');
				}else{
					$this->_objTpl->hideBlock('rating');
				}
			}

			if($this->_objTpl->blockExists('attachment')){
				if(!empty($arrValues['attachment'])){
					$this->_objTpl->parse('attachment');
				} else {
					$this->_objTpl->hideBlock('attachment');
				}
			}

			$this->_objTpl->parse('forumPosts');
		}

		if(!$this->_checkAuth($intCatId, 'write')){
			$this->_objTpl->hideBlock('addPost');
			$this->_objTpl->hideBlock('addPostAnchor');
		}else{
			$this->_objTpl->touchBlock('addPostAnchor');
		}

		//addpost code
		if(!empty($_REQUEST['create']) && $_REQUEST['create'] == $_ARRAYLANG['TXT_FORUM_CREATE_POST']){
			if(!$this->_checkAuth($intCatId, 'write')){//auth check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
				$this->_objTpl->hideBlock('addPost');
				return false;
			}
			if($_SESSION['auth']['userid'] < 1 && !$captcha->compare($_POST['captcha'], $_POST['offset'])) {//captcha check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_INVALID_CAPTCHA']);
				return false;
			}
			if(strlen(trim($content)) < $this->_minPostLenght){//content check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostLenght));
				return false;
			}

			if(false !== ($match = $this->_hasBadWords($content))){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
				return false;
			}
			$fileInfo = $this->_handleUpload('forum_attachment');
			if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
				return false;
			}

			$lastPostIdQuery = '	SELECT max( id ) as last_post_id
									FROM '.DBPREFIX.'module_forum_postings
									WHERE category_id = '.$intCatId.'
									AND	  thread_id = '.$intThreadId;
			if( ($objRSmaxId = $objDatabase->SelectLimit($lastPostIdQuery, 1)) !== false){
				$intPrevPostId = $objRSmaxId->fields['last_post_id'];
			}else{
				die('Database error: '.$objDatabase->ErrorMsg());
			}

			$insertQuery = 'INSERT INTO '.DBPREFIX.'module_forum_postings (
							id, 			category_id,	thread_id,			prev_post_id,
							user_id, 		time_created,	time_edited, 		is_locked,
							is_sticky, 		rating, 		views, 				icon,
							keywords,		subject,		content, 			attachment
						) VALUES (
							NULL, '.		$intCatId.', '.	$intThreadId.', '.$intPrevPostId.',
							'.$userId.', '.	time().', 		0, 					0,
						    0, 			  	0,		0, '.			$icon.",
						    '$keywords' ,'".$subject."',	'".$content."', '".$fileInfo['name']."'
						)";

			if($objDatabase->Execute($insertQuery) !== false){
				$lastInsertId = $objDatabase->Insert_ID();
				$this->updateViewsNewItem($intCatId, $lastInsertId, true);
				$this->_updateNotification($intThreadId);
				$this->_sendNotifications($intThreadId, $subject, $content);
				$objCache = &new Cache();
				$objCache->deleteAllFiles();
			}
			header('Location: index.php?section=forum&cmd=thread&id='.$intThreadId.'&pos='.$this->_getLastPos($postId, $intThreadId));
			die();
		}

		if(!empty($_REQUEST['preview_new'])){
			$content = $this->BBCodeToHTML(stripslashes($content));
			if(false !== ($match = $this->_hasBadWords($content))){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
				return false;
			}
			if(strlen(trim($content)) < $this->_minPostLenght){//content check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostLenght));
				return false;
			}
			$this->_objTpl->setVariable(array(
				'FORUM_POST_ROWCLASS'			=>	($intCounter++ % 2) + 1,
				'FORUM_POST_DATE'				=>	date(ASCMS_DATE_FORMAT, time()),
				'FORUM_USER_ID'					=>	$userId,
				'FORUM_USER_NAME'				=>	!empty($_SESSION['auth']['username']) ? '<a href="index.php?section=forum&amp;cmd=userinfo&amp;id='.$userId.'" title="'.$_SESSION['auth']['username'].'">'.$_SESSION['auth']['username'].'</a>' : $this->_anonymousName,
				'FORUM_USER_IMAGE'				=>	!empty($arrValues['user_image']) ? '<img border="0" width="60" height="60" src="'.$arrValues['user_image'].'" title="'.$arrValues['user_name'].'\'s avatar" alt="'.$arrValues['user_name'].'\'s avatar" />' : '',
				'FORUM_USER_GROUP'				=>	'',
				'FORUM_USER_RANK'				=>	'',

				'FORUM_USER_REGISTERED_SINCE'	=>	'',
				'FORUM_USER_POSTING_COUNT'		=>	'',
				'FORUM_USER_CONTACTS'			=>	'',

				'FORUM_POST_NUMBER'				=>	'#'.($this->_postCount+1),
				'FORUM_POST_ICON'				=>	$this->getThreadIcon($icon),
				'FORUM_POST_SUBJECT'			=>	stripslashes($subject),
				'FORUM_POST_MESSAGE'			=>	$content,
				'FORUM_POST_RATING'				=>	'0',
			));
			$this->_objTpl->touchBlock('createPost');
			$this->_objTpl->hideBlock('updatePost');
			$this->_objTpl->hideBlock('attachment');
			$this->_objTpl->hideBlock('postEdit');
			$this->_objTpl->hideBlock('postQuote');
			$this->_objTpl->touchBlock('previewNewPost');
			$this->_objTpl->hideBlock('previewEditPost');
			$this->_objTpl->parse('forumPosts');
		}


		if(!empty($_REQUEST['update']) && $_REQUEST['update'] == $_ARRAYLANG['TXT_FORUM_UPDATE_POST']){
			if(strlen(trim($content)) < $this->_minPostLenght){//content size check
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostLenght));
				return false;
			}
			if((!$this->_checkAuth($intCatId, 'edit') && $arrValues['user_id'] != $_SESSION['auth']['userid']) || ($_SESSION['auth']['userid'] == $this->_anonymousGroupId && !$this->_checkAuth($intCatId, 'edit'))){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
				$this->_objTpl->hideBlock('postEdit');
				return false;
			}
			if ($_SESSION['auth']['userid'] == 0 && !$captcha->compare($_POST['captcha'], $_POST['offset'])) {
				$this->_objTpl->touchBlock('updatePost');
				$this->_objTpl->hideBlock('createPost');

				$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_INVALID_CAPTCHA']);
				return false;
			}
			if(false !== ($match = $this->_hasBadWords($content))){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf($_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
				return false;
			}
			$fileInfo = $this->_handleUpload('forum_attachment');
			if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
				return false;
			}

			if(empty($_POST['forum_delete_attachment']) && empty($fileInfo['name']) && !empty($_REQUEST['forum_attachment_oldname'])){
				$fileInfo['name'] = contrexx_addslashes($_REQUEST['forum_attachment_oldname']);
			}elseif( (!empty($_POST['forum_delete_attachment']) && $_POST['forum_delete_attachment'] == 1) || (!empty($_REQUEST['forum_attachment_oldname']) && $fileInfo['name'] != $_REQUEST['forum_attachment_oldname'])){
				unlink(ASCMS_FORUM_UPLOAD_PATH.'/'.str_replace(array('./', '.\\'), '', $_REQUEST['forum_attachment_oldname']));
			}

			$updateQuery = 'UPDATE '.DBPREFIX.'module_forum_postings SET
							time_edited = '.mktime().',
							icon = '.$icon.',
							subject = \''.$subject.'\',
							keywords = \''.$keywords.'\',
							content = \''.$content.'\',
							attachment = \''.$fileInfo['name'].'\'
							WHERE id = '.$intPostId;

			if($objDatabase->Execute($updateQuery) !== false){
				$this->updateViews($intThreadId, $intPostId);
				$objCache = &new Cache();
				$objCache->deleteAllFiles();
			}

			header('Location: index.php?section=forum&cmd=thread&id='.$intThreadId.'&pos='.$this->_getLastPos($postId, $intThreadId));
			die();
		}

    	if(!empty($_REQUEST['preview_edit'])){
			$this->_objTpl->touchBlock('updatePost');
			$this->_objTpl->hideBlock('createPost');
			$this->_objTpl->hideBlock('previewNewPost');
			$this->_objTpl->touchBlock('previewEditPost');
		}

		$this->updateViews($intThreadId, $intPostId);
		return true;
	}


	function _hasNotification($intThreadId){
		global $objDatabase;
		if($_SESSION['auth']['userid'] < 1){
			return false;
		}
		$query = '	SELECT 1 FROM `'.DBPREFIX.'module_forum_notification`
						WHERE `thread_id` = '.$intThreadId.'
						AND `user_id` = '.$_SESSION['auth']['userid'];
		if(($objRS = $objDatabase->SelectLimit($query, 1)) !== false){
			if($objRS->RecordCount() > 0){
				return true;
			}else{
				return false;
			}
		}else{
			die('Database error: '.$objDatabase->ErrorMsg());
		}
	}

	/**
	 * update the notifications table
	 *
	 * @param integer $intThreadId
	 * @return void
	 */
	function _updateNotification($intThreadId){
		global $objDatabase;
		if(!empty($_REQUEST['notification']) && $_REQUEST['notification'] == 'notify'){
			$query = '	SELECT 1 FROM `'.DBPREFIX.'module_forum_notification`
						WHERE `thread_id` = '.$intThreadId.'
						AND `category_id` = 0
						AND `user_id` = '.$_SESSION['auth']['userid'];
			if(($objRS=$objDatabase->SelectLimit($query, 1)) !== false){
				if($objRS->RecordCount() > 0){
					$query = '	UPDATE `'.DBPREFIX.'module_forum_notification`
							  	SET `thread_id` = '.$intThreadId.', `user_id` = '.$_SESSION['auth']['userid'].', `is_notified` = \'0\'
							  	WHERE `thread_id` = '.$intThreadId.'
							  	AND `user_id` = '.$_SESSION['auth']['userid'];
				}else{
					$query = '	INSERT INTO `'.DBPREFIX.'module_forum_notification`
							  	SET `thread_id` = '.$intThreadId.',
							  		`user_id` = '.$_SESSION['auth']['userid'].',
							  		`is_notified` = \'0\'';
				}

			}
		}else{//$_REQUEST['notification'] empty/wrong, remove notification
			$query = '	DELETE FROM `'.DBPREFIX.'module_forum_notification`
					  	WHERE `thread_id` = '.$intThreadId.'
					  	AND `category_id` = 0
					  	AND `user_id` = '.$_SESSION['auth']['userid'];
		}

		if($objDatabase->Execute($query) === false){
			die('Database error: '.$objDatabase->ErrorMsg());
		}
	}


	/**
	 * send email notifications
	 *
	 * @param integer $intThreadId
	 * @param string $strSubject subject of the last message in the thread
	 * @param string $strContent content of the last message in the thread
	 * @return void
	 */
	function _sendNotifications($intThreadId, $strSubject, $strContent){
		global $objDatabase, $_CONFIG;
		require_once(ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php');

		$arrTempSubcribers = array();
		$arrSubscribers = array();

		$intCategoryId = $this->_getCategoryIdFromThread($intThreadId);

		$mail =& new PHPMailer();
		$query = '	SELECT `subject`, `user_id` FROM `'.DBPREFIX.'module_forum_postings`
					WHERE `thread_id` = '.$intThreadId.'
					AND `prev_post_id` = 0';

		if(($objRS = $objDatabase->SelectLimit($query, 1)) !== false){
			$strFirstPostSubject = $objRS->fields['subject'];
			$strFirstPostAuthor = $this->_getUserName($objRS->fields['user_id']);
		}else{
			die('Database error: '.$objDatabase->ErrorMsg());
		}

		//fetch thread subscribers
		$query = '	SELECT `users`.`username`, `users`.`email`, `users`.`id`
					FROM `'.DBPREFIX.'access_users` AS `users`
					INNER JOIN `'.DBPREFIX.'module_forum_notification` AS `notification` ON `users`.`id` = `notification`.`user_id`
					WHERE `notification`.`thread_id` = '.$intThreadId.'
					AND `notification`.`category_id` = 0';
		if(($objRS = $objDatabase->Execute($query)) !== false){
			while(!$objRS->EOF){
				$arrTempSubcribers[] = $objRS->fields;
				$objRS->MoveNext();
			}
		}

		//fetch category subscribers
		$query = '	SELECT `users`.`username`, `users`.`email`, `users`.`id`
					FROM `'.DBPREFIX.'access_users` AS `users`
					INNER JOIN `'.DBPREFIX.'module_forum_notification` AS `notification` ON `users`.`id` = `notification`.`user_id`
					WHERE `notification`.`category_id` = '.$intCategoryId.'
					AND `notification`.`thread_id` = 0';
		if(($objRS = $objDatabase->Execute($query)) !== false){
			while(!$objRS->EOF){
				$arrTempSubcribers[] = $objRS->fields;
				$objRS->MoveNext();
			}
		}

		foreach ($arrTempSubcribers as $entry) {
			if(!in_array($entry, $arrSubscribers)){
				$arrSubscribers[] = $entry;
			}
		}

		if(!empty($arrSubscribers)){
			$mail->IsHTML(false);
			$mail->From 	= $this->_arrSettings['notification_from_email'];
			$mail->FromName = $this->_arrSettings['notification_from_name'];
			$strThreadURL = 'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=forum&cmd=thread&id='.$intThreadId;
			$arrSearch  	= array('[[FORUM_THREAD_SUBJECT]]', '[[FORUM_THREAD_STARTER]]', '[[FORUM_LATEST_SUBJECT]]',	'[[FORUM_LATEST_MESSAGE]]',	'[[FORUM_THREAD_URL]]');
			$arrReplace 	= array($strFirstPostSubject, 		$strFirstPostAuthor, 		$strSubject,				$strContent, 				$strThreadURL);

			$_strMailTemplate = html_entity_decode(str_replace($arrSearch, $arrReplace, $this->stripBBtags($this->_arrSettings['notification_template'])));
			$_strMailSubject  = html_entity_decode(str_replace($arrSearch, $arrReplace, $this->stripBBtags($this->_arrSettings['notification_subject'])));

			foreach ($arrSubscribers as $arrSubscriber) {
				if($arrSubscriber['id'] == $_SESSION['auth']['userid']){//creator of the new post/thread doesn't want a notification
					continue;
				}
				$mail->ClearAddresses();
				$strUsername = htmlentities($arrSubscriber['username'], ENT_QUOTES, CONTREXX_CHARSET);
				$strMailTemplate = str_replace('[[FORUM_USERNAME]]', $strUsername, $_strMailTemplate);
				$strMailSubject  = str_replace('[[FORUM_USERNAME]]', $strUsername, $_strMailSubject);

				$mail->AddAddress($arrSubscriber['email']);
				$mail->Subject = stripslashes(contrexx_strip_tags($strMailSubject));
				$mail->Body    = stripslashes(contrexx_strip_tags($strMailTemplate));
				$mail->Send();
			}
		}
	}


	/**
	 * parse the icons into the current template
	 *
	 * @param array $arrIcons array containing the icons (see $this->_getIcons())
	 * @param string $strBlockName name of the block to parse
	 */

	function _setIcons($arrIcons, $strBlockName = 'icons')
	{
		$iconPath = ASCMS_MODULE_IMAGE_WEB_PATH.'/forum/thread/';
		$this->_objTpl->setVariable('FORUM_ICON_CHECKED', 'checked="checked"');
		foreach ($arrIcons as $index => $image) {
			$this->_objTpl->setVariable(array(
				'FORUM_ICON_VALUE' 	=> $index,
				'FORUM_ICON_SRC'	=> $iconPath.$image,
				'FORUM_ICON_ALT'	=> $iconPath.$image,
				'FORUM_ICON_TITLE'	=> $iconPath.$image,
			));
			$this->_objTpl->parse($strBlockName);
		}
	}

	/**
	 * read icons from filesystem
	 *
	 * @return array $arrDir contains images
	 */
	function _getIcons()
	{
		$iconDir = dir(ASCMS_MODULE_IMAGE_PATH.'/forum/thread');
		while (false !== ($entry = $iconDir->read())) {
			if(($index = intval($entry)) > 0 && substr($entry, -4) == '.gif'){
				$arrDir[$index] = $entry;
			}
		}
		return $arrDir;
	}



	/**
	 * show category
	 *
	 * @param integer $intCatId
	 * @return void
	 */
	function showCategory($intCatId)
	{
		global $objDatabase, $_ARRAYLANG;

		$this->_communityLogin();

		$intCatId = intval($intCatId);
		$pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;

		$this->_objTpl->setVariable(array(
			'FORUM_NAME'			=>	$this->_shortenString($this->_arrTranslations[$intCatId][$this->_intLangId]['name'], $this->_maxStringLenght),
			'FORUM_TREE'			=>	$this->_createNavTree($intCatId),
			'FORUM_DROPDOWN'		=>	$this->createForumDD('forum_quickaccess', $intCatId, 'onchange="gotoForum(this);"', ''),
			'FORUM_JAVASCRIPT'		=>	$this->getJavascript(),
			'FORUM_JAVASCRIPT_GOTO'	=> 	$this->getJavascript('goto'),
		));

		if ($intCatId != 0) {
			$arrForums = $this->createForumArray($this->_intLangId, $intCatId, 1);
			if (count($arrForums) > 0) {
				$this->_objTpl->setGlobalVariable(array(
					'TXT_FORUM'				=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
					'TXT_LASTPOST'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
					'TXT_THREADS'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'],
					'TXT_POSTINGS'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'],
					'TXT_FORUM_QUICKACCESS' =>	$_ARRAYLANG['TXT_FORUM_QUICKACCESS'],
				));
				$intCounter=0;
				foreach ($arrForums as $intKey	=> $arrValues) {
					if ($arrValues['status'] == 1) {
						$this->_objTpl->setVariable(array(
							'FORUM_SUBCATEGORY_ROWCLASS'		=>	($intCounter++ % 2) + 1,
							'FORUM_SUBCATEGORY_SPACER'			=>	(intval($arrValues['level'])-1)*25,
							'FORUM_SUBCATEGORY_ICON'			=>	'<img src="images/modules/forum/comment.gif" alt="comment.gif" border="0" />',
							'FORUM_SUBCATEGORY_ID'				=>	$arrValues['id'],
							'FORUM_SUBCATEGORY_NAME'			=>	$arrValues['name'],
							'FORUM_SUBCATEGORY_DESC'			=>	$arrValues['description'],
							'FORUM_SUBCATEGORY_LASTPOST_ID'		=>	$arrValues['last_post_id'],
							'FORUM_SUBCATEGORY_LASTPOST_TITLE'	=>	$arrValues['last_post_str'],
							'FORUM_SUBCATEGORY_LASTPOST_DATE'	=>	$arrValues['last_post_date'],
							'FORUM_SUBCATEGORY_THREADS'			=>	$arrValues['thread_count'],
							'FORUM_SUBCATEGORY_POSTINGS'		=>	$arrValues['post_count'],
						));

						$this->_objTpl->parse('forumSubCategory');
					}
				}
				$this->_objTpl->setVariable(array(
					'FORUM_THREADS_PAGING'			=>	getPaging($this->_threadCount, $pos, '&section=forum&amp;cmd=board&amp;id='.$intCatId, $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'], true, $this->_arrSettings['thread_paging']),
				));
			} else {
				$this->_objTpl->setVariable('TXT_THREADS_NONE', $_ARRAYLANG['TXT_FORUM_THREADS_NONE']);
			}
		} else {
			header('location: index.php?section=forum');
			die();
		}

	}


	/**
	 * Show an overview of all available board in the current language
	 *
	 * @global	array		$_ARRAYLANG
	 */
	function showForumOverview() {
		global $_ARRAYLANG;
		$this->_communityLogin();
		$strJavascriptToggleCode = '<script type="text/javascript" language="javascript">//<![CDATA['."\n";
		$arrForums = $this->createForumArray($this->_intLangId);

		foreach ($arrForums as $id => $forum) {
			if($forum['parent_id'] == 0 && $forum['status']){
				$strJavascriptToggleCode .= "toggleCategory('$id');\n";
			}
		}

		$strJavascriptToggleCode .= '//]]></script>';

		$this->_objTpl->setVariable(array(
			'FORUM_JAVASCRIPT' 				=> $this->getJavascript(),
			'FORUM_JAVASCRIPT_TOGGLE_CAT'	=> $strJavascriptToggleCode,
		));

		if (count($arrForums) > 0) {

			$this->_showLatestEntries($this->_getLatestEntries());

			$boolIsFirst	= true;

			$this->_objTpl->setGlobalVariable(array(
				'TXT_FORUM'				=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
				'TXT_LASTPOST'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
				'TXT_THREADS'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'],
				'TXT_POSTINGS'			=>	$_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'],
				'FORUM_DROPDOWN'		=>	$this->createForumDD('forum_quickaccess', 0, 'onchange="gotoForum(this);"', ''),
				'FORUM_JAVASCRIPT_GOTO'	=> 	$this->getJavascript('goto'),
			));
			$intCounter 	= 0;
			foreach ($arrForums as $intKey	=> $arrValues) {
				if ($arrValues['status'] == 1) {
					if ($arrValues['level'] == 0) {

						if (!$boolIsFirst) { //the first time we have to intercept the parsing for correct showing of the board-list
							$this->_objTpl->parse('forumMainCategory');
						} else {
							$boolIsFirst = false;
						}

						$this->_objTpl->setVariable(array(
							'FORUM_MAINCATEGORY_ID'			=>	$arrValues['id'],
							'FORUM_MAINCATEGORY_NAME'		=>	'<span onclick="toggleCategory(\''.$arrValues['id'].'\')">'.$arrValues['name'].'</span>',
							'FORUM_MAINCATEGORY_NAME_TITLE'	=>	$arrValues['name'],
							'FORUM_MAINCATEGORY_DESC'		=>	$arrValues['description'],
						));
						$intCounter 	= 0;
					} else {
						$this->_objTpl->setVariable(array(
							'FORUM_SUBCATEGORY_ROWCLASS'		=>	($intCounter++ % 2) + 1,
							'FORUM_SUBCATEGORY_SPACER'			=>	(intval($arrValues['level'])-1)*25,
							'FORUM_SUBCATEGORY_ICON'			=>	'<img src="images/modules/forum/comment.gif" alt="comment.gif" border="0" />',
							'FORUM_SUBCATEGORY_ID'				=>	$arrValues['id'],
							'FORUM_SUBCATEGORY_NAME'			=>	$arrValues['name'],
							'FORUM_SUBCATEGORY_DESC'			=>	$arrValues['description'],
							'FORUM_SUBCATEGORY_LASTPOST_ID'		=>	$arrValues['last_post_id'],
							'FORUM_SUBCATEGORY_LASTPOST_TITLE'	=>	$arrValues['last_post_str'],
							'FORUM_SUBCATEGORY_LASTPOST_DATE'	=>	$arrValues['last_post_date'],
							'FORUM_SUBCATEGORY_THREADS'			=>	$arrValues['thread_count'],
							'FORUM_SUBCATEGORY_POSTINGS'		=>	$arrValues['post_count']

						));

						$this->_objTpl->parse('forumSubCategory');
					}
				}
			}
		} else {
			//no forums in database
		}
	}

	/**
	 * show the user profile - adapted from the community module
	 *
	 * @param integer $userId as in `access_users`
	 * @return void
	 */
	function showProfile($userId)
	{
		global $objDatabase;
		$this->_communityLogin();
		$userId = intval($userId);
		$objResult = $objDatabase->SelectLimit("SELECT email, firstname, lastname, street, zip, phone, mobile, residence, profession, interests, webpage, company FROM ".DBPREFIX."access_users WHERE id=".$userId);
		if ($objResult !== false) {
			$this->_objTpl->setVariable(array(
				'COMMUNITY_FIRSTNAME'	=> $objResult->fields['firstname'],
				'COMMUNITY_LASTNAME'	=> $objResult->fields['lastname'],
				'COMMUNITY_STREET'		=> $objResult->fields['street'],
				'COMMUNITY_ZIP'			=> $objResult->fields['zip'],
				'COMMUNITY_RESIDENCE'	=> $objResult->fields['residence'],
				'COMMUNITY_PROFESSION'	=> $objResult->fields['profession'],
				'COMMUNITY_INTERESTS'	=> $objResult->fields['interests'],
				'COMMUNITY_WEBPAGE'		=> preg_replace('#(http://)?(www\.)?([a-zA-Z][a-zA-Z0-9-/]+\.[a-zA-Z][a-zA-Z0-9-/&\#\+=\?\.;%]+)#i', '<a href="http://$2$3"> $2$3 </a>' , $objResult->fields['webpage']),
				'COMMUNITY_EMAIL'		=> $objResult->fields['email'],
				'COMMUNITY_COMPANY'		=> $objResult->fields['company'],
				'COMMUNITY_PHONE'		=> $objResult->fields['phone'],
				'COMMUNITY_MOBILE'		=> $objResult->fields['mobile'],
			));
		}else{
			die('DB error: '.$objDatabase->ErrorMsg());
		}
		$this->_objTpl->setVariable("FORUM_REFERER", $_SERVER['HTTP_REFERER']);
	}

	/**
	 * show and update notifications
	 *
	 */
	function showNotifications()
	{
		global $objDatabase, $_ARRAYLANG;

		$this->_communityLogin();

		$this->_objTpl->setVariable(array(
			'TXT_FORUM_THREAD_NOTIFICATION' 	=> $_ARRAYLANG['TXT_FORUM_THREAD_NOTIFICATION'],
			'TXT_SELECT_ALL' 					=> $_ARRAYLANG['TXT_SELECT_ALL'],
			'TXT_DESELECT_ALL'				 	=> $_ARRAYLANG['TXT_DESELECT_ALL'],
			'TXT_FORUM_NOTIFICATION_HELPTEXT'	=> $_ARRAYLANG['TXT_FORUM_NOTIFICATION_HELPTEXT'],
			'TXT_FORUM_NOTIFICATION_SUBMIT'		=> $_ARRAYLANG['TXT_FORUM_NOTIFICATION_SUBMIT'],
			'TXT_FORUM_NOTIFICATION_HELPTEXT'	=> $_ARRAYLANG['TXT_FORUM_NOTIFICATION_HELPTEXT'],
			'TXT_FORUM_NOTIFICATION_SUBMIT'		=> $_ARRAYLANG['TXT_FORUM_NOTIFICATION_SUBMIT'],
			'TXT_FORUM_UNSUBSCRIBED_THREADS'	=> $_ARRAYLANG['TXT_FORUM_UNSUBSCRIBED_THREADS'],
			'TXT_FORUM_SUBSCRIBED_THREADS'		=> $_ARRAYLANG['TXT_FORUM_SUBSCRIBED_THREADS'],

		));


		if(empty($_SESSION['auth']) || $_SESSION['auth']['userid'] < 1){
			$this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_MUST_BE_AUTHENTICATED']);
			$this->_objTpl->hideBlock('notification');
			return false;
		}

		$this->_objTpl->setVariable('FORUM_JAVASCRIPT_NOTIFICATION', $this->getJavascript('notification'));

		if(isset($_REQUEST['forumNotificationSubmit'])){//drop and update notifications
			$query = "	DELETE FROM `".DBPREFIX."module_forum_notification`
						WHERE `user_id` = ".$_SESSION['auth']['userid']."
						AND thread_id = 0";

			if($objDatabase->Execute($query) === false){
				$this->_objTpl->setVariable('TXT_FORUM_ERROR', 'Database error: '.$objDatabase->ErrorMsg());
				$this->_objTpl->hideBlock('notification');
				return false;
			}

			foreach ($_REQUEST['subscribed'] as $intCategoryId) {
				$intCategoryId = intval($intCategoryId);
				if($intCategoryId > 0){
					$query = "	INSERT INTO `".DBPREFIX."module_forum_notification`
								VALUES ( ".$intCategoryId.", 0, ".$_SESSION['auth']['userid'].", '0')";
					if($objDatabase->Execute($query) === false){
						$this->_objTpl->setVariable('TXT_FORUM_ERROR', 'Database error: '.$objDatabase->ErrorMsg());
						$this->_objTpl->hideBlock('notification');
						return false;
					}
				}
			}
			$this->_objTpl->setVariable('TXT_FORUM_SUCCESS', $_ARRAYLANG['TXT_FORUM_NOTIFICATION_UPDATED']);
		}

		$arrUnsubscribedThreads = $arrForums = $this->createForumArray($this->_intLangId);

		$strOptionsUnsubscribed = $strOptionsSubscribed = '';

		$query = "	SELECT `n`.`category_id`, `l`.`name` , `c`.`status`
					FROM `".DBPREFIX."module_forum_notification` AS `n`
					LEFT JOIN ".DBPREFIX."module_forum_categories_lang AS `l` USING ( category_id )
					LEFT JOIN ".DBPREFIX."module_forum_categories AS `c` ON ( `c`.`id` = `n`.`category_id` )
					WHERE `n`.`user_id` = ".$_SESSION['auth']['userid']."
					AND `n`.`thread_id` = 0
					AND `l`.`lang_id` = ".$this->_intLangId."
					AND `c`.`status` = '1'
					ORDER BY `c`.`id` ASC";

		if(($objRS = $objDatabase->Execute($query)) === false){
			die('DB error: '.$objDatabase->ErrorMsg());
		}

		while(!$objRS->EOF){
			$arrSubscribedThreads[$objRS->fields['category_id']] = $objRS->fields;
			unset($arrUnsubscribedThreads[$objRS->fields['category_id']]);
			$objRS->MoveNext();
		}

		if(!empty($arrSubscribedThreads)){
			foreach ($arrSubscribedThreads as $intCatID => $arrThread){
				$strOptionsSubscribed .= '<option value="'.$intCatID.'">'.$arrThread['name'].'</option>';
			}
		}

		if(!empty($arrUnsubscribedThreads)){
			foreach ($arrUnsubscribedThreads as $intCatID => $arrThread){
				$strOptionsUnsubscribed .= '<option value="'.$intCatID.'">'.(str_repeat('&nbsp;', ($arrForums[$intCatID]['level']*2))).$arrThread['name'].'</option>';
			}
		}

		$this->_objTpl->setVariable(array(
			'FORUM_NOTIFICATION_UNSUBSCRIBED'	=>	$strOptionsUnsubscribed,
			'FORUM_NOTIFICATION_SUBSCRIBED'		=>	$strOptionsSubscribed,
		));
	}


	/**
	 * Returns needed javascripts for the forum-module
	 *
	 * @param 	string 		$type
	 * @return	string		$strJavaScript
	 */
	function getJavascript($type = '') {
		global $_ARRAYLANG;
		switch($type){
			case 'scrollto':
				$strJavaScript = '
				<script type="text/javascript" language="JavaScript">
				//<![CDATA[
					function setScrollPos(){
						if (typeof(window.pageYOffset) != \'undefined\') {
							offset = window.pageYOffset;
						} else {
							offset = document.documentElement.scrollTop;
						}
						document.getElementById("scrollpos").value = offset;
					}
				//]]>
				</script>
				';
				break;
			case 'goto':
				$strJavaScript = '
							<script type="text/javascript" language="JavaScript">
							//<![CDATA[
								function gotoForum(objSelect){
									id = objSelect.options[objSelect.selectedIndex].value;
									if(id==0){return top.location.href="?section=forum";}
									if(id.indexOf("_cat") > -1){
										return top.location.href="?section=forum&cmd=cat&id="+parseInt(id);
									}else{
										return top.location.href="?section=forum&cmd=board&id="+id;
									}
								}
							//]]>
							</script>
						';
				break;
			case 'deletePost':
				$strJavaScript = '
							<script type="text/javascript" language="JavaScript">
							//<![CDATA[
								function deletePost(thread_id, post_id){
									if(confirm("'.$_ARRAYLANG['TXT_FORUM_CONFIRM_DELETE'].'\n'.$_ARRAYLANG['TXT_FORUM_CANNOT_UNDO_OPERATION'].'")){
										window.location.href = "?section=forum&cmd=thread&id="+thread_id+"&act=delete&postid="+post_id;
									}
								}
							//]]>
							</script>
						';
				break;
			case 'deleteThread':
				$strJavaScript = '
							<script type="text/javascript" language="JavaScript">
							//<![CDATA[
								function deleteThread(category_id, thread_id){
									if(confirm("'.$_ARRAYLANG['TXT_FORUM_CONFIRM_DELETE'].'\n'.$_ARRAYLANG['TXT_FORUM_CANNOT_UNDO_OPERATION'].'")){
										window.location.href = "?section=forum&cmd=board&id="+category_id+"&act=delete&threadid="+thread_id;
									}
								}
							//]]>
							</script>
						';
				break;
			case 'notification':
				$strJavaScript = '
							<script type="text/javascript" language="JavaScript">
							//<![CDATA[
								function AddToTheList(from,dest,add,remove){
								    if(from.selectedIndex < 0){
										if(from.options[0] != null){
											from.options[0].selected = true;
										}
										from.focus();
										return false;
									}else{
										for(var i=0; i<from.length; i++){
											if (from.options[i].selected){
										    	dest.options[dest.length] = new Option( from.options[i].text, from.options[i].value, false, false);
								   			}
										}
									    for (var i=from.length-1; i>=0; i--){
											if (from.options[i].selected){
										       from.options[i] = null;
								   			}
										}
									}
								    disableButtons(from,dest,add,remove);
								}

								function RemoveFromTheList(from,dest,add,remove){
									if ( dest.selectedIndex < 0){
										if (dest.options[0] != null){
											dest.options[0].selected = true;
										}
										dest.focus();
										return false;
									}else{
										for (var i=0; i<dest.options.length; i++){
											if (dest.options[i].selected){
										    	from.options[from.options.length] = new Option( dest.options[i].text, dest.options[i].value, false, false);
								   			}
										}
									    for (var i=dest.options.length-1; i>=0; i--){
											if (dest.options[i].selected){
										       dest.options[i] = null;
								   			}
										}
									}
									disableButtons(from,dest,add,remove);
								}

								function disableButtons(from,dest,add,remove){
									if (from.options.length > 0 ){
										add.disabled = 0;
									}else{
										add.disabled = 1;
									}
									if (dest.options.length > 0){
										remove.disabled = 0;
									}else{
										remove.disabled = 1;
									}
								}

								function SelectAllList(CONTROL){
									for(var i = 0;i < CONTROL.length;i++){
										CONTROL.options[i].selected = true;
									}
								}

								function DeselectAllList(CONTROL){
									for(var i = 0;i < CONTROL.length;i++){
										CONTROL.options[i].selected = false;
									}
								}
							//]]>
							</script>';
				break;
			case 'insertText':
				$thanks = $_ARRAYLANG['TXT_FORUM_RATING_THANKS'];
				$strJavaScript = <<< EOJS
<script type="text/javascript" language="JavaScript">
//<![CDATA[
	var ratePost = function(postId, delta, obj){
		var d = document;
		var dl=document.location
		var abs = dl.protocol+'//'+dl.host+dl.href.split(/index\.php/)[0].split(dl.host)[1]
		var url=abs+'index.php?section=forum&cmd=thread&act=rate&value='+delta+'&postId='+postId;
		var i = d.createElement("img");
		i.src = url;
		i.id = 'tmp_Img';
		d.body.appendChild(i);
		d.body.removeChild(d.getElementById(i.id));
		document.getElementById("forum_current_rating_"+postId).innerHTML=document.getElementById("forum_current_rating_"+postId).innerHTML*1+delta;
		document.getElementById("forum_rating_"+postId).innerHTML="$thanks";
		x=setTimeout('document.getElementById("forum_rating_'+postId+'").parentNode.removeChild(document.getElementById("forum_rating_'+postId+'"))', 2000);
	}

	var showToolTip = function(txt, node, id){
		d=document;
		oTxt=d.createTextNode(txt);
		oDiv=d.createElement('div');
		oDiv.id=id;
		oDiv.appendChild(oTxt);
		node.parentNode.appendChild(oDiv);
	}

	var hideToolTip = function(id){
		document.getElementById(id).parentNode.removeChild(document.getElementById(id));
	}

//]]>
</script>
EOJS;

				break;
			default:
				$strJavaScript = '
							<script type="text/javascript" language="JavaScript">
							//<![CDATA[
								function toggleCategory(categoryId){
									objDiv 	= document.getElementById("maincat_"+categoryId);
									objImg 	= document.getElementById("maincat_"+categoryId+"_img");

									if (objDiv.style.display == "block") {
								    	objDiv.style.display = "none";
								    	objImg.src = "'.ASCMS_MODULE_IMAGE_WEB_PATH.'/forum/arrow_down.gif";
								    } else {
								    	objDiv.style.display = "block";
								    	objImg.src = "'.ASCMS_MODULE_IMAGE_WEB_PATH.'/forum/arrow_up.gif";
								    }
								 }
							//]]>
							</script>
						';
				break;

		}
		return $strJavaScript;
	}

}
?>
