<?php

namespace Nimbly\Resolve\Tests\Fixtures;

use DateTime;

class ConstructorClass
{
	public function __construct(
		protected string $name,
		protected DateTime $start_at)
	{
	}

	public function getEvent(): array
	{
		return [
			"name" => $this->name,
			"start_at" => $this->start_at
		];
	}
}