import { TriggerEndpointList } from '@canvas/components/settings/TriggerEndpointList';
import { Icon } from '@canvas/components/ui/Icon';
import type { FlowNode } from '@canvas/lib/graph';
import { useWorkflowStore } from '@canvas/store/useWorkflowStore';

type TriggerSettingsProps = {
    node: FlowNode;
};

export function TriggerSettings({ node }: TriggerSettingsProps) {
    const setChatOpen = useWorkflowStore((state) => state.setChatOpen);
    const setFormOpen = useWorkflowStore((state) => state.setFormOpen);
    const closePanel = useWorkflowStore((state) => state.closePanel);

    const config = node.data.config;
    const isChat = node.data.nodeType === 'trigger.chat';
    const isForm = node.data.nodeType === 'trigger.form';

    const openChatTest = () => {
        closePanel();
        setChatOpen(true);
    };

    const openFormTest = () => {
        closePanel();
        setFormOpen(true);
    };

    return (
        <>
            <TriggerEndpointList nodeType={node.data.nodeType} config={config} />

            {isChat && (
                <div className="flex items-center justify-between rounded-2xl border border-primary/20 bg-primary/5 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Icon name="message-circle" className="size-5" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-content-main">پنجره اختصاصی گفتگو</p>
                            <p className="mt-0.5 text-[11px] text-content-muted">
                                تست و ارسال پیام در نوار پایین بوم قرار دارد.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openChatTest}
                        className="flex items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-primary/90"
                    >
                        <Icon name="player-play" className="size-3.5" />
                        <span>باز کردن چت</span>
                    </button>
                </div>
            )}

            {isForm && (
                <div className="flex items-center justify-between rounded-2xl border border-emerald-500/25 bg-emerald-500/8 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-500">
                            <Icon name="clipboard-list" className="size-5" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-content-main">پنجره اختصاصی تست فرم</p>
                            <p className="mt-0.5 text-[11px] text-content-muted">
                                تکمیل و تست زنده فرم در نوار پایین بوم قرار دارد.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openFormTest}
                        className="flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-500"
                    >
                        <Icon name="player-play" className="size-3.5" />
                        <span>باز کردن فرم</span>
                    </button>
                </div>
            )}
        </>
    );
}
