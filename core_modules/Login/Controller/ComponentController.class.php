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
 * Main controller for Login
 * 
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_login
 */

namespace Cx\Core_Modules\Login\Controller;

/**
 * Main controller for Login
 * 
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_login
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Load your component.
     * 
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objTemplate, $sessionObj;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if (!isset($sessionObj) || !is_object($sessionObj)) $sessionObj = \cmsSession::getInstance();
                $objLogin = new \Cx\Core_Modules\Login\Controller\Login(\Env::get('cx')->getPage()->getContent());
                $pageTitle = \Env::get('cx')->getPage()->getTitle();
                $pageMetaTitle = \Env::get('cx')->getPage()->getMetatitle();
                \Env::get('cx')->getPage()->setContent($objLogin->getContent($pageMetaTitle, $pageTitle));
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                if (\FWUser::getFWUserObject()->objUser->login(true)) {
                    \Cx\Core\Csrf\Controller\Csrf::header('location: index.php');
                }
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                $objLoginManager = new \Cx\Core_Modules\Login\Controller\LoginManager();
                $objLoginManager->getPage();
                break;

            default:
                break;
        }
    }
    
    /**
     * Do something before a module is loaded
     * 
     * This method is called only if any module
     * gets loaded for content parsing
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentParse(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        if (
            $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND &&
            $page->getModule() == 'logout'
        ) {
            \FWUser::getFWUserObject()->logout();
            exit;
        }
    }
}
