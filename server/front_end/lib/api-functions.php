<?
$DEBUG=0;//0: debug disabled - 1: basic debug - 2: debug with curl responses

if($DEBUG){
	$requirelogin=0;
	require_once("../config.php");
}

function send_request($url,$username,$password,$method="get",$payload="{}"){
	global $DEBUG;

	//assumes valid input
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
	if($method=="post"){
		curl_setopt($ch, CURLOPT_POST, 1);
	} else if($method=="delete"){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	} else if($method=="put"){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	}
	if($method=="post" or $method=="put") curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	$response = curl_exec($ch);

	if($DEBUG==2){
		var_dump($response);
		//$response_info=curl_getinfo($ch);
		//var_dump($response_info);
	}

	//build response
	$response_decoded= new stdClass();
	if($response) {
		$response_decoded->response_status=curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response_decoded->data=json_decode($response);
	} else {
		$response_decoded->success = false;
		$response_decoded->error = new stdClass();
		$response_decoded->error->mensaje = "Error when trying to get data";
	}
	curl_close($ch);
	return $response_decoded;
}

// Front end auth
function do_auth($user,$pass){
	global $config;
	$response=send_request($config->api_fullpath."login",$user,$pass);
	//error_log(grab_dump($response));
	return (isset($response->response_status) and $response->response_status=="200");
}


//Organizations

