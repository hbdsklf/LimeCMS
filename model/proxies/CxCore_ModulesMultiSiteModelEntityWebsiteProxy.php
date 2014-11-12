<?php

namespace Cx\Model\Proxies;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class CxCore_ModulesMultiSiteModelEntityWebsiteProxy extends \Cx\Core_Modules\MultiSite\Model\Entity\Website implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    private function _load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    
    public function setId($id)
    {
        $this->_load();
        return parent::setId($id);
    }

    public function getId()
    {
        $this->_load();
        return parent::getId();
    }

    public function setName($name)
    {
        $this->_load();
        return parent::setName($name);
    }

    public function getName()
    {
        $this->_load();
        return parent::getName();
    }

    public function setCreationDate($creationDate)
    {
        $this->_load();
        return parent::setCreationDate($creationDate);
    }

    public function getCreationDate()
    {
        $this->_load();
        return parent::getCreationDate();
    }

    public function setCodeBase($codeBase)
    {
        $this->_load();
        return parent::setCodeBase($codeBase);
    }

    public function getCodeBase()
    {
        $this->_load();
        return parent::getCodeBase();
    }

    public function setLanguage($language)
    {
        $this->_load();
        return parent::setLanguage($language);
    }

    public function getLanguage()
    {
        $this->_load();
        return parent::getLanguage();
    }

    public function setStatus($status)
    {
        $this->_load();
        return parent::setStatus($status);
    }

    public function getStatus()
    {
        $this->_load();
        return parent::getStatus();
    }

    public function setWebsiteServiceServerId($websiteServiceServerId)
    {
        $this->_load();
        return parent::setWebsiteServiceServerId($websiteServiceServerId);
    }

    public function getWebsiteServiceServerId()
    {
        $this->_load();
        return parent::getWebsiteServiceServerId();
    }

    public function setWebsiteServiceServer(\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer)
    {
        $this->_load();
        return parent::setWebsiteServiceServer($websiteServiceServer);
    }

    public function getWebsiteServiceServer()
    {
        $this->_load();
        return parent::getWebsiteServiceServer();
    }

    public function getOwner()
    {
        $this->_load();
        return parent::getOwner();
    }

    public function setSecretKey($secretKey)
    {
        $this->_load();
        return parent::setSecretKey($secretKey);
    }

    public function getSecretKey()
    {
        $this->_load();
        return parent::getSecretKey();
    }

    public function setIpAddress($ipAddress)
    {
        $this->_load();
        return parent::setIpAddress($ipAddress);
    }

    public function getIpAddress()
    {
        $this->_load();
        return parent::getIpAddress();
    }

    public function setOwnerId($ownerId)
    {
        $this->_load();
        return parent::setOwnerId($ownerId);
    }

    public function getOwnerId()
    {
        $this->_load();
        return parent::getOwnerId();
    }

    public function setThemeId($themeId)
    {
        $this->_load();
        return parent::setThemeId($themeId);
    }

    public function getThemeId()
    {
        $this->_load();
        return parent::getThemeId();
    }

    public function setInstallationId($installationId)
    {
        $this->_load();
        return parent::setInstallationId($installationId);
    }

    public function getInstallationId()
    {
        $this->_load();
        return parent::getInstallationId();
    }

    public function setFtpUser($ftpUser)
    {
        $this->_load();
        return parent::setFtpUser($ftpUser);
    }

    public function getFtpUser()
    {
        $this->_load();
        return parent::getFtpUser();
    }

    public function setup($options)
    {
        $this->_load();
        return parent::setup($options);
    }

    public function validate()
    {
        $this->_load();
        return parent::validate();
    }

    public function cleanup()
    {
        $this->_load();
        return parent::cleanup();
    }

    public function destroy()
    {
        $this->_load();
        return parent::destroy();
    }

    public function generateInstalationId()
    {
        $this->_load();
        return parent::generateInstalationId();
    }

    public function setFqdn()
    {
        $this->_load();
        return parent::setFqdn();
    }

    public function getFqdn()
    {
        $this->_load();
        return parent::getFqdn();
    }

    public function setBaseDn()
    {
        $this->_load();
        return parent::setBaseDn();
    }

    public function getBaseDn()
    {
        $this->_load();
        return parent::getBaseDn();
    }

    public function getDomainAliases()
    {
        $this->_load();
        return parent::getDomainAliases();
    }

    public function getDomains()
    {
        $this->_load();
        return parent::getDomains();
    }

    public function mapDomain(\Cx\Core_Modules\MultiSite\Model\Entity\Domain $domain)
    {
        $this->_load();
        return parent::mapDomain($domain);
    }

    public function unMapDomain($domain)
    {
        $this->_load();
        return parent::unMapDomain($domain);
    }

    public function setupLicense($options)
    {
        $this->_load();
        return parent::setupLicense($options);
    }

    public function initializeLanguage()
    {
        $this->_load();
        return parent::initializeLanguage();
    }

    public function setupTheme($websiteThemeId)
    {
        $this->_load();
        return parent::setupTheme($websiteThemeId);
    }

    public function setupFtpAccount($websiteName)
    {
        $this->_load();
        return parent::setupFtpAccount($websiteName);
    }

    public function generatePasswordRestoreUrl()
    {
        $this->_load();
        return parent::generatePasswordRestoreUrl();
    }

    public function generateVerificationUrl()
    {
        $this->_load();
        return parent::generateVerificationUrl();
    }

    public function generateAuthToken()
    {
        $this->_load();
        return parent::generateAuthToken();
    }

    public function generateAccountPassword()
    {
        $this->_load();
        return parent::generateAccountPassword();
    }

    public function __toString()
    {
        $this->_load();
        return parent::__toString();
    }

    public function __get($name)
    {
        $this->_load();
        return parent::__get($name);
    }

    public function getComponentController()
    {
        $this->_load();
        return parent::getComponentController();
    }

    public function setVirtual($virtual)
    {
        $this->_load();
        return parent::setVirtual($virtual);
    }

    public function isVirtual()
    {
        $this->_load();
        return parent::isVirtual();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'name', 'creationDate', 'codeBase', 'language', 'status', 'websiteServiceServerId', 'secretKey', 'ipAddress', 'ownerId', 'themeId', 'installationId', 'ftpUser', 'websiteServiceServer', 'domains');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}