import { useCallback, useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from 'react';
import { ApiError, apiRequest } from '../lib/api';
import type { PaginatedResponse, Sermon, SermonConversation, SermonMessage } from '../types';

const suggestions = [
    'What was the main message of this sermon?',
    'Which Bible passages were discussed?',
    "Explain the pastor's main points in simple language.",
    'What practical lessons can I apply this week?',
    'What questions could I discuss with my small group?',
];

type ConversationResponse = {
    data: SermonConversation | null;
    availability: { available: boolean; reason: string | null };
};

type QuestionResponse = {
    data: { conversation: SermonConversation; question: SermonMessage; answer: SermonMessage };
};

export function AskLumaSection({ sermon }: { sermon: Sermon }) {
    const [messages, setMessages] = useState<SermonMessage[]>([]);
    const [question, setQuestion] = useState('');
    const [availability, setAvailability] = useState<ConversationResponse['availability'] | null>(null);
    const [olderPage, setOlderPage] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [loadingOlder, setLoadingOlder] = useState(false);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');
    const inputRef = useRef<HTMLTextAreaElement>(null);

    const loadMessages = useCallback(async (path?: string, replace = false) => {
        const response = await apiRequest<PaginatedResponse<SermonMessage>>(
            path ?? `/api/v1/sermons/${sermon.id}/conversation/messages`,
        );
        setMessages((current) => replace ? response.data : [...response.data, ...current]);
        setOlderPage(response.links.next);
    }, [sermon.has_transcript, sermon.id, sermon.processing_status]);

    useEffect(() => {
        let active = true;
        setLoading(true);
        setError('');

        Promise.all([
            apiRequest<ConversationResponse>(`/api/v1/sermons/${sermon.id}/conversation`),
            apiRequest<PaginatedResponse<SermonMessage>>(`/api/v1/sermons/${sermon.id}/conversation/messages`),
        ]).then(([conversation, history]) => {
            if (!active) return;
            setAvailability(conversation.availability);
            setMessages(history.data);
            setOlderPage(history.links.next);
        }).catch(() => {
            if (active) setError('The conversation could not be loaded. Please refresh and try again.');
        }).finally(() => {
            if (active) setLoading(false);
        });

        return () => { active = false; };
    }, [sermon.id]);

    const submitQuestion = async (content: string, requestId: string = crypto.randomUUID()) => {
        const trimmedQuestion = content.trim();
        if (!trimmedQuestion || sending || !availability?.available) return;

        setSending(true);
        setError('');
        try {
            const response = await apiRequest<QuestionResponse>(`/api/v1/sermons/${sermon.id}/conversation/messages`, {
                method: 'POST',
                body: JSON.stringify({ question: trimmedQuestion, client_request_id: requestId }),
            });
            setMessages((current) => {
                const withoutAttempt = current.filter((message) => message.client_request_id !== requestId);
                return [...withoutAttempt, response.data.question, response.data.answer];
            });
            setQuestion('');
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'Luma could not answer that question. Please try again.');
            await loadMessages(undefined, true).catch(() => undefined);
        } finally {
            setSending(false);
        }
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        void submitQuestion(question);
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            event.currentTarget.form?.requestSubmit();
        }
    };

    const loadOlder = async () => {
        if (!olderPage || loadingOlder) return;
        setLoadingOlder(true);
        await loadMessages(olderPage).catch(() => setError('Older messages could not be loaded.'));
        setLoadingOlder(false);
    };

    const isAvailable = availability?.available === true;

    return (
        <section className="mt-8 overflow-hidden rounded-[1.75rem] border border-black/10 bg-ink text-white" aria-labelledby="ask-luma-title">
            <div className="grid gap-8 p-6 sm:p-8 lg:grid-cols-[0.72fr_1.28fr] lg:p-10">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.22em] text-acid">Sermon study assistant</p>
                    <h2 id="ask-luma-title" className="mt-3 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Ask Luma</h2>
                    <p className="mt-3 max-w-md text-sm leading-6 text-stone-300">Ask about this sermon’s message, Scripture references, or practical application. Answers use this sermon’s transcript and notes.</p>
                    <div className="mt-6 flex flex-wrap gap-2">
                        {suggestions.map((suggestion) => (
                            <button
                                key={suggestion}
                                type="button"
                                disabled={!isAvailable || sending}
                                onClick={() => void submitQuestion(suggestion)}
                                className="rounded-full border border-white/20 px-3 py-2 text-left text-xs font-medium text-stone-200 transition hover:border-acid hover:text-acid disabled:cursor-not-allowed disabled:opacity-45"
                            >
                                {suggestion}
                            </button>
                        ))}
                    </div>
                    <p className="mt-6 text-xs leading-5 text-stone-400">AI responses are study aids and may contain mistakes. Verify Scripture references and interpretations.</p>
                </div>

                <div className="min-w-0 rounded-2xl bg-paper p-4 text-ink sm:p-6">
                    {loading ? (
                        <p className="py-10 text-center text-sm text-stone-500">Loading conversation…</p>
                    ) : !isAvailable ? (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                            <p className="text-sm font-semibold text-amber-950">Questions are not available yet.</p>
                            <p className="mt-2 text-sm leading-6 text-amber-900">{availability?.reason ?? 'Finish preparing this sermon first.'}</p>
                        </div>
                    ) : (
                        <>
                            <div className="max-h-[32rem] overflow-y-auto pr-1" aria-live="polite" aria-busy={sending}>
                                {olderPage && (
                                    <div className="mb-5 text-center">
                                        <button type="button" disabled={loadingOlder} onClick={() => void loadOlder()} className="text-xs font-semibold text-moss hover:underline disabled:opacity-50">
                                            {loadingOlder ? 'Loading…' : 'Load older messages'}
                                        </button>
                                    </div>
                                )}
                                {messages.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-stone-300 p-6 text-center">
                                        <p className="font-semibold">Start a conversation about this sermon.</p>
                                        <p className="mt-2 text-sm text-stone-500">Choose a suggestion or write your own question below.</p>
                                    </div>
                                ) : (
                                    <div className="grid gap-4">
                                        {messages.map((message) => (
                                            <MessageBubble
                                                key={message.id}
                                                message={message}
                                                retry={() => void submitQuestion(message.content, message.client_request_id ?? crypto.randomUUID())}
                                                retryDisabled={sending}
                                            />
                                        ))}
                                    </div>
                                )}
                                {sending && <p className="mt-4 text-sm font-medium text-moss">Luma is reading the sermon…</p>}
                            </div>

                            <form onSubmit={handleSubmit} className="mt-5 border-t border-black/10 pt-5">
                                <label htmlFor="sermon-question" className="sr-only">Ask a question about this sermon</label>
                                <textarea
                                    ref={inputRef}
                                    id="sermon-question"
                                    value={question}
                                    onChange={(event) => setQuestion(event.target.value)}
                                    onKeyDown={handleKeyDown}
                                    maxLength={2000}
                                    rows={3}
                                    placeholder="Ask a question about this sermon…"
                                    className="w-full resize-y rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-moss"
                                />
                                <div className="mt-3 flex items-center justify-between gap-3">
                                    <span className="text-xs text-stone-500">{question.length.toLocaleString()} / 2,000</span>
                                    <button disabled={sending || question.trim() === ''} className="rounded-full bg-acid px-5 py-2.5 text-sm font-bold text-ink transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50">
                                        {sending ? 'Thinking…' : 'Send question'}
                                    </button>
                                </div>
                            </form>
                        </>
                    )}
                    {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
                </div>
            </div>
        </section>
    );
}

