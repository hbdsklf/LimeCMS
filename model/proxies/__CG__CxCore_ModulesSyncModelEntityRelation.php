<?php

namespace Cx\Model\Proxies\__CG__\Cx\Core_Modules\Sync\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Relation extends \Cx\Core_Modules\Sync\Model\Entity\Relation implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function __get($name)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__get', array($name));

        return parent::__get($name);
    }





    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'id', 'lft', 'rgt', 'lvl', 'localFieldName', 'doSync', 'defaultEntityId', 'children', 'parent', 'relatedSync', 'foreignDataAccess', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'id', 'lft', 'rgt', 'lvl', 'localFieldName', 'doSync', 'defaultEntityId', 'children', 'parent', 'relatedSync', 'foreignDataAccess', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Relation $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setLft($lft)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLft', array($lft));

        return parent::setLft($lft);
    }

    /**
     * {@inheritDoc}
     */
    public function getLft()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLft', array());

        return parent::getLft();
    }

    /**
     * {@inheritDoc}
     */
    public function setRgt($rgt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRgt', array($rgt));

        return parent::setRgt($rgt);
    }

    /**
     * {@inheritDoc}
     */
    public function getRgt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRgt', array());

        return parent::getRgt();
    }

    /**
     * {@inheritDoc}
     */
    public function setLvl($lvl)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLvl', array($lvl));

        return parent::setLvl($lvl);
    }

    /**
     * {@inheritDoc}
     */
    public function getLvl()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLvl', array());

        return parent::getLvl();
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalFieldName($localFieldName)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLocalFieldName', array($localFieldName));

        return parent::setLocalFieldName($localFieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalFieldName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLocalFieldName', array());

        return parent::getLocalFieldName();
    }

    /**
     * {@inheritDoc}
     */
    public function setDoSync($doSync)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDoSync', array($doSync));

        return parent::setDoSync($doSync);
    }

    /**
     * {@inheritDoc}
     */
    public function getDoSync()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDoSync', array());

        return parent::getDoSync();
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultEntityId($defaultEntityId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDefaultEntityId', array($defaultEntityId));

        return parent::setDefaultEntityId($defaultEntityId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultEntityId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDefaultEntityId', array());

        return parent::getDefaultEntityId();
    }

    /**
     * {@inheritDoc}
     */
    public function addChildren(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addChildren', array($children));

        return parent::addChildren($children);
    }

    /**
     * {@inheritDoc}
     */
    public function addChild(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addChild', array($children));

        return parent::addChild($children);
    }

    /**
     * {@inheritDoc}
     */
    public function removeChild(\Cx\Core_Modules\Sync\Model\Entity\Relation $children)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeChild', array($children));

        return parent::removeChild($children);
    }

    /**
     * {@inheritDoc}
     */
    public function setChildren($children)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setChildren', array($children));

        return parent::setChildren($children);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getChildren', array());

        return parent::getChildren();
    }

    /**
     * {@inheritDoc}
     */
    public function setParent(\Cx\Core_Modules\Sync\Model\Entity\Relation $parent)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setParent', array($parent));

        return parent::setParent($parent);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getParent', array());

        return parent::getParent();
    }

    /**
     * {@inheritDoc}
     */
    public function setRelatedSync(\Cx\Core_Modules\Sync\Model\Entity\Sync $relatedSync)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRelatedSync', array($relatedSync));

        return parent::setRelatedSync($relatedSync);
    }

    /**
     * {@inheritDoc}
     */
    public function getRelatedSync()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRelatedSync', array());

        return parent::getRelatedSync();
    }

    /**
     * {@inheritDoc}
     */
    public function setForeignDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $foreignDataAccess)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setForeignDataAccess', array($foreignDataAccess));

        return parent::setForeignDataAccess($foreignDataAccess);
    }

    /**
     * {@inheritDoc}
     */
    public function getForeignDataAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getForeignDataAccess', array());

        return parent::getForeignDataAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function getComponentController()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getComponentController', array());

        return parent::getComponentController();
    }

    /**
     * {@inheritDoc}
     */
    public function setVirtual($virtual)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVirtual', array($virtual));

        return parent::setVirtual($virtual);
    }

    /**
     * {@inheritDoc}
     */
    public function isVirtual()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isVirtual', array());

        return parent::isVirtual();
    }

    /**
     * {@inheritDoc}
     */
    public function initializeValidators()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'initializeValidators', array());

        return parent::initializeValidators();
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'validate', array());

        return parent::validate();
    }

    /**
     * {@inheritDoc}
     */
    public function __call($methodName, $arguments)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__call', array($methodName, $arguments));

        return parent::__call($methodName, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__toString', array());

        return parent::__toString();
    }

}
