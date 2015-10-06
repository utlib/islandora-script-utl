<?php

	# repository connection parameters
	$url      = 'localhost:8080/fedora';
	$username = 'fedoraAdmin';
	$password = 'fedoraAdmin';
	# set up connection and repository variables
	$connection = new RepositoryConnection($url, $username, $password);
	$repository = new FedoraRepository(new FedoraApi($connection),new SimpleCache());  

	//provide pid of the book as an argument when running this script on drush
	//e.g. drush php-script convert-compound-to-page-cleanup.php book:123

	$root_pid = drush_shift();

	$parent_obj = $repository->getObject($root_pid);

	echo "parent pid: ".$root_pid."\n";

	$itql = 'select $page_itql from <#ri>
            where $page_itql <fedora-rels-ext:isMemberOf> <info:fedora/'.$root_pid.'>
            order by $page_itql';


    $page_objects = $repository->ri->itqlQuery($itql,'unlimited','0');    

    //change path accordingly for your configuration
    require_once('/var/www/drupal/sites/all/modules/islandora/islandora_solution_pack_large_image/includes/derivatives.inc');

	foreach ($page_objects as $page) {


		$page_obj_pid = $page['page_itql']['value'];

		echo "start updating : " . $page_obj_pid."\n";

		$page_obj = $repository->getObject($page_obj_pid);
		drush_print("Re generating JP2");

		//call islandora_large_image_create_JP2_derivate function on derivates, second argument has to be 'TRUE' to regenerate JP2 - this automatically populates RELS-INT datastream
		$fulltextResult = islandora_large_image_create_JP2_derivative($page_obj, TRUE);
        
        // check to make sure the result was successful as reported by the function
        if ($fulltextResult['success'] != 1) {
            print("\n\n**ERROR re-generating JP2 datastream for $page_obj\n");
            print_r($fulltextResult);
        }


		echo $page_obj_pid." updated \n";
		
	}    
