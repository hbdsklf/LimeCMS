<?php

/**
 * Content Manager 2 (Doctrine-based version)
 *
 * @copyright   Comvation AG
 * @author      Comvation Engineering Team
 * @package     contrexx
 * @subpackage  admin
 */

use Doctrine\Common\Util\Debug as DoctrineDebug;

require ASCMS_CORE_PATH.'/BackendTable.class.php';
require ASCMS_CORE_PATH.'/Module.class.php';
require ASCMS_CORE_PATH.'/routing/LanguageExtractor.class.php';

class ContentManagerException extends ModuleException {}

class ContentManager extends Module {
    //doctrine entity manager
	protected $em = null;
    //the mysql connection
    protected $db = null;
    //the init object
    protected $init = null;

    protected $pageRepository = null;

    /**
     * @param string $act
     * @param $template
     * @param $db the ADODB db object
     * @param $init the Init object
     */
	public function __construct($act, $template, $db, $init) {
        parent::__construct($act, $template);
        if($this->act == 'new')
            $this->act = ''; //default action;
	
        $this->em = Env::em();
        $this->db = $db;
        $this->init = $init;
        $this->pageRepository = $this->em->getRepository('Cx\Model\ContentManager\Page');
        $this->defaultAct = 'actRenderCM';
	}

	protected function actRenderCM() {
        JS::activate('cx');
        JS::activate('ckeditor');
        JS::activate('jqueryui');
        JS::activate('jstree');
        JS::activate('chosen');
        JS::registerJS('lib/javascript/lock.js');

		$this->template->addBlockfile('ADMIN_CONTENT', 'content_manager', 'content_manager.html');
		$this->template->touchBlock('content_manager');
		$this->template->addBlockfile('CONTENT_MANAGER_MEAT', 'content_manager_meat', 'cm.html');
		$this->template->touchBlock('content_manager_meat');

        $this->setLanguageVars(array(
            //langs
            'TXT_CORE_GERMAN', 'TXT_CORE_FRENCH', 'TXT_CORE_ENGLISH',
            //categories
            'TXT_CORE_SITE_TYPE', 'TXT_CORE_SITE_CONTENT', 'TXT_CORE_SITE_ACCESS', 'TXT_CORE_SITE_SETTINGS', 'TXT_CORE_SITE_HISTORY',
            //type tab
            'TXT_CORE_TYPE_EXPLANATION', 'TXT_CORE_TYPE_CONTENT', 'TXT_CORE_TYPE_CONTENT_DESCRIPTION', 'TXT_CORE_TYPE_REDIRECTION', 'TXT_CORE_TYPE_REDIRECTION_DESCRIPTION', 'TXT_CORE_TYPE_APP', 'TXT_CORE_TYPE_APP_DESCRIPTION', 
            //content tab
            'TXT_CORE_NAVTITLE', 'TXT_CORE_CONTENTTITLE', 'TXT_CORE_ACTIVE_FROM_TO', 'TXT_CORE_ACTIVE_FROM', 'TXT_CORE_ACTIVE_TO', 'TXT_CORE_META_INFORMATION', 'TXT_CORE_META_TITLE',  'TXT_CORE_META_KWORDS', 'TXT_CORE_META_DESC', 'TXT_CORE_META_ROBOTS',
            //settings tab
            'TXT_CORE_APPLICATION_AREA', 'TXT_CORE_APPLICATION', 'TXT_CORE_AREA', 'TXT_CORE_OPTICS_STYLE', 'TXT_CORE_SKIN', 'TXT_CORE_SPECIAL_CONTENT_PAGE', 'TXT_CORE_CUSTOMCONTENT', 'TXT_CORE_REDIRECTION', 'TXT_CORE_TARGET', 'TXT_CORE_PERFORMANCE_OPTIMIZATION', 'TXT_CORE_CACHING', 'TXT_CORE_LINK', 'TXT_CORE_SLUG', 'TXT_CORE_CSSNAME',
            //bottom buttons
            'TXT_CORE_PREVIEW', 'TXT_CORE_SAVE_PUBLISH', 'TXT_CORE_SAVE'
        ));

        // TODO: move including of add'l JS dependencies to cx obj from /cadmin/index.html
        $this->template->setVariable('CXJS_INIT_JS', ContrexxJavascript::getInstance()->initJs());


        $this->template->setVariable('SKIN_OPTIONS', $this->getSkinOptions());
	}

