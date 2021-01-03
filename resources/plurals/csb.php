<?php
// Kashubian (csb)
return [
    'forms' => 3,
    'rule' => '(n == 1) ? 0 : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2',
    'func' => static fn (int $n): int => (int)(($n == 1) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2)),
];
