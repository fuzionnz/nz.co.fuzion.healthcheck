<?php

function smarty_function_crmHCSQL($params, &$smarty) {
    $is_error = 0;
    $error = "";
    $values = "";
    $sql="";
    if (!array_key_exists('sql', $params) && !array_key_exists('file', $params) && !array_key_exists('json', $params)) {
        $smarty->trigger_error("assign: missing 'sql', 'json' OR 'file' parameter");
        $error = "crmAPI: missing 'sql', 'json' or 'file' parameter";
        $is_error = 1;
    }

    $parameters = array();

    try {
	    if (array_key_exists('file', $params)) {
        list($filename, $interval) = explode("-", $params["file"]);
        $filename =  $filename.".sql";

        if (strpos($interval, '_year') !== false) {
          $year = ($interval == 'current_year') ? date('Y') : date('Y') - 1;
          $additionalWhere = "AND YEAR(receive_date) = {$year}";
        }
        else {
          $month = str_replace('_month', '', $interval);
          $additionalWhere = "AND receive_date >= DATE_SUB(now(), INTERVAL {$month} MONTH)";
        }

        $sql = str_replace('additional_where', $additionalWhere, file_get_contents($filename, true));

        if (!$sql)  throw new Exception ("missing filename or empty ".$filename);

      }
      if (!empty($params['type']) && $params['type'] == 'dedupe') {
        if (!empty($params['context']) && $params['context'] == 'build_element') {
          $sql = 'select id, CONCAT(contact_type, " - ", title) as title from civicrm_dedupe_rule_group';
        }
        else {
          $additionalWhere = "where MONTH(action_log_date) = " . date('m') . " AND YEAR(action_log_date) = " . date('Y');
          if (!empty($params['rule_id'])) {
            $additionalWhere .= ' AND rule_id = ' . $params['rule_id'];
          }
          $sql = str_replace('additional_where', $additionalWhere, file_get_contents($filename, true));
        }
      }

	    $forbidden=array("delete ", "drop ","update ","grant ");
	    foreach ($forbidden as $check) {
        if(strpos(strtolower($sql), $check)!==false){
          $smarty->trigger_error($check."command not allowed");
          $error = "crmAPI: you can not ".$check."using crmHCSQL";
          $is_error = 1;
          break;
        }
	    }

      if (array_key_exists('debug', $params)) {
        $smarty->trigger_error("sql:". $params["sql"]);
      }

        if ($is_error==0) {
            $errorScope = CRM_Core_TemporaryErrorScope::useException();
	          CRM_Core_DAO::executeQuery("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED ;");
            $dao = CRM_Core_DAO::executeQuery($sql,$parameters);
            $values = array();
            $keys= null;
            if (array_key_exists('sequential', $params)) {
              while ($dao->fetch()) {
                if (!$keys) $keys= array_keys($dao->toArray());
                $values[] = array_values($dao->toArray());
              }
            } else {
              while ($dao->fetch()) {
                $values[] = $dao->toArray();
              }
              if (!empty($values)) {
                $keys= array_keys($values[0]);
              }
            }
          }
    }
    catch(Exception $e){
        $is_error=1;
        $error = "crmAPI: ".$e->getMessage();
        $values="";
    }

    if(array_key_exists('set', $params)){
        if($values!=""){
            //echo "console.log('string')";
            $smarty->assign($params['set'], $values);
        }
    }

    if (array_key_exists('debug', $params)) {
      return json_encode(array("is_error"=>$is_error, "keys"=> $keys, "error"=>$error, "values" => $values,"sql" => trim(preg_replace('/\s+/', ' ', $sql))), JSON_NUMERIC_CHECK);
    }
    if (!$smarty)
      return array("is_error"=>$is_error, "keys"=> $keys, "error"=>$error, "values" => $values);

    return json_encode(array("is_error"=>$is_error, "keys"=> $keys, "error"=>$error, "values" => $values), JSON_NUMERIC_CHECK);
}