    protected function getSkinOptions() {
        $query = "SELECT id,themesname FROM ".DBPREFIX."skins ORDER BY id";
        $rs = $this->db->Execute($query);

        $options = '';        
        while(!$rs->EOF) {
            $id = $rs->fields['id'];
            $name = $rs->fields['themesname'];
            $options .= "<option value=\"$id\">$name</option>\n";
            
            $rs->MoveNext();
        }
        return $options;
    }

    protected function setLanguageVars($ids) {
        global $_CORELANG;
        foreach($ids as $id) {
            $this->template->setVariable($id, $_CORELANG[$id]);
        }
    }

    protected function actAjaxGetHistoryTable() {       
        if(!isset($_GET['pageId']))
            throw new ContentManagerException('please provide a pageId');

        //(I) get the right page
        $id = $_GET['pageId'];
        $page = $this->pageRepository->findOneById($id);

        if(!$page) {
            throw new ContentManagerException("could not find page with id $id");
        }

        //(II) build the table with headers
        $table = new BackendTable(array('width' => '100%'));
        $table->setAutoGrow(true);

        $table->setHeaderContents(0,0,'Date');
        $table->setHeaderContents(0,1,'Title');
        $table->setHeaderContents(0,2,'Author');
        //make sure those are th's too
        $table->setHeaderContents(0,3,'');
        $table->setHeaderContents(0,4,'');

        //(III) collect page informations - path, virtual language directory
        $path = $this->pageRepository->getPath($page, true);

        $le = new \Cx\Core\Routing\LanguageExtractor($this->db, DBPREFIX);
        $langDir = $le->getShortNameOfLanguage($page->getLang());

        //(IV) add current entry to table
        $this->addHistoryEntries($page, $table, 1);
      
        //(V) add the history entries
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $logs = $logRepo->getLogEntries($page);
      
        for($i = 0 ; $i < count($logs); $i++) {
            $version = $i + 1;
            $row = $i + 2;
            $logRepo->revert($page, $version);
            $this->addHistoryEntries($page, $table, $row, $version, $langDir.'/'.$path);
        }
       
        //(VI) render
        die($table->toHtml());
    }

    protected function addHistoryEntries($page, $table, $row, $version='', $path='') {
        global $_ARRAYLANG;

        $dateString = $page->getUpdatedAt()->format(ASCMS_DATE_FORMAT);

        if($row > 1) { //not the current page
            $table->setCellContents($row, 3, '<a href="javascript:loadHistoryVersion('.$page->getId().','.$version.')">'.$_ARRAYLANG['TXT_CORE_LOAD'].'</a>');
            $historyLink = ASCMS_PATH_OFFSET."/$path?history=$version";
            $table->setCellContents($row, 4, '<a href="'.$historyLink.'" target="_blank">'.$_ARRAYLANG['TXT_CORE_PREVIEW'].'</a>');
        }
        else { //current page state
            $dateString .= ' ('. $_ARRAYLANG['TXT_CORE_CURRENT'] . ')';
        }

        $table->setCellContents($row, 0, $dateString);
        $table->setCellContents($row, 1, $page->getTitle());
        $table->setCellContents($row, 2, $page->getUsername());
    }

    protected function actAjaxGetCustomContentTemplates() {
        if(!isset($_GET['themeId']))
            throw new ContentManagerException('please provide a value for "themeId".');

        $module = isset($_GET['module']) ? $_GET['module'] : '';
        $themeId = intval($_GET['themeId']);
        $isHomeRequest = $module == 'home';

        $templates = $this->init->getCustomContentTemplatesForTheme($themeId);
        $matchingTemplates = array();

        foreach($templates as $name) {
            $isHomeTemplate = substr($name,0,4) == 'home';
            if($isHomeTemplate && $isHomeRequest)
                $matchingTemplates[] = $name;
            else if(!$isHomeTemplate && !$isHomeRequest)
                $matchingTemplates[] = $name;              
        }
        
        die(json_encode($matchingTemplates));
    }

    protected function actAjaxRevert() {       
        if(!isset($_POST['pageId']))
            throw new ContentManagerException('please provide a pageId');
        if(!isset($_POST['version']))
            throw new ContentManagerException('please provide a version you want to revert to');

        $id = $_POST['pageId'];
        $version = $_POST['version'];
        $page = $this->pageRepository->findOneById($id);

        if(!$page)
            new ContentManagerException("could not find page with id $id");
       
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        
        $logRepo->revert($page, $version);

        $this->em->persist($page);
        $this->em->flush();
    }
}
?>
