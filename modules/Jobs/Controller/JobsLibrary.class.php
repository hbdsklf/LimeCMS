<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Class Document System
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  module_jobs
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Jobs\Controller;

/**
 * Class Document System
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  module_jobs
 * @todo        Edit PHP DocBlocks!
 */
class JobsLibrary
{
    /**
     * Array of setting values
     * 
     * @var array 
     */
    protected $arrSettings = array();

    /**
    * Gets the categorie option menu string
    *
    * @global    object     $objDatabase
    * @global    string     $_LANGID
    * @param     string     $lang
    * @param     string     $selectedOption
    * @return    string     $modulesMenu
    */
    function getCategoryMenu($langId, $selectedCatId="")
    {
        global $objDatabase;

        $strMenu = "";
        $query="SELECT catid,
                       name
                  FROM ".DBPREFIX."module_jobs_categories
                 WHERE lang=".$langId."
              ORDER BY catid";

        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $selected = "";
            if($selectedCatId==$objResult->fields['catid']){
                $selected = "selected";
            }
            $strMenu .="<option value=\"".$objResult->fields['catid']."\" $selected>".stripslashes($objResult->fields['name'])."</option>\n";
            $objResult->MoveNext();
        }
        return $strMenu;
    }

    function getLocationMenu($selectedLocId="")
    {
        global $objDatabase;

        $strMenu = "";
        $query="SELECT id,
                       name
                  FROM ".DBPREFIX."module_jobs_location
                  WHERE 1
              ORDER BY id";

        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $selected = "";
            if($selectedLocId==$objResult->fields['id']){
                $selected = "selected";
            }
            $strMenu .="<option value=\"".$objResult->fields['id']."\" $selected>".stripslashes($objResult->fields['name'])."</option>\n";
            $objResult->MoveNext();
        }
        return $strMenu;
    }

    /**
     * Parse the Hot / Latest jobs
     * 
     * @param \Cx\Core\Html\Sigma $template template object
     * @param \Cx\Core\Locale\Model\Entity\Locale $locale Output locale to use 
     * 
     * @return null
     */
    public function parseHotOrLatestJobs(\Cx\Core\Html\Sigma $template, \Cx\Core\Locale\Model\Entity\Locale $locale)
    {
        //If the block 'jobs_list' not exists, then return
        if (!$template->blockExists('jobs_list')) {
            return;
        }

        //Get the Settings values from DB
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();
        $settings = $this->getSettings();

        //If the config option 'templateIntegration' is off, then return
        if (
            !isset($settings['templateIntegration']) ||
            empty($settings['templateIntegration'])
        ) {
            return;
        }

        //Set the limit based on the config option 'listingLimit'
        $limit = '';
        if (
            isset($settings['listingLimit']) &&
            !empty($settings['listingLimit'])
        ) {
            $limit = ' LIMIT 0, ' . $settings['listingLimit'];
        }

        //get all the hot/newset jobs based on the config option 'sourceOfJobs'
        $query = 'SELECT j.date AS date,
                         j.id AS docid,
                         j.title AS title,
                         j.workload AS workload,
                         j.workloc AS workloc,
                         j.author AS author,
                         jc.name AS name,
                         j.paid
                    FROM `' . DBPREFIX . 'module_jobs` AS j,
                         `' . DBPREFIX . 'module_jobs_categories` AS jc
                    WHERE j.status  = 1 '
                        . ((isset($settings['sourceOfJobs']) && $settings['sourceOfJobs'] == 'manual') ? ' AND j.hot = 1 ' : '') . '
                        AND j.lang  = ' . $locale->getId() . ' 
                        AND j.catid = jc.catid
                        AND (j.startdate <= "' . date('Y-m-d') . '" OR j.startdate = "0000-00-00 00:00:00")
                        AND (j.enddate >= "' . date('Y-m-d') . '" OR j.enddate = "0000-00-00 00:00:00") 
                    ORDER BY j.date DESC' . $limit;
        $objResult = $db->Execute($query);
        if (!$objResult || !$objResult->RecordCount()) {
            $template->hideblock('jobs_list');
        }

        while (!$objResult->EOF) {
            $detailUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('Jobs', 'details', $locale->getId(), array('id' => $objResult->fields['docid']));
            $template->setVariable(array(
                'JOBS_ID'	     => $objResult->fields['docid'],
                'JOBS_LONG_DATE' => date(ASCMS_DATE_FORMAT, $objResult->fields['date']),
                'JOBS_DATE'      => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['date']),
                'JOBS_LINK'      => "<a href=\"" . $detailUrl->toString() . "\" title=\"".contrexx_raw2xhtml($objResult->fields['title'])."\">".contrexx_raw2xhtml($objResult->fields['title'])."</a>",
                'JOBS_AUTHOR'    => contrexx_raw2xhtml($objResult->fields['author']),
                'JOBS_WORKLOAD'  => contrexx_raw2xhtml($objResult->fields['workload']),
                'JOBS_TITLE'     => contrexx_raw2xhtml($objResult->fields['title']),
                'JOBS_LINK_SRC'  => $detailUrl->toString(),
                'JOBS_WORKLOC'   => contrexx_raw2xhtml($objResult->fields['workloc']),
            ));

            if ($template->blockExists('job_paid')) {
                if ($objResult->fields['paid']) {
                    $template->touchBlock('job_paid');
                } else {
                    $template->hideBlock('job_paid');
                }
            }
            if ($template->blockExists('job_not_paid')) {
                if ($objResult->fields['paid']) {
                    $template->hideBlock('job_not_paid');
                } else {
                    $template->touchBlock('job_not_paid');
                }
            }

            $template->parse('jobs_list');
            $objResult->MoveNext();
        }
    }

    /**
     * Get all the setting values from DB
     * 
     * @return array array of setting values
     */
    public function getSettings()
    {
        if ($this->arrSettings) {
            return $this->arrSettings;
        }

        //Get the settings values from DB
        $query = "SELECT `name`, `value`
              FROM `".DBPREFIX."module_jobs_settings`";
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();
        $objResult = $db->Execute($query);
        
        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }

        return $this->arrSettings;
    }

    /**
     * Clear page and esi cache of this component
     */
    protected function clearCache() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getEvents()->triggerEvent(
            'clearEsiCache',
            array(
                'Widget',
                array('jobs_list'),
            )
        );
        $cx->getComponent('Cache')->deleteComponentFiles('Jobs');
    }
}
