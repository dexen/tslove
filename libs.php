<?php

function td(...$a)
{
	foreach ($a as $v)
		var_dump($v);
	die('td()');
}

function prepare_dir_ca_files(array $config) : array
{
	$pn = dirname($config['ca_key_file']);
	if (!is_dir($pn))
		mkdir($pn, 0777, $recursive = true);

	$pn = dirname($config['ca_cert_file']);
	if (!is_dir($pn))
		mkdir($pn, 0777, $recursive = true);

	return $config;
}

function propose_ca_files() : array
{
	return [
		'ca_key_file' => sprintf('CA/%s-%s-CA.key',
			get_current_user(),
			gethostname() ),
		'ca_cert_file' => sprintf('CA/%s-%s-CA.pem',
			get_current_user(),
			gethostname() ),
	];
}

function bail_exists(string $pn) : string
{
	if (file_exists($pn))
		throw new RuntimeException(sprintf('Output file "%s" exists, refuses to overwrite', $pn));
	return $pn;
}

function generate_ca_files(array $config)
{
	system(
		sprintf('openssl genrsa -out %s 4096',
			escapeshellcmd(bail_exists($config['ca_key_file'])) ) );

	system(
		sprintf('openssl req -x509 -new -nodes -key %s -sha256 -days 1024 -out %s',
			escapeshellcmd($config['ca_key_file']),
			escapeshellcmd(bail_exists($config['ca_cert_file'])) ) );
}