<?php 
require_once("citation.php");


function list_primary_faculty_unpublish_DBMI_content(){
	$output = '';
	$query = new EntityFieldQuery();
	
	//primary faculty & Senior research staff
	$query->entityCondition('entity_type', 'node')
	->entityCondition('bundle', 'person')
	->propertyCondition('status', 1)
	->fieldCondition('taxonomy_vocabulary_1', 'tid', array(1,9));
	
	$result = $query->execute();
	if (!empty($result)) {
		$total = array_keys($result["node"]);
		$output .= "<h2>Number of primary faculties & Senior research staff: " . count($total) . "</h2>";
		$output .= implode(", ", $total) . "<br />";
		$output .= "<hr>";
		
		foreach($total as $nid){
			$output .= "<h2>Person nid: <a href=\"/node/$nid\">" . $nid . "</a></h2>";
			$output .= get_DBMI_unpublished_content_for_person("publication", $nid);
			$output .= get_DBMI_unpublished_content_for_person("grant", $nid);
			$output .= "<hr>";
		}	
	}
	watchdog('DigitalVita Import', $output, NULL, WATCHDOG_NOTICE);
	return $output;
}

function get_all_primary_faculty(){
	$output = '';
	$query = new EntityFieldQuery();
	
	//primary faculty & Senior research staff
	$query->entityCondition('entity_type', 'node')
	->entityCondition('bundle', 'person')
	->propertyCondition('status', 1)
	->fieldCondition('taxonomy_vocabulary_1', 'tid', array(1,9));
	
	$result = $query->execute();
	if (!empty($result)) {
		$total = array_keys($result["node"]);
		$output .= "<h2>Number of primary faculties & Senior research staff: " . count($total) . "</h2>";
		$output .= implode(", ", $total) . "<br />";
		$output .= "<hr>";
	}
	
	//primary faculty & Senior research staff who uses dv profile
	$query->entityCondition('entity_type', 'node')
	->entityCondition('bundle', 'person')
	->propertyCondition('status', 1)
	->fieldCondition('taxonomy_vocabulary_1', 'tid', array(1,9))
	->fieldCondition('field_use_dv_profile', 'value', 1 , '=');
	
	$result = $query->execute();
	if (!empty($result)) {
		$dv = array_keys($result["node"]);
		$output .= "<h2>Number of primary faculties & Senior research staff who use Digital Vita profile: " . count($dv) . "</h2>";
		$output .= implode(", ", $dv) . "<br />";
		$output .= "<hr>";
	}
	
	foreach($total as $nid){
		$output .= "<h2>Person nid: <a href=\"/node/$nid\">" . $nid . "</a></h2>";
		//if the faculty use dv profile
		if(!in_array($nid, $dv)){			
			$output .= publish_DBMI_content_for_person("publication", $nid);
			$output .= publish_DBMI_content_for_person("grant", $nid);
			
			$output .= delete_DV_content_for_person("publication", $nid);
			$output .= delete_DV_content_for_person("grant", $nid);
			
		}else{
			$output .= unpublish_DBMI_content_for_person("publication", $nid);
			$output .= unpublish_DBMI_content_for_person("grant", $nid);
						
			$output .= import_from_dv_form('publication',$nid);
			$output .= import_from_dv_form('grant',$nid);
		}
	}
	watchdog('DigitalVita Import', $output, NULL, WATCHDOG_NOTICE);
	return $output;
}



