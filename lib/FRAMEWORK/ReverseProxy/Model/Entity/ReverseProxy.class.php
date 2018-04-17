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
 * Abstract representation of a caching reverse proxy
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Lib\ReverseProxy\Model\Entity;

/**
 * Abstract representation of a caching reverse proxy
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
abstract class ReverseProxy {
    /**
     * @var string Reverse proxy's hostname
     */
    protected $hostname;
    
    /**
     * @var int Reverse proxy's port
     */
    protected $port;
    
    /**
     * @var \Cx\Lib\ReverseProxy\Model\Entity\SsiProcessor SSI processor used for this proxy
     */
    protected $ssiProcessor;
    
    /**
     * Initializes a reverse proxy instance
     * @param string $hostname Proxy hostname
     * @param int $port Proxy port
     */
    public function __construct($hostname, $port, $ssiProcessor = null) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->ssiProcessor = $ssiProcessor;
    }
    
    /**
     * Drops a page cached by this reverse proxy
     *
     * Each entry in the array $domainsAndPorts has the following structure:
     * array(0 => <domain>, 1 => <port>)
     * @param string $urlPattern Drop all pages that match the pattern, for exact format, make educated guesses
     * @param array $domainsAndPorts List of domains and ports that can be used to access this website
     */
    public function clearCachePage($urlPattern, $domainsAndPorts) {
        foreach ($domainsAndPorts as $domainAndPort) {
            $this->clearCachePageForDomainAndPort($urlPattern, $domainAndPort[0], $domainAndPort[1]);
        }
    }
    
    /**
     * Clears a cache page
     * @param string $urlPattern Drop all pages that match the pattern, for exact format, make educated guesses
     * @param string $domain Domain name to drop cache page of
     * @param int $port Port to drop cache page of
     */
    protected abstract function clearCachePageForDomainAndPort($urlPattern, $domain, $port);
    
    /**
     * Drops all pages cached by this reverse proxy
     *
     * Each entry in the array $domainsAndPorts has the following structure:
     * array(0 => <domain>, 1 => <port>)
     * @param array $domainsAndPorts List of domains and ports that can be used to access this website
     */
    public function clearCache($domainsAndPorts) {
        $this->clearCachePage('*', $domainsAndPorts);
    }
    
    /**
     * Sets SSI processor used for this proxy
     * @param \Cx\Lib\ReverseProxy\Model\Entity\SsiProcessor $ssiProcessor New SSI processor
     */
    public function setSsiProcessor($ssiProcessor) {
        $this->ssiProcessor = $ssiProcessor;
    }
    
    /**
     * Gets SSI processor used for this proxy
     * @return \Cx\Lib\ReverseProxy\Model\Entity\SsiProcessor SSI processor used for this proxy
     */
    public function getSsiProcessor() {
        return $this->ssiProcessor;
    }
}