// get organizations
function get_organizations($user,$pass){
	global $config;
	$response=send_request($config->api_fullpath."organization",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

// get single organization
function get_organization($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."organization/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function set_organization($user,$pass,$id,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."organization/$id",$user,$pass,"put",json_encode($payload_obj));
	if($response->response_status != "200") return false;
	else return $response->data;
}

function add_organization($user,$pass,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."organization",$user,$pass,"post",json_encode($payload_obj));
	if($response->response_status != "201") return false;
	else return $response->data;
}

function delete_organization($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."organization/$id",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}


//Persons

function get_persons($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."organization/$id/person",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function get_person($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."person/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function add_person($user,$pass,$orgid,$name,$idnum,$cardnum,$visitedorgid=null){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->orgId= $orgid;
	$payload_obj->name= $name;
	$payload_obj->identNumber= $idnum;
	$payload_obj->cardNumber= $cardnum;
	$payload_obj->visitedOrgId= $visitedorgid;
	$response=send_request($config->api_fullpath."person",$user,$pass,"post",json_encode($payload_obj));
	//if($response->response_status != "201") return false;
	//else return $response->data;
	return $response;
}

function set_person($user,$pass,$id,$orgid,$name,$idnum,$cardnum){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->orgId= $orgid;
	$payload_obj->name= $name;
	$payload_obj->identNumber= $idnum;
	$payload_obj->cardNumber= $cardnum;
	$payload_obj->visitedOrgId= null;
	$response=send_request($config->api_fullpath."person/$id",$user,$pass,"put",json_encode($payload_obj));
	if($response->response_status != "200") return false;
	else return $response->data;
}

function delete_person($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."person/$id",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}


//Accesses

//get person accesses
function get_person_accesses($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."person/$id/access",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function get_door_accesses($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."door/$id/access",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function get_access($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."access/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function delete_access($user,$pass,$id,$is_allweek){
	global $config;
	if($is_allweek) $endpoint="access";
	else $endpoint="liaccess";
	$response=send_request($config->api_fullpath."$endpoint/$id",$user,$pass,"delete");
	$response->sentdata="send_request($config->api_fullpath$endpoint/$id,$user,$pass,delete)";
	return $response;
}

function add_access_allweek($user,$pass,$doorid,$personid,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->doorId = $doorid;
	$payload_obj->personId = $personid;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	$response=send_request($config->api_fullpath."access",$user,$pass,"post",json_encode($payload_obj));
	if($response->response_status != "201") return false;
	else return $response->data;
}

function edit_access_allweek($user,$pass,$id,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	$response=send_request($config->api_fullpath."access/$id",$user,$pass,"put",json_encode($payload_obj));
	if($response->response_status != "200") return false;
	else return $response->data;
}

function add_access_liaccess($user,$pass,$doorid,$personid,$weekday,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->doorId = $doorid;
	$payload_obj->personId = $personid;
	$payload_obj->weekDay = $weekday;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	$response=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($payload_obj));
	//$response->sent_data = $payload_obj;
	//if($response->response_status != "201") return false;
	//else return $response->data;
	//INSTEAD, return the entire response with error texts for debug
	return $response;
}

function edit_access_liaccess($user,$pass,$doorid,$personid,$id,$days_payload,$expiredate){
	global $config;
/*	VERSION WITH DELETE ALL > ADD ALL
//make a delete access first
	$response=delete_access($user,$pass,$id,1); //parameter allWeek as true for deleting all liaccess accesses
	sleep(1);// delay added so delete can impact on the database
	if($response->response_status == "200"){
		$payload_obj = new stdClass();
		//add fixed values
		$payload_obj->doorId = $doorid;
		$payload_obj->personId = $personid;
		$payload_obj->expireDate = $expiredate;
		//then for each days_payload, make an add liaccess
		//explode days liaccesses in a | separated string
		$days_payload_arr=explode("|",$days_payload);
		foreach($days_payload_arr as $day_payload){
			//decode each day payload
			$day_payload_decoded=json_decode($day_payload);
			//copy values to new access object
			$payload_obj->weekDay = $day_payload_decoded->weekDay;
			$payload_obj->iSide = $day_payload_decoded->iSide;
			$payload_obj->oSide = $day_payload_decoded->oSide;
			$payload_obj->startTime = $day_payload_decoded->startTime;
			$payload_obj->endTime = $day_payload_decoded->endTime;
			//send an add request for liaccess day
			$response_inner=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($payload_obj));
			if($response_inner->response_status != "201") $response=$response_inner;
			//var_dump($response_inner);
		
}
	}
	return $response;
*/

	//get current liaccess
	$response = get_access($user,$pass,$id);
	if($response and isset($response->liAccesses) and is_array($response->liAccesses)){

		//build array key,value with arr[weekday] = obj;
		$access_current=array();
		foreach($response->liAccesses as $obj) $access_current[$obj->weekDay]=$obj;

		//explode and build the sent liaccesses for each weekday
		$days_payload_arr=explode("|",$days_payload);
		$days_payload_arr_objs=array();
		foreach($days_payload_arr as $day_payload) $days_payload_arr_objs[]=json_decode($day_payload);

		//build array key,value with arr[weekday] = obj;
		$access_sent=array();
		foreach($days_payload_arr_objs as $obj) $access_sent[$obj->weekDay]=$obj;

		//foreach sent liaccess weekday
		foreach($access_sent as $k=>$v){
			if(!isset($access_current[$k])){
				//if not in current > ADD
				$response=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($v));
			} else {
				//else if in current >
				//check each value to know if its different
				if(($v->iSide!=$access_current[$k]->iSide) or ($v->oSide!=$access_current[$k]->oSide) or ($v->startTime!=$access_current[$k]->startTime) or ($v->endTime!=$access_current[$k]->endTime)){
					//if different > EDIT
					$response=send_request($config->api_fullpath."liaccess/".$access_current[$k]->id,$user,$pass,"put",json_encode($v));
				} //else SKIP > no edits needed
			}
		}
		//foreach current liaccess weekday
		foreach($access_current as $k=>$v){
			//if not in sent > DELETE
			if(!isset($access_sent[$k])){
				delete_access($user,$pass,$v->id,0);
			} //else SKIP > leave existing days
		}
	}

	return $response;
}

function delete_access_bulk($user,$pass, $ids){
	global $config;
	$ids_arr=explode("|",$ids);
	$success=1;
	foreach($ids_arr as $id){
		if(is_numeric($id)) $response=delete_access($user,$pass,$id,1);
		$success = $success and ($response->response_status == "200");
	}
	return $success;
}

function add_access_allweek_organization($user,$pass,$doorid,$orgid,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->doorId = $doorid;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	
	//get all persons in organization
	$persons_recs = get_persons($user,$pass,$orgid);

	if($persons_recs){
		//for each person, add
		foreach($persons_recs as $person_rec){
			$payload_obj->personId = $person_rec->id;
			if($person_rec->resStateId==3){
				$response=send_request($config->api_fullpath."access",$user,$pass,"post",json_encode($payload_obj));
			}
		}
	}
}

function add_access_liaccess_organization($user,$pass,$doorid,$orgid,$weekday,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->doorId = $doorid;
	$payload_obj->weekDay = $weekday;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;

	//get all persons in organization
	$persons_recs = get_persons($user,$pass,$orgid);

	if($persons_recs){
		//for each person, add
		foreach($persons_recs as $person_rec){
			$payload_obj->personId = $person_rec->id;
			if($person_rec->resStateId==3){
				$response=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($payload_obj));
			}
		}
	}
}

function add_access_allweek_zone($user,$pass,$personid,$zoneid,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->personId = $personid;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	
	//get all doors in zone
	$doors_recs = get_doors($user,$pass,$zoneid);

	if($doors_recs){
		//for each door, add
		foreach($doors_recs as $door_rec){
			$payload_obj->doorId = $door_rec->id;
			if($door_rec->resStateId==3){
				$response=send_request($config->api_fullpath."access",$user,$pass,"post",json_encode($payload_obj));
			}
		}
	}
}

function add_access_liaccess_zone($user,$pass,$personid,$zoneid,$weekday,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->personId = $personid;
	$payload_obj->weekDay = $weekday;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;

	//get all doors in zone
	$doors_recs = get_doors($user,$pass,$zoneid);

	if($doors_recs){
		//for each door, add
		foreach($doors_recs as $door_rec){
			$payload_obj->doorId = $door_rec->id;
			if($door_rec->resStateId==3){
				$response=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($payload_obj));
			}
		}
	}
}

