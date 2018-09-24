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
 * Main controller for Routing
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing\Controller;

/**
 * Main controller for Routing
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('Backend');
    }

    public function preResolve(\Cx\Core\Routing\Url $url) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $em = $this->cx->getDb()->getEntityManager();
        $rewriteRuleRepo = $em->getRepository($this->getNamespace() . '\\Model\\Entity\\RewriteRule');
        $rewriteRules = $rewriteRuleRepo->findBy(array(), array('orderNo'=>'asc'));
        $last = false;
        $originalUrl = clone $url;
        foreach ($rewriteRules as $rewriteRule) {
            try {
                $url = $rewriteRule->resolve($url, $last);
            } catch (\Exception $e) {
                // This is thrown if the regex of the rule is not valid
            }
            if ($last) {
                break;
            }
        }
        if ($originalUrl->toString() != $url->toString()) {
            if (
                $rewriteRule->getRewriteStatusCode() !=
                \Cx\Core\Routing\Model\Entity\RewriteRule::REDIRECTION_TYPE_INTERN
            ) {
                $headers = array(
                    'Location' => $url->toString(),
                );
                if ($rewriteRule->getRewriteStatusCode() == 301) {
                    array_push(
                        $headers,
                        $_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently'
                    );
                }
                $this->getComponent('Cache')->writeCacheFileForRequest(
                    null,
                    $headers,
                    ''
                );
                \Cx\Core\Csrf\Controller\Csrf::header(
                    'Location: ' . $url->toString(),
                    true,
                    $rewriteRule->getRewriteStatusCode()
                );
                die();
            }
            try {
                \DBG::log('Fetching content from ' . $url->toString());
                $request = new \HTTP_Request2($url->toString(), \HTTP_Request2::METHOD_GET);
                $request->setConfig(array(
                    'follow_redirects' => true,
                ));
                $response = $request->send();
                $content = $response->getBody();
                foreach ($response->getHeader() as $key=>$value) {
                    if (in_array($key, array(
                        'content-encoding',
                        'transfer-encoding',
                    ))) {
                        continue;
                    }
                    \Cx\Core\Csrf\Controller\Csrf::header($key . ':' . $value);
                }
                $continue = false;
                die($content);
            } catch (\HTTP_Request2_Exception $e) {
                \DBG::dump($e);
            }
        }
    }
}
