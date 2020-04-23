<?php

namespace Rector\Php80\Tests\Rector\Identical\StrStartsWithRector\Fixture;

class NotIdentical
{
    public function run()
    {
        $isNotMatch = substr($haystack, 0, strlen($needle)) !== $needle;
    }
}

?>
-----
<?php

namespace Rector\Php80\Tests\Rector\Identical\StrStartsWithRector\Fixture;

class NotIdentical
{
    public function run()
    {
        $isNotMatch = !str_starts_with($haystack, $needle);
    }
}

?>
