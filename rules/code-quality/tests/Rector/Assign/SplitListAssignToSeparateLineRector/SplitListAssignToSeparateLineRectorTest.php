<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Tests\Rector\Assign\SplitListAssignToSeparateLineRector;

use Iterator;
use Rector\CodeQuality\Rector\Assign\SplitListAssignToSeparateLineRector;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;

final class SplitListAssignToSeparateLineRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return SplitListAssignToSeparateLineRector::class;
    }
}
