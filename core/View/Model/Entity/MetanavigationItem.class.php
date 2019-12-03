<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * Metanavigation Item
 * 
 * Represents an entry in the backend metanavigation
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@drissg.ch>
 * @package     cloudrexx
 * @subpackage  core_view
 * @version     5.3.0
 */

namespace Cx\Core\View\Model\Entity;

/**
 * Metanavigation Item
 * 
 * Represents an entry in the backend metanavigation
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@drissg.ch>
 * @package     cloudrexx
 * @subpackage  core_view
 * @version     5.3.0
 */
class MetanavigationItem extends \Cx\Model\Base\EntityBase {

    /**
     * @var string Translated display name for this item
     */
    protected $translatedName = '';

    /**
     * @var string Icon name
     * @todo: Add documentation on what icons are available
     */
    protected $iconName = '';

    /**
     * @var \Cx\Lib\Net\Model\Entity\Uri Link the item points to
     */
    protected $link = null;

    /**
     * Creates a new metanavigation item
     *
     * @param string $translatedName Translated display name for this item
     * @param string $iconName Name of the icon for this item
     * @param \Cx\Lib\Net\Model\Entity\Uri Link this item points to
     */
    public function __construct(
        string $translatedName,
        string $iconName,
        \Cx\Lib\Net\Model\Entity\Uri $link
    ) {
        $this->translatedName = $translatedName;
        $this->iconName = $iconName;
        $this->link = $link;
    }

    /**
     * Returns the translated name for this item
     * @return string Translated display name
     */
    public function getTranslatedName(): string {
        return $this->translatedName;
    }

    /**
     * Returns the icon name
     * @return string Icon name
     */
    public function getIconName(): string {
        return $this->iconName;
    }

    /**
     * Returns the link this item points to
     * @return \Cx\Lib\Net\Model\Entity\Uri Link this item points to
     */
    public function getLink(): \Cx\Lib\Net\Model\Entity\Uri {
        return $this->link;
    }

    /**
     * Parses this item into the metanavigation
     *
     * This should simply set the following placeholders:
     * - TXT_METANAVIGATION_ITEM_NAME
     * - METANAVIGATION_ITEM_CLASS
     * - METANAVIGATION_ITEM_URL
     * @param \Cx\Core\Html\Sigma $template Template to parse into
     */
    public function parse(\Cx\Core\Html\Sigma $template) {
        $template->setVariable(array(
            'TXT_METANAVIGATION_ITEM_NAME' => $this->getTranslatedName(),
            'METANAVIGATION_ITEM_CLASS' => 'icon-' . $this->getIconName(),
            'METANAVIGATION_ITEM_URL' => (string) $this->getLink(),
        ));
    }
}

