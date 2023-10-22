<?php 
    include "headers.php";

    class Personnel{
        function getJobTicket($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT b.job_title, b.job_description, b.job_createDate, b.job_complaintId, d.joStatus_name, e.priority_name ";
            $sql .= "FROM tbljoborderpersonnel as a ";
            $sql .= "INNER JOIN tbljoborders as b ON a.joPersonnel_joId = b.job_id ";
            $sql .= "INNER JOIN tblcomplaints as c ON b.job_complaintId = c.comp_id ";
            $sql .= "INNER JOIN tbljoborderstatus as d ON c.comp_status = d.joStatus_id ";
            $sql .= "INNER JOIN tblpriority as e ON b.job_priority = e.priority_id "; 
            $sql .= "WHERE a.joPersonnel_userId = :userId ORDER BY b.job_id DESC";
    
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userId", $json["userId"]);
            $stmt->execute();
            $returnValue = 0;
            if($stmt->rowCount() > 0){
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = json_encode($rs);
            }
            return $returnValue;
        }

        function jobDone($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "UPDATE tblcomplaints SET comp_status = 3 WHERE comp_id = :compId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":compId", $json["compId"]);
            $stmt->execute();
            return $stmt->rowCount() > 0 ? 1 : 0;
        }
    }

    $json = isset($_POST["json"]) ? $_POST["json"] : "0";
    $operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
    $personnel = new Personnel();

    switch($operation){
        case "getJobTicket":
            echo $personnel->getJobTicket($json);
            break;
        case "jobDone":
            echo $personnel->jobDone($json);
            break;
    }
?>