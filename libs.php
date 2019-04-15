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

function prepare_dir_config_files(array $config) : array
{
	return prepare_dir_out_files($config,
		bail_files_exist(['ca_cert_cnf_file', 'master_config_file'], $config) );
}

function prepare_dir_ca_files(array $config) : array
{
	return prepare_dir_out_files($config,
		bail_files_exist(['ca_key_file', 'ca_cert_file'], $config) );
}

function propose_config_file(array $config) : array
{
	return array_merge(
		$config,
		[
			'master_config_file' => 'master.cnf',
			'ca_cert_cnf_file' => sprintf('CA/%s-%s-CA.pem.cnf',
				get_current_user(),
				gethostname() ),
		] );
}

function propose_ca_files(array $config) : array
{
	return array_merge(
		$config,
		[
			'ca_key_file' => sprintf('CA/%s-%s-CA.key',
				get_current_user(),
				gethostname() ),
			'ca_cert_file' => sprintf('CA/%s-%s-CA.pem',
				get_current_user(),
				gethostname() ), ] );
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
			escapeshellarg($config['ca_key_file']) ) );

	system(
		sprintf('openssl req -x509 -new -nodes -key %s -sha256 -days 1024 -out %s -config %s',
			escapeshellarg($config['ca_key_file']),
			escapeshellarg($config['ca_cert_file']),
			escapeshellarg($config['ca_cert_cnf_file']) ) );
}


function prepare_dir_cert_files(array $config) : array
{
	return prepare_dir_out_files($config,
		bail_files_exist([ 'csr_file', 'csr_cnf_file', 'ext_file', 'cert_file', 'key_file' ], $config));
}

function propose_cert_files(array $config) : array
{
	$config = array_merge(
		$config,
		propose_ca_files($config),
		[
			'csr_file' => 'domains/' .$config['domain'] .'.csr',
			'csr_cnf_file' => 'domains/' .$config['domain'] .'.csr.cnf',
			'ext_file' => 'domains/' .$config['domain'] .'.v3.ext',
			'cert_file' => 'domains/' .$config['domain'] .'.pem',
			'key_file' => 'domains/' .$config['domain'] .'.key',
		] );

	return $config;
}

function prompt_domain($argv) : array
{
	if (count($argv) === 2)
		$domain = $argv[1];
	else {
		$like = 'example.' .gethostname();
		$domain = readline(sprintf('Local dev domain name (like "%s") ', $like)); }

	return compact('domain');
}

function prepare_ca_cert_cnf_file(array $config) : array
{
	if (!file_exists($config['master_config_file']))
		throw new RuntimeException(sprintf('Master config file "%s" does not exist, run GEN_CONFIG', $config['master_config_file']));

	file_put_contents(
		$config['ca_cert_cnf_file'],
		file_get_contents($config['master_config_file'])
#			.'DN=Local dev CA'
#			.PHP_EOL
		);

	return $config;
}

function ensure_has_ca(array $config) : array
{
	foreach (['ca_key_file', 'ca_cert_file'] as $k)
		if (!file_exists($config[$k]))
			throw new RuntimeException(sprintf('CA file "%s" does not exists, run GEN_CA', $config[$k]));

	return $config;
}

function prepare_config_files(array $config) : array
{
	file_put_contents(
		$config['csr_cnf_file'],
		file_get_contents($config['master_config_file'])
		.'CN=' .$config['domain']
		.PHP_EOL );

	file_put_contents(
		$config['ext_file'],
		<<<EOS
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names

[alt_names]
DNS.1 = $config[domain]

EOS
	);

	return $config;
}

function generate_cert_files(array $config)
{
	prepare_config_files($config);
}
