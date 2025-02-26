<?php

namespace Nimbly\Resolve\Tests\Fixtures;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
	public function __construct(
		protected array $items = []
	)
	{
	}

	public function has(string $id): bool
	{
		return \array_key_exists($id, $this->items);
	}

	public function get(string $id)
	{
		return $this->items[$id] ?? throw new NotFoundException("Item not found.");
	}
}