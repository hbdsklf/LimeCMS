<?php declare(strict_types=1);

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
 * This is the superclass for all main Controllers for a Component
 *
 * Decorator for SystemComponent
 * Every component needs a SystemComponentController for initialization
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity\API\V2;

/**
 * This is the superclass for all main Controllers for a Component
 *
 * Decorator for SystemComponent
 * Every component needs a SystemComponentController for initialization
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class SystemComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    const COMPONENT_CONFIG_FILE = 'Component.yml';
    const VERSION = 2;

    protected $data = array();

    /**
     * Available controllers
     * @var array List of Controller objects
     */
    protected $controllers = array();

    public static function isThisVersion(
        \Cx\Core\Core\Controller\Cx $cx,
        \Cx\Core\Core\Model\Entity\SystemComponent $component
    ) {
        return (bool) $cx->getClassLoader()->getFilePath(
            $component->getDirectory(false) . '/' . static::COMPONENT_CONFIG_FILE
        );
    }

    /**
     * This finds the correct FQCN for a controller name
     * @param string $controllerClassShort Short name for controller
     * @return string Fully qualified controller class name
     */
    protected function getControllerClassName($controllerClassShort) {
        $class = $controllerClassShort;
        if (strpos('\\', $class) != 1) {
            if (!$this->cx->getClassLoader()->getFilePath($this->getDirectory(false).'/Controller/'.$class.'Controller.class.php')) {
                $class = '\\Cx\\Core\\Core\\Model\\Entity\\API\\V' . static::VERSION . '\\SystemComponent' . $class . 'Controller';
            } else {
                $class = $this->getNamespace() . '\\Controller\\' . $class . 'Controller';
            }
        }
        return $this->adjustFullyQualifiedClassName($class);
    }

    /**
     * Initializes a controller
     * @param \Cx\Core\Core\Model\Entity\SystemComponent $systemComponent SystemComponent to decorate
     * @param \Cx\Core\Core\Controller\Cx                               $cx         The Cloudrexx main class
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponent $systemComponent, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponent, $cx);
        $importer = new \Cx\Core_Modules\Listing\Model\Entity\YamlInterface();
        $this->data = $importer->import(
            file_get_contents($this->getDirectory(false) . '/' . static::COMPONENT_CONFIG_FILE)
        );
    }

    public function getData() {
        return $this->data;
    }

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        if (!isset($this->data['controllers'])) {
            return array('Frontend', 'Backend');
        }
        return $this->data['controllers'];
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson() {
        return array();
    }

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array();
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        return '';
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {}

    /**
     * Check whether the command has access to execute or not.
     *
     * @param string $command   name of the command to execute
     * @param array  $arguments list of arguments for the command
     *
     * @return boolean
     */
    public function hasAccessToExecuteCommand($command, $arguments)
    {
        $commands = $this->getCommandsForCommandMode();
        $method = (php_sapi_name() === 'cli') ? array('cli') : null;

        $objPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(null, $method, false, null, null, null);
        if (isset($commands[$command]) && $commands[$command] instanceof \Cx\Core_Modules\Access\Model\Entity\Permission) {
            $objPermission = $commands[$command];
        }

        if ($objPermission->hasAccess($arguments)) {
            return true;
        }

        return false;
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {}

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents() {}

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
    public function registerEventListeners() {}
}
