<?php
// Czech (cs)
return [
    'forms' => 3,
    'rule' => '(n == 1) ? 0 : (n >= 2 && n <= 4) ? 1 : 2',
    'func' => static fn (int $n): int => (int)(($n == 1) ? 0 : (($n >= 2 && $n <= 4) ? 1 : 2)),
];
