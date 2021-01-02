<?php
// Cornish (kw)
return [
    'forms' => 4,
    'rule' => '(n == 1) ? 0 : (n == 2) ? 1 : (n == 3) ? 2 : 3',
    'func' => static fn (int $n): int => (int)(($n == 1) ? 0 : (($n == 2) ? 1 : (($n == 3) ? 2 : 3))),
];
