<?php
include "headers.php";

class Admin
{
    function addLocation($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $location = $json["location"];
        $categoryId = $json["categoryId"];

        $checkDuplicateSql = "SELECT COUNT(*) as count FROM tbllocation WHERE location_name = :location";
        $checkDuplicateStmt = $conn->prepare($checkDuplicateSql);
        $checkDuplicateStmt->bindParam(":location", $location);
        $checkDuplicateStmt->execute();
        $duplicateCount = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($duplicateCount > 0) {
            return 2;
        }

        $sql = "INSERT INTO tbllocation(location_name, location_categoryId) VALUES(:location, :categoryId)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":categoryId", $categoryId);
        $returnValue = 0;

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $returnValue = 1;
            }
        }

        return $returnValue;
    }

    function addLocationCategory($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $locationCategory = $json["locationCategory"];

        $checkDuplicateSql = "SELECT COUNT(*) as count FROM tbllocationcategory WHERE locCateg_name = :locationCategory";
        $checkDuplicateStmt = $conn->prepare($checkDuplicateSql);
        $checkDuplicateStmt->bindParam(":locationCategory", $locationCategory);
        $checkDuplicateStmt->execute();
        $duplicateCount = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($duplicateCount > 0) {
            return 2;
        }

        $sql = "INSERT INTO tbllocationcategory(locCateg_name) VALUES(:locationCategory)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":locationCategory", $locationCategory);
        $returnValue = 0;

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $returnValue = 1;
            }
        }

        return $returnValue;
    }

    function getAllLocation()
    {
        include "connection.php";
        $sql = "SELECT * FROM tbllocation ORDER BY location_id";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function getLocationCategory()
    {
        include "connection.php";
        $sql = "SELECT * FROM tbllocationcategory ORDER BY locCateg_name";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function getLocations($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT tbllocation.location_id, tbllocation.location_name, tbllocationcategory.locCateg_name, tbllocationcategory.locCateg_id ";
        $sql .= "FROM tbllocation INNER JOIN tbllocationcategory ";
        $sql .= "ON tbllocation.location_categoryId = tbllocationcategory.locCateg_id ";
        $sql .= "WHERE tbllocationcategory.locCateg_id = :categoryId ORDER BY tbllocation.location_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":categoryId", $json["categoryId"]);
        $returnValue = 0;
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function getAllTickets()
    {
        include "connection.php";
        $sql = "SELECT a.*, b.joStatus_name 
            FROM tblcomplaints as a 
            INNER JOIN tbljoborderstatus as b ON a.comp_status = b.joStatus_id 
            ORDER BY a.comp_id DESC";

        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function getSelectedTicket($json)
    {
        // {"compId" : 2}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT c.comp_id, c.comp_subject, c.comp_description, c.comp_image, c.comp_date, c.comp_end_date, cl.fac_name, cl.fac_id, loc.location_name, lc.locCateg_name ";
        $sql .= "FROM tblcomplaints AS c ";
        $sql .= "INNER JOIN tblclients AS cl ON c.comp_clientId = cl.fac_id ";
        $sql .= "INNER JOIN tbllocation AS loc ON c.comp_locationId = loc.location_id ";
        $sql .= "INNER JOIN tbllocationcategory AS lc ON comp_locationCategoryId = lc.locCateg_id ";
        $sql .= "WHERE c.comp_id = :compId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":compId", $json["compId"]);
        $returnValue = 0;
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function getAllPersonnel()
    {
        include "connection.php";
        $sql = "SELECT * FROM tblusers WHERE user_level = 90 ORDER BY user_username";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function getPriority()
    {
        include "connection.php";
        $sql = "SELECT * FROM tblpriority";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function submitJobOrder($json)
    {
        include "connection.php";
        include "users.php";
        require_once "sendNotification.php";
        $json = json_decode($json, true);
        $date = getCurrentDate();
        $master = $json['master'];
        $detail = $json['detail'];
        $jobPersonnelId = $detail['jobPersonnelId'];

        try {
            $conn->beginTransaction();
            $sql = "INSERT INTO tbljoborders(job_complaintId, job_title, job_description, job_priority, job_createdBy, job_createDate) ";
            $sql .= "VALUES(:complaintId, :jobTitle, :jobDescription, :jobPriority, :jobCreatedBy, :date)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":complaintId", $master['ticketNumber']);
            $stmt->bindParam(":jobTitle", $master['subject']);
            $stmt->bindParam(":jobDescription", $master['description']);
            $stmt->bindParam(":jobPriority", $master['priority']);
            $stmt->bindParam(":jobCreatedBy", $master['jobCreatedBy']);
            $stmt->bindParam(":date", $date);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $newId = $conn->lastInsertId();
                $sql = "INSERT INTO tbljoborderpersonnel(joPersonnel_joId, joPersonnel_userId) ";
                $sql .= "VALUES(:jobId, :userId)";
                $stmt = $conn->prepare($sql);
                foreach ($jobPersonnelId as $userId) {
                    $stmt->bindParam(":jobId", $newId);
                    $stmt->bindParam(":userId", $userId);
                    $stmt->execute();

                    $tokens = getTokenForUserId($userId);
                    foreach ($tokens as $token) {
                        $notification = new Notification();
                        $notification->sendNotif($token, $master['subject'], "New job ticket");
                    }
                }
                if ($stmt->rowCount() > 0) {
                    $sql = "UPDATE tblcomplaints SET comp_status = 2 WHERE comp_id = :compId";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":compId", $master["ticketNumber"]);
                    $stmt->execute();

                    $defaultComment = "Hi Maam/Sir! A job order has been created for this ticket. A GSD personnel is going to contact you soon!";
                    $date = getCurrentDate();
                    $sql = "INSERT INTO tblcomments(comment_complaintId, comment_userId, comment_commentText, comment_date) ";
                    $sql .= "VALUES(:compId, :userId, :comment, :date)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':compId', $master["ticketNumber"]);
                    $stmt->bindParam(':userId', $master["jobCreatedBy"]);
                    $stmt->bindParam(':comment', $defaultComment);
                    $stmt->bindParam(':date', $date);
                    $stmt->execute();
                    //{"compId": 1, "userId":"2", "commentText" : "humana nani"}
                    if ($stmt->rowCount() > 0 && $master["additionalComment"] !== null) {
                        $date = getCurrentDate();
                        $sql = "INSERT INTO tblcomments(comment_complaintId, comment_userId, comment_commentText, comment_date) ";
                        $sql .= "VALUES(:compId, :userId, :commentText, :date)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':compId', $master["ticketNumber"]);
                        $stmt->bindParam(':userId', $master["jobCreatedBy"]);
                        $stmt->bindParam(':commentText', $master["additionalComment"]);
                        $stmt->bindParam(':date', $date);
                        $stmt->execute();
                    }
                }
            }
            $conn->commit();
            return 1;
        } catch (Exception $e) {
            $conn->rollBack();
            return 0;
        }
    }

    function getJobDetails($json)
    {
        include "connection.php";
        // {"compId": 69}
        $json = json_decode($json, true);
        $sql = "SELECT a.comp_subject, a.comp_image, a.comp_id, a.comp_closedBy, a.comp_end_date, a.comp_date_closed, a.comp_remark, b.location_name, c.locCateg_name, d.job_id, d.job_description, d.job_createDate, e.priority_name, f.fac_name, g.user_full_name, h.joStatus_name, h.joStatus_id 
        FROM tblcomplaints as a 
        INNER JOIN tbllocation as b ON a.comp_locationId = b.location_id 
        INNER JOIN tbllocationcategory as c ON a.comp_locationCategoryId = c.locCateg_id 
        INNER JOIN tbljoborders as d ON d.job_complaintId = a.comp_id 
        INNER JOIN tblpriority as e ON d.job_priority = e.priority_id 
        INNER JOIN tblclients as f ON f.fac_id = a.comp_clientId 
        INNER JOIN tblusers as g ON g.user_id = d.job_createdBy 
        INNER JOIN tbljoborderstatus as h ON a.comp_status = h.joStatus_id 
        WHERE a.comp_id = :compId";
        // echo "sql: " . $sql;
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":compId", $json["compId"]);
        $returnValue = 0;
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetch(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function getTicketsByStatus($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT a.*, b.joStatus_name ";
        $sql .= "FROM tblcomplaints as a  ";
        $sql .= "INNER JOIN tbljoborderstatus as b ON a.comp_status = b.joStatus_id ";
        $sql .= "WHERE comp_status = :status ";
        $sql .= "ORDER BY a.comp_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":status", $json["compStatus"]);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function getAssignedPersonnel($json)
    {
        // {"jobId":3}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT b.user_full_name 
            FROM tbljoborderpersonnel as a 
            INNER JOIN tblusers as b ON a.joPersonnel_userId = b.user_id 
            WHERE a.joPersonnel_joId = :jobId  
            ORDER BY user_full_name ";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":jobId", $json["jobId"]);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function getReport()
    {
        include "connection.php";
        $sql = "SELECT a.comp_subject AS Subject, b.location_name AS Location, 
		GROUP_CONCAT(DISTINCT CONCAT(' ', e.user_full_name)) AS Personnel, h.operation_name AS Operation, 
        GROUP_CONCAT(DISTINCT CONCAT(' ', j.equip_name)) AS Equipment, 
        f.fac_name as Submitted_By, g.joStatus_name AS Status, a.comp_date AS Date 
        FROM tblcomplaints as a 
        INNER JOIN tbllocation as b ON b.location_id = a.comp_locationId 
        INNER JOIN tbljoborders as c ON c.job_complaintId = a.comp_id 
        INNER JOIN tbljoborderpersonnel as d ON d.joPersonnel_joId = c.job_id 
        INNER JOIN tblusers as e ON e.user_id = d.joPersonnel_userId 
        INNER JOIN tblclients as f ON f.fac_id = a.comp_clientId 
        INNER JOIN tbljoborderstatus as g ON g.joStatus_id = a.comp_status 
        INNER JOIN tbloperation as h ON h.operation_id = a.comp_operation 
        INNER JOIN tbljobequipment as i ON i.joEquipment_compId = a.comp_id  
        INNER JOIN tblequipment as j ON j.equip_id = i.joEquipment_equipId 
        GROUP BY a.comp_id 
        ORDER BY comp_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $returnValue = 0;

        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }

        return $returnValue;
    }

    function getOperation()
    {
        include "connection.php";
        $sql = "SELECT * FROM tbloperation";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $returnValue = 0;
        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }
        return $returnValue;
    }

    function reopenJob($json)
    {
        include "connection.php";
        include "users.php";
        $json = json_decode($json, true);

        try {
            $conn->beginTransaction();

            $sql = "UPDATE tblcomplaints SET comp_status = 2 WHERE comp_id = :compId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":compId", $json["compId"]);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $date = getCurrentDate();
                $sql2 = "INSERT INTO tblcomments(comment_complaintId, comment_userId, comment_commentText, comment_date) ";
                $sql2 .= "VALUES(:compId, :userId, :comment, :date)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bindParam(':compId', $json['compId']);
                $stmt2->bindParam(':userId', $json['userId']);
                $stmt2->bindParam(':comment', $json['comment']);
                $stmt2->bindParam(':date', $date);
                $stmt2->execute();
            }
            $conn->commit();
            return 1;
        } catch (Exception $e) {
            $conn->rollBack();
            return $e->getMessage();
        }
    }

    function updateLocation($json)
    {
        // {"newLocationName":"CL3", "locationId": 4}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbllocation SET location_name = :newLocationName WHERE location_id = :locationId ";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":newLocationName", $json["newLocationName"]);
        $stmt->bindParam(":locationId", $json["locationId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function deleteLocation($json)
    {
        // {"locationId": 4}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "DELETE FROM tbllocation WHERE location_id = :locationId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":locationId", $json["locationId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function updateLocationCategory($json)
    {
        // {"newLocationCategName":"Computer Labs", "locationCategId": 1}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbllocationcategory SET locCateg_name = :newLocationCategName WHERE locCateg_id = :locationCategId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":newLocationCategName", $json["newLocationCategName"]);
        $stmt->bindParam(":locationCategId", $json["locationCategId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function addPersonnel($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $checkSql = "SELECT COUNT(*) FROM tblusers WHERE user_username = :username";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(":username", $json["username"]);
        $checkStmt->execute();
        $usernameExists = $checkStmt->fetchColumn();
        if ($usernameExists > 0) {
            return 2;
        }
        $insertSql = "INSERT INTO tblusers (user_username, user_password, user_full_name, user_email, user_contact, user_level)  
                          VALUES (:username, :password, :userFullname, :email, :contact, 90)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bindParam(":username", $json["username"]);
        $insertStmt->bindParam(":password", $json["password"]);
        $insertStmt->bindParam(":userFullname", $json["userFullname"]);
        $insertStmt->bindParam(":email", $json["email"]);
        $insertStmt->bindParam(":contact", $json["contact"]);
        $insertStmt->execute();
        return $insertStmt->rowCount() > 0 ? 1 : 0;
    }

    function addClient($json)
    {
        // {"fullName":"Joe Togan", "userId":"0912-0912", "password":"togan123", "deptId":"1"}
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "INSERT INTO tblclients(fac_name, fac_code, fac_password, fac_deptId, fac_status) 
        VALUES (:fullName, :userId, :password, :deptId, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fullName', $json['fullName']);
        $stmt->bindParam(':userId', $json['userId']);
        $stmt->bindParam(':password', $json['password']);
        $stmt->bindParam(':deptId', $json['deptId']);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function addEquipment($json)
    {
        // {"equipmentName" : "Chair"}
        include "connection.php";
        $json = json_decode($json, true);
        if (recordExists($json["equipmentName"], "tblequipment", "equip_name")) {
            return -1;
        }
        $sql = "INSERT INTO tblequipment(equip_name) VALUES(:equipmentName)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':equipmentName', $json["equipmentName"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function updateEquipment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tblequipment SET equip_name = :equipmentName WHERE equip_id = :equipmentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':equipmentName', $json["equipmentName"]);
        $stmt->bindParam(':equipmentId', $json["equipmentId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function getEquipment()
    {
        include "connection.php";
        $sql = "SELECT * FROM tblequipment ORDER BY equip_name";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function addDepartment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        if (recordExists($json["departmentName"], "tbldepartment", "dept_name")) {
            return -1;
        }
        $sql = "INSERT INTO tbldepartment(dept_name) VALUES(:departmentName)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':departmentName', $json["departmentName"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function getDepartment()
    {
        include "connection.php";
        $sql = "SELECT * FROM tbldepartment ORDER BY dept_name";
        $stmt = $conn->prepare($sql);
        $returnValue = 0;
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
        }
        return $returnValue;
    }

    function updateDepartment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbldepartment SET dept_name = :departmentName WHERE dept_id = :departmentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':departmentName', $json["departmentName"]);
        $stmt->bindParam(':departmentId', $json["departmentId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }
} // admin class

function getTokenForUserId($userId)
{
    include 'connection.php';
    $sql = "SELECT tkn_token FROM tbltokens WHERE tkn_userId = " . $userId;
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $returnValue = [];
    if ($stmt->rowCount() > 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $returnValue[] = json_decode($row['tkn_token'], true);
        }
    }
    return $returnValue;
}

function getUserToken($json)
{
    include 'connection.php';
    $json = json_decode($json, true);
    $sql = "SELECT tkn_token FROM tbltokens WHERE tkn_userId = :userId";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $json["userId"]);
    $stmt->execute();
    $returnValue = [];
    if ($stmt->rowCount() > 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $returnValue[] = json_decode($row['tkn_token'], true);
        }
    }
    return $returnValue;
}

function recordExists($value, $table, $column)
{
    include "connection.php";
    $sql = "SELECT COUNT(*) FROM $table WHERE $column = :value";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":value", $value);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

$json = isset($_POST["json"]) ? $_POST["json"] : "0";
$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";

$admin = new Admin();

switch ($operation) {
    case "addLocation":
        echo $admin->addLocation($json);
        break;
    case "addLocationCategory":
        echo $admin->addLocationCategory($json);
        break;
    case "getLocationCategory":
        echo $admin->getLocationCategory();
        break;
    case "getLocations":
        echo $admin->getLocations($json);
        break;
    case "getAllTickets":
        echo $admin->getAllTickets();
        break;
    case "getSelectedTicket":
        echo $admin->getSelectedTicket($json);
        break;
    case "getAllPersonnel":
        echo $admin->getAllPersonnel();
        break;
    case "getPriority":
        echo $admin->getPriority();
        break;
    case "submitJobOrder":
        echo $admin->submitJobOrder($json);
        break;
    case "getJobDetails":
        echo $admin->getJobDetails($json);
        break;
    case "getTicketsByStatus":
        echo $admin->getTicketsByStatus($json);
        break;
    case "getAssignedPersonnel":
        echo $admin->getAssignedPersonnel($json);
        break;
    case "getReport":
        echo $admin->getReport();
        break;
    case "reopenJob":
        echo $admin->reopenJob($json);
        break;
    case "updateLocation":
        echo $admin->updateLocation($json);
        break;
    case "deleteLocation":
        echo $admin->deleteLocation($json);
        break;
    case "updateLocationCategory":
        echo $admin->updateLocationCategory($json);
        break;
    case "addPersonnel":
        echo $admin->addPersonnel($json);
        break;
    case "getAllLocation":
        echo $admin->getAllLocation();
        break;
    case "addClient":
        echo $admin->addClient($json);
        break;
    case "addEquipment":
        echo $admin->addEquipment($json);
        break;
    case "getEquipment":
        echo $admin->getEquipment();
        break;
    case "updateEquipment":
        echo $admin->updateEquipment($json);
        break;
    case "getOperation":
        echo $admin->getOperation();
        break;
    case "addDepartment":
        echo $admin->addDepartment($json);
        break;
    case "getDepartment":
        echo $admin->getDepartment();
        break;
    case "updateDepartment":
        echo $admin->updateDepartment($json);
        break;
}
