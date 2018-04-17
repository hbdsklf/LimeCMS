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
 * Model main controller
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Controller;

/**
 * Model main controller
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    
    /**
     * PostInit hook to add entity validation
     * @param \Cx\Core\Core\Controller\Cx $cx Cx class instance
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {
        // init cx validation
        $cx->getEvents()->addEventListener(
            'model/onFlush',
            new \Cx\Core\Model\Model\Event\EntityBaseEventListener()
        );
    }

    /**
     * Slugifies the given string
     * @param $string The string to slugify
     * @return $string The slugified string
     */
    public function slugify($string) {
        // replace international characters
        $string = $this->getComponent('LanguageManager')
            ->replaceInternationalCharacters($string);

        // replace spaces
        $string = preg_replace('/\s+/', '-', $string);

        // replace all non-url characters
        $string = preg_replace('/[^a-zA-Z0-9-_]/', '', $string);

        // replace duplicate occurrences (in a row) of char "-" and "_"
        $string = preg_replace('/([-_]){2,}/', '-', $string);

        return $string;
    }
}
