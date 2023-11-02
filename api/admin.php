<?php 
    include "headers.php";

    class Admin{
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
            $sql = "SELECT tbllocation.location_id, tbllocation.location_name, tbllocationcategory.locCateg_name ";
            $sql .= "FROM tbllocation INNER JOIN tbllocationcategory ";
            $sql .= "ON tbllocation.location_categoryId = tbllocationcategory.locCateg_id ";
            $sql .= "WHERE tbllocationcategory.locCateg_id = :categoryId";
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

        function getAllTickets(){
            include "connection.php";
            $sql = "SELECT a.*, b.joStatus_name ";
            $sql .= "FROM tblcomplaints as a ";
            $sql .= "INNER JOIN tbljoborderstatus as b ON a.comp_status = b.joStatus_id ORDER BY a.comp_id DESC";
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

        function getSelectedTicket($json){
            // {"compId" : 2}
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT c.comp_id, c.comp_subject, c.comp_description, c.comp_date, cl.fac_name, cl.fac_id, loc.location_name, lc.locCateg_name ";
            $sql .= "FROM tblcomplaints AS c ";
            $sql .= "INNER JOIN tblclients AS cl ON c.comp_clientId = cl.fac_id ";
            $sql .= "INNER JOIN tbllocation AS loc ON c.comp_locationId = loc.location_id ";
            $sql .= "INNER JOIN tbllocationcategory AS lc ON comp_locationCategoryId = lc.locCateg_id ";
            $sql .= "WHERE c.comp_id = :compId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":compId", $json["compId"]);
            $returnValue = 0;
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }

        function getAllPersonnel(){
            include "connection.php";
            $sql = "SELECT * FROM tblusers WHERE user_level = 90";
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

        function getPriority(){
            include "connection.php";
            $sql = "SELECT * FROM tblpriority";
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

        function submitJobOrder($json){
            include "connection.php";
            $json = json_decode($json, true);
            $master = $json['master'];
            $detail = $json['detail'];
            $jobPersonnelId = $detail['jobPersonnelId'];

            try{
                $conn->beginTransaction();
                $sql = "INSERT INTO tbljoborders(job_complaintId, job_title, job_description, job_priority, job_createdBy) ";
                $sql .= "VALUES(:complaintId, :jobTitle, :jobDescription, :jobPriority, :jobCreatedBy)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":complaintId", $master['ticketNumber']);
                $stmt->bindParam(":jobTitle", $master['subject']);
                $stmt->bindParam(":jobDescription", $master['description']);
                $stmt->bindParam(":jobPriority", $master['priority']);
                $stmt->bindParam(":jobCreatedBy", $master['jobCreatedBy']);
                $stmt->execute();
                if($stmt->rowCount() > 0){
                    $newId = $conn->lastInsertId();
                    $sql = "INSERT INTO tbljoborderpersonnel(joPersonnel_joId, joPersonnel_userId) ";
                    $sql .= "VALUES(:jobId, :userId)";
                    $stmt = $conn->prepare($sql);
                    foreach($jobPersonnelId as $userId){
                        $stmt->bindParam(":jobId", $newId);
                        $stmt->bindParam(":userId", $userId);
                        $stmt->execute();

                        $token = getTokenForUserId($userId); // Implement this function to get the token for a user
                        $notification = new Notification();
                        $notification->sendNotif($token, $master['subject']);
                    }
                    if($stmt->rowCount() > 0){
                        $sql = "UPDATE tblcomplaints SET comp_status = 2 WHERE comp_id = :compId";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":compId", $master["ticketNumber"]);
                        $stmt->execute();
                    }
                }
                $conn->commit();
                return 1;
            }catch(Exception $e){
                $conn->rollBack();
                return 0;
            }
        }

        function getJobDetails($json){
            include "connection.php";
            // {"compId": 14}
            $json = json_decode($json, true);
            $sql = "SELECT a.comp_subject, a.comp_id, b.location_name, c.locCateg_name, d.job_description, d.job_createDate, e.priority_name, f.fac_name, g.user_full_name, h.joStatus_name, h.joStatus_id ";
            $sql .= "FROM tblcomplaints as a ";
            $sql .= "INNER JOIN tbllocation as b ON a.comp_locationId = b.location_id ";
            $sql .= "INNER JOIN tbllocationcategory as c ON a.comp_locationCategoryId = c.locCateg_id ";
            $sql .= "INNER JOIN tbljoborders as d ON d.job_complaintId = a.comp_id ";
            $sql .= "INNER JOIN tblpriority as e ON d.job_priority = e.priority_id ";
            $sql .= "INNER JOIN tblclients as f ON f.fac_id = a.comp_clientId ";
            $sql .= "INNER JOIN tblusers as g ON g.user_id = d.job_createdBy ";
            $sql .= "INNER JOIN tbljoborderstatus as h ON a.comp_status = h.joStatus_id ";
            $sql .= "WHERE a.comp_id = :compId";
            // echo "sql: " . $sql;
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":compId", $json["compId"]);
            $returnValue = 0;
            $stmt->execute();
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }
    }// admin class

    function getTokenForUserId($userId){
        include 'connection.php';
        $sql = "SELECT user_token FROM tblusers WHERE user_id = " . $userId;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['user_token'];
        }
        return 0; 
    }

    $json = isset($_POST["json"]) ? $_POST["json"] : "0";
    $operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";

    $admin = new Admin();

    switch($operation){
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
    }
?>