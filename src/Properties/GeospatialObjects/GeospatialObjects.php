<?php declare (strict_types=1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 *
 */

namespace KuboPlugin\Properties\GeospatialObjects;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;

/**
 * class KuboPlugin\Properties\GeospatialObjects\GeospatialObjects
 *
 * Geospatial Objects Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 11/06/2021 09:33
 */
class GeospatialObjects {
	private static function serializeObject($object){
		return serialize($object);
	}

    private static function unserializeObject($str){
        return html_entity_decode(unserialize($str));
    }

	public static function newObject(array $data){
		$user = $data["user"];
		$object = $data["object"];

		$object = self::serializeObject($object);

		$query = "INSERT INTO Properties.GeospatialObject (UserId, GeospatialObject) VALUES ($user, '$object')";

		$result = DBConnectionFactory::getConnection()->exec($query);

		return $result;
	}

    public static function getObject(int $objectId){
        $query = "SELECT * FROM Properties.GeospatialObject WHERE ObjectId = $objectId";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $result = $result[0] ?? [];

        if (isset($result["GeospatialObject"])){
            $result["GeospatialObject"] = self::unserializeObject($result["GeospatialObject"]);
        }

        return $result;
    }
}