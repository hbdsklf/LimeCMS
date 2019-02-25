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
 * Backend controller to create a default backend view.
 *
 * Create a subclass of this in order to create a normal backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity\API\V2;

/**
 * Backend controller to create a default backend view.
 *
 * Create a subclass of this in order to create a normal backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class SystemComponentBackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Default permission
     *
     * @var Cx\Core_Modules\Access\Model\Entity\Permission
     */
    protected $defaultPermission;

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        if (isset($this->getData('Backend')['backendTabs'])) {
            $cmds = $this->getData('Backend')['backendTabs'];
            foreach ($cmds as &$cmd) {
                if (is_array($cmd) && isset($cmd['context'])) {
                    unset($cmd['context']);
                    unset($cmd['entity']);
                    unset($cmd['vgconfig']);
                }
            }
            return $cmds;
        }
        return parent::getCommands();
    }

    /**
     * Check the access permission
     *
     * @param array $command
     *
     * @return boolean
     */
    protected function hasAccess($command) {
        $objPermission = is_array($command) && isset($command['permission']) ? $command['permission'] : $this->defaultPermission;
        if ($objPermission instanceof \Cx\Core_Modules\Access\Model\Entity\Permission) {
            if (!$objPermission->hasAccess()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     * @param boolean $isSingle Wether edit view or not
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        global $_ARRAYLANG;

        $cmds = array();
        if (isset($this->getData('Backend')['backendTabs'])) {
            $cmds = $this->getData('Backend')['backendTabs'];
        } else {
            $cmds = $this->getCommands();
        }
        // this should be moved to a more generic location as navigation should use the same $cmd
        if (!isset($cmds[current($cmd)])) {
            if (is_array(current($cmds))) {
                $cmd[0] = key($cmds);
            } else {
                $cmd[0] = current($cmds);
            }
        }
        $context = 'entity';
        if (is_array($cmds[current($cmd)]) && isset($cmds[current($cmd)]['context'])) {
            $context = $cmds[current($cmd)]['context'];
        }
        switch ($context) {
            case 'settings':
                if (!isset($cmd[1])) {
                    $cmd[1] = '';
                }
                switch ($cmd[1]) {
                    case '':
                        \Cx\Core\Setting\Controller\Setting::init(
                            $this->getName(),
                            null,
                            'FileSystem',
                            null,
                            \Cx\Core\Setting\Controller\Setting::REPOPULATE
                        );
                        \Cx\Core\Setting\Controller\Setting::storeFromPost();
                        \Cx\Core\Setting\Controller\Setting::setEngineType(
                            $this->getName(),
                            'FileSystem'
                        );
                        \Cx\Core\Setting\Controller\Setting::show(
                            $template,
                            $this->getName() . '/' . implode('/', $cmd),
                            $this->getName(),
                            $_ARRAYLANG[
                                'TXT_' . strtoupper(
                                    $this->getType()
                                ) . '_' . strtoupper(
                                    $this->getName() . '_ACT_' . $cmd[0] . '_DEFAULT'
                                )
                            ],
                            'TXT_' . strtoupper(
                                $this->getType() . '_' . $this->getName()
                            ) . '_'
                        );
                        break;
                    default:
                        if (!$template->blockExists('mailing')) {
                            return;
                        }
                        $template->setVariable(
                            'MAILING',
                            \Cx\Core\MailTemplate\Controller\MailTemplate::adminView(
                                $this->getName(),
                                'nonempty',
                                $config['corePagingLimit'],
                                'settings/email'
                            )->get()
                        );
                        break;
                }
                break;
            case 'entity':
                // Parse entity view generation pages
                $entityClassName = $this->getNamespace() . '\\Model\\Entity\\' . current($cmd);
                if (in_array($entityClassName, $this->getEntityClasses())) {
                    $this->parseEntityClassPage($template, $entityClassName, current($cmd), array(), $isSingle);
                    return;
                }
                break;
            default:
                if ($template->blockExists('overview')) {
                    $template->touchBlock('overview');
                }
                break;
        }
    }

    /**
     * This function returns an array which contains the vgOptions array for all entities
     *
     * @access public
     * @param $dataSetIdentifier
     * @return array
     */
    public function getAllViewGeneratorOptions($dataSetIdentifier = '') {
        $vgOptions = array();
        foreach ($this->getEntityClassesWithView() as $entityClassName) {
            $vgOptions[$entityClassName] = $this->getViewGeneratorOptions($entityClassName, $dataSetIdentifier);
        }
        $vgOptions[''] = $this->getViewGeneratorOptions('', '');
        return $vgOptions;
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        $header = '';
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        }
        return array(
            'header' => $header,
            'functions' => array(
                'add'       => true,
                'edit'      => true,
                'delete'    => true,
                'sorting'   => true,
                'paging'    => true,
                'filtering' => false,
            ),
        );
    }

    /**
     * Return true here if you want the first tab to be an entity view
     * @return boolean True if overview should be shown, false otherwise
     */
    protected function showOverviewPage() {
        return false;
    }
}
