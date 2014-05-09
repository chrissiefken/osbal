<?php

function createConfig() {
	if(file_exists($configPath)){
		return array('message' => 'Config file exists.', 'error' => true);
	} else {
		file_put_contents($configPath, '[osbal_config]');
	}
}

function applyConfig() {

}
?>