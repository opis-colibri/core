<?php
// Scottish Gaelic (gd)
return [
    'forms' => 4,
    'rule' => '(n == 1 || n == 11) ? 0 : (n == 2 || n == 12) ? 1 : (n > 2 && n < 20) ? 2 : 3',
    'func' => static fn (int $n): int => (int)(($n == 1 || $n == 11) ? 0 : (($n == 2 || $n == 12) ? 1 : (($n > 2 && $n < 20) ? 2 : 3))),
];
