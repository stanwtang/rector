<?php

declare(strict_types=1);

namespace Rector\Php80\ValueObject;

use PhpParser\Node\Expr\FuncCall;

final class StrposFuncCallToZero
{
    /**
     * @var FuncCall
     */
    private $strposFuncCall;

    /**
     * @var bool
     */
    private $isPositive = false;

    public function __construct(FuncCall $strposFuncCall, bool $isPositive)
    {
        $this->strposFuncCall = $strposFuncCall;
        $this->isPositive = $isPositive;
    }

    public function getStrposFuncCall(): FuncCall
    {
        return $this->strposFuncCall;
    }

    public function isPositive(): bool
    {
        return $this->isPositive;
    }
}