function MessageBubble({ message, retry, retryDisabled }: { message: SermonMessage; retry: () => void; retryDisabled: boolean }) {
    const isUser = message.role === 'user';

    return (
        <article className={`max-w-[92%] rounded-2xl px-4 py-3 ${isUser ? 'ml-auto bg-acid' : 'bg-white ring-1 ring-black/10'}`}>
            <p className="text-xs font-bold uppercase tracking-[0.16em] text-moss">{isUser ? 'You' : 'Luma'}</p>
            <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-stone-800">{message.content}</p>
            {message.sources.length > 0 && (
                <details className="mt-3 border-t border-black/10 pt-3">
                    <summary className="cursor-pointer text-xs font-semibold text-moss">View sermon context ({message.sources.length})</summary>
                    <div className="mt-3 grid gap-3">
                        {message.sources.map((source, index) => (
                            <blockquote key={`${message.id}-${index}`} className="border-l-2 border-acid pl-3 text-xs leading-5 text-stone-600">
                                <strong className="block text-stone-800">{source.section}</strong>
                                {source.excerpt}
                            </blockquote>
                        ))}
                    </div>
                </details>
            )}
            {message.status === 'failed' && isUser && (
                <button type="button" onClick={retry} disabled={retryDisabled} className="mt-3 text-xs font-bold text-red-800 underline disabled:opacity-50">Retry question</button>
            )}
        </article>
    );
}
