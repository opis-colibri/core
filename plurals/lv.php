<?php
// Latvian (lv)
return [
    'forms' => 3,
    'rule' => '(n % 10 == 1 && n % 100 != 11 ? 0 : n != 0 ? 1 : 2)',
    'func' => static fn (int $n): int => (int)(($n % 10 == 1 && $n % 100 != 11 ? 0 : ($n != 0 ? 1 : 2))),
];
