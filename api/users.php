<?php
include "headers.php";

class User
{
    function login($json)
    {
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

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            } else {
                $returnValue = adminLogin($json);
            }
        }
        return $returnValue;
    }

    function addComplaint($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $date = getCurrentDate();
        $endDate = $json["endDate"];
        
        if ($endDate . ' 23:59:59' < $date) {
            return 5;
        }
        
        $returnValueImage = uploadImage();
        // return $returnValueImage;

        switch ($returnValueImage) {
            case 2:
                // You cannot Upload files of this type!
                return 2;
            case 3:
                // There was an error uploading your file!
                return 3;
            case 4:
                // Your file is too big (25mb maximum)
                return 4;
            default:
                break;
        }

        $sql = "INSERT INTO tblcomplaints(comp_clientId, comp_locationId, comp_subject, comp_description, comp_locationCategoryId, comp_date, comp_end_date, comp_image) ";
        $sql .= "VALUES(:clientId, :locationId, :subject, :description, :locationCategoryId, :date, :endDate, :image)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":clientId", $json["clientId"]);
        $stmt->bindParam(":locationId", $json["locationId"]);
        $stmt->bindParam(":subject", $json["subject"]);
        $stmt->bindParam(":description", $json["description"]);
        $stmt->bindParam(":locationCategoryId", $json["locationCategoryId"]);
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->bindParam(":image", $returnValueImage);

        $returnValue = 0;
        $stmt->execute();
        $returnValue = $stmt->rowCount() > 0 ? 1 : 0;
        return $returnValue;
    }


    function getComplaints($json)
    {
        // {"userId": 1}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT * FROM tblcomplaints WHERE comp_clientId = :userId ORDER BY comp_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":userId", $json["userId"]);
        $returnValue = 0;
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function addComment($json)
    {
        //{"compId": 1, "userId":"2", "commentText" : "humana nani"}
        include "connection.php";
        try {
            $conn->beginTransaction();
            $conn->commit();
            $json = json_decode($json, true);
            $date = getCurrentDate();
            $sql = "INSERT INTO tblcomments(comment_complaintId, comment_userId, comment_commentText, comment_date) ";
            $sql .= "VALUES(:compId, :userId, :commentText, :date)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':compId', $json["compId"]);
            $stmt->bindParam(':userId', $json["userId"]);
            $stmt->bindParam(':commentText', $json["commentText"]);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $sql = "UPDATE tblcomplaints SET comp_lastUser = :fullName WHERE comp_id = :compId";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':compId', $json["compId"]);
                $stmt->bindParam(":fullName", $json["fullName"]);
                $stmt->execute();
            } else {
                $conn->rollBack();
                return 0;
            }
            return 1;
        } catch (Exception $e) {
            $conn->rollBack();
            return 0;
        }

        // return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function getComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT c.comment_commentText, c.comment_date, a.full_name, a.user_id ";
        $sql .= "FROM vwusers as a ";
        $sql .= "INNER JOIN tblcomments as c ON c.comment_userId = a.user_id ";
        $sql .= "WHERE c.comment_complaintId = :compId ORDER BY c.comment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":compId", $json["compId"]);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function insertToken($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tblusers SET user_token = :token WHERE user_id = :userId;";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $json['token']);
        $stmt->bindParam(':userId', $json['userId']);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function changePassword($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tblclients set fac_password = :password WHERE fac_code = :userId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":userId", $json["userId"]);
        $stmt->bindParam(":password", $json["password"]);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $returnValue = 1;
        } else {
            $returnValue = changeUserPassword($json);
        }
        return $returnValue;
    }

    function getCurrentPassword($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT fac_password from tblclients WHERE fac_code = :userId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":userId", $json["userId"]);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            } else {
                $jsonEncoded = json_encode($json);
                $returnValue = getCurrentUserPassword($jsonEncoded);
            }
        }
        return $returnValue;
    }

    function getSelectedComplaint($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT * FROM tblcomplaints WHERE comp_id = :compId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":compId", $json["compId"]);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function updateTicket($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $date = getCurrentDate();
        $endDate = $json["endDate"];
        
        if ($endDate . ' 23:59:59' < $date) {
            return 5;
        }

        $sql = "UPDATE tblcomplaints SET comp_subject = :subject, comp_locationId = :locationId, comp_locationCategoryId = :locationCategoryId, comp_end_date = :endDate, comp_description = :description ";
        $sql .= "WHERE comp_id = :compId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":compId", $json["compId"]);
        $stmt->bindParam(":locationId", $json["locationId"]);
        $stmt->bindParam(":subject", $json["subject"]);
        $stmt->bindParam(":description", $json["description"]);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->bindParam(":locationCategoryId", $json["locationCategoryId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function getSelectedStatus($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT * FROM tblcomplaints WHERE comp_clientId = :userId AND comp_status = :status ORDER BY comp_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":userId", $json["userId"]);
        $stmt->bindParam(":status", $json["status"]);
        $returnValue = 0;
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }
} //User

function uploadImage()
{
    $file = $_FILES['file'];
    // print_r($file);
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    // $fileType = $_FILES['file']['type'];

    $fileExt = explode(".", $fileName);
    $fileActualExt = strtolower(end($fileExt));

    $allowed = array("jpg", "jpeg", "png");

    if (in_array($fileActualExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 25000000) {
                $fileNameNew = uniqid("", true) . "." . $fileActualExt;
                $fileDestination =  'images/' . $fileNameNew;
                move_uploaded_file($fileTmpName, $fileDestination);
                return $fileNameNew;
            } else {
                return 4;
            }
        } else {
            return 3;
        }
    } else {
        return 2;
    }
}


function getCurrentUserPassword($json)
{
    include "connection.php";
    $json = json_decode($json, true);
    $sql = "SELECT user_password from tblusers WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $json["userId"]);
    $returnValue = 0;
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
    }
    return $returnValue;
}

function changeUserPassword($json)
{
    include "connection.php";
    $jsonString = json_encode($json);
    $decodedJson = json_decode($jsonString, true);
    $userId = $decodedJson["userId"];
    $password = $decodedJson["password"];
    $sql = "UPDATE tblusers set user_password = :password WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $userId);
    $stmt->bindParam(":password", $password);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? 1 : 0;
}

function getCurrentDate()
{
    $today = new DateTime("now", new DateTimeZone('Asia/Manila'));
    return $today->format('Y-m-d H:i:s');
}

function getAdminToken()
{
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

function adminLogin($json)
{
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
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
    }
    return $returnValue;
}

$json = isset($_POST["json"]) ? $_POST["json"] : "0";
$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$user = new User();

switch ($operation) {
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
        echo getAdminToken();
        break;
    case "changePassword":
        echo $user->changePassword($json);
        break;
    case "getCurrentPassword":
        echo $user->getCurrentPassword($json);
        break;
    case "getSelectedComplaint":
        echo $user->getSelectedComplaint($json);
        break;
    case "updateTicket":
        echo $user->updateTicket($json);
        break;
    case "getSelectedStatus":
        echo $user->getSelectedStatus($json);
        break;
}
