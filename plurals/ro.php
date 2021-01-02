<?php
// Romanian (ro)
return [
    'forms' => 3,
    'rule' => '(n == 1 ? 0 : (n == 0 || (n % 100 > 0 && n % 100 < 20)) ? 1 : 2)',
    'func' => static fn (int $n): int => (int)(($n == 1 ? 0 : (($n == 0 || ($n % 100 > 0 && $n % 100 < 20)) ? 1 : 2))),
];
