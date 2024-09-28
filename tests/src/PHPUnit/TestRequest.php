<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/mapper package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Mapper\Tests\PHPUnit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestRequest extends Request
{
    /**
     * @param array<int,string> $argv
     * @param string $testClass
     * @param string $testMethod
     * @param boolean $hasError
     */
    public function __construct(
        private array $argv,
        private string $testClass,
        private string $testMethod,
        private bool $hasError = false,
    ) {
        parent::__construct();
    }

    public function getUri(): string
    {
        return implode(' ', array_map('escapeshellarg', $this->argv));
    }

    public function getMethod(): string
    {
        return 'PHPUNIT';
    }

    public function getResponse(): Response
    {
        return new TestResponse($this->hasError);
    }

    public function getClientIp(): string
    {
        return \sprintf('%s::%s', $this->testClass, $this->testMethod);
    }
}