function add_access_allweek_organization_zone($user,$pass,$zoneid,$orgid,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	
	//get all persons in organization
	$persons_recs = get_persons($user,$pass,$orgid);

	if($persons_recs){
		//get all doors in zone
		$doors_recs = get_doors($user,$pass,$zoneid);
		if($doors_recs){
			//for each person
			foreach($persons_recs as $person_rec){
				$payload_obj->personId = $person_rec->id;
				if($person_rec->resStateId==3){
					//and each door, add
					foreach($doors_recs as $door_rec){
						$payload_obj->doorId = $door_rec->id;
						if($door_rec->resStateId==3){
							$response=send_request($config->api_fullpath."access",$user,$pass,"post",json_encode($payload_obj));
						}
					}
				}
			}
		}
	}
}

function add_access_liaccess_organization_zone($user,$pass,$zoneid,$orgid,$weekday,$iside,$oside,$starttime,$endtime,$expiredate){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->weekDay = $weekday;
	$payload_obj->iSide = $iside;
	$payload_obj->oSide = $oside;
	$payload_obj->startTime = $starttime;
	$payload_obj->endTime = $endtime;
	$payload_obj->expireDate = $expiredate;
	
	//get all persons in organization
	$persons_recs = get_persons($user,$pass,$orgid);

	if($persons_recs){
		//get all doors in zone
		$doors_recs = get_doors($user,$pass,$zoneid);
		if($doors_recs){
			//for each person
			foreach($persons_recs as $person_rec){
				$payload_obj->personId = $person_rec->id;
				if($person_rec->resStateId==3){
					//and each door, add
					foreach($doors_recs as $door_rec){
						$payload_obj->doorId = $door_rec->id;
						if($door_rec->resStateId==3){
							$response=send_request($config->api_fullpath."liaccess",$user,$pass,"post",json_encode($payload_obj));
						}
					}
				}
			}
		}
	}
}


