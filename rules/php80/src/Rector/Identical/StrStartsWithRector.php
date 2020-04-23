<?php

declare(strict_types=1);

namespace Rector\Php80\Rector\Identical;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Php80\ValueObject\StrposFuncCallToZero;
use Rector\Php80\ValueObject\SubstrFuncCallToHaystack;

/**
 * @see https://wiki.php.net/rfc/add_str_starts_with_and_ends_with_functions
 *
 * @see https://3v4l.org/RQHB5 for weak compare
 * @see https://3v4l.org/AmLja for weak compare
 *
 * @see \Rector\Php80\Tests\Rector\Identical\StrStartsWithRector\StrStartsWithRectorTest
 */
final class StrStartsWithRector extends AbstractRector
{
    /**
     * @var string
     */
    private const STR_STARTS_WITH = 'str_starts_with';

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Change helper functions to str_starts_with()', [
            new CodeSample(
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        $isMatch = substr($haystack, 0, strlen($needle)) === $needle;

        $isNotMatch = substr($haystack, 0, strlen($needle)) !== $needle;
    }
}
PHP
,
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        $isMatch = str_starts_with($haystack, $needle);

        $isMatch = ! str_starts_with($haystack, $needle);
    }
}
PHP

            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Identical::class, NotIdentical::class];
    }

    /**
     * @param Identical|NotIdentical $node
     */
    public function refactor(Node $node): ?Node
    {
        $substrFuncCallToHaystack = $this->matchSubstrFuncCallToHaystack($node);
        if ($substrFuncCallToHaystack !== null) {
            return $this->refactorSubstrComparedToNode($substrFuncCallToHaystack);
        }

        $strposFuncCallToZero = $this->matchStrposFuncCallToZero($node);
        if ($strposFuncCallToZero !== null) {
            $strposFuncCall = $strposFuncCallToZero->getStrposFuncCall();
            $strposFuncCall->name = new Name(self::STR_STARTS_WITH);
            return $strposFuncCall;
        }

        return $node;
    }

    private function refactorSubstrComparedToNode(SubstrFuncCallToHaystack $substrFuncCallToHaystack): ?Node
    {
        $substrFuncCall = $substrFuncCallToHaystack->getSubstrFuncCall();
        if (! $this->isValue($substrFuncCall->args[1]->value, 0)) {
            return null;
        }

        if (! $this->isFuncCallName($substrFuncCall->args[2]->value, 'strlen')) {
            return null;
        }

        /** @var FuncCall $strlenFuncCall */
        $strlenFuncCall = $substrFuncCall->args[2]->value;
        $needleExpr = $strlenFuncCall->args[0]->value;

        $comparedExpr = $substrFuncCallToHaystack->getHaystackExpr();
        if (! $this->areNodesEqual($needleExpr, $comparedExpr)) {
            return null;
        }

        $strStartsWith = $this->createStrStartsWith($substrFuncCall, $needleExpr);
        if ($substrFuncCallToHaystack->isPositive()) {
            return $strStartsWith;
        }

        return new BooleanNot($strStartsWith);
    }

    private function createStrStartsWith(FuncCall $substrFuncCall, Expr $needleExpr): FuncCall
    {
        $haystackExpr = $substrFuncCall->args[0]->value;

        return $this->createFuncCall(self::STR_STARTS_WITH, [$haystackExpr, $needleExpr]);
    }

    /**
     * @param Identical|NotIdentical $binaryOp
     */
    private function matchSubstrFuncCallToHaystack(BinaryOp $binaryOp): ?SubstrFuncCallToHaystack
    {
        $isPositive = $binaryOp instanceof Identical ? true : false;

        if ($this->isFuncCallName($binaryOp->left, 'substr')) {
            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->left;
            return new SubstrFuncCallToHaystack($funcCall, $binaryOp->right, $isPositive);
        }

        if ($this->isFuncCallName($binaryOp->right, 'substr')) {
            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->right;
            return new SubstrFuncCallToHaystack($funcCall, $binaryOp->left, $isPositive);
        }

        return null;
    }

    /**
     * @param Identical|NotIdentical $binaryOp
     */
    private function matchStrposFuncCallToZero(BinaryOp $binaryOp): ?StrposFuncCallToZero
    {
        $isPositive = $binaryOp instanceof Identical ? true : false;

        if ($this->isFuncCallName($binaryOp->left, 'strpos')) {
            if (! $this->isValue($binaryOp->right, 0)) {
                return null;
            }

            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->left;
            return new StrposFuncCallToZero($funcCall, $isPositive);
        }

        if ($this->isFuncCallName($binaryOp->right, 'strpos')) {
            if (! $this->isValue($binaryOp->left, 0)) {
                return null;
            }

            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->right;
            return new StrposFuncCallToZero($funcCall, $isPositive);
        }

        return null;
    }
}
