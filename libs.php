<?php

function td(...$a)
{
	foreach ($a as $v)
		var_dump($v);
	die('td()');
}

function prepare_dir_config_files(array $config, array $keys) : array
{
	foreach ($keys as $k)
		if (!is_dir(dirname($config[$k])))
			mkdir(dirname($config[$k]), 0777, $recursive = true);

	return $config;
}

function prepare_dir_ca_files(array $config) : array
{
	return prepare_dir_config_files($config, ['ca_key_file', 'ca_cert_file']);
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


function prepare_dir_cert_files(array $config) : array
{
	return prepare_dir_config_files($config, [ 'key_file', 'cert_file' ]);
}

function propose_cert_files(array $config) : array
{
	$config = array_merge($config, propose_ca_files());

	$config['cert_file'] = 'domains/' .$config['domain'] .'.pem';
	$config['key_file'] = 'domains/' .$config['domain'] .'.key';

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
