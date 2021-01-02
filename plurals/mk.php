<?php
// Macedonian (mk)
return [
    'forms' => 2,
    'rule' => 'n == 1 || n % 10 == 1 ? 0 : 1',
    'func' => static fn (int $n): int => (int)($n == 1 || $n % 10 == 1 ? 0 : 1),
];
