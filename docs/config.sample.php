<?php
namespace Lg\FullPageCache;

$backend = new Backend(
	'127.0.0.1',
	6379,
	.25 // max 250ms for trying to contact redis
);
$config = new Config(
	$backend,
	array(
		'some.domain.com',
		'another.domain.com',
	),
	array(
		Request::PROTOCOL_HTTPS,
	)
);
// mostly for testing with self signed certs
$config->setCacheClientIgnoreSslErrors(true);