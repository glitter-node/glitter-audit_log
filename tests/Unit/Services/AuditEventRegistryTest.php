<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Services;

use InvalidArgumentException;
use LogicException;
use Modules\Glitter\AuditLog\Contracts\AuditEventDiscoveryInterface;
use Modules\Glitter\AuditLog\Services\AuditEventRegistry;
use Modules\Glitter\AuditLog\Support\AuditEventDefinitionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuditEventRegistryTest extends TestCase
{
    public function test_queries_definitions_by_key_and_source(): void
    {
        $registry = $this->registry([
            $this->valid('page.created', 'sirsoft-page'),
            $this->valid('board.created', 'sirsoft-board'),
        ]);

        self::assertCount(2, $registry->all());
        self::assertSame('page.created', $registry->find('page.created')?->key);
        self::assertNull($registry->find('missing'));
        self::assertSame(['page.created'], array_map(
            fn ($definition) => $definition->key,
            $registry->forSource('module', 'sirsoft-page'),
        ));
    }

    public function test_duplicate_keys_are_rejected_without_silent_overwrite(): void
    {
        $registry = $this->registry([$this->valid('same.key'), $this->valid('same.key')]);

        $this->expectException(LogicException::class);
        $registry->all();
    }

    #[DataProvider('invalidDefinitions')]
    public function test_invalid_definitions_are_rejected(array $definition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->registry([$definition])->all();
    }

    public static function invalidDefinitions(): array
    {
        $valid = self::definition();

        return [
            'empty key' => [[...$valid, 'key' => '']],
            'invalid source' => [[...$valid, 'source_type' => 'package']],
            'invalid severity' => [[...$valid, 'severity' => 'debug']],
            'missing description' => [[...$valid, 'description_key' => null]],
        ];
    }

    public function test_unknown_category_is_preserved_as_an_extension_string(): void
    {
        $definition = $this->registry([[...$this->valid('custom.key'), 'category' => 'reservation']])->all()[0];

        self::assertSame('reservation', $definition->category);
    }

    public function test_registry_has_no_activity_log_writer_or_database_dependency(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/src/Services/AuditEventRegistry.php');
        $discovery = file_get_contents(dirname(__DIR__, 3).'/src/Support/ActiveExtensionAuditEventDiscovery.php');

        self::assertStringNotContainsString('ActivityLog', $source.$discovery);
        self::assertStringNotContainsString('DB::', $source.$discovery);
        self::assertStringNotContainsString('save(', $source.$discovery);
        self::assertStringNotContainsString('create(', $source.$discovery);
    }

    private function registry(array $definitions): AuditEventRegistry
    {
        $discovery = new class($definitions) implements AuditEventDiscoveryInterface
        {
            public function __construct(private readonly array $items) {}

            public function definitions(): array
            {
                return $this->items;
            }
        };

        return new AuditEventRegistry($discovery, new AuditEventDefinitionFactory);
    }

    private function valid(string $key, string $identifier = 'sample'): array
    {
        return [...self::definition(), 'key' => $key, 'source_identifier' => $identifier];
    }

    private static function definition(): array
    {
        return [
            'key' => 'sample.created',
            'source_type' => 'module',
            'source_identifier' => 'sample',
            'category' => 'content',
            'severity' => 'info',
            'description_key' => 'sample::activity.created',
            'expected_action' => 'created',
        ];
    }
}
