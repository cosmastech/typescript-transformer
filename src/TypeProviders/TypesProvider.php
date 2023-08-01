<?php

namespace Spatie\TypeScriptTransformer\TypeProviders;

use Spatie\TypeScriptTransformer\Support\TransformedCollection;
use Spatie\TypeScriptTransformer\Support\TypeScriptTransformerLog;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

interface TypesProvider
{
    public function provide(
        TypeScriptTransformerConfig $config,
        TypeScriptTransformerLog $log,
        TransformedCollection $types
    ): void;
}
