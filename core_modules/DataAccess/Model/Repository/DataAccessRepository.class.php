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
 * DataAccessRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Model\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DataAccessRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */
class DataAccessRepository extends EntityRepository {

    /**
     * Tries to find a matching DataAccess entity for the given criteria
     * @param string $method Request method (get, post, ...)
     * @param string $requestApiKey API key used in request
     * @param array $arguments List of arguments to the current request
     * @param string $dataAccessName Name of the selected DataSource
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess Matching DataAccess object or null
     */
    public function getAccess($method, $requestApiKey, $arguments, $dataAccessName) {
        $requestReadonly = in_array($method, array('options', 'head', 'get'));

        $dataAccess = $this->findOneBy(array('name' => $dataAccessName));

        // do we have a DataAccess for this DataSource?
        if (!$dataAccess) {
            \DBG::msg('DataSource not found!');
            return null;
        }

        // does our apiKey match with one or more of the DataAccesses?
        $apiKeyValid = false;
        foreach ($dataAccess->getDataAccessApiKeys() as $apiKey) {
            // if write access is needed (!$requestReadonly): does the api key allow write?
            if (!$requestReadonly && $apiKey->getReadOnly()) {
                continue;
            }

            if (!$apiKey->getApiKey()) {
                continue;
            }

            if ($apiKey->getApiKey()->getApiKey() != $requestApiKey) {
                continue;
            }
            $apiKeyValid = true;
            break;
        }

        // $validApiKeys now contains all DataAccessApiKey entities that allow
        // this request. If there's at least one, this user has access to this
        // DataAccess object.
        if (!$apiKeyValid) {
            \DBG::msg('There\'s no DataAccess with a matching API key!');
            return null;
        }

        // Fetch correct permission
        $permission = null;
        if ($requestReadonly) {
            $permission = $dataAccess->getReadPermission();
        } else {
            $permission = $dataAccess->getWritePermission();
        }

        // Check permission
        if ($permission && !$permission->hasAccess($arguments)) {
            \DBG::msg('Access denied!');
            return null;
        }
        return $dataAccess;
    }

    /**
     * Returns the HTTP method names you're allowed to use for this DataSource with this API key
     * @param \Cx\Core\DataSource\Model\Entity\DataSource $dataSource Requested DataSource
     * @param string $requestApiKey API key of the request
     * @param array $arguments List of arguments to the current request
     * @return array List of HTTP methods
     */
    public function getAllowedMethods($dataSource, $requestApiKey, $arguments) {
        $baseMethods = array('OPTIONS');
        $readMethods = array('HEAD', 'GET');
        $writeMethods = array('PUT', 'PATCH', 'POST', 'DELETE');

        $hasAccess = false;
        $canRead = false;
        $canWrite = false;

        foreach ($dataSource->getDataAccesses() as $dataAccess) {
            $apiKeys = $dataAccess->getDataAccessApiKeys();
            foreach ($apiKeys as $apiKey) {
                if ($apiKey->getApiKey()->getApiKey() != $requestApiKey) {
                    continue;
                }
                $hasAccess = true;

                if (
                    !$dataAccess->getReadPermission() ||
                    $dataAccess->getReadPermission()->hasAccess($arguments)
                ) {
                    $canRead = true;
                }

                if (
                    !$apiKey->getReadOnly() &&
                    (
                        !$dataAccess->getWritePermission() ||
                        $dataAccess->getWritePermission()->hasAccess($arguments)
                    )
                ) {
                    $canWrite = true;
                }
            }
        }

        $allowedMethods = array();
        if ($hasAccess) {
            $allowedMethods = array_merge($allowedMethods, $baseMethods);
        }
        if ($canRead) {
            $allowedMethods = array_merge($allowedMethods, $readMethods);
        }
        if ($canWrite) {
            $allowedMethods = array_merge($allowedMethods, $writeMethods);
        }
        return $allowedMethods;
    }
}