//Events

function get_events($user,$pass,$orgid="",$personid="",$zoneid="",$doorid="",$side="",$fromdate="",$fromtime="",$untildate="",$untiltime="",$startevt=1,$q=15){
	global $config;
	$querystring="";
	if($orgid!="") $querystring.="orgId=".$orgid;
	if($personid!="") $querystring.="&personId=".$personid;
	if($zoneid!="") $querystring.="&zoneId=".$zoneid;
	if($doorid!="") $querystring.="&doorId=".$doorid;
	if($side!="") $querystring.="&side=".$side;
	if($fromdate!="") $querystring.="&startDateTime=".$fromdate."+".$fromtime;
	if($untildate!="") $querystring.="&endDateTime=".$untildate."+".$untiltime;
	if($startevt!="") $querystring.="&startEvt=".$startevt;
	if($q!="") $querystring.="&evtsQtty=".$q;

	$response=send_request($config->api_fullpath."events?$querystring",$user,$pass);
	return $response;
}


//Zones

function get_zones($user,$pass){
	global $config;
	$response=send_request($config->api_fullpath."zone",$user,$pass);
	if($response->response_status != "200") return false;
	else {
		for($i=0;$i<count($response->data);$i++){
			if(!isset($response->data[$i]->id)) {
				$uri_parts=explode("/",$response->data[$i]->uri);
				$response->data[$i]->id = end($uri_parts);
			}
		}
		return $response->data;
	}
}
/*
function get_zones($user,$pass){
	global $config;
	$response=send_request($config->api_fullpath."zone",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}
*/

// get single zone
function get_zone($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."zone/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function set_zone($user,$pass,$id,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."zone/$id",$user,$pass,"put",json_encode($payload_obj));
	if($response->response_status != "200") return false;
	else return $response->data;
}

function add_zone($user,$pass,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."zone",$user,$pass,"post",json_encode($payload_obj));
	if($response->response_status != "201") return false;
	else return $response->data;
}

function delete_zone($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."zone/$id",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}


//Doors

//get doors in a zone
function get_doors($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."zone/$id/door",$user,$pass);
	if($response->response_status != "200") return false;
	else {
		for($i=0;$i<count($response->data);$i++){
			if(!isset($response->data[$i]->name)) {
				$response->data[$i]->name = $response->data[$i]->description;
			}
		}
		return $response->data;
	}
}

/*function get_doors($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."zone/$id/door",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}*/

function get_door($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."door/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

//TODO: name, doorNum, controllerId, rlseTime, bzzrTime, alrmTime, zoneId
function add_door($user,$pass,$zoneid,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->zoneId= $zoneid;
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."door",$user,$pass,"post",json_encode($payload_obj));
	//if($response->response_status != "201") return false;
	//else return $response->data;
	return $response;
}

function set_door($user,$pass,$id,$zoneid,$name){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->zoneId= $zoneid;
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."door/$id",$user,$pass,"put",json_encode($payload_obj));
	if($response->response_status != "200") return false;
	else return $response->data;
}

function delete_door($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."door/$id",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}


//Visit door groups

function get_visit_door_groups($user,$pass){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup",$user,$pass);
	if($response->response_status != "200") return false;
	else {
		for($i=0;$i<count($response->data);$i++){
			if(!isset($response->data[$i]->id)) {
				$uri_parts=explode("/",$response->data[$i]->uri);
				$response->data[$i]->id = end($uri_parts);
			}
		}
		return $response->data;
	}
}

