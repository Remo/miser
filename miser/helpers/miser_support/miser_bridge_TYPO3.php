<?php

// Cache directories
$this->css_dir( PATH_site . 'typo3temp/' );
$this->js_dir ( PATH_site . 'typo3temp/' );	

// Organisational lists
$this->keys_no_file(array('print.css'));

function find_it($path){
	return PATH_site . $path;
}
?>