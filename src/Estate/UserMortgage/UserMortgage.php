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

namespace KuboPlugin\Estate\UserMortgage;


use EmmetBlue\Core\Factory\DatabaseConnectionFactory as DBConnectionFactory;
use EmmetBlue\Core\Factory\DatabaseQueryFactory as DBQueryFactory;
use EmmetBlue\Core\Builder\QueryBuilder\QueryBuilder as QB;


/**
 * class KuboPlugin\Estate\UserMortgage
 *
 * Mortgages Visitor
 *
 * @author Sixtus Onumajuru <jigga.e10@gmail.com>
 * @since v0.0.1 18/10/2021 06:20
 */
class UserMortgage {
	public static function newMortgage(array $data){
        $propertyId = $data["property_id"] ?? null;
        $property_name =  $data["property_name"] ?? null;
        $property_address =  $data["property_address"] ?? null;
        $property_params = $data["property_params"] ?? [];
        $mortgage_id =  $data["mortgage_id"] ?? null;
        $mortgagee_name =  $data["mortgagee_name"] ?? null;
        $user_params = $data["user_params"] ?? [];
        $mortgage_bank = $data["mortgage_bank"] ?? null;
        $employment_params = $data["employment_params"] ?? [];
        $state = $data["state"] ?? null;
        $taxId = $data["tax_id"] ?? null;

        $inputData = [
            "mortgage_id"=>$mortgage_id,
            "employment_params"=>QB::wrapString($employment_params, "'"),
            "property_params"=>QB::wrapString($property_params, "'"),
            "user_params"=>QB::wrapString($user_params, "'"),
            "mortgagee_name"=>QB::wrapString($mortgagee_name, "'"),
            "property_id"=>$propertyId,
            "property_name"=>QB::wrapString($property_name, "'"),
            "property_address"=>QB::wrapString($property_address, "'"),
            "mortgage_bank"=>QB::wrapString($mortgage_bank, "'"),
            "state"=>QB::wrapString($state, "'"),
            "txn_id"=>$txn_id
        ];

        $result = DBQueryFactory::insert("[Estate].[Mortgages]", $inputData, false);

        return $result;
	}

    public static function viewMortgages(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Estate.Mortgages  WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) ORDER BY id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public static function viewMortgagesBySeven(int $userId,array $data){

       $fetch = "FIRST";
       $offset = 0;

       if($data['offset'] != 0){
           $fetch = "NEXT";
           $offset = $data['offset'];
       }

       $fromDate = date('Y-m-d');
       $toDate = date('Y-m-d', strtotime('-7 days', strtotime(date('Y-m-d'))));



       $query = "SELECT * FROM Estate.Mortgages WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND date_started  >= $fromDate AND date_started  <  $toDate ORDER BY id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
       $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

       return $result;
    }

    public static function viewMortgagesByThirty(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;

        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $fromDate = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d'))));


        $query = "SELECT * FROM Estate.Mortgages WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  <  $toDate ORDER BY id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public static function viewMortgagesByNinety(int $userId,array $data){
        $fetch = "FIRST";
       $offset = 0;

       if($data['offset'] != 0){
           $fetch = "NEXT";
           $offset = $data['offset'];
       }

       $fromDate = date('Y-m-d');
       $toDate = date('Y-m-d', strtotime('-90 days', strtotime(date('Y-m-d'))));



       $query = "SELECT * FROM Estate.Mortgages WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND DateCreated  >= $fromDate AND DateCreated  <  $toDate ORDER BY id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY";  // WHERE PropertyId IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $EnquiryId)
       $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

       return $result;
    }


    public static function searchMortgages(int $userId,array $data){
        $fetch = "FIRST";
        $offset = 0;
        $searchTerm = $data['searchTerm'];
        if($data['offset'] != 0){
            $fetch = "NEXT";
            $offset = $data['offset'];
        }

        $query = "SELECT * FROM Estate.Mortgages WHERE property_id IN (SELECT PropertyId FROM Properties.UserProperty WHERE UserId = $userId) AND WHERE id LIKE '%$searchTerm%' AND property_name LIKE '%$searchTerm%' AND mortgagee_name LIKE '%$searchTerm%' AND mortgage_bank LIKE '%$searchTerm%' AND monthly_payment LIKE '%$searchTerm%' AND down_payment LIKE '%$searchTerm%' ORDER BY id DESC OFFSET $offset ROWS FETCH $fetch 1000 ROWS ONLY"; 
        $result = DBConnectionFactory::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
}