function import_from_dv_form($arg, $uid){

	$message = "";
		
	$url = variable_get('dv_ws_server', 'http://dv-test.dbmi.pitt.edu');
	$url .= '/dv-pub/profile-data/';
	//claudia
	//becich
	switch ($arg) {
		case "grant":
			$get = "/get/grant";
			break;
		case "publication":
			$get = "/get/PeerReviewedPublication";
			break;
		default:
			$message .= "No web service available for importing " . $arg . "<br />";
		break;
	}
	
	if(isset($get)){
		$message .= "<h3>- Import Digital Vita" . ucfirst($arg) . "</h3>";
		
		$alias = get_alias_by_uid($uid);
		if(empty($alias)){
			//no person in DBMI has this alias
			$message .= "<strong>This person doesn't have an Digital Vita alias set. </strong><br />";
		}else{
			
			$message .= "<strong>Found alias: ". $alias . "</strong><br />";
						
			//get existing dbmi contents
			$existing_dv_ids = array();
			$existing_dv_contents = get_DBMI_content_for_person($arg, $uid);
			if(!empty($existing_dv_contents)){
				$existing_dv_ids = array_keys($existing_dv_contents);
			}
						
			//get active contents from digital vita
			$imported_dv_ids = array();
			$active_dv_contents = json_decode(file_get_contents($url . $alias . $get));
											
			$message .= "<strong>Active Digital Vita ". $arg . "s: </strong><br />";
			//check if a active digital vita content has been imported
			foreach($active_dv_contents as $key => $item)
			{
				$imported_dv_ids[] = $item->id;
				$key = $key + 1;
				$pub_message = "";
					
				//imported before
				if(in_array($item->id, $existing_dv_ids)){
					
					//digital vita give every updated item a new id
					$node_id = $existing_dv_contents[$item->id];					
					$pub_message .= $key . ". <a class=\"dvid\" href=\"/node/" . $node_id . "\" target=\"_blank\">" . $item->id . "</a> (DigitalVita ID) was imported. ";
					$pub_message = '<div>' . $pub_message . '</div>';
					$message .= $pub_message;
					
				}else{			
					//imported from digital vita
					$node = create_DBMI_content_for_person($arg, $item, $uid);
					if($node->nid != null){
						$pub_message .= '<div>' . $key . ". ";
						$pub_message .= "<a class=\"dvid\" href=\"/node/" . $node->nid . "\" target=\"_blank\">" . $item->id . "</a> (DigitalVita ID) imported: ";					
						$pub_message .= print_dv_content($arg, $item);	
						$pub_message .= '</div>';
						$message .= $pub_message;
					}
				}
			}
			
			$message .= "<strong>Deleted " . $arg. "s: </strong><br />";
			foreach($existing_dv_contents as $dv_id => $node_id){
				if(in_array($dv_id, $imported_dv_ids)){
					//if the item still active on dv
					//do nothing
				}else{
					if(delete_DBMI_content_by_nid($node_id)){
						$message .="- Deleted <a href=\"/node/" . $node_id . "\">" . $dv_id . "</a>. <br />";
					}else{
						$message .="- Problem with deleting <a href=\"/node/" . $node_id . "\">" . $dv_id . "</a>. <br />";
					}
					
				}
			}
		}	
	}
	
	$message .= "<hr>";
	
	return $message;
}

function get_alias_by_uid($uid){
	$alias = "";
	$node = node_load($uid);
	if(isset($node->field_dv_profile_alias)){
		$alias =  $node->field_dv_profile_alias['und'][0]['value'];
	}
	return $alias;
}

function delete_DV_content_for_person($arg, $uid){
	$output = "<h3>- Delete DV " . $arg . "s: </h3>";

	$query = new EntityFieldQuery();
	$query
	->entityCondition('entity_type', 'node')
	->entityCondition('bundle', $arg)
	->propertyCondition('status', 1)
	->fieldCondition('field_person', 'nid', $uid, '=')
	->fieldCondition('field_imported_from_dv', 'value', 1, '=');

	$result = $query->execute();
	if (!empty($result)) {
		$nids = $result["node"];
		foreach($nids as $nid => $obj){
			try {
				node_delete($nid);
				$output .= "Delete node " . $nid . "<br />";

			} catch (Exception $e) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				$output .= "<strong>Problem with deleting DV " . $arg . "s. </strong><br />";
			}
		}
	}else{
		$output .= "No DV content needs to delete";
	}
	$output .= "<hr>";
	return $output;
}

