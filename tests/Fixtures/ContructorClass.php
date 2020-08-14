<?php

namespace Resolve\Tests\Fixtures;

use DateTime;

class ConstructorClass
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var DateTime
	 */
	protected $start_at;

	public function __construct(string $name, DateTime $start_at)
	{
		$this->name = $name;
		$this->start_at = $start_at;
	}

	public function getEvent(): array
	{
		return [
			"name" => $this->name,
			"start_at" => $this->start_at
		];
	}
}