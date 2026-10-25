export type TestNotificationStatus = {
    type: 'success' | 'error';
    message: string;
};

type TestNotificationControlProps = {
    active: boolean;
    disabled: boolean;
    sending: boolean;
    status: TestNotificationStatus | null;
    onSend: () => void;
};

export function TestNotificationControl({ active, disabled, sending, status, onSend }: TestNotificationControlProps) {
    if (!active && !status) {
        return null;
    }

    return (
        <>
            {active && (
                <button
                    type="button"
                    disabled={disabled || sending}
                    onClick={onSend}
                    className="h-11 rounded-xl border border-moss/25 bg-white px-5 text-sm font-semibold text-moss hover:border-moss/40 hover:bg-lime-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {sending ? 'Sending test…' : 'Send test notification'}
                </button>
            )}
            {status && (
                <p
                    role={status.type === 'error' ? 'alert' : 'status'}
                    className={`basis-full rounded-xl px-4 py-3 text-sm font-medium ${status.type === 'error' ? 'bg-red-50 text-red-800' : 'border border-lime-700/20 bg-lime-100 text-moss'}`}
                >
                    {status.message}
                </p>
            )}
        </>
    );
}
