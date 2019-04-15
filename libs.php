<?php

function td(...$a)
{
	foreach ($a as $v)
		var_dump($v);
	die('td()');
}

function propose_ca_files() : array
{
	return [
		'ca_key_file' => sprintf('%s-%s-CA.key',
			get_current_user(),
			gethostname() ),
		'ca_cert_file' => sprintf('%s-%s-CA.pem',
			get_current_user(),
			gethostname() ),
	];
}

function generate_ca_files(array $config)
{
}
