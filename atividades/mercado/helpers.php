<?php
function precoComDesconto(float $preco, float $descontoPct): float {
    $pct = max(0, min(99, $descontoPct));
    if ($pct <= 0) {
        return round($preco, 2);
    }
    return round($preco * (1 - ($pct / 100)), 2);
}

function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}