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
            $sql .= "INNER JOIN tbljoborderstatus as b ON a.comp_status = b.joStatus_id";
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
                $stmt->bindParam(":jobCreatedBy", $master['facultyId']);
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
    }
?>