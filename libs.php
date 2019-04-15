<?php

function td(...$a)
{
	foreach ($a as $v)
		var_dump($v);
	die('td()');
}
