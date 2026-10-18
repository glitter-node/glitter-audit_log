<?php

namespace Modules\Glitter\AuditLog\Support;

use App\Contracts\Extension\ModuleManagerInterface;
use App\Contracts\Extension\PluginManagerInterface;
use App\Contracts\Extension\TemplateManagerInterface;
use InvalidArgumentException;
use Modules\Glitter\AuditLog\Contracts\AuditEventDiscoveryInterface;

class ActiveExtensionAuditEventDiscovery implements AuditEventDiscoveryInterface
{
    public function __construct(
        private readonly ModuleManagerInterface $modules,
        private readonly PluginManagerInterface $plugins,
        private readonly TemplateManagerInterface $templates,
    ) {}

    public function definitions(): array
    {
        return [
            ...$this->fromObjects($this->modules->getActiveModules(), 'module'),
            ...$this->fromObjects($this->plugins->getActivePlugins(), 'plugin'),
            ...$this->fromTemplates(),
        ];
    }

    /** @param array<int|string, object> $extensions */
    private function fromObjects(array $extensions, string $sourceType): array
    {
        $definitions = [];

        foreach ($extensions as $extension) {
            if (! method_exists($extension, 'getAuditEvents')) {
                continue;
            }

            $identifier = $extension->getIdentifier();
            foreach ($extension->getAuditEvents() as $definition) {
                $definitions[] = $this->assertSource($definition, $sourceType, $identifier);
            }
        }

        return $definitions;
    }

    private function fromTemplates(): array
    {
        $definitions = [];

        foreach (['admin', 'user'] as $type) {
            $template = $this->templates->getActiveTemplate($type);
            if (! is_array($template) || ! isset($template['audit_events'])) {
                continue;
            }

            foreach ($template['audit_events'] as $definition) {
                $definitions[] = $this->assertSource($definition, 'template', $template['identifier']);
            }
        }

        return $definitions;
    }

    /** @param mixed $definition */
    private function assertSource(mixed $definition, string $type, string $identifier): array
    {
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Audit event declaration must be an array.');
        }

        if (($definition['source_type'] ?? null) !== $type || ($definition['source_identifier'] ?? null) !== $identifier) {
            throw new InvalidArgumentException('Audit event declaration source does not match its active extension.');
        }

        return $definition;
    }
}
