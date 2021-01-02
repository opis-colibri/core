<?php
// Norwegian (old code) (no)
return [
    'forms' => 2,
    'rule' => '(n != 1)',
    'func' => static fn (int $n): int => (int)($n != 1),
];
