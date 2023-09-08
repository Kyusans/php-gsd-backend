<?php 
    include "headers.php";

    class User{
        function login($json){
            include "connection.php";
            //{"username":"kobi", "password":"kobi123"}

            $json = json_decode($json, true);
            $userId = $json["userId"];
            $password = $json["password"];

            $sql = "SELECT * FROM tblusers WHERE user_userId = :userId AND user_password = :password";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":password", $password);
            $returnValue = 0;

            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                    $returnValue = json_encode($rs);
                }
            }
            return $returnValue;
        }
        function addLocation($json){
            include "connection.php";
            $json = json_decode($json, true);
            $location = $json["location"];
            $categoryId = $json["categoryId"];
            $sql = "INSERT INTO tbllocation(location_name, location_categoryId) VALUES(:location, :categoryId)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":location", $location);
            $stmt->bindParam(":categoryId", $categoryId);
            $returnValue = 0;

            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                   $returnValue = 1;
                }
            }
            return $returnValue;
        }
        function addLocationCategory($json){
            include "connection.php";
            $json = json_decode($json, true);
            $locationCategory = $json["locationCategory"];
            $sql = "INSERT INTO tbllocationcategory(locCateg_name) VALUES(:locationCategory)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":locationCategory", $locationCategory);
            $returnValue = 0;

            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                   $returnValue = 1;
                }
            }
            return $returnValue;
        }
        

        function getLocationCategory(){
            include "connection.php";
            $sql = "SELECT * FROM tbllocationcategory";
            $stmt = $conn->prepare($sql);
            $returnValue = 0;
            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $returnValue = json_encode($rs);
                }
            }
            return $returnValue;
        }

        function getLocations($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT location_name FROM tbllocation WHERE location_categoryId = :categoryId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":categoryId", $json["categoryId"]);
            $returnValue = 0;
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }
    }

    $json = isset($_POST["json"]) ? $_POST["json"] : "0";
    $operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";

    $user = new User();

    switch($operation){
        case "login":
            echo $user->login($json);
            break;
        case "addLocation":
            echo $user->addLocation($json);
            break;
        case "addLocationCategory":
            echo $user->addLocationCategory($json);
            break;
        case "getLocationCategory":
            echo $user->getLocationCategory();
            break;
        case "getLocations":
            echo $user->getLocations($json);
            break;
    }
?>