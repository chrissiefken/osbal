<?php
//global server configuration settings are here
class config {
	//path to a writable settings directory
	const configPath = '/usr/local/osbal/config/';

	//filename where usernames and hashed passwords are stored
	const userFile = 'users';

	//filename where SSL / stunnel settings are stored
	const sslFile = 'ssl';

	//filename where admin ip and subnet are stored
	const adminIpSettings = 'adminIp';

	const haPatner = 'partner';

	const lbServices = 'services';

	// environment settings
	// if you are using Ubuntu 14.04 these don't need to be changed
	const haproxyCfg = '/etc/haproxy/haproxy.cfg';
	const stunnelCfg = '/etc/stunnel/ssl.conf';
	const sslDirectory = ''; 
}
?>