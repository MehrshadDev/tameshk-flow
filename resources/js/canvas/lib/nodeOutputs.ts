import { Handle } from '@canvas/lib/handles';
import { handleLabels } from '@canvas/lib/nodeStyles';
import type { NodeDefinition } from '@canvas/types/graph';

export type ResolvedOutput = {
    id: string;
    label: string;
};

type RawSwitchRule = {
    id?: unknown;
    output_name?: unknown;
    rename_output?: unknown;
};

export function resolveNodeOutputs(
    nodeType: string,
    config: Record<string, unknown> | undefined,
    definition?: NodeDefinition,
): ResolvedOutput[] {
    if (nodeType === 'logic.switch') {
        const rawRules = config?.rules;
        const outputs: ResolvedOutput[] = [];

        if (Array.isArray(rawRules) && rawRules.length > 0) {
            rawRules.forEach((rule: RawSwitchRule, idx: number) => {
                const id =
                    typeof rule?.id === 'string' && rule.id.trim() !== ''
                        ? rule.id.trim()
                        : `case_${idx + 1}`;
                const customLabel =
                    rule?.rename_output && typeof rule?.output_name === 'string'
                        ? rule.output_name.trim()
                        : '';

                outputs.push({
                    id,
                    label: customLabel !== '' ? customLabel : `حالت ${idx + 1}`,
                });
            });
        } else if (config && ('case_1' in config || 'case_2' in config || 'case_3' in config)) {
            (['case_1', 'case_2', 'case_3'] as const).forEach((key, idx) => {
                const val = String(config[key] ?? '').trim();
                if (val !== '' || key === 'case_1') {
                    outputs.push({
                        id: key,
                        label: `حالت ${idx + 1}`,
                    });
                }
            });
        } else {
            outputs.push({ id: 'case_1', label: 'حالت ۱' });
        }

        const fallback = String(config?.fallback_output ?? 'extra');
        if (fallback !== 'none') {
            outputs.push({ id: Handle.Default, label: 'پیش‌فرض' });
        }

        return outputs;
    }

    const staticOutputs = definition?.outputs ?? [Handle.Main];

    return staticOutputs.map((id) => ({
        id,
        label: handleLabels[id] ?? id,
    }));
}
