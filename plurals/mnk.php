<?php
// Mandinka (mnk)
return [
    'forms' => 3,
    'rule' => '(n == 0 ? 0 : n == 1 ? 1 : 2)',
    'func' => static fn (int $n): int => (int)(($n == 0 ? 0 : ($n == 1 ? 1 : 2))),
];
