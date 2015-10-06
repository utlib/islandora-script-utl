<?php

	$connection = new IslandoraTuque();

	$repository = $connection->repository;

	$root_pid = drush_shift();

	$parent_obj = $repository->getObject($root_pid);

	echo "parent pid: ".$root_pid."\n";

	$itql = 'select $page_itql from <#ri>
            where $page_itql <fedora-rels-ext:isMemberOf> <info:fedora/'.$root_pid.'>
            order by $page_itql';


    $page_objects = $repository->ri->itqlQuery($itql,'unlimited','0');    

    require_once('../sites/all/modules/islandora/islandora_solution_pack_large_image/includes/derivatives.inc');

	foreach ($page_objects as $page) {


		$page_obj_pid = $page['page_itql']['value'];

		echo "start updating : " . $page_obj_pid."\n";

		$page_obj = $repository->getObject($page_obj_pid);
		drush_print("Re generating JP2");

		$fulltextResult = islandora_large_image_create_JP2_derivative($page_obj, TRUE);
        
        // check to make sure the result was successful as reported by the function
        if ($fulltextResult['success'] != 1) {
            print("\n\n**ERROR generating FULL_TEXT datastream for $objectPID\n");
            print_r($fulltextResult);
        }


        drush_print("Re creating isMemberOf");

        //store value of isMemberOf before removing

		$ismemberOf = $page_obj->relationships->get(FEDORA_RELS_EXT_URI,'isMemberOf');

		$ismemberOf_val = $ismemberOf[0]['object']['value'];

		$page_obj->relationships->remove(FEDORA_RELS_EXT_URI,'isMemberOf');

		$page_obj->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOf', $ismemberOf_val);	

		drush_print("Re creating isPageOf");

		$ispageOf = $page_obj->relationships->get(FEDORA_RELS_EXT_URI,'isPageOf');

		$ispageof_val = $ispageOf[0]['object']['value'];

		$page_obj->relationships->remove(FEDORA_RELS_EXT_URI,'isPageOf');

		$page_obj->relationships->add(ISLANDORA_RELS_EXT_URI,'isPageOf',$ispageof_val);

		drush_print("Re creating isSection with value of 1");

		$page_obj->relationships->remove(ISLANDORA_RELS_EXT_URI,'isSection');

		$page_obj->relationships->add(ISLANDORA_RELS_EXT_URI,'isSection','1',TRUE);


		echo $page_obj_pid." updated \n";
		
	}    
