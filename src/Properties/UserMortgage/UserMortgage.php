<?php declare (strict_types=1);
/**
 * Controller Class.
 *
 * This file is part of Project Kubo, please read the documentation
 * available in the root level of this project
 *
 * @license MIT
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 *
 */

namespace KuboPlugin\Properties\UserMortgage;


use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;


/**
 * class KuboPlugin\Properties\UserMortgage
 *
 * Mortgages Visitor
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class UserMortgage {
	public static function newMortgage(array $data){
        if($empty($data)){
            return "Parameter not set";
        }
        $propertyId = $data["property_id"] ?? 0;
        $property_name =  $data["property_name"] ?? '';
        $property_address =  $data["property_address"] ?? '';
        $property_params = $data["property_params"] ?? '';
        $mortgage_id =  $data["mortgage_id"] ?? time().rand(100,900);
        $mortgagee_name =  $data["mortgagee_name"] ?? '';
        $user_params = $data["user_params"] ?? '';
        $mortgage_bank = $data["mortgage_bank"] ?? '';
        $employment_params = $data["employment_params"] ?? '';
        $state = $data["state"] ?? '';
        $txn_id = $data["txn_id"] ?? time().rand(1000,9000);
        $deal_id = time().rand(100000,900000);

        $inputData = [
            "MortgageId"=>$mortgage_id,
            "EmploymentParams"=>QB::wrapString($employment_params, "'"),
            "PropertyParams"=>QB::wrapString($property_params, "'"),
            "UserParams"=>QB::wrapString($user_params, "'"),
            "MortgageeName"=>QB::wrapString($mortgagee_name, "'"),
            "PropertyId"=>$propertyId,
            "PropertyName"=>QB::wrapString($property_name, "'"),
            "PropertyAddress"=>QB::wrapString($property_address, "'"),
            "MortgageBank"=>QB::wrapString($mortgage_bank, "'"),
            "State"=>QB::wrapString($state, "'"),
            "TxnId"=>$txn_id,
            "DealId"=>$deal_id
        ];

        $result = DBQueryFactory::insert("[Properties].[Mortgages]", $inputData, false);

        return $result;
	}

    public static function viewMortgages(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Mortgages  WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) ORDER BY Id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public static function viewMortgagesBySeven(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }

       $fetch = "FIRST";
       $offset = 0;

       if($data['offset'] != 0){
           $fetch = "NEXT";
           $offset = $data['offset'];
       }

       $fromDate = date('Y-m-d');
       $toDate = date('Y-m-d', strtotime('-7 days', strtotime(date('Y-m-d'))));



       $query = "SELECT * FROM Properties.Mortgages WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateStarted  >= $fromDate AND DateStarted  <  $toDate ORDER BY Id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
       $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

       return $result;
    }

    public static function viewMortgagesByThirty(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Properties.Mortgages WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateStarted  >= $fromDate AND DateStarted  <  $toDate ORDER BY Id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public static function viewMortgagesByNinety(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
       $offset = 0;

       if($data['offset'] != 0){
           $fetch = "NEXT";
           $offset = $data['offset'];
       }

       $fromDate = date('Y-m-d');
       $toDate = date('Y-m-d', strtotime('-90 days', strtotime(date('Y-m-d'))));



       $query = "SELECT * FROM Properties.Mortgages WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateStarted  >= $fromDate AND DateStarted  <  $toDate ORDER BY Id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
       $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

       return $result;
    }


    public static function searchMortgages(int $userId,array $data){
        if($userId == 0 OR empty($data)){
            return "Parameters not set";
        }
        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Properties.Mortgages WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND Id LIKE '%$searchTerm%' OR PropertyName LIKE '%$searchTerm%' OR MortgageeName LIKE '%$searchTerm%' OR MortgageBank LIKE '%$searchTerm%' OR MonthlyPayment LIKE '%$searchTerm%' OR DownPayment LIKE '%$searchTerm%' ORDER BY Id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
}