function publish_DBMI_content_for_person($arg, $uid){
	$output = "<h3>- Publish DBMI " . $arg . "s: </h3>";

	$query = new EntityFieldQuery();
	$query
	->entityCondition('entity_type', 'node')
	->entityCondition('bundle', $arg)
	->propertyCondition('status', 0)
	->fieldCondition('field_person', 'nid', $uid, '=');

	$result = $query->execute();
	if (!empty($result)) {
		$all_unpublished_nids = $result["node"];
		
		$query = new EntityFieldQuery();
		$query
		->entityCondition('entity_type', 'node')
		->entityCondition('bundle', $arg)
		->propertyCondition('status', 0)
		->fieldCondition('field_person', 'nid', $uid, '=')
		->fieldCondition('field_imported_from_dv', 'value', 1, '=');	
		$result = $query->execute();
		
		$unpublished_dv_nids = array();	
		if (!empty($result)) {
			$unpublished_dv_nids = $result["node"];
		}
		
		if(count($unpublished_dv_nids) == count($all_unpublished_nids)){
			$output .= "No DBMI content needs to publish";
		}else{
			foreach($all_unpublished_nids as $nid => $obj){
				if(!in_array($nid, $unpublished_dv_nids)){
					try {
						$node = node_load($nid);
						$node->status = 1;
						node_save($node);
						$output .= "Publish node " . $nid . "<br />";
			
					} catch (Exception $e) {
						echo 'Caught exception: ',  $e->getMessage(), "\n";
						$output .= "<strong>Problem with publishing DBMI " . $arg . "s. </strong><br />";
					}
				}
			}
		}

	}else{
		$output .= "No DBMI content needs to publish";
	}
	$output .= "<hr>";
	return $output;
}

function unpublish_DBMI_content_for_person($arg, $uid){
	$output = "<h3>- Unpublish DBMI " . $arg . "s: </h3>";
	
	$query = new EntityFieldQuery();
	$query
	->entityCondition('entity_type', 'node')
	->entityCondition('bundle', $arg)
	->propertyCondition('status', 1)
	->fieldCondition('field_person', 'nid', $uid, '=');

	$result = $query->execute();
	if (!empty($result)) {
		$nids = $result["node"];
		$message = "";
		foreach($nids as $nid => $obj){
			try {
				$node = node_load($nid);
				if(!isset($node->field_imported_from_dv['und']) || $node->field_imported_from_dv['und'][0]['value'] != 1){
					$node->status = 0;
					node_save($node);
					$message .= "Unpublish node " . $nid . "<br />";
				}
			} catch (Exception $e) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				$output .= "<strong>- Problem with unpublishing DBMI " . $arg . "s. </strong><br />";
			}
		}
		
		if($message == ""){
			$message = "No DBMI content needs to unpublish";
		}
		$output .= $message;
			
	}else{
		$output .= "No DBMI content needs to unpublish";
	}	
	$output .= "<hr>";
	return $output;
}

