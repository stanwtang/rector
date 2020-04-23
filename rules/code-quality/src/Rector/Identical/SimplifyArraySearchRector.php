<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\Identical;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use Rector\Core\PhpParser\Node\Manipulator\BinaryOpManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\CodeQuality\Tests\Rector\Identical\SimplifyArraySearchRector\SimplifyArraySearchRectorTest
 */
final class SimplifyArraySearchRector extends AbstractRector
{
    /**
     * @var BinaryOpManipulator
     */
    private $binaryOpManipulator;

    public function __construct(BinaryOpManipulator $binaryOpManipulator)
    {
        $this->binaryOpManipulator = $binaryOpManipulator;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Simplify array_search to in_array',
            [
                new CodeSample(
                    'array_search("searching", $array) !== false;',
                    'in_array("searching", $array, true);'
                ),
                new CodeSample('array_search("searching", $array) != false;', 'in_array("searching", $array);'),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Identical::class, NotIdentical::class, Equal::class, NotEqual::class];
    }

    /**
     * @param Identical|NotIdentical|Equal|NotIdentical $node
     */
    public function refactor(Node $node): ?Node
    {
        $matchedNodes = $this->binaryOpManipulator->matchFirstAndSecondConditionNode(
            $node,
            function (Node $node): bool {
                return $this->isFuncCallName($node, 'array_search');
            },
            function (Node $node): bool {
                return $this->isBool($node);
            }
        );

        if ($matchedNodes === null) {
            return null;
        }

        /** @var FuncCall $arraySearchFuncCall */
        /** @var ConstFetch $boolConstFetch */
        [$arraySearchFuncCall, $boolConstFetch] = $matchedNodes;

        $inArrayFuncCall = $this->createFuncCall('in_array', [
            $arraySearchFuncCall->args[0],
            $arraySearchFuncCall->args[1],
        ]);

        if ($this->shouldBeStrict($node)) {
            $inArrayFuncCall->args[2] = new Arg($this->createTrue());
        }

        if ($this->resolveIsNot($node, $boolConstFetch)) {
            return new BooleanNot($inArrayFuncCall);
        }

        return $inArrayFuncCall;
    }

    private function shouldBeStrict(BinaryOp $binaryOp): bool
    {
        return $binaryOp instanceof Identical || $binaryOp instanceof NotIdentical;
    }

    private function resolveIsNot(BinaryOp $binaryOp, ConstFetch $constFetch): bool
    {
        if ($binaryOp instanceof Identical || $binaryOp instanceof Equal) {
            return $this->isFalse($constFetch);
        }

        return $this->isTrue($constFetch);
    }
}
