<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Definition\Type\SchemaExtension\ValidatorExtension;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

class SchemaBuilder
{
    /** @var TypeResolver */
    private $typeResolver;

    /** @var bool */
    private $enableValidation;

    public function __construct(TypeResolver $typeResolver, bool $enableValidation = false)
    {
        $this->typeResolver = $typeResolver;
        $this->enableValidation = $enableValidation;
    }

    /**
     * @param string      $name
     * @param string|null $queryAlias
     * @param string|null $mutationAlias
     * @param string|null $subscriptionAlias
     * @param string[]    $types
     *
     * @return ExtensibleSchema
     */
    public function create(string  $name, ?string $queryAlias, ?string $mutationAlias = null, ?string $subscriptionAlias = null, array $types = []): ExtensibleSchema
    {
        $this->typeResolver->setCurrentSchemaName($name);
        $query = $this->typeResolver->resolve($queryAlias);
        $mutation = $this->typeResolver->resolve($mutationAlias);
        $subscription = $this->typeResolver->resolve($subscriptionAlias);

        $schema = new ExtensibleSchema($this->buildSchemaArguments($name, $query, $mutation, $subscription, $types));
        $extensions = [];

        if ($this->enableValidation) {
            $extensions[] = new ValidatorExtension();
        }
        $schema->setExtensions($extensions);

        return $schema;
    }

    private function buildSchemaArguments(string $schemaName, Type $query, ?Type $mutation, ?Type $subscription, array $types = []): array
    {
        return [
            'query' => $query,
            'mutation' => $mutation,
            'subscription' => $subscription,
            'typeLoader' => function ($name) use ($schemaName) {
                $this->typeResolver->setCurrentSchemaName($schemaName);

                return $this->typeResolver->resolve($name);
            },
            'types' => function () use ($types, $schemaName) {
                $this->typeResolver->setCurrentSchemaName($schemaName);

                return \array_map([$this->typeResolver, 'getSolution'], $types);
            },
        ];
    }
}
