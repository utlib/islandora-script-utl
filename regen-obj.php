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
    		$file_url = $repo_url.'/objects/'.$page_pid.'/datastreams/OBJ/content';

			$url_info = parse_url($file_url);

			$url_path_info = pathinfo($url_info['path']);

			$drupal_result = drupal_http_request($file_url);


			if (!empty($drupal_result->data)) {
				$new_file = file_save_data($drupal_result->data, file_default_scheme().'://');

				$path = drupal_realpath($new_file->uri);

				$obj_ds->setContentFromFile($path);

				file_delete($new_file);

				echo "regenerating OBJ for $page_pid completed\n";
			} 
    	}

    }