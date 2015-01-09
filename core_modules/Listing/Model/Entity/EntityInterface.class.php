<?php

/**
 * EntityInterface Class
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Model\Entity;

/**
 * EntityInterface Class
 * 
 * This class used to convert the entity objects into array and array
 * into entity objects
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_listing
 */
class EntityInterface implements Exportable, Importable
{

    protected $entityClass;

    /**
     * constructor
     */
    public function __construct()
    {
        
    }

    /**
     * This function is used to convert the array into entity objects.
     * 
     * @param  array $data
     * 
     * @return array return as entity object array
     */
    public function export($data) 
    {
        if (empty($data)) {
            return;
        }
        
        $em = \Env::get('em');
        $entityClassMetaData    = $em->getClassMetadata($this->entityClass);
        $repository             = $em->getRepository($this->entityClass);
        
        foreach ($data as $entityArray) {
            
            $entityObj = null;
            $primaryKeyName = $entityClassMetaData->getSingleIdentifierFieldName();

            if (!empty($entityArray[$primaryKeyName])) {
                $entityObj = $repository->findOneBy(array($primaryKeyName => $entityArray[$primaryKeyName]));
            }

            if (!$entityObj) {
                $entityObj = new $this->entityClass();
            }
            //get the association mappings
            $associationMappings = $entityClassMetaData->getAssociationMappings();
            //class methods
            $classMethods = get_class_methods($entityObj);

            foreach ($entityArray as $entityField => $entityValue) {
                $associationObj = null;
                
                if (!in_array('set' . ucfirst($entityField), $classMethods)) {
                    continue;
                }
                
                if ($entityClassMetaData->isSingleValuedAssociation($entityField)) {  
                    
                    $targetEntity = $associationMappings[$entityField]['targetEntity'];
                    
                    $mappingEntityField = $em->getClassMetadata($targetEntity)->getSingleIdentifierFieldName();
                    $mappingEntityValue = is_array($entityValue) ? $entityValue[$mappingEntityField] : $entityValue;                    
                    $associationObj = $em->getRepository($targetEntity)->findOneBy(array($mappingEntityField => $mappingEntityValue));
                    
                    if (!$associationObj) {
                        $associationObj = new $targetEntity();
                    }
                    foreach ($entityValue as $method => $value) {
                        $associationObj->{'set' . ucfirst($method)}($value);
                    }
                    $entityValue = $associationObj;
                }
                
                $entityObj->{'set' . ucfirst($entityField)}($entityValue);
                
            }
            $entities[] = $entityObj;
        }
        return $entities;
    }

    /**
     * set the entity class
     * 
     * @param string $entityClass
     */
    public function setEntityClass($entityClass) 
    {
        $this->entityClass = $entityClass;
    }

    /**
     * This function is used to convert the entity object into array.
     * 
     * @param  object $object entity object
     * @return array  return as array
     */
    public function import($object)
    {

        if (!is_object($object)) {
            return;
        }
            
        $em = \Env::get('em');
        $associationEntityColumns = array();

        $entityClassMetaData = $em->getClassMetadata(get_class($object));
        $associationMappings = $entityClassMetaData->getAssociationMappings();
        if (!empty($associationMappings)) {
            foreach ($associationMappings as $field => $associationMapping) {
                if ($entityClassMetaData->isSingleValuedAssociation($field) && in_array('get' . ucfirst($field), get_class_methods($object))) {
                    $associationObject = $object->{'get' . ucfirst($field)}();
                    if ($associationObject) {
                        //get association columns
                        $associationEntityColumn = $this->getColumnNamesByEntity($associationObject);
                        $associationEntityColumns[$field] = !\FWValidator::isEmpty($associationEntityColumn) ? $associationEntityColumn : '';
                    }
                }
            }
        }
        //get entity columns    
        $entityColumns = $this->getColumnNamesByEntity($object);
        $resultData = array_merge($entityColumns, $associationEntityColumns);
        $resultData['virtual'] = $object->isVirtual();

        return array($resultData);
    }
    
    /**
     * get column name and values form the given entity object
     * 
     * @param object $entityObject  
     * 
     * @return array
     */
    public function getColumnNamesByEntity($entityObject) 
    {
        $data = array();
        $entityClassMetaData  =  \Env::get('em')->getClassMetadata(get_class($entityObject));
        foreach ($entityClassMetaData->getColumnNames() as $column) {
            $field = $entityClassMetaData->getFieldName($column);
            $value = $entityClassMetaData->getFieldValue($entityObject, $field);
            if ($value instanceof \DateTime) {
                $value = $value->format('d.M.Y H:i:s');
            } elseif (is_array($value)) {
                $value = serialize($value);
            }
            $data[$field] = $value;
        }
        return $data;
    }

}
