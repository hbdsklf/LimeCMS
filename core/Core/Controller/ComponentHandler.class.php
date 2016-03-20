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
 * Handles all components
 * 
 * This is a wrapper class for SystemComponentRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */

namespace Cx\Core\Core\Controller;

/**
 * ComponentException is thrown if component could not be found
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */
class ComponentException extends \Exception {}

/**
 * Handles all components
 *
 * This is a wrapper class for SystemComponentRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */
class ComponentHandler {
    
    /**
     * Are we in frontend or backend mode?
     * @var boolean
     */
    private $frontend;
    
    /**
     * Repository of SystemComponents
     * @var \Cx\Core\Core\Model\Repository\SystemComponentRepository
     */
    protected $systemComponentRepo;
    
    /**
     * Instanciates a new ComponentHandler
     * @todo Read component list from license (see $this->components for why we didn't do that yet)
     * @param \Cx\Core_Modules\License\License $license Current license
     * @param boolean $frontend Wheter we are in frontend mode or not
     * @param \Doctrine\ORM\EntityManager $em Doctrine entity manager
     */
    public function __construct(\Cx\Core_Modules\License\License $license, $frontend, \Doctrine\ORM\EntityManager $em) {
        $this->frontend = $frontend;
        //$this->components = $license->getLegalComponentsList();
        $this->systemComponentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $this->systemComponentRepo->findAll();
        
        $this->callRegisterEventsHooks();
        $this->callRegisterEventListenersHooks();
    }
    
    /**
     * Calls hook scripts on components to register events
     */
    public function callRegisterEventsHooks() {
        $this->systemComponentRepo->callRegisterEventsHooks();
    }
    
    /**
     * Calls hook scripts on components to register event listeners
     */
    public function callRegisterEventListenersHooks() {
        $this->systemComponentRepo->callRegisterEventListenersHooks();
    }
    
    /**
     * Calls hook scripts on components before resolving
     */
    public function callPreResolveHooks() {
        $this->systemComponentRepo->callPreResolveHooks();
    }
    
    /**
     * Calls hook scripts on components after resolving
     */
    public function callPostResolveHooks() {
        $this->systemComponentRepo->callPostResolveHooks();
    }
    
    /**
     * Calls hook scripts on components before loading content
     */
    public function callPreContentLoadHooks() {
        $this->systemComponentRepo->callPreContentLoadHooks();
    }
    
    /**
     * Calls hook scripts on components before loading module content
     */
    public function callPreContentParseHooks() {
        $this->systemComponentRepo->callPreContentParseHooks();
    }
    
    /**
     * Calls hook scripts on components after loading module content
     */
    public function callPostContentParseHooks() {
        $this->systemComponentRepo->callPostContentParseHooks();
    }
    
    /**
     * Calls hook scripts on components after loading content
     */
    public function callPostContentLoadHooks() {
        $this->systemComponentRepo->callPostContentLoadHooks();
    }
    
    /**
     * Calls hook scripts on components before finalizing
     */
    public function callPreFinalizeHooks() {
        $this->systemComponentRepo->callPreFinalizeHooks();
    }
    
    /**
     * Calls hook scripts on components after finalizing
     */
    public function callPostFinalizeHooks() {
        $this->systemComponentRepo->callPostFinalizeHooks();
    }
    
    /**
     * Load the component with the name specified
     * @param \Cx\Core\Core\Controller\Cx $cx Main class instance
     * @param string $componentName Name of component to load
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     * @return null
     * @throws ComponentException If component is not found
     */
    public function loadComponent(\Cx\Core\Core\Controller\Cx $cx, $componentName, \Cx\Core\ContentManager\Model\Entity\Page $page = null) {
        $component = $this->systemComponentRepo->findOneBy(array('name'=>$componentName));
        if (!$component) {
            throw new ComponentException('Component not found (' . $componentName . ')');
        }
        $component->load($page);
    }
}
