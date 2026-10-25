export function NotificationHelp() {
    return (
        <details className="group rounded-2xl border border-black/10 bg-white/60 p-4 sm:p-5">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-ink marker:hidden">
                <span>Not receiving notifications?</span>
                <span aria-hidden="true" className="text-xl font-normal leading-none text-moss transition-transform group-open:rotate-45">+</span>
            </summary>
            <div className="mt-4 grid gap-5 border-t border-black/10 pt-4 text-sm leading-6 text-stone-600 md:grid-cols-2">
                <p className="md:col-span-2">Allowing notifications in your browser may not be enough. Your operating system must also allow Google Chrome to show notifications.</p>
                <section aria-labelledby="notification-help-macos">
                    <h3 id="notification-help-macos" className="font-semibold text-ink">macOS</h3>
                    <ol className="mt-2 list-decimal space-y-2 pl-5">
                        <li>Open <strong className="font-semibold text-stone-700">System Settings → Notifications → Google Chrome</strong>.</li>
                        <li>Enable <strong className="font-semibold text-stone-700">Allow Notifications</strong>.</li>
                        <li>In Chrome, open <strong className="font-semibold text-stone-700">Settings → Privacy and security → Site settings → Notifications</strong>.</li>
                        <li>Ensure notifications are allowed for Luma.</li>
                    </ol>
                </section>
                <section aria-labelledby="notification-help-windows">
                    <h3 id="notification-help-windows" className="font-semibold text-ink">Windows 10/11</h3>
                    <ol className="mt-2 list-decimal space-y-2 pl-5">
                        <li>Open <strong className="font-semibold text-stone-700">Settings → System → Notifications</strong>.</li>
                        <li>Enable notifications and allow notifications from Google Chrome.</li>
                        <li>In Chrome, open <strong className="font-semibold text-stone-700">Settings → Privacy and security → Site settings → Notifications</strong>.</li>
                        <li>Ensure notifications are allowed for Luma.</li>
                        <li>If notifications still do not appear, turn off <strong className="font-semibold text-stone-700">Do Not Disturb</strong>.</li>
                    </ol>
                </section>
            </div>
        </details>
    );
}