function get_DBMI_content_for_person($arg, $uid){

	//get existing content in dbmi
	$existing_dv_publications = array();
	$query = new EntityFieldQuery();
	$query->entityCondition('entity_type', 'node')
	->entityCondition('bundle', $arg)
	->propertyCondition('status', 1)
	->fieldCondition('field_person', 'nid', $uid , '=')
	->fieldCondition('field_dv_id', 'value', 'NULL' , '!=');
	$result = $query->execute();

	if (isset($result['node'])) {
		$ids = array_keys($result['node']);
		$nids = implode(", ", $ids);

		$getExistingPublications = db_query("SELECT f.field_dv_id_value, f.entity_id
				FROM {field_data_field_dv_id} f WHERE f.entity_id in (" . $nids . ")");
		$record = $getExistingPublications->fetchAll();
		foreach($record as $existing_item){
			$existing_dv_publications[$existing_item->field_dv_id_value] = $existing_item->entity_id;
		}
	}
	return $existing_dv_publications;
}


function get_DBMI_unpublished_content_for_person($arg, $uid){
	
	$output = "<h3>- Get Unpublished DBMI " . $arg . "s: </h3>";
	//get existing content in dbmi
	$existing_dv_publications = array();
	$query = new EntityFieldQuery();
	$query->entityCondition('entity_type', 'node')
	->entityCondition('bundle', $arg)
	->propertyCondition('status', 0)
	->fieldCondition('field_person', 'nid', $uid , '=');
	$result = $query->execute();

	if (isset($result['node'])) {
		$ids = array_keys($result['node']);
		$output .= implode(", ", $ids);
	}else{
		$output .= "No DBMI unpublished content found. ";
	}
	return $output;
}

function create_DBMI_content_for_person($arg, $item, $uid){
	global $user;
	$node = new stdClass();
	
	if($arg == "grant"){
		$node->title = $item->projectTitle;
		$node->type = "grant";
		node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
		$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
		$node->uid = $user->uid;
		$node->status = 1; //(1 or 0): published or not
		$node->promote = 0; //(1 or 0): promoted to front page
		$node->comment = 0; // 0 = comments disabled, 1 = read only, 2 = read/write
		
		if(isset($item->description))$node->body[$node->language][0]['value']   = $item->description;
		$node->body[$node->language][0]['summary'] = text_summary("");
		$node->body[$node->language][0]['format']  = 1; // filtered html
		
		$node->field_person[$node->language][0]['nid'] = $uid;
		$node->field_imported_from_dv[$node->language][0]['value'] = 1;
		$node->field_dv_id[$node->language][0]['value'] = $item->id;
		
		if(isset($item->grantOrContractNumber))$node->field_grant_number[$node->language][0]['value'] = $item->grantOrContractNumber;
		if(isset($item->fundingSourceName))$node->field_grant_funding_agency[$node->language][0]['value'] = $item->fundingSourceName;
	
		if(isset($item->principalInvestigator))$node->field_grant_pi_other[$node->language][0]['value'] = $item->principalInvestigator;
		
		if(isset($item->role)){
			if($item->role == "PI (sole)"){
				$node->field_grant_role[$node->language][0]['value']  = 6;
			}else if($item->role == "PI (multiple)"){
				$node->field_grant_role[$node->language][0]['value']  = 2;
			}else if($item->role == "Co-Investigator"){
				$node->field_grant_role[$node->language][0]['value']  = 1;
			}else{
				$node->field_grant_role = null;
			}
		}
				
 		$node->field_grant_effort = null;
		//field_grant_start_date
		if(isset($item->minEventDate))$node->field_grant_start_date[$node->language][0]['value'] = $item->minEventDate->year . "-" . $item->minEventDate->month . "-" .$item->minEventDate->day . "T00:00:00"; 
		if(isset($item->maxEventDate))$node->field_grant_start_date[$node->language][0]['value2'] = $item->maxEventDate->year . "-" . $item->maxEventDate->month . "-" .$item->maxEventDate->day . "T00:00:00";
	
		$node = node_submit($node); // Prepare node for saving
		node_save($node);
		
	}else if($arg == "publication"){

		$node->title = $item->title;
		$node->type = "publication";
		node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
		$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
		$node->uid = $user->uid;
		$node->status = 1; //(1 or 0): published or not
		$node->promote = 0; //(1 or 0): promoted to front page
		$node->comment = 0; // 0 = comments disabled, 1 = read only, 2 = read/write
		
		// Citation field
		$citation = Citation::CitePublication($item, true);
		$node->body[$node->language][0]['value']   = $citation;
		$node->body[$node->language][0]['summary'] = text_summary("");
		$node->body[$node->language][0]['format']  = 1; // filtered html
		
		// Text field
		$node->field_dv_id[$node->language][0]['value'] = $item->id;
		if(isset($item->publicationDate->month) && $item->publicationDate->month != 0) $node->field_publication_month[$node->language][0]['value'] = $item->publicationDate->month;
		if(isset($item->publicationDate->year) && $item->publicationDate->year != 0) $node->field_publication_year[$node->language][0]['value'] = $item->publicationDate->year;
		
		$node->field_person[$node->language][0]['nid'] = $uid;
		$node->field_imported_from_dv[$node->language][0]['value'] = 1;
		if(isset($item->pubMedID)){
			$node->field_external_link[$node->language][0]['title'] = 'PubMed';
			$node->field_external_link[$node->language][0]['url'] = 'http://www.ncbi.nlm.nih.gov/pubmed/' . $item->pubMedID;
		}
		
		$node = node_submit($node); // Prepare node for saving
		node_save($node);			
	}
	
	return $node;
}

function delete_DBMI_content_by_nid($nid){
	try {
		node_delete($nid);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		return false;
	}
	return true;
}

function print_dv_content($arg, $item){
	if($arg == "grant"){
		$output = printGrant($item);
	}else if($arg == "publication"){
		$output = Citation::CitePublication($item, true);
	}
	return $output;
}
