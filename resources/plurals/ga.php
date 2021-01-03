<?php
// Irish (ga)
return [
    'forms' => 5,
    'rule' => 'n == 1 ? 0 : n == 2 ? 1 : (n > 2 && n < 7) ? 2 : (n > 6 && n < 11) ? 3 : 4',
    'func' => static fn (int $n): int => (int)($n == 1 ? 0 : ($n == 2 ? 1 : (($n > 2 && $n < 7) ? 2 : (($n > 6 && $n < 11) ? 3 : 4)))),
];
