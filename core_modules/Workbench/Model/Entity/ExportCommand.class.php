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
 * Command to pack components into a zip file
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to pack components into a zip file
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class ExportCommand extends Command {

    /**
     * Command name
     * @var string
     */
    protected $name = 'export';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Export a component (core, core_module, lib, module, template, etc.)';

    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) export [core|core_module|lib|module] {component_name} ([customized|uncustomized]) {path to zip package}';

    /**
     * Command help text
     * @var string
     */
    protected $help = 'Packs a component to a zip package at the specified location.';

    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        $comp = new ReflectionComponent($arguments[3], $arguments[2]);
        $customized = false;
        if (isset($arguments[5])) {
            if ($arguments[5] == 'customized') {
                $customized = true;
            }
            $arguments[4] = $arguments[5];
        }
        $zipPath = $arguments[4];

        if (file_exists($zipPath)) {
            if (!$this->interface->yesNo('File already exists. Do you want to overwrite it?')) {
                return;
            }
        }

        $comp->pack($zipPath, $customized);

        $this->interface->show('Component exported successfully.');
    }
}
