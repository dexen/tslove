<?php

function td(...$a)
{
	foreach ($a as $v)
		var_dump($v);
	die('td()');
}

function prepare_dir_out_files(array $config, array $keys) : array
{
	foreach ($keys as $k)
		if (!is_dir(dirname($config[$k])))
			mkdir(dirname($config[$k]), 0777, $recursive = true);

	return $config;
}

function prepare_dir_ca_files(array $config) : array
{
	return prepare_dir_out_files($config,
		bail_files_exist(['ca_key_file', 'ca_cert_file'], $config) );
}

function propose_config_file() : array
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

function bail_files_exist(array $a, array $config) : array
{
	foreach ($a as $k)
		if (file_exists($config[$k]))
			throw new RuntimeException(sprintf('Output file "%s" exists, refuses to overwrite', $config[$k]));
	return $a;
}

function generate_ca_files(array $config)
{
	system(
		sprintf('openssl genrsa -out %s 4096',
			escapeshellcmd($config['ca_key_file']) ) );

	system(
		sprintf('openssl req -x509 -new -nodes -key %s -sha256 -days 1024 -out %s',
			escapeshellarg($config['ca_key_file']),
			escapeshellarg($config['ca_cert_file']) ) );
}


function prepare_dir_cert_files(array $config) : array
{
	return prepare_dir_out_files($config,
		bail_files_exist([ 'csr_file', 'conf_file', 'ext_file', 'cert_file', 'key_file' ], $config));
}

function propose_cert_files(array $config) : array
{
	$config = array_merge(
		$config,
		propose_ca_files(),
		[
			'csr_file' => 'domains/' .$config['domain'] .'.csr',
			'conf_file' => 'domains/' .$config['domain'] .'.csr.cnf',
			'ext_file' => 'domains/' .$config['domain'] .'.v3.ext',
			'cert_file' => 'domains/' .$config['domain'] .'.pem',
			'key_file' => 'domains/' .$config['domain'] .'.key',
		] );

	return $config;
}

function prompt_domain() : array
{
	$like = 'example.' .gethostname();

	$config = [
		'domain' => readline(sprintf('Local dev domain name (like "%s") ', $like)),
	];

	return $config;
}
