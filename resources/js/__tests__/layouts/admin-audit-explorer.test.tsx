import React from 'react';
import { afterEach, describe, expect, it } from 'vitest';

import {
    createLayoutTest,
    createMockComponentRegistryWithBasics,
} from '@core/template-engine/__tests__/utils/layoutTestUtils';

import layout from '../../../layouts/admin/admin_audit_explorer.json';
import routes from '../../../routes/admin.json';
import en from '../../../lang/en.json';
import ko from '../../../lang/ko.json';

type Node = Record<string, any>;

function walk(node: unknown, visit: (value: Node) => void): void {
    if (Array.isArray(node)) {
        node.forEach((item) => walk(item, visit));
        return;
    }
    if (!node || typeof node !== 'object') return;

    const value = node as Node;
    visit(value);
    Object.values(value).forEach((child) => walk(child, visit));
}

function byId(id: string): Node {
    let result: Node | undefined;
    walk(layout, (node) => {
        if (node.id === id) result = node;
    });
    expect(result, `missing layout node: ${id}`).toBeDefined();

    return result as Node;
}

function translationKeys(node: unknown): string[] {
    const keys: string[] = [];
    walk(node, (value) => {
        Object.values(value).forEach((candidate) => {
            if (typeof candidate !== 'string') return;
            for (const match of candidate.matchAll(/\$t:glitter-audit_log\.([\w.]+)/g)) {
                keys.push(match[1]);
            }
        });
    });

    return keys;
}

function hasKey(dictionary: Node, dotted: string): boolean {
    return dotted.split('.').every((segment) => {
        if (!dictionary || !Object.prototype.hasOwnProperty.call(dictionary, segment)) return false;
        dictionary = dictionary[segment];
        return true;
    });
}

describe('admin audit explorer layout', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('renders through the layout engine with supported components', async () => {
        const registry = createMockComponentRegistryWithBasics();
        const Wrapper = ({ children, title, description }: any) => (
            <section><h1>{title}</h1><p>{description}</p>{children}</section>
        );
        registry.register('composite', 'PageHeader', Wrapper);
        registry.register('composite', 'Input', Wrapper);
        registry.register('composite', 'Select', Wrapper);
        registry.register('composite', 'Pagination', Wrapper);

        const test = createLayoutTest(layout as never, {
            componentRegistry: registry as never,
            locale: 'ko',
            translations: {'glitter-audit_log': ko},
        });
        test.mockApi('audits', {
            response: {data: [], pagination: {current_page: 1, last_page: 1, total: 0}},
        });

        await test.render();
        expect(document.body.textContent).toContain('감사 로그 탐색기');
        expect(() => test.assertNoValidationErrors()).not.toThrow();
        test.cleanup();
    });

    it('registers the admin route and both layout permissions', () => {
        expect(routes.routes[0].path).toBe('*/admin/modules/glitter-audit_log/audits');
        expect(routes.routes[0].layout).toBe('admin_audit_explorer');
        expect(layout.permissions).toEqual([
            'glitter-audit_log.audits.read',
            'core.activities.read',
        ]);
    });

    it('maps every API filter and pagination parameter directly from query', () => {
        const source = layout.data_sources[0];
        expect(source.endpoint).toBe('/api/modules/glitter-audit_log/admin/audits');
        expect(Object.keys(source.params)).toEqual([
            'page', 'per_page', 'log_type', 'action', 'actor', 'resource_type',
            'resource_id', 'source_type', 'source_identifier', 'date_from', 'date_to', 'search',
        ]);

        const pagination = byId('audit_pagination');
        expect(JSON.stringify(pagination)).toContain('mergeQuery');
        expect(JSON.stringify(pagination)).toContain('{{$args[0]}}');
    });

    it('resets page on apply and intentionally clears all audit parameters on reset', () => {
        const apply = byId('apply_filters').actions[0].params;
        const reset = byId('reset_filters').actions[0].params;
        expect(apply.mergeQuery).toBe(true);
        expect(apply.query.page).toBe('');
        expect(reset.query).toEqual({
            page: '', log_type: '', action: '', actor: '', resource_type: '', resource_id: '',
            source_type: '', source_identifier: '', date_from: '', date_to: '', search: '',
        });
    });

    it('contains empty/loading states, source labels, and structured detail bindings', () => {
        expect(byId('audit_loading').text).toContain('$t:');
        expect(byId('audit_empty').text).toContain('$t:');
        const serialized = JSON.stringify(layout);
        for (const source of ['core', 'module', 'plugin', 'template', 'unknown']) {
            expect(serialized).toContain(`audit.source.${source}`);
        }
        expect(serialized).toContain('Object.entries(audit.changes');
        expect(serialized).toContain('Object.entries(audit.bulk_changes');
        expect(serialized).toContain('Object.entries(audit.properties');
        expect(serialized).not.toContain('user_agent');
    });

    it('defines every referenced visible translation in Korean and English', () => {
        const keys = translationKeys([layout, routes]);
        expect(keys.length).toBeGreaterThan(0);
        for (const key of keys) {
            expect(hasKey(ko, key), `missing ko key: ${key}`).toBe(true);
            expect(hasKey(en, key), `missing en key: ${key}`).toBe(true);
        }
    });

    it('does not hardcode visible layout text', () => {
        const violations: string[] = [];
        walk(layout, (node) => {
            for (const field of ['text', 'label', 'placeholder', 'title', 'description']) {
                const value = node[field];
                if (typeof value !== 'string') continue;
                if (value.includes('$t:') || value.includes('{{')) continue;
                violations.push(`${field}: ${value}`);
            }
        });
        expect(violations).toEqual([]);
    });
});
