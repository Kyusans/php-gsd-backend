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

        function addComplaint($json){
            include "connection.php";
            require_once "sendNotification.php";
            $json = json_decode($json, true);
            // {"clientId":"1", "locationId":"1", "subject":"guba aircon", "description":"nibuto ang aircon lmao", "status":"1", "locationCategoryId": "1"}
            $sql = "INSERT INTO tblcomplaints(comp_clientId, comp_locationId, comp_subject, comp_description, comp_locationCategoryId) ";
            $sql .= "VALUES(:clientId, :locationId, :subject, :description, :locationCategoryId)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":clientId", $json["clientId"]);
            $stmt->bindParam(":locationId", $json["locationId"]);
            $stmt->bindParam(":subject", $json["subject"]);
            $stmt->bindParam(":description", $json["description"]);
            $stmt->bindParam(":locationCategoryId", $json["locationCategoryId"]);
            $returnValue = 0;
            $stmt->execute();
            $returnValue = $stmt->rowCount() > 0 ? 1 : 0; 
            if($returnValue == 1){
                $token = getAdminToken();
                $notification = new Notification();
                $notification->sendNotif($token, $json["subject"], "New complaint ticket");
            }
            return $returnValue;
        }

        function getComplaints($json){
            // {"userId": 1}
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT * FROM tblcomplaints WHERE comp_clientId = :userId ORDER BY comp_id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userId", $json["userId"]);
            $returnValue = 0;
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }

        function addComment($json){
            //{"compId": 1, "userId":"2", "commentText" : "humana nani"}
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "INSERT INTO tblcomments(comment_complaintId, comment_userId, comment_commentText) ";
            $sql .= "VALUES(:compId, :userId, :commentText)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':compId', $json["compId"]);
            $stmt->bindParam(':userId', $json["userId"]);
            $stmt->bindParam(':commentText', $json["commentText"]);
            $stmt->execute();
            return $stmt->rowCount() > 0 ? 1 : 0;
        }

        function getComment($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT c.comment_commentText, c.comment_date, a.full_name ";
            $sql .= "FROM vwusers as a ";
            $sql .= "INNER JOIN tblcomments as c ON c.comment_userId = a.user_id ";
            $sql .= "WHERE c.comment_complaintId = :compId ORDER BY c.comment_id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":compId", $json["compId"]);
            $stmt->execute();
            $returnValue = 0;
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }

        function insertToken($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "UPDATE tblusers SET user_token = :token WHERE user_id = :userId;";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':token', $json['token']);
            $stmt->bindParam(':userId', $json['userId']);
            $stmt->execute();
            return $stmt->rowCount() > 0 ? 1 : 0;
        }
    }//User

    function getAdminToken() {
        include 'connection.php';
        $sql = "SELECT user_token FROM tblusers WHERE user_level = 100";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['user_token'];
        }
        return 0; 
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
        case "addComplaint":
            echo $user->addComplaint($json);
            break;
        case "getComplaints":
            echo $user->getComplaints($json);
            break;
        case "addComment":
            echo $user->addComment($json);
            break;
        case "getComment":
            echo $user->getComment($json);
            break;
        case "insertToken":
            echo $user->insertToken($json);
            break;
        case "getAdminToken":
            echo getAdminToken($json);
    }
?>