// get single visit door group
function get_visit_door_group($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

function add_visit_door_group($user,$pass,$name,$doorids){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."visitdoorgroup",$user,$pass,"post",json_encode($payload_obj));

	if($response->response_status != "201") return false;
	else {
		//add doors
		//get group id
		if(!isset($response->data->id)){
			$uri_parts=explode("/",$response->data->uri);
			$response->data->id = intval(end($uri_parts));
		}
		if(is_numeric($response->data->id)){
			//explode and build the sent door ids array
			$sent_doors_arr=explode("|",$doorids);
			foreach($sent_doors_arr as $sent_door_id){
				$response2=add_door_visit_door_group($user,$pass,$response->data->id,$sent_door_id);
			}
		}
		return $response->data;
	}
}

//add a door to an existing visit door group
function add_door_visit_door_group($user,$pass,$id,$doorid){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id/door/$doorid",$user,$pass,"put");
	if($response->response_status != "200") return false;
	else return $response->data;
}

// get doors from visit door group
function get_visit_door_group_doors($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id/door",$user,$pass);
	if($response->response_status != "200") return false;
	else return $response->data;
}

//edit group name
function set_visit_door_group($user,$pass,$id,$name,$doorids){
	global $config;
	$payload_obj = new stdClass();
	$payload_obj->name= $name;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id",$user,$pass,"put",json_encode($payload_obj));

	//explode and build the sent door ids array
	$sent_doors_arr=explode("|",$doorids);

	//get the current group door ids
	$response2=get_visit_door_group_doors($user,$pass,$id);

	if(($response2 !==false) and is_array($response2)){
		$current_doors_arr=array();
		//build array of all current ids
		foreach($response2 as $obj) $current_doors_arr[]=$obj->id;

		//foreach sent ids, check if they are or not already part of the group
		foreach($sent_doors_arr as $sent_door_id){
			if(!in_array($sent_door_id,$current_doors_arr)){
				//if not in current > ADD
				add_door_visit_door_group($user,$pass,$id,$sent_door_id);
			} //else if in current > SKIP
		}
		//foreach current id
		foreach($current_doors_arr as $current_door_id){
			//if not in sent > DELETE
			if(!in_array($current_door_id,$sent_doors_arr)){
				delete_door_visit_door_group($user,$pass,$id,$current_door_id);
			} //else SKIP > leave existing
		}
	}
	if($response->response_status != "200") return false;
	else return $response->data;
}

