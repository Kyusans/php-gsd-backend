<?php 
    include "headers.php";

    class User{
        function login($json){
            include "connection.php";
            
            //{"username":"kobi", "password":"kobi123"}

            $json = json_decode($json, true);
            $userId = $json["userId"];
            $password = $json["password"];

            $sql = "SELECT * FROM tblusers WHERE user_userId = :username AND user_password = :password";

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
            $userId = $json["userId"];
            $location = $json["location"];
            $sql = "INSERT INTO tbllocation(loc_name, loc_userId) VALUES(:location, :userId)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":location", $location);
            $returnValue = 0;

            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                   $returnValue = 1;
                }
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
    }
?>