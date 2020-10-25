<?php declare(strict_types=1);
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

namespace KuboPlugin\User\UserPermission;

use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;
use Samshal\Acl\Acl as AclManager;

/**
 * class Acl.
 *
 * Acl Controller
 *
 * @author Samuel Adeshina <samueladeshina73@gmail.com>
 * @since v0.0.1 25/10/2020 18:11
 */
class Acl
{
    protected const DEFAULT_PERMISSION = "access";

    public static function load(string $kycGroup, string $accountType)
    {
        $query = "SELECT * FROM Users.AclEndPointRules WHERE (AccountType='$accountType' OR AccountType IS NULL) AND (KycGroup='$kycGroup' OR KycGroup IS NULL) ORDER BY AccountType DESC;";

        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $acl = new AclManager();
        $acl->addPermission(self::DEFAULT_PERMISSION);

        foreach ($result as $rule){
            $roleString = $rule["AccountType"]."_".$rule["KycGroup"];
            $acl->addRole($roleString);
            
            $acl->addResource($rule["EndPoint"]);

            $can = $rule["EndPointRule"] == 1 ? "can" : "cannot";
            $acl->{$roleString}->{$can}->{self::DEFAULT_PERMISSION}($rule["EndPoint"]);
        }

        return $acl;
    }

    private static function convertEndPointToAclFormat(string $module, string $resource, string $action){
        $aclFormat = [];

        $module = str_replace("-", "_", $module);
        $resource = str_replace("-", "_", $resource);
        $action = str_replace("-", "_", $action);
        
        $aclFormat[] = implode("__", [$module, $resource, $action]);
        $aclFormat[] = implode("__", [$module, $resource]);
        $aclFormat[] = $module;

        return $aclFormat;
    }

    public static function checkPermission(int $userId, array $data){
        $permission = ["status"=>false];

        $query = "SELECT b.GroupName FROM Users.Account a INNER JOIN Users.KycTypeGroups b ON a.KycGroupId = b.GroupId WHERE a.UserId=$userId";
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (!isset($result[0]["GroupName"])){
            return $permission;
        }

        $kycGroup = $result[0]["GroupName"];
        $accountType = $data["accountType"];
        $endpoints = self::convertEndPointToAclFormat($data["endpoint"][0],$data["endpoint"][1],$data["endpoint"][2]);
        $roleString = $accountType."_".$kycGroup;

        $acl = self::load($kycGroup, $accountType);

        $found = false;
        foreach ($endpoints as $endpoint){
            try{
                $perm = $acl->getPermissionStatus($roleString, self::DEFAULT_PERMISSION, $endpoint);
                $found = true;
            }
            catch(\Exception $e){
                $perm = false;
            }

            if ($perm == true){
                $permission["status"] = true;
                break;
            }

            continue;
        }

        if (!$found){ //load permissions from general users registry (roles are stored as an underscore '_')
            foreach ($endpoints as $endpoint){
                try{
                    $perm = $acl->getPermissionStatus("_", self::DEFAULT_PERMISSION, $endpoint);
                }
                catch(\Exception $e){
                    $perm = false;
                }

                if ($perm){
                    $permission["status"] = true;
                    break;
                }

                continue;
            }
        }

        return $permission;
    }
}