//remove door from visit door group
function delete_door_visit_door_group($user,$pass,$id,$doorid){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id/door/$doorid",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}

//delete group
function delete_visit_door_group($user,$pass,$id){
	global $config;
	$response=send_request($config->api_fullpath."visitdoorgroup/$id",$user,$pass,"delete");
	if($response->response_status != "200") return false;
	else return $response->data;
}


//Visitors

function get_visitors($user,$pass,$visitdoorgroupid="",$orgid="",$cardnum=""){
	global $config;
	$querystring="";
	if($visitdoorgroupid!="") $querystring.="visitDoorGroupId=".$visitdoorgroupid;
	if($orgid!="") $querystring.="&visitedOrgId=".$orgid;
	if($cardnum!="") $querystring.="&cardNumber=".$cardnum;

	$response=send_request($config->api_fullpath."visitor?$querystring",$user,$pass);
	//$response=send_request($config->api_fullpath."visitor",$user,$pass);
	//echo $config->api_fullpath."visitor";
	return $response;
}

function add_visit($user,$pass,$name,$idnum,$cardnum,$orgid,$expirationdate,$expirationhour,$doorgroupids_str=""){
	//add user
	$response = add_person($user,$pass,1,$name,$idnum,$cardnum,$orgid);
//var_dump($response);
	if($response->response_status == "201"){
		//get created person id
		if(isset($response->data->id)) $personid = $response->data->id;
		else {
			//if not in response, create from uri
			$uri_parts=explode("/",$response->data->uri);
			$personid = end($uri_parts);
		}
//var_dump($personid);
		$doorgroupids=explode("|",$doorgroupids_str);
//var_dump($doorgroupids);
		foreach($doorgroupids as $doorgroupid){
//echo "entro<br>";
			//get door group doors
			$door_group_doors=get_visit_door_group_doors($user,$pass,$doorgroupid);
//var_dump($door_group_doors);
			if($door_group_doors){
				//for each door id, add allweek access
				foreach($door_group_doors as $door){
					//only iside access, NO oside
					$response2 = add_access_allweek($user,$pass,$door->id,$personid,1,0,"00:00",$expirationhour,$expirationdate);
//echo "entro<br>";
//var_dump($response2);
				}
			} //else no accesses for that door group
		}
	}
	return $response;
}

if($DEBUG){
	//$res=get_organizations("admin","admin");
	//$res=do_auth("admin","admin");
	//$res=get_organizations("admin","admin",2);
	$res=get_person_accesses("admin","admin",18);
//	$res=get_access("admin","admin",44);
	//$res=add_person("admin","admin","7","Ricky Martin","",123132);
	//$res=get_door_accesses("admin","admin",5);
//	$res=get_zones("admin","admin");
	//$res=get_zone("admin","admin",1);
//	$res=get_doors("admin","admin",1);
	//$res=get_door("admin","admin",5);
	//$res=add_access_allweek("admin","admin",3,1,1,1,"08:00:00","18:00:00","9999-12-31 00:00");
	//$res=edit_access_allweek("admin","admin",19,0,1,"08:00:00","18:00:00","9999-12-31 00:00");
	//$res=delete_access("admin","admin",1,1);
	//$res=add_access_liaccess("admin","admin",1,1,1,0,1,"01:00:00","23:00:00","9999-12-31 00:00");
	//$res=add_access_liaccess("admin","admin",1,3,1,1,1,"08:00:00","18:00:00","9999-12-31 00:00");
	//$res=add_access_liaccess("admin", "admin", 4, 3, 2, 1, 1, "08:00:00", "18:00:00", "9999-12-31 00:00");
	//$res=delete_access("admin","admin",11,0);

//$res=edit_access_liaccess("admin","admin",4,3,44,'{"expireDate":"9999-12-31","doorId":4,"personId":3,"weekDay":3,"startTime":"08:00","endTime":"18:00","iSide":1,"oSide":1}|{"expireDate":"9999-12-31","doorId":4,"personId":3,"weekDay":5,"startTime":"08:00","endTime":"18:00","iSide":1,"oSide":1}',"9999-12-31");
//$res=edit_access_liaccess("admin","admin",4,3,44,'{"expireDate":"9999-12-31","doorId":4,"personId":3,"weekDay":3,"startTime":"08:00","endTime":"18:00","iSide":1,"oSide":1}',"2018-12-31");

//	add_access_allweek($user,$pass,$doorid,$personid,$iside,$oside,$starttime,$endtime,$expiredate){
//	edit_access_allweek($user,$pass,$id,$iside,$oside,$starttime,$endtime,$expiredate){
//	$res=add_access_allweek("admin","admin",1,3,1,1,"09:00","13:00","9999-12-31");
	//$res=get_events("admin","admin","","","","","","2017-01-16","00:00","2018-08-16","00:00");
	//$res=get_visit_door_groups("admin","admin");
	//$res=get_visit_door_group("admin","admin",1);
	//$res=set_visit_door_group("admin","admin",9,"Door Group 9","5|6");
//	$res=get_visit_door_group_doors("admin","admin",9);
	//$res=add_visit_door_group("admin","admin","Door Group 9","3|5");
	//$res=delete_visit_door_group("admin","admin",4);
	//$res=get_persons("admin","admin",1);
	//$res=get_person("admin","admin",18);
//	$res=get_visitors("admin","admin");
	//$res=get_person_accesses("admin","admin",9);
//	$res=add_visit("admin","admin","fasdfasdf",212121,33334,2,"2018-03-02","23:59","1");

	echo "<pre>";
	var_dump($res);
}
?>