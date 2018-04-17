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
 * Main controller for Downloads
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 */

namespace Cx\Modules\Downloads\Controller;

/**
 * Main controller for Downloads
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
    }
     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objDownloadsModule = new Downloads(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objDownloadsModule->getPage());
                $downloads_pagetitle = $objDownloadsModule->getPageTitle();
                if ($downloads_pagetitle) {
                    \Env::get('cx')->getPage()->setTitle($downloads_pagetitle);
                    \Env::get('cx')->getPage()->setContentTitle($downloads_pagetitle);
                    \Env::get('cx')->getPage()->setMetaTitle($downloads_pagetitle);
                }

                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                $subMenuTitle = $_CORELANG['TXT_DOWNLOADS'];
                $objDownloadsModule = new DownloadsManager();
                $objDownloadsModule->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        // downloads group
        $groups            = Group::getGroups();
        $groupsPlaceholders = $groups->getGroupsPlaceholders();
        $this->registerDownloadsWidgets($groupsPlaceholders, \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::TYPE_PLACEHOLDER);

        // downloads category list
        $categoriesBlocks = Category::getCategoryWidgetNames();
        $this->registerDownloadsWidgets($categoriesBlocks, \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::TYPE_BLOCK);
    }

    /**
     * Register the downloads widgets
     *
     * @param array   $widgets widgets array
     * @param string  $type Widget type
     *
     * @return null
     */
    protected function registerDownloadsWidgets($widgets, $type) {

        $pos = 0;
        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        }
        $widgetController = $this->getComponent('Widget');
        foreach ($widgets as $widgetName) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName,
                $type,
                '',
                '',
                array('pos' => $pos)
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget($widget);
        }
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
        $eventListener = new \Cx\Modules\Downloads\Model\Event\DownloadsEventListener($this->cx);
        $evm->addEventListener('SearchFindContent', $eventListener);
        $evm->addEventListener('mediasource.load', $eventListener);

        // locale event listener
        $localeLocaleEventListener = new \Cx\Modules\Downloads\Model\Event\LocaleLocaleEventListener($this->cx);
        $evm->addModelListener('postPersist', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
        $evm->addModelListener('preRemove', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
    }

    /**
     * Find Downloads by keyword $searchTerm and return them in a
     * two-dimensional array compatible to be used by Search component.
     *
     * @param   string  $searchTerm The keyword to search by
     * @return  array   Two-dimensional array of Downloads found by keyword
     *                  $searchTerm.
     *                  If integration into search component is disabled or
     *                  no Download matched the giving keyword, then an
     *                  empty array is retured.
     */
    public function getDownloadsForSearchComponent($searchTerm) {
        $result = array();
        $download = new Download();
        $downloadLibrary = new DownloadsLibrary();
        $config = $downloadLibrary->getSettings();

        // abort in case downloads shall not be included into the global
        // fulltext search component
        if (!$config['integrate_into_search_component']) {
            return array();
        }

        // lookup downloads by given keyword
        $downloadAsset = $download->getDownloads(
            null,
            $searchTerm,
            null,
            null,
            null,
            null,
            $config['list_downloads_current_lang']
        );

        if (!$downloadAsset) {
            return array();
        }

        $langId = DownloadsLibrary::getOutputLocale()->getId();

        while (!$downloadAsset->EOF) {
            try {
                $url = DownloadsLibrary::getApplicationUrl(
                    $downloadAsset->getAssociatedCategoryIds()
                );
            } catch (DownloadsLibraryException $e) {
                $downloadAsset->next();
                continue;
            }
            $url->setParam('id', $downloadAsset->getId());
            $result[] = array(
                'Score'   => 100,
                'Title'   => $downloadAsset->getName($langId),
                'Content' => $downloadAsset->getTrimmedDescription($langId),
                'Link'    => $url->toString()
            );
            $downloadAsset->next();
        }

        return $result;
    }
}
