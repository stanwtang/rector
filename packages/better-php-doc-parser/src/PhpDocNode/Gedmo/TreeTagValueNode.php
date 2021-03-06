<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNode\Gedmo;

use Rector\BetterPhpDocParser\PhpDocNode\AbstractTagValueNode;

final class TreeTagValueNode extends AbstractTagValueNode
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function __toString(): string
    {
        return sprintf('(type="%s")', $this->type);
    }
}
