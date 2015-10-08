<?php
	
	//set fits.sh executable first... it could be at /opt/fits

	//usage 
	//drush php-script regen-obj [root of book object]

	# repository connection parameters
	$url      = 'localhost:8080/fedora';
	$username = 'fedoraAdmin';
	$password = 'fedoraAdmin';
	# set up connection and repository variables
	$connection = new RepositoryConnection($url, $username, $password);
	$repository = new FedoraRepository(new FedoraApi($connection),new SimpleCache());  

	$root_pid = drush_shift();

	$parent_obj = $repository->getObject($root_pid);

	$itql = 'select $page_itql from <#ri>
        where $page_itql <fedora-rels-ext:isMemberOf> <info:fedora/'.$root_pid.'>
        order by $page_itql';


    $page_objects = $repository->ri->itqlQuery($itql,'unlimited','0');   

    foreach ($page_objects as $page) {
    	$page_pid = $page['page_itql']['value'];

    	$object = islandora_object_load($page_pid);

    	if (!$object->getDataStream('JP2')) {
    		echo "regenerating OBJ for $page_pid\n";

    		$obj_ds = $object['OBJ'];

    		//url of image... http://fedora_repo_url:8080/objects/[pid]/datastreams/OBJ/content
    		$file_url = $repo_url.'/objects/'.$page_pid.'/datastreams/OBJ/content';

			$drupal_result = drupal_http_request($file_url);


			if (!empty($drupal_result->data)) {

				//create a temporary file
				$new_file = file_save_data($drupal_result->data, file_default_scheme().'://');

				$path = drupal_realpath($new_file->uri);

				//replace file...
				$obj_ds->setContentFromFile($path);

				//delete temporary file
				file_delete($new_file);

				echo "regenerating OBJ for $page_pid completed\n";
			} 
    	}

    }