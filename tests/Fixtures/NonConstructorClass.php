<?php

namespace Resolve\Tests\Fixtures;

use DateTime;

class NonConstructorClass
{
	public function getEvent(string $name, DateTime $start_at): array
	{
		return [
			"name" => $name,
			"start_at" => $start_at
		];
	}

	public static function getStaticEvent(string $name, DateTime $start_at): array
	{
		return [
			"name" => $name,
			"start_at" => $start_at
		];
	}
}