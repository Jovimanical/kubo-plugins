<?php declare (strict_types=1);
/**
 * Visitor Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 */

namespace KuboPlugin\SpatialEntity;

/**
 * class KuboPlugin\SpatialEntity\Entity
 *
 * Entity Visitor
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 09:11
 */
class Entity {
	public function newEntity(array $data){
        $return_result = Entity\Entity::newEntity($data);

        return $return_result;
    }
    
    public function viewEntitiesByType(array $data){
        return Entity\Entity::viewEntitiesByType($data);
    }
    
    public function viewEntityChildren(array $data){
        return Entity\Entity::viewEntityChildren($data);
    }
    
    public function viewEntityTypes(){
        return Entity\Entity::viewEntityTypes();
    }
    
    public function viewEntity(array $data){
        return Entity\Entity::viewEntity($data);
    }
}