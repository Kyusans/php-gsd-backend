<?php 
    include "headers.php";

    class Personnel{
        function getJobTicket($json){
            include "connection.php";
            $json = json_decode($json, true);
            $sql = "SELECT b.job_title, b.job_description, b.job_createDate, b.job_complaintId, d.joStatus_name
            FROM tbljoborderpersonnel as a
            INNER JOIN tbljoborders as b ON a.joPersonnel_joId = b.job_id
            INNER JOIN tblcomplaints as c ON b.job_complaintId = c.comp_id
            INNER JOIN tbljoborderstatus as d ON c.comp_id = d.joStatus_id
            WHERE a.joPersonnel_userId = :userId";
    
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
    }

    $json = isset($_POST["json"]) ? $_POST["json"] : "0";
    $operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
    $personnel = new Personnel();

    switch($operation){
        case "getJobTicket":
            echo $personnel->getJobTicket($json);
            break;
    }
?>