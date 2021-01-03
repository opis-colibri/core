<?php
// Welsh (cy)
return [
    'forms' => 4,
    'rule' => '(n == 1) ? 0 : (n == 2) ? 1 : (n != 8 && n != 11) ? 2 : 3',
    'func' => static fn (int $n): int => (int)(($n == 1) ? 0 : (($n == 2) ? 1 : (($n != 8 && $n != 11) ? 2 : 3))),
];
