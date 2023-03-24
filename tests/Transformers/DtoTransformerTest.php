<?php

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\String_;
use Spatie\TypeScriptTransformer\Structures\TypeReferencesCollection;
use function PHPUnit\Framework\assertEquals;
use function Spatie\Snapshots\assertMatchesSnapshot;
use function Spatie\Snapshots\assertMatchesTextSnapshot;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Enum\RegularEnum;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\Dto;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\DtoWithChildren;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\Enum;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\LevelUp\YetAnotherDto;
use Spatie\TypeScriptTransformer\Tests\FakeClasses\Integration\OtherDto;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\TypeProcessors\TypeProcessor;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

beforeEach(function () {
    $config = TypeScriptTransformerConfig::create()
        ->defaultTypeReplacements([
        DateTime::class => 'string',
        ]);

    $this->transformer = new DtoTransformer($config);
});

it('will replace types', function () {
    $type = $this->transformer->transform(
        new ReflectionClass(Dto::class),
        'Typed'
    );

    assertMatchesTextSnapshot($type->toString());
    expect($type->typeReferences->has(Enum::class))->toBeTrue();
    expect($type->typeReferences->has(RegularEnum::class))->toBeTrue();
    expect($type->typeReferences->has(OtherDto::class))->toBeTrue();
    expect($type->typeReferences->has(DtoWithChildren::class))->toBeTrue();
    expect($type->typeReferences->has(YetAnotherDto::class))->toBeTrue();
});

it('a type processor can remove properties', function () {
    $config = TypeScriptTransformerConfig::create();

    $transformer = new class($config) extends DtoTransformer {
        protected function typeProcessors(): array
        {
            $onlyStringPropertiesProcessor = new class implements TypeProcessor {
                public function process(
                    Type $type,
                    ReflectionProperty | ReflectionParameter | ReflectionMethod $reflection,
                    TypeReferencesCollection $typeReferences
                ): ?Type {
                    return $type instanceof String_ ? $type : null;
                }
            };

            return [$onlyStringPropertiesProcessor];
        }
    };

    $type = $transformer->transform(
        new ReflectionClass(Dto::class),
        'Typed'
    );

    assertMatchesTextSnapshot($type->toString());
});

it('will take transform as typescript attributes into account', function () {
    $class = new class {
        #[TypeScriptType('int')]
        public $int;

        #[TypeScriptType('int|bool')]
        public int $overwritable;

        #[TypeScriptType(['an_int' => 'int', 'a_bool' => 'bool'])]
        public $object;

        #[LiteralTypeScriptType('never')]
        public $pure_typescript;

        #[LiteralTypeScriptType(['an_any' => 'any', 'a_never' => 'never'])]
        public $pure_typescript_object;

        public int $regular_type;
    };

    $type = $this->transformer->transform(
        new ReflectionClass($class),
        'Typed'
    );

    assertMatchesSnapshot($type->toString());
});

it('transforms properties to optional ones when using optional attribute', function () {
    $class = new class {
        #[Optional]
        public string $string;
    };

    $type = $this->transformer->transform(
        new ReflectionClass($class),
        'Typed'
    );

    assertMatchesSnapshot($type->toString());
});

it('transforms all properties of a class with optional attribute to optional', function () {
    #[Optional]
    class DummyOptionalDto
    {
        public string $string;
        public int $int;
    }

    $type = $this->transformer->transform(
        new ReflectionClass(DummyOptionalDto::class),
        'Typed'
    );

    assertMatchesSnapshot($type->toString());
});
