<?php
include "headers.php";

class Personnel
{
  function getJobTicket($json)
  {
    include "connection.php";
    $json = json_decode($json, true);
    $sql = "SELECT b.job_title, b.job_description, b.job_createDate, b.job_complaintId, c.comp_lastUser, c.comp_end_date, c.comp_status, d.joStatus_name, e.priority_id, e.priority_name 
            FROM tbljoborderpersonnel as a 
            INNER JOIN tbljoborders as b ON a.joPersonnel_joId = b.job_id 
            INNER JOIN tblcomplaints as c ON b.job_complaintId = c.comp_id 
            INNER JOIN tbljoborderstatus as d ON c.comp_status = d.joStatus_id 
            INNER JOIN tblpriority as e ON b.job_priority = e.priority_id  
            WHERE a.joPersonnel_userId = :userId  
            ORDER BY b.job_id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $json["userId"]);
    $stmt->execute();
    $returnValue = 0;
    if ($stmt->rowCount() > 0) {
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $returnValue = json_encode($rs);
    }
    return $returnValue;
  }

  function getJobsByStatus($json)
  {
    include "connection.php";
    $json = json_decode($json, true);
    $sql = "SELECT b.job_title, b.job_description, b.job_createDate, b.job_complaintId, c.comp_lastUser, d.joStatus_name, e.priority_name ";
    $sql .= "FROM tbljoborderpersonnel as a ";
    $sql .= "INNER JOIN tbljoborders as b ON a.joPersonnel_joId = b.job_id ";
    $sql .= "INNER JOIN tblcomplaints as c ON b.job_complaintId = c.comp_id ";
    $sql .= "INNER JOIN tbljoborderstatus as d ON c.comp_status = d.joStatus_id ";
    $sql .= "INNER JOIN tblpriority as e ON b.job_priority = e.priority_id ";
    $sql .= "WHERE a.joPersonnel_userId = :userId AND d.joStatus_id = :status ORDER BY b.job_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $json["userId"]);
    $stmt->bindParam(":status", $json["status"]);
    $stmt->execute();
    $returnValue = 0;
    if ($stmt->rowCount() > 0) {
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $returnValue = json_encode($rs);
    }
    return $returnValue;
  }

  function jobDone($json)
  {
    include "connection.php";
    include "users.php";
    $date = getCurrentDate();
    $json = json_decode($json, true);
    require_once "sendNotification.php";
    $master = $json['master'];
    $detail = $json['detail'];
    $selectedEquipment = $detail['selectedEquipment'];
    try {
      $conn->beginTransaction();
      $sql = "UPDATE tblcomplaints 
                  SET comp_status = 3, comp_closedBy = :fullName, comp_date_closed = :closedDate, comp_operation = :operation, comp_remark = :remarks  
                  WHERE comp_id = :compId";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(":compId", $master["compId"]);
      $stmt->bindParam(":fullName", $master["fullName"]);
      $stmt->bindParam(":operation", $master["jobOperation"]);
      $stmt->bindParam(":remarks", $master["remarks"]);
      $stmt->bindParam(":closedDate", $date);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        if ($master["otherEquipment"] !== null) {
          $sql2 = "INSERT INTO tblequipment(equip_name) VALUES(:name)";
          $stmt2 = $conn->prepare($sql2);
          $stmt2->bindParam(":name", $master["otherEquipment"]);
          $stmt2->execute();
          if ($stmt2->rowCount() > 0) {
            $newId = $conn->lastInsertId();
            $sql3 = "INSERT INTO tbljobequipment(joEquipment_equipId, joEquipment_compId) VALUES(:equipId, :compId)";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bindParam(":equipId", $newId);
            $stmt3->bindParam(":compId", $master["compId"]);
            $stmt3->execute();
          }
        }

        if (isset($selectedEquipment) && count($selectedEquipment) > 0) {
          foreach ($selectedEquipment as $key => $value) {
            $sql4 = "INSERT INTO tbljobequipment(joEquipment_equipId, joEquipment_compId) VALUES(:equipId, :compId)";
            $stmt4 = $conn->prepare($sql4);
            $stmt4->bindParam(":equipId", $value);
            $stmt4->bindParam(":compId", $master["compId"]);
            $stmt4->execute();
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


  //priority diay ni hehe
  function getSelectedStatus($json)
  {
    include "connection.php";
    $json = json_decode($json, true);
    $sql = "SELECT b.job_title, b.job_description, b.job_createDate, b.job_complaintId, c.comp_lastUser, d.joStatus_name, e.priority_name 
            FROM tbljoborderpersonnel as a 
            INNER JOIN tbljoborders as b ON a.joPersonnel_joId = b.job_id 
            INNER JOIN tblcomplaints as c ON b.job_complaintId = c.comp_id 
            INNER JOIN tbljoborderstatus as d ON c.comp_status = d.joStatus_id 
            INNER JOIN tblpriority as e ON b.job_priority = e.priority_id 
            WHERE a.joPersonnel_userId = :userId AND b.job_priority = :priority AND c.comp_status = 2  ORDER BY b.job_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":priority", $json["priority"]);
    $stmt->bindParam(":userId", $json["userId"]);
    $returnValue = 0;
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
      $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $returnValue = json_encode($rs);
    }
    return $returnValue;
  }
} // personnel

$json = isset($_POST["json"]) ? $_POST["json"] : "0";
$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$personnel = new Personnel();

switch ($operation) {
  case "getJobTicket":
    echo $personnel->getJobTicket($json);
    break;
  case "getJobsByStatus":
    echo $personnel->getJobsByStatus($json);
    break;
  case "jobDone":
    echo $personnel->jobDone($json);
    break;
  case "getSelectedStatus":
    echo $personnel->getSelectedStatus($json);
    break;
}
