export function NotificationHelp() {
    return (
        <details className="group rounded-2xl border border-black/10 bg-white/60 p-4 sm:p-5">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-ink marker:hidden">
                <span>How to Enable Notifications</span>
                <span aria-hidden="true" className="text-xl font-normal leading-none text-moss transition-transform group-open:rotate-45">+</span>
            </summary>
            <div className="mt-4 grid gap-5 border-t border-black/10 pt-4 text-sm leading-6 text-stone-600 md:grid-cols-2">
                <section aria-labelledby="notification-help-desktop">
                    <h3 id="notification-help-desktop" className="font-semibold text-ink">Desktop (Chrome, Edge, etc.)</h3>
                    <ol className="mt-2 list-decimal space-y-2 pl-5">
                        <li>Click <strong className="font-semibold text-stone-700">Enable Browser Notifications</strong>.</li>
                        <li>Select <strong className="font-semibold text-stone-700">Allow</strong> when the browser asks for permission.</li>
                        <li>Make sure notifications are enabled in your computer&apos;s system settings.</li>
                        <li>If <strong className="font-semibold text-stone-700">Do Not Disturb</strong> is enabled, notifications may be silenced.</li>
                    </ol>
                </section>
                <section aria-labelledby="notification-help-android">
                    <h3 id="notification-help-android" className="font-semibold text-ink">Android</h3>
                    <ol className="mt-2 list-decimal space-y-2 pl-5">
                        <li>Open LUMA in Google Chrome.</li>
                        <li>Go to <strong className="font-semibold text-stone-700">Account Settings → Daily Reminders</strong>.</li>
                        <li>Tap <strong className="font-semibold text-stone-700">Enable Browser Notifications</strong>.</li>
                        <li>Select <strong className="font-semibold text-stone-700">Allow</strong> when Chrome requests permission.</li>
                        <li>No Home Screen installation is required.</li>
                    </ol>
                </section>
                <section aria-labelledby="notification-help-iphone">
                    <h3 id="notification-help-iphone" className="font-semibold text-ink">iPhone (Safari)</h3>
                    <ol className="mt-2 list-decimal space-y-2 pl-5">
                        <li>Open LUMA in Safari.</li>
                        <li>Tap the Share button and select <strong className="font-semibold text-stone-700">Add to Home Screen</strong>.</li>
                        <li>Open LUMA using the new Home Screen icon.</li>
                        <li>Log in and navigate to <strong className="font-semibold text-stone-700">Account Settings → Daily Reminders</strong>.</li>
                        <li>Tap <strong className="font-semibold text-stone-700">Enable Browser Notifications</strong> and select <strong className="font-semibold text-stone-700">Allow</strong>.</li>
                        <li>iOS 16.4 or later is required for web push notifications.</li>
                    </ol>
                </section>
                <section className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 md:col-span-2" aria-labelledby="notification-help-focus">
                    <h3 id="notification-help-focus" className="font-semibold">Important for iPhone Focus modes</h3>
                    <p className="mt-2">iPhone users: If you use Focus modes such as Personal, Sleep, or Do Not Disturb, notifications from LUMA may be silenced. To allow them, open iPhone Settings → Focus → Select your Focus mode → Apps → Allow Notifications From → Add LUMA. Repeat this for any Focus modes where you want LUMA notifications to appear.</p>
                    <p className="mt-2">Also check <strong className="font-semibold">Settings → Notifications → LUMA</strong> to confirm notifications are enabled.</p>
                </section>
            </div>
        </details>
    );
}
