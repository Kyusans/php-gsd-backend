<?php 
    include "headers.php";

    class User{
        function login($json){
            include "connection.php";
            //{"userId":"00-099-F", "password":"phinma-coc-cite"}

            $json = json_decode($json, true);
            $userId = $json["userId"];
            $password = $json["password"];

            $sql = "SELECT * FROM tblclients WHERE fac_code = :userId AND fac_password = :password";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":password", $password);
            $returnValue = 0;

            if($stmt->execute()){
                if($stmt->rowCount() > 0){
                    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                    $returnValue = json_encode($rs);
                }else{
                    $returnValue = adminLogin($json);
                }
            }
            return $returnValue;
        }
    }

    function adminLogin($json){
        include "connection.php";
        //{"userId":"admin", "password":"phinma-coc-cite"}
        $jsonString = json_encode($json);
        $decodedJson = json_decode($jsonString, true);
        $userId = $decodedJson["userId"];
        $password = $decodedJson["password"];
        $sql = "SELECT * FROM tblusers WHERE user_username = :userId AND user_password = :password";
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
    

    $json = isset($_POST["json"]) ? $_POST["json"] : "0";
    $operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";

    $user = new User();

    switch($operation){
        case "login":
            echo $user->login($json);
            break;
    }
?>