<?php

namespace Nimbly\Resolve\Tests\Fixtures;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{}