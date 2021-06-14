<?php

namespace Nimbly\Resolve\Tests\Fixtures;

use DateTime;

class InvokableClass
{
	public function __invoke(string $name, DateTime $start_at): array
	{
		return [
			"name" => $name,
			"start_at" => $start_at
		];
	}
}